<?php

/*
 * 普通工具类
 */

namespace app\modules\tools\helpers;

/**
 * 说明 ：工具类
 * @author  kevi
 * @date 2017年7月6日 下午1:41:34
 */
class Toolfun {

    public static function getUserIp() {
        $unknown = 'unknown';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] &&
                strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        /*
          处理多层代理的情况
          或者使用正则方式：$ip = preg_match("/[d.]
          {7,15}/", $ip, $matches) ? $matches[0] : $unknown;
         */
        if (false !== strpos($ip, ','))
            $ip = reset(explode(',', $ip));
        return $ip;
    }

    /**
     * 高德地图地理位数上传
     * @auther GL zyl
     * @param type $storeData
     * @return type
     */
    public static function setLbsAddress($storeData) {
        $surl = \Yii::$app->params['test_amap_create'];
        $key = \Yii::$app->params['test_amap_key'];
        $tableid = \Yii::$app->params['test_amap_tableid'];
        $loctype = \Yii::$app->params['test_amap_loctype'];
        $address = $storeData['province'] . $storeData['city'] . $storeData['area'] . $storeData['address'];
        $data = ['_name' => $storeData['store_name'], '_img' => $storeData['store_img'], '_address' => $address, '_location' => $storeData['coordinate'], 'telephone' => $storeData['phone_num'], 'store_code' => $storeData['store_code']];
        $jsonData = json_encode($data);
//        print_r($data);die;
        $postData = ['key' => $key, 'tableid' => $tableid, 'loctype' => $loctype, 'data' => $jsonData];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * 高德地图地理位置更新
     * @auther GL zyl
     * @param type $storeData
     * @return type
     */
    public static function updateLbsAddress($storeData) {
        $surl = \Yii::$app->params['test_amap_update'];
        $key = \Yii::$app->params['test_amap_key'];
        $tableid = \Yii::$app->params['test_amap_tableid'];
        $loctype = \Yii::$app->params['test_amap_loctype'];
        $address = $storeData['province'] . $storeData['city'] . $storeData['area'] . $storeData['address'];
        $data = ['_id' => $storeData['amap_id'], '_name' => $storeData['store_name'], '_img' => $storeData['store_img'], '_address' => $address, '_location' => $storeData['coordinate'], 'telephone' => $storeData['phone_num'], 'store_code' => $storeData['store_code']];
        $jsonData = json_encode($data);
//        print_r($data);die;
        $postData = ['key' => $key, 'tableid' => $tableid, 'loctype' => $loctype, 'data' => $jsonData];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }
    
    /**
     * 说明: 根据两点，算出距离
     * @author  kevi
     * @date 2017年12月14日 下午4:48:32
     * @param   $point1 经纬度1
     * @param   $point2 经纬度2
     * @param   $km 是否是千米单位
     * @return 
     */
    public static function getDistance($lat1,$lng1,$lat2,$lng2,$km=true){
        
        $pi80 = M_PI / 180;
        $lat1 *= $pi80;
        $lng1 *= $pi80;
        $lat2 *= $pi80;
        $lng2 *= $pi80;
        $r = 6372.797; // mean radius of Earth in km
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        $a = sin($dlat/2)*sin($dlat/2)+cos($lat1)*cos($lat2)*sin($dlng/2)*sin($dlng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $long = $r * $c ;
        return ($km ? $long : $long * 1000);
    }
}
