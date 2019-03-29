<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\numbers\helpers;

use app\modules\common\helpers\Commonfun;

class EszcCalculation {

    /**
     * 11X5 任选
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_RenPu($nums, $n) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), $n);
        return $comb;
    }
    

    /**
     * 11X5  任选胆拖
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_RenDan($nums, $n) {
        $areas = explode('#', $nums);
        $danArr = explode(',', $areas[0]);
        $tuoArr = explode(',', $areas[1]);
        $m = $n - count($danArr);
        if($m == 0) {
            return false;
        }
        $comb = Commonfun::getCombination(count($tuoArr), $m);
        return $comb;
    }

    /**
     * 11X5  前一直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_QianOneZhi($nums) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), 1);
        return $comb;
    }

    /**
     * 11X5  前二直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_QianTwoZhi($nums) {
        $areas = explode(';', $nums);
        $oneArr = explode(',', $areas[0]);
        $twoArr = explode(',', $areas[1]);
        $combOne = Commonfun::getCombination(count($oneArr), 1);
        $combTwo = Commonfun::getCombination(count($twoArr), 1);
        $commonNums = count(array_intersect($oneArr, $twoArr));
        $comb = $combOne * $combTwo - $commonNums;
        if($comb == 0){
            return false;
        }
        return $comb;
    }

    /**
     * 11X5  前三直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_QianThreeZhi($nums) {
        $areas = explode(';', $nums);
        $oneArr = explode(',', $areas[0]);
        $twoArr = explode(',', $areas[1]);
        $threeArr = explode(',', $areas[2]);
        $combOne = Commonfun::getCombination(count($oneArr), 1);
        $combTwo = Commonfun::getCombination(count($twoArr), 1);
        $combThree = Commonfun::getCombination(count($threeArr), 1);
        $onetwoNums = count(array_intersect($oneArr, $twoArr));
        $onethreeNums = count(array_intersect($oneArr, $threeArr));
        $twothreeNums = count(array_intersect($twoArr, $threeArr));
        $allNums = count(array_intersect($oneArr, $twoArr, $threeArr));
        $commonNums = $onetwoNums * count($threeArr) + $onethreeNums * count($twoArr) + $twothreeNums * count($oneArr) - $allNums * 2;
        $comb = $combOne * $combTwo * $combThree - $commonNums;
        if($comb == 0){
            return false;
        }
        return $comb;
    }
    
    /**
     * 11X5  前 组选 普通
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_QianZuPu($nums, $n) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), $n);
        return $comb;
    }

    /**
     * 11X5  前 组选 胆拖
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_QianZuDan($nums, $n) {
        $areas = explode('#', $nums);
        $danArr = explode(',', $areas[0]);
        $tuoArr = explode(',', $areas[1]);
        $m = $n - count($danArr);
        if($m == 0) {
            return false;
        }
        $comb = Commonfun::getCombination(count($tuoArr), $m);
        return $comb;
    }

    /**
     * 11X5  乐选三
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_LeSan($nums) {
        $areas = explode(';', $nums);
        $oneArr = explode(',', $areas[0]);
        $twoArr = explode(',', $areas[1]);
        $threeArr = explode(',', $areas[2]);
        $combOne = Commonfun::getCombination(count($oneArr), 1);
        $combTwo = Commonfun::getCombination(count($twoArr), 1);
        $combThree = Commonfun::getCombination(count($threeArr), 1);
        $onetwoNums = count(array_intersect($oneArr, $twoArr));
        $onethreeNums = count(array_intersect($oneArr, $threeArr));
        $twothreeNums = count(array_intersect($twoArr, $threeArr));
        $allNums = count(array_intersect($oneArr, $twoArr, $threeArr));
        $commonNums = $onetwoNums * count($threeArr) + $onethreeNums * count($twoArr) + $twothreeNums * count($oneArr) - $allNums * 2;
        $comb = $combOne * $combTwo * $combThree - $commonNums;
        if($comb == 0){
            return false;
        }
        return $comb;
    }

   /**
     * 11X5  乐选 普通
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_LeXuanPu($nums, $n) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), $n);
        return $comb;
    }

    /**
     * 11X5  乐选 胆拖
     * @param string $nums
     * @return integer,boolen
     */
    public static function fun_LeXuanDan($nums, $n) {
        $areas = explode('#', $nums);
        $danArr = explode(',', $areas[0]);
        $tuoArr = explode(',', $areas[1]);
        $m = $n - count($danArr);
        if($m == 0) {
            return false;
        }
        $comb = Commonfun::getCombination(count($tuoArr), $m);
        return $comb;
    }

    /**
     * 11X5 任选生成单一投注
     * @param type $nums
     * @return boolean
     */
    public static function note_RenPu($nums, $n) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < $n) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, $n);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 11X5 任选胆拖生成单一投注
     * @param string $nums
     * @return array
     */
    public static function note_RenDan($nums, $n) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < $n && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, $n - count($balls0));
            $tOrder = implode(",", $balls0);
            foreach ($orderArrs as $val) {
                $orders[] = $tOrder . "," . implode(",", $val);
            }
            if (count($orders) > 0) {
                return $orders;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 11X5 前一直选生成单一投注
     * @param string $nums
     * @return array
     */
    public static function note_QianOneZhi($nums, $n) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < $n) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, $n);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }
    
    /**
     * 11X5 前二直选生成单一投注
     * @param string $nums
     * @return array
     */
    public static function note_QianTwoZhi($nums, $n) {
        $areas = explode(';', trim($nums, ";"));
        if (count($areas) == 2) {
            $balls1 = explode(',', $areas[0]);
            $balls2 = explode(',', $areas[1]);
        }
        if (!is_array($balls1) || !is_array($balls2)) {
            return false;
        }
        $orders = Commonfun::proDifCross_string(",", $balls1, $balls2);
        return $orders;
    }
    
    /**
     * 11X5 前三直选生成单一投注
     * @param string $nums
     * @return array
     */
    public static function note_QianThreeZhi($nums, $n) {
        $areas = explode(';', trim($nums, ";"));
        if (count($areas) == 3) {
            $balls1 = explode(',', $areas[0]);
            $balls2 = explode(',', $areas[1]);
            $balls3 = explode(',', $areas[2]);
        }
        if (!is_array($balls1) || !is_array($balls2) || !is_array($balls3)) {
            return false;
        }

        $orders = Commonfun::proDifCross_string(",", $balls1, $balls2, $balls3);
        return $orders;
    }
   
    /**
     * 11X5 前组选生成单一投注
     * @param string $nums
     * @return array
     */
    public static function note_QianZuPu($nums, $n) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < $n) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, $n);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 11X5 前组选胆拖生成单一投注
     * @param string $nums
     * @return array
     */
    public static function note_QianZuDan($nums, $n) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < $n && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, $n - count($balls0));
            $tOrder = implode(",", $balls0);
            foreach ($orderArrs as $val) {
                $orders[] = $tOrder . "," . implode(",", $val);
            }
            if (count($orders) > 0) {
                return $orders;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 11X5 乐三生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function note_LeSan($nums) {
        $areas = explode(';', trim($nums, ";"));
        if (count($areas) == 3) {
            $balls1 = explode(',', $areas[0]);
            $balls2 = explode(',', $areas[1]);
            $balls3 = explode(',', $areas[2]);
        }
        if (!is_array($balls1) || !is_array($balls2) || !is_array($balls3)) {
            return false;
        }

        $orders = Commonfun::proDifCross_string(",", $balls1, $balls2, $balls3);
        return $orders;
    }
    
    /**
     * 11X5 乐选四/五生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function note_LeXuanPu($nums, $n) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < $n) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, $n);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }


    /**
     * 11X5 乐选四/五胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function note_LeXuanDan($nums, $n) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < $n && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, $n - count($balls0));
            $tOrder = implode(",", $balls0);
            foreach ($orderArrs as $val) {
                $orders[] = $tOrder . "," . implode(",", $val);
            }
            if (count($orders) > 0) {
                return $orders;
            }
            return false;
        } else {
            return false;
        }
    }
}