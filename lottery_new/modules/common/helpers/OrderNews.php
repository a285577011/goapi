<?php

namespace app\modules\common\helpers;

use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Constants;
use app\modules\user\models\User;
use app\modules\user\helpers\WechatTool;
use app\modules\common\models\StoreOperator;
use app\modules\user\models\ThirdUser;
use app\modules\competing\helpers\CompetConst;
use app\modules\common\services\KafkaService;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class OrderNews {

    /**
     * 中奖信息微信推送
     * @return type
     */
    public static function sendWechatMsg() {
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $time = date('Y-m-d', strtotime('-3 day', time()));
        $orderData = LotteryOrder::find()->select(['lottery_order_id', 'third_user.third_uid', 'lottery_id', 'lottery_name', 'periods', 'user.user_id', 'lottery_order.cust_no', 'lottery_order.status', 'win_amount', 'bet_money', 'lottery_order.create_time'])
                        ->leftJoin('user', 'user.cust_no = lottery_order.cust_no')
                        ->leftJoin('third_user', 'third_user.uid = user.user_id')
                        ->where(['send_status' => 0])
                        ->andWhere('third_user.third_uid  is not null')
                        ->andWhere(['in', 'lottery_order.deal_status', [1, 2, 3]])
                        ->andWhere(['in', 'lottery_order.status', [4, 5]])
                        ->andWhere(['>=', 'end_time', $time])
                        ->limit(5000)
                        ->asArray()->all();
        if (empty($orderData)) {
            return ['code' => 600, 'msg' => '暂无订单需推送'];
        }
        $all = [];
        foreach ($orderData as $val) {
            if (empty($val['third_uid'])) {//如果未绑定微信 则跳过
                $all[] = $val['lottery_order_id'];
                continue;
            }
            if ($val['status'] == 4) {
                $title = '恭喜您，中奖啦！';
                $resultMsg = '中奖 ' . $val['win_amount'] . ' 元';
                $remark = '奖金稍后发至您的账户余额内，请注意查收！';
            } else {
                $title = '未中奖，但是支持了公益事业，祝您下次中奖！';
                $resultMsg = '未中奖';
                $remark = '买彩票也是支持公益，坚持投注并分析，会中奖哦！';
            }
            if (in_array($val['lottery_id'], $football)) {
                $betMsg = '竞彩足球';
            } else {
                $betMsg = $val['lottery_name'] . $val['periods'] . ' 期';
            }

            $data = [
                'openId' => $val['third_uid'],
                'orderId' => $val['lottery_order_id'],
                'title' => $title,
                'resultMsg' => $resultMsg,
                'betMsg' => $betMsg,
                'betMoney' => $val['bet_money'],
                'betTime' => $val['create_time'],
                'remark' => $remark,
            ];
            KafkaService::addQue('WinOrder', $data, true);
            //$lotteryqueue = new \LotteryQueue(); //定义中奖消息队列名称并加入队列
            //$ret = $lotteryqueue->pushQueue('win_order_job', 'win_order_msg_push', $data);
        }
        LotteryOrder::updateAll(['send_status' => 2], ['in', 'lottery_order_id', $all]);
        return ['code' => 600, 'msg' => '推送成功'];
    }

    /**
     * 出票微信通知
     * @param type $userId   
     * @param type $orderCode
     * @param type $betMoney
     * @param type $endTime
     * @param type $lotteryCode
     * @param type $periods
     */
    public static function outOrderNotice($storeId, $storeNo, $userCustNo, $orderCode, $betMoney, $endTime, $lotteryCode, $periods = "") {
        $lotterys = Constants::LOTTERY;
        $footBalls = Constants::MADE_FOOTBALL_LOTTERY;
        $basketballs = CompetConst::MADE_BASKETBALL_LOTTERY;
        $user = User::findOne(["cust_no" => $userCustNo]);
        if ($user == null) {
            return false;
        }
        $thirdUser = ThirdUser::findOne(["uid" => $storeId, "uid_source" => 1]);
        $remark = "";
        $title = '您好，您收到了一个新订单，请尽快出票处理';
        if (in_array($lotteryCode, $footBalls)) {
            $orderTitle = "竞足" . $lotterys[$lotteryCode];
        } elseif (in_array($lotteryCode, $basketballs)) {
            $orderTitle = "竞篮" . $lotterys[$lotteryCode];
        } else {
            $orderTitle = $lotterys[$lotteryCode] . " 第" . $periods . "期";
        }
        $wechatTool = new WechatTool();
        $operators = StoreOperator::find()->select(['user_id', 'third_uid'])
                        ->leftJoin('third_user', 'store_operator.user_id = third_user.uid')
                        ->where(['store_id' => $storeNo, 'status' => 1])->asArray()->all();
        $operatorThirdUser = ThirdUser::find()->select(['third_uid'])->where(['uid' => $storeId])->asArray()->one();
        try {
            if (!empty($thirdUser) && !empty($thirdUser->third_uid)) {
                $wechatTool->sendTemplateMsgBetStore($title, $operatorThirdUser['third_uid'], $orderCode, $user->user_name . "({$user->user_tel})", $betMoney . "元", $orderTitle, $endTime, $remark);
            }
            foreach ($operators as $operator) {
                if (!empty($operator['third_uid'])) {
                    $wechatTool->sendTemplateMsgBetStore($title, $operator['third_uid'], $orderCode, $user->user_name . "({$user->user_tel})", $betMoney . "元", $orderTitle, $endTime, $remark);
                }
            }
        } catch (\Exception $e) {
            \Yii::redisSet('error:wx_sms_betstore', $e);
        }
    }

    /**
     * 出票成功，微信推送
     * @param type $orderCode
     * @param type $type
     * @return boolean
     */
    public static function userOutOrder($orderCode, $type) {
        $field = ['lottery_order.lottery_order_code', 'lottery_order.lottery_id', 'lottery_order.lottery_name', 'lottery_order.create_time', 'lottery_order.bet_money', 'lottery_order.bet_val',
            'lottery_order.periods', 't.third_uid', 'lr.lottery_time', 'lr.week', 's.store_name', 's.telephone as phone_num', 'lottery_order.lottery_order_id'];
        $userData = LotteryOrder::find()->select($field)
                ->leftJoin('third_user t', 't.uid = lottery_order.user_id')
                ->leftJoin('lottery_record as lr', 'lr.lottery_code = lottery_order.lottery_id and lr.periods = lottery_order.periods')
                ->leftJoin('store as s', 's.store_code = lottery_order.store_no and s.status = 1')
                ->where(['lottery_order_code' => $orderCode])
                ->asArray()
                ->one();
        $footballs = Constants::MADE_FOOTBALL_LOTTERY;
        $nums = Constants::MADE_NUMS_LOTTERY;
        $optional = Constants::MADE_OPTIONAL_LOTTERY;
        $basketballs = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdMade = CompetConst::MADE_BD_LOTTERY;
        $wcMade = CompetConst::MADE_WCUP_LOTTERY;
        $userOpenId = $userData['third_uid'];
        if(empty($userOpenId)) {
            return false;
        }
        if (in_array($userData['lottery_id'], $footballs)) {
            $lotteryMsg = '竞彩足球';
            $url = \Yii::$app->params["wxjc_order_detail"] . "{$userData['lottery_id']}/{$orderCode}_{$userData['lottery_order_id']}";
        } elseif (in_array($userData['lottery_id'], $basketballs)) {
            $lotteryMsg = '竞彩篮球';
            $url = \Yii::$app->params["wxjc_order_detail"] . "{$userData['lottery_id']}/{$orderCode}_{$userData['lottery_order_id']}";
        } elseif (in_array($userData['lottery_id'], $bdMade)) {
            $lotteryMsg = '北京单场';
            $url = \Yii::$app->params["wxjc_order_detail"] . "{$userData['lottery_id']}/{$orderCode}_{$userData['lottery_order_id']}";
        } elseif (in_array($userData['lottery_id'], $optional)) {
            $lotteryMsg = $userData['lottery_name'] . $userData['periods'] . '期';
            $url = \Yii::$app->params["wxjc_order_detail"] . "{$userData['lottery_id']}/{$orderCode}_{$userData['lottery_order_id']}";
        } elseif (in_array($userData['lottery_id'], $wcMade)) {
            $lotteryMsg = '竞彩冠亚军';
            $url = \Yii::$app->params["wxjc_order_detail"] . "{$userData['lottery_id']}/{$orderCode}_{$userData['lottery_order_id']}";
        } else {
            $lotteryMsg = $userData['lottery_name'] . $userData['periods'] . '期';
            $url = \Yii::$app->params["wxnum_order_detail"] . "{$orderCode}_{$userData['lottery_order_id']}";
        }
        if (in_array($userData['lottery_id'], $nums)) {
            $resultTime = $userData['lottery_time'] . '  ' . $userData['week'];
        } else {
            $resultTime = '视比赛时间而定';
        }
        $betMoney = $userData['bet_money'];
        $betTime = $userData['create_time'];

        if ($type == 1) {
            $title = '投注成功，祝您中大奖！';
            $remark = "订单编号:" . $orderCode . "\n出票门店:" . $userData['store_name'] . "(" . $userData['phone_num'] . ")\n\n福体竞，三彩齐上阵，大奖等您赢！";
        } else {
            $title = '很抱歉！您购买的彩种出票失败！';
            $remark = "出票门店:" . $userData['store_name'] . "(" . $userData['phone_num'] . ")\n\n退款将退回至原支付账户，请您放心！";
        }
        if ($userOpenId) {
            $wechatTool = new WechatTool();
            $wechatTool->sendTemplateMsgOutTicketUse($title, $userOpenId, $lotteryMsg, $betTime, $betMoney, $resultTime, $remark, $url, $orderCode);
        }
        return true;
    }

}
