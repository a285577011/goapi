<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\modules\common\models\LotteryOrder;
use app\modules\user\models\User;
use app\modules\common\models\PayRecord;
use yii\base\Object;
use app\modules\common\models\UserFunds;
use app\modules\common\models\Store;
use app\modules\common\services\KafkaService;
use app\modules\tools\helpers\Zmf;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class BackupController extends Controller {

    /**
     * 初始化备份payrecord
     */
    public function actionInitPayRecord() {
        $token = \Yii::$app->params['sqlserver_token'];
        $data['tablename'] = PayRecord::tableName();
        $data['keyfield'] = 'pay_record_id';
        $data['signdata'] = md5($data['tablename'] . $data['keyfield'] . $token);
        $surl = \Yii::$app->params['backup_sqlserver'];
        foreach (PayRecord::find()->asArray()->batch(1000) as $val) {
            $postLimit = 100; //每次传10条
            $total = ceil(count($val) / $postLimit);
            for ($j = 0; $j < $total; $j++) {
                $data['data'] = array_slice($val, $j * $postLimit, $postLimit);
                $postData = json_encode($data);
                $curl_ret = \Yii::sendCurlPost($surl, $postData);
                if ($curl_ret['code'] != 1) {
                    KafkaService::addLog('syncInitPayRecord', ['data' => $postData, 'requestResult' => $curl_ret]);
                }
            }
        }
        echo 'success';
        exit;
    }

    /**
     * 初始化备份lottery_order
     */
    public function actionInitLotteryOrder() {
        $token = \Yii::$app->params['sqlserver_token'];
        $data['tablename'] = LotteryOrder::tableName();
        $data['keyfield'] = 'lottery_order_id';
        $data['signdata'] = md5($data['tablename'] . $data['keyfield'] . $token);
        $surl = \Yii::$app->params['backup_sqlserver'];
        $lotteryqueue = new \LotteryQueue();
        foreach (LotteryOrder::find()->asArray()->batch(1000) as $val) {
            $postLimit = 100; // 每次传10条
            $total = ceil(count($val) / $postLimit);
            for ($j = 0; $j < $total; $j ++) {
                $data['data'] = array_slice($val, $j * $postLimit, $postLimit);
                $postData = json_encode($data);
                $curl_ret = \Yii::sendCurlPost($surl, $postData);
                if ($curl_ret['code'] != 1) {
                    KafkaService::addLog('syncInitLotteryOrder', ['data' => $postData, 'requestResult' => $curl_ret]);
                }
            }
        }
        echo 'success';
        exit();
    }

    /**
     * 初始化备份user_funds
     */
    public function actionInitUserFunds() {
        $token = \Yii::$app->params['sqlserver_token'];
        $data['tablename'] = UserFunds::tableName();
        $data['keyfield'] = 'user_funds_id';
        $data['signdata'] = md5($data['tablename'] . $data['keyfield'] . $token);
        $surl = \Yii::$app->params['backup_sqlserver'];
        $lotteryqueue = new \LotteryQueue();
        foreach (UserFunds::find()->asArray()->batch(1000) as $val) {
            $postLimit = 100; // 每次传10条
            $total = ceil(count($val) / $postLimit);
            for ($j = 0; $j < $total; $j ++) {
                $data['data'] = array_slice($val, $j * $postLimit, $postLimit);
                $postData = json_encode($data);
                $curl_ret = \Yii::sendCurlPost($surl, $postData);
                if ($curl_ret['code'] != 1) {
                    KafkaService::addLog('syncInitUserFunds', ['data' => $postData, 'requestResult' => $curl_ret]);
                }
            }
        }
        echo 'success';
        exit();
    }

    /**
     * 初始化备份user
     */
    public function actionInitUser() {
        $token = \Yii::$app->params['sync_im_token'];
        $data['tablename'] = User::tableName();
        $data['keyfield'] = 'user_id';
        $data['signdata'] = md5($data['tablename'] . $data['keyfield'] . $token);
        $surl = \Yii::$app->params['sync_im_api'];
        //$surl = 'http://27.155.105.176:8081/add';
        foreach (User::find()->asArray()->batch(1000) as $val) {
            $postLimit = 10; // 每次传10条
            $total = ceil(count($val) / $postLimit);
            for ($j = 0; $j < $total; $j ++) {
                $data['data'] = array_slice($val, $j * $postLimit, $postLimit);
                $postData = json_encode($data);
                $curl_ret = \Yii::sendCurlPost($surl, $postData);
                if ($curl_ret['code'] != 1) {
                    KafkaService::addLog('syncInitUser', ['data' => $postData, 'requestResult' => $curl_ret]);
                }
            }
        }
        echo 'success';
        exit();
    }

    /**
     * 初始化备份store
     */
    public function actionInitStore() {
        $token = \Yii::$app->params['sync_im_token'];
        $data['tablename'] = Store::tableName();
        $data['keyfield'] = 'store_id';
        $data['signdata'] = md5($data['tablename'] . $data['keyfield'] . $token);
        $surl = \Yii::$app->params['sync_im_api'];
        //$surl = 'http://27.155.105.176:8081/add';
        $lotteryqueue = new \LotteryQueue();
        foreach (Store::find()->asArray()->batch(1000) as $val) {
            $postLimit = 100; // 每次传10条
            $total = ceil(count($val) / $postLimit);
            for ($j = 0; $j < $total; $j ++) {
                $data['data'] = array_slice($val, $j * $postLimit, $postLimit);
                $postData = json_encode($data);
                $curl_ret = \Yii::sendCurlPost($surl, $postData);
                if ($curl_ret['code'] != 1) {
                    KafkaService::addLog('syncInitStore', ['data' => $postData, 'requestResult' => $curl_ret]);
                }
            }
        }
        echo 'success';
        exit();
    }

    public function actionAuto() {
        $data = [
            'lotteryId' => "D14", //玩法代码
            'issue' => '18022', //期号（竞彩玩法忽略此字段）
            'records' => [
                'record' => [
                    'id' => 'GLCAUTO18021213AI0000001', //投注序列号(不可重复)订单编号
                    'lotterySaleId' => '0', //销售代码(竞彩自由过关，过关方式以^分开)
                    'freelotterySaleId' => 0, //1:自由过关 0:非自由过关
//                    'phone'=>'13960774169',//手机号（可不填）
//                    'idCard'=>'350681199002095254',//身份证号（可不填）
                    'code' => "3*3*3*3*3*3*0*3*1*1*0*0*1*1^", //注码。投注内容
                    'money' => 200, //金额
                    'timesCount' => 1, //倍数
                    'issueCount' => 1, //期数
                    'investCount' => 1, //注数
                    'investType' => 0, //投注方式
                ]
            ]
        ];
        $zmfObj = new Zmf();
        $ret = $zmfObj->to1000($data);

        print_r($ret);die;
    }

}
