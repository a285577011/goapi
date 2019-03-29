<?php

namespace app\modules\orders\helpers;

use app\modules\competing\helpers\CompetConst;
use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Constants;
use app\modules\competing\models\LanSchedule;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\Schedule;
use app\modules\common\models\BettingDetail;

class DetailDeal {

    /**
     * 票务处理明细
     * @param type $lotteryType
     * @param type $lotteryOrderCode
     * @param int $page
     * @param type $size
     * @return type
     */
    public function getTicketing($lotteryType, $lotteryOrderCode, $page, $size) {
        $schePlay = CompetConst::SCHEDULE_PLAY;
        $price = Constants::PRICE;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $lottery = Constants::LOTTERY;
        $manner = Constants::MANNER;
        $scheMCN = CompetConst::COUNT_MCN;
        $field = ['lottery_id', 'play_code', 'play_name', 'bet_val', 'major_type', 'bet_double', 'bet_money', 'build_code', 'build_name', 'count'];
        $field2 = ['lottery_id', 'bet_val', 'play_name', 'play_code', 'bet_double', 'bet_money'];
        $orderData = LotteryOrder::find()->select($field)->where(['lottery_order_code' => $lotteryOrderCode])->asArray()->one();
        if (empty($orderData)) {
            return ['code' => 109, 'msg' => '查询结果不存在'];
        }
        if ($orderData['lottery_id'] != 3011 && $orderData['lottery_id'] != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $midArr = [];
        $betNums = explode("|", trim($orderData['bet_val'], '^'));
        $majorType = $orderData['major_type'];
        $result = [];
        $lotteryCode = $orderData['lottery_id'];
        foreach ($betNums as $bv) {
            preg_match($pattern, $bv, $result);
            if (!in_array($result[1], $midArr)) {
                $midArr[] = $result[1];
            }
        }

        if (in_array($lotteryType, $football)) {
            $scheData = Schedule::find()->select(['schedule_mid', 'schedule_code'])->where(['in', 'schedule_mid', $midArr])->indexBy('schedule_mid')->asArray()->all();
            $betAbb = '竞足' . $lottery[$orderData['lottery_id']];
        } elseif (in_array($lotteryType, $basketball)) {
            $scheData = LanSchedule::find()->select(['schedule_mid', 'schedule_code'])->where(['in', 'schedule_mid', $midArr])->indexBy('schedule_mid')->asArray()->all();
            $betAbb = '竞篮' . $lottery[$orderData['lottery_id']];
        } else {
            return ['code' => 109, 'msg' => '参数错误'];
        }

        $res = [];
        $bet = [];
        $r = [];
        $list = [];
        if ($majorType != 0) {
            $total = BettingDetail::find()->where(['lottery_order_code' => $lotteryOrderCode])->count();
            $pages = ceil($total / $size);
            $offset = ($page - 1) * $size;
            $betDetail = BettingDetail::find($field2)->where(['lottery_order_code' => $lotteryOrderCode])->limit($size)->offset($offset)->asArray()->all();
            foreach ($betDetail as $key => $dv) {
                $dBet = explode('|', $dv['bet_val']);
                foreach ($dBet as $k => $iv) {
                    preg_match($pattern, $iv, $res);
                    if (!in_array($lotteryCode, [3005, 3011])) {
                        $bet['bet_val'][$k] = $scheData[$res[1]]['schedule_code'] . '>' . $schePlay[$lotteryCode][$res[2]];
                    } else {
                        $str = explode('*', $res[2]);
                        preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                        $bet['bet_val'][$k] = $scheData[$res[1]]['schedule_code'] . '>' . $schePlay[$r[1]][$r[2]];
                    }
                    if ($majorType != 0) {
                        $bet['bet_double'] = $dv['bet_double'];
                        $bet['play_name'] = [$dv['play_name']];
                        $bet['bet_money'] = $dv['bet_money'];
                    }
                }
                $listAll[] = $bet;
            }
            $size = count($betDetail);
        } else {
            $n = 0;
            foreach ($betNums as $k => $iv) {
                preg_match($pattern, $iv, $res);
                if (!in_array($lotteryCode, [3005, 3011])) {
                    $arr = explode(",", $res[2]);
                    $betName = '';
                    foreach ($arr as $a) {
                        $betName .= $schePlay[$lotteryCode][$a] . '，';
                    }
                    if ($orderData['play_code'] == 1) {
                        $bet[$n]['count'] = $this->getCompetingCount($lotteryCode, ['play' => $orderData['play_code'], 'nums' => $iv]);
                        $bet[$n]['play_name'] = [$manner[$orderData['play_code']]];
                        $bet[$n]['bet_val'] = [$scheData[$res[1]]['schedule_code'] . '>' . rtrim($betName, '，')];
                    } else {
                        $betVal[$res[1]][] = $iv;
                    }
                    $n++;
                } else {
                    $resArr = explode('*', trim($res[2],'*'));
                    foreach ($resArr as $str) {
                        if ($orderData['play_code'] == 1) {
                            preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                            $arr = explode(",", $r[2]);
                            $betName = '';
                            foreach ($arr as $a) {
                                $betName .= $schePlay[$r[1]][$a] . '，';
                            }
                            $bet[$n]['count'] = $this->getCompetingCount($lotteryCode, ['play' => $orderData['play_code'], 'nums' => $res[1] . '*' . $str]);
                            $bet[$n]['play_name'] = [$manner[$orderData['play_code']]];
                            $bet[$n]['bet_val'] = [$scheData[$res[1]]['schedule_code'] . '>' . rtrim($betName, '，')];
                            $n++;
                        }  else {
                            $betVal[$res[1]][] = $res[1] . "*" . $str;
                        }
                    }
//                    $n++;
                }
            }
            if ($orderData['play_code'] == 1) {
                $list = $bet;
            } else {
                if (count($midArr) > 8) {
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
                $codeArr = explode(',', $orderData['play_code']);
                foreach ($newComb as $c_val) {
                    $sub1 = [];
                    $betArr = explode('|', $c_val['val']);
                    $arr1 = array_intersect($betArr, $orderArr);
                    if (count($arr1) < count($betArr)) {
                        $cacul = $this->doCacul($lotteryCode, $orderArr, $c_val['val'], $codeArr);
                    } else {
                        $contents = ['play' => $orderData['play_code'], 'nums' => $c_val['val']];
                        $dealArr = $this->dealDetail($contents);
                        foreach ($dealArr as $cv) {
                            $contents2 = ['play' => $cv['play_code'], 'nums' => $cv['bet_val']];
                            $count = $this->getCompetingCount($lotteryCode, $contents2);
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
                    $detail = $this->dealDetail($contents);
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
                                $detail = $this->dealDetail($contents);
                                foreach ($detail as $vv) {
                                    if (!in_array($vv['bet_val'], $val['same_bet'])) {
                                        $allCacul[$m]['nums'] = $vv['bet_val'];
                                        $contents = ['play' => $vv['play_code'], 'nums' => $vv['bet_val']];
                                        $count = $this->getCompetingCount($lotteryCode, $contents);
                                        $allCacul[$m]['count'] = $count;
                                        $allCacul[$m]['play_code'] = $vv['play_code'];
                                    }
                                }
                            } else {
                                $contents = ['play' => implode(',', $diffSub), 'nums' => $val['nums']];
                                $count = $this->getCompetingCount($lotteryCode, $contents);
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
                        $detail = $this->dealDetail($contents);
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
                            $detail = $this->dealDetail($contents);
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
                            $count = $this->getCompetingCount($lotteryCode, $contents);
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
                        preg_match($pattern, $b_val, $res4);
                        if (!in_array($lotteryCode, [3005, 3011])) {
                            $arr = explode(',', $res4[2]);
                            $betName = '';
                            foreach ($arr as $a) {
                                $betName .= $schePlay[$lotteryCode][$a] . '，';
                            }
                        } else {
                            $str = explode('*', $res4[2]);
                            preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                            $arr = explode(",", $r[2]);
                            $betName = '';
                            foreach ($arr as $a) {
                                $betName .= $schePlay[$r[1]][$a] . '，';
                            }
                        }
                        $bet['bet_val'][$k] = $scheData[$res4[1]]['schedule_code'] . '>' . rtrim($betName, '，');
                    }
                    $b_playCode = [];
                    if (!empty($orderData['build_code'])) {
                        $countSche = count($moreBet);
                        if ($countSche > 2) {
                            $mCN = $scheMCN[$countSche];
                            $newMCN = array_flip($mCN);
                        }
                    }
                    if (!empty($orderData['build_code']) && array_key_exists($m_val['play_code'], $newMCN)) {
                        $newPlayCode = $newMCN[$m_val['play_code']];
                        if (count($midArr) <= $countSche && $orderData['play_code'] == $m_val['play_code']) {
                            $newPlayCode = $orderData['build_code'];
                        }
                        $b_playCode[] = $newPlayCode;
                    } else {
                        $b_playCode = explode(',', $m_val['play_code']);
                    }
                    foreach ($b_playCode as $bp) {
                        $bet['play_name'][] = $manner[$bp];
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
                if ((int) $orderData['bet_double'] > 99) {
                    $lv['list_td'] = 'TD' . $ii;
                    $m = ceil($orderData['bet_double'] / 99);
                    $modNums = ((int) $orderData['bet_double'] % 99);
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
                    $lv['bet_money'] = $money * $orderData['bet_double'];
                    $lv['bet_double'] = $orderData['bet_double'];
                    $listAll[] = $lv;
                }
                $ii++;
            }
            $total = count($listAll);
            $page = 1;
            $pages = 1;
            $size = count($listAll);
        }
        if (!empty($orderData['build_code'])) {
            $playStr = $orderData['build_name'];
        } else {
            $playStr = $orderData['play_name'];
        }
        $data = ['page' => $page, 'size' => $size, 'pages' => $pages, 'total' => $total, 'data' => $listAll, 'count_sche' => count($midArr), 'play_str' => $playStr, 'order_money' => $orderData['bet_money'], 'bet_abb' => $betAbb];
        return ['code' => 600, 'msg' => '获取成功', 'data' => $data];
    }

    /**
     * 获取竞彩的注数
     * @param string $lotteryCode
     * @param array $contents
     * @return array
     */
    public function getCompetingCount($lotteryCode, $contents) {
        if ($lotteryCode != 3011 && $lotteryCode != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        
        $betNums = explode("|", $contents['nums']);
        $playCodes = explode(",", $contents['play']);
        $count = 0;
        $betCounts = [];
        foreach ($betNums as $v) {
            preg_match($pattern, $v, $result);
            if ($lotteryCode != 3011 && $lotteryCode != 3005) {
                $arr = explode(",", $result[2]);
                $betCounts[] = count($arr);
            } else {
                $resultBalls = trim($result[2], "*");
                $resultBalls = explode("*", $resultBalls);
                $thisCount = 0;
                foreach ($resultBalls as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    $arr = explode(",", $r[2]);
                    $thisCount+= count($arr);
                }
                $betCounts[] = $thisCount;
            }
        }
        foreach ($playCodes as $playCode) {
            $needBall = 0;
            switch ($playCode) {
                case "1":
                    $needBall = 1;
                    break;
                case "2":
                    $needBall = 2;
                    break;
                case "3":
                    $needBall = 3;
                    break;
                case "6":
                    $needBall = 4;
                    break;
                case "11":
                    $needBall = 5;
                    break;
                case "18":
                    $needBall = 6;
                    break;
                case "28":
                    $needBall = 7;
                    break;
                case "35":
                    $needBall = 8;
                    break;
                default :
                    break;
            }
            $ret = Commonfun::getCombination_array($betCounts, $needBall);
            foreach ($ret as $nums) {
                $theCount = 1;
                foreach ($nums as $v) {
                    $theCount = $theCount * $v;
                }
                $count +=$theCount;
            }
        }
//        $infos["content"] = $order;
        return $count;
    }

    /**
     * 获取每注详情
     * @param type $contents
     * @return type
     */
    public function dealDetail($contents) {
        $betNums = explode("|", $contents['nums']);
        $playCodes = explode(",", $contents['play']);
        $order = [];
        $n = 0;
        foreach ($playCodes as $playCode) {
            $needBall = 0;
            switch ($playCode) {
                case "1":
                    $needBall = 1;
                    break;
                case "2":
                    $needBall = 2;
                    break;
                case "3":
                    $needBall = 3;
                    break;
                case "6":
                    $needBall = 4;
                    break;
                case "11":
                    $needBall = 5;
                    break;
                case "18":
                    $needBall = 6;
                    break;
                case "28":
                    $needBall = 7;
                    break;
                case "35":
                    $needBall = 8;
                    break;
                default :
                    break;
            }
            $ret = Commonfun::getCombination_array($betNums, $needBall);
            foreach ($ret as $nums) {
                $combData = [];
                $crossData = [];
                $crossNum = 0;
                foreach ($nums as $v) {
                    $crossData[$crossNum][] = $v;
                    $crossNum++;
                }
                $str = '';
                for ($i = 0; $i < $needBall; $i++) {
                    $str.=',$crossData[' . $i . ']';
                }
                eval('$combData[] = \app\modules\common\helpers\Commonfun::proCross_string("|"' . $str . ');');

                foreach ($combData as $v1) {
                    foreach ($v1 as $v2) {
                        $vvArr = explode('|', $v2);
                        sort($vvArr);
                        $order[$n]["bet_val"] = implode('|', $vvArr);
                        $order[$n]["play_code"] = $playCode;
                        $n++;
                    }
                }
            }
        }

        return $order;
    }

    public function doCacul($lotteryCode, $orderArr, $combStr, $codeArr) {
        $n = 0;
        $combArr = explode('|', $combStr);
        $caculArr = [];
        $cArr = [];
        $sArr = [];
        foreach ($combArr as $comb) {
            if (!in_array($comb, $orderArr)) {
                $cArr[] = $comb;
                $n++;
            } else {
                $sArr[] = $comb;
            }
        }
        $playCode = $this->doComb($combArr, $n, $codeArr);
        if (!empty($playCode)) {
            $diffCode = array_diff($codeArr, $playCode);
            $combStr = implode('|', $combArr);
            $contents = ['play' => implode(',', $playCode), 'nums' => $combStr];
            $count = $this->getCompetingCount($lotteryCode, $contents);
            $caculArr[] = ['nums' => $combStr, 'count' => $count, 'play_code' => implode(',', $playCode)];
            if (!empty($diffCode)) {
                $contents = ['play' => implode(',', $diffCode), 'nums' => $combStr];
                $dealArr = $this->dealDetail($contents);
                foreach ($dealArr as $cv) {
                    $contents2 = ['play' => $cv['play_code'], 'nums' => $cv['bet_val']];
                    $count = $this->getCompetingCount($lotteryCode, $contents2);
                    $caculArr[] = ['nums' => $cv['bet_val'], 'count' => $count, 'play_code' => $cv['play_code']];
                }
            }
        } else {
            $numsArr = $this->doCross($cArr, $sArr, $n, $codeArr);
            foreach ($numsArr as $numArr) {
                foreach ($numArr as $num) {
                    $playCode = $this->doComb($num['nums'], $num['sameNums'], $codeArr);
                    $combStr = implode('|', $num['nums']);
                    $contents = ['play' => implode(',', $playCode), 'nums' => $combStr];
                    $count = $this->getCompetingCount($lotteryCode, $contents);
                    $caculArr[] = ['nums' => $combStr, 'count' => $count, 'play_code' => implode(',', $playCode)];
                }
            }
        }
        return $caculArr;
    }

    public function doComb($combArr, $n, $pcodeArr) {
        $count = count($combArr);
        $playCode = [];
        if ($n == 1) {
            switch ($count) {
                case 1:
                    $playCode = [1];
                    break;
                case 2:
                    $playCode = [2];
                    break;
                case 3:
                    $playCode = [3];
                    break;
                case 4:
                    $playCode = [6];
                    break;
                case 5:
                    $playCode = [11];
                    break;
                case 6:
                    $playCode = [18];
                    break;
                case 7:
                    $playCode = [28];
                    break;
                case 8:
                    $playCode = [35];
                    break;
                default :
                    break;
            }
        } elseif ($n == 2) {
            switch ($count) {
                case 2:
                    $playCode = [2];
                    break;
                case 3:
                    $playCode = [2, 3];
                    break;
                case 4:
                    $playCode = [3, 6];
                    break;
                case 5:
                    $playCode = [6, 11];
                    break;
                case 6:
                    $playCode = [11, 18];
                    break;
                case 7:
                    $playCode = [18, 28];
                    break;
                case 8:
                    $playCode = [28, 35];
                    break;
                default :
                    break;
            }
        } elseif ($n == 3) {
            switch ($count) {
                case 3:
                    $playCode = [2, 3];
                    break;
                case 4:
                    $playCode = [2, 3, 6];
                    break;
                case 5:
                    $playCode = [3, 6, 11];
                    break;
                case 6:
                    $playCode = [6, 11, 18];
                    break;
                case 7:
                    $playCode = [11, 18, 28];
                    break;
                case 8:
                    $playCode = [18, 28, 35];
                    break;
                default :
                    break;
            }
        } elseif ($n == 4) {
            switch ($count) {
                case 4:
                    $playCode = [2, 3, 6];
                    break;
                case 5:
                    $playCode = [2, 3, 6, 11];
                    break;
                case 6:
                    $playCode = [3, 6, 11, 18];
                    break;
                case 7:
                    $playCode = [6, 11, 18, 28];
                    break;
                case 8:
                    $playCode = [11, 18, 28, 35];
                    break;
                default :
                    break;
            }
        } elseif ($n == 5) {
            switch ($count) {
                case 5:
                    $playCode = [2, 3, 6, 11];
                    break;
                case 6:
                    $playCode = [2, 3, 6, 11, 18];
                    break;
                case 7:
                    $playCode = [3, 6, 11, 18, 28];
                    break;
                case 8:
                    $playCode = [6, 11, 18, 28, 35];
                    break;
                default :
                    break;
            }
        } elseif ($n == 6) {
            switch ($count) {
                case 6:
                    $playCode = [2, 3, 6, 11, 18];
                    break;
                case 7:
                    $playCode = [2, 3, 6, 11, 18, 28];
                    break;
                case 8:
                    $playCode = [3, 6, 11, 18, 28, 35];
                    break;
                default :
                    break;
            }
        } elseif ($n == 7) {
            switch ($count) {
                case 7:
                    $playCode = [2, 3, 6, 11, 18, 28];
                    break;
                case 8:
                    $playCode = [2, 3, 6, 11, 18, 28, 35];
                    break;
                default :
                    break;
            }
        } elseif ($n == 8) {
            switch ($count) {
                case 8:
                    $playCode = [2, 3, 6, 11, 18, 28, 35];
                    break;
                default :
                    break;
            }
        }
        $code = array_intersect($pcodeArr, $playCode);
        return $code;
    }

    public function doCross($cArr, $sArr, $n, $pcodeArr) {
        $numArr = [];
        foreach ($pcodeArr as $pcode) {
            switch ($pcode) {
                case 2:
                    $m = 2;
                    $numArr[] = $this->doNumsArr($cArr, $sArr, $n, $m);
                    break;
                case 3:
                    $m = 3;
                    $numArr[] = $this->doNumsArr($cArr, $sArr, $n, $m);
                    break;
                case 6:
                    $m = 4;
                    $numArr[] = $this->doNumsArr($cArr, $sArr, $n, $m);
                    break;
                case 11:
                    $m = 5;
                    $numArr[] = $this->doNumsArr($cArr, $sArr, $n, $m);
                    break;
                case 18:
                    $m = 6;
                    $numArr[] = $this->doNumsArr($cArr, $sArr, $n, $m);
                    break;
                case 28:
                    $m = 7;
                    $numArr[] = $this->doNumsArr($cArr, $sArr, $n, $m);
                    break;
                case 35:
                    $m = 8;
                    $numArr[] = $this->doNumsArr($cArr, $sArr, $n, $m);
                    break;
            }
        }
        return $numArr;
    }

    public function doNumsArr($cArr, $sArr, $n, $m) {
        $numArr = [];
        $num = [];
        for ($i = 1; $i <= $n; $i++) {
            $combArr = Commonfun::getCombination_array($cArr, $i);
            foreach ($combArr as $ca) {
                $nums = $m - $i;
                if ($nums > count($sArr) || $nums == 0) {
                    return $numArr;
                }
                $sameArr = Commonfun::getCombination_array($sArr, $nums);
                foreach ($sameArr as $same) {
                    $num = array_merge($ca, $same);
                    $numArr[] = ['nums' => $num, 'sameNums' => $i];
                }
            }
        }
        return $numArr;
    }

}
