<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace app\modules\welfare\helpers;

use app\modules\common\helpers\Commonfun;

class FCCalculation{

    /**
     * 双色球直选单式
     */
    public static function ssqFun_100101($nums){
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
    
    
}

