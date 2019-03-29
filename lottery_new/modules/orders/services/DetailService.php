<?php

namespace app\modules\orders\services;

use app\modules\common\models\LotteryOrder;
use app\modules\orders\models\MajorData;
use app\modules\orders\helpers\OrderDeal;
use app\modules\competing\models\DealOrder;
use app\modules\common\helpers\Commonfun;

class DetailService {

    /**
     * 创建处理明细订单
     * @param type $orderId
     * @return type
     */
    public static function creatrDealOrder($orderId) {
        $orderData = LotteryOrder::find()->select(['lottery_order_code', 'lottery_id', 'bet_val', 'play_code', 'build_code', 'bet_double', 'major_type', 'odds', 'source_id', 'source'])->where(['lottery_order_id' => $orderId])->asArray()->one();
        $majorData = [];
        if ($orderData['major_type'] != 0) {
            if ($orderData['source'] == 4) {
                $majorId = $orderData['source_id'];
                $source = 2;
            } elseif ($orderData['source'] == 7) {
                $majorId = $orderData['source_id'];
                $source = 7;
            } else {
                $majorId = $orderId;
                $source = 1;
            }
            $major = MajorData::find()->select(['major'])->where(['order_id' => $majorId, 'source' => $source])->asArray()->one();
            $majorData = json_decode($major['major'], true);
        }
        $listAll = OrderDeal::deal($orderData['lottery_id'], $orderData['bet_val'], $orderData['play_code'], $orderData['build_code'], $orderData['bet_double'], $orderData['major_type'], $majorData);
//       
        $key = ['order_id', 'lottery_code', 'play_code', 'bet_val', 'bet_money', 'bet_double', 'odds', 'create_time'];
        $db = \Yii::$app->db;
        $field = [];
        foreach ($listAll as $item) {
            $betArr = $item['bet_val'];
            $playCode = $item['play_code'];
            $betMoney = $item['bet_money'];
            $betDouble = $item['bet_double'];
            $betStr = implode('|', $betArr);
            $field[] = [$orderId, $orderData['lottery_id'], $playCode, $betStr, $betMoney, $betDouble, $orderData['odds'], date('Y-m-d H:i:s')];
        }
        $data = $db->createCommand()->batchInsert('deal_order', $key, $field)->execute();
        if ($data === false) {
            return ['code' => 109, 'msg' => '写入失败'];
        }
        return ['code' => 600, 'msg' => '写入成功'];
    }

    /**
     * 创建处理明细订单详情
     * @param type $orderId
     * @return type
     */
    public static function createDealDetail($orderId) {
        $dealData = DealOrder::find()->select(['deal_order_id', 'lottery_code', 'play_code', 'bet_val', 'odds'])->where(['order_id' => $orderId])->asArray()->all();
        $key = ['deal_order_id', 'lottery_code', 'bet_val', 'odds', 'fen_json', 'schedule_nums', 'create_time'];
        $db = \Yii::$app->db;
        foreach ($dealData as $val) {
            $oddsArr = json_decode($val['odds'], true);
            $betArr = explode('|', $val['bet_val']);
            $detailArr = self::getDealDetail($val['lottery_code'], $betArr, $val['play_code'], $oddsArr);
            foreach ($detailArr as $item) {
                $scheNums = count(explode('|', $item['bet_val']));
                $field[] = [$val['deal_order_id'], $val['lottery_code'], $item['bet_val'], $item['odds'], $item['fen_json'], $scheNums, date('Y-m-d H:i:s')];
            }
        }
        $data = $db->createCommand()->batchInsert('deal_detail', $key, $field)->execute();
        if ($data === false) {
            return ['code' => 109, 'msg' => '写入失败'];
        }
        return ['code' => 600, 'msg' => '写入成功'];
    }

    /**
     * 获取详情
     * @param type $lotteryCode
     * @param type $betArr
     * @param type $playStr
     * @param type $odds
     * @return type
     */
    public static function getDealDetail($lotteryCode, $betArr, $playStr, $odds) {
        if ($lotteryCode != 3005 && $lotteryCode != 3011) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $order = [];
        $playCodes = explode(',', $playStr);
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
                    return [
                        "code" => 2,
                        "msg" => "玩法未开放"
                    ];
            }

            $ret = Commonfun::getCombination_array($betArr, $needBall);
            foreach ($ret as $nums) {
                $combData = [];
                $crossData = [];
                $crossNum = 0;
                foreach ($nums as $v) {
                    preg_match($pattern, $v, $result);
                    if ($lotteryCode != 3005 && $lotteryCode != 3011) {
                        $arr = explode(",", $result[2]);
                        $crossData[$crossNum] = [];
                        foreach ($arr as $a) {
                            $crossData[$crossNum][] = $result[1] . "({$a})";
                        }
                        $crossNum++;
                    } else {
                        $resultBalls = trim($result[2], "*");
                        $resultBalls = explode("*", $resultBalls);
                        $crossData[$crossNum] = [];
                        foreach ($resultBalls as $str) {
                            preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                            $arr = explode(",", $r[2]);
                            foreach ($arr as $a) {
                                $crossData[$crossNum][] = $result[1] . "*" . $r[1] . "({$a})";
                            }
                        }
                        $crossNum++;
                    }
                }
                if ($needBall != 1) {
                    $str = '';
                    for ($i = 0; $i < $needBall; $i++) {
                        $str .= ',$crossData[' . $i . ']';
                    }
                    eval('$combData[] = \app\modules\common\helpers\Commonfun::proCross_string("|"' . $str . ');');
                } else {
                    foreach ($crossData as $crossVal) {
                        $combData[] = $crossVal;
                    }
                }
                foreach ($combData as $v1) {
                    foreach ($v1 as $v2) {
                        $order[$n]["bet_val"] = $v2;
                        $order[$n]["play_code"] = $playCode;
                        $oddsAmount = 1;
                        $fenData = [];
                        $item = explode('|', $v2);
                        foreach ($item as $vi) {
                            preg_match($pattern, $vi, $res);
                            if ($lotteryCode != 3005 && $lotteryCode != 3011) {
                                $oddsAmount *= $odds[$lotteryCode][$res[1]][$res[2]];
                                if ($lotteryCode == 3002) {
                                    $fenData[$res[1]] = $odds[$lotteryCode][$res[1]]['rf_nums'];
                                } elseif ($lotteryCode == 3004) {
                                    $fenData[$res[1]] = $odds[$lotteryCode][$res[1]]['fen_cutoff'];
                                }
                            } else {
                                $str = explode('*', $res[2]);
                                preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                                $oddsAmount *= $odds[$r[1]][$res[1]][$r[2]];
                                if ($r[1] == 3002) {
                                    $fenData[$res[1]] = $odds[$r[1]][$res[1]]['rf_nums'];
                                } elseif ($r[1] == 3004) {
                                    $fenData[$res[1]] = $odds[$r[1]][$res[1]]['fen_cutoff'];
                                }
                            }
                        }
                        $order[$n]['odds'] = $oddsAmount;
                        $order[$n]['fen_json'] = json_encode($fenData);
                        $n++;
                    }
                }
            }
        }
        return $order;
    }

}
