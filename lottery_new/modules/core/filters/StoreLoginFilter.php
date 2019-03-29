<?php

namespace app\modules\core\filters;

use yii\base\ActionFilter;
use app\modules\store\models\StoreToken;

class StoreLoginFilter extends ActionFilter {

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
                return parent::beforeAction($action);
            }
            \Yii::jsonError(402, '请先登录');
            return false;
        } else {
            //验证token的合法性:从数据库或者redis中验证token返回user_id
            if (is_string($token) && $token != '0') {
                $redisToken = \Yii::tokenGet('token_store:' . $token);
                if (!empty($redisToken)) {
                    $redisArr = explode('|', $redisToken);
                    \Yii::$custNo = $redisArr[0];
                    \Yii::$userId = $redisArr[1];
                } else {
                    $connection = \Yii::$app->db;
                    $nowTime = date('Y--m-d H:i:s');
                    $store = StoreToken::find()->select(['cust_no'])->where(['token' => $token])->andWhere("'{$nowTime}' < expire_time")->asArray()->one();
                    if (empty($store)) {
                        if (in_array(($action->controller->id . '/' . $action->id), $this->any)) {
                            \Yii::$custNo = '';
                            return parent::beforeAction($action);
                        }
                        return \Yii::jsonError(400, "该帐号登录失效，请重新登录");
                    }
                    \Yii::$custNo = $token['cust_no'];
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