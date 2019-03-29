<?php

namespace app\modules\agents\controllers;

use app\modules\agents\models\OpenId;
use app\modules\tools\helpers\SmsTool;
use app\modules\user\helpers\UserTool;
use Yii;
use yii\web\Controller;
use app\modules\user\models\User;
use app\modules\user\services\IUserService;
use app\modules\tools\helpers\Toolfun;
use app\modules\agents\models\Agents;
use app\modules\agents\models\AgentsIp;
use app\modules\agents\helpers\AgentsTool;
use app\modules\common\helpers\Constants;
use app\modules\user\models\CouponsDetail;

/**
 * 代理商控制器
 */
class AgentsController extends Controller {

    private $userService;

    public function __construct($id, $module, $config = [],IUserService $userService) {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }
    
    
    /**
     * 说明: 第三方用户登录
     * @author  kevi
     * @date 2017年11月14日 下午4:12:07
     * @param
     * @return 
     */
    public function actionPlatformUserLogin() {
        $request = \Yii::$app->request;
        $appId = $request->post_nn('appId');
        $accessToken = $request->post('access_token');
        if(empty($accessToken)){
            $accessToken = $request->post_nn('accessToken');
        }
        $userTel = $request->post('user_tel');
        if(empty($userTel)){
            $userTel = $request->post('userTel');
        }
        $cust_no = $request->post('gl_cust_no');
        if(empty($cust_no)){
            $cust_no = $request->post('custNo');
        }
        $manager_no = $request->post('manageNo');//中新业务经理编号

        $agents = $this->AuthCheck($appId,$accessToken);
        if($agents['agents_id'] == 12){//彩银通 不要用户注册，直接访问地址
            $ret = [ 'gl_url' =>$agents['to_url']];
            return $this->jsonResult(600, 'login success', $ret);
        }
        if(!empty($cust_no)){
            $phpUser = User::find()->where(['cust_no'=>$cust_no])->one();
        }else{
            if(empty($userTel)|| strlen($userTel)!=11){
                return $this->jsonError(100, '参数错误，手机号格式不正确');
            }
            $phpUser = User::find()->where(['user_tel'=>$userTel])->one();
        }
        if(!empty($phpUser)){//php中已经注册
            $cust_no = $phpUser->cust_no;
            $user_id = $phpUser->user_id;
        }else{
            $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
            if($javaUser['httpCode'] !=200){//java未注册,进行注册
                $javaRegUser = $this->userService->javaRegister($userTel, '888888');
                if ($javaRegUser['httpCode'] == 200) {
                    if ($javaRegUser['httpCode'] == 200) {
                        $javaUser['data']['account'] = $javaRegUser['data'];
                        $javaUser['data']['userName'] = $javaRegUser['data'];
                        $javaUser['data']['checkStatus'] = 0;
                    }else{
                        return $this->jsonError(490, '登录失败,注册未成功,请稍候重试');
                    }
                }else{
                    return $this->jsonError(490, '登录失败,注册未成功,请稍候重试');
                }
                $registerfrom = 5;
                $fromId = $agents['agents_id'];
            }else{
                $registerfrom =2;
            }
            //创建php用户
            $result = $this->userService->setRegisterData($javaUser);
            $result['register_from'] = $registerfrom;
            if($registerfrom !=2){//写这偷个懒 如果不是咕啦原有用户则挂上代理商信息
                $result['agent_code'] = $agents['agents_account'];
                $result['agent_name'] = $agents['agents_account'];
                $result['agent_id'] = $agents['agents_id'];
                $result['from_id'] = $fromId;
                $result['user_remark'] = $manager_no;
            }
            $userjava = $this->userService->createOrUpdateUser($userTel, $javaUser['data']['account'], $result);
            $cust_no = $userjava['data']['cust_no'];
            $user_id = $userjava['data']['user_id'];
        }
        $data = json_encode(['agents_id'=>$agents['agents_id'],'cust_no'=>$cust_no,'user_id'=>$user_id,'expert_time'=>time()+604800]);
        $key = 'GL_lottery';
        $agentsTool  = new AgentsTool();
        $platformToken= $agentsTool->encrypt($data, $key);//用户信息加密
        $gl_url = $agents['to_url'].'?third_app='.urlencode($platformToken.'|'.$agents['agents_code']);
        \Yii::tokenSet("platform_user_{$agents['agents_id']}:{$user_id}", "{$platformToken}");
        $ret = [
            'gl_cust_no' =>$cust_no,
            'gl_url' =>$gl_url,
        ];
        return $this->jsonResult(600, 'login success', $ret);
    }


    /**
     * 说明: 第三方用户登录（蒙发利，无用户手机号）
     * @author  kevi
     * @date 2017年11月14日 下午4:12:07
     * @param
     * @return
     */
    public function actionAgentUserLogin() {
        $request = \Yii::$app->request;
        $appId = $request->post_nn('appId');
        $accessToken = $request->post_nn('access_token');
        $userTel = $request->post('user_tel');
        $openId = $request->post('openId');
        $opType = $request->post('type');
        $lotteryCode = '&lottery_code='.$request->post('lottery_code');
        $seatNo = '_'.$request->post_nn('seat_no');//蒙发利座椅编号
        $agents = $this->AuthCheck($appId,$accessToken);//代理商身份校验
        if($userTel){
            if(strlen($userTel)!=11){
                return $this->jsonError(100, '参数错误，手机号格式不正确');
            }
            $phpUser = User::find()->where(['user_tel'=>$userTel])->one();
        }else if(empty($userTel)&&!empty($openId)){//蒙发利没有手机号模式
            //根据openId获取是否绑定过账号
            $phpUser = $this->GetUserByOpenId($agents['agents_id'],$openId,$opType);
            if(!$phpUser){//用户没有绑定过手机，则保存openId并且返回openId给前端
                $openObj = OpenId::find()->where(['open_id'=>$openId,'type'=>$opType,'agent_id'=>$agents['agents_id']])->one();
                if(!$openObj){
                    $openObj = new OpenId();
                    $openObj->agent_id = $agents['agents_id'];
                    $openObj->open_id = $openId;
                    $openObj->type = $opType;
                    $openObj->save();
                }
                $openId = '&openId='.$openId;
                $opType = '&opType='.$opType;
                $gl_url = $agents['to_url'].'?third_app='.urlencode('|'.$agents['agents_code'].$seatNo).$openId.$opType.$lotteryCode;
                $ret = [
                    'gl_url' =>$gl_url,
                ];
                return $this->jsonResult(600,'no login',$ret);
            }
        }else{
            return $this->jsonError(100, '参数缺失');
        }

        if(!empty($phpUser)){//php中已经注册
            $cust_no = $phpUser->cust_no;
            $user_id = $phpUser->user_id;
        }else{
            $userjava = $this->regAgents($userTel,$agents);
            $cust_no = $userjava['data']['cust_no'];
            $user_id = $userjava['data']['user_id'];
        }
        $data = json_encode(['agents_id'=>$agents['agents_id'],'cust_no'=>$cust_no,'user_id'=>$user_id,'expert_time'=>time()+604800]);
        $agentsTool  = new AgentsTool();
        $platformToken= $agentsTool->encrypt($data, 'GL_lottery');//用户信息加密
        $gl_url = $agents['to_url'].'?third_app='.urlencode($platformToken.'|'.$agents['agents_code'].$seatNo).$lotteryCode;
        \Yii::tokenSet("platform_user_{$agents['agents_id']}:{$user_id}", "{$platformToken}");
        $ret = [
            'gl_cust_no' =>$cust_no,
            'gl_url' =>$gl_url,
        ];
        return $this->jsonResult(600, 'login success', $ret);
    }

    /**
     * 说明: 第三方用户登录（美图，无用户手机号）
     * @author  kevi
     * @date 2017年11月14日 下午4:12:07
     * @param
     * @return
     */
    public function actionAgentUserAuth() {

        $request = \Yii::$app->request;
        $appId = $request->post_nn('appId');
        $accessToken = $request->post_nn('accessToken');
        $userTel = $request->post('userTel');
        $custNo = $request->post('custNo');
        $openId = $request->post('openId');
        $opType = $request->post('opType',0);
        $agents = $this->AuthCheck($appId,$accessToken);//代理商身份校验
        if($custNo){//有cust_no 已绑定手机用户
            $phpUser = User::find()->where(['cust_no'=>$custNo])->one();
            if(!$phpUser){
                return $this->jsonError(490, 'custNo不存在，请核对信息');
            }
        }else if($userTel){//有手机号
            if(strlen($userTel)!=11){
                return $this->jsonError(100, '参数错误，手机号格式不正确');
            }
            $phpUser = User::find()->where(['user_tel'=>$userTel])->one();
        }else if(empty($userTel)&&!empty($openId)){//没有手机号模式，先记录第三方openId
            //根据openId获取是否绑定过账号
            $phpUser = $this->GetUserByOpenId($agents['agents_id'],$openId,$opType);
            if(!$phpUser){//用户没有绑定过手机，则保存openId并且返回openId给前端
                $openObj = OpenId::find()->where(['open_id'=>$openId,'type'=>$opType,'agent_id'=>$agents['agents_id']])->one();
                if(!$openObj){
                    $openObj = new OpenId();
                    $openObj->agent_id = $agents['agents_id'];
                    $openObj->open_id = $openId;
                    $openObj->type = $opType;
                    if(!$openObj->save()){
                        print_r($openObj->errors);die;
                    }
                }
                $gl_url = $agents['to_url'].'?third_app='.urlencode('|'.$agents['agents_code']).'&openId='.$openId;
                $ret = [
                    'open_id' => $openId,
                    'gl_url' =>$gl_url,
                ];
                return $this->jsonResult(600,'no login',$ret);
            }
        }else{
            return $this->jsonError(100, '参数缺失');
        }

        if(!empty($phpUser)){//php中已经注册
            $cust_no = $phpUser->cust_no;
            $user_id = $phpUser->user_id;
        }else{//php中未注册
            $userjava = $this->regAgents($userTel,$agents);
            $cust_no = $userjava['data']['cust_no'];
            $user_id = $userjava['data']['user_id'];
            //赠送优惠券
            if($agents['agents_code']=='huanxin'){
                CouponsDetail::activitySendCoupons($agents['agents_code'],1,$cust_no);
            }
        }
        $data = json_encode(['agents_id'=>$agents['agents_id'],'cust_no'=>$cust_no,'user_id'=>$user_id,'expert_time'=>time()+604800]);
        $agentsTool  = new AgentsTool();
        $platformToken= $agentsTool->encrypt($data, 'GL_lottery');//用户信息加密
        $gl_url = $agents['to_url'].'?third_app='.urlencode($platformToken.'|'.$agents['agents_code']);
        \Yii::tokenSet("platform_user_{$agents['agents_id']}:{$user_id}", "{$platformToken}");
        $ret = [
            'open_id' => $openId,
            'gl_cust_no' =>$cust_no,
            'gl_url' =>$gl_url,
        ];
        return $this->jsonResult(600, 'login success', $ret);
    }

    //测试登录
    public function actionGlTestLogin(){
        $request = \Yii::$app->request;
        $appId = $request->post('appId','GLf64ae47eb57c83');
        $secretKey = $request->post('secretKey','b07e338e308f5f5b7ead81ee657f28fe');
        $userTel = $request->post('user_tel');
        $custNo = $request->post('gl_cust_no');
        $surl = 'https://caipiao.goodluckchina.net/api/agents/agents/platform-user-login';
        $post_data = [
            'appId' => $appId,
            'secretKey'=>$secretKey,
            'access_token'=>md5($appId.$secretKey),
//            'user_tel'=>$userTel,
//            'gl_cust_no' =>$custNo
        ];
        print_r($post_data);die;
        $ret = \Yii::sendCurlPost($surl, $post_data);
        $this->jsonResult(600, '成功', $ret);
    }

    //代理商权限验证
    public function AuthCheck($appId,$accessToken){
        $toolfun = new Toolfun();
        $userIp = $toolfun->getUserIp();
        //查找该appID 所对应的 secret_key 和 ip白名单
        $agents = Agents::find()->where(['agents_appid'=>$appId,'use_status'=>1])->asArray()->one();
        if(empty($agents)){
            return $this->jsonError(101, 'appId错误或该代理商已禁用');
        }
        //验证appID白名单
        if(YII_ENV_PROD){
            $isAllowedIp = AgentsIp::find()->where(['agents_id'=>$agents['agents_id'],'ip_address'=>$userIp,'status'=>1])->one();
            if(empty($isAllowedIp)){
                return $this->jsonError(102, 'IP鉴权失败！');
            }
        }
        //对比md5(secret_key)==$accessToken
        if(md5($appId.$agents['secret_key'])!=$accessToken){
            return $this->jsonError(103, 'access_token验签失败。请核对access_token');
        }
        return $agents;
    }

    //根据openId获取用户信息
    public function GetUserByOpenId($agentId,$openId,$type){
        $phpUser = User::find()->select(['user.user_id','user.cust_no'])
            ->leftJoin('open_id','user.user_tel = open_id.tel')
            ->where(['open_id.agent_id'=>$agentId,'open_id'=>$openId,'open_id.type'=>$type])->one();
        return $phpUser;
    }

    /**
     * 说明:无手机号专用
     * @author chenqiwei
     * @date 2018/3/8 下午3:17
     * @param
     * @return
     */
    public function actionRegisterOpenid(){
        $request = Yii::$app->request;
        $userTel = $request->post('account');//手机号
        $smsCode = $request->post('code', '');
        $agentsCode = $request->post('agentsCode', 'mfl');
        $openId = $request->post('openId', '');
        $opType = $request->post('opType', 0);
        $saveKey = Constants::SMS_KEY_LOGIN;
        SmsTool::check_code($saveKey, $userTel, $smsCode);
        $agents = Agents::find()->where(['agents_code'=>$agentsCode])->asArray()->one();
        $openObj = OpenId::find()->where(['open_id'=>$openId,'type'=>$opType,'agent_id'=>$agents['agents_id']])->one();


        if($openObj){
            $autoRet = $this->regAgents($userTel,$agents);
            $openObj->tel = $userTel;
            $openObj->save();

            $user_id = $autoRet['data']['user_id'];
            $cust_no = $autoRet['data']['cust_no'];
            $agent_id = $openObj->agent_id;

            $data = json_encode(['agents_id'=>$agent_id,'cust_no'=>$cust_no,'user_id'=>$user_id,'expert_time'=>time()+604800]);
            $agentsTool  = new AgentsTool();
            $platformToken= $agentsTool->encrypt($data, 'GL_lottery');//用户信息加密
            \Yii::tokenSet("platform_user_{$agent_id}:{$user_id}", "{$platformToken}");
            return $this->jsonResult(600, '登录成功', ['token' => $platformToken]);
        }
        if($agentsCode = 'mfl'){
            $msg = '注册失败，请重新扫码二维码';
        }else if($agentsCode = 'meitu'){
            $msg = '注册失败，请重新进入咕啦体育';
        }else{
            $msg = '注册失败，请重新稍后再试';
        }
        return $this->jsonError(405,$msg);

    }

    public function regAgents($userTel,$agents){
        $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
        if($javaUser['httpCode'] !=200){//java未注册,进行注册
            $javaRegUser = $this->userService->javaRegister($userTel, '888888');
            if ($javaRegUser['httpCode'] == 200) {
                if ($javaRegUser['httpCode'] == 200) {
                    $javaUser['data']['account'] = $javaRegUser['data'];
                    $javaUser['data']['userName'] = $javaRegUser['data'];
                    $javaUser['data']['checkStatus'] = 0;
                }else{
                    return $this->jsonError(490, '登录失败,注册未成功,请稍候重试');
                }
            }else{
                return $this->jsonError(490, '登录失败,注册未成功,请稍候重试');
            }
            $registerfrom = 5;
            $fromId = $agents['agents_id'];
        }else{
            $registerfrom =2;
        }
        //创建php用户
        $result = $this->userService->setRegisterData($javaUser);
        $result['register_from'] = $registerfrom;
        if($registerfrom !=2){//写这偷个懒 如果不是咕啦原有用户则挂上代理商信息
            $result['agent_code'] = $agents['agents_account'];
            $result['agent_name'] = $agents['agents_account'];
            $result['agent_id'] = $agents['agents_id'];
            $result['from_id'] = $fromId;
        }
        $userjava = $this->userService->createOrUpdateUser($userTel, $javaUser['data']['account'], $result);
        return $userjava;
    }


}
