<?php

namespace app\modules\orders\controllers;

use Yii;
use yii\web\Controller;
use app\modules\tools\helpers\Nm;
use app\modules\tools\helpers\Jw;
use app\modules\tools\helpers\Des;
use app\modules\tools\kafka\Kafka;
use app\modules\common\services\KafkaService;

class JwController extends Controller {
	
	/**
	 * 查询余额
	 */
	public function actionJw200100(){
		$request = \Yii::$app->request;
		$Obj = new Jw();
        $ret = $Obj->to200100();

        $this->jsonResult(600,'succ', $ret);
	}
    /**
     * 说明:竞彩投注接口  LV->LPS
     * @author chenqiwei
     * @date 2018/1/10 下午2:18
     * @param
     * @return json
     */
    public function action200008()
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
        $post = \Yii::$app->request->post();
			/*
		 * $content=['gameId'=>$post["gameId"],
		 * 'issue'=>$post["issue"],
		 * 'orderList'=>$post['orderList'],
		 * ];
		 */
		$content = [ 
			'gameId' => "2008" , 
			'issue' => "18051644" , 
			'orderList' => [ 
				[ 
					'betCount' => "2" , 
					'betDetail' => "1;23001;1;02|03|;2;23001;1;04|03|;" , 
					'orderId' => 'GLCHB11X518051609T0000013' , 
					'ticketMoney' => "4" , 
					'timeStamp' => (string) Jw::getMillisecond() 
				] 
			] 
		];
        $obj = new Jw();
        $ret = $obj->to200008($content);

        $this->jsonResult(600,'succ', $ret);
    }

    /**
     * 说明:主动查询竞彩出票结果接口
     * @param
     * @return json
     */
    public function action200009(){
        $request = \Yii::$app->request;
        $orderIds = $request->post('orderIds');
        $obj = new Jw();
        $ret = $obj->to200009($orderIds);

        $this->jsonResult(600,'succ', $ret);

    }


    /**
     * 说明: 出票结果接口(异步回调) 
     * @param
     * @return
     */

    public function actionJw300002()
    {
    	header('Content-Type:application/json');
    	$postData = file_get_contents('php://input');
    	if(!$postData){
    		$this->jsonError('101', '参数为空');
    	}
    	$postArr=json_decode($postData,true);
    	if($postArr['apiCode']==300002){
    		$zmfObj = new Jw();
        	$zmfRet = $zmfObj->to300002($postArr);
    	}
        $return=[];
        $return['resCode']="0";
        $return['resMsg']="";
        $jw=new Jw();
        $postArr['version']=$jw->version;
        $key = \Yii::$app->params['jw_key'];
        $return['content']=[];
        $return['messageId']=$postArr['messageId'];
        $return['version']=$postArr['version'];
        $return['apiCode']=$postArr['apiCode'];
        $return['partnerId']=$postArr['partnerId'];
        $return['hmac']=Jw::hmac($postArr['apiCode'].$postArr['content'].$postArr['messageId'].$postArr['partnerId'].$postArr['version'],substr($key, 0,16));
        return json_encode($return);
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

