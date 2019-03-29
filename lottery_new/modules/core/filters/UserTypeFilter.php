<?php

namespace app\modules\core\filters;

use yii\base\ActionFilter;
use app\modules\user\models\UserToken;
use app\modules\user\models\User;

class UserTypeFilter extends ActionFilter {

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
                $redisToken = \Yii::tokenGet('token_user:' . $token);
                if (!empty($redisToken)) {
                    $redisArr = explode('|', $redisToken);
                    $userType = User::find()->select(['user_type'])->where(['user_id' => $redisArr[1]])->asArray()->one();
                    if (empty($userType)) {
                        \Yii::jsonError(422, '非法会员');
                        return false;
                    } elseif ($userType['user_type'] != 3) {
                        \Yii::jsonError(421, '请先通过门店申请');
                        return false;
                    }
                } else {
                    $connection = \Yii::$app->db;
                    $nowTime = date('Y--m-d H:i:s');
                    $user = UserToken::find()->select(['cust_no'])->where(['token' => $token])->andWhere("'{$nowTime}' < expire_time")->asArray()->one();
                    if (empty($user)) {
                        if (in_array(($action->controller->id . '/' . $action->id), $this->any)) {
                            \Yii::$custNo = '';
                            return parent::beforeAction($action);
                        }
                        return \Yii::jsonError(400, "该帐号登录失效，请重新登录");
                    } else {
                        $userType = User::find()->select(['user_type'])->where(['cust_no' => $user['cust_no']])->asArray()->one();
                        if (empty($userType)) {
                            \Yii::jsonError(422, '非法会员');
                            return false;
                        } elseif ($userType['user_type'] != 3) {
                            \Yii::jsonError(421, '请先通过门店申请');
                            return false;
                        }
                    }
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