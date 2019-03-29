<?php

namespace app\modules\welfare\controllers;

use yii\web\Controller;
use Yii;
use app\modules\common\services\OrderService;
use app\modules\common\helpers\Constants;
use app\modules\welfare\helpers\FCCalculation;
use app\modules\welfare\helpers\LogicApplication;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\PayService;

/**
 * ThreeDWel controller for the `welfare` module
 */
class ThreeDWelController extends Controller {

    /**
     * Renders the index view for the module
     * @return string
     */
    public function playOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $boolStr = false, $outType) {
        $post = Yii::$app->request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $welfareCode = $orderData['lottery_code']; //彩种code
        $betCont = $orderData['contents']; //投注内容（玩法，号码）
        $periods = $orderData['periods']; // 期数
        $total = $orderData['total']; //总价
        $multiple = $orderData['multiple']; // 倍数
        $chase = isset($orderData['chase']) ? $orderData['chase'] : 1; // 追期数
        $islimit = isset($orderData['is_limit']) ? $orderData['is_limit'] : 0; // 是否追期限制
        $limAmount = isset($orderData['limit_amount']) ? $orderData['limit_amount'] : ''; // 追期限制
        $countBet = $orderData['count_bet']; // 注数
        $random = isset($orderData['is_random']) ? $orderData['is_random'] : 0;
        $isBetAdd = 0;
        $remark = isset($post['remark']) ? $post['remark'] : '';
//        if(isset($orderData['end_time'])){
            $endTime = date('Y-m-d H:i:s', $orderData['end_time'] / 1000);
//        }  else {
//            $endTime = '';
//        }
        $count = 0;
        $playCode = [];
        $betNums = '';
        $playName = [];
        $playParam = Constants::TD_PLAYNAME;
        $price = Constants::PRICE;
        $lotArr = Constants::LOTTERY;
        $abbArr = Constants::LOTTERY_ABBREVI;
        $lotName = $lotArr[$welfareCode];
        $abbName = $abbArr[$welfareCode];
        $format = 'Y-m-d H:i:s';
        $createTime = date($format);
        foreach ($betCont as $val) {
            $fun = 'tdFun_' . $val['play'];
            $noteNums = FCCalculation::$fun($val['nums']);
            $count += $noteNums;
            $playCode[] = $val['play'];
            $playName[] = $playParam[$val['play']];
            $betNums .= $val['nums'] . '^';
        }

        if ($countBet != $count) {
            return [
                "code" => 2,
                "msg" => "投注内容注数不对应！"
            ];
        }

        $singleCost = floatval($count) * $price * $multiple;
        $cost = floatval($count) * $price * $multiple * $chase;
        if ($total != $cost) {
            return [
                "code" => 2,
                "msg" => "投注总金额错误！"
            ];
        }

        $insert = ['lottery_type' => $abbName, 'lottery_name' => $lotName, 'lottery_id' => $welfareCode, 'play_code' => implode(',', $playCode), 'play_name' => implode(',', $playName), 'periods' => $periods,
            'cust_no' => $custNo, "store_id" => $storeId, 'source_id' => $sourceId, 'bet_val' => $betNums, 'agent_id' => '0', 'periods_total' => $chase, 'bet_double' => $multiple, 'bet_money' => $singleCost, "source" => $source, 'count' => $count,
            'is_bet_add' => $isBetAdd, 'is_random' => $random, 'win_limit' => $limAmount, 'is_limit' => $islimit, 'create_time' => $createTime, 'end_time' => $endTime, 'user_id' => $userId, 'store_no' => $storeCode, 'auto_type' => $outType, 'remark' => $remark];
        return OrderService::selfDoLotterOrder($insert,$boolStr);
        /*$result = OrderService::insertOrder($insert, $boolStr);
        if ($result["error"] === true) {
            if ($source != 6) {
                $paySer = new PayService();
                $paySer->productPayRecord($custNo, $result["orderCode"], 1, 1, $singleCost, 1);
            }
            return [
                "code" => 600,
                "msg" => "下注成功！",
                "result" => ["lottery_order_code" => $result["orderCode"]]
            ];
        } else {
            return [
                "code" => 2,
                "msg" => "下注失败！"
            ];
        }*/
    }

    /**
     * 投注验证
     * @return array
     */
    public function playVerification() {
        $request = Yii::$app->request;
        $betCont = $request->post('contents'); //投注内容（玩法，号码）
        $total = $request->post('total'); //总价
        $multiple = $request->post('multiple'); // 倍数
        $chase = $request->post('chase', 1); // 追期数
        $countBet = $request->post('count_bet'); // 注数

        $count = 0;
        $price = Constants::PRICE;
        $playCode = [];
        $betNums = '';
        $playName = [];

        $playParam = Constants::TD_PLAYNAME;
        foreach ($betCont as $val) {
            $fun = 'tdFun_' . $val['play'];
            $noteNums = FCCalculation::$fun($val['nums']);
            $count += $noteNums;
            $playCode[] = $val['play'];
            $playName[] = $playParam[$val['play']];
            $betNums .= $val['nums'] . '^';
        }

        if ($countBet != $count) {
            return [
                "code" => 2,
                "msg" => "投注失败，请重新选择号码！"
            ];
        }

        $cost = floatval($count) * $price * $multiple * $chase;
        if ($total != $cost) {
            return [
                "code" => 2,
                "msg" => "投注失败，请重新选择号码！"
            ];
        }

        return [
            "code" => 0,
            "msg" => "投注信息正确！",
            "data" => [
                "lottery_name" => '福彩3D',
                "play_name" => implode(',', $playName),
                "play_code" => implode(',', $playCode),
                "bet_val" => $betNums
            ]
        ];
    }

    public function productSuborder($model) {
        $infos = [];
//        $model = LotteryOrder::findOne(["lottery_order_id" => $orderId]);
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
        $content = trim($model->bet_val, "^");
        $noteNums = explode("^", $content);
        $playCodes = explode(",", $model->play_code);
        $playNames = explode(",", $model->play_name);
        $order = [];
        $n = 0;
        foreach ($noteNums as $key => $val) {
            $numsArr = Commonfun::noteNums($val);
            $fun = "tdNote_" . $playCodes[$key];
            $ret = LogicApplication::$fun($numsArr);
            foreach ($ret as $k => $v) {
                $order[$n]["bet_val"] = $v;
                $order[$n]["play_code"] = $playCodes[$key];
                $order[$n]["play_name"] = $playNames[$key];
                $n++;
            }
        }

        $infos["content"] = $order;
        $result = OrderService::insertDetail($infos);
        if ($result["error"] === true) {
            return [
                "code" => 0,
                "msg" => "操作成功"
            ];
        } else {
            return [
                "code" => 2,
                "msg" => "操作失败",
                "err" => $result
            ];
        }
    }

}