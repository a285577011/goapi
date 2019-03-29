<?php

namespace app\modules\store\helpers;

use app\modules\numbers\helpers\SzcConstants;
use app\modules\numbers\helpers\EszcCalculation;
use app\modules\numbers\services\EszcService;

// 11选五
class Eleventh {

    /**
     * 是否是复式投注
     * @param unknown $lottery_id 玩法Id
     *  @param unknown $playCode 玩法code
     */
    public static function checkIsMix($lottery_id, $playCode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                if (($playCode >= 201112 && $playCode <= 201118) || ($playCode >= 201141 && $playCode <= 201145)) {
                    return true;
                }
                return false;
                break;
        }
    }

    /**
     * 胆拖对应的单式投注code
     * 
     */
    public static function dantuoToSingle($lottery_id) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return [
                    201122 => 201102,
                    201123 => 201103,
                    201124 => 201104,
                    201125 => 201105,
                    201126 => 201106,
                    201127 => 201107,
                    201128 => 201108,
                    201154 => 201134,
                    201155 => 201135
                ];
                break;
        }
    }

    /**
     * 是否是乐选玩法
     * @param unknown $lottery_id
     * @param unknown $playCode 玩法code
     */
    public static function isLeXuan($lottery_id, $playCode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return $playCode >= 201163 && $playCode <= 201170;
                break;
        }
    }

    /**
     * 是否是胆拖投注
     */
    public static function isDanTuo($lottery_id, $playCode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return ($playCode >= 201122 && $playCode <= 201128) || ($playCode >= 201154 && $playCode <= 201155);
                break;
        }
    }

    /**
     * 胆拖拆单注
     */
    public static function caiDanTuo($lottery_id, $playCode, $betVal) {
        $nums11X5 = SzcConstants::NUMS_11X5;
        $ballNums = $nums11X5[$lottery_id];
        $fun = EszcService::getNoteName($playCode);
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return EszcCalculation::$fun($betVal, $ballNums[$playCode]);
                break;
        }
    }

    /**
     * 是否是任选或前一
     */
    public static function isRenxuanAndZhixuan1($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return ($playcode >= 201102 && $playcode <= 201118) || $playcode == 201131 || $playcode == 201141;
                break;
        }
    }

    /**
     * 是否是前一单式
     */
    public static function isQianyiDan($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return $playcode == 201131;
                break;
        }
    }

    /**
     * 是否是直选(大于1的)
     */
    public static function isZhixuan($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return in_array($playcode, [
                    201141,
                    201142,
                    201143,
                    201132,
                    201133
                ]);
                break;
        }
    }

    /**
     * 是否是组选,直选
     * $playcode >= 201132 && $playcode <= 201145
     */
    public static function isZhuxuanAndZhixuan($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return $playcode >= 201132 && $playcode <= 201145;
                break;
        }
    }

    /**
     * 是否是组选复式
     */
    public static function isZhuxuanMax($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return in_array($playcode, [
                    201144,
                    201145
                ]);
                break;
        }
    }

    /**
     * 是否是直选大于1
     */
    public static function isZhixuan1($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return in_array($playcode, [
                    201142,
                    201143,
                    201132,
                    201133
                ]);
                break;
        }
    }

    /**
     * 是否是组选复式
     */
    public static function isZhuxuan($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return in_array($playcode, [
                    201134,
                    201135,
                    201144,
                    201145
                ]);
                break;
        }
    }

    /**
     * 获取组选或直选的号码数
     */
    public static function getZZNum($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                if (in_array($playcode, [201132, 201134, 201142, 201144])) {
                    return 2;
                } elseif (in_array($playcode, [201133, 201135, 201143, 201145])) {
                    return 3;
                }
                break;
        }
    }

    /**
     * 获取玩法名称
     */
    public static function getPlayName($lottery_id, $playcode) {
        switch ($lottery_id) {
            case 2011: // 福建11选五
                return SzcConstants::SZC_PLAYNAME[$lottery_id][$playcode];
                break;
        }
    }

    /**
     * 获取投注金额
     */
    public static function getBetMoney($lottery_id, $playcode, $betVal, $betDouble) {
        $nums11X5 = SzcConstants::NUMS_11X5;
        $ballNums = $nums11X5[$lottery_id];
        $fun = EszcService::getNoteName($playcode);
        switch ($lottery_id) {
            case 2011: // 福建11选五
                $data = [];
                foreach ($betVal as $v) {
                    $data[] = EszcCalculation::$fun($v, $ballNums[$playcode]);
                }
                $money = 0;
                foreach ($data as $v) {
                    foreach ($v as $vc) {
                        $money+=2 * $betDouble;
                    }
                }
                return $money;
                break;
        }
    }

}
