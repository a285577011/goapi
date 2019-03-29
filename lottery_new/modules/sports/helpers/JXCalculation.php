<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\sports\helpers;

use app\modules\common\helpers\Commonfun;

class JXCalculation {

    /**
     * 多乐11选5  任选普通
     * @param string $nums
     * @return integer,boolen
     */
    public static function jx115Fun_RenPu($nums, $n) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), $n);
        return $comb;
    }
    

    /**
     * 多乐11选5  任选胆拖
     * @param string $nums
     * @return integer,boolen
     */
    public static function jx115Fun_RenDan($nums, $n) {
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
     * 多乐11选5  前一直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function jx115Fun_QianOneZhi($nums) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), 1);
        return $comb;
    }

    /**
     * 多乐11选5  前二直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function jx115Fun_QianTwoZhi($nums) {
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
     * 多乐11选5  前三直选
     * @param string $nums
     * @return integer,boolen
     */
    public static function jx115Fun_QianThreeZhi($nums) {
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
     * 多乐11选5  前 组选 普通
     * @param string $nums
     * @return integer,boolen
     */
    public static function jx115Fun_QianZuPu($nums, $n) {
        $areas = explode(',', $nums);
        $comb = Commonfun::getCombination(count($areas), $n);
        return $comb;
    }

    /**
     * 多乐11选5  前 组选 胆拖
     * @param string $nums
     * @return integer,boolen
     */
    public static function jx115Fun_QianZuDan($nums, $n) {
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
     * 任选三单式生成单注投注单
     * @param type $nums
     * @return boolean
     */
    public static function jx115Note_200602($nums) {
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
    public static function jx115Note_200603($nums) {
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
    public static function jx115Note_200604($nums) {
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
    public static function jx115Note_200605($nums) {
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
    public static function jx115Note_200606($nums) {
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
    public static function jx115Note_200607($nums) {
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
    public static function jx115Note_200608($nums) {
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
    public static function jx115Note_200612($nums) {
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
    public static function jx115Note_200613($nums) {
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
    public static function jx115Note_200614($nums) {
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
    public static function jx115Note_200615($nums) {
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
    public static function jx115Note_200616($nums) {
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
    public static function jx115Note_200617($nums) {
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
    public static function jx115Note_200618($nums) {
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
    public static function jx115Note_200622($nums) {
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
    public static function jx115Note_200623($nums) {
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
    public static function jx115Note_200624($nums) {
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
    public static function jx115Note_200625($nums) {
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
    public static function jx115Note_200626($nums) {
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
    public static function jx115Note_200627($nums) {
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
    public static function jx115Note_200628($nums) {
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
    public static function jx115Note_200631($nums) {
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
    public static function jx115Note_200632($nums) {
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
    public static function jx115Note_200633($nums) {
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
    public static function jx115Note_200634($nums) {
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
    public static function jx115Note_200635($nums) {
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
    public static function jx115Note_200641($nums) {
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
    public static function jx115Note_200642($nums) {
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
    public static function jx115Note_200643($nums) {
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
    public static function jx115Note_200644($nums) {
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
    public static function jx115Note_200645($nums) {
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
    public static function jx115Note_200654($nums) {
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
    public static function jx115Note_200655($nums) {
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

}
