<?php

namespace app\modules\user\controllers;

use yii\web\Controller;
use app\modules\user\helpers\UserCoinHelper;

class UserCoinController extends Controller {

    /**
     * 咕币充值
     * @return type
     */
    public function actionCoinRecharge() {
        $request = \Yii::$app->request;
        $coinType = $request->post('coin_cz_type', '');
        $custNo = $this->custNo;
        $userId = $this->userId;
        $ret = UserCoinHelper::coinRecharge($coinType, $custNo, $userId, 1);
        if ($ret['code'] != 600) {
            return $this->jsonError($ret['code'], $ret['msg']);
        }
        return $this->jsonResult(600, '下单成功', $ret['data']);
    }

    /**
     * 获取充值类型
     * @return type
     */
    public function actionGetCoinCzType() {
        $typeList = UserCoinHelper::getCoinCzType();
        return $this->jsonResult(600, '获取成功', $typeList);
    }
    
    /**
     * 获取会员咕币领取列表
     * @return type
     */
    public function actionGetCoinTask() {
        $userId = $this->userId;
        $custNo = $this->custNo;
        $taskList = UserCoinHelper::getUserCoinList($userId, $custNo);
        return $this->jsonResult(600, '获取成功', $taskList);
    }
    
    /**
     * 会员领取咕币
     * @return type
     */
    public function actionReceiveCoin() {
        $userId = $this->userId;
        $custNo = $this->custNo;
        $request = \Yii::$app->request;
        $sourceType = $request->post('source_type', '');
        if(empty($sourceType)) {
            return $this->jsonError(100, '请选择要领取类型');
        }
        $receive = UserCoinHelper::receiveCoin($userId, $custNo, $sourceType);
        if($receive['code'] != 600) {
            return $this->jsonError(109, $receive['msg']);
        }
        return $this->jsonResult(600, '领取成功', true);
    }
    
}
