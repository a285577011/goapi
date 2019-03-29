<?php

namespace app\modules\core\filters;

use yii\base\ActionFilter;
use app\modules\user\models\UserToken;

class ExpertLoginFilter extends ActionFilter {

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
        $tokenType = empty($request->post('token_type')) ? $request->get('token_type') : $request->post('token_type');
        if (empty($token)) {
            if (in_array(($action->controller->id . '/' . $action->id), $this->any)) {
                \Yii::$custNo = '';
                \Yii::$userId = '';
                return parent::beforeAction($action);
            }
            \Yii::jsonError(402, '请先登录');
            return false;
        } else {
            //验证token的合法性:从数据库或者redis中验证token返回user_id
            if (is_string($token) && $token != '0') {
                if(empty($tokenType)) {
                    $redisToken = \Yii::tokenGet('token_expert:' . $token);
                }  elseif($tokenType == 1) {
                    $redisToken = \Yii::tokenGet('token_user:' . $token);
                }  else {
                    return \Yii::jsonError(400, "该帐号登录失效，请重新登录");
                }
                if (!empty($redisToken)) {
                    $redisArr = explode('|', $redisToken);
                    \Yii::$custNo = $redisArr[0];
                    \Yii::$userId = $redisArr[1];
                } else {
                    if (in_array(($action->controller->id . '/' . $action->id), $this->any)) {
                        \Yii::$custNo = '';
                        \Yii::$userId = '';
                        return parent::beforeAction($action);
                    }
                    return \Yii::jsonError(400, "该帐号登录失效，请重新登录");
                }
                return true;
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