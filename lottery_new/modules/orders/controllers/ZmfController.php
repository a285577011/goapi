<?php

namespace app\modules\orders\controllers;

use Yii;
use yii\web\Controller;
use app\modules\tools\helpers\Zmf;

class ZmfController extends Controller {

//    /**
//     * 说明:竞彩投注接口  LV->LPS
//     * @author chenqiwei
//     * @date 2018/1/10 下午2:18
//     * @param
//     * @return json
//     */
//    public function actionZmf1000()
//    {
////        echo phpinfo();die;
//        $request = \Yii::$app->request;
////        $id = $request->post('id');
////        $code = $request->post('code');
////        if(!$id){
////            $this->jsonResult(100,'Order Code not null');
////        }
//        $data = [
//            'lotteryId'=>"D14",//玩法代码
//            'issue'=>'18022',//期号（竞彩玩法忽略此字段）
//            'records'=>[
//                'record'=>[
//                    'id'=>'GLCAUTO18021213AI0000001',//投注序列号(不可重复)订单编号
//                    'lotterySaleId'=>'0',//销售代码(竞彩自由过关，过关方式以^分开)
//                    'freelotterySaleId'=>0,//1:自由过关 0:非自由过关
////                    'phone'=>'13960774169',//手机号（可不填）
////                    'idCard'=>'350681199002095254',//身份证号（可不填）
//                    'code'=>"3*3*3*3*3*3*0*3*1*1*0*0*1*1^",//注码。投注内容
//                    'money'=>200,//金额
//                    'timesCount'=>1, //倍数
//                    'issueCount'=>1,//期数
//                    'investCount'=>1,//注数
//                    'investType'=>0,//投注方式
//                ]
//            ]
//        ];
//        $zmfObj = new Zmf();
//        $ret = $zmfObj->to1000($data);
//
//        $this->jsonResult(600,'succ', $ret);
//    }

    /**
     * 说明:主动查询竞彩出票结果接口 LV->LPS
     * @author chenqiwei
     * @date 2018/1/10 下午2:50
     * @param
     * @return json
     */
    public function actionZmf1019(){
        $request = \Yii::$app->request;
        $messageId = $request->post('messageId');
        $data = [
            'messageId'=>$messageId,//玩法代码
        ];
        $zmfObj = new Zmf();
        $ret = $zmfObj->to1019($data);

        $this->jsonResult(600,'succ', $ret);

    }


    /**
     * 说明: 竞彩出票结果接口(异步回调)  LPS->LV
     * @author chenqiwei
     * @date 2018/1/11 上午9:49
     * @param
     * @return
     */

    public function actionZmf1101()
    {
        //1接收zmf的xml参数
        $request = \Yii::$app->request;
        $paramsXml = $request->getRawBody();
        if(empty($paramsXml)){
            $this->jsonError(109,'参数错误');
        }
        $zmfObj = new Zmf();
        $zmfRet = $zmfObj->to1101($paramsXml);
        \Yii::redisSet('zmfOrder1', $zmfRet, 600);
        $messageId = $zmfRet['messageId'];
        ob_clean();
        $retBody = $zmfObj->formatxml($zmfRet['data']);
        $jmxit = $zmfObj->zmfencrypt($retBody);//加密消息体
        ob_clean();
        $retArr = [
            'head'=>[
                'messageId'=>$messageId,
                'result'=>0,
                'md'=>md5($jmxit),
            ],
            'body'=>$jmxit,
        ];
        \Yii::redisSet('zmfOrder2', $retArr, 600);
        $retBody = $zmfObj->formatxml($retArr,'message');
        ob_clean();
        \Yii::redisSet('zmfOrder', $retBody, 600);
        return $retBody;
    }

    public function xmlToArray($xml) {

        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);

        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $val = json_decode(json_encode($xmlstring), true);

        return $val;
    }

    /**
     * 说明:封装放回数据的xml格式
     * @author chenqiwei
     * @date 2018/2/24 下午1:49
     * @param   array   data    数据
     * @return  obj
     */
    public function createXmlResponse($data,$rootTag){
        $xmlObj = \Yii::createObject([
            'class' => 'yii\web\Response',
            'format' => \yii\web\Response::FORMAT_XML,
            'formatters' => [
                \yii\web\Response::FORMAT_XML => [
                    'class' => 'yii\web\XmlResponseFormatter',
                    'rootTag' => $rootTag, //根节点
                ],
            ],
            'data' =>$data,
        ]);

        return $xmlObj;
    }

    public function actionZmf1002(){
        $request = \Yii::$app->request;
        $messageId = $request->post('messageId');
        $data = [
            'messageId'=>$messageId,//玩法代码
        ];
        $zmfObj = new Zmf();
        $ret = $zmfObj->to1002($data);

        $this->jsonResult(600,'succ', $ret);
    }
    

}

