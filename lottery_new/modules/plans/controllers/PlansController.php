<?php

namespace app\modules\plans\controllers;

use Yii;
use yii\web\Controller;
use app\modules\common\services\PlanService;
use app\modules\common\services\PayService;
use app\modules\user\models\User;
use app\modules\common\models\UserPlan;
use app\modules\common\models\Store;
use app\modules\common\services\OrderService;

class PlansController extends Controller {

    /**
     * 获取计划列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetPlanList() {
        $request = Yii::$app->request;
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        $planList = PlanService::getAllPlan($page, $size);
        return $this->jsonResult(600, '计划列表', $planList);
    }

    /**
     * 获取计划详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetPlanDetail() {
        $request = Yii::$app->request;
        $planId = $request->post('plan_id', '');
        if ($planId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $detail = PlanService::getPlanDetail($planId);
        if (empty($detail)) {
            return $this->jsonError(109, '查询结果不存在,请稍后再试');
        }
        $detailList['data'] = $detail;
        return $this->jsonResult(600, '计划详情', $detailList);
    }

    /**
     * 获取我发布的计划列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetIpostedList() {
        $userId = $this->userId;
//        $userId = 30;
        $request = Yii::$app->request;
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        $status = $request->post('status', '');
        $planList = PlanService::getAllPlan($page, $size, $status, $userId);
        return $this->jsonResult(600, '我发布的计划列表', $planList);
    }

    /**
     * 获取我发布的计划详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetIpostedDetail() {
        $storeId = $this->userId;
//        $storeId = 30;
        $request = Yii::$app->request;
        $planId = $request->post('plan_id', '');
        if ($planId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $detail = PlanService::getIpostedPlanDetail($planId, $storeId);
        if (empty($detail)) {
            return $this->jsonError(109, '查询结果不存在,请稍后再试');
        }
        $detailList['data'] = $detail;
        return $this->jsonResult(600, '我的计划详情', $detailList);
    }

    /**
     * 获取认购人列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetSubscribePeople() {
        $request = Yii::$app->request;
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        $planId = $request->post('plan_id', '');
        if ($planId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $peopleList = PlanService::getSubscribePeople($planId, $page, $size);
        return $this->jsonResult(600, '认购人列表', $peopleList);
    }

    /**
     * 获取我的认购客户
     * @auther GL zyl
     * @return type
     */
    public function actionGetCustomList() {
        $storeId = $this->userId;
//        $storeId = 30;
        $request = Yii::$app->request;
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        $status = $request->post('status', '');
        $customList = PlanService::getCustomList($storeId, $page, $size, $status);
        return $this->jsonResult(600, '门店的认购客户', $customList);
    }

    /**
     * 获取客户购彩计划详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetCustomPlanDetail() {
        $storeId = $this->userId;
//        $storeId = 30;
        $request = Yii::$app->request;
        $userPlanId = $request->post('user_plan_id', '');
        if ($userPlanId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $detail = PlanService::getCustomPlan($storeId, $userPlanId);
        if (empty($detail)) {
            return $this->jsonError(109, '查询结果不存在，请稍后再试');
        }
        $detailList['data'] = $detail;
        return $this->jsonResult(600, '客户购彩计划详情', $detailList);
    }

    /**
     * 获取客户的购彩列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetOrderList() {
        $request = Yii::$app->request;
        $userId = $request->post('user_id', '');
        $userPlanId = $request->post('user_plan_id', '');
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        if ($userId == '' || $userPlanId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $orderData = PlanService::getOrderList($userPlanId, $userId, $page, $size);
        return $this->jsonResult(600, '客户购彩列表', $orderData);
    }

    /**
     * 获取客户购彩详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetOrderDetail() {
        $request = Yii::$app->request;
        $userId = $request->post('user_id', '');
        $orderCode = $request->post('lottery_order_code', '');
        if ($userId == '' || $orderCode == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $order = PlanService::getCustomBet($userId, $orderCode);
        if (empty($order)) {
            return $this->jsonError(109, '查询结果不存在，请稍后再试');
        }
        $orderData['data'] = $order;
        return $this->jsonResult(600, '客户购彩详情', $orderData);
    }

    /**
     * 发布计划
     * @auther GL zyl
     * @return type
     */
    public function actionPostPlan() {
        $userId = $this->userId;
//        $userId = 31;
        $request = Yii::$app->request;
        $title = $request->post('title', '');
        $settleType = $request->post('settle_type', '');
        $settleCycle = $request->post('settle_cycle', '');
        $outsetMoney = $request->post('outset_money', '');
        $increment = $request->post('increment', '');
        $remark = $request->post('plan_remark', '');
        if ($title == '' || $settleType == '' || $outsetMoney == '' || $increment == '' || $remark == '') {
            return $this->jsonError(100, '参数缺失');
        }
        if ($settleType == 1) {
            if ($settleCycle == '') {
                return $this->jsonError(100, '请填写周期数');
            }
        }
        $saveData = PlanService::savePlan($userId, $title, $settleType, $settleCycle, $outsetMoney, $increment, $remark);
        if ($saveData['code'] != 600) {
            return $this->jsonResult(109, $saveData['msg'], $saveData['data']);
        }
        return $this->jsonResult(600, '发布成功', true);
    }

    /**
     * 停止计划
     * @auther GL zyl
     * @return type
     */
    public function actionStopPlan() {
        $userId = $this->userId;
//        $userId = 31;
        $request = Yii::$app->request;
        $planId = $request->post('plan_id', '');
        if ($planId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $stop = PlanService::stopPlan($userId, $planId);
        if ($stop['code'] != 600) {
            return $this->jsonResult(109, $stop['msg'], $stop['data']);
        }
        return $this->jsonResult(600, '停止成功', true);
    }

    /**
     * 获取计划列表
     * @auther GL zyl
     * @return type
     */
    public function actionCustomGetPlanList() {
        $request = Yii::$app->request;
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        $planList = PlanService::getAllPlan($page, $size);
        return $this->jsonResult(600, '计划列表', $planList);
    }

    /**
     * 获取计划详情
     * @auther GL zyl
     * @return type
     */
    public function actionCustomGetPlanDetail() {
        $request = Yii::$app->request;
        $planId = $request->post('plan_id', '');
        if ($planId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $detail = PlanService::getPlanDetail($planId);
        if (empty($detail)) {
            return $this->jsonError(109, '查询结果不存在,请稍后再试');
        }
        $detailList['data'] = $detail;
        return $this->jsonResult(600, '计划详情', $detailList);
    }

    /**
     * 认购计划
     * @auther GL zyl
     * @return type
     */
    public function actionSubscribePlan() {
        $userId = $this->userId;
        $custNo = $this->custNo;
//        $userId = 24;
//        $custNo = 'gl00001040';
        $request = Yii::$app->request;
        $planId = $request->post('plan_id', '');
        $buyMoney = $request->post('buy_money', '');
        $winType = $request->post('win_type', '');
        $betScale = $request->post('bet_scale', '');
        if ($planId == '' || $buyMoney == '' || $winType == '' || $betScale == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $saveData = PlanService::saveUserPlan($userId, $planId, $buyMoney, $winType, $betScale);
        if ($saveData['code'] != 600) {
            return $this->jsonError(109, $saveData['msg']);
        }
        $userPlanCode = $saveData['data'];

        $paySer = new PayService();
        $paySer->productPayRecord($custNo, $userPlanCode, 7, 1, $buyMoney, 4);
        $data = [];
        $data['data']['orde_code'] = $userPlanCode;
        return $this->jsonResult(600, "下单成功", $data);
    }

    /**
     * 获取我认购的计划列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetSubscribeList() {
        $userId = $this->userId;
//        $userId = 24;
        $request = Yii::$app->request;
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        $status = $request->post('status', '');
        $subscribeList = PlanService::getSubscribePlan($userId, $page, $size, $status);
        return $this->jsonResult(600, '我认购的计划列表', $subscribeList);
    }

    /**
     * 获取我认购的计划详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetSubscribeDetail() {
        $userId = $this->userId;
//        $userId = 24;
        $request = Yii::$app->request;
        $userPlanId = $request->post('user_plan_id', '');
        if ($userPlanId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $subscribeDetail = PlanService::getSubscribePlanDetail($userId, $userPlanId);
        if (empty($subscribeDetail)) {
            return $this->jsonError(109, '查询结果不存在，请稍后再试');
        }
        return $this->jsonResult(600, '我认购的计划详情', $subscribeDetail);
    }

    /**
     * 下订单
     * auther GL ctx
     * @return json
     */
    public function actionPlayOrder() {
        $request = Yii::$app->request;
        $post = $request->post();
        if (!isset($post["user_plan_id"]) || empty($post["user_plan_id"])) {
            return $this->jsonResult(2, '未设置计划用户', '');
        }
        $userPlanId = $post["user_plan_id"];
        $storeId = $this->userId;
        $userPlan = UserPlan::findOne(["store_id" => $storeId, "user_plan_id" => $userPlanId, 'status' => 2]);
        $userId = $userPlan->user_id;
        if ($userPlan == null) {
            return $this->jsonResult(2, '未找到对应计划用户', '');
        }
        $user = User::findOne(["user_id" => $userId]);
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        if(floatval($orderData['total'] > floatval($userPlan['able_funds'] * $userPlan['bet_scale'] / 100))){
            return $this->jsonError(2, '投注金额不可大于' . floatval($userPlan['able_funds'] * $userPlan['bet_scale'] / 100));
        }
        if (!isset($orderData["lottery_type"]) || empty($orderData["lottery_type"])) {
            return $this->jsonResult(2, '投注彩种未设置', '');
        }
        $store = Store::find()->select(['store_id', 'store_code','sale_lottery', 'business_status'])->where(['user_id' => $storeId])->asArray()->one();
        if ($store['business_status'] != 1) {
            return $this->jsonError(2, '门店已暂停营业！！');
        }
        $lotteryCode = $orderData['lottery_code'];
        $saleLotteryArr = explode(',', $store['sale_lottery']);
        if(in_array(3000, $saleLotteryArr)){
            array_push($saleLotteryArr, '3006', '3007', '3008', '3009', '3010', '3011');
        }
        if(in_array(3100, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3001', '3002', '3003', '3004', '3005');
        }
        if(!in_array($lotteryCode, $saleLotteryArr)) {
            return $this->jsonError(488, '你所购买的彩种，门店不可接单！');
        }
        $userNo = $user->cust_no;
        switch ($orderData["lottery_type"]) {
            case "1":
                $ret = OrderService::numsOrder($userNo, $userId, $storeId, $store['store_code'], 6, $userPlanId);
                break;
            case "2":
                $ret = OrderService::competingOrder($userNo, $userId, $storeId, $store['store_code'], 6, $userPlanId);
                break;
            case '3':
                $ret = OrderService::optionalOrder($userNo, $userId, $storeId, $store['store_code'], 6, $userPlanId);
                break;
            case '4':
                $ret = OrderService::basketOrder($userNo, $userId, $storeId, $store['store_code'], 6, $userPlanId);
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

    /**
     * 获取我的购彩列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetMyOrderList() {
        $request = Yii::$app->request;
        $userId = $this->userId;
//        $userId = 24;
        $userPlanId = $request->post('user_plan_id', '');
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        if ($userId == '' || $userPlanId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $orderData = PlanService::getOrderList($userPlanId, $userId, $page, $size);
        return $this->jsonResult(600, '购彩列表', $orderData);
    }

    /**
     * 获取我的购彩详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetMyOrderDetail() {
        $request = Yii::$app->request;
        $userId = $this->userId;
//        $userId = 24;
        $orderCode = $request->post('lottery_order_code', '');
        if ($userId == '' || $orderCode == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $order = PlanService::getCustomBet($userId, $orderCode);
        if (empty($order)) {
            return $this->jsonError(109, '查询结果不存在，请稍后再试');
        }
        $orderData['data'] = $order;
        return $this->jsonResult(600, '购彩详情', $orderData);
    }

    /**
     * 结算计划
     * @auther GL zyl
     * @return type
     */
    public function actionSettltmentPlan() {
        $storeId = $this->userId;
//        $storeId = 24;
        $request = Yii::$app->request;
        $userPlanId = $request->post('user_plan_id', '');
        $actionType = $request->post('action_type', '');
        if (empty($userPlanId) || empty($actionType)) {
            return $this->jsonError(100, '参数缺失');
        }
        if($actionType == 1){
            $settle = PlanService::refuse($userPlanId);
        }  elseif($actionType == 2) {
            $settle = PlanService::settle($storeId, $userPlanId);
        }
        return $this->jsonError($settle['code'], $settle['msg']);
    }
    
    /**
     * 计划接单
     * @auther GL zyl
     * @return type
     */
    public function actionAcceptPlan() {
        $storeId = $this->userId;
        $request = Yii::$app->request;
        $userPlanId = $request->post('user_plan_id', '');
        if(empty($userPlanId)) {
            return $this->jsonError(100, '参数缺失');
        }
        $accept = PlanService::accept($storeId,$userPlanId);
        return $this->jsonError($accept['code'], $accept['msg']);
    }

}
