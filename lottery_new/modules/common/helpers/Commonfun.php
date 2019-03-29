<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\common\helpers;

use app\modules\common\helpers\Constants;
use app\modules\common\models\LotteryTime;
use app\modules\common\models\LotteryRecord;
use app\modules\common\models\Odds3006;
use app\modules\common\models\Odds3007;
use app\modules\common\models\Odds3008;
use app\modules\common\models\Odds3009;
use app\modules\common\models\Odds3010;
use app\modules\common\models\Lottery;
use app\modules\common\models\Queue;
use yii\db\Query;
use app\modules\user\helpers\WechatTool;

class Commonfun {

    /**
     * 获取编码
     * @param string $lotteryType
     * @param char $letter
     * @return string
     */
    public static function getCode($lotteryType, $letter) {
        $time = date('ymdH');
        $redisStr = "GLC:" . $lotteryType . ":" . $time . ":" . $letter;
        $likeStr = "GLC" . $lotteryType . $time . $letter;
        $code = $likeStr . (self::getSerialnum($redisStr));
        return $code;
    }

    /**
     * 生成流水号
     * @param string $redisStr
     * @return string
     */
    public static function getSerialnum($redisStr) {
        $redis = \Yii::$app->redis;
        $serialnum = $redis->executeCommand('incr', [$redisStr]);
        $redis->executeCommand('expire', ["{$redisStr}", 7200]);
        $serialnum = sprintf("%07d", $serialnum);
        return $serialnum;
    }

    /**
     * 获取当前期数
     * @param string $lotteryCode
     * @return array
     */
    public static function currentPeriods($lotteryCode, $status = 1) {
        $lotteryRate = Constants::LOTTERY_RATE;
        $lotteryType = Constants::LOTTERY_ABBREVI;
        $key = "Periods:" . $lotteryType[$lotteryCode];

        $info = \yii::redisGet($key);
        if ($info != null) {
            $data = (array) json_decode($info);
            return [
                "error" => true,
                "periods" => $data["periods"],
                "data" => $data
            ];
        }
        if ($lotteryRate[$lotteryCode] == "minute") {
            if ($lotteryCode == '2011') {
                $status == $status;
            } else {
                $status = 1;
            }
        }
        $records = LotteryRecord::find()
                    ->select("lottery_record_id,lottery_code,lottery_name,periods,lottery_time,week,limit_time,pool")
                    ->where(["lottery_code" => $lotteryCode])
                    ->andWhere(["status" => $status])
                    ->asArray()
                    ->one();
        switch ($lotteryRate[$lotteryCode]) {
            case "week":
                $week = date('w', strtotime($records["lottery_time"]));
                $weekChinese = Constants::WEEKARR;
                $theWeek = $weekChinese[$week];
                $lotTime = LotteryTime::find()
                        ->select("limit_time")
                        ->where(["lottery_code" => $lotteryCode, "rate" => "每周", "week" => $theWeek])
                        ->asArray()
                        ->one();
                break;
            case "day":
                $lotTime = LotteryTime::find()
                        ->select("limit_time")
                        ->where(["lottery_code" => $lotteryCode, "rate" => "每天"])
                        ->asArray()
                        ->one();
                break;
            case "minute":
                $limitTime = $records["lottery_time"];
                $data = $records;
                if (empty($records["limit_time"]) && !empty($data)) {
                    $data['limit_time'] = $limitTime;
                    $lotRe = LotteryRecord::findOne($records["lottery_record_id"]);
                    $lotRe->limit_time = $data['limit_time'];
                    $lotRe->save();
                }
                if (strtotime($data['lottery_time']) - time() > 0) {
                    \yii::redisSet($key, json_encode($data), strtotime($data['lottery_time']) - time());
                } else {
                    if ($status == 0) {
                        return ["error" => false];
                    }
                    if ($lotteryCode == '2011') {
                        return self::currentPeriods($lotteryCode, 0);
                    }
                }
                return [
                    "error" => true,
                    "periods" => $data["periods"],
                    "data" => $data
                ];
                break;
            default :
                return [
                    "error" => false,
                    "msg" => "未开放"
                ];
        }
        if ($records != null) {
            $data = $records;
            if (empty($records["limit_time"])) {
                $data['limit_time'] = date('Y-m-d', strtotime($data["lottery_time"])) . " " . $lotTime["limit_time"];
                $lotRe = LotteryRecord::findOne($records["lottery_record_id"]);
                $lotRe->limit_time = $data['limit_time'];
                $lotRe->save();
            }
            if($status == 0) {
                $pool = LotteryRecord::find()->select(['pool'])->where(['lottery_code' => $lotteryCode, 'status' => 1])->asArray()->one();
                $data['pool'] = $pool['pool'];
            }
            if (strtotime($data['limit_time']) < time()) {
                if ($status == 0) {
                    return [
                        "error" => false
                    ];
                } else {
                    return self::currentPeriods($lotteryCode, 0);
                }
            }
            \yii::redisSet($key, json_encode($data), strtotime($data['limit_time']) - time());
            return [
                "error" => true,
                "periods" => $data["periods"],
                "data" => $data
            ];
        } else {
            return [
                "error" => false
            ];
        }
    }

    /**
     * 说明: 获取当期期数（未启用）
     * @author  kevi
     * @date 2017年10月26日 上午9:07:49
     * @param
     * @return 
     */
    public static function getNowPeriods($lotteryCode, $status = 1) {
        $records = LotteryRecord::find()
                        ->select("lottery_record_id,lottery_code,lottery_name,periods,lottery_time,week,limit_time")
                        ->where(["lottery_code" => $lotteryCode])->andWhere(["status" => 1])
                        ->asArray()->one();
        if (!empty($records)) {
            if (strtotime($records['limit_time']) < time()) {//如果该期 投注截止时间大于现在，则取下一期时间作为当期
                $records = LotteryRecord::find()
                                ->select("lottery_record_id,lottery_code,lottery_name,periods,lottery_time,week,limit_time")
                                ->where(["lottery_code" => $lotteryCode])->andWhere(["status" => 0])
                                ->asArray()->one();
            }
        }
        return $records;
    }

    /**
     * 阶乘
     * @n 数字几
     */
    public static function factorial($n) {
        if ($n == 0) {
            return 1;
        }
        return array_product(range(1, $n));
    }

    /**
     * 排列
     * @n n个元素
     * @m 从n个不同元素中取出m个元素的排列数
     */
    public static function arrangement($n, $m) {
        $f1 = self::factorial($n);
        $f2 = self::factorial($n - $m);

        //var_dump($f2);die;
        $arrangement = $f1 / $f2;
        return $arrangement;
    }

    /**
     * 组合数
     * @n n个元素
     * @m 从n个不同元素中取出m个元素的组合数
     */
    public static function getCombination($n, $m) {
        if ($n < $m) {
            return 0;
        }
        $a = self::arrangement($n, $m);

        $f = self::factorial($m);
        $combination = $a / $f;
        return $combination;
    }

    /**
     * 生成排列的数组
     * @param type $arr
     * @param type $num
     * @return array
     */
    public static function getArrangement_array($arr, $num) {
        $result = [];
        $v = [];
        if ($num < 1) {
            return "数据错误！";
        }
        if ($num == 1) {
            if (is_array($arr) && count($arr) > 0) {
                foreach ($arr as $key => $value) {
                    $v = [];
                    $v[] = $value;
                    $result[] = $v;
                }
                return $result;
            } else {
                return "数据错误！";
            }
        } else {
            if (is_array($arr) && count($arr) > 0) {
                $num = $num - 1;
                foreach ($arr as $key => $value) {
                    $arr1 = $arr;
                    unset($arr1[$key]);
                    $ret = self::getArrangement_array($arr1, $num);
                    if (!is_array($ret)) {
                        return $ret;
                    }
                    foreach ($ret as $k => $v) {
                        $v[] = $value;
                        $result[] = $v;
                    }
                }
                return $result;
            } else {
                return "数据错误！";
            }
        }
    }

    /**
     * 生成组合数组
     * @param array $arr
     * @param integer $num
     * @return array
     */
//    public static function getCombination_array($arr, $num) {
//        $ret = self::getArrangement_array($arr, $num);
//        $result = [];
//        if (is_array($ret) && count($ret) > 0) {
//            foreach ($ret as $key => $val) {
//                sort($val);
//                if (!in_array($val, $result)) {
//                    $result[] = $val;
//                }
//            }
//            return $result;
//        } else {
//            return $ret;
//        }
//    }
//    public static function getCombination_array($arr, $num, $v = []) {
//        global $result;
//        if (!is_array($arr)) {
//            return "数据错误！";
//        }
//        sort($arr);
//        $count = count($arr);
//        if ($count < $num) {
//            return "数据错误！";
//        }
//        if ($num == 0) {
//            $result[] = $v;
//        } else {
//            for ($i = 0; $i < $count; $i++) {
//                $v[] = array_shift($arr);
//                self::getCombination_array($arr, $num - 1, $v);
//            }
//        }
//        return $result;
//    }

    public static function getCombination_array($arr, $num) {
        if (!is_array($arr)) {
            return "数据错误！";
        }
        sort($arr);
        $count = count($arr);
        if ($count < $num) {
            return "数据错误！";
        }
        if ($num < 1) {
            return "数据错误！";
        }
        $result = [];
        if ($num == 1) {
            foreach ($arr as $key => $value) {
                $v = [];
                $v[] = $value;
                $result[] = $v;
            }
            return $result;
        } else {
            $num = $num - 1;
            $i = $count - $num;
            foreach ($arr as $key => $value) {
                if ($i <= 0) {
                    break;
                }
                unset($arr[$key]);
                $ret = self::getCombination_array($arr, $num);
                if (!is_array($ret)) {
                    return $ret;
                }
                foreach ($ret as $k => $v) {
                    $v[] = $value;
                    $result[] = $v;
                }
                $i--;
            }
            return $result;
        }
    }

    /**
     * 生成交叉组合数组
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    public static function cross_array($arr1, $arr2) {
        $result = [];
        $num = 0;
        if (is_array($arr1) && is_array($arr2) && count($arr1) > 0 && count($arr2) > 0) {
            foreach ($arr1 as $val1) {
                foreach ($arr2 as $val2) {
                    $result[$num][0] = $val1;
                    $result[$num][1] = $val2;
                    $num++;
                }
            }
            return $result;
        } else {
            return "数据错误！";
        }
    }

    /**
     * 生成交叉组合数组，不可重复出现
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    public static function difCross_array($arr1, $arr2) {
        $result = [];
        $num = 0;
        if (is_array($arr1) && is_array($arr2) && count($arr1) > 0 && count($arr2) > 0) {
            foreach ($arr1 as $val1) {
                foreach ($arr2 as $val2) {
                    if (!in_array($val2, $val1)) {
                        $result[$num] = $val1;
                        $result[$num][] = $val2;
                        $num++;
                    }
                }
            }
            return $result;
        } else {
            return "数据错误！";
        }
    }

    /**
     * 生成交叉组合字符串，必须含有三个参数或者以上，第一个参数为拼接用的字符串
     * @param string $str
     * @param array $arr1
     * @param array $arr2
     * @return string
     */
    public static function proCross_string() {
        $args = func_get_args();
        $count = func_num_args();
        if (!is_string($args[0])) {
            return "数据错误！";
        }
        if ($count >= 3) {
            $result = $args[1];
//            foreach ($args[1] as $key => $val) {
//                $result[$key] = [];
//                $result[$key][] = $val;
//            }
            for ($n = 2; $n < $count; $n++) {
                if (!is_array($args[$n]) || count($args[$n]) < 1) {
                    return "数据错误！";
                }
                $result = self::cross_array($result, $args[$n]);
                if (!is_array($result)) {
                    return "数据错误！";
                }
                foreach ($result as &$val) {
                    $val = implode($args[0], $val);
                }
            }
        } else {
            return "数据错误！";
        }
        return $result;
    }

    /**
     * 生成交叉组合字符串，必须含有三个参数或者以上，第一个参数为拼接用的字符串，不可相同数字同时出现
     * @param string $str
     * @param array $arr1
     * @param array $arr2
     * @return string
     */
    public static function proDifCross_string() {
        $args = func_get_args();
        $count = func_num_args();
        if (!is_string($args[0])) {
            return "数据错误！";
        }
        if ($count >= 3) {
            $result = [];
            foreach ($args[1] as $key => $val) {
                $result[$key] = [];
                $result[$key][] = $val;
            }
            for ($n = 2; $n < $count; $n++) {
                if (!is_array($args[$n]) || count($args[$n]) < 1) {
                    return "数据错误！";
                }
                $result = self::difCross_array($result, $args[$n]);
                if (!is_array($result)) {
                    return "数据错误！";
                }
            }
            foreach ($result as &$val) {
                $val = implode($args[0], $val);
            }
        } else {
            return "数据错误！";
        }
        return $result;
    }

    /**
     * 判断投注的号码是否有重复
     * @param array $arr
     * @return boolean
     */
    public static function numsDifferent($arr) {
        foreach ($arr as $val) {
            if (strpos($val['nums'], '|')) {
                $numsArr = explode('|', $val['nums']);
            } elseif (strpos($val['nums'], '#')) {
                $numsArr = explode('#', $val['nums']);
            } elseif (strpos($val['nums'], ';')) {
                $numsArr = explode(';', $val['nums']);
            } else {
                $numsArr = explode('|', $val['nums']);
            }
            $diffArr = [];
            if (is_array($numsArr)) {
                foreach ($numsArr as $val) {
                    $diffArr = explode(',', $val);
                    $isnums = self::allIsNumber($diffArr);
                    $diff = self::allDifferent($diffArr);
                    if ($diff == false || $isnums == false) {
                        return false;
                    }
                }
            } else {
                $diffArr = explode(',', $numsArr);
                $isnums = self::allIsNumber($diffArr);
                $diff = self::allDifferent($diffArr);
                if ($diff == false || $isnums == false) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 判断是否有重复
     * @param array $arr
     * @return boolean
     */
    public static function allDifferent($arr) {
        $n = count($arr) - 1;
        for ($m = 0; $m < $n; $m++) {
            $val = $arr[$m];
            unset($arr[$m]);
            if (in_array($val, $arr)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 是否数组中以数字组成
     * @param type $balls
     * @return boolean
     */
    public static function allIsNumber($balls) {
        foreach ($balls as $ball) {
            if (is_numeric($ball) == false) {
                return false;
            }
        }
        return true;
    }

    /**
     * 缺个成数组,并补零
     * @param type $nums
     * @return array
     */
    public static function noteNums($nums) {
        return explode('|', $nums);
//        $arr = [];
//        if (count($numsArr) == 1) {
//            $nums = explode(',', $numsArr[0]);
//            foreach ($nums as &$val) {
//                $val = sprintf("%02d", $val);
//            }
//            $data = implode(',', $nums);
//            $arr[] = $data;
//        } else {
//            foreach ($numsArr as $val) {
//                $nums = explode(',', $val);
//                foreach ($nums as &$val) {
//                    $val = sprintf("%02d", $val);
//                }
//                $data = implode(',', $nums);
//                $arr[] = $data;
//            }
//        }
//        return $arr;
    }

    /**
     * 赛程让球赔率
     * @param int $scheduleId
     * @return array
     */
    public static function getOdds3006($scheduleId) {
        $odds = Odds3006::find()->select(['let_ball_nums', 'let_wins', 'let_level', 'let_negative'])->where(['schedule_id' => $scheduleId])->orderBy('updates_nums desc')->asArray()->one();
        return $odds;
    }

    /**
     * 比分赔率
     * @param int $scheduleId
     * @return array
     */
    public static function getOdds3007($scheduleId) {
        $odds = Odds3007::find()->where(['schedule_id' => $scheduleId])->orderBy('updates_nums desc')->asArray()->one();
        return $odds;
    }

    /**
     * 总进球数赔率
     * @param int $scheduleId
     * @return array
     */
    public static function getOdds3008($scheduleId) {
        $odds = Odds3008::find()->where(['schedule_id' => $scheduleId])->orderBy('updates_nums desc')->asArray()->one();
        return $odds;
    }

    /**
     * 半全场赔率
     * @param int $scheduleId
     * @return array
     */
    public static function getOdds3009($scheduleId) {
        $odds = Odds3009::find()->where(['schedule_id' => $scheduleId])->orderBy('updates_nums desc')->asArray()->one();
        return $odds;
    }

    /**
     * 胜平负赔率
     * @param int $scheduleId
     * @return array
     */
    public static function getOdds3010($scheduleId) {
        $odds = Odds3010::find()->select(['outcome_wins', 'outcome_level', 'outcome_negative'])->where(['schedule_id' => $scheduleId])->orderBy('updates_nums desc')->asArray()->one();
        return $odds;
    }

    public static function stationLetter() {
        //发送站内信通知客服同时写入日志-----!!未完成
    }

    /**
     * 支付限制更新
     */
    public static function updatePayLimit() {
        $list_0 = (new Query())->select("*")->from("pay_type")->where(["parent_id" => 0])->indexBy("pay_type_id")->all();
        $list_1 = (new Query())->select("*")->from("pay_type")->where([">", "parent_id", 0])->andwhere(["status" => 1])->all();
        $arr = [];
        foreach ($list_1 as $val) {
            $key = $list_0[$val["parent_id"]]["pay_type_code"];
            if (!isset($arr[$key])) {
                $arr[$key] = [];
            }
            $arr[$key][] = $val["pay_type_code"];
        }
        foreach ($arr as $key => $val) {
            \Yii::redisSet("pay:" . $key . "_limit_pay", implode(",", $val));
        }
        foreach ($list_0 as $val) {
            \Yii::redisSet("pay:" . $val["pay_type_code"], $val["status"]);
        }
    }

    /**
     * 添加队列
     * @param type $job
     * @param type $queueName
     * @param type $args
     * @return boolean
     */
    public static function addQueue($job, $queueName, $args) {
        $queue = new Queue();
        $queue->job = $job;
        $queue->queue_name = $queueName;
        $queue->args = json_encode($args, true);
        $queue->status = 1;
        $queue->create_time = date("Y-m-d H:i:s");
        if ($queue->validate()) {
            $ret = $queue->save();
            if ($ret == false) {
                //消息推送
                return false;
            }
            return $queue->queue_id;
        } else {
            $e = $queue->getFirstErrors();
            //消息推送
            return false;
        }
    }

    /**
     * 更新队列
     * @param type $queueId
     * @param type $status
     * @return boolean
     */
    public static function updateQueue($queueId, $status) {
        $ret = Queue::updateAll([
                    "status" => $status,
                    "modify_time" => date("Y-m-d H:i:s")
                        ], [
                    "queue_id" => $queueId
        ]);
        if ($ret == false) {
            //消息推送
            return false;
        }
        return true;
    }

    /**
     * 出错警报推送给开发人员
     * @param type $title
     * @param type $level
     * @param type $errorInfo
     * @param type $status
     * @param type $remark
     */
    public static function sysAlert($title, $level, $errorInfo, $status, $remark) {
        $wechatTool = new WechatTool();
        $wx = [];
        if (YII_ENV == "dev") {
            $wx[] = "otEbv0SK-A0T5dBi17TPIOA1dXkg";
            $wx[] = "otEbv0UFiPepm8Se606re-pJv7RQ";
            $wx[] = "otEbv0YBBw-9FL_ly8g6CigdiaNY";
            $wx[] = "otEbv0RVq41n4aFfpDOOxUuON3Hc";
            $wx[] = "otEbv0QRT-Rox4fgrLy-8QGv4254";
            $wx[] = "otEbv0X0grvJWxpCExRMuPf6pPuA";
            $wx[] = "otEbv0QKbRMgWfAB0RXMRCS7muF0";
            $wx[] = "otEbv0ciDzEwsP3KgUyage2PQjaE";
            $serverName = "测试服务";
        } else {
            $wx[] = "oV4Ujw-7Ymtu2vP8UCpWHje-v_iE";
            $wx[] = "oV4Ujw1HpXQUu_5PTYKdCzrA-kas";
            $wx[] = "oV4UjwxwG6T9Oz0MSBJrF9X-O4w0";
            $wx[] = "oV4Ujw8Eb0RocX85-KWD7yqc-qRM";
            $wx[] = "oV4Ujw53u2li86du5hl4YcHED0KE";
            $wx[] = "oV4Ujw_QF7SMjkhs5pIxfZ-umaO8";
            $wx[] = "oV4Ujw2xbHsbmJ0QjKCMfUKYxzmI";
            $wx[] = "oV4Ujw8YQguMc97A__kf0yJywmSw";
            $wx[] = "oV4UjwxvEkD6M1SnchiW_4ZJdFCI";
            $serverName = "正式服务";
        }
        $time = date("Y-m-d H:i:s");
        foreach ($wx as $val) {
            $ret = $wechatTool->sendTemplateMsgSysAlert($title, $val, $level, $errorInfo, $serverName, $time, $status, $remark);
//            var_dump($ret);
        }
    }

    /**
     * 获取是否实名验证
     */
    public static function javaGetStatus($custNo) {
        $surl = \Yii::$app->params['java_getStatus'];
        $postData = ['custNo' => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * 获取预计派奖时间
     * @param type $maxTime
     * @return type
     */
    public static function getAwardTime($maxTime) {
        $date = date('Y-m-d', strtotime($maxTime));
        $nDate = strtotime($maxTime);
        $time1 = strtotime($date . ' 21:30:00');
        $time2 = strtotime($date . ' 23:59:59');
        $time3 = strtotime($date . ' 00:00:00');
        $time4 = strtotime($date . ' 07:00:00');
        if ($nDate >= $time1 && $nDate <= $time2) {
            $time = date('Y-m-d', strtotime('+1 day', $nDate)) . '09:00:00';
        } elseif ($nDate >= $time3 && $nDate <= $time4) {
            $time = $date . '09:00:00';
        } else {
            $time = date('Y-m-d H:i:s', strtotime('+3 hours', strtotime($maxTime)));
        }
        $awardTime = (string) strtotime($time);
        return $awardTime;
    }

    /**
     * 判断数组是否存在该键
     * @param type $data
     * @param type $name
     * @return type
     */
    public static function arrParameter($data, $name) {
        if (!isset($data[$name])) {
            return \Yii::jsonError(109, $name . "参数缺失;");
        }
        return $data[$name];
    }

    /**
     * sql语句模板数据嵌入
     * @param string $tempStr
     * @param array $data
     * @return string
     */
    public static function strTemplateReplace($tempStr, $data) {
        $arr = [];
        foreach ($data as $key => $val) {
            $arr[":" . $key] = $val;
        }
        $result = strtr($tempStr, $arr);
        return $result;
    }

    /**
     * 判断开奖结果形态
     * @param type $openNums
     * @return int
     */
    public static function openType($openNums) {
        $openArr = explode(',', $openNums);
        $type = [];
        $numsArr = [];
        $puke = Constants::PUKE_NUMS;
        $nums = array_flip($puke);
        foreach ($openArr as $val) {
            $item = explode('_', $val);
            if (!in_array($item[0], $type)) {
                $type[] = $item[0];
            }
            $item[1] = $nums[$item[1]];
            if (!in_array($item[1], $numsArr)) {
                $numsArr[] = $item[1];
            }
        }
        sort($numsArr);
        if (count($numsArr) == 1) {
            return 1;
        } elseif (count($numsArr) == 2) {
            return 5;
        } else {
            for ($i = 2; $i < count($numsArr); $i++) {
                if ($numsArr[$i - 2] + 1 == $numsArr[$i - 1] && $numsArr[$i - 1] + 1 == $numsArr[$i]) {
                    $flag = 1;
                } else {
                    if ($numsArr == ['1', '12', '13']) {
                        $flag = 1;
                    } else {
                        $flag = 0;
                    }
                }
            }
            if (count($type) == 1 && $flag == 1) {
                return 2;
            } elseif (count($type) == 1 && $flag == 0) {
                return 3;
            } elseif ($flag == 1) {
                return 4;
            } else {
                return 6;
            }
        }
    }

}
