<?php

namespace app\modules\competing\services;

use Yii;
use app\modules\common\services\OrderService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\Constants;
use yii\db\Query;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\Schedule;
use app\modules\common\models\Lottery;
use app\modules\common\services\PayService;
use app\modules\common\models\BettingDetail;
use app\modules\competing\helpers\CompetConst;
use app\modules\experts\models\ArticlesPeriods;

class FootballService {

    /**
     * 竞彩下单
     * @param string $lotteryCode
     * @param string $custNo
     * @return array
     */
    public function playOrder($lotteryCode, $custNo, $userId, $storeId, $storeCode, $source, $sourceId = '', $outType) {
        $layName = Constants::MANNER;
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $lotteryName = Constants::LOTTERY;
        $request = Yii::$app->request;
        $post = $request->post();
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        $remark = isset($post['remark']) ? $post['remark'] : '';
        //$periods = $post['periods']; // 期数
        $betNums = $orderData['contents']; // 投注内容
        $total = $orderData['total']; //总价
        $countBet = $orderData['count_bet']; // 注数
//        $maxTime = $orderData['maxcount_bet_time'];
//        if (isset($orderData['end_time'])) {
//            $endTime = date('Y-m-d H:i:s', $orderData['end_time'] / 1000);
//        } else {
//            $endTime = '';
//        }
        $count = 0;
        $playName = [];
        $mCN = CompetConst::M_CHUAN_N;
        $buildPlay = '';
        $buildName = '';
        if (array_key_exists($betNums['play'], $mCN)) {
            $buildPlay = $betNums['play'];
            $buildName = $layName[$buildPlay];
            $betNums['play'] = $mCN[$buildPlay];
        }
        $arr = explode(",", $betNums["play"]);
        if (is_array($arr)) {
            foreach ($arr as $val) {
                $playName[] = $layName[$val];
            }
        } else {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $playCode = $betNums["play"];
        $playNum = $betNums["nums"];

        $ret = $this->getCompetingCount($lotteryCode, $betNums);
        if ($ret["code"] == 0) {
            $count = $ret["result"];
            $odds = json_encode($ret["odds"], JSON_FORCE_OBJECT);
        } else {
            return $ret;
        }
        $multiple = 1;
        $price = Constants::PRICE;
        $subCount = 0;
        $majorData = '';
        if (array_key_exists('major_type', $orderData) && array_key_exists('major_data', $orderData)) {
            $betMoney = 0;
            foreach ($orderData['major_data'] as $vm) {
                if (!is_int($vm['mul']) || $vm['mul'] <= 0) {
                    return \Yii::jsonError(2, '投注倍数格式不对');
                }
                $betMoney += $price * $vm['mul'];
                $subCount += 1;
            }
            $majorType = $orderData['major_type'];
            $majorData = json_encode($orderData['major_data']);
        } else {
            $multiple = $orderData['multiple']; // 倍数
            $subCount = $count;
            $betMoney = $price * $count;
            if ($multiple >= 1) {
                $betMoney *= $multiple;
            } else {
                return ["code" => 2, "msg" => "投注加倍参数错误！"];
            }
            $majorType = 0;
        }
        if ($countBet != $count || $countBet != $subCount || $count != $subCount) {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $totalMoney = $betMoney;
        if ($total != $totalMoney) {
            return [ "code" => 2, "msg" => "投注总金额错误！"];
        }
//        $period = strtotime('+3 hour', (int) $maxTime);
        $endTime = $ret['limit_time'];
        $periods = $ret['max_time'];
        $insert = [
            "lottery_type" => $lotteryType[$lotteryCode],
            "play_code" => $playCode,
            "play_name" => implode(',', $playName),
            "lottery_id" => $lotteryCode,
            "lottery_name" => $lotteryName[$lotteryCode],
            "periods" => $periods,
            "cust_no" => $custNo,
            "store_id" => $storeId,
            "source_id" => $sourceId,
            "agent_id" => "0",
            "bet_val" => $playNum . "^",
            "bet_double" => $multiple,
            "is_bet_add" => 0,
            "bet_money" => $betMoney,
            "source" => $source,
            "count" => $count,
            "periods_total" => 1,
            "is_random" => 0,
            "win_limit" => 0,
            "is_limit" => 0,
            "odds" => $odds,
            "end_time" => $endTime,
            'user_id' => $userId,
            'store_no' => $storeCode,
            'build_code' => $buildPlay,
            'build_name' => $buildName,
            'major_type' => $majorType,
            'auto_type' => $outType,
            'remark' => $remark
        ];
        return OrderService::selfDoLotterOrder($insert, false, $majorData);
        /* $ret = OrderService::insertOrder([
          "lottery_type" => $lotteryType[$lotteryCode],
          "play_code" => $playCode,
          "play_name" => implode(',', $playName),
          "lottery_id" => $lotteryCode,
          "lottery_name" => $lotteryName[$lotteryCode],
          "periods" => $periods,
          "cust_no" => $custNo,
          "store_id" => $storeId,
          "source_id" => $sourceId,
          "agent_id" => "0",
          "bet_val" => $playNum . "^",
          "bet_double" => $multiple,
          "is_bet_add" => 0,
          "bet_money" => $betMoney,
          "source" => $source,
          "count" => $count,
          "periods_total" => 1,
          "is_random" => 0,
          "win_limit" => 0,
          "is_limit" => 0,
          "odds" => $odds,
          "end_time" => $endTime,
          'user_id' => $userId,
          'store_no' => $storeCode,
          'build_code' => $buildPlay,
          'build_name' => $buildName,
          'major_type' => $majorType
          ], false);

          if ($ret["error"] === true) {
          if ($source != 6) {
          $paySer = new PayService();
          $paySer->productPayRecord($custNo, $ret["orderCode"], 1, 1, $betMoney, 1);
          }
          if($majorType != 0) {
          $majorOrder = new MajorService();
          $majorOrder->createMajor($ret['orderId'], $majorData, $majorType);
          }
          return [
          "code" => 600,
          "msg" => "下注成功！",
          "result" => ["lottery_order_code" => $ret["orderCode"]]
          ];
          } elseif ($ret == false) {
          return [
          "code" => 2,
          "msg" => "下注失败！"
          ];
          } else {
          return [
          "code" => 2,
          "msg" => "下注失败！",
          "result" => $ret
          ];
          } */
    }

    /**
     * 投注验证
     * @param string $lotteryCode
     * @return array
     */
    public function playVerification($lotteryCode) {
        $layName = Constants::MANNER;
        $lotteryName = Constants::LOTTERY;
        $request = Yii::$app->request;
        $post = $request->post();
        $betNums = $post['contents']; // 投注内容
        $total = $post['total']; //总价
        $multiple = $post['multiple']; // 倍数
        $countBet = $post['count_bet']; // 注数
        $count = 0;
        $mCN = CompetConst::M_CHUAN_N;
        $flag = 0;
        if (array_key_exists($betNums['play'], $mCN)) {
            $mCNPlay = $betNums['play'];
            $betNums['play'] = $mCN[$betNums['play']];
            $arr = explode(',', $mCNPlay);
            $flag = 1;
        } else {
            $arr = explode(",", $betNums["play"]);
        }
        $playName = [];
        if (is_array($arr)) {
            if ($flag == 0) {
                foreach ($arr as $val) {
                    $playName[] = $layName[$val];
                }
                $playCode = $betNums["play"];
            } else {
                $playName[] = $layName[$mCNPlay];
                $playCode = $mCNPlay;
            }
        } else {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $playNum = $betNums["nums"];
        $limit_time = "";
        $ret = $this->getCompetingCount($lotteryCode, $betNums);
        if ($ret["code"] == 0) {
            $count = $ret["result"];
            $limit_time = $ret["limit_time"];
            $end_time = $ret['max_time'];
        } else {
            return $ret;
        }
        $price = Constants::PRICE;
        $subCount = 0;
        if (array_key_exists('major_type', $post) && array_key_exists('major_data', $post)) {
            $betMoney = 0;
            foreach ($post['major_data'] as $vm) {
                $betMoney += $price * $vm['mul'];
                $subCount += 1;
            }
        } else {
            $subCount = $count;
            $betMoney = $price * $count;
            if ($multiple >= 1) {
                $betMoney *= $multiple;
            } else {
                return ["code" => 2, "msg" => "投注加倍参数错误！"];
            }
        }
        if ($countBet != $count || $countBet != $subCount || $count != $subCount) {
            return ["code" => 2, "msg" => "投注内容注数不对应！"];
        }
        $totalMoney = $betMoney;
        if ($total != $totalMoney) {
            return [ "code" => 2, "msg" => "投注总金额错误！"];
        }
        return [
            "code" => 0,
            "msg" => "投注信息正确！",
            "data" => [
                "lottery_name" => $lotteryName[$lotteryCode],
                "play_name" => (implode(',', $playName)),
                "play_code" => $playCode,
                "bet_val" => ($playNum . "^"),
                "limit_time" => $limit_time,
                "max_time" => $end_time
            ]
        ];
    }

    /**
     * 获取竞彩的注数
     * @param string $lotteryCode
     * @param array $contents
     * @return array
     */
    public function getCompetingCount($lotteryCode, $contents, $isLimit = true) {
        $odds = [];
        $mids = [];
        $lottery = Lottery::findOne(["lottery_code" => $lotteryCode]);
        if ($lottery->status == "0") {
            return [
                "code" => 109,
                "msg" => "投注失败，此彩种已经停止投注，请选择其他彩种进行投注",
                "result" => ""
            ];
        }
        $isMix = ($lotteryCode == "3011");
        if ($isMix == false) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            //$pattern = '/^([0-9]+\*[0-9]+)\((([0-9]|,)+)\)$/';
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $scheduleMids = [];
        $betNums = explode("|", $contents["nums"]);
        $playCodes = explode(",", $contents['play']);
        $count = 0;
        if (!is_array($playCodes)) {
            return [
                "code" => 2,
                "msg" => "玩法格式出错"
            ];
        }
        if (!is_array($betNums)) {
            return [
                "code" => 2,
                "msg" => "投注格式出错"
            ];
        }

        $betCounts = [];
        $isMost6 = false; //是否最高只能4串1
        $isMost18 = false;
        if (in_array($lotteryCode, ["3007", "3009"])) {
            $isMost6 = true;
        }
        if ($lotteryCode == '3008') {
            $isMost18 = true;
        }
        $timeArr = [];
        foreach ($betNums as $v) {
            preg_match($pattern, $v, $result);
            if (is_array($result) && count($result) > 0 && isset($result[2])) {
                if ($isMix == false) {
                    if ($isLimit == true) {
                        if (!isset($scheduleMids[$lotteryCode])) {
                            $scheduleMids[$lotteryCode] = $this->getSchedule($lotteryCode, $playCodes);
                        }
                        if (!isset($scheduleMids[$lotteryCode][$result[1]])) {
                            return [
                                "code" => 415,
                                "msg" => "已选择的场次含有已停售场次，请重新选择"
                            ];
                        } else {
                            if (!array_key_exists($result[1], $timeArr)) {
                                $timeArr[$result[1]]['max_time'] = $scheduleMids[$lotteryCode][$result[1]]['start_time'];
                                $timeArr[$result[1]]['end_time'] = $scheduleMids[$lotteryCode][$result[1]]['endsale_time'];
                            }
                        }
                    }
                    $mids[] = $result[1];
                    $arr = explode(",", $result[2]);
                    $betCounts[] = count($arr);
                    if (!isset($odds[$lotteryCode])) {
                        $odds[$lotteryCode] = [];
                    }
                    $odds[$lotteryCode][$result[1]] = $this->getOdds($lotteryCode, $result[1], $arr);
                } else {
                    $resultBalls = trim($result[2], "*");
                    $resultBalls = explode("*", $resultBalls);
                    $thisCount = 0;
                    foreach ($resultBalls as $str) {
                        preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                        if ($isLimit == true) {
                            if (!isset($scheduleMids[$r[1]])) {
                                $scheduleMids[$r[1]] = $this->getSchedule($r[1], $playCodes);
                            }
                            if (!isset($scheduleMids[$r[1]][$result[1]])) {
                                return [
                                    "code" => 415,
                                    "msg" => "已选择的场次含有已停售场次，请重新选择"
                                ];
                            } else {
                                if (!array_key_exists($result[1], $timeArr)) {
                                    $timeArr[$result[1]]['max_time'] = $scheduleMids[$r[1]][$result[1]]['start_time'];
                                    $timeArr[$result[1]]['end_time'] = $scheduleMids[$r[1]][$result[1]]['endsale_time'];
                                }
                            }
                        }
                        if ($isMost6 == false && in_array($r[1], ["3007", "3009"])) {
                            $isMost6 = true;
                        }
                        if ($isMost18 == false && $r[1] == 3008) {
                            $isMost18 = true;
                        }
                        $mids[] = $result[1];
                        $arr = explode(",", $r[2]);
                        $thisCount+= count($arr);
                        if (!isset($odds[$r[1]])) {
                            $odds[$r[1]] = [];
                        }
                        $odds[$r[1]][$result[1]] = $this->getOdds($r[1], $result[1], $arr);
                    }
                    $betCounts[] = $thisCount;
                }
            } else {
                return [
                    "code" => 2,
                    "msg" => "投注格式出错"
                ];
            }
        }
        foreach ($playCodes as $playCode) {
            $needBall = 0;
            if ($isMost6 == true && $playCode > "6") {
                return [
                    "code" => 2,
                    "msg" => "投注格式出错,非胜平负，最高只能四串一"
                ];
            }
            if ($isMost18 == true && $playCode > '18') {
                return ['code' => 2, 'msg' => '投注格式出错,非胜平负，总进球最高只能四串一'];
            }
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

            if (is_array($betNums) && count($betNums) >= $needBall) {
                $ret = Commonfun::getCombination_array($betCounts, $needBall);
                if (!is_array($ret)) {
                    return [
                        "code" => 2,
                        "msg" => "投注格式出错"
                    ];
                }
                foreach ($ret as $nums) {
                    $theCount = 1;
                    foreach ($nums as $v) {
                        $theCount = $theCount * $v;
                    }
                    $count +=$theCount;
                }
            } else {
                return [
                    "code" => 2,
                    "msg" => "投注格式出错"
                ];
            }
        }
//        $scheduleEndTime = (new Query())->select("min(endsale_time) limit_time")->from("schedule")->where(["schedule_mid" => $mids])->one();
        $endTime = min(array_column($timeArr, 'end_time'));
        $maxTime = max(array_column($timeArr, 'max_time'));
        $awardTime = Commonfun::getAwardTime($maxTime);
        return [
            "code" => 0,
            "msg" => "获取成功",
            "result" => $count,
            "odds" => $odds,
            "limit_time" => $endTime,
            'max_time' => $awardTime
        ];
    }

    /**
     * 生成子单
     * @param model $model
     * @return array
     */
    public function productSuborder($model) {
        $infos = [];
        $infos["agent_id"] = $model->agent_id;
        $infos["bet_double"] = $model->bet_double;
        $infos["bet_money"] = $model->bet_money;
        $infos["is_bet_add"] = $model->is_bet_add;
        $infos["lottery_id"] = $model->lottery_id;
        $infos["lottery_name"] = $model->lottery_name;
        $infos["lottery_order_code"] = $model->lottery_order_code;
        $infos["lottery_order_id"] = $model->lottery_order_id;
        $infos["opt_id"] = $model->opt_id;
        $infos["periods"] = $model->periods;
        $infos["status"] = $model->status;
        $infos["cust_no"] = $model->cust_no;
        $infos["count"] = $model->count;
//        $infos["odds"] = $model->odds;
        $odds = json_decode($model->odds, true);

        $contents = trim($model->bet_val, "^");
        $lotteryCode = $model->lottery_id;

        $isMix = ($lotteryCode == "3011");
        if ($isMix == false) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            //$pattern = '/^([0-9]+\*[0-9]+)\((([0-9]|,)+)\)$/';
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $betNums = explode("|", $contents);
        $playCodes = explode(",", $model->play_code);
        $playNames = explode(",", $model->play_name);
        $order = [];
        $n = 0;
        if (!is_array($playCodes)) {
            return [
                "code" => 2,
                "msg" => "玩法格式出错"
            ];
        }
        foreach ($playCodes as $key => $playCode) {
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

            if (is_array($betNums) && count($betNums) >= $needBall) {
                $ret = Commonfun::getCombination_array($betNums, $needBall);
                if (!is_array($ret)) {
                    return [
                        "code" => 2,
                        "msg" => "投注格式出错"
                    ];
                }
                foreach ($ret as $nums) {
                    $combData = [];
                    $crossData = [];
                    $crossNum = 0;
                    foreach ($nums as $v) {
                        preg_match($pattern, $v, $result);
                        if (is_array($result) && count($result) > 0 && isset($result[2])) {
                            if ($isMix == false) {
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
                        } else {
                            return [
                                "code" => 2,
                                "msg" => "投注格式出错"
                            ];
                        }
                    }
                    if ($needBall != 1) {
                        $str = '';
                        for ($i = 0; $i < $needBall; $i++) {
                            $str.=',$crossData[' . $i . ']';
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
                            $order[$n]["play_name"] = $playNames[$key];
                            $oddsAmount = 1;
                            $item = explode('|', $v2);
                            foreach ($item as $vi) {
                                preg_match($pattern, $vi, $res);
                                if ($isMix == false) {
                                    $oddsAmount *= $odds[$lotteryCode][$res[1]][$res[2]];
                                } else {
                                    $str = explode('*', $res[2]);
                                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                                    $oddsAmount *= $odds[$r[1]][$res[1]][$r[2]];
                                }
                            }
                            $order[$n]['odds'] = $oddsAmount;
                            $n++;
                        }
                    }
                }
            } else {
                return [
                    "code" => 2,
                    "msg" => "投注格式出错"
                ];
            }
        }
        $infos["content"] = $order;
        $result = OrderService::insertDetail($infos);
        if ($result["error"] === true) {
            return [
                "code" => 0,
                "msg" => "操作成功"
            ];
        } else {
            return [
                "code" => 2,
                "msg" => "操作失败",
                "err" => $result
            ];
        }
    }

    /**
     * 获取对应彩种与对应玩法的可下注赛程
     * @param string $lotteryCode
     * @param string $play
     * @return array
     */
    public function getSchedule($lotteryCode, $play) {
        $format = 'Y-m-d H:i:s';
        $col = Constants::SCHEDULE_PLAY;
        if ((is_array($play) && in_array(1, $play)) || $play == 1) {
            $whereStr = " {$col[$lotteryCode]}=2 ";
        } else {
            $whereStr = " ({$col[$lotteryCode]}=2 or {$col[$lotteryCode]}=1) ";
        }
        $data = (new Query())->select("schedule_mid,start_time,endsale_time,schedule_code")
                ->from("schedule ")
                ->where([">", "start_time", date($format, strtotime("+10 minute"))])
                ->andWhere(["<", "beginsale_time", date($format)])
                ->andWhere([">", "endsale_time", date($format)])
                ->andWhere($whereStr)
                ->andWhere(["schedule_status" => 1])
                ->indexBy("schedule_mid")
                ->all();
        return $data;
    }

    public function getOrder($lotteryOrderCode, $cust_no = '', $orderId = '') {
        $where['lottery_order.lottery_order_code'] = $lotteryOrderCode;
        if (!empty($cust_no)) {
            $where['lottery_order.cust_no'] = $cust_no;
        }
        if (!empty($orderId)) {
            $where['lottery_order.lottery_order_id'] = $orderId;
        }
        $status = Constants::ORDER_STATUS;
        $majorArr = CompetConst::MAJOR_ARR;
        $freeChuan = CompetConst::NO_FREE_SCHE;
        $lotOrder = LotteryOrder::find()
                ->select("bet_val,odds,lottery_order.lottery_id,lottery_order.lottery_name,refuse_reason,bet_money,lottery_order_code,lottery_order.create_time,lottery_order_id,lottery_order.status,win_amount,play_code,play_name,bet_double,count,periods,s.store_name,s.store_code,s.telephone as phone_num,major_type,build_code,build_name,l.lottery_pic,lottery_order.deal_status")
                ->leftJoin('store as s', 's.store_code = lottery_order.store_no and s.status = 1')
                ->leftJoin('lottery l', 'l.lottery_code = lottery_order.lottery_id')
                ->where($where)
                ->asArray()
                ->one();
        if ($lotOrder == null) {
            return [
                "code" => 2,
                "msg" => "查询结果不存在"
            ];
        }
        $odds = [];
        if (!empty($lotOrder["odds"])) {
            $odds = json_decode($lotOrder["odds"], 256);
        }
        $lotOrder["status_name"] = $status[$lotOrder["status"]];
        $lotOrder['award_time'] = date('Y-m-d H:i:s', $lotOrder['periods']);
        $lotOrder['major_name'] = $majorArr[$lotOrder['major_type']];
        if (empty($lotOrder['build_code'])) {
            $lotOrder['build_code'] = '';
            $lotOrder['build_name'] = '';
        }
        $playCodeArr = explode(',', $lotOrder['play_code']);
        if (in_array(1, $playCodeArr)) {
            if ($lotOrder['lottery_id'] == 3011) {
                $lotOrder['lottery_name'] = '混合单关';
            } else {
                $lotOrder['lottery_name'] .= '(单)';
            }
        }
        $data = $lotOrder;
        $betVal = trim($lotOrder["bet_val"], "^");
        if ($lotOrder["lottery_id"] != '3011') {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $betNums = explode("|", $betVal);
        if(empty($lotOrder['build_code'])) {
            if(in_array($lotOrder['play_code'], $freeChuan[count($betNums)])) {
                $data['free_type'] = 'M串N';
            } else {
                $data['free_type'] = '自由串关';
            }
        } else {
            if(in_array($lotOrder['build_code'], $freeChuan[count($betNums)])) {
                $data['free_type'] = 'M串N';
            } else {
                $data['free_type'] = '自由串关';
            }
        }
        $mids = [];
        $bets = [];
        foreach ($betNums as $key => $ball) {
            $bets[$key] = [];
            preg_match($pattern, $ball, $result);
            $n = 0;
            if ($lotOrder["lottery_id"] != '3011') {
                $bets[$key]["mid"] = $result[1];
                $mids[] = $result[1];
                $arr = explode(",", $result[2]);
                foreach ($arr as $v) {
                    $bets[$key]["lottery"][$n] = [];
                    $bets[$key]["lottery"][$n]["bet"] = $v;
                    $bets[$key]["lottery"][$n]["play"] = $lotOrder["lottery_id"];
                    $bets[$key]["lottery"][$n]["odds"] = isset($odds[$lotOrder["lottery_id"]][$result[1]][$v]) ? $odds[$lotOrder["lottery_id"]][$result[1]][$v] : "赔率"; //赔率
                    $n++;
                }
            } else {
                $mids[] = $result[1];
                $bets[$key]["mid"] = $result[1];
                $result[2] = trim($result[2], "*");
                $strs = explode("*", $result[2]);
                foreach ($strs as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    $arr = explode(",", $r[2]);
                    foreach ($arr as $v) {
                        $bets[$key]["lottery"][$n] = [];
                        $bets[$key]["lottery"][$n]["bet"] = $v;
                        $bets[$key]["lottery"][$n]["play"] = $r[1];
                        $bets[$key]["lottery"][$n]["odds"] = isset($odds[$r[1]][$result[1]][$v]) ? $odds[$r[1]][$result[1]][$v] : "赔率"; //赔率
                        $n++;
                    }
                }
            }
        }
        $schedules = Schedule::find()
                ->select("schedule.*,sr.schedule_result_3006,sr.schedule_result_3007,sr.schedule_result_3008,sr.schedule_result_3009,sr.schedule_result_3010,sr.status")
                ->join("left join", "schedule_result sr", "schedule.schedule_mid=sr.schedule_mid")
                ->where(["in", "schedule.schedule_mid", $mids])
                ->indexBy("schedule_mid")
                ->asArray()
                ->all();
        $plays = Constants::LOTTERY;
        $bf = Constants::COMPETING_3007_RESULT;
        foreach ($bets as &$val) {
            foreach ($val["lottery"] as $key => $v) {
                $val["lottery"][$key]["play_name"] = $plays[$v["play"]];
            }
            $schedule = $schedules[$val["mid"]];
            $val["schedule_code"] = $schedule["schedule_code"];
            $val["home_team_name"] = $schedule["home_team_name"];
            $val["visit_team_name"] = $schedule["visit_team_name"];
            $val['visit_short_name'] = $schedule['visit_short_name'];
            $val['home_short_name'] = $schedule['home_short_name'];
            $val["schedule_result_3006"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3006"];
            $val["schedule_result_bf"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3007"];
            $val['status'] = $schedule['status'];
            if (!empty($val["schedule_result_bf"])) {
                if (!isset($bf[$val["schedule_result_bf"]])) {
                    $val["schedule_result_bf"] = str_replace(" ", "", $val["schedule_result_bf"]);
                    if (isset($bf[$val["schedule_result_bf"]])) {
                        $val["schedule_result_3007"] = $bf[$val["schedule_result_bf"]];
                    } else {
                        $bfBalls = explode(":", $val["schedule_result_bf"]);
                        if ($bfBalls[0] > $bfBalls[1]) {
                            $val["schedule_result_3007"] = "90";
                        } else if ($bfBalls[0] == $bfBalls[1]) {
                            $val["schedule_result_3007"] = "99";
                        } else {
                            $val["schedule_result_3007"] = "09";
                        }
                    }
                } else {
                    $val["schedule_result_3007"] = $bf[$val["schedule_result_bf"]];
                }
            } else {
                $val["schedule_result_3007"] = "";
            }
            $val["schedule_result_3008"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3008"];
            $val["schedule_result_3009"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3009"];
            $val["schedule_result_3010"] = ($schedule["status"] != 2) ? "" : $schedule["schedule_result_3010"];
            $val["rq_nums"] = $schedule["rq_nums"];
        }
        $data["contents"] = $bets;
        $data['discount_data'] = PayService::getDiscount(['order_code' => $lotteryOrderCode]);
        return [
            "code" => 600,
            "msg" => "获取成功",
            "result" => $data
        ];
    }

    public function getDetail($lotteryOrderCode, $conditionData, $page, $size, $isStore = false) {
        $status = Constants::ORDER_STATUS;
        $lottery = Constants::LOTTERY;
//        if ($isStore == true) {
//            $lotOrder = LotteryOrder::find()
//                    ->select("lottery_order_id,odds,bet_val,lottery_id")
//                    ->where(["lottery_order_code" => $lotteryOrderCode])
//                    ->andWhere(["store_id" => $conditionData])
//                    ->asArray()
//                    ->one();
//        } else {
        $lotOrder = LotteryOrder::find()
                ->select("lottery_order_id,odds,bet_val,lottery_id,lottery_name,build_code,build_name,play_name,play_code,bet_money,lottery_name")
                ->where(["lottery_order_code" => $lotteryOrderCode])
//                    ->andWhere(["cust_no" => $conditionData])
                ->asArray()
                ->one();
//        }
        if ($lotOrder == null) {
            return [
                "code" => 2,
                "msg" => "查询结果不存在"
            ];
        }
        $odds = [];
        if (!empty($lotOrder["odds"])) {
            $odds = json_decode($lotOrder["odds"], 256);
        }
        $total = BettingDetail::find()->where(['lottery_order_id' => $lotOrder['lottery_order_id']])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $bettingDetails = (new Query())
                ->select("betting_detail_id,lottery_order_id,lottery_order_code,lottery_id,lottery_name,play_name,play_code,bet_double,win_amount,bet_val,status,win_level,bet_money")
                ->from("betting_detail")
                ->where(["lottery_order_id" => $lotOrder["lottery_order_id"]])
                ->limit($size)
                ->offset($offset)
                ->all();

        $betVal = trim($lotOrder["bet_val"], "^");
        if ($lotOrder["lottery_id"] != '3011') {
            $patternDetail = '/^([0-9]+)\((([0-9]|,)+)\)$/';
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $patternDetail = '/^([0-9]+\*[0-9]+)\((([0-9]|,)+)\)$/';
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $betNums = explode("|", $betVal);
        foreach ($betNums as $ball) {
            preg_match($pattern, $ball, $result);
            if ($lotOrder["lottery_id"] != '3011') {
                $mids[] = $result[1];
            } else {
                $strs = explode("*", $result[1]);
                $mids[] = $strs[0];
            }
        }
        $schedules = Schedule::find()
                ->select("schedule.schedule_code,schedule.schedule_mid as schedule_mid,schedule.visit_team_name,schedule.home_team_name,schedule.home_short_name,schedule.visit_short_name,schedule.rq_nums,sr.schedule_result_3006,sr.schedule_result_3007,sr.schedule_result_3008,sr.schedule_result_3009,sr.schedule_result_3010")
                ->join("left join", "schedule_result sr", "schedule.schedule_mid=sr.schedule_mid")
                ->where(["in", "schedule.schedule_mid", $mids])
                ->indexBy("schedule_mid")
                ->asArray()
                ->all();
        $betPlay = [];
        foreach ($bettingDetails as &$val) {
            $betNums = explode("|", $val["bet_val"]);
            $val["bet"] = [];
            foreach ($betNums as $key => $ball) {
                preg_match($patternDetail, $ball, $result);
                if ($lotOrder["lottery_id"] != '3011') {
                    $mid = $result[1];
                    $theOdds = isset($odds[$lotOrder["lottery_id"]][$mid][$result[2]]) ? $odds[$lotOrder["lottery_id"]][$mid][$result[2]] : "赔率";
                    if (!isset($betPlay[$lotOrder["lottery_id"]])) {
                        $str = "COMPETING_BET_" . $lotOrder["lottery_id"];
                        eval('$betPlay[$lotOrder["lottery_id"]] = \app\modules\common\helpers\Constants::' . $str . ';');
                    }
                    $val["bet"][] = $schedules[$mid]["schedule_code"] . ($lotOrder["lottery_id"] == '3006' ? '[' . $schedules[$mid]["rq_nums"] . ']' : '') . '(' . $betPlay[$lotOrder["lottery_id"]][$result[2]] . '|' . $theOdds . ')';
                    $val["content"][$key] = [];
                    $val["content"][$key]["schedule_code"] = $schedules[$mid]["schedule_code"];
                    $val["content"][$key]["lottery_code"] = $lotOrder["lottery_id"];
                    $val["content"][$key]["rq_nums"] = $schedules[$mid]["rq_nums"];
                    $val["content"][$key]["bet_play"] = $betPlay[$lotOrder["lottery_id"]][$result[2]];
                    $val['content'][$key]['bet_code'] = $result[2];
                    $val["content"][$key]["bet_odds"] = $theOdds; //赔率
                    $val['content'][$key]['visit_team_name'] = $schedules[$mid]['visit_team_name'];
                    $val['content'][$key]['home_team_name'] = $schedules[$mid]['home_team_name'];
                    $val['content'][$key]['visit_short_name'] = $schedules[$mid]['visit_short_name'];
                    $val['content'][$key]['home_short_name'] = $schedules[$mid]['home_short_name'];
                    $srOpen = 'schedule_result_' . $lotOrder["lottery_id"];
                    if ($srOpen == 'schedule_result_3007') {
                        $schedules[$mid][$srOpen] = str_replace(':', '', $schedules[$mid][$srOpen]);
                    }
                    $val['content'][$key]['result'] = $schedules[$mid][$srOpen];
                } else {
                    $strs = explode("*", $result[1]);
                    $mid = $strs[0];
                    $theOdds = isset($odds[$strs[1]][$mid][$result[2]]) ? $odds[$strs[1]][$mid][$result[2]] : "赔率";
                    if (!isset($betPlay[$strs[1]])) {
                        $str = "COMPETING_BET_" . $strs[1];
                        eval('$betPlay[$strs[1]] = \app\modules\common\helpers\Constants::' . $str . ';');
                    }
                    $val["bet"][] = $schedules[$mid]["schedule_code"] . ($strs[1] == '3006' ? '[' . $schedules[$mid]["rq_nums"] . ']' : '') . '(' . $betPlay[$strs[1]][$result[2]] . '|' . $theOdds . ')';
                    $val["content"][$key]["schedule_code"] = $schedules[$mid]["schedule_code"];
                    $val["content"][$key]["lottery_code"] = $strs[1];
                    $val["content"][$key]["rq_nums"] = $schedules[$mid]["rq_nums"];
                    $val["content"][$key]["bet_play"] = $betPlay[$strs[1]][$result[2]];
                    $val['content'][$key]['bet_code'] = $result[2];
                    $val["content"][$key]["bet_odds"] = $theOdds; //赔率
                    $val['content'][$key]['visit_team_name'] = $schedules[$mid]['visit_team_name'];
                    $val['content'][$key]['home_team_name'] = $schedules[$mid]['home_team_name'];
                    $srOpen = 'schedule_result_' . $strs[1];
                    if ($srOpen == 'schedule_result_3007') {
                        $schedules[$mid][$srOpen] = trim($schedules[$mid][$srOpen], ':');
                    }
                    $val['content'][$key]['result'] = $schedules[$mid][$srOpen];
                }
            }
            $val['status_name'] = $status[$val['status']];
            $val["bet"] = implode("x", $val["bet"]);
        }
        if (!empty($lotOrder['build_code'])) {
            $playStr = $lotOrder['build_name'];
        } else {
            $playStr = $lotOrder['play_name'];
        }
        $playCodeArr = explode(',', $lotOrder['play_code']);
        if (in_array(1, $playCodeArr)) {
            if ($lotOrder['lottery_id'] == 3011) {
                $lotOrder['lottery_name'] = '混合单关';
            } else {
                $lotOrder['lottery_name'] .= '(单)';
            }
        }
        $betAbb = '竞足' . $lotOrder['lottery_name'];
        return [
            "code" => 600,
            "msg" => "获取成功",
            "result" => ['page' => $page, 'pages' => $pages, 'size' => count($bettingDetails), 'total' => $total, 'data' => $bettingDetails, 'count_sche' => count($mids), 'play_str' => $playStr, 'order_money' => $lotOrder['bet_money'], 'bet_abb' => $betAbb]
        ];
    }

    /**
     * 获取历史交锋统计
     * @param integer $home_team_id
     * @param integer $visit_team_id
     * @return array|boolean
     */
    public function getHistoryCount($home_team_id, $visit_team_id) {
        $data = (new Query())->select("*")->from("history_count")->where(["home_team_id" => $home_team_id, "visit_team_id" => $visit_team_id])->one();
        if ($data == null) {
            return false;
        } else {
            return $data;
        }
    }

    /**
     * 获取积分排名统计
     * @param integer $team_id
     * @return array|boolean
     */
    public function getIntegralCount($team_id) {
        $data = (new Query())->select("*")->from("integral_count")->where(["team_id" => $team_id])->indexBy("integral_type")->orderBy("integral_type asc")->all();
        if ($data == null) {
            return false;
        } else {
            return $data;
        }
    }

    /**
     * 获取赛程历史
     * @param integer $team_id
     * @return array|boolean
     */
    public function getScheduleHistory($team_id) {
        $data = (new Query())->select("*")->from("schedule_history")->where(["home_team_id" => $team_id])->orWhere(["visit_team_id" => $team_id])->orderBy("play_time desc")->all();
        if ($data == null) {
            return false;
        } else {
            return $data;
        }
    }

    /**
     * 获取亚盘记录
     * @param integer $home_team_id
     * @param integer $visit_team_id
     * @return array|boolean
     */
    public function getAsianHandicap($home_team_id, $visit_team_id) {
        $data = (new Query())->select("*")->from("asian_handicap")->where(["home_team_id" => $home_team_id, "visit_team_id" => $visit_team_id])->all();
        if ($data == null) {
            return false;
        } else {
            return $data;
        }
    }

    /**
     * 获取欧赔记录
     * @param integer $home_team_id
     * @param integer $visit_team_id
     * @return array|boolean
     */
    public function getEuropeOdds($home_team_id, $visit_team_id) {
        $data = (new Query())->select("*")->from("europe_odds")->where(["home_team_id" => $home_team_id, "visit_team_id" => $visit_team_id])->all();
        if ($data == null) {
            return false;
        } else {
            return $data;
        }
    }

    public function getOdds($lotteryCode, $scheduleMid, $vals) {
        $select = [];
        switch ($lotteryCode) {
            case "3006":
                $arr = [
                    "let_wins" => "3",
                    "let_level" => "1",
                    "let_negative" => "0"
                ];
                foreach ($vals as $val) {
                    $key = array_search($val, $arr);
                    $select[$key] = $val;
                }
                break;
            case "3007":
                foreach ($vals as $val) {
                    $v1 = substr($val, 0, 1);
                    $v2 = substr($val, 1, 1);
                    if ($v1 > $v2) {
                        $select["score_wins_" . $val] = $val;
                    } else if ($v1 == $v2) {
                        $select["score_level_" . $val] = $val;
                    } else {
                        $select["score_negative_" . $val] = $val;
                    }
                }
                break;
            case "3008":
                foreach ($vals as $val) {
                    $select["total_gold_" . $val] = $val;
                }
                break;
            case "3009":
                foreach ($vals as $val) {
                    $select["bqc_" . $val] = $val;
                }
                break;
            case "3010":
                $arr = [
                    "outcome_wins" => "3",
                    "outcome_level" => "1",
                    "outcome_negative" => "0"
                ];
                foreach ($vals as $val) {
                    $key = array_search($val, $arr);
                    $select[$key] = $val;
                }
                break;
        }
        $data = (new Query())->select($this->getAsString($select))->from("odds_" . $lotteryCode)->where(["schedule_mid" => $scheduleMid])->orderBy("updates_nums desc")->one();
        return $data;
    }

    public function getAsString($arr) {
        $strs = [];
        foreach ($arr as $key => $val) {
            $strs[] = $key . ' as ' . $val;
        }
        $str = implode(',', $strs);
        return $str;
    }

    /**
     * 跟新子单赔率
     * @param type $orderId 订单ID
     * @return type
     */
    public function updateOdds($lotteryCode, $orderId, $bet) {
        $odds = [];
        $mids = [];
        if ($lotteryCode != 3011) {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $betNums = explode("|", trim($bet, '^'));
        $result = [];
        $r = [];
        foreach ($betNums as $v) {
            preg_match($pattern, $v, $result);
            if ($lotteryCode != '3011') {
                $mids[] = $result[1];
                $arr = explode(",", $result[2]);
                if (!array_key_exists($lotteryCode, $odds)) {
                    $odds[$lotteryCode] = [];
                }
                $odds[$lotteryCode][$result[1]] = $this->getOdds($lotteryCode, $result[1], $arr);
            } else {
                $resultBalls = trim($result[2], "*");
                $resultBalls = explode("*", $resultBalls);
                $thisCount = 0;
                foreach ($resultBalls as $str) {
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str, $r);
                    $mids[] = $result[1];
                    $arr = explode(",", $r[2]);
                    $thisCount+= count($arr);
                    if (!isset($odds[$r[1]])) {
                        $odds[$r[1]] = [];
                    }
                    $odds[$r[1]][$result[1]] = $this->getOdds($r[1], $result[1], $arr);
                }
            }
        }
        $order = LotteryOrder::findOne(['lottery_order_id' => $orderId]);
        $order->odds = json_encode($odds, JSON_FORCE_OBJECT);
        $order->saveData();
        $detail = BettingDetail::find()->select(['betting_detail_id', 'bet_val'])->where(['lottery_order_id' => $orderId])->asArray()->all();
        $updetail = '';
        foreach ($detail as $val) {
            $betArr = explode('|', $val['bet_val']);
            $oddsAmount = 1;
            $fenData = [];
            foreach ($betArr as $it) {
                preg_match($pattern, $it, $res);
                if ($lotteryCode != 3011) {
                    $oddsAmount *= $odds[$lotteryCode][$res[1]][$res[2]];
                } else {
                    $str = explode('*', $res[2]);
                    preg_match('/^([0-9]+)\((([0-9]|,)+)\)$/', $str[1], $r);
                    $oddsAmount *= $odds[$r[1]][$res[1]][$r[2]];
                }
            }
            $updetail .= "update betting_detail set odds = {$oddsAmount}, fen_json = '" . json_encode($fenData) . "' where betting_detail_id = {$val['betting_detail_id']} and lottery_order_id = {$orderId};";
            BettingDetail::addQueSync(BettingDetail::$syncUpdateType, 'odds,fen_json', ['betting_detail_id' => $val['betting_detail_id'], 'lottery_order_id' => $orderId]);
        }
        $db = \Yii::$app->db;
        $updateId = $db->createCommand($updetail)->execute();
        if ($updateId == false) {
            return ['code' => 109, 'msg' => '详情表修改失败'];
        }
        return ['code' => 600, 'msg' => '赔率修改成功'];
    }

    /**
     * 获取可投注赛程
     * @param type $lotteryCode 彩种玩法
     * @param type $playType 投注类型 2：单关
     * @param type $active 查询来源 1：APP 2：PC
     * @return type
     */
    public function getBetSchedule($lotteryCode, $playType, $active = 1) {
        $schedulePlay = Constants::SCHEDULE_PLAY;
        $scheduleList = [];
        $list = [];
        $where = [];
        $pwhere = [];

        $lottery = Lottery::findOne(["lottery_code" => $lotteryCode]);
        if ($lottery->status == "0") {
            return \Yii::jsonError(109, '投注失败，此彩种已经停止投注，请选择其他彩种进行投注');
        }
        if ($active == 2) {
            $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
                'schedule.beginsale_time', 'schedule.endsale_time', 'schedule.rq_nums', 'schedule.schedule_spf', 'schedule.schedule_rqspf', 'schedule.schedule_bf', 'schedule.schedule_zjqs', 'schedule.schedule_bqcspf',
                'schedule.high_win_status', 'schedule.hot_status', 'schedule.league_name league_short_name', 'schedule.hot_status', 'e.odds_3', 'e.odds_3_trend', 'e.odds_1', 'e.odds_1_trend', 'e.odds_0', 'e.odds_0_trend', 'h.home_team_rank',
                'h.home_team_league', 'h.visit_team_rank', 'h.visit_team_league', 'l.league_color'];
        } else {
            $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
                'schedule.beginsale_time', 'schedule.endsale_time', 'schedule.rq_nums', 'schedule.schedule_spf', 'schedule.schedule_rqspf', 'schedule.schedule_bf', 'schedule.schedule_zjqs', 'schedule.schedule_bqcspf',
                'schedule.high_win_status', 'schedule.hot_status', 'schedule.league_name league_short_name', 'schedule.hot_status'];
        }

        if ($lotteryCode == Lottery::CODE_HH) {
            $oddStr = ['odds3006', 'odds3007', 'odds3008', 'odds3009', 'odds3010'];
            if ($playType == 2) {
                $pwhere = ['or', ['schedule_spf' => 2], ['schedule_rqspf' => 2], ['schedule_bf' => 2], ['schedule_zjqs' => 2], ['schedule_bqcspf' => 2]];
            }
        } else {
            $oddStr = ['odds' . $lotteryCode];
            $playField = $schedulePlay[$lotteryCode];
            $where = ['!=', $playField, 3];
            if ($playType == 2) {
                $pwhere[$schedulePlay[$lotteryCode]] = 2;
            }
        }
        $query = Schedule::find()->select($field);
        if ($active == 2) {
            $query->leftJoin('europe_odds e', "e.schedule_mid = schedule.schedule_mid and e.company_name = '平均欧赔' and e.handicap_type = 2")
                    ->leftJoin('history_count h', 'h.schedule_mid = schedule.schedule_mid')
                    ->leftJoin('league l', 'l.league_id = schedule.league_id and l.league_type = 1');
        }
        $scheDetail = $query->with($oddStr)
                ->where(['schedule.schedule_status' => 1])
                ->andWhere($where)
                ->andWhere($pwhere)
                ->andWhere(['>', 'start_time', date('Y-m-d H:i:s', strtotime("+10 minute"))])
                ->andWhere(['<', 'beginsale_time', date('Y-m-d H:i:s')])
                ->andWhere(['>', 'endsale_time', date('Y-m-d H:i:s')])
                ->orderBy('start_time,schedule_mid')
                ->asArray()
                ->all();
        foreach ($scheDetail as &$value) {
            $shcedultDate = (string) $value['schedule_date'];
            if ($value['hot_status'] == 1) {
                $gameDate = 'hotGame';
            } else {
                $gameDate = date('Y-m-d', strtotime($shcedultDate));
            }

            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            if ($playType == 2 && $lotteryCode == 3011) {
                if ($value['schedule_spf'] == 1) {
                    $value['odds3010'] = null;
                }
                if ($value['schedule_rqspf'] == 1) {
                    $value['odds3006'] = null;
                }
                if ($value['schedule_bf'] == 1) {
                    $value['odds3007'] = null;
                }
                if ($value['schedule_zjqs'] == 1) {
                    $value['odds3008'] = null;
                }
                if ($value['schedule_bqcspf'] == 1) {
                    $value['odds3009'] = null;
                }
            }
            if (array_key_exists($gameDate, $list)) {
                $list[$gameDate]['game'][] = $value;
                $list[$gameDate]['field_num'] += 1;
            } else {
                $list[$gameDate]['game'][] = $value;
                $list[$gameDate]['field_num'] = 1;
                if ($gameDate == 'hotGame') {
                    $list[$gameDate]['hot_status'] = 1;
                    $list[$gameDate]['game_date'] = '';
                    $list[$gameDate]['hot_title'] = '火爆竞猜中';
                } else {
                    $list[$gameDate]['hot_status'] = 0;
                    $list[$gameDate]['game_date'] = date('Y-m-d', strtotime($gameDate));
                    $list[$gameDate]['week'] = '周' . $weekarray[date('w', strtotime($gameDate))];
                }
            }
        }
        $hotStatus = [];
        $gameArr = [];
        foreach ($list as $key => $val) {
            $hotStatus[$key] = $val['hot_status'];
            $gameArr[] = $val['game_date'];
            $scheduleList[] = $val;
        }
        array_multisort($hotStatus, SORT_DESC, $gameArr, SORT_ASC, $scheduleList);
        return $scheduleList;
    }

    public function getHotSchedule() {
        $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
            'schedule.beginsale_time', 'schedule.endsale_time', 'schedule.rq_nums', 'schedule.schedule_spf', 'schedule.schedule_rqspf', 'schedule.schedule_bf', 'schedule.schedule_zjqs', 'schedule.schedule_bqcspf',
            'schedule.high_win_status', 'schedule.hot_status', 'schedule.league_name  league_short_name', 'schedule.hot_status', 'e.odds_3', 'e.odds_3_trend', 'e.odds_1', 'e.odds_1_trend', 'e.odds_0', 'e.odds_0_trend', 'h.home_team_rank',
            'h.home_team_league', 'h.visit_team_rank', 'h.visit_team_league', 'h.double_play_num', 'h.num3', 'h.num1', 'h.num0', 'h.home_num_3', 'h.home_num_1', 'h.home_num_0', 'h.visit_num_3', 'h.visit_num_1', 'h.visit_num_0',
            'h.scale_3010_3', 'h.scale_3010_1', 'h.scale_3010_0', 'h.scale_3006_3', 'h.scale_3006_1', 'h.scale_3006_0', 'p.json_data', 'p.pre_result_title', 'ht.team_img home_team_img', 'vt.team_img visit_team_img', 'l.league_img', 'l.league_color'];

        $oddStr = ['odds3006', 'odds3010'];

        $scheDetail = Schedule::find()->select($field)
                ->leftJoin('history_count h', 'h.schedule_mid = schedule.schedule_mid')
                ->leftJoin('pre_result p', 'p.schedule_mid = h.schedule_mid')
                ->leftJoin('europe_odds e', "e.schedule_mid = schedule.schedule_mid and e.company_name = '平均欧赔' and e.handicap_type = 2")
                ->leftJoin('team ht', 'ht.team_id = schedule.home_team_id and ht.team_type = 1')
                ->leftJoin('team vt', 'vt.team_id = schedule.visit_team_id and vt.team_type = 1')
                ->leftJoin('league l', 'l.league_id = schedule.league_id and l.league_type = 1')
                ->with($oddStr)
                ->where(['schedule.schedule_status' => 1, 'schedule.hot_status' => 1])
                ->andWhere(['>', 'start_time', date('Y-m-d H:i:s', strtotime("+10 minute"))])
                ->andWhere(['<', 'beginsale_time', date('Y-m-d H:i:s')])
                ->andWhere(['>', 'endsale_time', date('Y-m-d H:i:s')])
                ->indexBy('schedule_mid')
                ->orderBy('start_time,schedule_mid')
                ->asArray()
                ->all();
        $mid = array_keys($scheDetail);
        $total = ArticlesPeriods::find()->select(['periods', 'count(expert_articles_id) as total'])
                ->innerJoin('expert_articles as e', 'e.expert_articles_id = articles_periods.articles_id and e.article_status = 3')
                ->where(['in', 'periods', $mid])
                ->indexBy('periods')
                ->groupBy('periods')
                ->asArray()
                ->all();
        $list = [];
        foreach ($scheDetail as $key => &$value) {
            $value['article_total'] = isset($total[$key]) ? $total[$key]['total'] : 0;
            $jsonData = json_decode($value["json_data"], true);
            $value["avg_visit_per"] = sprintf("%.1f", $jsonData["avg_visit_per"]);
            $value["avg_home_per"] = sprintf("%.1f", $jsonData["avg_home_per"]);
            $list[] = $value;
        }
        return $list;
    }

}
