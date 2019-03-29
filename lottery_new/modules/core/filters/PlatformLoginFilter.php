<?php

namespace app\modules\core\filters;

use yii\base\ActionFilter;
use app\modules\tools\helpers\Toolfun;
use app\modules\agents\helpers\AgentsTool;
use app\modules\agents\models\AgentsIp;

class PlatformLoginFilter extends ActionFilter {

    private $isLogin = false;
    public function __construct($config = array()) {
        parent::__construct($config);
        
        
    }

    public function beforeAction($action) {
        
        
        $request = \Yii::$app->request;
        $platformToken = empty($request->post("platform_token")) ? $request->get("platform_token") : $request->post("platform_token");
        if(empty($platformToken)){
            \Yii::jsonError(402, '请先登录');
        }
        $userIp = Toolfun::getUserIp();
        $agentsTool  = new AgentsTool();
        $userInfo = $agentsTool->decrypt($platformToken, 'GL_lottery');
        $userInfo = json_decode($userInfo,true);
        $redisToken = \Yii::tokenGet('platform_user'.$userInfo['agents_id'].':'.$userInfo['user_id']);
//         if(empty($redisToken)||$redisToken!=$platformToken){
//             return \Yii::jsonError(400, "该帐号登录失效，请重新登录");
//         }
// echo $redisToken;die;
        $isAllowedIp = AgentsIp::find()->where(['agents_id'=>$userInfo['agents_id'],'ip_address'=>$userIp,'status'=>1])->one();
        if(empty($isAllowedIp)){
            return \Yii::jsonError(100, 'IP鉴权失败！');
        }
        \Yii::$custNo = $userInfo['cust_no'];
        \Yii::$userId = $userInfo['user_id'];
        \Yii::$agentId = $userInfo['agents_id'];
        return parent::beforeAction($action);
    }

//     public function afterAction($action, $result)
//     {
//         return parent::afterAction($action, $result);
//     }
//except only
}

?>