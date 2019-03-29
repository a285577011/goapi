<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\welfare\helpers;

use app\modules\common\models\LotteryRecord;
use app\modules\common\models\LotteryTime;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\Constants;
use yii\db\Query;
    

class LogicApplication {

    public static function ssqNote_1001($arr) {

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

    public static function tdNote_100201($arr){
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
    
    public static function qlcNote_1003($arr) {
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

}
