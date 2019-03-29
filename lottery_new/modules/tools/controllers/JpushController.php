<?php

namespace app\modules\tools\controllers;
// use yii\web\Controller;
use yii\web\Controller;
use JPush\Client;

/**
 * 邮件发送工具
 */
class JpushController extends Controller
{
    /**
     * 说明: 发送邮件测试
     * @author  kevi
     * @date 2017年7月18日 下午3:10:25
     * @param
     * @return 
     */
    public function actionSendEmail(){
        echo 'aa';die;
        $mail = \Yii::$app->mailer->compose();
        $mail->setTo('330541666@qq.com');
        $mail->setSubject("邮件测试");
        $mail->setHtmlBody("<br>问我我我我我");
        if($mail->send()){
            echo "success";
        }else{
            echo "failse";
        }
        die();
    }
    
    /**
     * 说明: 推送所有用户消息
     * @author  kevi
     * @date 2017年12月20日 上午10:51:55
     * @param
     * @return 
     */
    public function actionPushAll(){
        $jclien = new Client();
    }
}
