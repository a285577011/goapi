<?php

namespace app\modules\orders\controllers;

use Yii;
use yii\web\Controller;
use app\modules\tools\helpers\Nm;

class NmController extends Controller {

    /**
     * 说明:竞彩投注接口  LV->LPS
     * @author chenqiwei
     * @date 2018/1/10 下午2:18
     * @param
     * @return json
     */
    public function actionNm801()
    {
        $request = \Yii::$app->request;
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
        $orderid = $request->post('orderid');
        $data = [
            [
                'lotterytype'=>2001,
                'phase'=>'18056',
                'orderid'=>$orderid,
                'playtype'=>'200101',
                'betcode'=>'01,02,03,04,05|01,02^',
                'multiple'=>1,
                'amount'=>2,
                'add'=>0,
                'endtime'=>'',
            ],
//        	['lotterytype'=>2001,
//        	'phase'=>'18056',
//        	'orderid'=>'test0019',
//        	'playtype'=>200101,
//        	'betcode'=>'01,02,03,04,05|01,02^',
//        	'multiple'=>1,
//        	'amount'=>2,
//        	'add'=>0,
//        	'endtime'=>'',],
        ];
        $datas=[];
        $datas['orderlist'] =$data;
        $nmObj = new Nm();
        $ret = $nmObj->to801($datas);

        $this->jsonResult(600,'succ', $ret);
    }

    /**
     * 说明:主动查询竞彩出票结果接口 LV->LPS
     * @author chenqiwei
     * @date 2018/1/10 下午2:50
     * @param
     * @return json
     */
    public function actionNm802(){
        $data = [
            [
                'orderid'=>'test0024',
            ],[
                'orderid'=>'test0025',
            ],
            [
                'orderid'=>'test0026',
            ],
        ];
        $datas['orderlist'] =$data;
        $nmObj = new Nm();
        $ret = $nmObj->to802($datas);

        $this->jsonResult(600,'succ', $ret);
    }

    /**
     * 说明:主动查询竞彩出票结果接口 LV->LPS
     * @author chenqiwei
     * @date 2018/1/10 下午2:50
     * @param
     * @return json
     */
    public function actionNm803(){
    	$request = \Yii::$app->request;
    	$orderid = $request->post('orderid');
        $data = [
            [
                'orderid'=>$orderid,
            ],
        ];
        $datas['orderlist'] =$data;
        $nmObj = new Nm();
        $ret = $nmObj->to803($datas);

        $this->jsonResult(600,'succ', $ret);
    }

    public function actionNm806(){

        $nmObj = new Nm();
        $ret = $nmObj->to806();

        $this->jsonResult(600,'succ', $ret);
    }

    /**
     * 说明: 投注结果通知接口(异步回调)  LPS->LV
     * @author chenqiwei
     * @date 2018/1/11 上午9:49
     * @param
     * @return
     */

    public function actionNm903()
    {
        //1接收zmf的xml参数
        $request = \Yii::$app->request;
        $paramsXml = $request->getRawBody();
        $paramsXml = rawurldecode($paramsXml);
        $paramsXml = str_replace("xml+version","xml version",$paramsXml);
        $paramsXml = str_replace("+encoding="," encoding=",$paramsXml);
        if(empty($paramsXml)){
            $this->jsonError(109,'参数错误');
        }
        $nm = new Nm();
        $retData = $nm->to903($paramsXml);

        return $retData;
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

