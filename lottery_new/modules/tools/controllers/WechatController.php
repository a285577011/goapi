<?php

namespace app\modules\tools\controllers;

use app\modules\common\models\LotteryOrder;
use Yii;
use yii\web\Controller;
use app\modules\user\helpers\WechatTool;
use app\modules\common\helpers\Commonfun;

class WechatController extends Controller {
    
    /**
     * 说明: 发送投注消息-门店
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
    public function actionSendMsgBetStore(){
        $request = \Yii::$app->request;
        $userOpenId = $request->post('open_id');
        $orderId = $request->post('order_id');
        $userName = $request->post('user_name');
        $orderMoney = $request->post('order_money');
        $orderTitle = $request->post('order_title');
        $orderLimitTime = $request->post('order_limit_time');

            $userOpenId = 'otEbv0SK-A0T5dBi17TPIOA1dXkgsss';
            $orderId = 'WX02302301';
            $userName = '达芙妮女士（13696995112）';
            $orderMoney = '200元';
            $orderTitel = '双色球2013023期';
            $orderLimitTime = '2017-08-15 21:30:00';
            $remark = '';
            $title = '1';
        //微信发送-投注订单消息给彩店
        $wechatTool = new WechatTool();
        $ret = $wechatTool->sendTemplateMsgBetStore($title, $userOpenId, $orderId, $userName, $orderMoney, $orderTitle,$orderLimitTime, $remark);

        $result['openId'] = $userOpenId;
        $this->jsonResult(600, '推送成功',$result);
    }

    /**
     * 通知门店未处理的派奖微信推送
     * @param not null $title 标题
     * @param not null $userOpenId 用户openId
     * @param not null $orderId 订单号
     * @param not null $money  到账金额
     * @param not null $time  到账时间
     * @param not null $detail 到账详情
     * @param not null $remark 备注
     */
    public function actionSendGetAward(){

        $fields= 'COUNT(lottery_order.lottery_order_id) num, SUM(lottery_order.win_amount) money, s.user_id, t.third_uid ';
        $rows= LotteryOrder::find()->select($fields)
            ->innerJoin('store s', 's.user_id = lottery_order.store_id')
            ->innerJoin('third_user t', 't.uid = s.user_id')
            ->where(['lottery_order.status'=>4, 'lottery_order.deal_status'=>1])
            ->groupBy('lottery_order.store_id')
            ->asArray()
            ->all();
        $wechatTool = new WechatTool();
        $title = '您有未派奖订单，请及时处理';
        $type = '未派奖订单';
        $remark = '请您核实订单，尽快与用户取得联系';
        $time = date('Y-m-d');
        foreach($rows as $v){
            if($v['third_uid']){
                $wechatTool -> sendtemplateMsgUntreatedAward($title, $v['third_uid'], $type, $v['num'], $v['money'], $time, $remark);
            }
        }
    }
    /**
     * 微信警报通知
     */
    public function actionWarnNotic(){
    	$get=\Yii::$app->request->get();
    	Commonfun::sysAlert($get['msg']??'', $get['level']??'', $get['errorInfo']??'', "未处理", "请尽快处理！");
    }
    
    
}