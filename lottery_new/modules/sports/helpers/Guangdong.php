<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\sports\helpers;

use app\modules\common\helpers\Commonfun;

class Guangdong {

    public static function difArray($arr) {
        foreach ($arr as $key => $val) {
            unset($arr[$key]);
            if (in_array($val, $arr)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 任选二单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200502($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 2);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 任选三单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200503($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 3);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 任选四单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200504($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 4);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 任选五单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200505($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 5);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 任选六单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200506($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 6);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 任选七单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200507($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 7);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 任选八单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200508($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 8);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 任选二复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200512($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 2);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 任选三复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200513($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 3);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 任选四复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200514($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 4);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 任选五复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200515($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 5);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 任选六复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200516($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 6);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 任选七复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200517($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 7);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 任选八复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200518($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 8);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 任选二胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200522($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 2 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 2 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 任选三胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200523($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 3 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 3 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 任选四胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200524($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 4 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 4 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 任选五胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200525($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 5 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 5 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 任选六胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200526($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 6 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 6 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 任选七胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200527($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 7 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 7 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 任选八胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200528($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 8 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 8 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 前一单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200531($nums) {
        $totalNum = count(explode(',', trim($nums, ",")));
        $count = Commonfun::getCombination($totalNum, 1);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 前二单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200532($nums) {
        $areas = explode(';', $nums);
        if (count($areas) == 2) {
            $balls1 = explode(',', $areas[0]);
            $balls2 = explode(',', $areas[1]);
            $sameNum = 0;
            foreach ($balls1 as $val) {
                if (in_array($val, $balls2)) {
                    $sameNum++;
                }
            }
            $comb = count($balls1) * count($balls2) - $sameNum;
            return $comb;
        }
        return false;
    }

    /**
     * 前三单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200533($nums) {
        $areas = explode(';', $nums);
        if (count($areas) == 3) {
            $balls1 = explode(',', $areas[0]);
            $balls2 = explode(',', $areas[1]);
            $balls3 = explode(',', $areas[2]);
            $sameNum12 = 0;
            $sameNum13 = 0;
            $sameNum23 = 0;
            $sameNum = 0;
            foreach ($balls1 as $val) {
                if (in_array($val, $balls2)) {
                    $sameNum12++;
                    if (in_array($val, $balls3)) {
                        $sameNum++;
                    }
                }
                if (in_array($val, $balls3)) {
                    $sameNum13++;
                }
            }
            foreach ($balls2 as $val) {
                if (in_array($val, $balls3)) {
                    $sameNum23++;
                }
            }
            $count1 = count($balls1);
            $count2 = count($balls2);
            $count3 = count($balls3);
            $totalSameNum = $sameNum12 * $count3 + $sameNum13 * $count2 + $sameNum23 * $count1 - $sameNum * 2;
            $comb = $count1 * $count2 * $count3 - $totalSameNum;
            return $comb;
        }
        return false;
    }

    /**
     * 前二组选单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200534($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 2);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 前三组选单式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200535($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 3);
        if ($count == 1) {
            return $count;
        }
        return false;
    }

    /**
     * 前一复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200541($nums) {
        $totalNum = count(explode(',', trim($nums, ",")));
        $count = Commonfun::getCombination($totalNum, 1);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 前二复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200542($nums) {
        $areas = explode(';', $nums);
        if (count($areas) == 2) {
            $balls1 = explode(',', $areas[0]);
            $balls2 = explode(',', $areas[1]);
            $sameNum = 0;
            foreach ($balls1 as $val) {
                if (in_array($val, $balls2)) {
                    $sameNum++;
                }
            }
            $comb = count($balls1) * count($balls2) - $sameNum;
            return $comb;
        }
        return false;
    }

    /**
     * 前三复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200543($nums) {
        $areas = explode(';', $nums);
        if (count($areas) == 3) {
            $balls1 = explode(',', $areas[0]);
            $balls2 = explode(',', $areas[1]);
            $balls3 = explode(',', $areas[2]);
            $sameNum12 = 0;
            $sameNum13 = 0;
            $sameNum23 = 0;
            $sameNum = 0;
            foreach ($balls1 as $val) {
                if (in_array($val, $balls2)) {
                    $sameNum12++;
                    if (in_array($val, $balls3)) {
                        $sameNum++;
                    }
                }
                if (in_array($val, $balls3)) {
                    $sameNum13++;
                }
            }
            foreach ($balls2 as $val) {
                if (in_array($val, $balls3)) {
                    $sameNum23++;
                }
            }
            $count1 = count($balls1);
            $count2 = count($balls2);
            $count3 = count($balls3);
            $totalSameNum = $sameNum12 * $count3 + $sameNum13 * $count2 + $sameNum23 * $count1 - $sameNum * 2;
            $comb = $count1 * $count2 * $count3 - $totalSameNum;
            return $comb;
        }
        return false;
    }

    /**
     * 前二组选复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200544($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 2);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 前三组选复式注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200545($nums) {
        $balls = explode(',', trim($nums, ","));
        if (self::difArray($balls) == false) {
            return false;
        } else {
            $totalNum = count($balls);
        }
        $count = Commonfun::getCombination($totalNum, 3);
        if ($count > 0) {
            return $count;
        }
        return false;
    }

    /**
     * 前二组选胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200554($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 2 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 2 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * 前三组选胆拖注数
     * @param string $nums
     * @return integer,boolen
     */
    public static function GD_200555($nums) {
        $area = explode('#', trim($nums, "#"));
        if (count($area) != 2) {
            return false;
        }
        $balls0 = explode(',', trim($area[0], ","));
        $balls1 = explode(',', trim($area[1], ","));
        if (self::difArray(array_merge($balls0, $balls1)) == false) {
            return false;
        } else {
            $totalNum0 = count($balls0);
            $totalNum1 = count($balls1);
        }
        if ($totalNum0 < 3 && $totalNum0 > 0) {
            $count = Commonfun::getCombination($totalNum1, 3 - $totalNum0);
            if ($count > 0) {
                return $count;
            }
            return false;
        } else {
            return false;
        }
    }

    ////////////////////////////////////////////详情单生成

    /**
     * 任选二单式生成单注投注单
     * @param string $nums
     * @return array
     */
    public static function gdNote_200502($nums) {
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
    public static function gdNote_200503($nums) {
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
    public static function gdNote_200504($nums) {
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
    public static function gdNote_200505($nums) {
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
    public static function gdNote_200506($nums) {
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
    public static function gdNote_200507($nums) {
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
    public static function gdNote_200508($nums) {
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
    public static function gdNote_200512($nums) {
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
    public static function gdNote_200513($nums) {
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
    public static function gdNote_200514($nums) {
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
    public static function gdNote_200515($nums) {
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
    public static function gdNote_200516($nums) {
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
    public static function gdNote_200517($nums) {
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
    public static function gdNote_200518($nums) {
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
    public static function gdNote_200522($nums) {
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
    public static function gdNote_200523($nums) {
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
    public static function gdNote_200524($nums) {
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
    public static function gdNote_200525($nums) {
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
    public static function gdNote_200526($nums) {
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
    public static function gdNote_200527($nums) {
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
    public static function gdNote_200528($nums) {
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
    public static function gdNote_200531($nums) {
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
    public static function gdNote_200532($nums) {
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
    public static function gdNote_200533($nums) {
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
    public static function gdNote_200534($nums) {
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
    public static function gdNote_200535($nums) {
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
    public static function gdNote_200541($nums) {
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
    public static function gdNote_200542($nums) {
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
    public static function gdNote_200543($nums) {
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
    public static function gdNote_200544($nums) {
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
    public static function gdNote_200545($nums) {
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
    public static function gdNote_200554($nums) {
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
    public static function gdNote_200555($nums) {
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

    ////////////////////////////////////兑奖区域

    /**
     * 任选二单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200502($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 2) {
            return 6;
        }
        return false;
    }

    /**
     * 任选三单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200503($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 3) {
            return 19;
        }
        return false;
    }

    /**
     * 任选四单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200504($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 4) {
            return 78;
        }
        return false;
    }

    /**
     * 任选五单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200505($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 540;
        }
        return false;
    }

    /**
     * 任选六单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200506($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 90;
        }
        return false;
    }

    /**
     * 任选七单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200507($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 26;
        }
        return false;
    }

    /**
     * 任选八单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200508($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 9;
        }
        return false;
    }

    /**
     * 任选二复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200512($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 2) {
            return 9;
        }
        return false;
    }

    /**
     * 任选三复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200513($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 3) {
            return 19;
        }
        return false;
    }

    /**
     * 任选四复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200514($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 4) {
            return 78;
        }
        return false;
    }

    /**
     * 任选五复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200515($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 540;
        }
        return false;
    }

    /**
     * 任选六复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200516($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 90;
        }
        return false;
    }

    /**
     * 任选七复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200517($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 26;
        }
        return false;
    }

    /**
     * 任选八复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200518($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 9;
        }
        return false;
    }

    /**
     * 任选二胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200522($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 2) {
            return 6;
        }
        return false;
    }

    /**
     * 任选三胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200523($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 3) {
            return 19;
        }
        return false;
    }

    /**
     * 任选四胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200524($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 4) {
            return 78;
        }
        return false;
    }

    /**
     * 任选五胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200525($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 540;
        }
        return false;
    }

    /**
     * 任选六胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200526($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 90;
        }
        return false;
    }

    /**
     * 任选七胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200527($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 26;
        }
        return false;
    }

    /**
     * 任选八胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200528($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            }
        }
        if ($orderRightNum == 5) {
            return 9;
        }
        return false;
    }

    /**
     * 前一单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200531($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        if ($betVals[0] == $openVals[0]) {
            return 13;
        }
        return false;
    }

    /**
     * 前二单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200532($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        if (count($betVals) != 2) {
            return false;
        }
        foreach ($betVals as $key => $val) {
            if ($val != $openVals[$key]) {
                return false;
            }
        }
        return 130;
    }

    /**
     * 前三单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200533($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        if (count($betVals) != 3) {
            return false;
        }
        foreach ($betVals as $key => $val) {
            if ($val != $openVals[$key]) {
                return false;
            }
        }
        return 1170;
    }

    /**
     * 前二组选单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200534($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            } else {
                return false;
            }
        }
        if ($orderRightNum == 2) {
            return 65;
        }
        return false;
    }

    /**
     * 前三组选单式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200535($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            } else {
                return false;
            }
        }
        if ($orderRightNum == 3) {
            return 195;
        }
        return false;
    }

    /**
     * 前一复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200541($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        if ($betVals[0] == $openVals[0]) {
            return 13;
        }
        return false;
    }

    /**
     * 前二复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200542($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        if (count($betVals) != 2) {
            return false;
        }
        foreach ($betVals as $key => $val) {
            if ($val != $openVals[$key]) {
                return false;
            }
        }
        return 130;
    }

    /**
     * 前三复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200543($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        if (count($betVals) != 3) {
            return false;
        }
        foreach ($betVals as $key => $val) {
            if ($val != $openVals[$key]) {
                return false;
            }
        }
        return 1170;
    }

    /**
     * 前二组选复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200544($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            } else {
                return false;
            }
        }
        if ($orderRightNum == 2) {
            return 65;
        }
        return false;
    }

    /**
     * 前三组选复式兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200545($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            } else {
                return false;
            }
        }
        if ($orderRightNum == 3) {
            return 195;
        }
        return false;
    }

    /**
     * 前二组选胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200554($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            } else {
                return false;
            }
        }
        if ($orderRightNum == 2) {
            return 65;
        }
        return false;
    }

    /**
     * 前三组选胆拖兑奖
     * @param string $nums
     * @return integer,boolen
     */
    public static function gdWinning_200555($betVal, $openVal) {
        $betVals = explode(',', trim($betVal, "^"));
        $openVals = explode(',', trim($openVal, "^"));
        $orderRightNum = 0; //猜对数字个数
        foreach ($betVals as $val) {
            if (in_array($val, $openVals)) {
                $orderRightNum++;
            } else {
                return false;
            }
        }
        if ($orderRightNum == 3) {
            return 195;
        }
        return false;
    }

}
