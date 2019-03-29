<?php

namespace app\modules\cron\controllers;

use app\modules\common\models\LotteryAdditional;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\ScheduleResult;
use app\modules\common\models\Schedule;
use app\modules\common\models\Queue;
use app\modules\common\services\OrderService;
use yii\web\Controller;
use app\modules\common\services\ExpertLevelService;
use app\modules\common\helpers\OrderNews;
use app\modules\common\helpers\Winning;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\ArticleRed;
use app\modules\user\helpers\WechatTool;
use app\modules\common\models\PayRecord;
use app\modules\user\models\ThirdUser;
use app\modules\user\models\User;
use app\modules\competing\models\LanScheduleResult;
use app\modules\common\models\LotteryRecord;
use app\modules\competing\models\LanSchedule;
use app\modules\common\helpers\Constants;
use app\modules\orders\models\TicketDispenser;
use yii\db\Expression;
use app\modules\common\services\SyncService;
use app\modules\orders\helpers\DealOrder;
use app\modules\common\services\KafkaService;
use app\modules\user\helpers\UserCoinHelper;

class TimeController extends Controller {

    /**
     * 合买截止时间30分钟内的出单
     * @return boolean
     */
    public function actionProgrammeLimitPlay() {
        $programmeService = new \app\modules\common\services\ProgrammeService();
        $ret = $programmeService->limitTimePlay();
        //SyncService::syncFromHttp();
        \Yii::redisSet('cron:programmelimitplay', date('Y-m-d H:i:s'));
        return $ret;
    }

    /**
     * 追号定时出单
     * @return boolean
     */
    public function actionAdditionTimePlay() {
        $lotAdds = LotteryAdditional::find()->select("lottery_additional_id")->where(["status" => 2, "pay_status" => 1])->andWhere([">", "periods_total", 1])->asArray()->all();
        if ($lotAdds == null) {
            return true;
        }
        foreach ($lotAdds as $val) {
            $ret = OrderService::additionalOrder($val["lottery_additional_id"]);
            $data[] = $ret;
            if ($ret == false) {
                echo "lottery_additional_id:" . $val["lottery_additional_id"] . ",失败";
            } else {
                echo "lottery_additional_id:" . $val["lottery_additional_id"] . ",成功";
            }
        }
        \Yii::redisSet('ce', json_encode($data));
        return true;
    }

    /**
     * 竞彩足球详情单兑奖
     * @return type
     */
    public function actionCashComptingDetail() {
        header('Access-Control-Allow-Origin:*');
        $request = \yii::$app->request;
        $mid = $request->get('mid', '');
        $code = $request->get('code', '');
        if (empty($mid) || empty($code)) {
            return $this->jsonError(100, 'mid不可为空');
        }
//        $articleRed = new ArticleRed();
//        $articleRed->acticlePreResult($mid);
//        $winning = new \app\modules\common\helpers\Winning();
//        $ret = $winning->getWinningCompeting($mid);
//        return $this->jsonResult(600, '成功', $ret['data']);
//        return $ret;

        $wining = new Winning();
        if ($code == 3000) {
            $data = ScheduleResult::find()->select(['schedule_result_3006', 'schedule_result_3007', 'schedule_result_3008', 'schedule_result_3009', 'schedule_result_3010'])->where(['schedule_mid' => $mid, 'status' => 2])->asArray()->one();
            if (empty($data)) {
                return $this->jsonError(109, '此场次暂未开始');
            }
            $bifen = Constants::BIFEN_ARR;
            $result3007 = str_replace(':', '', $data['schedule_result_3007']);
            if ($data['schedule_result_3010'] == 0) {
                if (!in_array($result3007, $bifen[0])) {
                    $result3007 = '09';
                }
            } elseif ($data['schedule_result_3010'] == 1) {
                if (!in_array($result3007, $bifen[1])) {
                    $result3007 = '99';
                }
            } elseif ($data['schedule_result_3010'] == 3) {
                if (!in_array($result3007, $bifen[3])) {
                    $result3007 = '90';
                }
            }
            if ($data['schedule_result_3008'] > 7) {
                $result3008 = 7;
            } else {
                $result3008 = $data['schedule_result_3008'];
            }
            $ret = $wining->footballLevel($mid, $data['schedule_result_3006'], "'" . $result3007 . "'", "'" . $result3008 . "'", "'" . $data['schedule_result_3009'] . "'", "'" . $data['schedule_result_3010'] . "'");
        } elseif ($code == 3100) {
            $data = LanScheduleResult::find()->select(['result_qcbf'])->where(['schedule_mid' => $mid, 'result_status' => 2])->asArray()->one();
            if (empty($data)) {
                return $this->jsonError(109, '此场次暂未开始');
            }
            $bf = explode(':', $data['result_qcbf']);
            $ret = $wining->basketballLevel($mid, $bf[0], $bf[1]);
        }

        return $this->jsonResult(600, '成功', $ret);
    }

    /**
     * 竞彩足球订单兑奖
     * @return type
     */
    public function actionCashComptingOrder() {
        $winning = new Winning();
        $ret = $winning->getComptingOrder();
        return $this->jsonResult(600, '成功', $ret['data']);
//        return $ret;
    }

    /**
     * 派奖(暂停)
     * @return type
     */
    public function actionGetAwardFunds() {
        $winning = new Winning();
        $ret=['data'=>1];
//        $ret = $winning->getAwardsFunds();
        return $this->jsonResult(600, '成功', $ret['data']);
    }

    /**
     * 订单出票失败
     * @return boolean
     */
    public function actionOrderOutFalse() {
        //9过点撤销，
        $lotOrders = LotteryOrder::find()->select("lottery_order_code")->where(["status" => 9, "deal_status" => 0])->asArray()->all();
        if ($lotOrders == null) {
            return true;
        }
        foreach ($lotOrders as $val) {
            $ret = OrderService::outOrderFalse($val["lottery_order_code"], 9);
            if ($ret == false) {
                echo "lottery_order_code:" . $val["lottery_order_code"] . ",失败";
            } else {
                echo "lottery_order_code:" . $val["lottery_order_code"] . ",成功";
            }
        }
        \Yii::redisSet('cron:OrderOutFalse', date('Y-m-d H:i:s'));
    }

    /**
     * 查询到账
     * @auther GL zyl
     * @return type
     */
    public function actionGetToAccount() {
        $withdraw = new \app\modules\common\services\WithdrawService;
        $redis = \Yii::$app->redis;
        $redisRets = $redis->executeCommand('zrange', ["waitting_callback_withdraw", 0, -1]);
        if (empty($redisRets)) {
            return $this->jsonError(600, '暂无提现记录');
        }
        $flag = $succFlag = 0;
        $logs = '成功回调:';
        foreach ($redisRets as $withdrawCode) {
            $data = $withdraw->getWithdrawToAccoutn($withdrawCode);
            if ($data['code'] == 600) {
                $redis->executeCommand('ZREM', ["waitting_callback_withdraw", $withdrawCode]);
                $logs.= $withdrawCode . ',';
                $succFlag++;
                SyncService::syncFromHttp();
            }
            if ($flag == 50) {
                break;
            }
            $flag++;
        }
        if($succFlag>0){
            KafkaService::addLog('withdraw_log',$logs);
        }
        return $this->jsonResult(600, '查询成功', $data);
    }

    /**
     * 合买派奖
     * @auther GL zyl
     * @return type
     */
    public function actionProgrammeAwardFunds() {
        $winning = new Winning();
        $ret = $winning->programmeAwardFunds();
        \Yii::redisSet('cron:ProgrammeAwardFunds', date('Y-m-d H:i:s'));
        return $this->jsonResult(600, '合买派奖', $ret);
    }

    /**
     * 计划派奖 （暂停）
     * @auther GL zyl
     * @return type
     */
    public function actionPlanAwardFunds() {
        $winning = new Winning();
        $ret = $winning->planAwardsFunds();
        return $this->jsonResult(600, '计划购彩派奖成功', $ret);
    }

    /**
     * 定时警报
     */
    public function actionTimedAlarm() {
        $sT = strtotime('00:30');
        $eT = strtotime('06:30');
        $nT = strtotime(date('H:i'));
        if ($nT > $sT && $nT < $eT) {
            return $this->jsonResult(600, '00:30-06:30时间段内不推送', true);
        }
        //数字彩（11X5 除外）SZ
        //SZ1
        $lotterys = [
            '1001' => '双色球',
            '1002' => '福彩3D',
            '1003' => '七乐彩',
            '2001' => '超级大乐透',
            '2002' => '排列三',
            '2003' => '排列五',
            '2004' => '七星彩'
        ];
        foreach ($lotterys as $key => $val) {
            $ret = Commonfun::currentPeriods($key);
            if ($ret["error"] == false) {
                Commonfun::sysAlert($val . "期数错误", "紧急", $val, "未处理", "请尽快处理！");
            }
        }
        //SZ2
//        $szTimeS = date('Y-m-d') . ' 00:00:00';
//        $szTimeE = date('Y-m-d') . ' 23:59:59';
        $nowTime = time();
        $codeArr = array_keys($lotterys);
        $szRecord = LotteryRecord::find()->select(['lottery_name', 'periods', 'lottery_time', 'lottery_numbers'])->where(['status' => 1])->andWhere(['in', 'lottery_code', $codeArr])->asArray()->all();
        if (!empty($szRecord)) {
            $errorInfo = '';
            foreach ($szRecord as $sz) {
                if ($nowTime >= strtotime("{$sz['lottery_time']} +30 minutes")) {
                    if (empty($val['lottery_numbers'])) {
                        $errorInfo .= $sz['lottery_name'] . '_' . $sz['periods'] . ' 、';
                    }
                }
            }
            if (!empty($errorInfo)) {
                Commonfun::sysAlert("数字彩已开奖30分钟还没数据", "紧急", $errorInfo, "未处理", "请尽快处理！");
            }
        }

        //方案1：时间150分钟之后是否结束
//        $userDomain = \Yii::$app->params["userDomain"];
        $time1 = date("Y-m-d H:i:s", strtotime("-150 minutes"));
        $scheduleQuery1 = ScheduleResult::find()->select("schedule_mid")->where(['in', "status", [1, 6]]);
        $info1 = Schedule::find()->select(["schedule_mid", "schedule_code"])->where(["<", "start_time", $time1])->andWhere(["in", "schedule_mid", $scheduleQuery1])->asArray()->all();
        if ($info1 != null) {
            $errorInfo = "schedulMid_ZQ:";
            foreach ($info1 as $val) {
                $errorInfo.= $val['schedule_code'] . "({$val['schedule_mid']})、";
            }
            Commonfun::sysAlert("足球比赛时间150分钟之后未结束", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }

        //方案2：比赛中 5分钟内都不曾更新
        $time2 = date("Y-m-d H:i:s", strtotime("-5 minutes"));
        $info2 = ScheduleResult::find()->select(["schedule_result.schedule_mid", 's.schedule_code'])
                ->leftJoin('schedule s', 's.schedule_mid = schedule_result.schedule_mid')
                ->where(["schedule_result.status" => 1])
                ->andWhere(["<", "schedule_result.modify_time", $time2])
                ->andWhere(["!=", "schedule_result.match_time", "90+"])
                ->asArray()
                ->all();
        if ($info2 != null) {
            $errorInfo = "schedulMid_ZQ:";
            foreach ($info2 as $val) {
                $errorInfo.= $val['schedule_code'] . "({$val['schedule_mid']})、";
            }
            Commonfun::sysAlert("足球比赛中5分钟内都不曾更新", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }


        //方案3：比赛开始时间已经超过30分钟没数据 
        $time3 = date("Y-m-d H:i:s", strtotime("-30 minutes"));
        $scheduleQuery3 = ScheduleResult::find()->select("schedule_mid")->where(["status" => 0]);
        $info3 = Schedule::find()->select(["schedule_mid", 'schedule_code'])->where(["<", "start_time", $time3])->andWhere(["in", "schedule_mid", $scheduleQuery3])->asArray()->all();

        if ($info3 != null) {
            $errorInfo = "schedulMid_ZQ:";
            foreach ($info3 as $val) {
                $errorInfo.= $val['schedule_code'] . "({$val['schedule_mid']})、";
            }
            Commonfun::sysAlert("足球比赛开始时间已经超过30分钟没数据", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }


        $qId = [];
        $time4 = date("Y-m-d H:i:s", strtotime("-10 minutes"));
        $info4 = Queue::find()->where(["<", "create_time", $time4])->andWhere(["push_status" => 1, "status" => 1])->asArray()->all();
        if ($info4 != null) {
            $errorInfo = "queueId:";
            foreach ($info4 as $val) {
                $qId[] = $val["queue_id"];
                $errorInfo.="{$val['queue_id']}、";
            }
            Commonfun::sysAlert("线程超过十分钟还未开始跑", "加急", $errorInfo, "未处理", "请尽快处理！");
        }


        $info5 = Queue::find()->where(["<", "create_time", $time4])->andWhere(["push_status" => 1, "status" => 2])->asArray()->all();
        if ($info5 != null) {
            $errorInfo = "queueId:";
            foreach ($info5 as $val) {
                $qId[] = $val["queue_id"];
                $errorInfo.="{$val['queue_id']}、";
            }
            Commonfun::sysAlert("线程未跑完就中断了", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }


        $info6 = Queue::find()->where(["<", "create_time", $time4])->andWhere(["push_status" => 1, "status" => 4])->asArray()->all();
        if ($info6 != null) {
            $errorInfo = "queueId:";
            foreach ($info6 as $val) {
                $qId[] = $val["queue_id"];
                $errorInfo.="{$val['queue_id']}、";
            }
            Commonfun::sysAlert("线程异常,详细错误请查看数据库", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }

        Queue::updateAll(["push_status" => 2], ["in", "queue_id", $qId]);



        //方案 赛程结果错误
        $outTime = ScheduleResult::find()->select(['schedule_result.schedule_mid', 's.schedule_code'])
                ->leftJoin('schedule s', 's.schedule_mid = schedule_result.schedule_mid')
                ->where(['schedule_result.status' => 5])
                ->asArray()
                ->all();
        if (!empty($outTime)) {
            $errorInfo = "schedulMid_ZQ:";
            foreach ($outTime as $ov) {
                $errorInfo .= $ov['schedule_code'] . '(' . $ov['schedule_mid'] . ')、';
                Commonfun::sysAlert("足球赛程结果不对", "紧急", $errorInfo, "未处理", "请尽快处理！");
            }
        }

        //篮球赛程 L
        //L1: 180分钟 比赛未结算警报
        $lanTime = date("Y-m-d H:i:s", strtotime("-180 minutes"));
        $lanSchedule = LanScheduleResult::find()->select("schedule_mid")->where(["result_status" => 1]);
        $lanInfo = LanSchedule::find()->select(["schedule_mid", 'schedule_code'])->where(["<", "start_time", $lanTime])->andWhere(["in", "schedule_mid", $lanSchedule])->asArray()->all();
        if (!empty($lanInfo)) {
            $errorInfo = "schedulMid_LQ:";
            foreach ($lanInfo as $val) {
                $errorInfo.= $val['schedule_code'] . "({$val['schedule_mid']})、";
            }
            Commonfun::sysAlert("篮球比赛时间180分钟之后未结束", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }

        //L2：比赛中 5分钟内都不曾更新
        $lanTime2 = date("Y-m-d H:i:s", strtotime("-5 minutes"));
        $lanInfo2 = LanScheduleResult::find()->select(["lan_schedule_result.schedule_mid", 's.schedule_code'])
                ->leftJoin('lan_schedule s', 's.schedule_mid = lan_schedule_result.schedule_mid')
                ->where(["lan_schedule_result.result_status" => 1])
                ->andWhere(["<", "lan_schedule_result.update_time", $lanTime2])
                ->asArray()
                ->all();
        if (!empty($lanInfo2)) {
            $errorInfo = "schedulMid_LQ:";
            foreach ($lanInfo2 as $val) {
                $errorInfo.= $val['schedule_code'] . "({$val['schedule_mid']})、";
            }
            Commonfun::sysAlert("篮球比赛中5分钟内都不曾更新", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }

        //L3：比赛开始时间已经超过30分钟没数据 
        $lanTime3 = date("Y-m-d H:i:s", strtotime("-30 minutes"));
        $lanSchedule2 = LanScheduleResult::find()->select("schedule_mid")->where(["result_status" => 0]);
        $lanInfo3 = LanSchedule::find()->select(["schedule_mid", 'schedule_code'])->where(["<", "start_time", $lanTime3])->andWhere(["in", "schedule_mid", $lanSchedule2])->asArray()->all();
        if (!empty($lanInfo3)) {
            $errorInfo = "schedulMid_LQ:";
            foreach ($lanInfo3 as $val) {
                $errorInfo.= $val['schedule_code'] . "({$val['schedule_mid']})、";
            }
            Commonfun::sysAlert("篮球比赛开始时间已经超过30分钟没数据", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }

        //L4 赛程结果错误
        $outTimeLan = LanScheduleResult::find()->select(['lan_schedule_result.schedule_mid', 's.schedule_code'])
                ->leftJoin('lan_schedule s', 's.schedule_mid = lan_schedule_result.schedule_mid')
                ->where(['lan_schedule_result.result_status' => 5])
                ->asArray()
                ->all();
        if (!empty($outTimeLan)) {
            $errorInfo = "schedulMid_LQ:";
            foreach ($outTimeLan as $lv) {
                $errorInfo .= $lv['schedule_code'] . '(' . $lv['schedule_mid'] . ')、';
                Commonfun::sysAlert("篮球赛程结果不对", "紧急", $errorInfo, "未处理", "请尽快处理！");
            }
        }

        //方案：赛程推迟还未重新开赛
        $delayTime = date("Y-m-d H:i:s", strtotime("-36 hours"));
        $delayQuery = ScheduleResult::find()->select("schedule_mid")->where(['status' => 4]);
        $delayInfo = Schedule::find()->select(["schedule_mid", 'schedule_code'])->where(["<", "start_time", $delayTime])->andWhere(["in", "schedule_mid", $delayQuery])->asArray()->all();
        if (!empty($delayInfo)) {
            $errorInfo = "schedulMid_ZQ:";
            foreach ($delayInfo as $val) {
                $errorInfo.= $val['schedule_code'] . "({$val['schedule_mid']})、";
            }
            Commonfun::sysAlert("足球比赛推迟36小时还未处理", "紧急", $errorInfo, "未处理", "请尽快处理！");
        }

        $lotArr = [2005, 2006, 2007, 2010, 2011];
        $ret = LotteryRecord::find()->select(['periods', 'lottery_time', 'lottery_code', 'lottery_name'])->where(['status' => 1])->andWhere(['in', 'lottery_code', $lotArr])->indexBy('lottery_code')->asArray()->all();
        if (empty($ret)) {
            Commonfun::sysAlert("11X5数据错误", "紧急", "在开售期间，无期数出售", "未处理", "请尽快处理！");
        } else {
            foreach ($ret as $key => $kq) {
                if (empty($kq)) {
                    Commonfun::sysAlert("11X5_{$kq['lottery_name']}", "紧急", "在开售期间，无期数出售", "未处理", "请尽快处理！");
                } else {
                    if (strtotime('10 minute', strtotime($kq['lottery_time'])) < time()) {
                        Commonfun::sysAlert("11X5_{$kq['lottery_name']}", "紧急", "{$kq['periods']}_已过开奖时间，当前期还未开奖！", "未处理", "请尽快处理！");
                    }
                }
            }
        }

        exit(1);
    }

    /**
     * 合买等级定时更新
     * @auther GL zyl
     * @return type
     */
    public function actionUpdateProgrammeLevels() {
        $expertLevel = new ExpertLevelService();
        $ret = $expertLevel->updateTable();
        $sql = "call sp_update_expert_levels();";
        $connection = \Yii::$app->db;
        $connection->createCommand($sql)->execute();

        return $this->jsonResult(600, '操作成功', $ret);
    }

    /**
     * 合买子单兑奖
     * @auther GL zyl
     * @return type
     */
    public function actionProgrammeUserAward() {
//        $winning = new Winning();
//        $ret = $winning->programmeUserAwardFunds();
        return $this->jsonResult(600, '操作成功', true);
    }

    /**
     * 用户中奖结果通知 (暂定半小时)
     * @auther GL zyl
     * @return type
     */
    public function actionWechatMsgOrderAward() {
        $ret = OrderNews::sendWechatMsg();
        \Yii::redisSet('cron:WechatMsgOrderAward', date('Y-m-d H:i:s'));
        return $this->jsonResult(600, '操作成功', $ret);
    }

    /**
     * 方案清算线程写入
     * @return type
     */
    public function actionArticlePushJob() {
        $articleRed = new ArticleRed();
        $ret = $articleRed->articlePushJob();
        return $this->jsonResult(600, $ret['msg'], true);
    }

    /**
     * 方案文章审核通知
     */
    public function actionSendReviewMsg() {
        $get = \Yii::$app->request->get();
        $articleStatus = [
            "3" => "上线",
            "4" => "下线",
            "5" => "审核失败"
        ];
        $info = (new \yii\db\Query())->select("*")->from("expert_articles")->where(["expert_articles_id" => $get["expert_articles_id"]])->one();
        if (empty($info) || !isset($articleStatus[$info["article_status"]])) {
            return $this->jsonResult(109, "无需推送消息！", "");
        }
        $thirdUser = ThirdUser::findOne(["uid" => $info["user_id"]]);
        $remarks = [
            "3" => "恭喜！你的文章已上线！",
            "4" => "请重新修改之后,提交审核!",
            "5" => ("理由：" . $info["remark"])
        ];
        $titles = [
            "3" => "你的文章已上线！",
            "4" => "对不起，你的文章被下线!",
            "5" => "你的文章审核未通过！"
        ];
        if ($thirdUser->third_uid) {
            $wechatTool = new WechatTool();
            $order = (new \yii\db\Query())->select("user_article_code")->from("user_article")->where(["article_id" => $get["expert_articles_id"]])->one();
            $wechatTool->sendtemplateMsgArticleReview($titles[$info["article_status"]], $thirdUser->third_uid, $info["article_title"], $info["review_time"], $articleStatus[$info["article_status"]], $remarks[$info["article_status"]], $order['user_article_code']);
        }
        return $this->jsonResult(600, "完成微信推送！", "");
    }

    /**
     * 发放活动奖金通知
     */
    public function actionSendCampaignBonusMsg() {
        $get = \Yii::$app->request->get();
        $payRecord = PayRecord::findOne(["order_code" => $get["order_code"], "pay_type" => 20]);
        if (empty($payRecord)) {
            return $this->jsonResult(109, "无需推送消息！", "");
        }
        $user = User::findOne(["cust_no" => $payRecord->cust_no]);
        $thirdUser = ThirdUser::findOne(["uid" => $user->user_id]);
        if ($thirdUser->third_uid) {
            $wechatTool = new WechatTool();
            $wechatTool->sendtemplateMsgCampaignBonus("你收到一笔活动奖金", $thirdUser->third_uid, $payRecord->pay_money, $payRecord->pay_time, "活动奖金已经打入你的可用余额，赶紧去下载咕啦体育APP中查看！", "", $get["order_code"]);
        }
        return $this->jsonResult(600, "完成微信推送！", "");
    }

    /**
     * 说明: 数字彩手动对奖
     * @author  kevi
     * @date 2017年11月30日 上午9:29:23
     * @param
     * @return 
     */
    public function actionCheckLottery() {
        $request = \Yii::$app->request;
        $lottery_code = $request->get('lottery_code');
        $periods = $request->get('periods');
        $openNumber = $request->get('openNumber');
        echo $lottery_code . ' ' . $periods . ' ' . $openNumber;
        die;
        $winHelper = new Winning();
        switch ($lottery_code) {
            case 1001:
                $winHelper->lottery1001Level($periods, $openNumber);
                break;
            case 1002:
                $winHelper->lottery1002Level($periods, $openNumber);
                break;
            case 1003:
                $winHelper->lottery1003Level($periods, $openNumber);
                break;
            case 2001:
                $winHelper->lottery2001Level($periods, $openNumber);
                break;
            case 2002:
                $winHelper->lottery2002Level($periods, $openNumber);
                break;
            case 2003:
                $winHelper->lottery2003Level($periods, $openNumber);
                break;
            case 2004:
                $winHelper->lottery2004Level($periods, $openNumber);
                break;
            default:
        }

        $ret = $winHelper->lottery2001Level($periods, $openNumber);
        $this->jsonResult(600, '成功执行' . $ret['UpdateRowCount'] . '条记录', 'succ');
    }

    /**
     * 重置门店自动出票机
     * @return type
     */
    public function actionResetOutTicket() {
        $week = date('w');
        if ($week == 0 || $week == 6) {
            $ticketNums = \Yii::$app->params['sysWOutTicket'];
        } else {
            $ticketNums = \Yii::$app->params['sysOutTicket'];
        }
        $update = ['mod_nums' => new Expression("pre_out_nums"), 'modify_time' => date('Y-m-d H:i:s')];
        $where = ['type' => 2];
        $data = TicketDispenser::updateAll($update, $where);
        if ($data === false) {
            return $this->jsonError(109, "更新失败！");
        }
        return $this->jsonResult(600, "更新成功！", true);
        \Yii::redisSet('cron:ResetOutTicket', date('Y-m-d H:i:s'));
    }

    /**
     * 5分钟 定时获取取消赛程相关信息加入队列
     */
    public function actionGetCancelSchedule() {
        $redis = \Yii::$app->redis;
        $key = 'cancel_schedule';
        $scheData = $redis->SMEMBERS($key);
        foreach ($scheData as $val) {
            $valArr = explode('_', $val);
            $mid = $valArr[1];
            $code = $valArr[0];
            KafkaService::addQue('CancelScheduleAward', ['mid' => $mid, 'code' => $code], true);
        }
    }
    
    public function actionResetUserActive() {
        UserCoinHelper::resetUserActive();
        return $this->jsonResult(600, '更新成功', true);
    }
    
    /**
     * 确认出票
     * @return type
     */
    public function actionConfirmOutTicket(){
        $orderList = LotteryOrder::find()->select(['lottery_order_code'])->where(['status' => 2, 'auto_type' => 2])->asArray()->all();
        foreach ($orderList as $val) {
            KafkaService::addQue('ConfirmOutTicket', ['orderCode' => $val['lottery_order_code']], true);
        }
        return $this->jsonResult(600, '更新成功', true);
    }
    
    /**
     * 订单轮循门店
     * @return type
     */
    public function actionOrderPollingStore() {
        $now = date('Y-m-d H:i:s', strtotime('-10 minute'));
        $orderData = LotteryOrder::find()->select(['lottery_order_code'])
                ->innerJoin('store s', 's.store_code = lottery_order.store_no')
                ->where(['lottery_order.status' => 2, 'lottery_order.deal_status' => 0, 'lottery_order.auto_type' => 1, 'lottery_order.suborder_status' => 1, 's.company_id' => 1])
                ->andWhere(['!=', 'lottery_order.lottery_type', 1])
                ->andWhere(['<', 'lottery_order.create_time', $now])
                ->asArray()
                ->all();
        foreach ($orderData as $val) {
            KafkaService::addQue('OrderPollingStore', ['orderCode' => $val['lottery_order_code']], true);
        }
        return $this->jsonResult(600, '更新成功', true);
    }

}
