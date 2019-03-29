<?php

namespace app\modules\user\helpers;

use app\modules\user\models\WechatModel;

class WechatTool {

    /**
     * 说明: 生成随机字符串
     * @author  kevi
     * @date 2017年7月31日 下午2:07:58
     * @param
     * @return
     */
    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 说明:
     * @author  kevi
     * @date 2017年8月8日 下午1:49:57
     * @param
     * @return
     */
    public function getOauthAccessToken($appId, $appSecret, $code) {
        $request_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId . '&secret=' . $appSecret . '&code=' . $code . '&grant_type=authorization_code';
        //初始化一个curl会话
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 说明: 获取签名
     * @author  kevi
     * @date 2017年8月8日 上午9:54:05
     * @param
     * @return
     */
    public function getSignPackage($url) {
        $jsapiTicket = $this->getJsApiTicket();
        // 注意 URL 一定要动态获取，不能 hardcode.
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);

        $signPackage = array(
            "appId" => \Yii::$app->params['wechat']['appid'],
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    /**
     * 说明: 全局存储与更新jsapiticket
     * @author  kevi
     * @date 2017年8月8日 下午1:49:57
     * @param
     * @return
     */
    public function getJsApiTicket() {
        $jsapiTicket = \Yii::redisGet('wxgzh_jsapiticket');
        if (empty($jsapiTicket)) {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            $jsapiTicket = $res->ticket;

            if ($jsapiTicket) {
                \Yii::redisSet('wxgzh_jsapiticket', $jsapiTicket, 7000);
            }
        }
        return $jsapiTicket;
    }

    /**
     * 说明: 全局存储与更新access_token(基础token)
     * @author  kevi
     * @date 2017年8月8日 下午1:49:57
     * @param
     * @return
     */
    public function getAccessToken() {
//        $appId = \Yii::$app->params['wechat']['appid'];
//        $appSecret = \Yii::$app->params['wechat']['appsecret'];

        $access_token = \Yii::redisGet('wxgzh_token');
//        if (empty($access_token)) {
//            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
////             $res = json_decode($this->httpGet($url));
//
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            $result = curl_exec($ch);
//            curl_close($ch);
//            $result = json_decode($result, true);
//
//            $access_token = $result['access_token'];
//            if ($access_token) {
//                \Yii::redisSet('wxgzh_token', $access_token, 6000);
//            }
//        }
        return $access_token;
    }

    /**
     * 说明: 微信授权 获取用户信息
     * @author  kevi
     * @date 2017年8月10日 上午9:13:11
     * @param access_token not null
     * @param openId not null
     * @return  array
     */
    public function getUserInfo($access_token, $openId) {
        $request_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openId . '&lang=zh_CN';
        //初始化一个curl会话
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }

    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    /**
     * 说明: 获取公众号消息模板-投注消息模板-用户
     * @author  kevi
     * @date 2017年8月14日 下午3:50:11
     * @param   not null    $touser  用户openId
     * @param   not null    $issueInfo  彩种-期数(双色球2017194期)
     * @param   not null    $betTime    投注时间(2017-08-14 09:23:30)
     * @param   not null    $fee    金额(10元)
     * @param   not null    $drawTime   开奖时间(2017-08-15 21:30:00)
     * @param   not null    $remark 备注(祝您中大奖！)
     * @return
     */
    public function sendTemplateMsgBetUser($toUser, $issueInfo, $betTime, $fee, $drawTime, $remark) {
        $access_token = $this->getAccessToken();
        $surl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $data = [
            'result' => [
                'value' => '恭喜,投注成功！',
                'color' => '#FF0000'
            ],
            'issueInfo' => [
                'value' => $issueInfo,
                'color' => '#FF0000'
            ],
            'betTime' => [
                'value' => $betTime,
                'color' => '#FF0000'
            ],
            'fee' => [
                'value' => $fee,
                'color' => '#FF0000'
            ],
            'drawTime' => [
                'value' => $drawTime,
                'color' => '#FF0000'
            ],
            'remark' => [
                'value' => $remark,
                'color' => '#FF0000'
            ],
        ];
        $postData = [
            "touser" => $toUser,
            "template_id" => "pYSkL4s39ZOJAHKqC5byVFvakljas5zrrZ7BTmLerAA",
            "data" => $data,
        ];

        $postData = json_encode($postData);
        $ret = \Yii::sendCurlPost($surl, $postData);
        return $ret;
    }

    /**
     * 说明: 获取公众号消息模板-投注消息模板-门店
     * @author  kevi
     * @date 2017年8月14日 下午3:50:11
     * @param   not null    $userOpenId  用户openId
     * @param   not null    $order_code  订单编号(WX02302301)
     * @param   not null    $userName    用户昵称(达芙妮女士（13696995112）)
     * @param   not null    $orderMoney    订单金额(10元)
     * @param   not null    $orderTitle   订单标题(双色球2013023期)
     * @param   not null    $orderLimitTime   订单截止时间(2017-08-15 21:30:00)
     * @param   not null    $remark 备注(祝您中大奖！)
     * @return
     */
    public function sendTemplateMsgBetStore($title, $userOpenId, $order_code, $userName, $orderMoney, $orderTitle, $orderLimitTime, $remark) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgBetStore($title, $userOpenId, $order_code, $userName, $orderMoney, $orderTitle, $orderLimitTime, $remark);
        //发送消息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息
        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'orderId' => $order_code,
            'userName' => $userName,
            'orderMoney' => $orderMoney,
            'orderTitle' => $orderTitle,
            'orderLimitTime' => $orderLimitTime,
            'remark' => $remark,
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 1, $status, $order_code);
        return $ret;
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
     * @param not null $order_code 订单编号
     * @return type
     */
    public function sendTemplateMsgOutTicketUse($title, $userOpenId, $lotteryMsg, $betTime, $betMoney, $resultTime, $remark, $url, $order_code) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgOutUser($title, $userOpenId, $lotteryMsg, $betTime, $betMoney, $resultTime, $remark, $url);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息
        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'orderId' => $order_code,
            'lotteryMsg' => $lotteryMsg,
            'betTime' => $betTime,
            'betMoney' => $betMoney,
            'resultTime' => $resultTime,
            'remark' => $remark,
            'url' => $url,
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 2, $status, $order_code);
        return $ret;
    }

    /**
     * 说明： 获取公众号消息模板--系统警报--开发人员
     * @auther  ctx
     * @date 2017年9月20日 下午2:05:23
     * @param   not null    $title  标题
     * @param   not null    $userOpenId  用户openId
     * @param   not null    $level  错误级别
     * @param   not null    $errorInfo    错误信息
     * @param   not null    $serverName    主机名 正式服务器 测试服务器
     * @param   not null    $time   时间
     * @param   not null    $status   错误状态
     * @param   not null    $remark 备注
     * @return type
     */
    public function sendTemplateMsgSysAlert($title, $userOpenId, $level, $errorInfo, $serverName, $time, $status, $remark) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgSysAlert($title, $userOpenId, $level, $errorInfo, $serverName, $time, $status, $remark);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        return $ret;
    }

//     public function sendTemplateMsgSysError($userOpenId, $orderId, $userName, $orderMoney, $orderTitle,$orderLimitTime, $remark){
//         $tplUrl = $this->getSendTemplateUrl();
//         $color = '#34A4F0';
//         $data = [
//             'first' => [
//                 'value' => '您好，您收到了一个新订单，请尽快出票处理!',
//                 'color' => '#34A4F0'
//             ],'keyword1' => [
//                 'value' => $orderId,
//                 'color' => $color
//             ],'keyword2' => [
//                 'value' => $userName,
//                 'color' => $color
//             ],'keyword3' => [
//                 'value' => $orderMoney,
//                 'color' => $color
//             ],'keyword4' => [
//                 'value' => $orderTitle,
//                 'color' => $color
//             ],'keyword5' => [
//                 'value' => $orderLimitTime,
//                 'color' => $color
//             ],'remark' => [
//                 'value' => $remark,
//                 'color' => $color
//             ],
//         ];
//         $postData = json_encode($postData);
//         $ret = \Yii::sendCurlPost($tplUrl, $postData);
//         return $ret;
//     }

    /**
     * 说明: 获取微信模板消息发送url
     * @author  kevi
     * @date 2017年9月20日 上午10:04:36
     * @param
     * @return string url
     */
    public function getSendTemplateUrl() {
        $access_token = $this->getAccessToken();
        $Tplurl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        return $Tplurl;
    }

    /**
     * 说明： 获取公众号消息模板--充值成功消息模板--会员
     * @auther  zyl
     * @date 2017年9月21日 下午
     * @param not null  $title  消息标题
     * @param not null $userOpenId 用户openId
     * @param not null  $czMoney  充值金额
     * @param not null $czTime 充值时间
     * @param not null $userFunds 现有余额
     * @return type
     */
    public function sendTemplateMsgRechargeUse($title, $userOpenId, $czMoney, $czTime, $userFunds, $order_code) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgRechargeUse($title, $userOpenId, $czMoney, $czTime, $userFunds);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息
        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'orderId' => $order_code,
            'czMoney' => $czMoney,
            'czTime' => $czTime,
            'userFunds' => $userFunds,
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 3, $status, $order_code);
        return $ret;
    }

    /**
     * 说明： 获取公众号消息模板--合买未满元消息模板--会员
     * @auther  zyl
     * @date 2017年9月21日 下午
     * @param not null $userOpenId 用户openId
     * @param not null  $expertName  发起人
     * @param not null $withMoney 投注金额
     * @param not null $programmeCode 方案编号
     * @param not null  $betTime 投注时间
     * @return type
     */
    public function sendTemplateMsgProgrammeUse($userOpenId, $expertName, $withMoney, $programmeCode, $betTime, $order_code, $type = '') {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgProgrammeUse($userOpenId, $expertName, $withMoney, $programmeCode, $betTime, $type);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息

        if ($type == 'cancel') {
            $title = '很抱歉！您参与的合买方案因投注赛程取消故撤单！';
        } elseif ($type == 'outFalse') {
            $title = '很抱歉！您参与的合买方案因门店拒绝出票故撤单！';
        }elseif ($type == 'outTime') {
            $title = '很抱歉！您参与的合买方案因出票时间不足故撤单！';
        } elseif ($type == 'falseCon') {
            $title = '很抱歉！您参与的合买方案因投注内容有误故撤单！';
        }elseif ($type == 'falsePlay') {
            $title = '很抱歉！您参与的合买方案因下单错误故撤单！';
        }elseif ($type == 'NoCon') {
            $title = '很抱歉！您参与的合买方案因未提交方案内容故撤单！';
        }else {
            $title = '很抱歉！您参与的合买方案因未达到指定人数撤单！';
        }

        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'orderId' => $order_code,
            'expertName' => $expertName,
            'withMoney' => $withMoney,
            'programmeCode' => $programmeCode,
            'betTime' => $betTime,
            'remark' => '退款将退回至原支付账户，请您放心！',
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 4, $status, $order_code);
        return $ret;
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
     * @return type
     */
    public function sendTemplateMsgAwards($title, $userOpenId, $resultMsg, $betMsg, $betMoney, $betTime, $remark, $order_code) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgAwards($title, $userOpenId, $resultMsg, $betMsg, $betMoney, $betTime, $remark);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息
        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'orderId' => $order_code,
            'resultMsg' => $resultMsg,
            'betMsg' => $betMsg,
            'betMoney' => $betMoney,
            'betTime' => $betTime,
            'remark' => $remark,
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 5, $status, $order_code);
        return $ret;
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
    public function sendtemplateMsgArticleReview($title, $userOpenId, $articleTitle, $reviewTime, $articleStatus, $remark, $order_code) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgArticleReview($title, $userOpenId, $articleTitle, $reviewTime, $articleStatus, $remark);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息
        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'orderId' => $order_code,
            'articleTitle' => $articleTitle,
            'reviewTime' => $reviewTime,
            'articleStatus' => $articleStatus,
            'remark' => $remark,
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 6, $status, $order_code);
        return $ret;
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
    public function sendtemplateMsgCampaignBonus($title, $userOpenId, $money, $time, $detail, $remark, $order_code) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgCampaignBonus($title, $userOpenId, $money, $time, $detail, $remark);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息
        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'orderId' => $order_code,
            'money' => $money,
            'time' => $time,
            'detail' => $detail,
            'remark' => $remark,
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 7, $status, $order_code);
        return $ret;
    }

    /**
     * 说明： 通知门店未处理的派奖
     * @auther  gwp
     * @date 2017年12月06日
     * @param not null $title 标题
     * @param not null $userOpenId 用户openId
     * @param not null $orderId 订单号
     * @param not null  $money  到账金额
     * @param not null  $time  到账时间
     * @param not null $detail 到账详情
     * @param not null $remark 备注
     * @return json
     */
    public function sendtemplateMsgUntreatedAward($title, $userOpenId, $type, $num, $money, $time, $remark) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgUntreatedBonus($title, $userOpenId, $type, $num, $money, $time, $remark);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息
        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'type' => $type,
            'num' => $num,
            'money' => $money,
            'time' => $time,
            'remark' => $remark,
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 8, $status);
        return $ret;
    }


    /**
     * 说明： 获取公众号消息模板--充值申请模板--流量单
     * @auther  zyl
     * @date 2017年9月21日 下午
     * @param not null  $title  消息标题
     * @param not null $userOpenId 用户openId
     * @param not null  $czMoney  充值金额
     * @param not null $czTime 充值时间
     * @param not null $userFunds 现有余额
     * @return type
     */
    public function sendTemplateMsgRechargeApiUser($title, $userOpenId, $name ,$cust_no,$czMoney, $czTime) {
        $wechatModel = new WechatModel();
        //获取微信发送模板的url
        $tplUrl = $this->getSendTemplateUrl();
        //封装消息模板
        $postData = $wechatModel->templateMsgRechargeApiUser($title, $userOpenId, $name ,$cust_no,$czMoney, $czTime);
        //发送信息
        $ret = \Yii::sendCurlPost($tplUrl, $postData);
        //存储微信消息
        $data = [
            'title' => $title,
            'userOpenId' => $userOpenId,
            'name' => $name,
            'cust_no' => $cust_no,
            'czMoney' => $czMoney,
            'czTime' => $czTime,
            'errcode' => $ret['errcode'],
            'errmsg' => $ret['errmsg'],
        ];
        $status = $ret['errmsg'] == 'ok' ? 1 : 2;
        $wechatModel->addWxMsgRecord($data, $userOpenId, 3, $status);
        return $ret;
    }

}
