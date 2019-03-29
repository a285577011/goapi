<?php
namespace app\modules\user\controllers;

use app\modules\user\models\RedeemCode;
use app\modules\user\models\RedeemRecord;
use app\modules\user\models\UserGrowthRecord;
use yii\web\Controller;
use app\modules\user\services\IUserService;
use app\modules\user\models\User;
use app\modules\user\services\IThirduserService;
use app\modules\user\helpers\WechatTool;
use app\modules\common\helpers\Constants;
use app\modules\tools\helpers\SmsTool;
use app\modules\store\models\Store;
use app\modules\store\services\StoreService;

/**
 * 说明 ：微信公众号控制器
 * @author  kevi
 * @date 2017年8月4日 上午9:48:44
 */

class WechatController extends Controller
{
    private $appId;
    private $appSecret;
    private $userService;
    private $thirdService;
    
    public function __construct($id, $module, $config = [] , IThirduserService $thirdService , IUserService $userService)
    {
        $this->appId = \Yii::$app->params['wechat']['appid'];
        $this->appSecret = \Yii::$app->params['wechat']['appsecret'];
        $this->userService = $userService;
        $this->thirdService = $thirdService;
        parent::__construct($id,$module,$config);
    }
      
    
    /**
     * 说明: 微信公众号回调地址
     * @author  kevi
     * @date 2017年8月8日 上午9:54:31
     * @param  code not null 
     * @param  state not null 
     * @param  uid_source  null 
     * @return 
     */
   
    public function actionWechatCallback(){
        $code = $_GET["code"];
        $state = $_GET["state"];
        $this->wechatLogin($code, $state);
    }
    
    /**
     * 说明: 微信授权登录 ：获取access_token、openId等；获取并保存用户资料到数据库
     * @author  kevi
     * @date 2017年8月8日 上午9:55:04
     * @param
     * @return 
     */
    public function wechatLogin($code,$state)
    {
        //获取OAuth access_token
        $wechatTool = new WechatTool();
        $result = $wechatTool->getOauthAccessToken($this->appId, $this->appSecret,$code);
        $access_token = $result['access_token'];//授权token
        $openId = $result['openid'];
        
        //请求微信接口，获取用户信息
        $userInfo = $wechatTool->getUserInfo($access_token,$openId);
        $data =array(
            'union_id' => $userInfo['unionid'],
            'third_uid' => $userInfo['openid'],
            'type' => 1,  //1-微信公众号 2-QQ 3-微信 4-微博
            'icon' => $userInfo['headimgurl'],
            'nickname' => $userInfo['nickname'],
            'sex' => $userInfo['sex'],
        );
        $thirdUser = $this->thirdService->saveThirdUser($data);
        if(empty($thirdUser['uid'])){//如果未绑定
            return $this->redirect(\Yii::$app->params['userDomain'].'/login/bounding?openid='.$data['third_uid'].'&type='.$state);
        }else{
            $user = User::find()->select(['user_id','cust_no','user_type'])->where(['user_id'=>$thirdUser['uid']])->asArray()->one();
            $redis = \Yii::$app->redis;
            $redis->database = 1;
            $token = $redis->executeCommand('get', ["user_token:{$user['cust_no']}"]);
            if(empty($token)){
                $token = $this->userService->autoLogin($user['cust_no'], $user['user_id']);//自动登录
            }
            if($state==2){//彩店入驻
                if($user['user_type']==1){
                    return $this->redirect(\Yii::$app->params['userDomain'].'/store/toCertification?token='.$token);
                }
                return $this->redirect(\Yii::$app->params['userDomain'].'/store/certification?token='.$token);
            }else if($state==1){//绑定手机
                return $this->redirect(\Yii::$app->params['userDomain'] . '/activity/download-app');
            }else if($state==3){//分享时授权
                return $this->redirect(\Yii::$app->params['userDomain'] .'/pay/5?token='.$token);
            }elseif ($state == 4) {//合买分享
                return $this->redirect(\Yii::$app->params['userDomain'] . '/pay/2?token=' . $token);
            }
            return $this->redirect(\Yii::$app->params['userDomain'].'/jc/jz?isAuth=1&token='.$token);
        }
    }
    
    /**
     * 说明: 检查手机号是否注册过
     * @author  kevi
     * @date 2017年8月4日 上午11:56:21
     * @param  not null user_tel 手机号
     * @return 
     */
    public function actionIsUser(){
        $request =\Yii::$app->request;    
        $userTel = $request->post('user_tel');
        if(empty($userTel)){
            return $this->jsonError(100, '手机号不能为空');
        }
        $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
        if($javaUser['httpCode']==200){
            return $this->jsonResult(600, '已注册号码', 1);
        }elseif($javaUser['httpCode']==412){
            return $this->jsonResult(600, '未注册号码', 0);
        }else{
            return $this->jsonError(401, '系统错误,请稍后再试');
        }
    }
    
    /**
     * 说明: 微信绑定系统用户
     * @author  kevi
     * @date 2017年8月4日 下午2:29:09
     * @param not null account 手机号
     * @param not null openid 第三方id
     * @param not null smsCode 短信验证码
     * @param null password 新用户密码
     * @return 
     */
    public function actionBoundingUser(){
        $request =\Yii::$app->request;
        $userTel = $request->post('account');
        $openId = $request->post('openid');
        $smsCode = $request->post('smsCode');
        $saveKey = Constants::SMS_KEY_WX_BOUNDING;
        SmsTool::check_code($saveKey, $userTel, $smsCode);

        $user = User::find()->select(['user_id','cust_no','user_type'])->where(['user_tel'=>$userTel])->asArray()->one();
        if(!$user){//php 用户不存在
            $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
            if($javaUser['httpCode']==200){//已注册
                $result = $this->userService->setRegisterData($javaUser);
                $result['register_from'] = 2;
                $userjava = $this->userService->createOrUpdateUser($userTel, $javaUser['data']['account'], $result);
                $user = $userjava['data'];
            }elseif($javaUser['httpCode']==412){
                $javaUser = $this->userService->javaRegister($userTel, '888888');
                if ($javaUser['httpCode'] == 200) {
                    $javaUser = $this->userService->getJavaUserDetailByTel($userTel);//java 根据手机号获取用户信息接口
                    if($javaUser['httpCode'] ==200){
                        $result = $this->userService->setRegisterData($javaUser);
                        $result['register_from'] = 4;
                        $userjava = $this->userService->createOrUpdateUser($userTel, $javaUser['data']['account'], $result);
                        $user = $userjava['data'];
                    }else{
                        return $this->jsonError(490, '绑定失败,请稍候重试');
                    }
                }else{//java接口请求失败
                    return $this->jsonError($javaUser['httpCode'], $javaUser['msg']);
                }
            }else{
                return $this->jsonError(401, '系统错误,请稍后再试');
            }
        }

        $fret = $this->thirdService->thirdUserBound($openId, $user['user_id'],1);//绑定过程
        if($fret['code']!=600){
            return $this->jsonError(491, '绑定失败');
        }
        $token = $this->userService->autoLogin($user['cust_no'],$user['user_id']);//自动登录
        //绑定赠送成长值
        $UserGrowth = new UserGrowthRecord();
        $UserGrowth -> updateGrowth($user['cust_no'], '', 8);
        return $this->jsonResult(600, '绑定成功', ['token' => $token,'user_type'=>$user['user_type']]);
    }
    
    
    /**
     * 说明: 微信绑定系统彩店用户
     * @author  kevi
     * @date 2017年8月4日 下午2:29:09
     * @param not null account 手机号
     * @param not null openid 第三方id
     * @param not null smsCode 短信验证码
     * @return
     */
    public function actionBoundingStore(){
        $request =\Yii::$app->request;
        $userTel = $request->post('account');
        $openId = $request->post('openid');
        $smsCode = $request->post('smsCode');
        $saveKey = Constants::SMS_KEY_WX_BOUNDING;
        $smsRet = SmsTool::check_code($saveKey, $userTel, $smsCode);
        $store = Store::find()->select(['store_id','cust_no'])->where(['phone_num'=>$userTel])->asArray()->one();
        if(!empty($store)){//已注册用户,直接绑定
            $fret = $this->thirdService->thirdUserBound($openId, $store['store_id'],2);//绑定过程
            if($fret['code']!=600){
                return $this->jsonError(490, '绑定失败');
            }
            $token = $this->userService->autoLogin($store['cust_no'],$store['store_id'],'store');//自动登录
            return $this->jsonResult(600, '绑定成功', ['token' => $token]);
        }else{//未注册，去注册
            $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
            if($javaUser['httpCode'] == 412){//注册系统账号
                return $this->jsonError(413, '未注册号码,请先注册');
            }
            $storeService = new StoreService();
            $store = $storeService->createOrUpdateUser($userTel, $javaUser['data']['account'], $javaUser);
            $token = $this->userService->autoLogin($store['cust_no'],$store['store_id'],'store');//自动登录
            return $this->jsonResult(600, '绑定成功', ['token' => $token]);
        }
    }
    
    
    /**
     * 说明: 发送绑定-短信验证码
     * @author  kevi
     * @date 2017年8月8日 下午1:53:25
     * @param
     * @return 
     */
    public function actionSendSmsCodeWxbounding(){
        $request = \Yii::$app->request;
        $userTel = $request->post('account');
        $cType = $request->post('cType',4); //1:注册 4:修改密码
        if (empty($userTel)) {
            return $this->jsonError(100, '参数缺失');
        }
        $saveKey = Constants::SMS_KEY_WX_BOUNDING;
        $smstool = new SmsTool();
        $ret = $smstool->sendSmsCode($cType,$saveKey,$userTel);
        if ($ret) {
            return $this->jsonResult(600, '发送成功', true);
        }
    }

    /**
     * 说明: 发送更换绑定手机-短信验证码
     * @author  gwp
     * @date 2018年01月09日
     * @param
     * @return
     */
    public function actionSendSmsCodeChange(){
        $request = \Yii::$app->request;
        $userTel = $request->post('tel');
        $cType = $request->post('cType',4); //1:注册 4:其他
        if (empty($userTel)) {
            return $this->jsonResult(100, '参数缺失', 2);
        }
        $saveKey = Constants::SMS_KEY_WX_CHANGE_BOUNDING;
        $smstool = new SmsTool();
        $ret = $smstool->sendSmsCode($cType,$saveKey,$userTel);
        if ($ret) {
            return $this->jsonResult(600, '发送成功', 1);
        }
    }
    
    
    /**
     * 说明: 获取 微信接口调用前的 配置
     * @author  kevi
     * @date 2017年8月10日 上午8:59:31
     * @param url  null 接口完整地址
     * @param token not null 登录token
     * @param userAgent null 用户来源系统 1:android 2:ios
     * @return 
     */
    public function actionGetSysConfig(){
        $request = \Yii::$app->request;
        $url = $request->post('url');
        $token = $request->post('token');
        $wechatTool = new WechatTool();
        $config = $wechatTool->getSignPackage($url);
        return $this->jsonResult(600, '获取成功', $config);
    }




    public function actionStoreGetCode(){
        $request = \Yii::$app->request;
        $storeCode = $request->get('store_code');
        $storeCode = $storeCode?$storeCode:12;
        $appId = 'wx082d0083da789f0d';
        $redirect_uri = \Yii::$app->params['userDomain'].'/api/user/wechat/store-callback';
        $url ="https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appId}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state={$storeCode}#wechat_redirect" ;
        return $this->redirect($url);
    }

    public function actionStoreCallback(){
        $code = $_GET["code"];
        $state = $_GET["state"];
        $this->getOpenId($code, $state);
    }

    public function getOpenId($code,$state){

        $appId = 'wx082d0083da789f0d';
        $appSecret ='9a107c54e1ff043f4a66cf08d321a68f';
        //获取OAuth access_token
        $wechatTool = new WechatTool();
        $result = $wechatTool->getOauthAccessToken($appId, $appSecret,$code);
//        $access_token = $result['access_token'];//授权token
        //请求微信接口，获取用户信息
//        $userInfo = $wechatTool->getUserInfo($access_token,$result['openid']);

//        print_r($userInfo);die;

//        $data =array(
//            'union_id' => $userInfo['unionid'],
//            'third_uid' => $userInfo['openid'],
//            'type' => 1,  //1-微信公众号 2-QQ 3-微信 4-微博
//            'icon' => $userInfo['headimgurl'],
//            'nickname' => $userInfo['nickname'],
//            'sex' => $userInfo['sex'],
//        );
        if(empty($result['openid'])){
            $msg = '扫码失败，请重新扫码';
            if($state==12){
                $msg = '获取兑换码失败，请退出重试';
            }
            return $this->jsonError(107,$msg);
        }
        if($state==12){
            //跳转获取兑换码页面
            $url = \Yii::$app->params['userDomain'].'/user/spread/exchangeCode/'.$result['openid'];
            return $this->redirect($url);
        }
        $redeemRecord = RedeemRecord::find()->where(['open_id'=>$result['openid']])->one();
        if(!$redeemRecord){
            $redeemRecord = new RedeemRecord();
            $redeemRecord->store_code = $state;
            $redeemRecord->open_id = $result['openid'];
            $redeemRecord->create_time = date('Y-m-d H:i:s');
            $redeemRecord->status = 0;
            $redeemRecord->save();
        }
        $url =\Yii::$app->params['userDomain'].'/user/spread/wx_dingyue';
        return $this->redirect($url);
    }
}