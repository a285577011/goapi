<?php

/*
 * 文件上传工具类
 */

namespace app\modules\tools\helpers;

require \Yii::$app->basePath.'/vendor/qiniu/autoload.php';
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

/**
 * 说明 ：文件上传类
 * @author  kevi
 * @date 2017年7月6日 下午1:41:34
 */
class Uploadfile {

    /**
     * 说明: 上传文件到七牛
     * @author  kevi
     * @date 2017年7月6日 下午1:41:26
     * @param $pic 上传的文件
     * @param $key 文件保存位置与文件名
     * @return 
     */
    public static function qiniu_upload($pic,$key){
        if(!empty($pic)){
            $accessKey = \Yii::$app->params['qiniu_accessKey'];
            $secretKey = \Yii::$app->params['qiniu_secretKey'];
            //要上传的空间
            $bucket = \Yii::$app->params['qiniu_bucket'];
            //鉴权对象
            $auth = new Auth($accessKey,$secretKey);
            $uploadToken = $auth->uploadToken($bucket);
    
            $picture = \Yii::$app->params['qiniu_link_host'] . $key;
            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($uploadToken, $key, $pic);
            if ($err !== null) {
                return $err->getResponse()->error;
            }else{
                return  $picture;
            }
        }else{
            return 441;
        }
    }
    
    
    /**
     * 说明: 上传文件到七牛 (base64)
     * @author  kevi
     * @date 2017年7月6日 下午1:41:26
     * @param $pic 上传的文件
     * @param $key 文件保存位置与文件名
     * @return
     */
    public static function qiniu_upload_base64($pic,$key){
        if(!empty($pic)){
            $accessKey = \Yii::$app->params['qiniu_accessKey'];
            $secretKey = \Yii::$app->params['qiniu_secretKey'];
            //要上传的空间
            $bucket = \Yii::$app->params['qiniu_bucket'];
            //鉴权对象
            $auth = new Auth($accessKey,$secretKey);
            $uploadToken = $auth->uploadToken($bucket);
    
            $picture = \Yii::$app->params['qiniu_link_host'] . $key;
            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($uploadToken, $key, $pic);
            if ($err !== null) {
                return $err->getResponse()->error;
            }else{
                return  $picture;
            }
        }else{
            return 441;
        }
    }
    
    /**
     * 说明: 上传文件至图片服务器
     * @author  kevi
     * @date 2017年7月6日 下午1:35:51
     * @param $file
     * @return $saveDir
     */
    public static function pic_host_upload($file,$saveDir,$name = ''){
        header('content-type:text/html;charset=utf8');
        $ch = curl_init();
        $url = \Yii::$app->params["lottery_img_host"];
        //加@符号curl就会把它当成是文件上传处理
        $value = new \CURLFile($file['tmp_name']);
        $data = [
            'name' => rand(0,20),
            'save_dir' => $saveDir,
            'img'=>$value,
            'type' => 1
        ];
        if($name){
            $data['name'] = $name;
        }
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    /**
     * 上传图片到图片服务器
     * @author GL zyl
     * @param img $file
     * @param string $saveDir
     * @param string $name
     * @return $saveDir
     */
    public static function pic_host_upload_base64($file, $saveDir, $name){
        header('content-type:text/html;charset=utf8');
        $ch = curl_init();
        $url = \Yii::$app->params["lottery_img_host"];
        //加@符号curl就会把它当成是文件上传处理
        $data = [
            'name' => $name,
            'save_dir' => $saveDir,
            'file'=>$file,
            'type' => 2
        ];
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 图片上传格式判断
     * @auther GL zyl
     * @param type $file
     * @return string|int
     */
    public static function check_upload_pic($file){
        $typeArr = array('gif', 'jpg', 'jpeg', 'png');
        $result = [];
        if($file['name']){
            $name = $file['name'];
            $type = strtolower(substr($name,strrpos($name,'.')+1));
            if(!in_array($type, $typeArr)) {
                $result = ['code'=>440, 'msg' => '文件格式不正确'];
                return $result;
            }
            $result = ['code'=>600];
            return $result;
        }else{
            $result = ['code' => 441, 'msg' => '上传文件未找到'];
            return $result;
        }
    }
    
    /**
     * 上传图片到图片服务器
     * @param img $file
     * @param string $saveDir
     * @param string $name
     * @return $saveDir
     */
    public static function sysUploadImg($file, $saveDir, $name){
        header('content-type:text/html;charset=utf8');
        $ch = curl_init();
        $url = \Yii::$app->params["lottery_img_host"];
        //加@符号curl就会把它当成是文件上传处理
        $value = new \CURLFile($file);
        $data = [
            'name' => $name,
            'save_dir' => $saveDir,
            'img'=>$value,
            'type' => 1
        ];
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    
}
