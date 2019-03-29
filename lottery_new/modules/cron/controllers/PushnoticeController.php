<?php

namespace app\modules\cron\controllers;

use yii\web\Controller;
use app\modules\tools\helpers\Nm;
use app\modules\common\helpers\Commonfun;

/**
 * 第三方api推送通知
 */
class PushnoticeController extends Controller {

    /**
     * 定时检查未推送成功给第三方的通知
     * @return boolean
     */
    public function actionThridPushNotice() {
        $thirdNotice = new \app\modules\openapi\services\ApiNoticeService();
        $thirdNotice -> api_notice_crontab();
    }
    /**
     * 糯米金额预警
     */
    public function actionNmAccount(){
    	$nmObj = new Nm();
    	$ret = $nmObj->to806();
    	$money=$ret['body']['balance'];
    	if($money<100000){
    		if(\yii::redisGet('nm-money-notice')){
    			echo 'notice-already';
    			exit;
    		}
    		\yii::redisSet('nm-money-notice', 1, 86400);
    		Commonfun::sysAlert('咕啦在糯米的总账', '预警', '咕啦在糯米剩余总账户余额:' .$money, '待处理', '请及时充值');
    		echo 'notice';
    		exit;
    	}
    	echo 'success';
    	exit;
    }
}
