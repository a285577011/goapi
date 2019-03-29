<?php

namespace app\modules\tools\helpers;

use app\modules\orders\models\ZmfOrder;
use app\modules\tools\helpers\Des;
use app\modules\orders\models\AutoOutOrder;
use app\modules\common\services\KafkaService;
use app\modules\orders\helpers\OrderDeal;

class Zmf {

//    private $venderId = '18020601'; //销售商代码
//    private $url = 'http://120.77.204.131:8098/'; //智魔方地址
//    private $key = '56B06065B5237AF34DBBCBF8'; //密钥
    private $iv = '12345678'; //IV 向量
    private $version = '1500'; //版本号

    public function getHead($command) {
        $venderId = \Yii::$app->params['zmf_venderId'];
        $messageId = $venderId . $command . date('ymdHis') . floor(floatval(microtime()) * 1000) . rand(0, 1000);
        $head = ['head' => ['version' => $this->version, 'command' => $command, 'venderId' => $venderId, 'messageId' => $messageId]];
        return $head;
    }

    /**
     * 说明: 1000 提交投注记录接口 
     * @author  kevi
     * @date 2018年1月5日 下午4:41:04
     * @param
     * @return 
     */
    public function to1000($data) {
        $command = 1000;
        $dataXml = $this->formatxml($data);
        $key = \Yii::$app->params['zmf_key'];
        $desModel = new Des($key, $this->iv);
        $desData = $desModel->encrypt($dataXml); //des3加密
        //post消息体封装
        $head = $this->getHead($command); //获取公共head
        $head['head']['md'] = md5($desData);
        $head['body'] = $desData;
        $mxlHead = $this->formatxml($head, 'message');
        //提交请求
        $postRet = $this->xmlpost($mxlHead);
        $ret = $this->xmlToArray($postRet);
        $status = NULL;
        if ($ret['head']['result'] == 0) {
            $status = 0;
            $xmlret = $desModel->decrypt($ret['body']);
            $ret['body'] = $this->xmlToArray($xmlret);
        }
        //写日志
        $zmfOrder = ZmfOrder::find()->where(['order_code' => $data['records']['record']['id']])->andWhere('status is not null')->one();
        if (!empty($zmfOrder)) {
            return '下单失败，重复订单！！！';
        }
        $zmfOrder = new ZmfOrder();
        $zmfOrder->order_code = $data['records']['record']['id'];
        $zmfOrder->version = $this->version;
        $zmfOrder->command = $command;
        $zmfOrder->messageId = $head['head']['messageId'];
        $zmfOrder->bet_val = json_encode($data);
        $zmfOrder->status = $status;
        $zmfOrder->ret_sync_data = json_encode($ret);
        $zmfOrder->create_time = date('Y-m-d H:i:s');
        if (!$zmfOrder->save()) {
            $errorMsg = $zmfOrder->errors;
            KafkaService::addLog('zmforder1000', $errorMsg);
            return \Yii::jsonError(100, $errorMsg);
        }
        return $ret;
    }

    /**
     * 说明: 1001 查询期信息接口
     * @author  kevi
     * @date 2018年1月5日 下午4:41:04
     * @param
     * @return
     */
    public function to1019($data) {
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

        if ($ret['head']['result'] == 0) {
            $xmlret = $desModel->decrypt($ret['body']);
            $ret = $this->xmlToArray($xmlret);
            $ticketId = $ret['records']['record']['ticketId'];
            if ($ret['records']['record']['result'] == 0 && !empty($ticketId)) {
                $status = 4;
            } elseif ($ret['records']['record']['result'] == '200021') {
                $status = 2;
            } else {
                $status = 5;
            }
        }elseif ($ret['head']['result'] == 200021) {
            $status = 2;
        }  else {
            $status = 5;
        }
        //写入日志
        $zmfOrder = ZmfOrder::find()->where(['messageId' => $data['messageId']])->andWhere('status !=1')->one();
        if (!empty($zmfOrder)) {
            $zmfOrder->ret_async_data = json_encode($ret);
//            $zmfOrder->status = $status;
            if($status ==4 ){
                $zmfOrder->status = 2;
            }
            $zmfOrder->modify_time = date('Y-m-d H:i:s');
            if (!$zmfOrder->save()) {
                $errorMsg = $zmfOrder->errors;
                \Yii::jsonError(400, $errorMsg);
            }
            $autoOutOrder = AutoOutOrder::findOne(['out_order_code' => $zmfOrder->order_code, 'status' => 2]);
            if (empty($autoOutOrder)) {
                return \Yii::jsonError(400, '订单不存在');
            }
            $autoOutOrder->ticket_code = $ticketId;
            $autoOutOrder->status = $status;
            $autoOutOrder->modify_time = date('Y-m-d H:i:s');
            if (!$autoOutOrder->save()) {
                $errorMsg = $autoOutOrder->errors;
                return \Yii::jsonError(400, $errorMsg);
            }
            KafkaService::addQue('ConfirmOutTicket', ['orderCode' => $autoOutOrder->order_code], true);
        }
        return $ret;
    }

    public function to1101($paramsXml) {
        $paramsArr = $this->xmlToArray($paramsXml);
        //des3解密
        $key = \Yii::$app->params['zmf_key'];
        $desModel = new Des($key, $this->iv);
        $desData = $desModel->decrypt($paramsArr['body']);

        $body = $this->xmlToArray($desData);
        $messageId = $body['messageId'];
        $ticketId = '';
        if ($body['result'] == 0) {
            $ticketId = $body['records']['record']['ticketId'];
            if ($ticketId != '') {
                $status = 4;
            } else {
                $status = 5;
            }
        } elseif ($body['result'] == '200021') {
            $status = 2;
        } else {
            $status = 5;
        }


        //写日志
        $zmfOrder = ZmfOrder::find()->where(['order_code' => $body['records']['record']['id']])->one();
        if (!empty($zmfOrder)) {
            $zmfOrder->ret_async_data = json_encode($body);
            $zmfOrder->status = 1;
            $zmfOrder->modify_time = date('Y-m-d H:i:s');
            if (!$zmfOrder->save()) {
                $errorMsg = $zmfOrder->getFirstErrors();
                $data2 = [
                    'records' => [
                        'record' => [
                            'id' => $body['records']['record']['id'],
                            'result' => 0,
                        ]
                    ]
                ];
                return ['data' => $data2, 'messageId' => $messageId];
            }
            $autoOutOrder = AutoOutOrder::findOne(['out_order_code' => $zmfOrder->order_code, 'status' => 2]);
            if (empty($autoOutOrder)) {
                $data2 = [
                    'records' => [
                        'record' => [
                            'id' => $body['records']['record']['id'],
                            'result' => 0,
                        ]
                    ]
                ];
                return ['data' => $data2, 'messageId' => $messageId];
            }
            $autoOutOrder->ticket_code = $ticketId;
            $autoOutOrder->status = $status;
            $autoOutOrder->modify_time = date('Y-m-d H:i:s');
            if (!$autoOutOrder->save()) {
                $errorMsg = $autoOutOrder->getFirstErrors();
                $data2 = [
                    'records' => [
                        'record' => [
                            'id' => $body['records']['record']['id'],
                            'result' => 0,
                        ]
                    ]
                ];
                return ['data' => $data2, 'messageId' => $messageId];
            }
            OrderDeal::confirmOutTicket($autoOutOrder->order_code);
            
            
            KafkaService::addQue('ConfirmOutTicket', ['orderCode' => $autoOutOrder->order_code], true);
        }

        $data2 = [
            'records' => [
                'record' => [
                    'id' => $body['records']['record']['id'],
                    'result' => 0,
                ]
            ]
        ];
        return ['data' => $data2, 'messageId' => $messageId];
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

    public function to11011($data) {
        $dataXml = $this->formatxml($data);
        $key = \Yii::$app->params['zmf_key'];
        $desModel = new Des($key, $this->iv);
        $desData = $desModel->encrypt($dataXml); //des3加密
        //post消息体封装
        $head = ['head' => ['messageId' => 15002, 'result' => 0]];
        $head['head']['md'] = md5($desData);
        $head['body'] = $desData;
        $head = $this->formatxml($head, 'message');
        //提交请求
//        $postRet =$this->xmlpost($head);
//        $xmlret= $this->xmlToArray($postRet);
        return $head;
    }

    public function toxml($data2, $id) {
        $dataXml = $this->formatxml($data2);
        $key = \Yii::$app->params['zmf_key'];
        $desModel = new Des($key, $this->iv);
        $desData = $desModel->encrypt($dataXml); //des3加密
        //post消息体封装
        $head = ['head' => ['version' => 1500, 'command' => 1101, 'venderId' => 20170102, 'messageId' => $id]];
        $head['head']['md'] = md5($desData);
        $head['body'] = $desData;
        $ret = $this->formatxml($head, 'message');
        return $ret;
    }

    public function toxml2($data) {
        $dataXml = $this->formatxml($data2);
        $key = \Yii::$app->params['zmf_key'];
        $desModel = new Des($key, $this->iv);
        $desData = $desModel->encrypt($dataXml); //des3加密
        //post消息体封装
        $head = ['head' => ['version' => 1500, 'command' => 1101, 'venderId' => 20170102, 'messageId' => $id]];
        $head['head']['md'] = md5($desData);
        $head['body'] = $desData;
        $ret = $this->formatxml($head, 'message');
        return $ret;
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
            $itemx = $dom->createElement(is_string($key) ? $key : "item");
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
        $url = \Yii::$app->params['zmf_url'];
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

}
