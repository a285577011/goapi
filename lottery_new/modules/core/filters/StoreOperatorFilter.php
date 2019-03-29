<?php

namespace app\modules\core\filters;

use yii\base\ActionFilter;
use app\modules\user\models\User;
use app\modules\common\models\StoreOperator;
use app\modules\common\models\Store;

class StoreOperatorFilter extends ActionFilter {

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
        $token_type = empty($request->post("token_type")) ? $request->get("token_type") : $request->post("token_type");
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
                if (!empty($token_type) && $token_type == "storeBack") {
                    $redisToken = \Yii::tokenGet('token_storeback:' . $token);
                } else {
                    $redisToken = \Yii::tokenGet('token_user:' . $token);
                }
                if (!empty($redisToken)) {
                    $redisArr = explode('|', $redisToken);
                    $user = User::findOne(["user_id" => $redisArr[1]]);
                    if (empty($user->user_type)) {
                        \Yii::jsonError(422, '非法会员');
                        return false;
                    } elseif ($user->user_type != 3) {
                        if ($user->is_operator == 2) {
                            $storeOperator = StoreOperator::findOne(["user_id" => $redisArr[1]]);
                            if ($storeOperator->status == 1) {
                                 $storeInfo = Store::findOne(['store_code' => $storeOperator->store_id,"status"=>1]);
                                if(empty($storeInfo)){
                                    \Yii::jsonError(421, '找不到该门店');
                                }
                                //门店运营者ID、NO
                                \Yii::$custNo = $storeInfo->cust_no;
                                \Yii::$userId = $storeInfo->user_id;
                                //操作员ID、NO
                                \Yii::$storeOperatorId = $redisArr[1];
                                \Yii::$storeOperatorNo = $redisArr[0];
                                //门店编号
                                \Yii::$storeCode = $storeOperator->store_id;
                            } else {
                                \Yii::jsonError(421, '该操作员已被禁用');
                            }
                        } else {
                            \Yii::jsonError(421, '请先通过门店申请');
                            return false;
                        }
                    } else {
                        $storeInfo  = Store::find()->select(['store_code'])->where(['user_id' => $redisArr[1], 'status' => 1])->asArray()->one();
                        \Yii::$custNo = $redisArr[0];
                        \Yii::$userId = $redisArr[1];
                        \Yii::$storeCode = $storeInfo['store_code'];
                    }
                } else {
                    \Yii::jsonError(400, '该帐号登录失效，请重新登录');
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