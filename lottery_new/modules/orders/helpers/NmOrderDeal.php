<?php

namespace app\modules\orders\helpers;

use app\modules\competing\helpers\CompetConst;
use app\modules\common\helpers\Constants;
use app\modules\orders\helpers\OrderDeal;
use app\modules\competing\models\BdSchedule;
use app\modules\common\helpers\Commonfun;
use app\modules\orders\helpers\DetailDeal;

class NmOrderDeal {

    public static function getZlBet($lotteryCode, $betNums, $playStr) {
//        $betArr = explode('|', rtrim($betNums, '^'));
        $midArr = OrderDeal::getMids($lotteryCode, $betNums);
        if ($lotteryCode != 3011 && $lotteryCode != 3005) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $result = [];
        $mCn = CompetConst::SCHE_COUNT_MCN;
        $thirdMcn = array_flip(Constants::THIRD_MCHUANN);
        $nmSche = AutoConsts::NM_SCHE_COUNT;
        $arr = ['1' => '1', '2' => '2', '3' => '3', '6' => '6', '11' => '11', '18' => '18', '28' => '28', '35' => '35'];
        $arrMcn = CompetConst::M_CHUAN_N + $arr;
        $betStr = '';
        $betStrE = '';
        $betArr = [];
        $betArrE = [];
        $pArr = [];
        $thirdPlay = [];
        foreach ($betNums as &$iv) {
            preg_match($pattern, $iv, $result);
            $result[1] = $midArr[$result[1]]['open_mid'];
            if ($lotteryCode != 3011 && $lotteryCode != 3005) {
                if (!in_array($lotteryCode, $pArr)) {
                    $pArr[] = $lotteryCode;
                }
                $betStr = $result[1] . '(' . $result[2] . ')';
                $betStrE = $result[1] . '(' . $result[2] . ')';
            } else {
                $resultBalls = explode("*", ltrim($result[2], "*"));
                foreach ($resultBalls as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    if (!in_array($r[1], $pArr)) {
                        $pArr[] = $r[1];
                    }
                    $betStr = $result[1] . '(' . $r[2] . ')';
                }
                $betStrE = $result[1] . $result[2];
            }
            $betArr[] = $betStr;
            $betArrE[] = $betStrE;
        }
        if (count($pArr) == 1) {
            $lotteryCode = $pArr[0];
            $betVal = $betArr;
        } else {
            $lotteryCode = $lotteryCode;
            $betVal = $betArrE;
        }
        $playArr = explode(',', $playStr);
        $schePlay = $mCn[count($betNums)];
        if (isset($schePlay[$playStr])) {
            $playCode = $schePlay[$playStr];
            $third = $thirdMcn[$playCode];
            if (in_array($third, $nmSche[count($betNums)])) {
                $freeType = 0;
                $thirdPlay[] = $third;
                $betValStr = implode('|', $betVal) . '^';
            } else {
                $freeType = 1;
                foreach ($playArr as $v) {
                    $subPlay = explode(',', $arrMcn[$v]);
                    foreach ($subPlay as $sub) {
                        $thirdPlay[] = $thirdMcn[$sub];
                    }
                }
                $betValStr = implode('|', $betVal) . '@' . implode('|', $thirdPlay) . '^';
            }
        } else {
            foreach ($playArr as $v) {
                $subPlay = explode(',', $arrMcn[$v]);
                foreach ($subPlay as $sub) {
                    $thirdPlay[] = $thirdMcn[$sub];
                }
            }
            $freeType = 1;
            $betValStr = implode('|', $betVal) . '@' . implode('|', $thirdPlay) . '^';
        }
        $betList = ['betVal' => $betValStr, 'playCode' => implode('|', $thirdPlay), 'betLottery' => $lotteryCode, 'freeType' => $freeType];
        return $betList;
    }

    public static function getOptionalBet($betNums) {
        $betVal = str_replace('_', '~', $betNums);
        return $betVal;
    }

    public static function getBdBet($lotteryCode, $betNums, $playStr) {
        $betArr = explode('|', rtrim($betNums, '^'));
        $midArr = self::getBdSort($betArr);
        $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        $result = '';
        foreach ($betArr as &$bv) {
            preg_match($pattern, $bv, $result);
            $result[1] = $midArr[$result[1]]['bd_sort'];
            $bv = $result[1] . '(' . $result[2] . ')';
        }
        $mCn = CompetConst::BD_SCHE_COUNT_MCN;
        $betStr = implode('|', $betArr) . '^';
        $schePlay = $mCn[count($betArr)];
        $betList = [];
        if (isset($schePlay[$playStr])) {
            $thirdPlay = $schePlay[$playStr];
            $betList[] = ['betVal' => $lotteryCode . '1' . $thirdPlay . '-' . $betStr, 'playCode' => $thirdPlay];
        } else {
            $playArr = explode(',', $playStr);
            foreach ($playArr as $v) {
                $betList[] = ['betVal' => $lotteryCode . '1' . $v . '-' . $betStr, 'playCode' => $v];
            }
        }
        return $betList;
    }

    public static function getBdSort($betArr) {
        $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        $midArr = [];
        $result = [];
        foreach ($betArr as $bv) {
            preg_match($pattern, $bv, $result);
            if (!in_array($result[1], $midArr)) {
                $midArr[] = $result[1];
            }
        }
        $bdSort = BdSchedule::find()->select(['open_mid', 'bd_sort'])->where(['in', 'open_mid', $midArr])->indexBy('open_mid')->asArray()->all();
        return $bdSort;
    }

    public static function createAutoOrder($lotteryCode, $orderCode, $listAll, $periods = '', $add = 0) {
        $keys = ['out_order_code', 'order_code', 'free_type', 'lottery_code', 'play_code', 'periods', 'bet_val', 'bet_add', 'multiple', 'amount', 'count', 'create_time', 'source'];
        $compet = CompetConst::COMPET;
        $optArr = Constants::MADE_OPTIONAL_LOTTERY;
        $wcupArr = CompetConst::MADE_WCUP_LOTTERY;
        $nmScheCount = AutoConsts::NM_SCHE_COUNT;
        $db = \Yii::$app->db;
        $tran = $db->beginTransaction();
        $autoCode = [];
        try {
            foreach ($listAll as $item) {
                if (in_array($lotteryCode, $compet)) {
                    $betArr = self::getZlBet($lotteryCode, $item['bet_val'], $item['play_code']);
                    $betStr = $betArr['betVal'];
                    if ($betArr['freeType'] == 1) {
                        $playCode = $betArr['betLottery'] . '1' . '9099';
                    } else {
                        $playCode = $betArr['betLottery'] . '1' . $betArr['playCode'];
                    }
                    $periods = date('Ymd');
                    $betLottery = $betArr['betLottery'];
                } elseif (in_array($lotteryCode, $optArr)) {
                    $betval = str_replace('_', '~', $item['bet_val']);
                    $betStr = str_replace('*', ',', $betval);
                    if ($item['play_code'] == 0) {
                        $playCode = $lotteryCode . '01';
                    } else {
                        $playCode = $lotteryCode . '02';
                    }
                    $betLottery = $lotteryCode;
                } elseif (in_array($lotteryCode, $wcupArr)) {
                    $betStr = $item['betVal'];
                    $playCode = $lotteryCode;
                    if ($playCode == '301201' || $playCode == '301202') {
                        $betLottery = '3012';
                    } else {
                        $betLottery = '3013';
                    }
                    $periods = $periods . '001';
                } else {
                    $betData = self::getPlayCode($lotteryCode, $item['play_code'], $item['bet_val']);
                    $playCode = $betData['play_code'];
                    $betStr = $betData['bet_nums'];
                    $betLottery = $lotteryCode;
                }
                $outOrderCode = Commonfun::getCode('AUTO', "NM");
                $mul = $item['bet_double'];
                $money = $item['bet_money'];
                $count = $item['count'];
                $createTime = date('Y-m-d H:i:s');
                $info[] = [$outOrderCode, $orderCode, 1, $betLottery, $playCode, $periods, $betStr, $add, $mul, $money, $count, $createTime, 'NM'];
                $autoCode[] = $outOrderCode;
            }
            $data = $db->createCommand()->batchInsert("auto_out_order", $keys, $info)->execute();
            if ($data === false) {
                $tran->rollBack();
                return ['code' => 108, 'msg' => '数据写入失败'];
            }
            $tran->commit();
            return ['code' => 600, 'msg' => '数据写入成功', 'data' => $autoCode, 'auto_third' => 'NM'];
        } catch (Exception $ex) {
            $tran->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }

    public static function getPlayCode($lotteryCode, $playCode, $betNums) {
        switch ($lotteryCode) {
            case '2001':
                if ($playCode == 0) {
                    $playCode = $lotteryCode . '01';
                } elseif ($playCode == 1) {
                    $playCode = $lotteryCode . '02';
                } else {
                    $playCode = $lotteryCode . '03';
                }
                break;
            case '2002':
                if ($playCode == 0) {
                    $playCode = $lotteryCode . '01';
                    $betSigin = explode('^', rtrim($betNums, '^'));
                    foreach ($betSigin as &$sigin) {
                        $betArr = explode('|', $sigin);
                        foreach ($betArr as &$v) {
                            $v = str_pad($v, 2, '0', STR_PAD_LEFT);
                        }
                        $sigin = implode('|', $betArr);
                    }
                    $betNums = implode('^', $betSigin) . '^';
                } elseif ($playCode == 1) {
                    $playCode = $lotteryCode . '11';
                    $betArr = explode('|', rtrim($betNums, '^'));
                    $bet = [];
                    foreach ($betArr as $v) {
                        $newV = explode(',', $v);
                        foreach ($newV as &$n) {
                            $n = str_pad($n, 2, '0', STR_PAD_LEFT);
                        }
                        $bet[] = implode(',', $newV);
                    }
                    $betNums = implode('|', $bet) . '^';
                } elseif ($playCode == 2) {
                    $playCode = $lotteryCode . '21';
                } elseif ($playCode == 3) {
                    $playCode = $lotteryCode . '03';
                    $betSigin = explode('^', rtrim($betNums, '^'));
                    foreach ($betSigin as &$sigin) {
                        $betArr = explode(',', $sigin);
                        foreach ($betArr as &$v) {
                            $v = str_pad($v, 2, '0', STR_PAD_LEFT);
                        }
                        $sigin = implode(',', $betArr);
                    }
                    $betNums = implode('^', $betSigin) . '^';
                } elseif ($playCode == 4) {
                    $playCode = $lotteryCode . '13';
                    $betArr = explode(',', rtrim($betNums, '^'));
                    foreach ($betArr as &$v) {
                        $v = str_pad($v, 2, '0', STR_PAD_LEFT);
                    }
                    $betNums = implode(',', $betArr) . '^';
                } elseif ($playCode == 5) {
                    $playCode = $lotteryCode . '12';
                    $betArr = explode(',', rtrim($betNums, '^'));
                    foreach ($betArr as &$v) {
                        $v = str_pad($v, 2, '0', STR_PAD_LEFT);
                    }
                    $betNums = implode(',', $betArr) . '^';
                }
                break;
            case '2003':
                if ($playCode == 0) {
                    $playCode = $lotteryCode . '01';
                    $betSigin = explode('^', rtrim($betNums, '^'));
                    foreach ($betSigin as &$sigin) {
                        $betArr = explode('|', $sigin);
                        foreach ($betArr as &$v) {
                            $v = str_pad($v, 2, '0', STR_PAD_LEFT);
                        }
                        $sigin = implode('|', $betArr);
                    }
                    $betNums = implode('^', $betSigin) . '^';
                } else {
                    $playCode = $lotteryCode . '02';
                    $betArr = explode('|', rtrim($betNums, '^'));
                    $bet = [];
                    foreach ($betArr as $v) {
                        $newV = explode(',', $v);
                        foreach ($newV as &$n) {
                            $n = str_pad($n, 2, '0', STR_PAD_LEFT);
                        }
                        $bet[] = implode(',', $newV);
                    }
                    $betNums = implode('|', $bet) . '^';
                }
                break;
            case '2004':
                if ($playCode == 0) {
                    $playCode = $lotteryCode . '01';
                    $betSigin = explode('^', rtrim($betNums, '^'));
                    foreach ($betSigin as &$sigin) {
                        $betArr = explode('|', $sigin);
                        foreach ($betArr as &$v) {
                            $v = str_pad($v, 2, '0', STR_PAD_LEFT);
                        }
                        $sigin = implode('|', $betArr);
                    }
                    $betNums = implode('^', $betSigin) . '^';
                } else {
                    $playCode = $lotteryCode . '02';
                    $betArr = explode('|', rtrim($betNums, '^'));
                    $bet = [];
                    foreach ($betArr as $v) {
                        $newV = explode(',', $v);
                        foreach ($newV as &$n) {
                            $n = str_pad($n, 2, '0', STR_PAD_LEFT);
                        }
                        $bet[] = implode(',', $newV);
                    }
                    $betNums = implode('|', $bet) . '^';
                }
                break;
        }
        return ['play_code' => $playCode, 'bet_nums' => $betNums];
    }

}
