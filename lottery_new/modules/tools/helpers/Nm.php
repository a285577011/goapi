<?php

namespace app\modules\tools\helpers;

use app\modules\orders\models\ZmfOrder;
use app\modules\common\services\KafkaService;
use app\modules\orders\helpers\OrderDeal;

class Nm {

//    private $venderId = '18020601'; //销售商代码
//    private $url = 'http://120.77.204.131:8098/'; //智魔方地址
//    private $key = '56B06065B5237AF34DBBCBF8'; //密钥
    private $merchant = '80028';
    private $version = '1.0'; //版本号

    public function getHead($command) {
//        $venderId = \Yii::$app->params['zmf_venderId'];
        $messageId = $this->merchant . $command . date('ymdHis') . floor(floatval(microtime()) * 1000) . rand(0, 1000);
        $head = ['head' => ['version' => $this->version, 'command' => $command, 'merchant' => $this->merchant, 'messageid' => $messageId,'timestamp' => date('YmdHis')]];
        return $head;
    }

    /**
     * 说明: 1000 提交投注记录接口 
     * @author  kevi
     * @date 2018年1月5日 下午4:41:04
     * @param
     * @return 
     */
    public function to801($data) {
        $ret = $this->postData(801,$data);
        $orderList = $ret['body']['orderlist']['order'];
        if(count($data['orderlist'])==1){//xml转数组的坑，如果是一维数组，则强制转二维
            $orderList2[]=$orderList;
            $orderList = $orderList2;
        }
        foreach ($orderList as $k => $order){
            $zmfOrder = new ZmfOrder();
            $zmfOrder->order_code = $order['orderid'];
            $zmfOrder->version = $this->version;
            $zmfOrder->command = 801;
            $zmfOrder->messageId = $ret['head']['messageid'];
            $zmfOrder->bet_val = json_encode($data['orderlist'][$k]);
            $zmfOrder->status = $order['errorcode'];
            $zmfOrder->ret_sync_data = json_encode($order);
            $zmfOrder->create_time = date('Y-m-d H:i:s');
            if (!$zmfOrder->save()) {
                $errorMsg = $zmfOrder->errors;
                KafkaService::addLog('nmorder801', $errorMsg);
                return \Yii::jsonError(100, $errorMsg);
            }
//            KafkaService::addLog('nmorder801', $order);
            if($order['errorcode']==0){
                return 1;
            } else {
                return $order['errorcode'];
            }
        }
        return 0;
//        print_r($orderList);die;
    }

    /**
     * 说明:投注结果查询
     * @author chenqiwei
     * @date 2018/5/15 上午10:31
     * @param
     * @return
     */
    public function to802($data){

        $ret = $this->postData(802,$data);
        $orderList = $ret['body']['orderlist']['order'];

        if(count($data['orderlist'])==1){//xml转数组的坑，如果是一维数组，则强制转二维
            $orderList2[]=$orderList;
            $orderList = $orderList2;
        }
        $count = 0;
        foreach ($orderList as $order){
            $zmfOrder = ZmfOrder::find()->where(['order_code'=>$order['orderid'],'status'=>0])->one();
            if(empty($zmfOrder)){
                continue;
            }
            $zmfOrder->command = 802;
            $zmfOrder->status = $order['errorcode'];
            $zmfOrder->ret_sync_data = json_encode($order);
            $zmfOrder->modify_time = date('Y-m-d H:i:s');

//            $autoOutOrder = AutoOutOrder::findOne(['out_order_code' => $order['orderid'], 'status' => 2]);
//            $autoOutOrder->ticket_code = $order['ticketlist']['ticketid'];
//            $autoOutOrder->status = 4;
//            $autoOutOrder->modify_time = date('Y-m-d H:i:s');
//            $autoOutOrder->save();

            if (!$zmfOrder->save()) {
                $errorMsg = $zmfOrder->errors;
                KafkaService::addLog('nmorder802', $errorMsg);
                return \Yii::jsonError(100, $errorMsg);
            }
            $count++;
        }
        return $count;
    }


    /**
     * 说明:中奖结果查询
     * @author chenqiwei
     * @date 2018/5/15 上午10:31
     * @param
     * @return
     */
    public function to803($data){

        $ret = $this->postData(803,$data);

        return $ret;
    }

    /**
     * 说明: 余额查询
     * @author chenqiwei
     * @date 2018/5/15 上午11:04
     * @param
     * @return
     */
    public function to806(){

        $datas['content']['merchant'] ='80028';
        $ret = $this->postData(806,$datas);
        return $ret;
    }

    public function postData($command,$data){
        $dataXml = $this->formatxml($data,'message');
        $desModel = new Des(\Yii::$app->params['nm_appkey']);
        $desData = $desModel->encrypt_ecb($dataXml); //des加密
        
        //post消息体封装
        $head = $this->getHead($command); //获取公共head
        $head['head']['md'] = md5($desData);
        $head['body'] = $desData;
        $head['signature']=md5($head['head']['command'].$head['head']['timestamp'].$head['head']['merchant'].\Yii::$app->params['nm_appkey']);
        $mxlHead = $this->formatxml($head, 'content');
        //提交请求
//        ob_clean();
        if (ob_get_contents()){
            ob_clean();
        }
        $postRet = $this->xmlpost($mxlHead);
        $ret = $this->xmlToArray($postRet);
        if ($ret['head']['errorcode'] == 0) {
            $xmlret = $desModel->decrypt_ecb($ret['body']);
            $ret['body'] = $this->xmlToArray($xmlret);
        }
        return $ret;
    }
    /**
     * 说明:投注结果异步通知
     * @author chenqiwei
     * @date 2018/5/15 上午11:04
     * @param
     * @return
     */
    public function to903($data){
        $paramsArr = $this->xmlToArray($data);
        //des3解密
        $desModel = new Des(\Yii::$app->params['nm_appkey']);
        $desData = $desModel->decrypt_ecb($paramsArr['body']);
        $body = $this->xmlToArray903($desData);
        $orderList = $body['orderlist']['order'];

        if(isset($orderList['orderid'])){//xml转数组的坑，如果是一维数组，则强制转二维
            $orderList2[]=$orderList;
            $orderList = $orderList2;
        }
        foreach ($orderList as $order){
            if($order['errorcode'] == 0 ){//出票成功
                //1、写日志
                $zmfOrder = ZmfOrder::find()->where(['order_code'=>$order['orderid'],'status'=>0])->one();
//                $zmfOrder->ret_async_data = json_encode($order);
                if(empty($zmfOrder)){
                    continue;
                }
                $zmfOrder->command = 903;
                $zmfOrder->status = $order['errorcode'];
                $zmfOrder->ret_async_data = json_encode($order);
                $zmfOrder->modify_time = date('Y-m-d H:i:s');
                $zmfOrder->save();
                $ticketId = $order['ticketlist']['ticketcontent']['ticketid'];
                $status = 4;
            }elseif($order['errorcode'] == 2 ){//出票失败
                $ticketId = '';
                $status = 5;
            }
            OrderDeal::thirdCall($order['orderid'], $status, $ticketId);
        }

        $retData = [
            'merchant'=>$this->merchant,
            'command' =>903,
            'errorcode'=>0
        ];
        $ret = $this->formatxml($retData,'response');
        ob_clean();
        return  $ret;
//        print_r($body);die;

    }

    public function formatxml($arr, $firstDom = "body", $dom = 0, $item = 0) {
        if (!$dom) {
            $dom = new \DOMDocument("1.0");
        }
        if (!$item) {
            $item = $dom->createElement($firstDom);
            $dom->appendChild($item);
        }
        foreach ($arr as $key => $val) {
            $itemx = $dom->createElement(is_string($key) ? $key : "order");
            $item->appendChild($itemx);
            if (!is_array($val)) {
                $text = $dom->createTextNode($val);
                $itemx->appendChild($text);
            } else {
                $this->formatxml($val, $firstDom, $dom, $itemx);
            }
        }
        $dom->encoding = 'UTF-8';
        return $dom->saveXML();
    }

    public function xmlToArray($xml) {

        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);

        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		
        $val = json_decode(json_encode($xmlstring), true);

        return $val;
    }

    public function xmlpost($xml_data) {
        $url = 'http://123.57.24.176/b2b/bet';
        if(YII_ENV_DEV){
            $url = 'http://123.56.233.30/b2b/bet';
        }
        $header[] = "Content-type: application/xml"; //定义content-type为xml
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }

    public function xmlToArray903($xml) {
    
    	// 禁止引用外部xml实体
    	libxml_disable_entity_loader(true);

    	$xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

    	foreach ($xmlstring->orderlist->order as $k => $v){
//    	    echo $code = $v->errorcode;die;
    	    if($v->errorcode == 0 ){
                $sp =  $v->ticketlist->ticket->attributes()->sp;
                $ticketid = $v->ticketlist->ticket->attributes()->ticketid;
                $v->ticketcontent->sp=$sp;
                $v->ticketcontent->ticketid=$ticketid;
            }
        }
    	$val = json_decode(json_encode($xmlstring), true);
    	return $val;
    }
    public function to802res($data){
    
    	$ret = $this->postData(802,$data);
    	$orderList = $ret['body']['orderlist']['order'];
    
    	if(count($data['orderlist'])==1){//xml转数组的坑，如果是一维数组，则强制转二维
    		$orderList2[]=$orderList;
    		$orderList = $orderList2;
    	}
    	$count = 0;
    	foreach ($orderList as $order){
    		if($order['errorcode'] == 0 ){//出票成功
    			//1、写日志
    			$zmfOrder = ZmfOrder::find()->where(['order_code'=>$order['orderid'],'status'=>0])->one();
    			//                $zmfOrder->ret_async_data = json_encode($order);
    			if(empty($zmfOrder)){
    				continue;
    			}
    			$zmfOrder->command = 903;
    			$zmfOrder->status = $order['errorcode'];
    			$zmfOrder->ret_async_data = json_encode($order);
    			$zmfOrder->modify_time = date('Y-m-d H:i:s');
    			$zmfOrder->save();
    			$ticketId = $order['ticketlist']['ticketcontent']['ticketid'];
    			$status = 4;
    		}elseif($order['errorcode'] == 2 ){//出票失败
    			$ticketId = '';
    			$status = 5;
    		}
    		OrderDeal::thirdCall($order['orderid'], $status, $ticketId);
    	}
    }

}
