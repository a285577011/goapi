<?php

namespace app\modules\orders\helpers;

use app\modules\numbers\helpers\SzcConstants;
use app\modules\numbers\helpers\SzcCalculation;
use app\modules\numbers\helpers\EszcCalculation;
use app\modules\numbers\services\EszcService;

class SzcDeal {

    /**
     * 计算数字彩的注数
     * @param type $lotteryCode
     * @param type $bet
     * @param type $playCode
     * @return type
     */
    public static function noteNums($lotteryCode, $bet, $playCode) {
        $playParam = SzcConstants::SZC_PLAYNAME;
        $funs = SzcConstants::FUNS;
        if (!array_key_exists($playCode, $playParam[$lotteryCode])) {
            return ["code" => 20002, "msg" => "投注玩法错误！"];
        }
        $nums11X5 = SzcConstants::NUMS_11X5;
        switch ($lotteryCode) {
            case '1001':
            case '1002':
            case '1003':
            case '2001':
            case '2002':
            case '2003':
            case '2004':
                $fun = $funs[$lotteryCode] . $playCode;
                $noteNums = SzcCalculation::$fun($bet);
                break;
            case '2005':
            case '2006':
            case '2007':
            case '2010':
            case '2011':
                $ballNums = $nums11X5[$lotteryCode];
                $fun = EszcService::getFunName($playCode);
                if (empty($fun)) {
                    return ['code' => 20002, 'msg' => '玩法不存在'];
                }
                $noteNums = EszcCalculation::$fun($bet, $ballNums[$playCode]);
                break;
        }
        return ['code' => 600, 'msg' => 'succ', 'data' => $noteNums];
    }

}
