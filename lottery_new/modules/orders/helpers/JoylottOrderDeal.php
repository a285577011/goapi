<?php

namespace app\modules\orders\helpers;

use app\modules\orders\helpers\AutoConsts;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\Constants;
use app\modules\orders\helpers\OrderDeal;
use app\modules\competing\helpers\CompetConst;
use app\modules\orders\helpers\DetailDeal;
class JoylottOrderDeal {

    public static function createAutoOrder($lotteryCode, $orderCode, $listAll, $periods = '', $add = 0) {
        $keys = ['out_order_code', 'order_code', 'free_type', 'lottery_code', 'play_code', 'periods', 'bet_val', 'bet_add', 'multiple', 'amount', 'count', 'create_time', 'source'];
        $autoCode = [];
        $elevenArr = Constants::ELEVEN_TREND;
        $compet = CompetConst::COMPET;
        $optArr = Constants::MADE_OPTIONAL_LOTTERY;
//        $wcupArr = CompetConst::MADE_WCUP_LOTTERY;
        $autoPlay = AutoConsts::JOYLOTT_ZL_PLAY;
        $db = \Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            foreach ($listAll as $item) {
                $freeType = 1;
                if (in_array($lotteryCode, $elevenArr)) {
                    $auotPlayCode = AutoConsts::JOYLOTT_ELEVEN_PLAYCODE[$lotteryCode];
                    $playCode = $auotPlayCode[$item['play_code']];
                    $betLottery = $lotteryCode;
                    $betStr = self::get11X5Bet($lotteryCode, $playCode, $item['bet_val']);
                    $outOrderCode = Commonfun::getCode('AUTO', "JL");
                    $mul = $item['bet_double'];
                    $money = $item['bet_money'];
                    $count = $item['count'];
                    $createTime = date('Y-m-d H:i:s');
                    $info[] = [$outOrderCode, $orderCode, $freeType, $betLottery, $playCode, $periods, $betStr, $add, $mul, $money, $count, $createTime, 'JW'];
                    $autoCode[] = $outOrderCode;
                } elseif (in_array($lotteryCode, $compet)) {
                    $betArr = self::getZlBet($lotteryCode, $item['bet_val'], $item['play_code']);
                    foreach ($betArr as $v) {
                        $betStr = $v['betVal'];
                        $playCode = $autoPlay[$v['betCode']];
                        $betLottery = $v['betLottery'];
                        $periods = date('Ymd');
                        $freeType = $v['thirdPlay'];
                        if (isset($v['betCount'])) {
                            $money = $item['bet_double'] * 2 * $v['betCount'];
                            $count = $v['betCount'];
                        } else {
                            $money = $item['bet_money'];
                            $count = $item['count'];
                        }
                        $outOrderCode = Commonfun::getCode('AUTO', "JL");
                        $mul = $item['bet_double'];
                        $createTime = date('Y-m-d H:i:s');
                        $info[] = [$outOrderCode, $orderCode, $freeType, $betLottery, $playCode, $periods, $betStr, $add, $mul, $money, $count, $createTime, 'JW'];
                        $autoCode[] = $outOrderCode;
                    }
                } elseif (in_array($lotteryCode, $optArr)) {
                    $betval = str_replace('_', '-', $item['bet_val']);
                    $arr = ['*', '^'];
                    $betStr = str_replace($arr, '|', $betval);
                    $betLottery = '4000';
                    $playCode = $autoPlay[$lotteryCode];
                    $outOrderCode = Commonfun::getCode('AUTO', "JL");
                    $mul = $item['bet_double'];
                    $money = $item['bet_money'];
                    $count = $item['count'];
                    $createTime = date('Y-m-d H:i:s');
                    $info[] = [$outOrderCode, $orderCode, $freeType, $betLottery, $playCode, $periods, $betStr, $add, $mul, $money, $count, $createTime, 'JW'];
                    $autoCode[] = $outOrderCode;
                } else {
                    $betData = self::getSzcBet($lotteryCode, $item['play_code'], $item['bet_val'], $periods);
                    $playCode = $betData['betCode'];
                    $betStr = $betData['betVal'];
                    $betLottery = $lotteryCode;
                    $periods = $betData['periods'];
                    $outOrderCode = Commonfun::getCode('AUTO', "JL");
                    $mul = $item['bet_double'];
                    $money = $item['bet_money'];
                    $count = $item['count'];
                    $createTime = date('Y-m-d H:i:s');
                    $info[] = [$outOrderCode, $orderCode, $freeType, $betLottery, $playCode, $periods, $betStr, $add, $mul, $money, $count, $createTime, 'JW'];
                    $autoCode[] = $outOrderCode;
                }
            }
            $data = $db->createCommand()->batchInsert("auto_out_order", $keys, $info)->execute();
            if ($data === false) {
                $tran->rollBack();
                return ['code' => 108, 'msg' => '数据写入失败'];
            }
            $tran->commit();
            return ['code' => 600, 'msg' => '数据写入成功', 'data' => $autoCode, 'auto_third' => 'JW'];
        } catch (Exception $ex) {
            $tran->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }

    /**
     * 11X5自动出票单
     * @param type $lotteryCode
     * @param type $playCode
     * @param type $betStr
     * @return string
     */
    public static function get11X5Bet($lotteryCode, $playCode, $betStr) {
        $codeStr = AutoConsts::JOYLOTT_PCODE[$lotteryCode];
        switch ($playCode) {
            case $codeStr . '01':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '02':
                $bet = str_replace('#', '|*', str_replace(',', '|', $betStr)) . '|';
                break;
            case $codeStr . '03':
                $bet = str_replace(',', '|', $betStr);
                break;
            case $codeStr . '04';
                $bet = str_replace('#', '|*', str_replace(',', '|', $betStr)) . '|';
                break;
            case $codeStr . '05':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '06':
                $bet = str_replace('#', '|*', str_replace(',', '|', $betStr)) . '|';
                break;
            case $codeStr . '07':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '08':
                $bet = str_replace('#', '|*', str_replace(',', '|', $betStr)) . '|';
                break;
            case $codeStr . '09':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '10':
                $bet = str_replace('#', '|*', str_replace(',', '|', $betStr)) . '|';
                break;
            case $codeStr . '11':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '12':
                $bet = str_replace('#', '|*', str_replace(',', '|', $betStr)) . '|';
                break;
            case $codeStr . '13':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '14':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '15':
                $bet = str_replace(',', '|', str_replace(';', '|-', $betStr)) . '|-';
                break;
            case $codeStr . '16':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '17':
                $bet = str_replace('#', '|*', str_replace(',', '|', $betStr)) . '|';
                break;
            case $codeStr . '18':
                $bet = str_replace(',', '|', str_replace(';', '|-', $betStr)) . '|-';
                break;
            case $codeStr . '19':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '20':
                $bet = str_replace('#', '|*', str_replace(',', '|', $betStr)) . '|';
                break;
            case $codeStr . '22':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '23':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
            case $codeStr . '24':
                $bet = str_replace(',', '|', $betStr) . '|';
                break;
        }
        return $bet;
    }

    public static function getZLBet($lotteryCode, $betNums, $playStr) {
        $midArr = OrderDeal::getMids($lotteryCode, $betNums);
        if ($lotteryCode != 3011 && $lotteryCode != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $bet = [];
        $res = [];
        $mCn = CompetConst::SCHE_COUNT_MCN;
        $thirdMcn = array_flip(Constants::THIRD_MCHUANN);
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $playArr = explode(',', $playStr);
        if (count($betNums) < 7) {
            $schePlay = $mCn[count($betNums)];
            $playCode = $schePlay[$playStr];
            $thirdPlay = $thirdMcn[$playCode];
        } else {
            if (count($playArr) > 1) {
                $schePlay = $mCn[count($betNums)];
                $playCode = $schePlay[$playStr];
                $thirdPlay = $thirdMcn[$playCode];
            } else {
                $thirdPlay = $thirdMcn[$playStr];
            }
        }
        $betE = [];
        $jwPlay = AutoConsts::JOYLOTT_ZL_PLAY;
        $jwPlayArr = [];
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
                $strBet = $res[2];
                if ($lotteryCode == 3001 || $lotteryCode == 3002) {
                    $strBet = str_replace('0', '2', str_replace('3', '1', $res[2]));
                }
                $play = $lotteryCode;
                if (!in_array($play, $jwPlayArr)) {
                    $jwPlayArr[] = $play;
                }
                $betStr = $date . '|' . $week . $code . '|' . $strBet;
                $betStrE = $date . '|' . $week . $code . '|' . $jwPlay[$play] . '@' . $strBet;
            } else {
                $resArr = explode('*', trim($res[2], '*'));
                foreach ($resArr as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    $strBet = $r[2];
                    if ($r[1] == 3001 || $r[1] == 3002) {
                        $strBet = str_replace('0', '2', str_replace('3', '1', $r[2]));
                    }
                }
                $date = $midArr[$res[1]]['schedule_date'];
                $code = substr($midArr[$res[1]]['schedule_code'], -3);
                $week = date('w', strtotime($midArr[$res[1]]['schedule_date']));
                if ($week == 0) {
                    $week = 7;
                }
                $play = $r[1];
                if (!in_array($play, $jwPlayArr)) {
                    $jwPlayArr[] = $play;
                }
                $betStr = $date . '|' . $week . $code . '|' . $strBet;
                $betStrE = $date . '|' . $week . $code . '|' . $jwPlay[$play] . '@' . $strBet;
            }
            $bet[] = $betStr;
            $betE[] = $betStrE;
        }
        if (count($jwPlayArr) == 1) {
            $betVal = implode('/', $bet);
            $betCode = $jwPlayArr[0];
        } else {
            $betVal = implode('/', $betE);
            $betCode = $lotteryCode;
        }
        if (in_array($lotteryCode, $football)) {
            $betLottery = '3000';
        } else {
            $betLottery = '3100';
        }
        $playArr = explode(',', $playStr);
        $detailDeal = new DetailDeal();
        $schePlay = $mCn[count($betNums)];
        if (count($playArr) != 1) {
            $schePlay = $mCn[count($betNums)];
            if (isset($schePlay[$playStr])) {
                $playCode = $schePlay[$playStr];
                $thirdPlay = $thirdMcn[$playCode];
                $betList[] = ['betCode' => $betCode, 'betVal' => $betVal, 'betLottery' => $betLottery, 'thirdPlay' => $thirdPlay];
            } else {
                foreach ($playArr as $v) {
                    $cacul = $detailDeal->getCompetingCount($lotteryCode, ['nums' => implode('|', $betNums), 'play' => $v]);
                    if (isset($schePlay[$v])) {
                        $thirdPlay = $thirdMcn[$schePlay[$v]];
                    } else {
                        $thirdPlay = $thirdMcn[$v];
                    }
                    $betList[] = ['betCode' => $betCode, 'betVal' => $betVal, 'betLottery' => $betLottery, 'thirdPlay' => $thirdPlay, 'betCount' => $cacul];
                }
            }
        } else {
            if (isset($schePlay)) {
                $thirdPlay = $thirdMcn[$schePlay[$playStr]];
            } else {
                $thirdPlay = $thirdMcn[$playStr];
            }
            $betList[] = ['betCode' => $betCode, 'betVal' => $betVal, 'betLottery' => $betLottery, 'thirdPlay' => $thirdPlay];
        }
        return $betList;
    }

    public static function getSzcBet($lotteryCode, $playCode, $betNums, $periods) {
        switch ($lotteryCode) {
            case '1001':
                if ($playCode == 0) {
                    $playCode = '3000';
                } elseif ($playCode == 1) {
                    $playCode = '3001';
                }
                $betSigin = explode('^', rtrim($betNums, '^'));
                $arr = ['|', '^'];
                if (count($betSigin) > 1) {
                    foreach ($betSigin as $sigin) {
                        $betArr = explode('|', rtrim($sigin, '^'));
                        foreach ($betArr as &$val) {
                            $valArr = explode(',', $val);
                            foreach ($valArr as &$v) {
                                $v = (int) $v;
                            }
                            $val = implode(',', $valArr);
                        }
                        $betNums = implode('|', $betArr);
                        $siginArr[] = str_replace(',', '|', str_replace($arr, '|-', $betNums)) . '|-';
                    }
                    $betStr = implode('_', $siginArr);
                } else {
                    $betArr = explode('|', rtrim($betNums, '^'));
                    foreach ($betArr as &$val) {
                        $valArr = explode(',', $val);
                        foreach ($valArr as &$v) {
                            $v = (int) $v;
                        }
                        $val = implode(',', $valArr);
                    }
                    $betNums = implode('|', $betArr);
                    $betStr = str_replace(',', '|', str_replace($arr, '|-', $betNums)) . '|-';
                }
                $periods = '20' . $periods;
                break;
            case '1002':
                if ($playCode == 0) {
                    $playCode = '2000';
                    if (count(explode('^', rtrim($betNums, '^'))) > 1) {
                        $betNums = implode('^_', $betArr) . '^';
                    }
                    $betStr = str_replace('^', '|', $betNums);
                } elseif ($playCode == 1) {
                    $playCode = '2010';
                    $arr = ['|', '^'];
                    $betStr = str_replace(',', '|', str_replace($arr, '|-', $betNums));
                } elseif ($playCode == 3) {
                    $playCode = '2011';
                    $arr = [',', '^'];
                    if (count(explode('^', rtrim($betNums, '^'))) > 1) {
                        $betNums = implode('^_', $betArr) . '^';
                    }
                    $betStr = str_replace($arr, '|', $betNums);
                } elseif ($playCode == 4) {
                    $playCode = '2031';
                    $betStr = str_replace(',', '|', str_replace('^', '|-', $betNums));
                } elseif ($playCode == 5) {
                    $playCode = '2030';
                    $betStr = str_replace(',', '|', str_replace('^', '|-', $betNums));
                }
                break;
            case '1003':
                $betSigin = explode('^', rtrim($betNums, '^'));
                if (count($betSigin) > 1) {
                    foreach ($betSigin as $sigin) {
                        $betArr = explode(',', rtrim($betNums, '^'));
                        foreach ($betArr as &$val) {
                            $val = (int) $val;
                        }
                        $siginArr[] = implode('|', $betArr) . '|';
                    }
                    $betStr = implode('_', $siginArr);
                } else {
                    $betArr = explode(',', rtrim($betNums, '^'));
                    foreach ($betArr as &$val) {
                        $val = (int) $val;
                    }
                    $betStr = implode('|', $siginArr);
                }

                if ($playCode == 0) {
                    $playCode = '1000';
                    $betStr = $betNums . '|';
                } elseif ($playCode == 1) {
                    $playCode = '1001';
                    $betStr = $betNums . '|-';
                }
                $periods = '20' . $periods;
                break;
            case '2001':
                $betSigin = explode('^', rtrim($betNums, '^'));
                $arr = ['|', '^'];
                if (count($betSigin) > 1) {
                    foreach ($betSigin as $sigin) {
                        $betArr = explode('|', rtrim($sigin, '^'));
                        foreach ($betArr as &$val) {
                            $valArr = explode(',', $val);
                            foreach ($valArr as &$v) {
                                $v = (int) $v;
                            }
                            $val = implode(',', $valArr);
                        }
                        $betNums = implode('|', $betArr);
                        $siginArr[] = str_replace(',', '|', str_replace($arr, '|-', $betNums)) . '|-';
                    }
                    $betStr = implode('_', $siginArr);
                } else {
                    $betArr = explode('|', rtrim($betNums, '^'));
                    foreach ($betArr as &$val) {
                        $valArr = explode(',', $val);
                        foreach ($valArr as &$v) {
                            $v = (int) $v;
                        }
                        $val = implode(',', $valArr);
                    }
                    $betNums = implode('|', $betArr);
                    $betStr = str_replace(',', '|', str_replace($arr, '|-', $betNums)) . '|-';
                }
                if ($playCode == 0) {
                    $playCode = '1';
                } elseif ($playCode == 1) {
                    if (count(explode(',', $betArr[0])) > 5 && count(explode(',', $betArr[1])) == 2) {
                        $playCode = '2';
                    } elseif (count(explode(',', $betArr[0])) == 5 && count(explode(',', $betArr[1])) > 2) {
                        $playCode = '3';
                    } elseif (count(explode(',', $betArr[0])) > 5 && count(explode(',', $betArr[1])) > 2) {
                        $playCode = '4';
                    }
                }
                break;
            case '2002':
                if ($playCode == 0) {
                    $playCode = '1';
                    if (count(explode('^', rtrim($betNums, '^'))) > 1) {
                        $betNums = implode('^_', $betArr) . '^';
                    }
                    $betStr = str_replace('^', '|', $betNums);
                } elseif ($playCode == 1) {
                    $playCode = '2';
                    $arr = ['|', '^'];
                    $betStr = str_replace(',', '|', str_replace($arr, '|-', $betNums));
                } elseif ($playCode == 3) {
                    $playCode = '4';
                    $arr = [',', '^'];
                    if (count(explode('^', rtrim($betNums, '^'))) > 1) {
                        $betNums = implode('^_', $betArr) . '^';
                    }
                    $betStr = str_replace($arr, '|', $betNums);
                } elseif ($playCode == 4) {
                    $playCode = '5';
                    $betStr = str_replace(',', '|', str_replace('^', '|-', $betNums));
                } elseif ($playCode == 5) {
                    $playCode = '6';
                    $betStr = str_replace(',', '|', str_replace('^', '|-', $betNums));
                }
                break;
            case '2003':
                if ($playCode == 0) {
                    $playCode = '1';
                    if (count(explode('^', rtrim($betNums, '^'))) > 1) {
                        $betNums = implode('^_', $betArr) . '^';
                    }
                } else {
                    $playCode = '2';
                }
                $betStr = str_replace('^', '|', str_replace(',', '', $betNums));
                break;
            case '2004':
                if ($playCode == 0) {
                    $playCode = '1';
                    if (count(explode('^', rtrim($betNums, '^'))) > 1) {
                        $betNums = implode('^_', $betArr) . '^';
                    }
                } else {
                    $playCode = '2';
                }
                $betStr = str_replace('^', '|', str_replace(',', '', $betNums));
                break;
        }
        return ['betCode' => $playCode, 'betVal' => $betStr, 'periods' => $periods];
    }

}
