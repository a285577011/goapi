<?php

namespace app\modules\orders\helpers;

use app\modules\store\helpers\Storefun;
use app\modules\common\helpers\Constants;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\models\Schedule;
use app\modules\competing\models\LanSchedule;
use app\modules\common\helpers\Commonfun;
use app\modules\tools\helpers\CallBackTool;
use yii\base\Exception;
use app\modules\orders\models\TicketDispenser;
use yii\db\Expression;
use app\modules\openapi\services\ApiNoticeService;
use app\modules\common\models\PayRecord;
use app\modules\user\models\UserGrowthRecord;
use app\modules\orders\models\AutoOutOrder;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\ApiOrder;
use app\modules\common\services\OrderService;
use app\modules\common\services\KafkaService;
use app\modules\common\helpers\OrderNews;
use app\modules\orders\helpers\SzcDeal;
use app\modules\orders\helpers\AutoConsts;
use app\modules\tools\helpers\UpdateOdds;
use app\modules\common\models\Store;
use app\modules\numbers\services\EszcService;
use app\modules\numbers\helpers\EszcCalculation;
use app\modules\numbers\helpers\SzcConstants;
use app\modules\competing\services\WorldcupService;
use app\models\orders\models\AutoOutThird;
use app\modules\common\services\RedisService;
use app\modules\common\models\BettingDetail;
use app\modules\orders\models\WeightLotteryOut;

class OrderDeal {

    /**
     * 拆票
     * @param type $lotteryCode 彩种
     * @param type $betStr 投注内容
     * @param type $playCode 普通玩法 N串1
     * @param type $buildCode 主串 M串N
     * @param type $mul 倍数
     * @param type $type 投注方式 1：普通投注 2：奖金优化
     * @return type
     */
    public static function deal($lotteryCode, $betStr, $playCode, $buildCode, $mul, $majorType = 0, $majorData = []) {
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
            $midsArr = self::getMids($lotteryCode, $betNums);
            $midArr = array_keys($midsArr);
            $res = [];
            $bet = [];
            $r = [];
            $list = [];
            $n = 0;
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
                        if ($playCode == 1) {
                            preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                            $bet[$n]['count'] = $dealOrder->getCompetingCount($lotteryCode, ['play' => $playCode, 'nums' => $res[1] . '*' . $str]);
                            $bet[$n]['play_code'] = $playCode;
                            $bet[$n]['bet_val'] = [$res[1] . "*" . $str];
                            $n++;
                        } else {
                            $betVal[$res[1]][] = $res[1] . "*" . $str;
                        }
                    }
//                    $n++;
                }
            }
            if ($playCode == 1) {
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
        return $listAll;
    }

    /**
     * 获取出票门店
     * @param type $lotteryCode 彩种
     * @param type $outTicket 出票数
     * @param type $endTime 截止时间
     * @param type $type 出票方式
     * @param type $storeNo 门店
     * @return type
     */
    public static function judgeTimeout($outTicket, $endTime, $type, $storeNo = '') {
        $time = time();
        $endMinute = floor((strtotime($endTime) - $time) % 86400 / 60);
        if ($type == 1) {
            $outMinute = ceil($outTicket / 4);
        } elseif ($type == 2) {
            $outMinute = ceil($outTicket / 8);
        }
        if ($outMinute > $endMinute) {
            return ['code' => 109, 'msg' => '距离截止出票时间够短,无法保证正常出票！！请重新投注或放弃投注'];
        }
        if (!empty($storeNo) && $type == 2) {
            $modNums = TicketDispenser::find()->select(['mod_nums'])->where(['store_no' => $storeNo, 'type' => 2, 'status' => 1])->asArray()->one();
            if (empty($modNums)) {
                $type = 1;
                $outMinute = ceil($outTicket / 4);
                if ($outMinute > $endMinute) {
                    return ['code' => 109, 'msg' => '距离截止出票时间够短,无法保证正常出票！！请重新投注或放弃投注'];
                }
            } else {
//                $week = date('w');
//                if($week == 0 || $week == 6) {
//                    $modNums = intval($modNums['mod_nums']) + 480;
//                }  else {
                $modNums = $modNums['mod_nums'];
//                }
                if ($outTicket > $modNums) {
                    return ['code' => 109, 'msg' => '门店没有足够的余票出此单'];
                }
            }
        }
        return ['code' => 600, 'msg' => '可以出票', 'data' => $type];
    }

    /**
     * 获取出票门店
     * @param type $lotteryCode 彩种编号
     * @param type $outTicket 出票数
     * @param type $type 出票类型
     * @param type $storeNo 门店编号
     * @return type
     */
    public static function getOutStore($lotteryCode, $outTicket, $type, $storeNo = '', $ipProvince = '') {
        $week = date('w');
        if ($week == 0 || $week == 6) {
            $modNums = 480;
        } else {
            $modNums = 0;
        }
//        $storeFun = new Storefun();
        $store = Storefun::getStore($lotteryCode, $outTicket, $type, $storeNo, $ipProvince);
        return $store;
    }

    /**
     * 写入自动出票表 
     * @param type $lotteryCode 彩种编号
     * @param type $orderCode 订单编号
     * @param type $listAll 票根数组
     * @param type $periods 期数
     * @param type $add 是否追加
     * @return type
     */
    public static function createAutoOrder($lotteryCode, $orderCode, $listAll, $periods = '', $add = 0, $endTime, $storeNo) {
        $keys = ['out_order_code', 'order_code', 'free_type', 'lottery_code', 'play_code', 'periods', 'bet_val', 'bet_add', 'multiple', 'amount', 'count', 'create_time'];
        $autoMcn = AutoConsts::AUTO_CHUAN;
        $autoLot = AutoConsts::AUTO_PLAY;
        $mCN = CompetConst::M_CHUAN_N;
        $compet = CompetConst::COMPET;
        $mC1 = CompetConst::AUTO_CHANG;
        $optArr = Constants::MADE_OPTIONAL_LOTTERY;
        $wcupArr = CompetConst::MADE_WCUP_LOTTERY;
        $db = \Yii::$app->db;
        $tran = $db->beginTransaction();
        $autoCode = [];
        $outNums = count($listAll);
        try {
            $overTime = self::judgeTimeout($outNums, $endTime, 2, $storeNo);
            if ($overTime['code'] != 600) {
                throw new Exception($overTime['msg']);
            }
            $update = ['mod_nums' => new Expression("mod_nums-{$outNums}"), 'modify_time' => date('Y-m-d H:i:s')];
            $where = ['store_no' => $storeNo, 'type' => 2];
            $ret = TicketDispenser::updateAll($update, $where);
            if ($ret === false) {
                throw new Exception('扣票失败');
            }
            foreach ($listAll as $item) {
                $code = [];
                if (in_array($lotteryCode, $compet)) {
                    $betArr = self::dealAutoTicket($lotteryCode, $item['bet_val']);
                    $playCode = explode(',', $item['play_code']);
                    $betval = $betArr['betVal'];
                    foreach ($playCode as $p) {
                        $code[] = $autoMcn[$p];
                    }
                    if (count($playCode) == 1) {
                        if (array_key_exists($playCode[0], $mCN)) {
                            $free = 1;
                        } elseif (count($item['bet_val']) > $mC1[$playCode[0]]) {
                            $free = 1;
                        } else {
                            $free = 0;
                        }
                    } else {
                        $free = 1;
                    }
                    $codeStr = implode('^', $code);
                    $lotCode = $autoLot[$betArr['betCode']];
                } elseif (in_array($lotteryCode, $optArr)) {
                    $betval = str_replace('_', '4', $item['bet_val']);
                    $free = 0;
                    $codeStr = $item['play_code'];
                    $lotCode = $autoLot[$lotteryCode];
                } elseif (in_array($lotteryCode, $wcupArr)) {
                    $betval = $item['bet_val'];
                    $free = 0;
                    $codeStr = $autoMcn[$item['play_code']];
                    $lotCode = $autoLot[$lotteryCode];
                } else {
                    $betNums = self::getIsDouble($lotteryCode, $item['bet_val'], $item['play_code']);
                    if ($lotteryCode == 2001 || $lotteryCode == 1001 || $lotteryCode == 1003) {
                        $betval = str_replace(',', '', $betNums);
                    } else {
                        if (strpos($item['bet_val'], '|') === false) {
                            $betval = str_replace(',', '', $betNums);
                        } else {
                            $betStr = str_replace('|', '*', $betNums);
                            $betval = str_replace(',', '', $betStr);
                        }
                    }
                    $free = 0;
                    $codeStr = $item['play_code'];
                    $lotCode = $autoLot[$lotteryCode];
                }
                $outOrderCode = Commonfun::getCode('AUTO', "ZMF");
                $mul = $item['bet_double'];
                $money = $item['bet_money'] * 100;
                $count = $item['count'];
                $createTime = date('Y-m-d H:i:s');
                $info[] = [$outOrderCode, $orderCode, $free, $lotCode, $codeStr, $periods, $betval, $add, $mul, $money, $count, $createTime];
                $autoCode[] = $outOrderCode;
            }
            $data = $db->createCommand()->batchInsert("auto_out_order", $keys, $info)->execute();
            if ($data === false) {
                $tran->rollBack();
                return ['code' => 108, 'msg' => '数据写入失败'];
            }
            $tran->commit();
            return ['code' => 600, 'msg' => '数据写入成功', 'data' => $autoCode, 'auto_third' => 'ZMF'];
        } catch (Exception $ex) {
            $tran->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }

    /**
     * 处理自动出票注码格式
     * @param type $lotteryCode 彩种编号
     * @param type $betNums 投注内容
     * @return type
     */
    public static function dealAutoTicket($lotteryCode, $betNums) {
        $midArr = self::getMids($lotteryCode, $betNums);
        if ($lotteryCode != 3011 && $lotteryCode != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $bet = [];
        $res = [];
        $autoPlay = AutoConsts::AUTO_PLAY;
        $playArr = [];
        $betE = [];
        foreach ($betNums as $k => $iv) {
            preg_match($pattern, $iv, $res);
            $betStr = '';
            $strBet = '';
            $betStrE = '';
            if (!in_array($lotteryCode, [3005, 3011])) {
                $date = $midArr[$res[1]]['schedule_date'];
                $code = substr($midArr[$res[1]]['schedule_code'], -3);
                $week = date('w', strtotime($midArr[$res[1]]['schedule_date']));
                if ($week == 0) {
                    $week = 7;
                }
                $arr = explode(",", $res[2]);
                foreach ($arr as $a) {
                    $strBet .= $a;
                }
                $playCode = $lotteryCode;
                if (!in_array($playCode, $playArr)) {
                    $playArr[] = $playCode;
                }
                $betStr = $date . '|' . $week . '|' . $code . '|' . $strBet;
                $betStrE = $date . '|' . $week . '|' . $code . '|' . $autoPlay[$playCode] . '|' . $strBet;
            } else {
                $resArr = explode('*', trim($res[2], '*'));
                foreach ($resArr as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    $arr = explode(",", $r[2]);
                    foreach ($arr as $a) {
                        $strBet .= $a;
                    }
                }
                $date = $midArr[$res[1]]['schedule_date'];
                $code = substr($midArr[$res[1]]['schedule_code'], -3);
                $week = date('w', strtotime($midArr[$res[1]]['schedule_date']));
                if ($week == 0) {
                    $week = 7;
                }
                $playCode = $r[1];
                if (!in_array($playCode, $playArr)) {
                    $playArr[] = $playCode;
                }
                $betStr = $date . '|' . $week . '|' . $code . '|' . $strBet;
                $betStrE = $date . '|' . $week . '|' . $code . '|' . $autoPlay[$playCode] . '|' . $strBet;
            }
            $bet[] = $betStr;
            $betE[] = $betStrE;
        }
        if (count($playArr) == 1) {
            $betVal = implode('^', $bet);
            $betCode = $playArr[0];
        } else {
            $betVal = implode('^', $betE);
            $betCode = $lotteryCode;
        }
        $ret = ['betCode' => $betCode, 'betVal' => $betVal];
        return $ret;
    }

    /**
     * 获取赛程
     * @param type $lotteryCode 彩种编号
     * @param type $betArr 投注内容
     * @return array
     */
    public static function getMids($lotteryCode, $betArr) {
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        if ($lotteryCode != 3011 && $lotteryCode != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $result = [];
        $midArr = [];
        foreach ($betArr as $bv) {
            preg_match($pattern, $bv, $result);
            if (!in_array($result[1], $midArr)) {
                $midArr[] = $result[1];
            }
        }
        if (in_array($lotteryCode, $football)) {
            $scheData = Schedule::find()->select(['schedule_mid', 'schedule_code', 'schedule_date', 'open_mid'])->where(['in', 'schedule_mid', $midArr])->indexBy('schedule_mid')->asArray()->all();
        } elseif (in_array($lotteryCode, $basketball)) {
            $scheData = LanSchedule::find()->select(['schedule_mid', 'schedule_code', 'schedule_date', 'open_mid'])->where(['in', 'schedule_mid', $midArr])->indexBy('schedule_mid')->asArray()->all();
        }
        return $scheData;
    }

    /**
     * 判断出票类型
     * @param type $orderData  下注内容
     * @return type
     */
    public static function judgeOutType($orderData) {
        $lotteryCode = $orderData['lottery_code'];
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $competArr = array_merge($football, $basketball);
        $betNums = $orderData['contents'];
        $buildPlay = '';
        $majorData = [];
        $majorType = 0;
        $mul = 1;
        $mCN = CompetConst::M_CHUAN_N;
        $outType = 1;
        $outTicket = [];
        if (in_array($lotteryCode, $competArr)) {
            if (array_key_exists($betNums['play'], $mCN)) {
                $buildPlay = $betNums['play'];
                $betNums['play'] = $mCN[$buildPlay];
            }
            if (array_key_exists('major_type', $orderData) && array_key_exists('major_data', $orderData)) {
                $majorType = $orderData['major_type'];
                $majorData = $orderData['major_data'];
            } else {
                $mul = $orderData['multiple'];
            }
            $betStr = $orderData['contents']['nums'];
            $playCode = $orderData['contents']['play'];
            $buildCode = $buildPlay;
            $outTicket = self::deal($lotteryCode, $betStr, $playCode, $buildCode, $mul, $majorType, $majorData);
            if (count($outTicket) > 30) {
                $outType = 2;
            }
            $outNums = count($outTicket);
        } else {
            $mul = $orderData['multiple'];
            $count = $orderData['count_bet'];
            $outNums = ceil($count / 5) * ceil($mul / 99);
            $endTime = $orderData['end_time'];
        }
        $endTime = date('Y-m-d H:i:s', $orderData['end_time'] / 1000);
        return ['outType' => $outType, 'outNums' => $outNums, 'endTime' => $endTime, 'outTicket' => $outTicket];
    }

    /**
     * 数字彩自动出票
     * @param type $orderId
     * @return type
     */
    public static function autoSzOrder($lotteryCode, $playStr, $betVal, $betDouble, $isAdd) {
        $listAll = [];
        $n = 1;
        $singleArr = [];
        $betArr = explode('^', rtrim($betVal, '^'));
        $playArr = explode(',', $playStr);
        $groupArr = [];
        foreach ($betArr as $key => $bet) {
            $noteData = SzcDeal::noteNums($lotteryCode, $bet, $playArr[$key]);
            if ($noteData['code'] != 600) {
                return $noteData;
            }
            $noteNums = $noteData['data'];
            if ($noteNums == 1) {
                if ($playArr[$key] == '100203' || $playArr[$key] == '200203') {
                    $groupArr[] = $bet . '^';
                } else {
                    $singleArr[] = $bet . '^';
                }
            } else {
                if ($lotteryCode == '1002' || $lotteryCode == '2002') {
                    if ($playArr[$key] == '100212' || $playArr[$key] == '200212') {
                        $playCode = 5;
                    } elseif ($playArr[$key] == '100213' || $playArr[$key] == '200213') {
                        $playCode = 4;
                    } else {
                        $playCode = 1;
                    }
                } else {
                    $playCode = 1;
                }
                $listAll[] = ['bet_val' => $bet . '^', 'play_code' => $playCode, 'count' => $noteNums];
            }
        }
        $signle = [];
        $m = 0;
        foreach ($singleArr as $val) {
            if ($n == 5) {
                $signle[$m][$n] = ['bet_val' => $val, 'play_code' => 0, 'count' => 1];
                $n = 1;
                $m++;
            } else {
                $signle[$m][$n] = ['bet_val' => $val, 'play_code' => 0, 'count' => 1];
                $n++;
            }
        }
        foreach ($signle as $it) {
            $betStr = '';
            $count = 0;
            foreach ($it as $v) {
                $betStr .= $v['bet_val'];
                $count += $v['count'];
            }
            $listAll[] = ['bet_val' => $betStr, 'play_code' => 0, 'count' => $count];
        }
        $group = [];
        $mm = 0;
        $nn = 1;
        foreach ($groupArr as $vv) {
            if ($n == 5) {
                $group[$mm][$nn] = ['bet_val' => $vv, 'play_code' => 3, 'count' => 1];
                $nn = 1;
                $mm++;
            } else {
                $group[$mm][$nn] = ['bet_val' => $vv, 'play_code' => 3, 'count' => 1];
                $nn++;
            }
        }
        foreach ($group as $vg) {
            $gbetStr = '';
            $count = 0;
            foreach ($vg as $g) {
                $gbetStr .= $g['bet_val'];
                $count += $g['count'];
            }
            $listAll[] = ['bet_val' => $gbetStr, 'play_code' => 3, 'count' => $count];
        }

        foreach ($listAll as $item) {
            $outNums = ceil($betDouble / 99);
            $price = Constants::PRICE;
            $allBetDouble = $betDouble;
            for ($num = 1; $num <= $outNums; $num++) {
                if ($allBetDouble > 99) {
                    $betDouble = 99;
                } else {
                    $betDouble = $allBetDouble;
                }
                if ($isAdd) {
                    $betMoney = 1.5 * $price * $betDouble * $item['count'];
                } else {
                    $betMoney = $price * $betDouble * $item['count'];
                }
                $listBet[] = ['bet_val' => $item['bet_val'], 'play_code' => $item['play_code'], 'bet_money' => $betMoney, 'bet_double' => $betDouble, 'count' => $item['count']];
            }
        }
        return $listBet;
    }

    /**
     * 确认出票成功
     * @param type $autoCode 出票
     * @return boolean
     * @throws Exception
     */
    public static function confirmOutTicket($orderCode) {
        $allOut = AutoOutOrder::find()->select(['out_order_code', 'status'])->where(['order_code' => $orderCode])->asArray()->all();

        $status = array_column($allOut, 'status');

//        $apiNotice = new ApiNoticeService();
//        $UserGlCoin = new UserGlCoinRecord();
        $UserGrowthRecord = new UserGrowthRecord();
        $orderData = LotteryOrder::findOne(['lottery_order_code' => $orderCode, 'status' => 2]);
        $basketballs = CompetConst::MADE_BASKETBALL_LOTTERY;
        $footballs = Constants::MADE_FOOTBALL_LOTTERY;
        $worldcupArr = CompetConst::MADE_WCUP_LOTTERY;
        if (empty($orderData)) {
            return 101;
        }
        $key = 'confrimOutTicket' . $orderCode;
        $trans = \Yii::$app->db->beginTransaction();
        try {
            if (in_array(5, $status)) {
                if (\Yii::$app->redis->set($key, '1', "nx", "ex", 5)) {
                    Commonfun::sysAlert('紧急通知', "自动出票失败", '订单：' . $orderCode, "待处理", "请即时处理！");
                    if ($orderData->source == 7) {
                        BettingDetail::updateAll(["status" => 6], ['lottery_order_code' => $orderCode, "status" => 2]);
                        $orderData->status = 6;
                        $orderData->saveData();
                        $tmp = OrderService::outOrderFalse($orderCode, 6);
                        if ($tmp === false) {
                            throw new Exception('拒绝出票失败！！');
                        }
//                    $thirdOrderCode = ApiOrder::find()->select(['third_order_code'])->where(['api_order_id' => $orderData->source_id])->asArray()->one();
//                    $apiNotice->PushNoticePlayOrder(10008, '出票失败！退款将退回至原支付账户，请您放心', $orderCode, $thirdOrderCode['third_order_code'], $orderData->user_id, 6);
                    }
//                OrderNews::userOutOrder($orderCode, 2);
                }
            } elseif (in_array(3, $status)) {
                Commonfun::sysAlert('紧急通知', "自动出票异常 ", '订单：' . $orderCode, "待处理", "请即时处理！");
            } elseif ((!in_array(1, $status)) && (!in_array(2, $status))) {
                if (\Yii::$app->redis->set($key, '1', "nx", "ex", 5)) {
                    $tmp = OrderService::outOrder($orderCode, $orderData->store_no);
                    if ($tmp['code'] != 1) {
                        throw new Exception($tmp['msg']);
                    }
                    OrderNews::userOutOrder($orderCode, 1); // 出票结果微信推送
                    if (in_array($orderData->lottery_id, $basketballs)) {
//                    $basketService = new BasketService();
                        if ($orderData->source == 7) {
                            
                        } else {
                            UpdateOdds::outUpdateOdds($orderData->lottery_order_code);
                        }

                        KafkaService::addQue('CreateDealOrder', ['orderId' => $orderData->lottery_order_id], true);
//                    $basketService->updateOdds($orderData->lottery_id, $orderData->lottery_order_id, $orderData->bet_val);
                    } elseif (in_array($orderData->lottery_id, $footballs)) {
//                    $footService = new FootballService();
                        if ($orderData->source == 7) {
                            
                        } else {
                            UpdateOdds::outUpdateOdds($orderData->lottery_order_code);
                        }
                        KafkaService::addQue('CreateDealOrder', ['orderId' => $orderData->lottery_order_id], true);
//                    $footService->updateOdds($orderData->lottery_id, $orderData->lottery_order_id, $orderData->bet_val);
                    } elseif (in_array($orderData->lottery_id, $worldcupArr)) {
//                    UpdateOdds::outUpdateOdds($orderData->lottery_order_code);
                        WorldcupService::updateOdds($orderData->lottery_id, $orderData->lottery_order_id, $orderData->bet_val);
                    }
//                KafkaService::addQue('CreateDealOrder', ['orderId' => $orderData->lottery_order_id], true);
                    if ($orderData->source != 7) {
                        $data = PayRecord::find()->select('pay_money,cust_no')
                                ->where(['order_code' => $orderCode, 'status' => 1, 'cust_no' => $orderData->cust_no])
                                ->asArray()
                                ->one();
                        //赠送咕啦币
//                    $glCoin = [
//                        'type' => 1,
//                        'coin_value' => $data['pay_money'], //实际支付金额
//                        'remark' => '购彩赠送',
//                        'coin_source' => 1,
//                        'order_code' => $orderData->lottery_order_code,
//                        'order_source' => $orderData->source,
//                    ];
//                    $UserGlCoin->updateGlCoin($orderData->cust_no, $glCoin);
                        //赠送成长值
                        $growth = [
                            'type' => 1,
                            'growth_value' => $orderData->bet_money,
                            'growth_remark' => '购彩赠送',
                            'growth_source' => 2,
                            'order_code' => $orderData->lottery_order_code,
                            'order_source' => $orderData->source,
                        ];
                        $UserGrowthRecord->updateGrowth($orderData->cust_no, $growth);
                    }
                }
            }
            $trans->commit();
            CallBackTool::addCallBack(1, ['lottery_order_code' => $orderCode]);
            return $orderCode;
        } catch (Exception $ex) {
            $trans->rollBack();
            KafkaService::addLog('autoOrder', $ex->getMessage());
            return $ex->getMessage();
        }
    }

    /**
     * 任选自动出票处理
     * @param type $betNums 投注内容
     * @param type $playCode 玩法
     * @param type $mul
     * @param type $count
     */
    public static function optionAutoOrder($lotteryCode, $betNums, $playCode, $mul, $count) {
        $outNums = ceil($mul / 99);
        $price = Constants::PRICE;
        $allBetDouble = $mul;
        $n = 0;
        $listAll = [];
        if ($lotteryCode == 4001) {
            if ($playCode == '400101') {
                $betPlay = 0;
            } else {
                $betPlay = 1;
            }
            for ($num = 1; $num <= $outNums; $num++) {
                if ($allBetDouble > 99) {
                    $betDouble = 99;
                } else {
                    $betDouble = $allBetDouble;
                }
                $allBetDouble = $allBetDouble - $betDouble;
                $listAll[$n]['bet_val'] = str_replace(',', '*', $betNums);
                $listAll[$n]['play_code'] = $betPlay;
                $listAll[$n]['bet_money'] = $betDouble * $price * $count;
                $listAll[$n]['bet_double'] = $betDouble;
                $listAll[$n]['count'] = $count;
                $n++;
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
                $betPlay = $d == 1 ? 0 : 1;
                $betDetail = implode(',', $betArr) . '^';
                for ($num = 1; $num <= $outNums; $num++) {
                    if ($allBetDouble > 99) {
                        $betDouble = 99;
                    } else {
                        $betDouble = $allBetDouble;
                    }
                    $allBetDouble = $allBetDouble - $betDouble;
                    $listAll[$n]['bet_val'] = str_replace(',', '*', $betDetail);
                    $listAll[$n]['play_code'] = $betPlay;
                    $listAll[$n]['bet_money'] = $betDouble * $price * $d;
                    $listAll[$n]['bet_double'] = $betDouble;
                    $listAll[$n]['count'] = $d;
                    $n++;
                }
            }
        }
        return $listAll;
    }

    /**
     * 竞彩冠亚军自动出票单处理
     * @param type $lotteryCode
     * @param type $mul
     * @param type $betNums
     * @param type $count
     * @return type
     */
    public static function worldcupAutoOrder($periods, $lotteryCode, $betNums, $mul, $count, $playCode) {
        $outNums = ceil($mul / 99);
        $price = Constants::PRICE;
        $allBetDouble = $mul;
        $n = 0;
        $listAll = [];
        $worldCupArr = CompetConst::MADE_WCUP_LOTTERY;
        if (in_array($lotteryCode, $worldCupArr)) {
            $codeStr = $periods . '01';
        } else {
            $codeStr = $periods . '02';
        }
        for ($num = 1; $num <= $outNums; $num++) {
            if ($allBetDouble > 99) {
                $betDouble = 99;
            } else {
                $betDouble = $allBetDouble;
            }
            $allBetDouble = $allBetDouble - $betDouble;
            $listAll[$n]['bet_val'] = $codeStr . '|' . str_replace(',', '', trim($betNums, '^'));
            $listAll[$n]['play_code'] = $playCode;
            $listAll[$n]['bet_money'] = $betDouble * $price * $count;
            $listAll[$n]['bet_double'] = $betDouble;
            $listAll[$n]['count'] = $count;
            $listAll[$n]['betVal'] = $betNums;
            $n++;
        }
        return $listAll;
    }

    /**
     * 获取复式投注格式
     * @param type $lotteryCode
     * @param type $betNums
     * @param type $playCode
     * @return type
     */
    public static function getIsDouble($lotteryCode, $betNums, $playCode) {
        $betStr = '';
        if ($lotteryCode == 2001 && $playCode == 1) {
            $betArr = explode('|', $betNums);
            if (count(explode(',', $betArr[0])) > 5) {
                $betStr .= '*' . $betArr[0];
            } else {
                $betStr .= $betArr[0];
            }
            if (count(explode(',', $betArr[1])) > 2) {
                $betStr .= '|*' . $betArr[1];
            } else {
                $betStr .= '|' . $betArr[1];
            }
        } elseif ($lotteryCode == 1001 && $playCode == 1) {
            $betArr = explode('|', $betNums);
            if (count(explode(',', $betArr[0])) > 6) {
                $betStr .= '*' . $betArr[0];
            } else {
                $betStr .= $betArr[0];
            }
            if (count(explode(',', $betArr[1])) > 1) {
                $betStr .= '|*' . $betArr[1];
            } else {
                $betStr .= '|' . $betArr[1];
            }
        } elseif ($lotteryCode == 1003) {
            $betArr = explode('|', $betNums);
            if (count(explode(',', $betArr[0])) > 7) {
                $betStr .= '*' . $betArr[0];
            } else {
                $betStr .= $betArr[0];
            }
        } elseif ($lotteryCode == 2002) {
            $betArr = explode(',', $betNums);
            if ($playCode == 4) {
                $betStr = '**' . $betNums;
            } elseif ($playCode == 5) {
                $betStr = '**' . $betNums;
            } else {
                $betStr = $betNums;
            }
        } elseif ($lotteryCode == 1002) {
            $betArr = explode(',', $betNums);
            if ($playCode == 4) {
                $betStr = '**' . $betNums;
            } elseif ($playCode == 5) {
                $betStr = '**' . $betNums;
            } else {
                $betStr = $betNums;
            }
        } else {
            $betStr = $betNums;
        }
        return $betStr;
    }

    /**
     * 校验出票门店
     * @param type $storeNo
     * @param type $lotteryCode
     * @return type
     */
    public static function judgeStoreData($storeNo, $lotteryCode) {
        $store = Store::find()->select(['store.user_id', 'store.store_code', 'store.sale_lottery', 'store.business_status', 't.out_lottery'])
                ->leftJoin('ticket_dispenser t', 't.store_no = store.store_code and t.status = 1 and t.type = 2')
                ->where(["store.store_code" => $storeNo, 'store.status' => 1])
                ->asArray()
                ->one();
        if (empty($store)) {
            return ['code' => 109, 'msg' => '投注门店不存在'];
        }
        if ($store['business_status'] != 1) {
            return ['code' => 109, 'msg' => '该门店已暂停营业！！'];
        }
        $saleLotteryArr = explode(',', $store['sale_lottery']);
        $autoLottery = explode(',', $store['out_lottery']);
        if (in_array(3000, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3006', '3007', '3008', '3009', '3010', '3011');
        }
        if (in_array(3100, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3001', '3002', '3003', '3004', '3005');
        }
        if (in_array(5000, $saleLotteryArr)) {
            array_push($saleLotteryArr, '5001', '5002', '5003', '5004', '5005', '5006');
        }
        if (in_array(3300, $saleLotteryArr)) {
            array_push($saleLotteryArr, '301201', '301301');
        }
        if (in_array(3000, $autoLottery)) {
            array_push($autoLottery, '3006', '3007', '3008', '3009', '3010', '3011');
        }
        if (in_array(3100, $autoLottery)) {
            array_push($autoLottery, '3001', '3002', '3003', '3004', '3005');
        }
        if (in_array(5000, $autoLottery)) {
            array_push($autoLottery, '5001', '5002', '5003', '5004', '5005', '5006');
        }
        if (in_array(3300, $autoLottery)) {
            array_push($autoLottery, '301201', '301301');
        }
        if (!in_array($lotteryCode, $saleLotteryArr)) {
            return ['code' => 488, 'msg' => '你所购买的彩种，该门店不可接单！'];
        }
        return ['code' => 600, 'msg' => 'succ', 'data' => $store, 'autoLottery' => $autoLottery];
    }

    public static function elevenAutoOrder($lotteryCode, $betNums, $playCode, $mul) {
        $betArr = explode('^', rtrim($betNums, '^'));
        $playArr = explode(',', $playCode);
        $notOut = AutoConsts::JOYLOTT_NOTOUT;
        $nums11X5 = SzcConstants::NUMS_11X5;
        $ballNums = $nums11X5[$lotteryCode];
        $listAll = [];
        foreach ($betArr as $key => $bet) {
            if (in_array($playArr[$key], $notOut)) {
                $noteName = EszcService::getNoteName($playArr[$key]);
                $noteData = EszcCalculation::$noteName($bet, $ballNums[$playArr[$key]]);
                foreach ($noteData as $k => $v) {
                    $listAll[] = ['bet_val' => $v, 'play_code' => $playArr[$key], 'count' => 1];
                }
            } else {
                $funName = EszcService::getFunName($playArr[$key]);
                $noteNums = EszcCalculation::$funName($bet, $ballNums[$playArr[$key]]);
                $listAll[] = ['bet_val' => $bet, 'play_code' => $playArr[$key], 'count' => $noteNums];
            }
        }
        $leS = SzcConstants::LE_SAN;
        $leP = SzcConstants::LE_PUTONG;
        $leD = SzcConstants::LE_DANTUO;
        $listBet = [];
        foreach ($listAll as $item) {
            $outNums = ceil($mul / 99);
            $price = Constants::PRICE;
            $allBetDouble = $mul;
            if (in_array($item['play_code'], $leS)) {
                $price = 6;
            } elseif (in_array($item['play_code'], $leP)) {
                $price = 10;
            } elseif (in_array($item['play_code'], $leD)) {
                $price = 14;
            }
            for ($num = 1; $num <= $outNums; $num++) {
                if ($allBetDouble > 99) {
                    $betDouble = 99;
                } else {
                    $betDouble = $allBetDouble;
                }
                $betMoney = $price * $betDouble * $item['count'];
                $listBet[] = ['bet_val' => $item['bet_val'], 'play_code' => $item['play_code'], 'bet_money' => $betMoney, 'bet_double' => $betDouble, 'count' => $item['count']];
            }
        }
        return $listBet;
    }

    /**
     * 出票方回调
     * @param type $autoCode
     * @param type $status
     * @param type $ticketId
     * @return boolean
     */
    public static function thirdCall($autoCode, $status, $ticketId) {
        $autoOutOrder = AutoOutOrder::findOne(['out_order_code' => $autoCode, 'status' => 2]);
        $autoOutOrder->ticket_code = $ticketId;
        $autoOutOrder->status = $status;
        $autoOutOrder->modify_time = date('Y-m-d H:i:s');
        $autoOutOrder->save();
        KafkaService::addQue('ConfirmOutTicket', ['orderCode' => $autoOutOrder->order_code], true);
        return true;
    }

    /**
     * 获取出票第三方
     * @param type $lotteryCode
     * @param type $outType
     * @return type
     */
    public static function getOutThird($lotteryCode, $outType) {
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $bd = CompetConst::MADE_BD_LOTTERY;
        $wcup = CompetConst::MADE_WCUP_LOTTERY;
        $optional = Constants::MADE_OPTIONAL_LOTTERY;
        if (in_array($lotteryCode, $basketball)) {
            $lotteryCode = 3100;
        }
        if (in_array($lotteryCode, $football)) {
            $lotteryCode = 3000;
        }
        if (in_array($lotteryCode, $bd)) {
            $lotteryCode = 5000;
        }
        if (in_array($lotteryCode, $wcup)) {
            $lotteryCode = 3300;
        }
        if (in_array($lotteryCode, $optional)) {
            $lotteryCode = 4000;
        }
        
        $where = ['and', ['lottery_code' => $lotteryCode, 'a.status' => 1, 'a.out_type' => $outType], ['like', 'a.out_lottery', $lotteryCode]];
        $autoThird = WeightLotteryOut::find()->select(['a.third_code', 'weight_lottery_out.weight', 'a.third_name', 'a.out_lottery'])
                ->innerJoin('auto_out_third a', 'a.third_code = weight_lottery_out.out_code')
                ->where($where)
                ->asArray()
                ->all();
        $weightArr = array_column($autoThird, 'weight');
        if (empty($autoThird) || array_sum($weightArr) < 1) {
            $where = ['and', ['status' => 1, 'out_type' => $outType], ['like', 'out_lottery', $lotteryCode]];
            $autoThird = AutoOutThird::find()->select(['third_code', 'third_name', 'weight', 'out_lottery'])->where($where)->asArray()->all();
            if(empty($autoThird)) {
                return ['code' => 109, 'msg' => '暂无出票方！请稍后再试！'];
            }
        }
        $weight = 0;
        $subThird = [];
        foreach ($autoThird as $one) {
            $oneWeight = (int) $one['weight'] ? $one['weight'] : 1;
            $weight += $oneWeight;
            for ($i = 0; $i < $oneWeight; $i ++) {
                $subThird[] = $one;
            }
        }
        $data = $subThird[rand(0, $weight - 1)];
        return ['code' => 600, 'msg' => '获取成功', 'data' => $data];
    }

}
