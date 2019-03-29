<?php

namespace app\modules\tools\helpers;

use app\modules\orders\models\ZmfOrder;
use app\modules\common\services\KafkaService;
use app\modules\orders\helpers\OrderDeal;

class Jw {

//    private $venderId = '18020601'; //销售商代码
//    private $url = 'http://120.77.204.131:8098/'; //智魔方地址
//    private $key = '56B06065B5237AF34DBBCBF8'; //密钥
//    private $merchant = '80028';
//    private $appKey = '12345678';
    public $version = '1.0'; //版本号

    public function getHead($command) {
        $merchant = \Yii::$app->params['jw_venderId'];
        $messageId = $merchant . $command . date('ymdHis') . floor(floatval(microtime()) * 1000) . rand(0, 1000);
        $head = ['head' => ['version' => $this->version, 'command' => $command, 'merchant' => $merchant, 'messageid' => $messageId,'timestamp' => date('YmdHis')]];
        return $head;
    }

    /**
     * 说明: 1000 提交投注记录接口 
     * @author  kevi
     * @date 2018年1月5日 下午4:41:04
     * @param
     * @return 
     */
    public function to200008($data) {
    	$data2=['apiCode'=>"200008"];
    	$data2['content'] =$data;
       	$post=$this->formatPost($data2);
       	$rs=\yii::curlJsonPost(\Yii::$app->params['jw_url'], json_encode($post));
        if(!$rs['error']){
        	$rsArr=json_decode($rs['data'],true);
        	if($rsArr['resCode']==0){
        		$content=$rsArr['content'];
        		$key = \Yii::$app->params['jw_key'];
        		$desModel = new Des($key,'12345678');
        		$content=$desModel->decrypt($content);
        		$contentArr=json_decode($content,true);
                        \Yii::redisSet('contentArr', $contentArr, 300);
        		foreach ($contentArr['orderList'] as $k => $order){
        			if($order['status']!=0){
        				KafkaService::addLog('jworderErr1200008', $order);
        				continue;
        			}
        			$zmfOrder = new ZmfOrder();
        			$zmfOrder->order_code = $order['orderId'];
        			$zmfOrder->version = $this->version;
        			$zmfOrder->command = $data2['apiCode'];
        			$zmfOrder->messageId = $post['messageId'];
        			$zmfOrder->bet_val = json_encode($data['orderList']);
        			$zmfOrder->status = $order['status'];
        			$zmfOrder->ret_sync_data = json_encode($order);
        			$zmfOrder->create_time = date('Y-m-d H:i:s');
        			if (!$zmfOrder->save()) {
        				$errorMsg = $zmfOrder->errors;
        				KafkaService::addLog('jworder200008', $errorMsg);
        				return \Yii::jsonError(100, $errorMsg);
        			}
                                if($order['status'] == 0) {
                                    return 1;
                                } else {
                                    return $order['status'];
                                }
        		}
        	}
        	KafkaService::addLog('jworderErr200008', $rs['data']);
        	return 0;
    	}
        
    }

    /**
     * 说明: 查询期信息接口
     * @param
     * @return
     */
    public function to200009($orderIds) {
    	$data=['apiCode'=>"200009"];
    	    	$key='orderId';
    	$orderData=[];
    	$orderIdArr=explode(',', $orderIds);
    	foreach ($orderIdArr as $v){
    		$orderData[][$key]=$v;
    	}
    	$data['content']['orderList'] =$orderData;
    	$post=$this->formatPost($data);
    	$rs=\yii::curlJsonPost(\Yii::$app->params['jw_url'], json_encode($post));
    	if(!$rs['error']){
    		$rsArr=json_decode($rs['data'],true);
    		$content=$rsArr['content'];
    		$key = \Yii::$app->params['jw_key'];
    		$desModel = new Des($key,'12345678');
    		$content=$desModel->decrypt($content);
    		$contentArr=json_decode($content,true);
    		foreach ($contentArr['resultList'] as $v){
    			if($v['status']==2){//出票成功
    				$status=4;
    			}elseif($v['status']==-1){//出票失败
    				$status=5;
    			}
    			else{
    				$status=1;
    			}
    			self::doTicket($v['orderId'], $v['tickSn'], $status);
    		}
    		return true;
    	}
    	KafkaService::addLog('to200009-fail', $rs);
	}
	/**
	 * 出票操作
	 */
	public static function doTicket($orderCode,$ticketId,$status){
		//写入日志
		$zmfOrder = ZmfOrder::find()->where(['order_code' => $orderCode])->andWhere('status !=1')->one();
		if (!empty($zmfOrder)) {
			$zmfOrder->ret_async_data = json_encode($ret);
			$zmfOrder->status = 2;
			$zmfOrder->modify_time = date('Y-m-d H:i:s');
			if (!$zmfOrder->save()) {
				$errorMsg = $zmfOrder->errors;
				KafkaService::addLog('to200009-order-fail1:'.$orderCode, $errorMsg);
				return false;
			}
			$autoOutOrder = AutoOutOrder::findOne(['out_order_code' => $zmfOrder->order_code, 'status' => 2]);
			if (empty($autoOutOrder)) {
				KafkaService::addLog('to200009-order-fail2:'.$orderCode, $autoOutOrder);
				return false;
			}
			$autoOutOrder->ticket_code = $ticketId;
			$autoOutOrder->status = $status;
			$autoOutOrder->modify_time = date('Y-m-d H:i:s');
			if (!$autoOutOrder->save()) {
				$errorMsg = $autoOutOrder->errors;
				KafkaService::addLog('to200009-order-fail3:'.$orderCode, $errorMsg);
				return false;
			}
			KafkaService::addQue('ConfirmOutTicket', ['orderCode' => $autoOutOrder->order_code], true);
		}
		return true;
	}
	public function to300002($postArr)
	{
		$key = \Yii::$app->params['jw_key'];
		$desModel = new Des($key, '12345678');
		$content = $desModel->decrypt($postArr['content']);
		$contentArr = json_decode($content, true);
		/*$contentArr=json_decode('{
	"notifyList": [{
			"gameId": "4",
			"innerId": "",
			"orderId": "3011491559496785",
			"status": "2",
			"tickSn": "",
			"wagerTime": "2018-05-15 17:26:53"
		}
	]
}',true);*/
		// 写日志
		foreach ($contentArr['notifyList'] as $contentC)
		{
			$zmfOrder = ZmfOrder::find()->where([ 
				'order_code' => $contentC['orderId'] 
			])->one();
			if (! empty($zmfOrder))
			{
				$zmfOrder->ret_async_data = json_encode($contentArr);
				$zmfOrder->status = 1;
				$zmfOrder->modify_time = date('Y-m-d H:i:s');
				if (! $zmfOrder->save())
				{
					$errorMsg = $zmfOrder->getFirstErrors();
					KafkaService::addLog('to300002-order-fail1:'.$contentC['orderId'], $errorMsg);
					return false;
				}
                                if($contentC['status'] == 2) {
                                    $status = 4;
                                    $ticketId = $contentC['tickSn'];
                                } elseif ($contentC['status'] = '-1') {
                                    $status = 5;
                                    $ticketId = '';
                                }
                                OrderDeal::thirdCall($zmfOrder->order_code, $status, $ticketId);
			}			
		}
		return true;
    }

    /**
     * 说明:封装智魔方接收回调返回
     * @author chenqiwei
     * @date 2018/1/12 下午3:28
     * @param
     * @return
     */
    public function retzmf1101($data) {
        $dataXml = $this->formatxml($data);
        $key = \Yii::$app->params['zmf_key'];
        $desModel = new Des($key, $this->iv);
        $desData = $desModel->encrypt($dataXml); //des3加密
        //post消息体封装
        $head = ['head' => ['messageId' => 1500, 'result' => 0]];
        $head['head']['md'] = md5($desData);
        $head['body'] = $desData;
        $head = $this->formatxml($head, 'message');
        //提交请求
//        $postRet =$this->xmlpost($head);
//        $xmlret= $this->xmlToArray($postRet);
        return $head;
    }

    /**
     * 说明: 1001 查询期信息接口
     * @author  kevi
     * @date 2018年1月5日 下午4:41:04
     * @param
     * @return
     */
    public function to1002($data) {
        $dataXml = $this->formatxml($data);
        $key = \Yii::$app->params['zmf_key'];
        $desModel = new Des($key, $this->iv);
        $desData = $desModel->encrypt($dataXml); //des3加密
        ob_clean();
//        print_r($desData);die;
        //post消息体封装
        $venderId = \Yii::$app->params['zmf_venderId'];
        $head = ['head' => ['version' => 1500, 'command' => 1019, 'venderId' => $venderId, 'messageId' => $data['messageId']]];
        $head['head']['md'] = md5($desData);
        $head['body'] = $desData;
        $head = $this->formatxml($head, 'message');
        //提交请求
        $postRet = $this->xmlpost($head);
        $ret = $this->xmlToArray($postRet);
        $ticketId = '';

//        if ($ret['head']['result'] == 0) {
//            $xmlret = $desModel->decrypt($ret['body']);
//            $ret = $this->xmlToArray($xmlret);
//            $ticketId = $ret['records']['record']['ticketId'];
//            $status = 4;
//        } else {
//            $status = 5;
//        }
        return $ret;
    }

    public function to200100() {
    	$content=['apiCode'=>"200100",'content'=>[]];
    	$post=$this->formatPost($content);
    	$rs=\yii::curlJsonPost(\Yii::$app->params['jw_url'], json_encode($post));
    	echo 111;die;
    	if(!$rs['error']){
    		$rsArr=json_decode($rs['data'],true);
    		$content=$rsArr['content'];
    		$key = \Yii::$app->params['jw_key'];
    		$desModel = new Des($key,'12345678');
    		$content=$desModel->decrypt($content);
    		return json_decode($content,true)['balance'];
    	}
    	return false;
    }
    public function formatPost($data){
    	$key = \Yii::$app->params['jw_key'];
    	$post=['apiCode'=>$data['apiCode']];
    	$desModel = new Des($key,'12345678');
    	$post['content']=$desModel->encrypt(json_encode($data['content']));
    	$post['version']=$this->version;
    	$post['partnerId']=\Yii::$app->params['jw_venderId'];
    	$post['messageId']=time().mt_rand(0,9999);
    	$post['hmac']=Jw::hmac($post['apiCode'].$post['content'].$post['messageId'].$post['partnerId'].$post['version'],substr($key, 0,16));
    	return $post;
    }

//    public function toxml($data2, $id) {
//        $dataXml = $this->formatxml($data2);
//        $key = \Yii::$app->params['zmf_key'];
//        $desModel = new Des($key, $this->iv);
//        $desData = $desModel->encrypt($dataXml); //des3加密
//        //post消息体封装
//        $head = ['head' => ['version' => 1500, 'command' => 1101, 'venderId' => 20170102, 'messageId' => $id]];
//        $head['head']['md'] = md5($desData);
//        $head['body'] = $desData;
//        $ret = $this->formatxml($head, 'message');
//        return $ret;
//    }
//
//    public function toxml2($data) {
//        $dataXml = $this->formatxml($data2);
//        $key = \Yii::$app->params['zmf_key'];
//        $desModel = new Des($key, $this->iv);
//        $desData = $desModel->encrypt($dataXml); //des3加密
//        //post消息体封装
//        $head = ['head' => ['version' => 1500, 'command' => 1101, 'venderId' => 20170102, 'messageId' => $id]];
//        $head['head']['md'] = md5($desData);
//        $head['body'] = $desData;
//        $ret = $this->formatxml($head, 'message');
//        return $ret;
//    }

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
        $url = 'http://123.56.233.30/b2b/bet';
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

    public function zmfencrypt($data) {
        $key = \Yii::$app->params['zmf_key'];
        $desModel = new Des($key, '12345678');
        $jmxit = $desModel->encrypt($data);
        return $jmxit;
    }
    public static function getMillisecond(){
    		list($t1, $t2) = explode(' ', microtime());
    		return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }
    /**
     * 基于md5的加密算法hmac
     * @param String $data 预加密数据
     * @param String $key  密钥
     * @return String
     */
    public static function hmac($data, $key){
    	if (function_exists('hash_hmac')) {
    		return hash_hmac('md5', $data, $key);
    	}
    
    	$key = (strlen($key) > 64) ? pack('H32', 'md5') : str_pad($key, 64, chr(0));
    	$ipad = substr($key,0, 64) ^ str_repeat(chr(0x36), 64);
    	$opad = substr($key,0, 64) ^ str_repeat(chr(0x5C), 64);
    	return md5($opad.pack('H32', md5($ipad.$data)));
    }
    /**
     * 说明: 查询期信息接口
     * @param
     * @return
     */
    public function to200009Data($orderIds) {
    	$data=['apiCode'=>"200009"];
    	$key='orderId';
    	$orderData=[];
    	$orderIdArr=explode(',', $orderIds);
    	foreach ($orderIdArr as $v){
    		$orderData[][$key]=$v;
    	}
    	$data['content']['orderList'] =$orderData;
    	$post=$this->formatPost($data);
    	$rs=\yii::curlJsonPost(\Yii::$app->params['jw_url'], json_encode($post));
    	if(!$rs['error']){
    		$rsArr=json_decode($rs['data'],true);
    		$content=$rsArr['content'];
    		$key = \Yii::$app->params['jw_key'];
    		$desModel = new Des($key,'12345678');
    		$content=$desModel->decrypt($content);
    		$contentArr=json_decode($content,true);
    		return $contentArr['resultList'];
    	}
    	return false;
    }

}
