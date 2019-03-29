<?php

namespace app\modules\numbers\services;

use Yii;
use app\modules\common\helpers\Constants;
use app\modules\common\services\OrderService;
use app\modules\numbers\helpers\SzcConstants;
use app\modules\numbers\helpers\EszcCalculation;

class EszcService {

    public static function playOrder($custNo, $userId, $storeId, $storeCode, $source = 1, $sourceId = "", $boolStr = false, $outType) {
        $post = Yii::$app->request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $lotteryCode = $orderData['lottery_code']; //彩种code
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
        $endTime = date('Y-m-d H:i:s', $orderData['end_time'] / 1000);
        $count = 0;
        $playCode = [];
        $betNums = '';
        $playName = [];
        $playParam = SzcConstants::SZC_PLAYNAME;
        $price = Constants::PRICE;
        $lotArr = Constants::LOTTERY;
        $abbArr = Constants::LOTTERY_ABBREVI;
        $leS = SzcConstants::LE_SAN;
        $leP = SzcConstants::LE_PUTONG;
        $leD = SzcConstants::LE_DANTUO;
        $nums11X5 = SzcConstants::NUMS_11X5;
        $ballNums = $nums11X5[$lotteryCode];
        $lotName = $lotArr[$lotteryCode];
        $abbName = $abbArr[$lotteryCode];
        $format = 'Y-m-d H:i:s';
        $createTime = date($format);
        $singleCost = 0;
        foreach ($betCont as $val) {
            $funStr = self::getFunName($val['play']);
            if (empty($funStr)) {
                return ['code' => 109, 'msg' => '玩法不存在'];
            }
            $noteNums = EszcCalculation::$funStr($val['nums'], $ballNums[$val['play']]);
            if (in_array($val['play'], $leS)) {
                $price = 6;
            } elseif (in_array($val['play'], $leP)) {
                $price = 10;
            } elseif (in_array($val['play'], $leD)) {
                $price = 14;
            }
            $singleCost += $noteNums * $price * $multiple;
            $count += $noteNums;
            $playCode[] = $val['play'];
            $playName[] = $playParam[$lotteryCode][$val['play']];
            $betNums .= $val['nums'] . '^';
        }
        if ($countBet != $count) {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }

        $cost = $singleCost * $chase;
        if ($total != $cost) {
            return ["code" => 2, "msg" => "投注总金额错误！"
            ];
        }

        $insert = ['lottery_type' => $abbName, 'lottery_name' => $lotName, 'lottery_id' => $lotteryCode, 'play_code' => implode(',', $playCode), 'play_name' => implode(',', $playName), 'periods' => $periods,
            'cust_no' => $custNo, "store_id" => $storeId, 'source_id' => $sourceId, 'bet_val' => $betNums, 'agent_id' => '0', 'periods_total' => $chase, 'bet_double' => $multiple, 'bet_money' => $singleCost, "source" => $source, 'count' => $count,
            'is_bet_add' => $isBetAdd, 'is_random' => $random, 'win_limit' => $limAmount, 'is_limit' => $islimit, 'create_time' => $createTime, 'end_time' => $endTime, 'user_id' => $userId, 'store_no' => $storeCode, 'auto_type' => $outType, 'remark' => $remark];

        return OrderService::selfDoLotterOrder($insert, $boolStr);
    }

    /**
     * 投注验证
     * @return array
     */
    public static function playVerification() {
        $request = Yii::$app->request;
        $betCont = $request->post('contents'); //投注内容（玩法，号码）
        $total = $request->post('total'); //总价
        $multiple = $request->post('multiple'); // 倍数
        $chase = $request->post('chase', 1); // 追期数
        $countBet = $request->post('count_bet'); // 注数
        $lotteryCode = $request->post('lottery_code');
        $count = 0;
        $price = Constants::PRICE;
        $playCode = [];
        $betNums = '';
        $playName = [];

        $playParam = SzcConstants::SZC_PLAYNAME;
        $leS = SzcConstants::LE_SAN;
        $leP = SzcConstants::LE_PUTONG;
        $leD = SzcConstants::LE_DANTUO;
        $nums11X5 = SzcConstants::NUMS_11X5;
        $ballNums = $nums11X5[$lotteryCode];
        $singleCost = 0;
        foreach ($betCont as $val) {
            $funStr = self::getFunName($val['play']);
            if (empty($funStr)) {
                return ['code' => 109, 'msg' => '玩法不存在'];
            }
            $noteNums = EszcCalculation::$funStr($val['nums'], $ballNums[$val['play']]);
            if (in_array($val['play'], $leS)) {
                $price = 6;
            } elseif (in_array($val['play'], $leP)) {
                $price = 10;
            } elseif (in_array($val['play'], $leD)) {
                $price = 14;
            }
            $singleCost += $noteNums * $price * $multiple;
            $count += $noteNums;
            $playCode[] = $val['play'];
            $playName[] = $playParam[$lotteryCode][$val['play']];
            $betNums .= $val['nums'] . '^';
        }

        if ($countBet != $count) {
            return ["code" => 2, "msg" => "投注失败，请重新选择号码！"];
        }
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
        $infos['cust_no'] = $model->cust_no;
        $infos['count'] = $model->count;
        $content = trim($model->bet_val, '^');
        $noteNums = explode('^', $content);
        $playCodes = explode(',', $model->play_code);
        $playNames = explode(',', $model->play_name);
        $order = [];
        $n = 0;
        $nums11X5 = SzcConstants::NUMS_11X5;
        $ballNums = $nums11X5[$model->lottery_id];
        foreach ($noteNums as $key => $nums) {
            $noteStr = self::getNoteName($playCodes[$key]);
            if(empty($noteStr)) {
                return ['code' => 2, 'msg' => '玩法不存在'];
            }
            $ret = EszcCalculation::$noteStr($nums, $ballNums[$playCodes[$key]]);
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
            return ['code' => 2, 'msg' => '操作失败', 'err' => $result];
        }
    }

    public static function getFunName($playCode) {
        $renP = SzcConstants::REN_PUTONG;
        $renD = SzcConstants::REN_DANTUO;
        $qianO = SzcConstants::QIAN_ONE_ZHI;
        $qianTZ = SzcConstants::QIAN_TWO_ZHI;
        $qianSZ = SzcConstants::QIAN_THREE_ZHI;
        $qianZP = SzcConstants::QIAN_ZU_PUTONG;
        $qianZD = SzcConstants::QIAN_ZU_DANTUO;
        $leS = SzcConstants::LE_SAN;
        $leP = SzcConstants::LE_PUTONG;
        $leD = SzcConstants::LE_DANTUO;
        $funStr = '';
        if (in_array($playCode, $renP)) {
            $funStr = 'fun_RenPu';
        } elseif (in_array($playCode, $renD)) {
            $funStr = 'fun_RenDan';
        } elseif (in_array($playCode, $qianO)) {
            $funStr = 'fun_QianOneZhi';
        } elseif (in_array($playCode, $qianTZ)) {
            $funStr = 'fun_QianTwoZhi';
        } elseif (in_array($playCode, $qianSZ)) {
            $funStr = 'fun_QianThreeZhi';
        } elseif (in_array($playCode, $qianZP)) {
            $funStr = 'fun_QianZuPu';
        } elseif (in_array($playCode, $qianZD)) {
            $funStr = 'fun_QianZuDan';
        } elseif (in_array($playCode, $leS)) {
            $funStr = 'fun_LeSan';
        } elseif (in_array($playCode, $leP)) {
            $funStr = 'fun_LeXuanPu';
        } elseif (in_array($playCode, $leD)) {
            $funStr = 'fun_LeXuanDan';
        }
        return $funStr;
    }

    public static function getNoteName($playCode) {
        $renP = SzcConstants::REN_PUTONG;
        $renD = SzcConstants::REN_DANTUO;
        $qianO = SzcConstants::QIAN_ONE_ZHI;
        $qianTZ = SzcConstants::QIAN_TWO_ZHI;
        $qianSZ = SzcConstants::QIAN_THREE_ZHI;
        $qianZP = SzcConstants::QIAN_ZU_PUTONG;
        $qianZD = SzcConstants::QIAN_ZU_DANTUO;
        $leS = SzcConstants::LE_SAN;
        $leP = SzcConstants::LE_PUTONG;
        $leD = SzcConstants::LE_DANTUO;
        $noteStr = '';
        if (in_array($playCode, $renP)) {
            $noteStr = 'note_RenPu';
        } elseif (in_array($playCode, $renD)) {
            $noteStr = 'note_RenDan';
        } elseif (in_array($playCode, $qianO)) {
            $noteStr = 'note_QianOneZhi';
        } elseif (in_array($playCode, $qianTZ)) {
            $noteStr = 'note_QianTwoZhi';
        }elseif (in_array($playCode, $qianSZ)) {
            $noteStr = 'note_QianThreeZhi';
        } elseif (in_array($playCode, $qianZP)) {
            $noteStr = 'note_QianZuPu';
        } elseif (in_array($playCode, $qianZD)) {
            $noteStr = 'note_QianZuDan';
        } elseif (in_array($playCode, $leS)) {
            $noteStr = 'note_LeSan';
        } elseif (in_array($playCode, $leP)) {
            $noteStr = 'note_LeXuanPu';
        } elseif (in_array($playCode, $leD)) {
            $noteStr = 'note_LeXuanDan';
        }
        return $noteStr;
    }

}
