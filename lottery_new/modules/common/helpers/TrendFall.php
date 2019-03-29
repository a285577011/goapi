<?php

namespace app\modules\common\helpers;

class TrendFall {
    
        /**
     * 红蓝区遗漏的相关计算
     * @param array $data
     * @return array
     */
    public static function getDirectMissing($data) {
        $missing = [];
        $redNums = explode(',', $data[0]['red_omission']);
        $blueNums = explode(',', $data[0]['blue_omission']);
        $tailNums = explode(',', $data[0]['redtail_omission']);
        foreach ($redNums as $rk => $rv) {
            $rsumOut[$rk] = $rmaxMiss[$rk] = $ravgMiss[$rk] = $rsumMiss[$rk] = 0;
            $rmaxOut[$rk] = $rtmpOut[$rk] = 1;
        }
        foreach ($blueNums as $bk => $bv) {
            $bsumOut[$bk] = $bmaxMiss[$bk] = $bavgMiss[$bk] = $bsumMiss[$bk] = 0;
            $bmaxOut[$bk] = $btmpOut[$bk] = 1;
        }
        foreach ($tailNums as $tk => $tv) {
            $tsumOut[$tk] = $tmaxMiss[$tk] = $tavgMiss[$tk] = $tsumMiss[$tk] = 0;
            $tmaxOut[$tk] = $ttmpOut[$tk] = 1;
        }

        foreach ($data as $k => $v) {
            $redMiss[$k] = explode(',', $v['red_omission']);
            $blueMiss[$k] = explode(',', $v['blue_omission']);
            $tailMiss[$k] = explode(',', $v['redtail_omission']);
            if ($k > 0) {
                $lastRed = $redMiss[$k - 1];
                $lastBlue = $blueMiss[$k - 1];
                $lastTail = $tailMiss[$k - 1];
            } else {
                $lastRed = [];
                $lastBlue = [];
                $lastTail = [];
            }
            $redMissing = self::getCalulMiss($redMiss[$k], $lastRed, $rsumOut, $rmaxMiss, $rmaxOut, $rsumMiss, $rtmpOut);
            $blueMissing = self::getCalulMiss($blueMiss[$k], $lastBlue, $bsumOut, $bmaxMiss, $bmaxOut, $bsumMiss, $btmpOut);
            $tailMissing = self::getCalulMiss($tailMiss[$k], $lastTail, $tsumOut, $tmaxMiss, $tmaxOut, $tsumMiss, $ttmpOut);
            $rmaxMiss = $redMissing['max_miss'];
            $rmaxOut = $redMissing['max_out'];
            $rsumMiss = $redMissing['sum_miss'];
            $rsumOut = $redMissing['sum_out'];
            $rtmpOut = $redMissing['tmp_max_out'];
            $bmaxMiss = $blueMissing['max_miss'];
            $bmaxOut = $blueMissing['max_out'];
            $bsumMiss = $blueMissing['sum_miss'];
            $bsumOut = $blueMissing['sum_out'];
            $btmpOut = $blueMissing['tmp_max_out'];
            $tmaxMiss = $tailMissing['max_miss'];
            $tmaxOut = $tailMissing['max_out'];
            $tsumMiss = $tailMissing['sum_miss'];
            $tsumOut = $tailMissing['sum_out'];
            $ttmpOut = $tailMissing['tmp_max_out'];
            $rcrentMiss = $redMiss[$k];
            $bcrentMiss = $blueMiss[$k];
            $tcrentMiss = $tailMiss[$k];
        }
        foreach ($redMissing['sum_out'] as $key => $val) {
            $ravgMiss[$key] = round($redMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($blueMissing['sum_out'] as $key => $val) {
            $bavgMiss[$key] = round($blueMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($tailMissing['sum_out'] as $key => $val) {
            $tavgMiss[$key] = round($tailMissing['sum_miss'][$key] / ($val + 1));
        }
        $missing['red_missing'] = array_merge($redMissing, ['avg_miss' => $ravgMiss, 'current_miss' => $rcrentMiss]);
        $missing['blue_missing'] = array_merge($blueMissing, ['avg_miss' => $bavgMiss, 'current_miss' => $bcrentMiss]);
        $missing['redtail_missing'] = array_merge($tailMissing, ['avg_miss' => $tavgMiss, 'current_miss' => $tcrentMiss]);
        return $missing;
    }

    /**
     * 组式投注遗漏数的相关计算
     * @param array $data
     * @return array
     */
    public static function getGroupMissing($data) {
        $missing = [];
        $countNums = explode(',', $data[0]['group_omission']);
        for ($i = 0; $i < count($countNums); $i++) {
            $hsumOut[$i] = $hmaxMiss[$i] = $havgMiss[$i] = $hsumMiss[$i] = 0;
            $hmaxOut[$i] = $htmpOut[$i] = 1;
            $tsumOut[$i] = $tmaxMiss[$i] = $tavgMiss[$i] = $tsumMiss[$i] = 0;
            $tmaxOut[$i] = $ttmpOut[$i] = 1;
            $dsumOut[$i] = $dmaxMiss[$i] = $davgMiss[$i] = $dsumMiss[$i] = 0;
            $dmaxOut[$i] = $dtmpOut[$i] = 1;
            $gsumOut[$i] = $gmaxMiss[$i] = $gavgMiss[$i] = $gsumMiss[$i] = 0;
            $gmaxOut[$i] = $gtmpOut[$i] = 1;
            $spsumOut[$i] = $spmaxMiss[$i] = $spavgMiss[$i] = $spsumMiss[$i] = 0;
            $spmaxOut[$i] = $sptmpOut[$i] = 1;
            $stsumOut[$i] = $stmaxMiss[$i] = $stavgMiss[$i] = $stsumMiss[$i] = 0;
            $stmaxOut[$i] = $sttmpOut[$i] = 1;
        }
        $sumNums = explode(',', $data[0]['sum_omission']);
        for($j = 0; $j < count($sumNums); $j++) {
            $ssumOut[$j] = $smaxMiss[$j] = $savgMiss[$j] = $ssumMiss[$j] = 0;
            $smaxOut[$j] = $stmpOut[$j] = 1;
        }
        foreach ($data as $k => $v) {
            $hundMiss[$k] = explode(',', $v['hundred_omission']);
            $tenMiss[$k] = explode(',', $v['ten_omission']);
            $digitsMiss[$k] = explode(',', $v['digits_omission']);
            $groupMiss[$k] = explode(',', $v['group_omission']);
            $sumMiss[$k] = explode(',', $v['sum_omission']);
            $spanMiss[$k] = explode(',', $v['span_omission']);
            $tailMiss[$k] = explode(',', $v['sumtail_omission']);
            if ($k > 0) {
                $lastHund = $hundMiss[$k - 1];
                $lastTen = $tenMiss[$k - 1];
                $lastDig = $digitsMiss[$k - 1];
                $lastGroup = $groupMiss[$k - 1];
                $lastSum = $sumMiss[$k - 1];
                $lastSpan = $spanMiss[$k - 1];
                $lastTail = $tailMiss[$k - 1];
            } else {
                $lastDig = [];
                $lastGroup = [];
                $lastHund = [];
                $lastTen = [];
                $lastSum = [];
                $lastSpan = [];
                $lastTail = [];
            }
            $hundredMissing = self::getCalulMiss($hundMiss[$k], $lastHund, $hsumOut, $hmaxMiss, $hmaxOut, $hsumMiss, $htmpOut);
            $tenMissing = self::getCalulMiss($tenMiss[$k], $lastTen, $tsumOut, $tmaxMiss, $tmaxOut, $tsumMiss, $ttmpOut);
            $digitsMissing = self::getCalulMiss($digitsMiss[$k], $lastDig, $dsumOut, $dmaxMiss, $dmaxOut, $dsumMiss, $dtmpOut);
            $groupMissing = self::getCalulMiss($groupMiss[$k], $lastGroup, $gsumOut, $gmaxMiss, $gmaxOut, $gsumMiss, $gtmpOut);
            $sumMissing = self::getCalulMiss($sumMiss[$k], $lastSum, $ssumOut, $smaxMiss, $smaxOut, $ssumMiss, $stmpOut);
            $spanMissing = self::getCalulMiss($spanMiss[$k], $lastSpan, $spsumOut, $spmaxMiss, $spmaxOut, $spsumMiss, $sptmpOut);
            $tailMissing = self::getCalulMiss($tailMiss[$k], $lastTail, $stsumOut, $stmaxMiss, $stmaxOut, $stsumMiss, $sttmpOut);
            $hmaxMiss = $hundredMissing['max_miss'];
            $hmaxOut = $hundredMissing['max_out'];
            $hsumMiss = $hundredMissing['sum_miss'];
            $hsumOut = $hundredMissing['sum_out'];
            $htmpOut = $hundredMissing['tmp_max_out'];
            $tmaxMiss = $tenMissing['max_miss'];
            $tmaxOut = $tenMissing['max_out'];
            $tsumMiss = $tenMissing['sum_miss'];
            $tsumOut = $tenMissing['sum_out'];
            $ttmpOut = $tenMissing['tmp_max_out'];
            $dmaxMiss = $digitsMissing['max_miss'];
            $dmaxOut = $digitsMissing['max_out'];
            $dsumMiss = $digitsMissing['sum_miss'];
            $dsumOut = $digitsMissing['sum_out'];
            $dtmpOut = $digitsMissing['tmp_max_out'];
            $gmaxMiss = $groupMissing['max_miss'];
            $gmaxOut = $groupMissing['max_out'];
            $gsumMiss = $groupMissing['sum_miss'];
            $gsumOut = $groupMissing['sum_out'];
            $gtmpOut = $groupMissing['tmp_max_out'];
            
            $smaxMiss = $sumMissing['max_miss'];
            $smaxOut = $sumMissing['max_out'];
            $ssumMiss = $sumMissing['sum_miss'];
            $ssumOut = $sumMissing['sum_out'];
            $stmpOut = $sumMissing['tmp_max_out'];
            
            $spmaxMiss = $spanMissing['max_miss'];
            $spmaxOut = $spanMissing['max_out'];
            $spsumMiss = $spanMissing['sum_miss'];
            $spsumOut = $spanMissing['sum_out'];
            $sptmpOut = $spanMissing['tmp_max_out'];
            
            $stmaxMiss = $tailMissing['max_miss'];
            $stmaxOut = $tailMissing['max_out'];
            $stsumMiss = $tailMissing['sum_miss'];
            $stsumOut = $tailMissing['sum_out'];
            $sttmpOut = $tailMissing['tmp_max_out'];
            $hcrentMiss = $hundMiss[$k];
            $tcrentMiss = $tenMiss[$k];
            $dcrentMiss = $digitsMiss[$k];
            $gcrentMiss = $groupMiss[$k];
            $screntMiss = $sumMiss[$k];
            $spcrentMiss = $spanMiss[$k];
            $stcrentMiss = $tailMiss[$k];
        }
        foreach ($hundredMissing['sum_out'] as $key => $val) {
            $havgMiss[$key] = round($hundredMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($tenMissing['sum_out'] as $key => $val) {
            $tavgMiss[$key] = round($tenMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($digitsMissing['sum_out'] as $key => $val) {
            $davgMiss[$key] = round($digitsMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($groupMissing['sum_out'] as $key => $val) {
            $gavgMiss[$key] = round($groupMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($sumMissing['sum_out'] as $key => $val) {
            $savgMiss[$key] = round($sumMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($spanMissing['sum_out'] as $key => $val) {
            $spavgMiss[$key] = round($spanMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($tailMissing['sum_out'] as $key => $val) {
            $stavgMiss[$key] = round($tailMissing['sum_miss'][$key] / ($val + 1));
        }
        $missing['hundred_missing'] = array_merge($hundredMissing, ['avg_miss' => $havgMiss, 'current_miss' => $hcrentMiss]);
        $missing['ten_missing'] = array_merge($tenMissing, ['avg_miss' => $tavgMiss, 'current_miss' => $tcrentMiss]);
        $missing['digits_missing'] = array_merge($digitsMissing, ['avg_miss' => $davgMiss, 'current_miss' => $dcrentMiss]);
        $missing['group_missing'] = array_merge($groupMissing, ['avg_miss' => $gavgMiss, 'current_miss' => $gcrentMiss]);
        $missing['sum_missing'] = array_merge($sumMissing, ['avg_miss' => $savgMiss, 'current_miss' => $screntMiss]);
        $missing['span_missing'] = array_merge($spanMissing, ['avg_miss' => $spavgMiss, 'current_miss' => $spcrentMiss]);
        $missing['sumtail_missing'] = array_merge($tailMissing, ['avg_miss' => $stavgMiss, 'current_miss' => $stcrentMiss]);
        return $missing;
    }

    /**
     * 多位数投注遗漏数的相关计算
     * @param array $data
     * @return array
     */
    public static function getMultiMissing($data) {
        $missing = [];
        for ($i = 0; $i < 10; $i++) {
            $msumOut[$i] = $mmaxMiss[$i] = $mavgMiss[$i] = $msumMiss[$i] = 0;
            $mmaxOut[$i] = $mtmpOut[$i] = 1;
            $htsumOut[$i] = $htmaxMiss[$i] = $htavgMiss[$i] = $htsumMiss[$i] = 0;
            $htmaxOut[$i] = $httmpOut[$i] = 1;
            $ttsumOut[$i] = $ttmaxMiss[$i] = $ttavgMiss[$i] = $ttsumMiss[$i] = 0;
            $ttmaxOut[$i] = $tttmpOut[$i] = 1;
            $thsumOut[$i] = $thmaxMiss[$i] = $thavgMiss[$i] = $thsumMiss[$i] = 0;
            $thmaxOut[$i] = $thtmpOut[$i] = 1;
            $hsumOut[$i] = $hmaxMiss[$i] = $havgMiss[$i] = $hsumMiss[$i] = 0;
            $hmaxOut[$i] = $htmpOut[$i] = 1;
            $tsumOut[$i] = $tmaxMiss[$i] = $tavgMiss[$i] = $tsumMiss[$i] = 0;
            $tmaxOut[$i] = $ttmpOut[$i] = 1;
            $dsumOut[$i] = $dmaxMiss[$i] = $davgMiss[$i] = $dsumMiss[$i] = 0;
            $dmaxOut[$i] = $dtmpOut[$i] = 1;
        }
        foreach ($data as $k => $v) {
            if (!empty($v['million_omission'])) {
                $millionMiss[$k] = explode(',', $v['million_omission']);
            }
            if (!empty($v['hundred_thousand_omission'])) {
                $hundthouMiss[$k] = explode(',', $v['hundred_thousand_omission']);
            }
            $tenthouMiss[$k] = explode(',', $v['ten_thousand_omission']);
            $thousandMiss[$k] = explode(',', $v['thousand_omission']);
            $hundMiss[$k] = explode(',', $v['hundred_omission']);
            $tenMiss[$k] = explode(',', $v['ten_omission']);
            $digitsMiss[$k] = explode(',', $v['digits_omission']);

            if ($k > 0) {
                if (!empty($v['million_omission'])) {
                    $lastMillion = $millionMiss[$k - 1];
                }
                if (!empty($v['hundred_thousand_omission'])) {
                    $lastHundthou = $hundthouMiss[$k - 1];
                }
                $lastTenthou = $tenthouMiss[$k - 1];
                $lastThousand = $thousandMiss[$k - 1];
                $lastHund = $hundMiss[$k - 1];
                $lastTen = $tenMiss[$k - 1];
                $lastDig = $digitsMiss[$k - 1];
            } else {
                $lastDig = [];
                $lastHund = [];
                $lastTen = [];
                $lastHundthou = [];
                $lastMillion = [];
                $lastTenthou = [];
                $lastThousand = [];
            }
            if (!empty($v['million_omission'])) {
                $millionMissing = self::getCalulMiss($millionMiss[$k], $lastMillion, $msumOut, $mmaxMiss, $mmaxOut, $msumMiss, $mtmpOut);
                $mcrentMiss = $millionMiss[$k];
                $mmaxMiss = $millionMissing['max_miss'];
                $mmaxOut = $millionMissing['max_out'];
                $msumMiss = $millionMissing['sum_miss'];
                $msumOut = $millionMissing['sum_out'];
                $mtmpOut = $millionMissing['tmp_max_out'];
            }
            if (!empty($v['hundred_thousand_omission'])) {
                $htcrentMiss = $hundthouMiss[$k];
                $hundthousandMissing = self::getCalulMiss($hundthouMiss[$k], $lastHundthou, $htsumOut, $htmaxMiss, $htmaxOut, $htsumMiss, $httmpOut);
                $htmaxMiss = $hundthousandMissing['max_miss'];
                $htmaxOut = $hundthousandMissing['max_out'];
                $htsumMiss = $hundthousandMissing['sum_miss'];
                $htsumOut = $hundthousandMissing['sum_out'];
                $httmpOut = $hundthousandMissing['tmp_max_out'];
            }
            $tenthousandMissing = self::getCalulMiss($tenthouMiss[$k], $lastTenthou, $ttsumOut, $ttmaxMiss, $ttmaxOut, $ttsumMiss, $tttmpOut);
            $thousandMissing = self::getCalulMiss($thousandMiss[$k], $lastThousand, $thsumOut, $thmaxMiss, $thmaxOut, $thsumMiss, $thtmpOut);
            $hundredMissing = self::getCalulMiss($hundMiss[$k], $lastHund, $hsumOut, $hmaxMiss, $hmaxOut, $hsumMiss, $htmpOut);
            $tenMissing = self::getCalulMiss($tenMiss[$k], $lastTen, $tsumOut, $tmaxMiss, $tmaxOut, $tsumMiss, $ttmpOut);
            $digitsMissing = self::getCalulMiss($digitsMiss[$k], $lastDig, $dsumOut, $dmaxMiss, $dmaxOut, $dsumMiss, $dtmpOut);


            $ttmaxMiss = $tenthousandMissing['max_miss'];
            $ttmaxOut = $tenthousandMissing['max_out'];
            $ttsumMiss = $tenthousandMissing['sum_miss'];
            $ttsumOut = $tenthousandMissing['sum_out'];
            $tttmpOut = $tenthousandMissing['tmp_max_out'];
            $thmaxMiss = $thousandMissing['max_miss'];
            $thmaxOut = $thousandMissing['max_out'];
            $thsumMiss = $thousandMissing['sum_miss'];
            $thsumOut = $thousandMissing['sum_out'];
            $thtmpOut = $thousandMissing['tmp_max_out'];
            $hmaxMiss = $hundredMissing['max_miss'];
            $hmaxOut = $hundredMissing['max_out'];
            $hsumMiss = $hundredMissing['sum_miss'];
            $hsumOut = $hundredMissing['sum_out'];
            $htmpOut = $hundredMissing['tmp_max_out'];
            $tmaxMiss = $tenMissing['max_miss'];
            $tmaxOut = $tenMissing['max_out'];
            $tsumMiss = $tenMissing['sum_miss'];
            $tsumOut = $tenMissing['sum_out'];
            $ttmpOut = $tenMissing['tmp_max_out'];
            $dmaxMiss = $digitsMissing['max_miss'];
            $dmaxOut = $digitsMissing['max_out'];
            $dsumMiss = $digitsMissing['sum_miss'];
            $dsumOut = $digitsMissing['sum_out'];
            $dtmpOut = $digitsMissing['tmp_max_out'];
            $ttcrentMiss = $tenthouMiss[$k];
            $thcrentMiss = $thousandMiss[$k];
            $hcrentMiss = $hundMiss[$k];
            $tcrentMiss = $tenMiss[$k];
            $dcrentMiss = $digitsMiss[$k];
        }

        if (!empty($v['million_omission'])) {
            foreach ($millionMissing['sum_out'] as $key => $val) {
                $mavgMiss[$key] = round($millionMissing['sum_miss'][$key] / ($val + 1));
            }
            $missing['million_missing'] = array_merge($millionMissing, ['avg_miss' => $mavgMiss, 'current_miss' => $mcrentMiss]);
        }
        if (!empty($v['hundred_thousand_omission'])) {
            foreach ($hundthousandMissing['sum_out'] as $key => $val) {
                $mavgMiss[$key] = round($hundthousandMissing['sum_miss'][$key] / ($val + 1));
            }
            $missing['hundred_thousand_missing'] = array_merge($hundthousandMissing, ['avg_miss' => $htavgMiss, 'current_miss' => $htcrentMiss]);
        }

        foreach ($tenthousandMissing['sum_out'] as $key => $val) {
            $ttavgMiss[$key] = round($tenthousandMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($thousandMissing['sum_out'] as $key => $val) {
            $thavgMiss[$key] = round($thousandMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($hundredMissing['sum_out'] as $key => $val) {
            $havgMiss[$key] = round($hundredMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($tenMissing['sum_out'] as $key => $val) {
            $tavgMiss[$key] = round($tenMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($digitsMissing['sum_out'] as $key => $val) {
            $davgMiss[$key] = round($digitsMissing['sum_miss'][$key] / ($val + 1));
        }

        $missing['ten_thousand_missing'] = array_merge($tenthousandMissing, ['avg_miss' => $ttavgMiss, 'current_miss' => $ttcrentMiss]);
        $missing['thousand_missing'] = array_merge($thousandMissing, ['avg_miss' => $thavgMiss, 'current_miss' => $thcrentMiss]);
        $missing['hundred_missing'] = array_merge($hundredMissing, ['avg_miss' => $havgMiss, 'current_miss' => $hcrentMiss]);
        $missing['ten_missing'] = array_merge($tenMissing, ['avg_miss' => $tavgMiss, 'current_miss' => $tcrentMiss]);
        $missing['digits_missing'] = array_merge($digitsMissing, ['avg_miss' => $davgMiss, 'current_miss' => $dcrentMiss]);
        return $missing;
    }
    
    /**
     * 11选5投注遗漏数的相关计算
     * @param array $data
     * @return array
     */
    public static function getElevenMissing($data) {
        $missing = [];
        for ($i = 0; $i < 11; $i++) {
            $osumOut[$i] = $omaxMiss[$i] = $oavgMiss[$i] = $osumMiss[$i] = 0;
            $omaxOut[$i] = $otmpOut[$i] = 1;
            $q1sumOut[$i] = $q1maxMiss[$i] = $q1avgMiss[$i] = $q1sumMiss[$i] = 0;
            $q1maxOut[$i] = $q1tmpOut[$i] = 1;
            $q2sumOut[$i] = $q2maxMiss[$i] = $q2avgMiss[$i] = $q2sumMiss[$i] = 0;
            $q2maxOut[$i] = $q2tmpOut[$i] = 1;
            $q3sumOut[$i] = $q3maxMiss[$i] = $q3avgMiss[$i] = $q3sumMiss[$i] = 0;
            $q3maxOut[$i] = $q3tmpOut[$i] = 1;
            $q2gsumOut[$i] = $q2gmaxMiss[$i] = $q2gavgMiss[$i] = $q2gsumMiss[$i] = 0;
            $q2gmaxOut[$i] = $q2gtmpOut[$i] = 1;
            $q3gsumOut[$i] = $q3gmaxMiss[$i] = $q3gavgMiss[$i] = $q3gsumMiss[$i] = 0;
            $q3gmaxOut[$i] = $q3gtmpOut[$i] = 1;
        }
        for($j=0; $j<7; $j++) {
            $ssumOut[$j] = $smaxMiss[$j] = $savgMiss[$j] = $ssumMiss[$j] = 0;
            $smaxOut[$j] = $stmpOut[$j] = 1;
        }
        foreach ($data as $k => $v) {
            $optionalMiss[$k] = explode(',', $v['optional_omission']);
            $qian1Miss[$k] = explode(',', $v['qone_omission']);
            $qian2Miss[$k] = explode(',', $v['qtwo_omission']);
            $qian3Miss[$k] = explode(',', $v['qthree_omission']);
            $qian2zuMiss[$k] = explode(',', $v['qtwo_group_omission']);
            $qian3zuMiss[$k] = explode(',', $v['qthree_group_omission']);
            $spanMiss[$k] = explode(',', $v['span_omission']);
            
            if ($k > 0) {
                $lastOptional = $optionalMiss[$k-1];
                $lastQianOne = $qian1Miss[$k - 1];
                $lastQianTwo = $qian2Miss[$k - 1];
                $lastQianThree = $qian3Miss[$k - 1];
                $lastQianTwoGroup = $qian2zuMiss[$k - 1];
                $lastQianThreeGroup = $qian3zuMiss[$k - 1];
                $lastSpan = $spanMiss[$k - 1];
            } else {
                $lastOptional = [];
                $lastQianOne = [];
                $lastQianTwo = [];
                $lastQianThree = [];
                $lastQianTwoGroup = [];
                $lastQianThreeGroup = [];
                $lastSpan = [];
            }
            $optionalMissing = self::getCalulMiss($optionalMiss[$k], $lastOptional, $osumOut, $omaxMiss, $omaxOut, $osumMiss, $otmpOut);
            $qian1Missing = self::getCalulMiss($qian1Miss[$k], $lastQianOne, $q1sumOut, $q1maxMiss, $q1maxOut, $q1sumMiss, $q1tmpOut);
            $qian2Missing = self::getCalulMiss($qian2Miss[$k], $lastQianTwo, $q2sumOut, $q2maxMiss, $q2maxOut, $q2sumMiss, $q2tmpOut);
            $qian3Missing = self::getCalulMiss($qian3Miss[$k], $lastQianThree, $q3sumOut, $q3maxMiss, $q3maxOut, $q3sumMiss, $q3tmpOut);
            $qian2zuMissing = self::getCalulMiss($qian2zuMiss[$k], $lastQianTwoGroup, $q2gsumOut, $q2gmaxMiss, $q2gmaxOut, $q2gsumMiss, $q2gtmpOut);
            $qian3zuMissing = self::getCalulMiss($qian3zuMiss[$k], $lastQianThreeGroup, $q3gsumOut, $q3gmaxMiss, $q3gmaxOut, $q3gsumMiss, $q3gtmpOut);
            $spanMissing = self::getCalulMiss($spanMiss[$k], $lastSpan, $ssumOut, $smaxMiss, $smaxOut, $ssumMiss, $stmpOut);
            
            $omaxMiss = $optionalMissing['max_miss'];
            $omaxOut = $optionalMissing['max_out'];
            $osumMiss = $optionalMissing['sum_miss'];
            $osumOut = $optionalMissing['sum_out'];
            $otmpOut = $optionalMissing['tmp_max_out'];
            $q1maxMiss = $qian1Missing['max_miss'];
            $q1maxOut = $qian1Missing['max_out'];
            $q1sumMiss = $qian1Missing['sum_miss'];
            $q1sumOut = $qian1Missing['sum_out'];
            $q1tmpOut = $qian1Missing['tmp_max_out'];
            $q2maxMiss = $qian2Missing['max_miss'];
            $q2maxOut = $qian2Missing['max_out'];
            $q2sumMiss = $qian2Missing['sum_miss'];
            $q2sumOut = $qian2Missing['sum_out'];
            $q2tmpOut = $qian2Missing['tmp_max_out'];
            $q3maxMiss = $qian3Missing['max_miss'];
            $q3maxOut = $qian3Missing['max_out'];
            $q3sumMiss = $qian3Missing['sum_miss'];
            $q3sumOut = $qian3Missing['sum_out'];
            $q3tmpOut = $qian3Missing['tmp_max_out'];
            $q2gmaxMiss = $qian2zuMissing['max_miss'];
            $q2gmaxOut = $qian2zuMissing['max_out'];
            $q2gsumMiss = $qian2zuMissing['sum_miss'];
            $q2gsumOut = $qian2zuMissing['sum_out'];
            $q2gtmpOut = $qian2zuMissing['tmp_max_out'];
            $q3gmaxMiss = $qian3zuMissing['max_miss'];
            $q3gmaxOut = $qian3zuMissing['max_out'];
            $q3gsumMiss = $qian3zuMissing['sum_miss'];
            $q3gsumOut = $qian3zuMissing['sum_out'];
            $q3gtmpOut = $qian3zuMissing['tmp_max_out'];
            $smaxMiss = $spanMissing['max_miss'];
            $smaxOut = $spanMissing['max_out'];
            $ssumMiss = $spanMissing['sum_miss'];
            $ssumOut = $spanMissing['sum_out'];
            $stmpOut = $spanMissing['tmp_max_out'];
            $ocrentMiss = $optionalMiss[$k];
            $q1crentMiss = $qian1Miss[$k];
            $q2crentMiss = $qian2Miss[$k];
            $q3crentMiss = $qian3Miss[$k];
            $q2gcrentMiss = $qian2zuMiss[$k];
            $q3gcrentMiss = $qian3zuMiss[$k];
            $spancrentMiss = $spanMiss[$k];
        }

        foreach ($optionalMissing['sum_out'] as $key => $val) {
            $oavgMiss[$key] = round($optionalMissing['sum_miss'][$key] / ($val + 1));
        }

        foreach ($qian1Missing['sum_out'] as $key => $val) {
            $q1avgMiss[$key] = round($qian1Missing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($qian2Missing['sum_out'] as $key => $val) {
            $q2avgMiss[$key] = round($qian2Missing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($qian3Missing['sum_out'] as $key => $val) {
            $q3avgMiss[$key] = round($qian3Missing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($qian2zuMissing['sum_out'] as $key => $val) {
            $q2gavgMiss[$key] = round($qian2zuMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($qian3zuMissing['sum_out'] as $key => $val) {
            $q3gavgMiss[$key] = round($qian3zuMissing['sum_miss'][$key] / ($val + 1));
        }
        foreach ($spanMissing['sum_out'] as $key => $val) {
            $savgMiss[$key] = round($spanMissing['sum_miss'][$key] / ($val + 1));
        }
        $missing['optional_missing'] = array_merge($optionalMissing, ['avg_miss' => $oavgMiss, 'current_miss' => $ocrentMiss]);
        $missing['qian_one_missing'] = array_merge($qian1Missing, ['avg_miss' => $q1avgMiss, 'current_miss' => $q1crentMiss]);
        $missing['qian_two_missing'] = array_merge($qian2Missing, ['avg_miss' => $q2avgMiss, 'current_miss' => $q2crentMiss]);
        $missing['qian_three_missing'] = array_merge($qian3Missing, ['avg_miss' => $q3avgMiss, 'current_miss' => $q3crentMiss]);
        $missing['qian_two_group_missing'] = array_merge($qian2zuMissing, ['avg_miss' => $q2gavgMiss, 'current_miss' => $q2gcrentMiss]);
        $missing['qian_three_group_missing'] = array_merge($qian3zuMissing, ['avg_miss' => $q3gavgMiss, 'current_miss' => $q3gcrentMiss]);
        $missing['span_missing'] = array_merge($spanMissing, ['avg_miss' => $savgMiss, 'current_miss' => $spancrentMiss]);
        return $missing;
    }
    

    /**
     * 计算遗漏数
     * @param array $arr
     * @param array $last
     * @param array $sumOut
     * @param array $maxMiss
     * @param array $maxOut
     * @param array $sumMiss
     * @return array
     */
    public static function getCalulMiss($arr, $last, $sumOut, $maxMiss, $maxOut, $sumMiss, $tmpmaxOut) {
        foreach ($arr as $key => $val) {
            if ($val < 1) {
                $sumOut[$key] += 1;
                if (!empty($last)) {
                    if ($val == $last[$key]) {
                        $tmpmaxOut[$key] += 1;
                    } else {
                        $tmpmaxOut[$key] = 1;
                    }
                }
            } else {
                if (!empty($last)) {
                    $sumMiss[$key] += 1;
                } else {
                    $sumMiss[$key] = $val;
                }
            }
            if ($val > $maxMiss[$key]) {
                $maxMiss[$key] = $val;
            }
            if ($tmpmaxOut[$key] > $maxOut[$key]) {
                $maxOut[$key] = $tmpmaxOut[$key];
            }
        }
        $result = ['sum_out' => $sumOut, 'sum_miss' => $sumMiss, 'max_miss' => $maxMiss, 'max_out' => $maxOut, 'tmp_max_out' => $tmpmaxOut];
        return $result;
    }
    
    /**
     * 开奖推送
     * @param type $lotteryCode  彩种编号
     * @param type $periods 期数
     * @param type $openNums 开奖号码
     * @return type
     */
    public function trendWebsocket($lotteryCode, $periods, $openNums) {
        $surl = \Yii::$app->params['trend_websocket'];
        $post_data = ['lottery_code' => $lotteryCode, 'periods' => $periods, 'open_number' => $openNums];
        $curl_ret = \Yii::sendCurlPost($surl, $post_data);
        return $curl_ret;
    }
}

