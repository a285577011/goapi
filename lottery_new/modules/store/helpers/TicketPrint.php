<?php

namespace app\modules\store\helpers;

use app\modules\common\helpers\Constants;


class TicketPrint {

    /**
     * 大乐透--生成打印模板
     * $betAry 投注内容
     * $count 倍数
     * $df 单复式 0单式 1复式
     * $add 是否追加 0否 1是
     */
    public function getDltForm($betAry, $count, $df, $add) {
        $formHead = StoreConstants::DLT_HEAD;
        //初始化模板数组
        for ($i = 3; $i <= 32; $i++) {
            for ($j = 0; $j < 13; $j++) {
                $formBody[$i][$j] = 0;
            }
        }
        foreach ($betAry as $k => $v) {
            $bet = explode("|", $v);
            $betAry = explode(",", trim($bet[0]));
            $betBlue = explode(",", trim($bet[1]));
            //红球
            foreach ($betAry as $key => $val) {
                //余数
                $x = $val % 5;
                //商
                $y = floor($val / 5);
                if ($x != 0) {
                    $x = $x + 2 + 5 * ($k - 1);
                    $y = $y + 1;
                } else {
                    $x = $x + 7 + 5 * ($k - 1);
                    $y = $y + 0;
                }
                $formBody[$x][$y] = 1;
            }
            //篮球
            foreach ($betBlue as $key => $val) {
                //余数
                $x = $val % 4;
                //商
                $y = floor($val / 4);
                if ($x != 0) {
                    $x = $x + 2 + 5 * ($k - 1);
                    $y = $y + 9;
                } else {
                    $x = $x + 6 + 5 * ($k - 1);
                    $y = $y + 8;
                }
                $formBody[$x][$y] = 1;
            }
        }
        //复式落点
        if ($df == 1) {
            $formBody[6][12] = 1;
        }
        //追加落点
        if ($add == 1) {
            $formBody[31][12] = 1;
        }
        //倍数 28代表十位数的行数 29代表个位数的行数
        //余数
        $m = $count % 10;
        //商
        $n = floor($count / 10);
        if ($n == 0) {
            $formBody[29][$m + 1] = 1;
        } elseif ($m == 0) {
            $formBody[28][$n + 1] = 1;
            $formBody[29][11] = 1;
        } else {
            $formBody[28][$n + 1] = 1;
            $formBody[29][$m + 1] = 1;
        }
        $arr = array_merge_recursive($formHead, $formBody);
        return $arr;
    }

    /**
     * 创建坐标画布
     */
    public static function creatCoord($x, $y) {
        $formBody = [];
        for ($i = 0; $i <= $x; $i++) {
            for ($j = 0; $j <= $y; $j++) {
                $formBody[$i][$j] = 0;
            }
        }
        return $formBody;
    }

    /**
     * 获取竞彩场次相加
     */
    public static function getShow($num) {
        $num = intval($num);
        if (!is_int($num)) {
            return [];
        }
        $data = array_reverse(str_split($num));
        $return = [];
        $return = self::getShowNumAdd($data[0]);
        if (isset($data[1])) {
            $return2 = self::getShowNumAdd($data[1]);
            foreach ($return2 as $k => $v) {
                $return2[$k] = $v * 10;
            }
            $return = array_merge($return, $return2);
        }
        if (isset($data[2])) {
            $return3 = self::getShowNumAdd($data[2]);
            foreach ($return3 as $k => $v) {
                $return3[$k] = $v * 100;
            }
            $return = array_merge($return, $return3);
        }
        return $return;
    }

    public static function getShowNumAdd($num) {
        $inArr = [
            1,
            2,
            4,
            5
        ];
        $noInArr = [
            3,
            6,
            7,
            8,
            9
        ];
        $ex = [
            0
        ];
        if (in_array($num, $inArr)) {
            return [$num];
        } elseif (in_array($num, $noInArr)) {
            switch ($num) {
                case 3:
                    return [1, 2];
                    break;
                case 6:
                    return [1, 5];
                    break;
                case 7:
                    return [2, 5];
                    break;
                case 8:
                    return [1, 2, 5];
                    break;
                case 9:
                    return [4, 5];
                    break;
            }
        }
        return [];
    }

    /**
     * 获取竞彩倍数相加数组
     * @param unknown $num
     */
    public static function getMulArr($num) {
        $num = intval($num);
        if (!is_int($num)) {
            return [];
        }
        if ($num < 60) {
            if ($num < 10) {
                return [$num];
            }
            list($shi, $ge) = str_split($num);
            if ($ge > 0) {
                return [$shi * 10, $ge];
            }
            return [$shi * 10];
        } else {
            list($shi, $ge) = str_split($num);
            $return = [];
            switch ($shi) {
                case 6:
                    $return = [10, 50];
                    break;
                case 7:
                    $return = [20, 50];
                    break;
                case 8:
                    $return = [30, 50];
                    break;
                case 9:
                    $return = [40, 50];
                    break;
            }
            $ge > 0 && array_push($return, $ge);
            return $return;
        }
        return [$num];
    }

    /**
     * 根据坐标设置点数
     */
    public static function setCoord($data, $body) {
        foreach ($data as $vcc) {
            list ( $x, $y ) = explode(',', $vcc);
            $body[$x][$y] = 1;
        }
        return $body;
    }

    /**
     * 竞彩倍数填坐标(单场)
     */
    public static function getDoubleCoord($body, $max, $num) {
        $first = $max - 4;
        if ($num < 5) {
            $i = 0;
        } elseif ($num < 9) {
            $i = 1;
        } elseif ($num < 31) {
            $i = 2;
        } else {
            $i = 3;
        }
        switch ($num) {
            case 1:
                $body[$first + $i][8] = 1;
                break;
            case 2:
                $body[$first + $i][9] = 1;
                break;
            case 3:
                $body[$first + $i][10] = 1;
                break;
            case 4:
                $body[$first + $i][11] = 1;
                break;
            case 5:
                $body[$first + $i][8] = 1;
                break;
            case 6:
                $body[$first + $i][9] = 1;
                break;
            case 7:
                $body[$first + $i][10] = 1;
            case 8:
                $body[$first + $i][11] = 1;
                break;
            case 9:
                $body[$first + $i][8] = 1;
                break;
            case 10:
                $body[$first + $i][9] = 1;
                break;
            case 20:
                $body[$first + $i][10] = 1;
                break;
            case 30:
                $body[$first + $i][11] = 1;
                break;
            case 40:
                $body[$first + $i][8] = 1;
                break;
            case 50:
                $body[$first + $i][9] = 1;
                break;
        }
        return $body;
    }

    /**
     * weekcoord
     * 周几布点
     */
    public static function setWeekCoord($body, $x, $num, $week) {
        switch ($week) {
            case 1:
                $body[$x][0 + 4 * ($num - 1)] = 1;
                break;
            case 2:
                $body[$x][1 + 4 * ($num - 1)] = 1;
                break;
            case 3:
                $body[$x][2 + 4 * ($num - 1)] = 1;
                break;
            case 4:
                $body[$x][3 + 4 * ($num - 1)] = 1;
                break;
            case 5:
                $body[$x + 1][0 + 4 * ($num - 1)] = 1;
                break;
            case 6:
                $body[$x + 1][1 + 4 * ($num - 1)] = 1;
                break;
            case 0:
                $body[$x + 1][2 + 4 * ($num - 1)] = 1;
                break;
        }
        return $body;
    }

    /**
     * 竞彩场次填坐标(多场)
     */
    public static function getChanciCoord($body, $first, $num, $changci = 1) {
        if ($num < 10) {
            $i = 0;
        } elseif ($num >= 10 && $num < 100) {
            $i = 1;
            $num = $num / 10;
        } else {
            $i = 2;
            $num = $num / 100;
        }
        switch ($num) {
            case 1:
                $body[$first + $i][0 + 4 * ($changci - 1)] = 1;
                break;
            case 2:
                $body[$first + $i][1 + 4 * ($changci - 1)] = 1;
                break;
            case 4:
                $body[$first + $i][2 + 4 * ($changci - 1)] = 1;
                break;
            case 5:
                $body[$first + $i][3 + 4 * ($changci - 1)] = 1;
                break;
        }
        return $body;
    }

    //M串N
    public static function getMcn($palyCode, $betVal) {
        $mCn = StoreConstants::COUNT_MCN;
        $manerCn = Constants::MANNER;
        $manerCn[1] = '1串1';
        $playAry = explode(",", $palyCode);
        if (count($playAry) > 1) {
            $betCount = count($betVal);
            $cn = array_search($palyCode, $mCn[$betCount]);
            return $manerCn[$cn];
        } else {
            return $manerCn[$palyCode];
        }
    }

    /**
     * 竞彩串关填坐标
     * @param unknown $body 初始化坐标
     * @param unknown $max X最大值
     * @param unknown $num 串关数 Mchuan N 2,1 2串1
     */
    public static function setMnCoord($body, $max, $num) {
        $first = $max - 4;
        $arr = [
            '2,1' => [$first, 0],
            '4,1' => [$first, 0],
            '7,1' => [$first, 0],
            '3,1' => [$first, 1],
            '4,4' => [$first, 1],
            '7,7' => [$first, 1],
            '3,3' => [$first, 2],
            '4,5' => [$first, 2],
            '7,8' => [$first, 2],
            '3,4' => [$first, 3],
            '4,6' => [$first, 3],
            '7,21' => [$first, 3],
            '4,11' => [$first, 4],
            '7,35' => [$first, 4],
            '7,120' => [$first, 5],
            '5,1' => [$first + 1, 0],
            '8,1' => [$first + 1, 0],
            '5,5' => [$first + 1, 1],
            '8,8' => [$first + 1, 1],
            '5,6' => [$first + 1, 2],
            '8,9' => [$first + 1, 2],
            '5,10' => [$first + 1, 3],
            '8,28' => [$first + 1, 3],
            '5,16' => [$first + 1, 4],
            '8,56' => [$first + 1, 4],
            '5,20' => [$first + 1, 5],
            '8,70' => [$first + 1, 5],
            '5,26' => [$first + 1, 6],
            '8,247' => [$first + 1, 6],
            '6,1' => [$first + 2, 0],
            '6,6' => [$first + 2, 1],
            '6,7' => [$first + 2, 2],
            '6,15' => [$first + 2, 3],
            '6,20' => [$first + 2, 4],
            '6,22' => [$first + 2, 5],
            '6,35' => [$first + 2, 6],
            '6,42' => [$first + 2, 7],
            '6,50' => [$first + 3, 0],
            '6,57' => [$first + 3, 1],
        ];
        list($x, $y) = $arr[$num];
        $body[$x][$y] = 1;
        return $body;
    }

    /**
     * 竞彩胜平负
     * $body 落点数组
     * $x 行数
     * $y 第几个
     * $strBet(字符串) 投注内容：胜平负
     */
    public static function setSpf($body, $x, $y, $strBet) {
        $betAry = explode(",", $strBet);
        foreach ($betAry as $k => $v) {
            if ($v == 3) {
                $body[$x][($y - 1) * 4] = 1;
            } elseif ($v == 1) {
                $body[$x][($y - 1) * 4 + 1] = 1;
            } elseif ($v == 0) {
                $body[$x][($y - 1) * 4 + 2] = 1;
            }
        }
        return $body;
    }

    /**
     * 竞彩总进球
     * $body 落点数组
     * $x 行数
     * $y 第几个
     * $strBet(字符串) 投注内容：总进球数
     */
    public static function setZjq($body, $x, $y, $strBet) {
        $betAry = explode(",", $strBet);
        foreach ($betAry as $k => $v) {
            switch ($v) {
                case 0:
                    $body[$x][($y - 1) * 4] = 1;
                    break;
                case 1:
                    $body[$x][($y - 1) * 4+1] = 1;
                    break;
                case 2:
                    $body[$x][($y - 1) * 4+2] = 1;
                    break;
                case 3:
                    $body[$x][($y - 1) * 4+3] = 1;
                    break;
                case 4:
                    $body[$x+1][($y - 1) * 4] = 1;
                    break;
                case 5:
                    $body[$x+1][($y - 1) * 4+1] = 1;
                    break;
                case 6:
                    $body[$x+1][($y - 1) * 4+2] = 1;
                    break;
                case 7:
                    $body[$x+1][($y - 1) * 4+3] = 1;
                    break;
            }
        }
        return $body;
    }

}
