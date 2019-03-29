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
class SmsTool {

    /**
     * 说明: 发送短信验证码
     * @author  kevi
     * @date 2017年8月10日 上午11:36:09
     * @param not null $cType   1:注册专用 4:其他
     * @param not null $saveKey redis保存的key,用途分类
     * @param not null $userTel 手机号
     * @return 
     */
    public function sendSmsCode($cType,$saveKey,$userTel){
        $nums = $this->getRandomNum(6);
        $ret = $this->sendSms($userTel, '本次操作验证码：'.$nums.',如非本人操作，请忽略。');
        if ($ret) {
            \Yii::redisSet("{$saveKey}:{$userTel}", $nums, 600);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 说明: 获取本地存储（redis）短信码
     * @author  kevi
     * @date 2017年8月8日 下午1:51:53
     * @param not null $saveKey redis保存的key,用途分类
     * @param not null $userTel 手机号
     * @param not null $smsCode   
     * @param null $isReturn 如果验证错误时是否返回
     * @return smscode
     */
    public static function check_code($saveKey,$userTel, $smsCode,$isReturn=0) {
        $redis = \yii::$app->redis;
        $data = $redis->executeCommand('get', ["{$saveKey}:{$userTel}"]);
        if (empty($data)) {
            if ($isReturn==1){return false;}
            \Yii::jsonError(409, "验证码已过期，请重新发送");
        }else if($data != $smsCode){
            if ($isReturn==1){return false;}
            \Yii::jsonError(410, '验证码错误,请重新输入');
        }
        return $data;
    }
    
    /**
     * 说明: 
     * @author  kevi
     * @date 2017年8月16日 上午11:52:38
     * @param   not null $phones   手机号
     * @param   not null $content   短信内容
     * @return 
     */
    public function sendSms($phones,$content){
        $data = [
            'account' => 'dh29841',
            'password' => md5('*!p0syW4'),
            'phones' => $phones,
            'content' => $content,
            'sign' => '【咕啦体育】',
            'sendtime' => date('YmdHi'),
        ];
        $data = json_encode($data);
        $surl = 'wt.3tong.net/json/sms/Submit';
        $ret = \Yii::sendCurlPost($surl, $data);
        if($ret['result']!=0){
            \Yii::jsonError(100, '发送失败'.$ret['desc']);
        }
        return $ret;
    }
    
/*
     * 生成随机数字串
     * $len 长度
     * */
    private function getRandomNum($len, $chars=null)
    {
        if (is_null($chars)){
            $chars = "0123456789";
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }
    
}
