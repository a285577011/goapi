<?php

namespace app\modules\orders\helpers;

use app\modules\competing\helpers\CompetConst;
use app\modules\orders\helpers\DetailDeal;
use app\modules\orders\helpers\OrderDeal;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\Constants;

class DealPrint {

    /**
     * 获取打印票样票根
     * @param type $lotteryCode
     * @param type $betStr
     * @param type $playCode
     * @param type $buildCode
     * @param type $mul
     * @param type $majorType
     * @param type $majorData
     * @return type
     */
    public static function dealPrint($lotteryCode, $betStr, $playCode, $buildCode, $mul, $majorType = 0, $majorData = []) {
        $price = Constants::PRICE;
        if ($majorType == 0) {
            $scheMCN = CompetConst::COUNT_MCN;
            $mCN = CompetConst::M_CHUAN_N;
            if (array_key_exists($playCode, $mCN)) {
                $playCode = $mCN[$playCode];
            }
            $dealOrder = new DetailDeal();
            if ($lotteryCode != 3011 && $lotteryCode != 3005) {
                $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            } else {
                $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
            }
            $betNums = explode("|", trim($betStr, '^'));
            $midsArr = OrderDeal::getMids($lotteryCode, $betNums);
            $midArr = array_keys($midsArr);
            $res = [];
            $bet = [];
            $r = [];
            $list = [];
            $n = 0;
            $betC = [];
            foreach ($betNums as $k => $iv) {
                preg_match($pattern, $iv, $res);
                if (!in_array($lotteryCode, [3005, 3011])) {
                    if ($playCode == 1) {
                        $bet[$n]['count'] = $dealOrder->getCompetingCount($lotteryCode, ['play' => $playCode, 'nums' => $iv]);
                        $bet[$n]['play_code'] = $playCode;
                        $bet[$n]['bet_val'] = [$iv];
                    } else {
                        $betVal[$res[1]][] = $iv;
                    }
                    $n++;
                } else {
                    $resArr = explode('*', trim($res[2], '*'));
                    foreach ($resArr as $str) {
                        preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                        if ($playCode == 1) {
                            $bet[$n]['count'] = $dealOrder->getCompetingCount($lotteryCode, ['play' => $playCode, 'nums' => $res[1] . '*' . $str]);
                            $bet[$n]['play_code'] = $playCode;
                            $bet[$n]['bet_val'] = [$res[1] . "*" . $str];
                            $n++;
                        } else {
                            $betVal[$res[1]][] = $res[1] . "*" . $str;
                        }
                        if (!in_array($r[1], $betC)) {
                            $betC[] = $r[1];
                        }
                    }
//                    $n++;
                }
            }
            if ($playCode == 1) {
                $list = $bet;
            } else {
                if (($lotteryCode == '3007' || $lotteryCode == '3009' || $lotteryCode == '3003' || in_array('3003', $betC) || in_array('3007', $betC) || in_array('3009', $betC)) && count($midArr) > 4) {
                    $newMid = Commonfun::getCombination_array($midArr, 4);
                } elseif (($lotteryCode == '3008' || in_array('3008', $betC)) && count($midArr) > 6) {
                    $newMid = Commonfun::getCombination_array($midArr, 6);
                } elseif (count($midArr) > 8) {
                    $newMid = Commonfun::getCombination_array($midArr, 8);
                } else {
                    $newMid[] = $midArr;
                }
                foreach ($newMid as $midVal) {
                    $crossStr = '';
                    foreach ($midVal as $mid) {
                        $crossStr .= ',$betVal[' . $mid . ']';
                    }
                    eval('$combData[] = \app\modules\common\helpers\Commonfun::proCross_string("|"' . $crossStr . ');');
                }
                $combArr = [];
                foreach ($combData as $cdArr) {
                    foreach ($cdArr as $cd) {
                        $combArr[] = $cd;
                    }
                }
                $startArr = explode('|', $combArr[0]);
                for ($i = 0; $i < count($combArr); $i++) {
                    $tempArr = explode('|', $combArr[$i]);
                    $num = count(array_intersect($startArr, $tempArr));
                    if ($num == count($startArr)) {
                        $newComb[] = ['num' => count($tempArr), 'val' => $combArr[$i]];
                    } else {
                        $newComb[] = ['num' => count($tempArr) - $num, 'val' => $combArr[$i]];
                    }
                }
                foreach ($newComb as $order) {
                    $orderBy[] = $order['num'];
                }
                array_multisort($orderBy, SORT_DESC, $newComb);
                $orderArr = [];
                $newCacul = [];
                $newOrder = [];
                $codeArr = explode(',', $playCode);
                foreach ($newComb as $c_val) {
                    $sub1 = [];
                    $betArr = explode('|', $c_val['val']);
                    $arr1 = array_intersect($betArr, $orderArr);
                    if (count($arr1) < count($betArr)) {
                        $cacul = $dealOrder->doCacul($lotteryCode, $orderArr, $c_val['val'], $codeArr);
                    } else {
                        $contents = ['play' => $playCode, 'nums' => $c_val['val']];
                        $dealArr = $dealOrder->dealDetail($contents);
                        foreach ($dealArr as $cv) {
                            $contents2 = ['play' => $cv['play_code'], 'nums' => $cv['bet_val']];
                            $count = $dealOrder->getCompetingCount($lotteryCode, $contents2);
                            $sub1[] = ['nums' => $cv['bet_val'], 'count' => $count, 'play_code' => $cv['play_code']];
                        }
                        $cacul = $sub1;
                    }
                    foreach ($cacul as $caculArr) {
                        if (!in_array($caculArr['nums'], $newOrder)) {
                            $newOrder[] = $caculArr['nums'];
                            $newCacul[] = $caculArr;
                        }
                    }
                    foreach ($betArr as $br) {
                        if (!in_array($br, $orderArr)) {
                            $orderArr[] = $br;
                        }
                    }
                }
                $newDetail = [];
                foreach ($newCacul as &$val) {
                    $contents = ['play' => $val['play_code'], 'nums' => $val['nums']];
                    $detail = $dealOrder->dealDetail($contents);
                    foreach ($detail as $vi) {
                        if (in_array($vi['bet_val'], $newDetail)) {
                            $val['same_play'][] = $vi['play_code'];
                            $val['same_bet'][] = $vi['bet_val'];
                        } else {
                            $newDetail[] = $vi['bet_val'];
                        }
                    }
                }
                $allCacul = [];
                $m = 0;
                foreach ($newCacul as $key => &$val) {
                    if (array_key_exists('same_play', $val)) {
                        if (count($val['same_bet']) == $val['count']) {
                            $a[] = $val;
                            continue;
                        } else {
                            $subPlay = explode(',', $val['play_code']);
                            $sub = array_intersect($subPlay, $val['same_play']);
                            $diffSub = array_diff($subPlay, $val['same_play']);
                            if (count($sub) == count($subPlay)) {
                                $contents = ['play' => implode(',', $sub), 'nums' => $val['nums']];
                                $detail = $dealOrder->dealDetail($contents);
                                foreach ($detail as $vv) {
                                    if (!in_array($vv['bet_val'], $val['same_bet'])) {
                                        $allCacul[$m]['nums'] = $vv['bet_val'];
                                        $contents = ['play' => $vv['play_code'], 'nums' => $vv['bet_val']];
                                        $count = $dealOrder->getCompetingCount($lotteryCode, $contents);
                                        $allCacul[$m]['count'] = $count;
                                        $allCacul[$m]['play_code'] = $vv['play_code'];
                                    }
                                }
                            } else {
                                $contents = ['play' => implode(',', $diffSub), 'nums' => $val['nums']];
                                $count = $dealOrder->getCompetingCount($lotteryCode, $contents);
                                $allCacul[$m]['nums'] = $val['nums'];
                                $allCacul[$m]['count'] = $count;
                                $allCacul[$m]['play_code'] = implode(',', $diffSub);
                            }
                        }
                    } else {
                        $allCacul[$m] = $val;
                    }
                    $m++;
                }
                $oneCacul = [];
                $moreCacul = [];
                $detailArr = [];
                foreach ($allCacul as $all_val) {
                    if ($all_val['count'] == 1) {
                        $oneCacul[] = $all_val;
                    } else {
                        $moreCacul[] = $all_val;
                        $contents = ['play' => $all_val['play_code'], 'nums' => $all_val['nums']];
                        $detail = $dealOrder->dealDetail($contents);
                        foreach ($detail as $vv) {
                            if (!in_array($vv['bet_val'], $detailArr)) {
                                $vvArr = explode('|', $vv['bet_val']);
                                sort($vvArr);
                                $detailArr[] = implode('|', $vvArr);
                            }
                        }
                    }
                }
                $oneArr = $oneCacul;
                foreach ($oneCacul as $key => $one_val) {
                    $arrNums = [];
                    unset($oneArr[$key]);
                    $numsArr = explode('|', $one_val['nums']);
                    foreach ($numsArr as $nums) {
                        preg_match($pattern, $nums, $res2);
                        $arrNums[$res2[1]] = $nums;
                    }
                    if (count($arrNums) < 8) {
                        foreach ($oneArr as $arr_val) {
                            $subNums = $numsArr;
                            $flag = 0;
                            $arrNums2 = [];
                            $subArr = explode('|', $arr_val['nums']);
                            foreach ($subArr as $subs) {
                                preg_match($pattern, $subs, $res3);
                                $arrNums2[$res3[1]] = $subs;
                            }
                            foreach ($arrNums2 as $key => $diff) {
                                if (array_key_exists($key, $arrNums)) {
                                    if ($diff != $arrNums[$key]) {
                                        $flag = 1;
                                    }
                                } else {
                                    $subNums[] = $diff;
                                }
                            }
                            if ($flag != 0) {
                                continue;
                            }
                            if (count($arrNums) > 8) {
                                continue;
                            }
                            if ($arr_val['play_code'] != $one_val['play_code']) {
                                $play = $one_val['play_code'] . ',' . $arr_val['play_code'];
                            } else {
                                $play = $one_val['play_code'];
                            }
                            $contents = ['play' => $play, 'nums' => implode('|', $subNums)];
                            $detail = $dealOrder->dealDetail($contents);
                            foreach ($detail as $vv) {
                                if (in_array($vv['bet_val'], $detailArr)) {
                                    $flag = 1;
                                }
                            }
                            if ($flag != 0) {
                                continue;
                            }
                            foreach ($detail as $vv) {
                                $detailArr[] = $vv['bet_val'];
                            }
                            $count = $dealOrder->getCompetingCount($lotteryCode, $contents);
                            $moreCacul[] = ['nums' => implode('|', $subNums), 'count' => $count, 'play_code' => $play];
                        }
                    }
                    sort($numsArr);
                    if (!in_array(implode('|', $numsArr), $detailArr)) {
                        $moreCacul[] = $one_val;
                    }
                }
                foreach ($moreCacul as $key => $m_val) {
                    $moreBet = explode('|', $m_val['nums']);
                    sort($moreBet);
                    $bet = [];
                    $newMCN = [];
                    foreach ($moreBet as $k => $b_val) {
                        $bet['bet_val'][$k] = $b_val;
                    }
//                    $b_playCode = [];
                    if (!empty($buildCode)) {
                        $countSche = count($moreBet);
                        if ($countSche > 2) {
                            $mCN = $scheMCN[$countSche];
                            $newMCN = array_flip($mCN);
                        }
                    }
                    if (!empty($$buildCode) && array_key_exists($buildCode, $newMCN)) {
                        $newPlayCode = $newMCN[$m_val['play_code']];
                        if (count($midArr) <= $countSche && $playCode == $m_val['play_code']) {
                            $newPlayCode = $buildCode;
                        }
                        $bet['play_code'] = $newPlayCode;
                    } else {
                        $bet['play_code'] = $m_val['play_code'];
                    }
                    $bet['count'] = $m_val['count'];
                    $list[] = $bet;
                }
            }
            $listBy = [];
            foreach ($list as $lk) {
                $listBy[] = $lk['count'];
            }
            array_multisort($listBy, SORT_DESC, $list);
            $ii = 1;
            $listAll = [];
            foreach ($list as &$lv) {
                $money = $lv['count'] * $price;
                if ($mul > 99) {
                    $lv['list_td'] = 'TD' . $ii;
                    $m = ceil($mul / 99);
                    $modNums = ($mul % 99);
                    for ($j = 1; $j <= $m; $j++) {
                        if ($j == $m) {
                            $lv['bet_money'] = $money * $modNums;
                            $lv['bet_double'] = $modNums;
                        } else {
                            $lv['bet_money'] = $money * 99;
                            $lv['bet_double'] = 99;
                        }
                        $listAll[] = $lv;
                    }
                } else {
                    $lv['bet_money'] = $money * $mul;
                    $lv['bet_double'] = $mul;
                    $listAll[] = $lv;
                }
                $ii++;
            }
        } else {
            $n = 0;
            foreach ($majorData as &$vm) {
                $count = ceil($vm['mul'] / 99);
                $vm['sub'] = rtrim($vm['sub'], '|');
                $allBetDouble = $vm['mul'];
                for ($num = 1; $num <= $count; $num++) {
                    if ($allBetDouble > 99) {
                        $betDouble = 99;
                    } else {
                        $betDouble = $allBetDouble;
                    }
                    $allBetDouble = $allBetDouble - $betDouble;
                    $listAll[$n]['bet_val'] = explode('|', $vm['sub']);
                    $listAll[$n]['play_code'] = $vm['subplay'];
                    $listAll[$n]['bet_money'] = $betDouble * $price;
                    $listAll[$n]['bet_double'] = $betDouble;
                    $listAll[$n]['count'] = 1;
                    $n++;
                }
            }
        }
        $dealOrder = self::getPrint($lotteryCode, $listAll);
        return $dealOrder;
    }

    /**
     * 获取打印样票串关
     * @param type $listAll
     * @return type
     */
    public static function getPrint($lotteryCode, $listAll) {
        $schMcn = CompetConst::SCHE_COUNT_MCN;
        $price = Constants::PRICE;
        $betList = [];
        $dealOrder = new DetailDeal();
        foreach ($listAll as $val) {
            $schNums = count($val['bet_val']);
            $mcn = $schMcn[$schNums];
            if (array_key_exists($val['play_code'], $mcn)) {
                $betList[] = ['bet_val' => $val['bet_val'], 'play_code' => $mcn[$val['play_code']], 'bet_double' => $val['bet_double'], 'count' => $val['count'], 'bet_money' => $val['bet_money']];
            } else {
                $playArr = explode(',', $val['play_code']);
                foreach ($playArr as $v) {
                    $contents = ['play' => $v, 'nums' => implode('|', $val['bet_val'])];
                    $count = $dealOrder->getCompetingCount($lotteryCode, $contents);
                    $betMoney = $price * $count * $val['bet_double'];
                    $betList[] = ['bet_val' => $val['bet_val'], 'play_code' => $mcn[$v], 'bet_double' => $val['bet_double'], 'count' => $count, 'bet_money' => $betMoney];
                }
            }
        }
        return $betList;
    }

    /**
     * 判断是否能打印样票
     * @param type $lotteryCode
     * @param type $betStr
     * @return type
     */
    public static function getIsPrint($lotteryCode, $betStr) {
        if ($lotteryCode == 3011 || $lotteryCode == 3005) {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
            $betNums = explode("|", trim($betStr, '^'));
            $res = [];
            $r = [];
            $betC = [];
            foreach ($betNums as $iv) {
                preg_match($pattern, $iv, $res);
                $resArr = explode('*', trim($res[2], '*'));
                foreach ($resArr as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    if (!in_array($r[1], $betC)) {
                        $betC[] = $r[1];
                    }
                }
            }
            if (count($betC) == 1) {
                if ($betC[0] == '3003' && count($betNums) > 3) {
                    return ['code' => 109, 'msg' => '该投注单玩法包含胜分差且场次数大于3场，不可打印样票'];
                }
                $lotteryCode = $betC[0];
            } elseif (array_intersect(['3003', '3007', '3009'], $betC) && count($betNums) > 3) {
                return ['code' => 109, 'msg' => '该投注单玩法包含比分或半全场且场次数大于3场，不可打印样票'];
            } elseif (in_array('3008', $betC) && count($betNums) > 6) {
                return ['code' => 109, 'msg' => '该投注单玩法包含总进球且场次数大于6场,不可打印样票'];
            } else {
                $lotteryCode = $lotteryCode;
            }
        }
        return ['code' => 600, 'msg' => '可打印样票', 'data' => $lotteryCode];
    }

    /**
     * 任选打印样票票根
     * @param type $lotteryCode
     * @param type $betNums
     * @param type $mul
     * @param type $count
     * @param type $divisor
     * @return type
     */
    public static function getOptionalPrint($lotteryCode, $betNums, $mul, $count) {
        if ($lotteryCode == 4001) {
            $divisor = 1;
            $outNums = ceil($mul / $divisor);
        } else {
            $divisor = 10;
            $fmod = fmod($mul, $divisor);
            $outNums = ceil(($mul - $fmod) / $divisor);
        }

        $price = Constants::PRICE;
        $allBetDouble = $mul;
        $n = 1;
        $m = 0;
        $listAll = [];
        if ($lotteryCode == 4001) {
            $isN = $count == 1 ? 5 : 1;
            for ($num = 1; $num <= $outNums; $num++) {
                if ($allBetDouble > $divisor) {
                    $betDouble = $divisor;
                } else {
                    $betDouble = $allBetDouble;
                }
                $allBetDouble = $allBetDouble - $betDouble;
                if ($n == $isN) {
                    $listAll[$m]['bet_val'][] = $betNums;
                    $listAll[$m]['bet_money'] = $betDouble * $price * $count * $n;
                    $listAll[$m]['bet_double'] = $betDouble;
                    $listAll[$m]['play_code'] = $isN == 5 ?  '400101' : '400102';
                    $n = 1;
                    $m++;
                } else {
                    $listAll[$m]['bet_val'][] = $betNums;
                    $listAll[$m]['bet_money'] = $betDouble * $price * $count * $n;
                    $listAll[$m]['bet_double'] = $betDouble;
                    $listAll[$m]['play_code'] = $isN == 5 ?  '400101' : '400102';
                    $n++;
                }
            }
        } else {
            $nums = 9;
            $numArr = explode(',', trim($betNums, '^'));
            $valData = [];
            foreach ($numArr as $key => $val) {
                if ($val != '_') {
                    $valData[$key] = [$key => $val];
                }
            }
            $numArrs = $valData;
            $combination = Commonfun::getCombination_array($numArrs, $nums);
            if ($outNums != 0) {
                $listAll[$m]['bet_money'] = 0;
            }
            $singleArr = [];
            $doubleArr = [];
            foreach ($combination as $v) {
                $d = 1;
                $allBetDouble = $mul;
                $betArr = ['_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_'];
                foreach ($numArr as $k => $vv) {
                    foreach ($v as $i) {
                        if (array_key_exists($k, $i)) {
                            $d *= strlen($i[$k]);
                            $betArr[$k] = $i[$k];
                            continue;
                        }
                    }
                }
                $betDetail = implode(',', $betArr) . '^';
                if ($d === 1) {
                    $singleArr[] = ['bet_val' => $betDetail, 'count' => $d];
                } else {
                    $doubleArr[] = ['bet_val' => $betDetail, 'count' => $d];
                }
            }
            $n = 1;
            $m = 0;
            if ($outNums != 0) {
                $listAll[$m]['bet_money'] = 0;
                foreach ($singleArr as $single) {
                    for ($num = 1; $num <= $outNums; $num++) {
                        if ($allBetDouble > $divisor) {
                            $betDouble = $divisor;
                        } else {
                            $betDouble = $allBetDouble;
                        }
                        $allBetDouble = $allBetDouble - $betDouble;
                        if ($n == 3) {
                            $listAll[$m]['bet_val'][] = $single['bet_val'];
                            $listAll[$m]['bet_money'] += $betDouble * $price * $single['count'];
                            $listAll[$m]['bet_double'] = $betDouble;
                            $listAll[$m]['play_code'] = '400201';
                            $n = 1;
                            $m++;
                            $listAll[$m]['bet_money'] = 0;
                        } else {
                            $listAll[$m]['bet_val'][] = $single['bet_val'];
                            $listAll[$m]['bet_money'] += $betDouble * $price * $single['count'];
                            $listAll[$m]['bet_double'] = $betDouble;
                            $listAll[$m]['play_code'] = '400201';
                            $n++;
                        }
                    }
                }
            }

            $nn = 1;
            $mm = count($listAll);
            if (!empty($singleArr)) {
                $listAll[$mm]['bet_money'] = 0;
                foreach ($singleArr as $single) {
                    if ($nn == 3) {
                        $listAll[$mm]['bet_val'][] = $single['bet_val'];
                        $listAll[$mm]['bet_money'] += $fmod * $price * $single['count'];
                        $listAll[$mm]['bet_double'] = $fmod;
                        $listAll[$mm]['play_code'] = '400201';
                        $nn = 1;
                        $mm++;
                        $listAll[$mm]['bet_money'] = 0;
                    } else {
                        $listAll[$mm]['bet_val'][] = $single['bet_val'];
                        $listAll[$mm]['bet_money'] += $fmod * $price * $single['count'];
                        $listAll[$mm]['bet_double'] = $fmod;
                        $listAll[$mm]['play_code'] = '400201';
                        $nn++;
                    }
                }
            }

            $mmm = count($listAll);
            foreach ($doubleArr as $double) {
                for ($num = 1; $num <= ceil($mul / $divisor); $num++) {
                    if ($allBetDouble > $divisor) {
                        $betDouble = $divisor;
                    } else {
                        $betDouble = $allBetDouble;
                    }
                    $allBetDouble = $allBetDouble - $betDouble;

                    $listAll[$mmm]['bet_val'][] = $double['bet_val'];
                    $listAll[$mmm]['bet_money'] = $betDouble * $price * $double['count'];
                    $listAll[$mmm]['bet_double'] = $betDouble;
                    $listAll[$mmm]['play_code'] = '400202';
                    $mmm++;
                }
            }
        }
        return $listAll;
    }

}
