<?php

namespace app\modules\common\services;

use app\modules\common\helpers\Constants;

class SyncApiRequestService {

    public static function getBaseUrl() {
        return \Yii::$app->params['backup_sqlserver'];
    }

    /**
     * 验证对奖对接
     * @param type $lotAbb
     * @param type $retData
     * @return type
     */
    public static function awardLottery($lotteyCode, $retData) {
        //获取路由
        $sendUrl = self::getBaseUrl() . 'check';
        $lotteyAbb = Constants::SYNC_LOTTERY_ABB;
        $data['check_type'] = $lotteyAbb[$lotteyCode];
        $data = array_merge($data, $retData);
        $jsonData = json_encode($data);
        $ret = \Yii::sendCurlPost($sendUrl, $jsonData);
        return $ret;
    }

    public static function getWinAmount($orderCode) {
        $resquestApi = self::getBaseUrl() . 'order/get_win_amount';
        $data = json_encode(['lottery_order_code' => $orderCode]);
        $curl_ret = \Yii::sendCurlPost($resquestApi, $data);
        if ($curl_ret['code'] == 600) {
            return $curl_ret;
        }
        KafkaService::addLog('sync-error:' . $api, $argv);
        return false;
    }
    public static function getWithdrawalAmount($cusNo) {
    	$resquestApi = self::getBaseUrl() . 'user/get_tx_amount';
    	$data = json_encode(['cust_no' => $cusNo]);
    	$curl_ret = \Yii::sendCurlPost($resquestApi, $data);
    	if ($curl_ret['code'] == 600) {
    		return $curl_ret;
    	}
    	KafkaService::addLog('getWithdrawalAmount-error:' . $resquestApi, $data);
    	return false;
    }
    
    /**
     * 取消赛程相关订单赔率修改
     * @param type $oddsData  相关详情单赔率
     * @return type
     */
    public static function updateCancelOdds($oddsData) {
        $sendUrl = self::getBaseUrl() . 'order/update_odds4cancel';
        $jsonData = json_encode($oddsData);
        $ret = \Yii::sendCurlPost($sendUrl, $jsonData);
        return $ret;
    }
    public static function getTotalAmount($cusNo) {
    	$resquestApi = self::getBaseUrl() . 'user/get_all_funds';
    	$data = json_encode(['cust_no' => $cusNo]);
    	$curl_ret = \Yii::sendCurlPost($resquestApi, $data);
    	if ($curl_ret['code'] == 600) {
    		return $curl_ret;
    	}
    	KafkaService::addLog('getTotalAmount-error:' . $resquestApi, $data);
    	return false;
    }
    

}
