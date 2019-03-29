<?php

namespace app\modules\common\helpers;

use Yii;
use app\modules\common\models\DiyFollow;
use app\modules\common\models\ProgrammeUser;
use app\modules\common\helpers\Constants;
use app\modules\common\services\ProgrammeService;
use app\modules\common\models\Programme;
use yii\db\Query;
use app\modules\competing\helpers\CompetConst;

class Made {

    /**
     * 获取定制用户进行跟单
     * @auther GL zyl
     * @param string $storeId
     * @param string $lcode
     * @param int $betMoney
     * @param int $programmeId
     * @return boolean
     */
    public static function CustomMade($expertNo, $lcode, $betNums, $programmeId, $onePrice) {
        $like = '%' . $lcode . '%';
        $custom = DiyFollow::find()->select(['expert_no', 'cust_no', 'follow_type', 'follow_num', 'bet_nums', 'follow_percent', 'max_bet_money', 'stop_money'])
                ->where(['expert_no' => $expertNo])
                ->andWhere(['like', 'lottery_codes', $like, false])
                ->andWhere(['<=', 'bet_nums', $betNums])
                ->asArray()
                ->all();
        if (empty($custom)) {
            return false;
        }
        $madeAmount = 0;
        $madeNums = 0;
        foreach ($custom as $val) {
            if ($val['follow_type'] == 2) {
                $amount = floor($betMoney * ($val['follow_percent'] / 100));
                if ($amount > $val['max_bet_money']) {
                    continue;
                }
            } else {
                $amount = $val['bet_nums'] * $onePrice;
                if ($val['bet_nums'] > $betNums) {
                    continue;
                }
                $maxMoney = $val['max_bet_money'];
                if ($maxMoney > 0) {
                    if ($amount > $maxMoney) {
                        continue;
                    }
                }
            }
            $result = self::BuyProgramme($val['cust_no'], $programmeId, $amount, $val['follow_num'], $val['stop_money'], $val['bet_nums']);
            if ($result['code'] == 100) {
                continue;
            } elseif ($result['code'] == 600) {
                $madeAmount += $amount;
                $madeNums += $val['bet_nums'];
            }
        }
        $programme = Programme::find()->where(["programme_id" => $programmeId])->one();
        $programme->made_amount = $madeAmount;
        $programme->made_nums = $madeNums;
        if (!$programme->save()) {
            return false;
        }
        return true;
    }

    /**
     * 跟单用户系统认购
     * @auther GL zyl
     * @param string $userNo
     * @param int $programmeId
     * @param int $betMoney
     * @param int $followNum
     * @return type
     */
    public static function BuyProgramme($userNo, $programmeId, $betMoney, $followNum, $limitFunds, $buyNums) {
        $user = (new Query())->select('u.user_name, f.able_funds')
                ->from('user as u')
                ->leftJoin('user_funds as f', 'f.cust_no = u.cust_no')
                ->where(['u.cust_no' => $userNo])
                ->one();
        if ($user['able_funds'] <= $limitFunds) {
            return ['code' => 100, 'msg' => '余额不足'];
        }
        $programme = Programme::find()->select(['status', 'programme_last_amount', 'lottery_code', 'periods', 'programme_last_number'])->where(["programme_id" => $programmeId])->asArray()->one();
        if ($programme['status'] != 2) {
            return ['code' => 100, 'msg' => '该方案已满额'];
        }
        $numsLottery = Constants::MADE_NUMS_LOTTERY;
        $footballLottery = Constants::MADE_FOOTBALL_LOTTERY;
        $optionalLottery = Constants::MADE_OPTIONAL_LOTTERY;
        $basketballLottery = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdLottery = CompetConst::MADE_BD_LOTTERY;
        $wcLottery = CompetConst::MADE_WCUP_LOTTERY;
        if ($betMoney > $programme['programme_last_amount']) {
            return ['code' => 109, 'msg' => '超额'];
        }
        if (in_array($programme['lottery_code'], $numsLottery) || in_array($programme['lottery_code'], $optionalLottery)) {
            $madeCount = ProgrammeUser::find()->where(['cust_no' => $userNo, 'lottery_code' => $programme['lottery_code'], 'periods' => $programme['periods']])->count();
            if ($madeCount >= $followNum) {
                return ['code' => 109, 'msg' => '今天已认购'];
            }
        } elseif (in_array($programme['lottery_code'], $footballLottery)) {
            $date = date('Y-m-d');
            $start = ['>=', 'create_time', $date . ' 00:00:00'];
            $end = ['<=', 'create_time', $date . ' 23:59:59'];
            $footballCode = ['in', 'lottery_code', $footballLottery];
            $madeCount = ProgrammeUser::find()->where(['cust_no' => $userNo])->andWhere($footballCode)->andWhere($start)->andWhere($end)->count();
            if ($madeCount >= $followNum) {
                return ['code' => 109, 'msg' => '今天已认购'];
            }
        }elseif (in_array($programme['lottery_code'], $basketballLottery)) {
            $date = date('Y-m-d');
            $start = ['>=', 'create_time', $date . ' 00:00:00'];
            $end = ['<=', 'create_time', $date . ' 23:59:59'];
            $baskeballCode = ['in', 'lottery_code', $basketballLottery];
            $madeCount = ProgrammeUser::find()->where(['cust_no' => $userNo])->andWhere($baskeballCode)->andWhere($start)->andWhere($end)->count();
            if ($madeCount >= $followNum) {
                return ['code' => 109, 'msg' => '今天已认购'];
            }
        }elseif (in_array($programme['lottery_code'], $bdLottery)) {
            $date = date('Y-m-d');
            $start = ['>=', 'create_time', $date . ' 00:00:00'];
            $end = ['<=', 'create_time', $date . ' 23:59:59'];
            $bdCode = ['in', 'lottery_code', $bdLottery];
            $madeCount = ProgrammeUser::find()->where(['cust_no' => $userNo])->andWhere($bdCode)->andWhere($start)->andWhere($end)->count();
            if ($madeCount >= $followNum) {
                return ['code' => 109, 'msg' => '今天已认购'];
            }
        }elseif (in_array($programme['lottery_code'], $wcLottery)) {
            $madeCount = ProgrammeUser::find()->where(['cust_no' => $userNo, 'lottery_code' => $programme['lottery_code']])->count();
            if ($madeCount >= $followNum) {
                return ['code' => 109, 'msg' => '此彩种已认购'];
            }
        }
        $funds = new \app\modules\common\services\FundsService();
        $retFunds = $funds->operateUserFunds($userNo, -$betMoney, -$betMoney, 0, true, '购彩-定制合买');
        if ($retFunds['code'] != 0) {
            return ['code' => 109, 'msg' => $retFunds['msg']];
        }
        $programmeService = new ProgrammeService();
        $sysBuy = $programmeService->sysBuyProgramme($programmeId, intval($betMoney), $buyNums, $userNo, $user['user_name'], 2, 1);
        if ($sysBuy === true) {
            return ['code' => 600, 'msg' => '认购成功'];
        } else {
            return ['code' => 109, 'msg' => '认购失败'];
        }
    }

}
