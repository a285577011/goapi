<?php
namespace app\modules\test\controllers;

use app\modules\tools\helpers\Des;
use yii\web\Controller;

class ThirdOrderController extends Controller
{
    public $enableCsrfValidation = false;

    private $key = '14302DD3F8202E85DDB9516F'; //密钥
    private $iv = '20180101';//IV 向量

   
    public function __construct($id,$module,$config=[])
    {
        parent::__construct($id,$module,$config);
    }


    /**
     * 说明:模拟流量方接口
     * @author chenqiwei
     * @date 2018/1/27 下午3:23
     * @param
     * @return
     */
    public function actionTest(){

        $request = \Yii::$app->request;
        $param =  $request->post();
        $command =  $request->get('command');
//        $json_data = json_encode($param);
        $des = new Des($this->key, $this->iv);
//        echo 1;die;
//        $re = $des->decrypt("uQHpYDOKEkeBNHe7C2MAKrPXKa7FESGaBwkKqjA4Uimr6ZNnxsU2YcSJZqeIByqLdymofyTmNIZ7oG4VWy5Y8VGG73lJ2RPqPzrrmU26aimvLXPM1mLY1WT2sHVnnqMtqsF4XkQU//Nw7GXmUZme3g==");
//        print_R($re);DIE;
//print_r($json_data);die;
        $des_data = $des->encrypt($json_data);

        $request_data = [
            'message' => [
                'head' => [
                    'command' => $command,
                    'venderId' => 'GL3d90690d167856',
                    'messageId' => 'kevi0007',
                    'md' => md5($des_data),
                ],
                'body' => $des_data,
            ],
        ];
        $json_data = json_encode($request_data);

//        $url = 'http://test.lottery_h5.com/api/openapi/thirdapi/transfer';
        $url = 'http://php.javaframework.cn/api/openapi/thirdapi/transfer';
//        $url = 'http://caipiao.goodluckchina.net/api/openapi/thirdapi/transfer';
        $retData = $this->jsonPost($url, $json_data);
        $retDataArr = json_decode($retData, true);
        if($retDataArr['message']['head']['status']==0){
            $retDataArr['message']['body'] = json_decode($des->decrypt($retDataArr['message']['body']), true);
        }
        //解密
        $this->jsonResult(600,'succ', $retDataArr);
    }

    public function jsonPost($url, $data){
        $header[] = "Content-type:application/json";//定义conten t-type为xml
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        if(curl_errno($ch))
        {
            \Yii::info(var_export(curl_error($ch),true), 'backuporder_log');
        }
        curl_close($ch);
        return $response;
    }

//    public function checkDate($body){
//        //解密
//        $des = new Des($this->key, $this->iv);
//        $de_body = $des ->decrypt($body);
//        return json_decode($de_body, true);
//    }

}