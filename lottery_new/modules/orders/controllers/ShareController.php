<?php

namespace app\modules\orders\controllers;

use Yii;
use yii\web\Controller;
use app\modules\orders\services\ShareService;
use app\modules\common\services\OrderService;


class ShareController extends Controller {
    
    /**
     * 分享订单
     * @return type
     */
    public function actionShareOrder() {
        $request = Yii::$app->request;
        $userId = $this->userId;
        $custNo = $this->custNo;
        $orderId = $request->post('order_id', '');
        $remark = $request->post('remark', '');
        if(empty($orderId) || empty($remark)) {
            return $this->jsonError(100, '参数缺失');
        }
        $shareService = new ShareService();
        $result = $shareService->shareOrder($userId, $custNo, $orderId, $remark);
        if($result['code'] != 600) {
            return $this->jsonError(109, $result['msg']);
        }
        return $this->jsonResult(600, '成功', true);
    }

    /**
     * 获取订单信息
     * @return type
     */
    public function actionGetOrderInfo() {
        $request = Yii::$app->request;
        $orderId = $request->post('order_id', '');
        $orderCode = $request->post('order_code', '');
        if(empty($orderId) || empty($orderCode)) {
            return $this->jsonError(100, '参数缺失');
        }
        $shareService = new ShareService();
        $data = $shareService->getOrderInfo($orderId, $orderCode);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 实单跟单
     * @return type
     */
    public function actionSharePlayOrder() {
        $request = Yii::$app->request;
        $shareId = $request->post('share_id', '');
        $orderInfo = $request->post('order_data', '');
        $storeId = $request->post('store_id', '');
        if(empty($shareId) || empty($orderInfo) || empty($storeId)) {
            return $this->jsonError(100, '参数缺失');
        }
        $userId = $this->userId;
        $orderData = json_decode($orderInfo, JSON_UNESCAPED_UNICODE);
        if (!isset($orderData["lottery_type"]) || empty($orderData["lottery_type"])) {
            return $this->jsonResult(2, '投注彩种未设置', '');
        }
        $shareService = new ShareService();
        $prevalid = $shareService->preValid($shareId, $storeId, $orderData['lottery_code']);
        if($prevalid['code'] != 600) {
            return $this->jsonError($prevalid['code'], $prevalid['msg']);
        }
        $userNo = $this->custNo;
        switch ($orderData["lottery_type"]) {
            case "1":
                $ret = OrderService::numsOrder($userNo, $storeId, 5, $shareId, $prevalid['data'], $userId);
                break;
            case "2":
                $ret = OrderService::competingOrder($userNo, $storeId, 5, $shareId, $prevalid['data'], $userId);
                break;
            case '3':
                $ret = OrderService::optionalOrder($userNo, $storeId, 5, $shareId, $prevalid['data'], $userId);
                break;
            case '4':
                $ret = OrderService::basketOrder($userNo, $storeId, 5, $shareId, $prevalid['data'], $userId);
                break;
            default :
                return $this->jsonResult(2, '无该彩种类型', '');
        }
        if ($ret["code"] == "600") {
            $ret1 = PlanService::planPay($ret["result"]["lottery_order_code"], $userPlan->user_plan_id);
            return json_encode($ret1);
        } else {
            return json_encode($ret);
        }
    }
}

