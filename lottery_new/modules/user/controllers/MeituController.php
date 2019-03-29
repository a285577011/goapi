<?php
namespace app\modules\user\controllers;

//use app\modules\user\models\UserGrowthRecord;
use app\modules\agents\models\Agents;
use app\modules\agents\models\OpenId;
use app\modules\user\models\User;
use yii\web\Controller;

/**
 * 说明 ：微信公众号控制器
 * @author  kevi
 * @date 2017年8月4日 上午9:48:44
 */

class MeituController extends Controller
{
    private $client_id = '2016867678';
    private $client_secret = '0e868ec55249add5954a';
    private $agentId = 15;
    
    public function __construct($id, $module, $config = [])
    {
//        $this->appId = \Yii::$app->params['wechat']['appid'];
//        $this->appSecret = \Yii::$app->params['wechat']['appsecret'];
//        $this->userService = $userService;
//        $this->thirdService = $thirdService;
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
   
    public function actionCallback(){
        $code = $_GET["code"];
        $state = $_GET["state"];
        if(!$code){
            return $this->jsonError(109,'参数错误');
        }
        $this->MeituLogin($code, $state);
    }
    
    /**
     * 说明: 微信授权登录 ：获取access_token、openId等；获取并保存用户资料到数据库
     * @author  kevi
     * @date 2017年8月8日 上午9:55:04
     * @param
     * @return 
     */
    public function MeituLogin($code,$state)
    {
        //获取OAuth access_token
        $result = $this->getOauthAccessToken($code);
        $access_token = $result['access_token'];//授权token
        //请求微信接口，获取用户信息
        $userInfo = $this->getUserInfo($access_token);
        $openId = $userInfo['id'];
        $agents = Agents::find()->where(['agents_code'=>'meitu','use_status'=>1])->asArray()->one();
        //根据openId获取是否绑定过账号
        $phpUser = $this->GetOpenUser($this->agentId,$openId,0);
        if(!$phpUser){//用户没有绑定过手机，则保存openId并且返回openId给前端
            $openObj = OpenId::find()->where(['open_id'=>$openId,'type'=>0,'agent_id'=>$this->agentId])->one();
            if(!$openObj){
                $openObj = new OpenId();
                $openObj->agent_id = $this->agentId;
                $openObj->open_id = $openId;
                $openObj->tmp_name = $userInfo['screen_name'];
                $openObj->tmp_avatar = $userInfo['avatar'];
                $openObj->type = 0;
                if(!$openObj->save()){
                    print_r($openObj->errors);die;
                }
            }
            $gl_url = $agents['to_url'].'?third_app='.urlencode('|'.$agents['agents_code']).'&openId='.$openId;
            $gl_url .= '&tmp_name='.$userInfo['screen_name'].'&tmp_avatar='.$userInfo['avatar'];
        }else if(!$phpUser['cust_no']){//授权过，但未绑定手机
            $gl_url = $agents['to_url'].'?third_app='.urlencode('|'.$agents['agents_code']).'&openId='.$openId;
            $gl_url .= '&tmp_name='.$userInfo['screen_name'].'&tmp_avatar='.$userInfo['avatar'];
        }else {//存在open_id 且绑定过手机 则返回登录信息
            $cust_no = $phpUser['cust_no'];
            $user_id = $phpUser['user_id'];

            $data = json_encode(['agents_id' => $agents['agents_id'], 'cust_no' => $cust_no, 'user_id' => $user_id, 'expert_time' => time() + 604800]);
            $agentsTool = new AgentsTool();
            $platformToken = $agentsTool->encrypt($data, 'GL_lottery');//用户信息加密
            $gl_url = $agents['to_url'] . '?third_app=' . urlencode($platformToken . '|' . $agents['agents_code']);
            \Yii::tokenSet("platform_user_{$agents['agents_id']}:{$user_id}", "{$platformToken}");
        }
        return $this->redirect($gl_url);
    }

    public function actionOpenLogin(){
        $request = \Yii::$app->request;
        $openId = $request->get('open_id');
        $openObj = OpenId::find()->where(['open_id'=>$openId,'type'=>0,'agent_id'=>$this->agentId])->one();
        if(!$openObj){//没有openId,跳转授权
            $callback = urlencode("https://caipiao.goodluckchina.net/api/user/meitu/callback");
            $gl_url = "https://openapi.account.meitu.com/oauth/authorize?client_id={$this->client_id}&response_type=code&redirect_uri={$callback}&state=state";
            return $this->redirect($gl_url);
        }

        $agents = Agents::find()->where(['agents_code'=>'meitu','use_status'=>1])->asArray()->one();
        $gl_url = $agents['to_url'].'?third_app='.urlencode('|'.$agents['agents_code']).'&openId='.$openId;
        $gl_url .= '&tmp_name='.$openObj['tmp_name'].'&tmp_avatar='.$openObj['tmp_avatar'];
        return $this->redirect($gl_url);
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




//    public function actionStoreGetCode(){
//        $request = \Yii::$app->request;
//        $storeCode = $request->get('store_code');
//        $storeCode = $storeCode?$storeCode:12;
//        $appId = 'wx082d0083da789f0d';
//        $redirect_uri = \Yii::$app->params['userDomain'].'/api/user/wechat/store-callback';
//        $url ="https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appId}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_base&state={$storeCode}#wechat_redirect" ;
//        return $this->redirect($url);
//    }


    /**
     * 说明:根据code 获取access_token
     * @author chenqiwei
     * @date 2018/5/18 下午4:41
     * @param
     * @return
     */
    public function getOauthAccessToken($code){
        $url = "https://openapi.account.meitu.com/oauth/access_token.json?oauth_grant_type=code";
        $post_data = [
            'code'=>$code,
            'client_id'=>$this->client_id,
            'client_secret'=>$this->client_secret,
        ];
        $result = $this->sendPost($url,$post_data);
        return $result;
    }

    /**
     * 说明:
     * @author chenqiwei
     * @date 2018/5/18 下午5:00
     * @param
     * @return
     */
    public function getUserInfo($access_token){
        $url = "https://openapi.account.meitu.com/open/user/show.json";
        $post_data = [
            'access_token'=>$access_token,
            'client_id'=>$this->client_id,
            'client_secret'=>$this->client_secret,
        ];

        $result = $this->sendPost($url,$post_data);
        if(isset($result['error_code'])){
            \Yii::jsonError(101,'授权失败：'.$result['error_msg']);
        }
        return $result;
    }

    public function sendPost($url,$post_data){
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        //执行并获取HTML文档内容
        $result = curl_exec($ch);
        if(curl_errno($ch)){
            return ['data'=>curl_errno($ch),'error'=>true];
        }
        //KafkaService::addLog('CurlPostResult', 'url:'.$surl.'; params:'.var_export($post_data,true).'; result:'.var_export($output,true));
        //释放curl句柄
        curl_close($ch);
        //打印获得的数据
        $result = json_decode($result, true);
        return $result;
    }
    //根据openId获取用户信息
    public function GetUserByOpenId($agentId,$openId,$type){
        $phpUser = User::find()->select(['user.user_id','user.cust_no'])
            ->leftJoin('open_id','user.user_tel = open_id.tel')
            ->where(['open_id.agent_id'=>$agentId,'open_id'=>$openId,'open_id.type'=>$type])->one();
        return $phpUser;
    }

    //根据openId获取用户信息
    public function GetOpenUser($agentId,$openId,$type){

        $phpUser = OpenId::find()->select(['open_id.open_id','open_id.tel','user.user_id','user.cust_no'])
            ->leftJoin('user','user.user_id = open_id.tel')
            ->where(['open_id.agent_id'=>$agentId,'open_id'=>$openId,'open_id.type'=>$type])->asArray()->one();

//        $phpUser = User::find()->select(['user.user_id','user.cust_no'])
//            ->leftJoin('open_id','user.user_tel = open_id.tel')
//            ->where(['open_id.agent_id'=>$agentId,'open_id'=>$openId,'open_id.type'=>$type])->one();
        return $phpUser;
    }

}