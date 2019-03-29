<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\sports\helpers;

use app\modules\common\helpers\Commonfun;

class FJCalculation {

    /**
     * 福建11X5  任选
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_RenPu($nums, $n) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), $n);
        return $comb;
    }
    

    /**
     * 福建11X5  任选胆拖
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_RenDan($nums, $n) {
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
     * 福建11X5  前一直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_QianOneZhi($nums) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), 1);
        return $comb;
    }

    /**
     * 福建11X5  前二直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_QianTwoZhi($nums) {
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
     * 福建11X5  前三直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_QianThreeZhi($nums) {
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
     * 福建11X5  前 组选 普通
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_QianZuPu($nums, $n) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), $n);
        return $comb;
    }

    /**
     * 福建11X5  前 组选 胆拖
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_QianZuDan($nums, $n) {
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
     * 福建11X5  乐选三
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_LeSan($nums) {
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
     * 福建11X5  乐选 普通
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_LeXuanPu($nums, $n) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), $n);
        return $comb;
    }

    /**
     * 福建11X5  乐选 胆拖
     * @param string $nums
     * @return integer,boolen
     */
    public static function fj115Fun_LeXuanDan($nums, $n) {
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
     * 
     * @param type $nums
     * @return boolean
     */
    public static function fj115Note_201102($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 2);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 任选三单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201103($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 3);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 任选四单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201104($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 4);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 任选五单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201105($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 5);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 任选六单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201106($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 6);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 任选七单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201107($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 7);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 任选八单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201108($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 8);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 任选二复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201112($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 2) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 2);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 任选三复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201113($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 3) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 3);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 任选四复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201114($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 4) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 4);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 任选五复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201115($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 5) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 5);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 任选六复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201116($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 6) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 6);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 任选七复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201117($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 7) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 7);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 任选八复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201118($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 8) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 8);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 任选二胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201122($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 2 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 2 - count($balls0));
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
     * 任选三胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201123($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 3 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 3 - count($balls0));
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
     * 任选四胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201124($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 4 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 4 - count($balls0));
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
     * 任选五胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201125($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 5 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 5 - count($balls0));
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
     * 任选六胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201126($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 6 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 6 - count($balls0));
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
     * 任选七胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201127($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 7 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 7 - count($balls0));
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
     * 任选八胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201128($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 8 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 8 - count($balls0));
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
     * 前一单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201131($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 1);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 前二单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201132($nums) {
        $balls = explode(';', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 2);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 前三单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201133($nums) {
        $balls = explode(';', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 3);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 前二组选单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201134($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 2);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 前三组选单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201135($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 3);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 前一复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201141($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 1) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 1);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 前二复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201142($nums) {
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
     * 前三复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201143($nums) {
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
     * 前二组选复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201144($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 2) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 2);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 前三组选复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201145($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 3) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 3);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 前二组选胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201154($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 2 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 2 - count($balls0));
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
     * 前三组选胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201155($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 3 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 3 - count($balls0));
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
     * 乐选三单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201163($nums) {
        $balls = explode(';', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 3);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 乐选四单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201164($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 4);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }

    /**
     * 乐选五单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201165($nums) {
        $balls = explode(',', trim($nums, ","));
        $count = Commonfun::getCombination(count($balls), 5);
        $orders = [];
        if ($count == 1) {
            $orders[] = implode(",", $balls);
            return $orders;
        }
        return false;
    }
    
    /**
     * 乐选三复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201166($nums) {
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
     * 乐选四复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201167($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 4) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 4);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 乐选五复式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201168($nums) {
        $balls = explode(',', trim($nums, ","));
        if (!is_array($balls) || count($balls) < 5) {
            return false;
        }
        $orderArrs = Commonfun::getCombination_array($balls, 5);
        $orders = [];
        foreach ($orderArrs as $val) {
            $orders[] = implode(",", $val);
        }
        return $orders;
    }

    /**
     * 乐选四胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201169($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 4 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 4 - count($balls0));
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
     *乐选五胆拖生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function fj115Note_201170($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (count($balls0) < 5 && count($balls0) > 0) {
            $orderArrs = Commonfun::getCombination_array($balls1, 5 - count($balls0));
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