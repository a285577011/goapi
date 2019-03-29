<?php

namespace app\modules\user\models;

use app\modules\common\services\KafkaService;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class WechatModel {

    /**
     * 说明: 获取公众号消息模板-投注消息模板-门店
     * @author  kevi
     * @date 2017年8月14日 下午3:50:11
     * @param   not null    $userOpenId  用户openId
     * @param   not null    $orderId  订单编号(WX02302301)
     * @param   not null    $userName    用户昵称(达芙妮女士（13696995112）)
     * @param   not null    $orderMoney    订单金额(10元)
     * @param   not null    $orderTitle   订单标题(双色球2013023期)
     * @param   not null    $orderLimitTime   订单截止时间(2017-08-15 21:30:00)
     * @param   not null    $remark 备注(祝您中大奖！)
     * @return
     */
    public function templateMsgBetStore($title, $userOpenId, $orderId, $userName, $orderMoney, $orderTitle, $orderLimitTime, $remark) {
//         $tplId = 'J_GNq3_JnTC-RVjWInqaxa83zrQaPh2syIi2Jdq7SFg';
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['new_order'];
        $color = '#34A4F0';
        $data = [
            'first' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'keyword1' => [
                'value' => $orderId,
                'color' => $color
            ], 'keyword2' => [
                'value' => $userName,
                'color' => $color
            ], 'keyword3' => [
                'value' => $orderMoney,
                'color' => $color
            ], 'keyword4' => [
                'value' => $orderTitle,
                'color' => $color
            ], 'keyword5' => [
                'value' => $orderLimitTime,
                'color' => $color
            ], 'remark' => [
                'value' => $remark,
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
        ];

        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 说明: 获取公众号消息模板-系统警报-开发人员
     * @author  ctx
     * @date 2017年9月26日 下午3:50:11
     * @param   not null    $title  标题
     * @param   not null    $userOpenId  用户openId
     * @param   not null    $level  错误级别
     * @param   not null    $errorInfo    错误信息
     * @param   not null    $serverName    主机名 正式服务器 测试服务器
     * @param   not null    $time   时间
     * @param   not null    $status   错误状态
     * @param   not null    $remark 备注
     * @return
     */
    public function templateMsgSysAlert($title, $userOpenId, $level, $errorInfo, $serverName, $time, $status, $remark) {
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['sys_error'];
        $color = '#34A4F0';
        $data = [
            'first' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'keyword1' => [
                'value' => $level,
                'color' => $color
            ], 'keyword2' => [
                'value' => $errorInfo,
                'color' => $color
            ], 'keyword3' => [
                'value' => $serverName,
                'color' => $color
            ], 'keyword4' => [
                'value' => $time,
                'color' => $color
            ], 'keyword5' => [
                'value' => $status,
                'color' => $color
            ], 'remark' => [
                'value' => $remark,
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
            "url" => \Yii::$app->params["userDomain"] . "/api/storeback/piaowu/problem-list?page=2&per-page=10",
        ];

        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 说明： 获取公众号消息模板--订单出票消息模板--会员
     * @auther  zyl
     * @date 2017年9月20日 下午2:05:23
     * @param not null  $title  消息标题
     * @param not null $userOpenId 用户openId
     * @param not null  $lotteryMsg  彩种信息
     * @param not null $betTime 投注时间
     * @param not null $betMoney 投注金额
     * @param not null $resultTime 开奖时间
     * @param not null $remark 备注
     * @return json
     */
    public function templateMsgOutUser($title, $userOpenId, $lotterMsg, $betTime, $betMoney, $resultTime, $remark, $url) {
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['out_ticket'];
        $color = '#34A4F0';
        $data = [
            'result' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'issueInfo' => [
                'value' => $lotterMsg,
                'color' => $color
            ], 'betTime' => [
                'value' => $betTime,
                'color' => $color
            ], 'fee' => [
                'value' => $betMoney,
                'color' => $color
            ], 'drawTime' => [
                'value' => $resultTime,
                'color' => $color
            ], 'remark' => [
                'value' => $remark,
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
            "url" => $url
        ];

        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 说明： 获取公众号消息模板--充值成功消息模板--会员
     * @auther  zyl
     * @date 2017年9月21日 下午
     * @param not null  $title  消息标题
     * @param not null $userOpenId 用户openId
     * @param not null  $czMoney  充值金额
     * @param not null $czTime 充值时间
     * @param not null $userFunds 充值余额
     * @return json
     */
    public function templateMsgRechargeUse($title, $userOpenId, $czMoney, $czTime, $userFunds) {
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['recharge_msg'];
        $color = '#34A4F0';
        $data = [
            'first' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'keyword1' => [
                'value' => $czMoney,
                'color' => $color
            ], 'keyword2' => [
                'value' => $czTime,
                'color' => $color
            ], 'keyword3' => [
                'value' => $userFunds,
                'color' => $color
            ], 'remark' => [
                'value' => '充值成功，祝您有个愉快的购彩体验！',
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
        ];

        $postData = json_encode($postData);
        return $postData;
    }


    /**
     * 说明： 获取公众号消息模板--充值申请模板--流量单
     * @auther  zyl
     * @date 2017年9月21日 下午
     * @param not null  $title  消息标题
     * @param not null $userOpenId 用户openId
     * @param not null  $czMoney  充值金额
     * @param not null $czTime 充值时间
     * @param not null $userFunds 充值余额
     * @return json
     */
    public function templateMsgRechargeApiUser($title, $userOpenId, $name ,$cust_no,$czMoney, $czTime){
        $color = '#34A4F0';
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['recharge_msg_apiuer'];
        $data = [
            'first' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'keyword1' => [//申请名称
                'value' => $name,
                'color' => $color
            ], 'keyword2' => [//申请人
                'value' => $cust_no,
                'color' => $color
            ], 'keyword3' => [//申请类型
                'value' => $czMoney,
                'color' => $color
            ], 'keyword4' => [//申请时间
                'value' => $czTime,
                'color' => $color
            ], 'remark' => [
                'value' => '请及时登录后台系统审核，谢谢！',
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
        ];
        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 说明： 获取公众号消息模板--合买撤单消息模板--会员
     * @auther  zyl
     * @date 2017年9月21日 下午
     * @param not null $userOpenId 用户openId
     * @param not null  $expertName  发起人
     * @param not null  $withMoney  跟单金额
     * @param not null $programmeCode 方案编号
     * @param not null $betTime 投注时间
     * @return json
     */
    public function templateMsgProgrammeUse($userOpenId, $expertName, $withMoney, $programmeCode, $betTime, $type = '') {
        if($type == 'cancel') {
            $title = '很抱歉！您参与的合买方案因投注赛程取消故撤单！';
        }  else {
            $title = '很抱歉！您参与的合买方案因未达到指定人数撤单！';
        }
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['programme_cancel'];
        $color = '#34A4F0';
        $data = [
            'first' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'keyword1' => [
                'value' => $expertName,
                'color' => $color
            ], 'keyword2' => [
                'value' => $withMoney,
                'color' => $color
            ], 'keyword3' => [
                'value' => $programmeCode,
                'color' => $color
            ], 'keyword4' => [
                'value' => $betTime,
                'color' => $color
            ], 'remark' => [
                'value' => '退款将退回至原支付账户，请您放心！',
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
        ];

        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 说明： 获取公众号消息模板--开奖结果消息模板--会员
     * @auther  zyl
     * @date 2017年9月21日 下午
     * @param not null $title 标题
     * @param not null $userOpenId 用户openId
     * @param not null  $resultMsg  开奖状态
     * @param not null  $betMsg  彩种期次
     * @param not null $betMoney 投注金额
     * @param not null $betTime 投注时间
     * @return json
     */
    public function templateMsgAwards($title, $userOpenId, $resultMsg, $betMsg, $betMoney, $betTime, $remark) {
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['award_order'];
        $color = '#34A4F0';
        $data = [
            'result' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'totalWinMoney' => [
                'value' => $resultMsg,
                'color' => $color
            ], 'issueInfo' => [
                'value' => $betMsg,
                'color' => $color
            ], 'fee' => [
                'value' => $betMoney,
                'color' => $color
            ], 'betTime' => [
                'value' => $betTime,
                'color' => $color
            ], 'remark' => [
                'value' => $remark,
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
        ];

        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 说明： 获取公众号消息模板--文章审核情况通知--专家
     * @auther  ctx
     * @date 2017年10月18日 下午
     * @param not null $title 标题
     * @param not null $userOpenId 用户openId
     * @param not null  $articleTitle  文章标题
     * @param not null  $reviewTime  审核时间
     * @param not null $articleStatus 审核状态
     * @param not null $remark 备注
     * @return json
     */
    public function templateMsgArticleReview($title, $userOpenId, $articleTitle, $reviewTime, $articleStatus, $remark) {
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['article_review'];
        $color = '#34A4F0';
        $data = [
            'first' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'keyword1' => [
                'value' => $articleTitle,
                'color' => $color
            ], 'keyword2' => [
                'value' => $reviewTime,
                'color' => $color
            ], 'keyword3' => [
                'value' => $articleStatus,
                'color' => $color
            ], 'remark' => [
                'value' => $remark,
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
        ];

        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 说明： 获取公众号消息模板--发放活动奖金通知--专家
     * @auther  ctx
     * @date 2017年10月19日
     * @param not null $title 标题
     * @param not null $userOpenId 用户openId
     * @param not null  $money  到账金额
     * @param not null  $time  到账时间
     * @param not null $detail 到账详情
     * @param not null $remark 备注
     * @return json
     */
    public function templateMsgCampaignBonus($title, $userOpenId, $money, $time, $detail, $remark) {
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['campaign_bonus'];
        $color = '#34A4F0';
        $data = [
            'first' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'keyword1' => [
                'value' => $money,
                'color' => $color
            ], 'keyword2' => [
                'value' => $time,
                'color' => $color
            ], 'keyword3' => [
                'value' => $detail,
                'color' => $color
            ], 'remark' => [
                'value' => $remark,
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
        ];

        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 说明： 通知门店未处理的派奖
     * @auther  gwp
     * @date 2017年12月06日
     * @param not null $title 标题
     * @param not null $userOpenId 用户openId
     * @param not null  $type 类型
     * @param not null  $num  数量
     * @param not null $time  时间点
     * @param not null $remark 备注
     * @return json
     */
    public function templateMsgUntreatedBonus($title, $userOpenId, $type, $num, $money, $time, $remark){
        $tplId = \Yii::$app->params['wechat_sms_tpl_id']['untreated_bonus'];
        $color = '#34A4F0';
        $data = [
            'first' => [
                'value' => $title,
                'color' => '#34A4F0'
            ], 'keyword1' => [
                'value' => $type,
                'color' => $color
            ], 'keyword2' => [
                'value' => $num,
                'color' => $color
            ], 'keyword3' => [
                'value' => $money,
                'color' => $color
            ], 'keyword4' => [
                'value' => $time,
                'color' => $color
            ], 'remark' => [
                'value' => $remark,
                'color' => $color
            ],
        ];
        $postData = [
            "touser" => $userOpenId,
            "template_id" => $tplId,
            "data" => $data,
        ];
        $postData = json_encode($postData);
        return $postData;
    }

    /**
     * 写入追号队列
     * @param array $data  存储微信模版消息数据
     * @param string $user_open_id  微信openid
     * @param int $type  模版消息类型：1=门店，2=订单出票，3=充值成功，4=合买未满元，5=开奖结果，6=文章审核，7=发放活动奖金，
     * @param int $status 发送状态 1=成功，2=失败
     * @param string $order_code 订单编码
     */
    public function addWxMsgRecord(array $data, $user_open_id, $type=0, $status, $order_code=''){
        $data = json_encode($data);
        //$lotteryqueue = new \LotteryQueue();
        $param = [
            'data' => $data,
            'user_open_id' => $user_open_id,
            'type' => $type,
            'status'=>$status,
            'order_code'=>$order_code
        ];
        KafkaService::addQue('WxMsgRecord', $param,false);
        //$lotteryqueue->pushQueue('wx_msg_record_job', 'wxMsgRecord', $param);
    }


}
