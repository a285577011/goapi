<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\sports\helpers;

use app\modules\common\helpers\Commonfun;

class TCCalculation {

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

    

}
