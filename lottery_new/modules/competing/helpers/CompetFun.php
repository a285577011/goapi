<?php

namespace app\modules\competing\helpers;

class CompetFun {

    /**
     * 组装投注内容
     * @auther GL zyl
     * @param type $lotteryCode  彩种编号
     * @param type $majorData 优化 内容
     * @return type
     */
    public function buildUp($lotteryCode, $majorData) {
        $scheList = [];
        $playArr = [];
        $data = [];
        if ($lotteryCode != 3005 && $lotteryCode != 3011) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $res = [];
        $r = [];
        foreach ($majorData as $vm) {
            $betArr = explode('|', $vm['sub']);
            foreach ($betArr as $it) {
                preg_match($pattern, $it, $res);
                if ($lotteryCode != 3005 && $lotteryCode != 3011) {
                    $scheList[$res[1]][] = $res[2]; 
                } else {
                    $str = explode('*', $res[2]);
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                    $scheList[$res[1]][$r[1]][] = $r[2]; 
                }
            }
            if(!in_array($vm['subplay'], $playArr)) {
                $playArr[] = $vm['subplay'];
            }
        }
        if($lotteryCode != 3005 && $lotteryCode != 3011) {
            foreach ($scheList as $key => $val) {
                $concent[] = $key . '(' . implode(',', $val)   . ')';
            }
        }  else {
            foreach ($scheList as $key => $val) {
                $schStr = $key . '*';
                foreach ($val as $k => $ii) {
                    $schStr .= $k . '(' . implode(',', $ii) . ')' . '*';
                }
                $concent[] = rtrim($schStr, '*');
            }
        }
        $data['play'] = implode(',', $playArr);
        $data['nums'] = implode('|', $concent);
        return $data;
    }

}
