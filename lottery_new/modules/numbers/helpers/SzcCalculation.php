<?php

namespace app\modules\numbers\helpers;

use app\modules\common\helpers\Commonfun;

class SzcCalculation {

    /**
     * 双色球直选单式
     */
    public static function ssqFun_100101($nums) {
        $bet_arr = explode('|', $nums);
        $red_arr = explode(',', $bet_arr[0]);
        $blue_arr = explode(',', $bet_arr[1]);
        $redCom = Commonfun::getCombination(count($red_arr), 6);
        $blueCom = Commonfun::getCombination(count($blue_arr), 1);
        $comb = $redCom * $blueCom;
        return $comb;
    }

    /**
     * 双色球直选复式
     */
    public static function ssqFun_100102($nums) {
        $bet_arr = explode('|', $nums);
        $red_arr = explode(',', $bet_arr[0]);
        $blue_arr = explode(',', $bet_arr[1]);
        $redCom = Commonfun::getCombination(count($red_arr), 6);
        $blueCom = Commonfun::getCombination(count($blue_arr), 1);
        $comb = $redCom * $blueCom;
        return $comb;
    }

    /**
     * 双色球直选胆拖
     */
    public static function ssqFun_100103($nums) {
        
    }

    /**
     * 福彩3D直选单式
     */
    public static function tdFun_100201($nums) {
        $bet_arr = explode('|', $nums);
        $comb = 1;
        foreach ($bet_arr as $val) {
            $arr = explode(',', $val);
            $combination = Commonfun::getCombination(count($arr), 1);
            $comb *= $combination;
        }
        return $comb;
    }

    /**
     * 福彩3D直选复式
     */
    public static function tdFun_100211($nums) {
        $bet_arr = explode('|', $nums);
        $comb = 1;
        foreach ($bet_arr as $val) {
            $arr = explode(',', $val);
            $combination = Commonfun::getCombination(count($arr), 1);
            $comb *= $combination;
        }
        return $comb;
    }

    /**
     * 福彩3D直选和值
     */
    public static function tdFun_100221($nums) {
        
    }

    /**
     * 福彩3D组三单式
     */
    public static function tdFun_100202($nums) {
        $bet_arr = explode(',', $nums);
        $createCom = Commonfun::arrangement(count($bet_arr), 2);
        $count = $createCom;
        return $count;
    }

    /**
     * 福彩3D组三复式
     */
    public static function tdFun_100212($nums) {
        $bet_arr = explode(',', $nums);
        $createCom = Commonfun::arrangement(count($bet_arr), 2);
        $count = $createCom;
        return $count;
    }

    /**
     * 福彩3D组三和值
     */
    public static function tdFun_100222($nums) {
        
    }

    /**
     * 福彩3D组六单式
     */
    public static function tdFun_100203($nums) {
        $bet_arr = explode(',', $nums);
        $createCom = Commonfun::getCombination(count($bet_arr), 3);
        $count = $createCom;
        return $count;
    }

    /**
     * 福彩3D组六复式
     */
    public static function tdFun_100213($nums) {
        $bet_arr = explode(',', $nums);
        $createCom = Commonfun::getCombination(count($bet_arr), 3);
        $count = $createCom;
        return $count;
    }

    /**
     * 福彩3D组六和值
     */
    public static function tdFun_100223($nums) {
        
    }

    /**
     * 七乐彩直选单式
     */
    public static function qlcFun_100301($nums) {
        $bet_arr = explode(',', $nums);
        $createCom = Commonfun::getCombination(count($bet_arr), 7);
        $count = $createCom;
        return $count;
    }

    /**
     * 七乐彩直选复式
     */
    public static function qlcFun_100302($nums) {
        $bet_arr = explode(',', $nums);
        $createCom = Commonfun::getCombination(count($bet_arr), 7);
        $count = $createCom;
        return $count;
    }

    /**
     * 七乐彩直选胆拖
     */
    public static function qlcFun_100303($nums) {
        
    }

    /**
     * 大乐透单式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function dltFun_200101($nums) {
        $areas = explode('|', $nums);
        $redBalls = explode(',', $areas[0]);
        $blueBalls = explode(',', $areas[1]);
        $redNum = Commonfun::getCombination(count($redBalls), 5);
        $blueNum = Commonfun::getCombination(count($blueBalls), 2);
        $comb = $redNum * $blueNum;
        if ($comb == 1) {
            return 1;
        } else {
            return false;
        }
    }

    /**
     * 大乐透复式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function dltFun_200102($nums) {
        $areas = explode('|', $nums);
        $redBalls = explode(',', $areas[0]);
        $blueBalls = explode(',', $areas[1]);
        if (count($redBalls) > 18) {
            return false;
        }
        $redNum = Commonfun::getCombination(count($redBalls), 5);
        $blueNum = Commonfun::getCombination(count($blueBalls), 2);
        $comb = $redNum * $blueNum;
        return $comb;
    }

    /**
     * 排列三单式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function plsFun_200201($nums) {
        $areas = explode('|', $nums);
        $baiBalls = explode(',', $areas[0]);
        $shiBalls = explode(',', $areas[1]);
        $geBalls = explode(',', $areas[2]);
        $comb = count($baiBalls) * count($shiBalls) * count($geBalls);
        if (Commonfun::allIsNumber($areas) == false || $comb != 1) {
            return false;
        }
        return $comb;
    }

    /**
     * 排列三复式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function plsFun_200211($nums) {
        $areas = explode('|', $nums);
        $baiBalls = explode(',', $areas[0]);
        $shiBalls = explode(',', $areas[1]);
        $geBalls = explode(',', $areas[2]);
        $comb = count($baiBalls) * count($shiBalls) * count($geBalls);
        return $comb;
    }

    /**
     * 排列三组三单式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function plsFun_200202($nums) {
        return false;
        $Balls = explode(',', $nums);
        if (Commonfun::allIsNumber($Balls) == true && count($Balls) == 2) {
            if ($Balls[0] == $Balls[1]) {
                return false;
            }
            return 1;
        }
        return false;
    }

    /**
     * 排列三组三复式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function plsFun_200212($nums) {
        $Balls = explode(',', $nums);
        if (Commonfun::allIsNumber($Balls) == false || count($Balls) < 2) {
            return false;
        }
        $comb = Commonfun::arrangement(count($Balls), 2);
        return $comb;
    }

    /**
     * 排列三组六单式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function plsFun_200203($nums) {
        $Balls = explode(',', $nums);
        if (Commonfun::allIsNumber($Balls) == true && count($Balls) == 3) {
            return 1;
        }
        return false;
    }

    /**
     * 排列三组六复式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function plsFun_200213($nums) {
        $Balls = explode(',', $nums);
        if (Commonfun::allIsNumber($Balls) == false || count($Balls) < 3) {
            return false;
        }
        $comb = Commonfun::getCombination(count($Balls), 3);
        return $comb;
    }

    /**
     * 排列五单式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function plfFun_200301($nums) {
        $Balls = explode('|', $nums);
        if (Commonfun::allIsNumber($Balls) == true && count($Balls) == 5) {
            return 1;
        }
        return false;
    }

    /**
     * 排列五复式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function plfFun_200302($nums) {
        $areas = explode('|', $nums);
        if (count($areas) == 5) {
            $wanBalls = explode(',', $areas[0]);
            $qianBalls = explode(',', $areas[1]);
            $baiBalls = explode(',', $areas[2]);
            $shiBalls = explode(',', $areas[3]);
            $geBalls = explode(',', $areas[4]);
            $comb = count($wanBalls) * count($qianBalls) * count($baiBalls) * count($shiBalls) * count($geBalls);
            return $comb;
        }
        return false;
    }

    /**
     * 七星彩单式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function qxcFun_200401($nums) {
        $Balls = explode('|', $nums);
        if (Commonfun::allIsNumber($Balls) == true && count($Balls) == 7) {
            return 1;
        }
        return false;
    }

    /**
     * 七星彩复式计算注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function qxcFun_200402($nums) {
        $areas = explode('|', $nums);
        if (count($areas) == 7) {
            $bwBalls = explode(',', $areas[0]);
            $swBalls = explode(',', $areas[1]);
            $wanBalls = explode(',', $areas[2]);
            $qianBalls = explode(',', $areas[3]);
            $baiBalls = explode(',', $areas[4]);
            $shiBalls = explode(',', $areas[5]);
            $geBalls = explode(',', $areas[6]);
            $comb = count($bwBalls) * count($swBalls) * count($wanBalls) * count($qianBalls) * count($baiBalls) * count($shiBalls) * count($geBalls);
            return $comb;
        }
        return false;
    }

    public static function ssqNote_100101($arr) {
        if (!is_array($arr)) {
            return "数据错误！";
        }
        $redBalls = explode(',', $arr[0]);
        $blueBalls = explode(',', $arr[1]);
        $redArrs = Commonfun::getCombination_array($redBalls, 6);
        $blueArrs = Commonfun::getCombination_array($blueBalls, 1);

        foreach ($redArrs as $key => &$val) {
            $val = implode(",", $val);
        }

        foreach ($blueArrs as $key => &$val) {
            $val = implode(",", $val);
        }

        $nums = Commonfun::cross_array($redArrs, $blueArrs);
        foreach ($nums as $key => &$val) {
            $val = implode("|", $val);
        }
        return $nums;
    }

    public static function ssqNote_100102($arr) {
        if (!is_array($arr)) {
            return "数据错误！";
        }
        $redBalls = explode(',', $arr[0]);
        $blueBalls = explode(',', $arr[1]);
        $redArrs = Commonfun::getCombination_array($redBalls, 6);
        $blueArrs = Commonfun::getCombination_array($blueBalls, 1);

        foreach ($redArrs as $key => &$val) {
            $val = implode(",", $val);
        }

        foreach ($blueArrs as $key => &$val) {
            $val = implode(",", $val);
        }

        $nums = Commonfun::cross_array($redArrs, $blueArrs);
        foreach ($nums as $key => &$val) {
            $val = implode("|", $val);
        }
        return $nums;
    }

    public static function tdNote_100201($arr) {
        $orders = [];
        $orders[] = implode(",", $arr);
        return $orders;
    }

    public static function tdNote_100211($arr) {
        $hand = explode(',', $arr[0]);
        $ten = explode(',', $arr[1]);
        $bit = explode(',', $arr[2]);

        $baiArrs = Commonfun::getCombination_array($hand, 1);
        $shiArrs = Commonfun::getCombination_array($ten, 1);
        $geArrs = Commonfun::getCombination_array($bit, 1);
        foreach ($baiArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        foreach ($shiArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        foreach ($geArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        $orders = Commonfun::cross_array($baiArrs, $ten);
        foreach ($orders as $key => &$val) {
            $val = implode(",", $val);
        }

        $orders = Commonfun::cross_array($orders, $geArrs);
        foreach ($orders as $key => &$val) {
            $val = implode(",", $val);
        }
        return $orders;
    }

    public static function tdNote_100202($arr) {
        $nums = explode(',', $arr[0]);
        $arrs = Commonfun::getCombination_array($nums, 2);
        $orders = [];
        foreach ($arrs as $key => $val) {
            $v1 = $val;
            $v1[] = $val[0];
            $v2 = $val;
            $v2[] = $val[1];
            $orders[] = $v1;
            $orders[] = $v2;
        }
        foreach ($orders as $key => &$val) {
            $val = implode(",", $val);
        }
        return $orders;
    }

    public static function tdNote_100212($arr) {
        $nums = explode(',', $arr[0]);
        $arrs = Commonfun::getCombination_array($nums, 2);
        $orders = [];
        foreach ($arrs as $key => $val) {
            $v1 = $val;
            $v1[] = $val[0];
            $v2 = $val;
            $v2[] = $val[1];
            $orders[] = $v1;
            $orders[] = $v2;
        }
        foreach ($orders as $key => &$val) {
            $val = implode(",", $val);
        }
        return $orders;
    }

    public static function tdNote_100203($arr) {
        $arrs = explode(',', $arr[0]);
        $arrs = Commonfun::getCombination_array($arrs, 3);
        $orders = [];
        foreach ($arrs as $key => $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    public static function tdNote_100213($arr) {
        $arrs = explode(',', $arr[0]);
        $arrs = Commonfun::getCombination_array($arrs, 3);
        $orders = [];
        foreach ($arrs as $key => $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    public static function qlcNote_100301($arr) {
        if (!is_array($arr)) {
            return "数据错误！";
        }
        $numsArr = explode(',', $arr[0]);
        $qxcArrs = Commonfun::getCombination_array($numsArr, 7);

        foreach ($qxcArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        return $qxcArrs;
    }

    public static function qlcNote_100302($arr) {
        if (!is_array($arr)) {
            return "数据错误！";
        }
        $numsArr = explode(',', $arr[0]);
        $qxcArrs = Commonfun::getCombination_array($numsArr, 7);

        foreach ($qxcArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        return $qxcArrs;
    }

    /**
     * 大乐透直选单式生成单一投注
     * @param array $areas
     * @return array
     */
    public static function dltNote_200101($areas) {
        $redBalls = explode(',', $areas[0]);
        $blueBalls = explode(',', $areas[1]);
        if (!is_array($redBalls) || !is_array($blueBalls) || count($redBalls) < 5 || count($blueBalls) < 2) {
            return false;
        }
        $redArrs = Commonfun::getCombination_array($redBalls, 5);
        $blueArrs = Commonfun::getCombination_array($blueBalls, 2);
        foreach ($redArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        foreach ($blueArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        $orders = Commonfun::cross_array($redArrs, $blueArrs);
        foreach ($orders as $key => &$val) {
            $val = implode("|", $val);
        }
        return $orders;
    }

    /**
     * 大乐透直选复式生成单一投注
     * @param array $areas
     * @return array
     */
    public static function dltNote_200102($areas) {
        $redBalls = explode(',', $areas[0]);
        $blueBalls = explode(',', $areas[1]);
        if (!is_array($redBalls) || !is_array($blueBalls) || count($redBalls) < 5 || count($blueBalls) < 2) {
            return false;
        }
        $redArrs = Commonfun::getCombination_array($redBalls, 5);
        $blueArrs = Commonfun::getCombination_array($blueBalls, 2);
        foreach ($redArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        foreach ($blueArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        $orders = Commonfun::cross_array($redArrs, $blueArrs);
        foreach ($orders as $key => &$val) {
            $val = implode("|", $val);
        }
        return $orders;
    }

    /**
     * 排列三直选单式生成单一投注
     * @param array $areas
     * @return array
     */
    public static function plsNote_200201($areas) {
        $orders = [];
        if (is_array($areas) && count($areas) == 3) {
            $orders[] = implode(",", $areas);
            return $orders;
        }
        return false;
    }

    /**
     * 排列三直选复式生成单一投注
     * @param array $areas
     * @return array
     */
    public static function plsNote_200211($areas) {
        $baiBalls = explode(',', $areas[0]);
        $shiBalls = explode(',', $areas[1]);
        $geBalls = explode(',', $areas[2]);
        if (!is_array($baiBalls) || !is_array($shiBalls) || !is_array($geBalls)) {
            return false;
        }
        $baiArrs = Commonfun::getCombination_array($baiBalls, 1);
        $shiArrs = Commonfun::getCombination_array($shiBalls, 1);
        $geArrs = Commonfun::getCombination_array($geBalls, 1);
        foreach ($baiArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        foreach ($shiArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        foreach ($geArrs as $key => &$val) {
            $val = implode(",", $val);
        }
        $orders = Commonfun::proCross_string(",", $baiArrs, $shiArrs, $geArrs);
        return $orders;
    }

    /**
     * 排列三组三单式生成单一投注
     * @param array $nums
     * @return array
     */
    public static function plsNote_200202($nums) {
        $balls = explode(',', $nums[0]);
        if (count($balls) != 3) {
            return false;
        }
        $orders = [$nums[0]];
        return $orders;
    }

    /**
     * 排列三组三复式生成单一投注
     * @param array $nums
     * @return array
     */
    public static function plsNote_200212($nums) {
        $balls = explode(',', $nums[0]);
        if (!is_array($balls) || count($balls) < 2) {
            return false;
        }
        $arrs = Commonfun::getCombination_array($balls, 2);
        $orders = [];
        foreach ($arrs as $key => $val) {
            $v1 = $val;
            $v1[] = $val[0];
            $v2 = $val;
            $v2[] = $val[1];
            $orders[] = $v1;
            $orders[] = $v2;
        }
        foreach ($orders as $key => &$val) {
            $val = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 排列三组六单式生成单一投注
     * @param array $nums
     * @return array
     */
    public static function plsNote_200203($nums) {
        $balls = explode(',', $nums[0]);
        if (count($balls) != 3) {
            return false;
        }
        $orders = [$nums[0]];
        return $orders;
    }

    /**
     * 排列三组六复式生成单一投注
     * @param array $nums
     * @return array
     */
    public static function plsNote_200213($nums) {
        $balls = explode(',', $nums[0]);
        if (!is_array($balls) || count($balls) < 2) {
            return false;
        }
        $arrs = Commonfun::getCombination_array($balls, 3);
        $orders = [];
        foreach ($arrs as $key => $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 排列五直选单式生成单一投注
     * @param array $areas
     * @return array
     */
    public static function plfNote_200301($areas) {
        $orders = [];
        if (is_array($areas) && count($areas) == 5) {
            $orders[] = implode(",", $areas);
            return $orders;
        }
        return false;
    }

    /**
     * 排列五直选复式生成单一投注
     * @param array $areas
     * @return array
     */
    public static function plfNote_200302($areas) {
        $wanBalls = explode(',', $areas[0]);
        $qianBalls = explode(',', $areas[1]);
        $baiBalls = explode(',', $areas[2]);
        $shiBalls = explode(',', $areas[3]);
        $geBalls = explode(',', $areas[4]);
        if (!is_array($wanBalls) || !is_array($qianBalls) || !is_array($baiBalls) || !is_array($shiBalls) || !is_array($geBalls)) {
            return false;
        }

        $orders = Commonfun::proCross_string(",", $wanBalls, $qianBalls, $baiBalls, $shiBalls, $geBalls);
        return $orders;
    }

    /**
     * 七星彩直选单式生成单一投注
     * @param array $areas
     * @return array
     */
    public static function qxcNote_200401($areas) {
        $orders = [];
        if (is_array($areas) && count($areas) == 7) {
            $orders[] = implode(",", $areas);
            return $orders;
        }
        return false;
    }

    /**
     * 七星彩直选复式生成单一投注
     * @param array $areas
     * @return array
     */
    public static function qxcNote_200402($areas) {
        $bwBalls = explode(',', $areas[0]);
        $swBalls = explode(',', $areas[1]);
        $wanBalls = explode(',', $areas[2]);
        $qianBalls = explode(',', $areas[3]);
        $baiBalls = explode(',', $areas[4]);
        $shiBalls = explode(',', $areas[5]);
        $geBalls = explode(',', $areas[6]);
        if (!is_array($bwBalls) || !is_array($swBalls) || !is_array($wanBalls) || !is_array($qianBalls) || !is_array($baiBalls) || !is_array($shiBalls) || !is_array($geBalls)) {
            return false;
        }
        $orders = Commonfun::proCross_string(",", $bwBalls, $swBalls, $wanBalls, $qianBalls, $baiBalls, $shiBalls, $geBalls);
        return $orders;
    }

}
