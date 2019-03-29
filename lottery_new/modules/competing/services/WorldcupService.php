<?php

namespace app\modules\competing\services;

use app\modules\competing\models\WorldcupChp;
use app\modules\competing\models\WorldcupFnl;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\helpers\Constants;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\OrderService;
use app\modules\common\models\LotteryOrder;
use app\modules\common\services\PayService;
use app\modules\common\models\BettingDetail;
use app\modules\competing\models\WorldcupSchedule;

class WorldcupService {

    /**
     * 获取冠军预猜球队
     * @return type
     */
    public static function getWcChp() {
        $field = ['open_mid', 'team_code', 'team_name', 'team_img', 'team_odds', 'status', 'team_chance'];
        $chpData = WorldcupChp::find()->select($field)->where(['status' => 1])->orderBy('open_mid')->asArray()->all();
        return $chpData;
    }

    /**
     * 获取冠亚军预猜球队
     * @return type
     */
    public static function getWcFnl() {
        $field = ['open_mid', 'home_code', 'home_name', 'home_img', 'visit_code', 'visit_name', 'visit_img', 'team_odds', 'team_chance', 'status'];
        $fnlData = WorldcupFnl::find()->select($field)->where(['status' => 1])->orderBy('open_mid')->asArray()->all();
        return $fnlData;
    }

    /**
     * 获取筛选球队
     * @return type
     */
    public static function getTeam($lotteryCode) {
        if($lotteryCode == '301201') {
            $field = ['team_code', 'team_name'];
            $teamData = WorldcupChp::find()->select($field)->where(['status' => 1])->asArray()->all();
        }  else {
            $feild = ['home_code', 'home_name', 'visit_code', 'visit_name'];
            $data = WorldcupFnl::find()->select($feild)->where(['status' => 1])->asArray()->all();
            $tmpData = [];
            foreach ($data as $val) {
                if(!array_key_exists($val['home_code'], $tmpData)){
                    $tmpData[$val['home_code']] = ['team_code' => $val['home_code'], 'team_name' => $val['home_name']];
                }
                if(!array_key_exists($val['visit_code'], $tmpData)){
                    $tmpData[$val['visit_code']] = ['team_code' => $val['visit_code'], 'team_name' => $val['visit_name']];
                }
            }
            foreach ($tmpData as $v){
                $teamData[] = $v;
            }
        }
        
        return $teamData;
    }

    /**
     * 冠亚军竞猜投注
     * @param type $custNo
     * @param type $userId
     * @param type $storeId
     * @param type $storeCode
     * @param type $source
     * @param type $sourceId
     * @param type $outType
     * @return type
     */
    public static function playOrder($custNo, $userId, $storeId, $storeCode, $source, $sourceId = '', $outType) {
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $lotteryName = Constants::LOTTERY;
        $request = \Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $lotteryCode = $orderData["lottery_code"];
        $remark = isset($post['remark']) ? $post['remark'] : '';
        $periods = '2018'; // 期数
        $betNums = $orderData['contents']; // 投注内容
        $total = $orderData['total']; //总价
        $multiple = $orderData['multiple']; // 倍数
        $countBet = $orderData['count_bet']; // 注数
        $playNum = $betNums["nums"];
        $endTime = date('Y-m-d H:i:s', $orderData['end_time'] / 1000);
        $numArr = explode(',', $playNum);
        $combination = Commonfun::getCombination_array($numArr, 1);
        if (!is_array($combination)) {
            return ['code' => 2, 'msg' => $combination];
        }
        $count = count($combination);
        if ($countBet != $count) {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $betMoney = Constants::PRICE * $count;

        if ($multiple >= 1) {
            $betMoney *=$multiple;
        } else {
            return ["code" => 2, "msg" => "投注加倍参数错误！"];
        }
        $oddsArr = self::getOdds($lotteryCode, $numArr);
        $checkMid = [];
        foreach ($oddsArr as $key => $item) {
            $odds[$key] = $item['team_odds'];
            $checkMid[] = $key;
        }
        if (count($checkMid) != count($numArr)) {
            return ['code' => 109, 'msg' => '投注失败,超时,含有已停售内容，请重新投注'];
        }
        $odds = json_encode($odds, JSON_FORCE_OBJECT);
        $totalMoney = $betMoney;
        if ($total != $totalMoney) {
            return ["code" => 2, "msg" => "投注总金额错误！"];
        }
        $insert = ["lottery_type" => $lotteryType[$lotteryCode], "play_code" => '1', "play_name" => '单场固定', "lottery_id" => $lotteryCode, "lottery_name" => $lotteryName[$lotteryCode],
            "periods" => $periods, "cust_no" => $custNo, "store_id" => $storeId, "source_id" => $sourceId, "agent_id" => "0", "bet_val" => $playNum . "^", "bet_double" => $multiple, "is_bet_add" => 0,
            "bet_money" => $betMoney, "source" => $source, "count" => $count, "periods_total" => 1, "is_random" => 0, "win_limit" => 0, "is_limit" => 0, "odds" => $odds, "end_time" => $endTime, 'user_id' => $userId,
            'store_no' => $storeCode, 'auto_type' => $outType, 'remark' => $remark];
        return OrderService::selfDoLotterOrder($insert, false);
    }

    /**
     * 任选生成详情单
     * @auther GL zyl
     * @param type $model
     */
    public static function productSuborder($model) {
        $infos = [];
        $infos["agent_id"] = $model->agent_id;
        $infos["bet_double"] = $model->bet_double;
        $infos["bet_money"] = $model->bet_money;
        $infos["is_bet_add"] = $model->is_bet_add;
        $infos["lottery_id"] = $model->lottery_id;
        $infos["lottery_name"] = $model->lottery_name;
        $infos["lottery_order_code"] = $model->lottery_order_code;
        $infos["lottery_order_id"] = $model->lottery_order_id;
        $infos["opt_id"] = $model->opt_id;
        $infos["periods"] = $model->periods;
        $infos["status"] = $model->status;
        $infos["cust_no"] = $model->cust_no;
        $infos["count"] = $model->count;
        $odds = json_decode($model->odds, true);

        $contents = trim($model->bet_val, "^");
        $numArr = explode(',', $contents);

        $orderArr = [];
        $n = 0;
        foreach ($numArr as $val) {
            $orderArr[$n]['bet_val'] = $val;
            $orderArr[$n]['play_code'] = '1';
            $orderArr[$n]['play_name'] = '单场固定';
            $orderArr[$n]['odds'] = $odds[$val];
            $n++;
        }
        $infos['content'] = $orderArr;
        $result = OrderService::insertDetail($infos);
        if ($result['error'] === true) {
            return ['code' => 0, 'msg' => '操作成功'];
        } else {
            return [
                'code' => 2, 'msg' => '操作失败', 'err' => $result];
        }
    }

    /**
     * 获取赔率
     * @param type $lotteryCode
     * @param type $mids
     */
    public static function getOdds($lotteryCode, $mids) {
        if ($lotteryCode == '301201') {
            $odds = WorldcupChp::find()->select(['open_mid', 'team_odds'])->where(['status' => 1])->andWhere(['in', 'open_mid', $mids])->indexBy('open_mid')->asArray()->all();
        } elseif ($lotteryCode == '301301') {
            $odds = WorldcupFnl::find()->select(['open_mid', 'team_odds'])->where(['status' => 1])->andWhere(['in', 'open_mid', $mids])->indexBy('open_mid')->asArray()->all();
        }
        return $odds;
    }

    /**
     * 投注验证
     * @param string $lotteryCode
     * @return array
     */
    public static function WorldcupVerification($lotteryCode) {
        $lotteryName = Constants::LOTTERY;
        $request = Yii::$app->request;
        $orderData = $request->post();
        $betNums = $orderData['contents']; // 投注内容
        $total = $orderData['total']; //总价
        $multiple = $orderData['multiple']; // 倍数
        $countBet = $orderData['count_bet']; // 注数

        $playNum = $betNums["nums"];
        $numArr = explode(',', $playNum);
        $combination = Commonfun::getCombination_array($numArr, 1);
        if (!is_array($combination)) {
            return ['code' => 2, 'msg' => $combination];
        }
        $count = count($combination);
        if ($countBet != $count) {
            return [
                "code" => 2,
                "msg" => "投注内容注数不对应！"
            ];
        }
        $betMoney = Constants::PRICE * $count;
        $oddsArr = self::getOdds($lotteryCode, $numArr);
        $checkMid = [];
        foreach ($oddsArr as $key => $item) {
            $checkMid[] = $key;
        }
        if ($checkMid != $numArr) {
            return ['code' => 109, 'msg' => '投注失败,超时,含有已停售内容，请重新投注'];
        }
        if ($multiple >= 1) {
            $betMoney *=$multiple;
        } else {
            return [
                "code" => 2,
                "msg" => "投注加倍参数错误！"
            ];
        }

        $totalMoney = $betMoney;
        if ($total != $totalMoney) {
            return [
                "code" => 2,
                "msg" => "投注总金额错误！"
            ];
        }
        return [
            "code" => 0,
            "msg" => "投注信息正确！",
            "data" => [
                "lottery_name" => $lotteryName[$lotteryCode],
                "play_name" => '1',
                "play_code" => '单场固定',
                "bet_val" => ($playNum . "^"),
                "limit_time" => '2018-07-16 00:00:00'
            ]
        ];
    }

    /**
     * 计算注数
     * @auther GL zyl
     * @param string $lotteryCode 彩种编号
     * @param array $contents 投注内容
     * @return array
     */
    public static function calculationCount($lotteryCode, $contents) {
        $odds = [];
        $lottery = Lottery::findOne(["lottery_code" => $lotteryCode]);
        if ($lottery->status == "0") {
            return ["code" => 109, "msg" => "投注失败，此彩种已经停止投注，请选择其他彩种进行投注"];
        }
        $playNum = $contents["nums"];
        $numArr = explode(',', $playNum);
        $combination = Commonfun::getCombination_array($numArr, 1);
        if (!is_array($combination)) {
            return ['code' => 2, 'msg' => $combination];
        }
        $count = count($combination);
        $oddsArr = self::getOdds($lotteryCode, $numArr);
        $checkMid = [];
        foreach ($oddsArr as $key => $item) {
            $odds[$key] = $item['team_odds'];
            $checkMid[] = $key;
        }
        if ($checkMid != $numArr) {
            return ['code' => 109, 'msg' => '投注失败,超时,含有已停售内容，请重新投注'];
        }
        return ["code" => 0, "msg" => "获取成功", "result" => $count, "odds" => $odds, "limit_time" => '2018-07-16 00:00:00', 'max_time' => '2018-07-16 00:00:00'];
    }

    /**
     * 获取冠亚军竞猜订单详情
     * @param type $orderCode
     * @param type $custNo
     * @param type $orderId
     * @return type
     */
    public static function getOrder($orderCode, $custNo = '', $orderId = '') {
        $where = [];
        $status = [
            '1' => '未支付',
            '2' => '处理中',
            '3' => '待开奖',
            '4' => '中奖',
            '5' => '未中奖',
            "6" => "出票失败",
            '9' => '过点撤销',
            '10' => '拒绝出票',
            '11' => '等待出票'
        ];
        $where['lottery_order.lottery_order_code'] = $orderCode;
        if (!empty($custNo)) {
            $where['lottery_order.cust_no'] = $custNo;
        }
        if (!empty($orderId)) {
            $where['lottery_order.lottery_order_id'] = $orderId;
        }
        
        $field = ['bet_val', 'lottery_order.lottery_id', 'lottery_order.lottery_name', 'bet_money', 'lottery_order_code', 'lottery_order.create_time', 'lottery_order_id', 'lottery_order.status', 'win_amount', 'play_code', 'play_name', 'bet_double',
            'count', 'lottery_order.periods', 's.store_name', 's.store_code', 's.telephone phone_num', 'odds', 'l.lottery_pic', 'lottery_order.deal_status', 'refuse_reason'];
        $data = LotteryOrder::find()->select($field)
                ->leftJoin('store as s', 's.store_code = lottery_order.store_no and s.status = 1')
                ->leftJoin('lottery l', 'l.lottery_code = lottery_order.lottery_id')
                ->where($where)
                ->asArray()
                ->one();
        if (empty($data)) {
            return ['code' => 109, 'data' => '该订单有误，请重新查看'];
        }
        $betArr = explode(',', trim($data['bet_val'], '^'));
        if($data['lottery_id'] == '301201') {
            $field2 = ['open_mid', 'team_name', 'status'];
            $teamData = WorldcupChp::find()->select($field2)->where(['in', 'open_mid', $betArr])->asArray()->all();
        }elseif ($data['lottery_id'] == '301301') {
            $str = "CONCAT(home_name, '-' , visit_name) team_name";
            $field2 = ['open_mid', $str, 'status'];
            $teamData = WorldcupFnl::find()->select($field2)->where(['in', 'open_mid', $betArr])->asArray()->all();
        }
        $oddsArr = json_decode($data['odds'], true);
        if (!empty($teamData)) {
            foreach ($teamData as $val) {
                if ($data['status'] == 0 || $data['status'] == 1) {
                    $scheResult = '';
                } else {
                    $scheResult = $val['status'];
                }
                $data['betval_arr'][] = ['open_mid' => $val['open_mid'], 'team_name' => $val['team_name'], 'result' => $val['status'], 'odds' => $oddsArr[$val['open_mid']]];
            }
        } else {
            $data['betval_arr'] = [];
        }
        $data['status_name'] = $status[$data['status']];
        $data['discount_data'] = PayService::getDiscount(['order_code' => $orderCode]); //优惠信息
        return ['code' => 600, 'result' => $data];
    }
    
    /**
     * 出票更新赔率
     * @auther GL zyl
     * @param type $lotteryCode 彩种编号
     * @param type $orderId 订单ID
     * @param type $bet 投注内容
     * @return type
     */
    public static function updateOdds($lotteryCode, $orderId, $bet) {
        $odds = [];
        $betNums = explode(",", trim($bet, '^'));
        $oddsArr = self::getOdds($lotteryCode, $betNums);
        foreach ($oddsArr as $key => $item) {
            $odds[$key] = $item['team_odds'];
        }
        $order = LotteryOrder::findOne(['lottery_order_id' => $orderId]);
        $order->odds = json_encode($odds, JSON_FORCE_OBJECT);
        $order->save();
        $detail = BettingDetail::find()->select(['betting_detail_id', 'bet_val'])->where(['lottery_order_id' => $orderId])->asArray()->all();
        $updetail = '';
        foreach ($detail as $val) {
            $oddsAmount = $odds[$val['bet_val']];
            $updetail .= "update betting_detail set odds = {$oddsAmount} where betting_detail_id = {$val['betting_detail_id']} and lottery_order_id = {$orderId};";
            BettingDetail::addQueSync(BettingDetail::$syncUpdateType,'odds,fen_json',['betting_detail_id' => $val['betting_detail_id'], 'lottery_order_id' => $orderId]);
        }
        $db = \Yii::$app->db;
        $updateId = $db->createCommand($updetail)->execute();
        if ($updateId == false) {
            return ['code' => 109, 'msg' => '详情表修改失败'];
        }
        return ['code' => 600, 'msg' => '赔率修改成功'];
    }
    
    /**
     * 获取世界杯基础赛程
     * @return type
     */
    public static function getScheduleInfo() {
        $field = ['game_city', 'game_field', 'game_level_id', 'game_level_name', 'schedule_date', 'start_time', 'sort', 'group_id', 'group_name', 'home_team_name', 'home_img', 'visit_team_name', 'visit_img', 'bifen'];
        $info = WorldcupSchedule::find()->select($field)->asArray()->all();
        $infoList = [];
        foreach ($info as $in) {
            $gameDate = $in['schedule_date'];
            if (array_key_exists($gameDate, $infoList)) {
                $infoList[$gameDate]['game'][] = $in;
                $infoList[$gameDate]['field_num'] += 1;
            } else {
                $infoList[$gameDate]['game'][] = $in;
                $infoList[$gameDate]['field_num'] = 1;
                $infoList[$gameDate]['game_date'] = date('Y-m-d', strtotime($gameDate));
                $infoList[$gameDate]['game_level'] = $in['game_level_id'];
                $infoList[$gameDate]['game_level_name'] = $in['game_level_name'];
            }
        }
//        $infoList = array_values($list);
        return $infoList;
    }

}
