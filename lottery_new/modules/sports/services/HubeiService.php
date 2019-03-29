<?php

namespace app\modules\sports\services;

use Yii;
use app\modules\common\helpers\Constants;
use app\modules\common\services\OrderService;
use app\modules\sports\helpers\HBConstants;
use app\modules\sports\helpers\HBCalculation;

class HubeiService {

    /**
     * 广东11选5，用户下订单
     * return json
     */
    public function playOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $boolStr = false, $outType) {
         $post = Yii::$app->request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $hbCode = $orderData['lottery_code']; //彩种code
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
        $playParam = HBConstants::HB11X5_PLAYNAME;
        $price = Constants::PRICE;
        $lotArr = Constants::LOTTERY;
        $abbArr = Constants::LOTTERY_ABBREVI;
        $renP = HBConstants::REN_PUTONG;
        $renD = HBConstants::REN_DANTUO;
        $qianO = HBConstants::QIAN_ONE_ZHI;
        $qianTZ = HBConstants::QIAN_TWO_ZHI;
        $qianSZ = HBConstants::QIAN_THREE_ZHI;
        $qianZP = HBConstants::QIAN_ZU_PUTONG;
        $qianZD = HBConstants::QIAN_ZU_DANTUO;
        $ballNums = HBConstants::HB11X5_NUMS;
        $lotName = $lotArr[$hbCode];
        $abbName = $abbArr[$hbCode];
        $format = 'Y-m-d H:i:s';
        $createTime = date($format);
         foreach ($betCont as $key => $val) {
            if(in_array($val['play'], $renP)){
                $noteNums = HBCalculation::hb115Fun_RenPu($val['nums'], $ballNums[$val['play']]);
            }elseif (in_array($val['play'], $renD)) {
                $noteNums = HBCalculation::hb115Fun_RenDan($val['nums'], $ballNums[$val['play']]);
            }  elseif (in_array($val['play'], $qianO)) {
                $noteNums = HBCalculation::hb115Fun_QianOneZhi($val['nums']);
            }  elseif (in_array($val['play'], $qianTZ)) {
                $noteNums = HBCalculation::hb115Fun_QianTwoZhi($val['nums']);
            }  elseif (in_array($val['play'], $qianSZ)) {
                $noteNums = HBCalculation::hb115Fun_QianThreeZhi($val['nums']);
            }elseif (in_array($val['play'], $qianZP)) {
                $noteNums = HBCalculation::hb115Fun_QianZuPu($val['nums'], $ballNums[$val['play']]);
            }elseif (in_array($val['play'], $qianZD)) {
                $noteNums = HBCalculation::hb115Fun_QianZuDan($val['nums'], $ballNums[$val['play']]);
            } else {
                return ['code' => 2, 'msg' => '玩法不存在'];
            }
            if ($noteNums == false) {
                return ["code" => 2,"msg" => "投注内容格式错误！"];
            }
            $playCode[$key] = $val['play'];
            $playName[$key] = $playParam[$val['play']];
            $count += $noteNums;
            $betNums .= $val['nums'] . '^';
        }
        if ($countBet != $count) {
            return ["code" => 2,"msg" => "投注内容注数不对应！"];
        }
        $singleCost = $count * $price * $multiple;
        $cost = $singleCost * $chase;
        if ($total != $cost) {
            return ["code" => 2, "msg" => "投注总金额错误！"
            ];
        }

         $insert = ['lottery_type' => $abbName, 'lottery_name' => $lotName, 'lottery_id' => $hbCode, 'play_code' => implode(',', $playCode), 'play_name' => implode(',', $playName), 'periods' => $periods,
            'cust_no' => $custNo, "store_id" => $storeId, 'source_id' => $sourceId, 'bet_val' => $betNums, 'agent_id' => '0', 'periods_total' => $chase, 'bet_double' => $multiple, 'bet_money' => $singleCost, "source" => $source, 'count' => $count,
            'is_bet_add' => $isBetAdd, 'is_random' => $random, 'win_limit' => $limAmount, 'is_limit' => $islimit, 'create_time' => $createTime, 'end_time' => $endTime, 'user_id' => $userId, 'store_no' => $storeCode, 'auto_type' => $outType, 'remark' => $remark];
         return OrderService::selfDoLotterOrder($insert,$boolStr);
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

        $playParam = HBConstants::HB11X5_PLAYNAME;
        $renP = HBConstants::REN_PUTONG;
        $renD = HBConstants::REN_DANTUO;
        $qianO = HBConstants::QIAN_ONE_ZHI;
        $qianTZ = HBConstants::QIAN_TWO_ZHI;
        $qianSZ = HBConstants::QIAN_THREE_ZHI;
        $qianZP = HBConstants::QIAN_ZU_PUTONG;
        $qianZD = HBConstants::QIAN_ZU_DANTUO;
        $ballNums = HBConstants::HB11X5_NUMS;
        foreach ($betCont as $val) {
            if(in_array($val['play'], $renP)){
                $noteNums = HBCalculation::hb115Fun_RenPu($val['nums'], $ballNums[$val['play']]);
            }elseif (in_array($val['play'], $renD)) {
                $noteNums = HBCalculation::hb115Fun_RenDan($val['nums'], $ballNums[$val['play']]);
            }  elseif (in_array($val['play'], $qianO)) {
                $noteNums = HBCalculation::hb115Fun_QianOneZhi($val['nums']);
            }  elseif (in_array($val['play'], $qianTZ)) {
                $noteNums = HBCalculation::hb115Fun_QianTwoZhi($val['nums']);
            }  elseif (in_array($val['play'], $qianSZ)) {
                $noteNums = HBCalculation::hb115Fun_QianThreeZhi($val['nums']);
            }elseif (in_array($val['play'], $qianZP)) {
                $noteNums = HBCalculation::hb115Fun_QianZuPu($val['nums'], $ballNums[$val['play']]);
            }elseif (in_array($val['play'], $qianZD)) {
                $noteNums = HBCalculation::hb115Fun_QianZuDan($val['nums'], $ballNums[$val['play']]);
            } else {
                return ['code' => 2, 'msg' => '玩法不存在'];
            }
            $count += $noteNums;
            $playCode[] = $val['play'];
            $playName[] = $playParam[$val['play']];
            $betNums .= $val['nums'] . '^';
        }

        if ($countBet != $count) {
            return ["code" => 2, "msg" => "投注失败，请重新选择号码！"];
        }
        $singleCost = $count * $price * $multiple;
        $cost = $singleCost * $chase;
        if ($total != $cost) {
            return ["code" => 2, "msg" => "投注失败，请重新选择号码！"];
        }

        return ["code" => 0, "msg" => "投注信息正确！", "data" => ["lottery_name" => '双色球', "play_name" => implode(',', $playName), "play_code" => implode(',', $playCode), "bet_val" => $betNums]];
    }

    /**
     * 生成详细投注单
     * auther 咕啦 zyl
     * @param model $model
     * @return json
     */
    public function productSuborder($model) {
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
        $infos['cust_no'] = $model->cust_no;
        $infos['count'] = $model->count;
        $content = trim($model->bet_val, '^');
        $noteNums = explode('^', $content);
        $playCodes = explode(',', $model->play_code);
        $playNames = explode(',', $model->play_name);
        $order = [];
        $n = 0;
        foreach ($noteNums as $key => $nums) {
            $fun = 'hb115Note_' . $playCodes[$key];
            $ret = HBCalculation::$fun($nums);
            foreach ($ret as $k => $v) {
                $order[$n]['bet_val'] = $v;
                $order[$n]['play_code'] = $playCodes[$key];
                $order[$n]['play_name'] = $playNames[$key];
                $n++;
            }
        }

        $infos['content'] = $order;
        $result = OrderService::insertDetail($infos);
        if ($result['error'] === true) {
            return ['code' => 0, 'msg' => "操作成功"];
        } else {
            return ['code' => 2, 'msg' => '操作失败','err' => $result];
        }
    }
}
