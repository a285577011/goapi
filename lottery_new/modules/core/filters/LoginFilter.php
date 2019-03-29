<?php

namespace app\modules\core\filters;

use yii\base\ActionFilter;
use app\modules\tools\helpers\Toolfun;
use app\modules\agents\helpers\AgentsTool;
use app\modules\agents\models\AgentsIp;

class LoginFilter extends ActionFilter {

    public $any = [];

    public function __construct($config = array()) {
        parent::__construct($config);
        if (!empty($this->only) && !empty($this->any)) {
            $this->only = array_merge($this->any, $this->only);
        }
    }

    public function beforeAction($action) {
        $request = \Yii::$app->request;
        $token = empty($request->post("token")) ? $request->get("token") : $request->post("token");
        if (empty($token)) {
            if (in_array(($action->controller->id . '/' . $action->id), $this->any)) {
                \Yii::$custNo = '';
                \Yii::$userId = '';
                \Yii::$agentId = 0;
                return parent::beforeAction($action);
            }
            \Yii::jsonError(402, '请先登录');
            return false;
        }
        if(strlen($token)>60){//第三方token 带用户信息
            $userIp = Toolfun::getUserIp();
            $agentsTool  = new AgentsTool();
//             $token = urldecode($token);
            $userInfo = $agentsTool->decrypt($token, 'GL_lottery');
            $userInfo = json_decode($userInfo,true);
//            $redisToken = \Yii::tokenGet('platform_user'.$userInfo['agents_id'].':'.$userInfo['user_id']);
            //         if(empty($redisToken)||$redisToken!=$platformToken){
            //             return \Yii::jsonError(400, "该帐号登录失效，请重新登录");
            //         }
//             $isAllowedIp = AgentsIp::find()->where(['agents_id'=>$userInfo['agents_id'],'ip_address'=>$userIp,'status'=>1])->one();
//             if(empty($isAllowedIp)){
//                 return \Yii::jsonError(100, 'IP鉴权失败！');
//             }
            \Yii::$custNo = $userInfo['cust_no'];
            \Yii::$userId = $userInfo['user_id'];
            \Yii::$agentId = $userInfo['agents_id'];
        }else{
            //验证token的合法性:从数据库或者redis中验证token返回user_id
            if (is_string($token) && $token != '0') {
                $redisToken = \Yii::tokenGet('token_user:' . $token);
                if (!empty($redisToken)) {
                    $redisArr = explode('|', $redisToken);
                    \Yii::$custNo = $redisArr[0];
                    \Yii::$userId = $redisArr[1];
                    \Yii::$agentId = 0;
                }else{
                    return \Yii::jsonError(400, "该帐号登录失效，请重新登录");
                }
//                 return true;
            }
        }
        return parent::beforeAction($action);
    }

//     public function afterAction($action, $result)
//     {
//         return parent::afterAction($action, $result);
//     }
//except only
}

?>