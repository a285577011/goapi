<?php

namespace app\modules\common\services;

use Yii;
use yii\db\Query;
use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Constants;
use app\modules\common\services\TogetherService;
use app\modules\common\models\Plan;
use app\modules\common\models\UserPlan;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\FundsService;
use app\modules\common\models\PayRecord;
use app\modules\common\models\LotteryAdditional;
use app\modules\common\models\Store;
use yii\base\Exception;
use app\modules\user\models\User;
use app\modules\common\models\StoreOperator;
use app\modules\user\helpers\WechatTool;

class PlanService {

    /**
     * 获取所以的计划
     * @auther GL zyl
     * @param type $pn
     * @param type $size
     * @param type $status
     * @param type $userId
     * @return type
     */
    public static function getAllPlan($pn, $size, $status = '', $userId = '') {
        $where = [];
        if ($status != '') {
            $where['plan.status'] = $status;
        }
        if ($userId != '') {
            $where['plan.store_id'] = $userId;
        }
        $total = Plan::find()->where($where)->count();
        $pages = ceil($total / $size);
        $data = Plan::find()
                ->select(['plan.plan_id', 'plan.title', 'plan.store_id', 'plan.store_name', 'plan.plan_buy_min', 'plan.settlement_type', 'plan.settlement_periods', 'plan.buy_nums', 'plan.status', 's.store_img'])
                ->leftJoin('store as s', 's.user_id = plan.store_id')
                ->where($where)
                ->limit($size)
                ->offset(($pn - 1) * $size)
                ->orderBy('plan.plan_id desc')
                ->asArray()
                ->all();
        foreach ($data as &$val) {
            if ($val['settlement_type'] == 1) {
                $val['cycle'] = $val['settlement_periods'] . '天期限';
            } else {
                $val['cycle'] = '不定期';
            }
            $val['status_name'] = $val['status'] == 1 ? '进行中' : '已截止';
        }
        $dataList = ['page' => $pn, 'size' => count($data), 'pages' => $pages, 'total' => $total, 'data' => $data];
        return $dataList;
    }

    /**
     * 获取计划详情
     * @auther GL zyl
     * @param type $planId
     * @return boolean|string
     */
    public static function getPlanDetail($planId) {
        $plan = (new Query())->select(['p.plan_id', 'p.plan_code', 'p.title', 'p.buy_amount', 'p.buy_nums', 'p.incr_money', 'p.plan_buy_min', 'p.settlement_type', 'p.settlement_periods', 'p.status', 'p.store_id',
                    'p.store_name', 'p.create_time', 'p.plan_remark', 's.his_win_amount', 's.his_win_nums'])
                ->from('plan as p')
                ->leftJoin('store as s', 's.user_id = p.store_id')
                ->where(['p.plan_id' => $planId])
                ->one();
        if (empty($plan)) {
            return false;
        }
        $date = date('m-d', strtotime('+1 day'));
        if ($plan['settlement_type'] == 1) {
            $endDate = $plan['settlement_periods'] . '天后';
            $plan['cycle'] = $plan['settlement_periods'] . '天';
        } else {
            $endDate = '不定期';
            $plan['cycle'] = '不定期';
        }
        $maxBuy = Constants::PLAN_BUY_MAX;
        $plan['max_buy'] = $maxBuy;
        $rule = ['today' => '今日', 'clinch_date' => $date, 'bet_date' => $date, 'end_date' => $endDate];
        $data = ['plan' => $plan, 'rule' => $rule];
        return $data;
    }

    /**
     * 获取我发布的计划详情
     * @auther GL zyl
     * @param type $planId
     * @param type $storeId
     * @return type
     */
    public static function getIpostedPlanDetail($planId, $storeId) {
        $plan = Plan::find()->select(['plan_id', 'plan_code', 'title', 'buy_amount', 'buy_nums', 'incr_money', 'plan_buy_min', 'settlement_type', 'settlement_periods', 'status', 'store_id', 'store_name', 'plan_remark', 'create_time'])
                ->where(['plan_id' => $planId, 'store_id' => $storeId])
                ->asArray()
                ->one();
        if ($plan['settlement_type'] == 1) {
            $plan['cycle'] = $plan['settlement_periods'] . '天期限';
        } else {
            $plan['cycle'] = '不定期';
        }
        return $plan;
    }

    /**
     * 获取认购计划的所有人
     * @auther GL zyl
     * @param type $planId
     * @param type $pn
     * @param type $size
     * @return type
     */
    public static function getSubscribePeople($planId, $pn, $size) {
        $sta = ['!=', 'status', 0];
        $where['plan_id'] = $planId;
        $total = UserPlan::find()->where($where)->andWhere($sta)->count();
        $pages = ceil($total / $size);
        $subList = UserPlan::find()
                ->select(['user_name', 'buy_money', 'total_profit', 'status', 'create_time'])
                ->where($where)
                ->andWhere($sta)
                ->limit($size)
                ->offset(($pn - 1) * $size)
                ->orderBy('buy_money desc')
                ->asArray()
                ->all();
        foreach ($subList as &$val) {
            $val['status_name'] = $val['status'] == 1 ? '等待接单' : ($val['status'] == 2 ? '进行中' : ($val['status'] == 3 ? '结算' : ($val['status'] == 4 ? '拒绝接单' : '未支付')));
            $cSub = substr($val['user_name'], 3, strlen($val['user_name']) - 6);
            $cLen = strlen($cSub);
            $val['user_name'] = str_replace($cSub, '*', $val['user_name'], $cLen);
        }
        $data = ['page' => $pn, 'size' => count($subList), 'pages' => $pages, 'total' => $total, 'data' => $subList];
        return $data;
    }

    /**
     * 获取认购我计划的客户
     * @auther GL zyl
     * @param type $storeId
     * @param type $pn
     * @param type $size
     * @param type $status
     * @return type
     */
    public static function getCustomList($storeId, $pn, $size, $status) {
        $where = [];
        $sta = [];
        if ($status != '') {
            $where['user_plan.status'] = $status;
        } else {
            $sta = ['!=', 'user_plan.status', 0];
        }
        $where['user_plan.store_id'] = $storeId;
        $total = UserPlan::find()->where($where)->andWhere($sta)->count();
        $pages = ceil($total / $size);
        $customList = UserPlan::find()
                ->select(['user_plan.user_plan_id', 'user_plan.user_name', 'user_plan.buy_money', 'user_plan.status', 'user_plan.create_time', 'user_plan.end_time', 'u.user_pic'])
                ->leftJoin('user as u', 'u.user_id = user_plan.user_id')
                ->where($where)
                ->andWhere($sta)
                ->limit($size)
                ->offset(($pn - 1) * $size)
                ->orderBy('user_plan.create_time')
                ->asArray()
                ->all();
        foreach ($customList as &$val) {
            if (!empty($val['end_time'])) {
                $val['cycle'] = $val['end_time'];
            } else {
                $val['cycle'] = '不定期';
            }
            $val['status_name'] = $val['status'] == 1 ? '等待接单' : ($val['status'] == 2 ? '进行中' : ($val['status'] == 3 ? '结算' : ($val['status'] == 4 ? '拒绝接单' : '未支付')));
        }
        $data = ['page' => $pn, 'size' => count($customList), 'pages' => $pages, 'total' => $total, 'data' => $customList];
        return $data;
    }

    /**
     * 获取客户认购计划
     * @auther GL zyl
     * @param type $storeId
     * @param type $userPlanId
     * @return boolean
     */
    public static function getCustomPlan($storeId, $userPlanId) {
        $status = Constants::ORDER_STATUS;
        $numsArr = Constants::MADE_NUMS_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $optionalArr = Constants::MADE_OPTIONAL_LOTTERY;
        $where['up.user_plan_id'] = $userPlanId;
        $data = (new Query())->select(['up.user_plan_id', 'up.user_plan_code', 'up.user_id', 'up.user_name', 'up.user_tel', 'up.able_funds', 'up.betting_funds', 'up.buy_money', 'up.create_time',
                    'up.end_time', 'up.plan_id', 'up.status', 'up.total_profit', 'p.plan_code', 'p.store_name', 'p.settlement_type', 'p.settlement_periods', 'p.title', 'up.bet_scale', 'up.win_type'])
                ->from('user_plan as up')
                ->leftJoin('plan as p', "p.plan_id = up.plan_id and p.store_id = '" . $storeId . "'")
                ->where($where)
                ->one();
        if (empty($data)) {
            return false;
        }
        if ($data['settlement_type'] == 1) {
            $data['cycle'] = $data['end_time'];
        } else {
            $data['cycle'] = '不定期';
        }
        $data['status_name'] = $data['status'] == 2 ? '进行中' : '结算';
        $orderData = LotteryOrder::find()->select(['lottery_order_id', 'lottery_additional_id', 'lottery_name', 'lottery_id', 'bet_money', 'win_amount', 'status', 'create_time'])
                ->where(['user_plan_id' => $data['user_plan_id']])
                ->orderBy('lottery_order_id desc')
                ->limit(5)
                ->asArray()
                ->all();
        if (!empty($orderData)) {
            foreach ($orderData as &$val) {
                if (in_array($val['lottery_id'], $football)) {
                    $val['lottery_name'] = '竞彩足球';
                    $val['lottery_type'] = 2;
                } elseif (in_array($val['lottery_id'], $numsArr)) {
                    $val['lottery_type'] = 1;
                } elseif (in_array($val['lottery_id'], $optionalArr)) {
                    $val['lottery_type'] = 3;
                }
                $val['order_status'] = $status[$val['status']];
                if (in_array($val['status'], [4, 5])) {
                    $profit = floatval($val['win_amount']) - intval($val['bet_money']);
                    $val['profit'] = $profit;
                    $val['margins'] = round($profit / intval($val['bet_money']) * 100, 2) . '%';
                } else {
                    $val['profit'] = '';
                    $val['margins'] = '';
                }
            }
        } else {
            $orderData = null;
        }

        $data['bet_order'] = $orderData;
        return $data;
    }

    /**
     * 获取投注订单列表
     * @auther GL zyl
     * @param type $userPlanId
     * @param type $userId
     * @param type $pn
     * @param type $size
     * @return type
     */
    public static function getOrderList($userPlanId, $userId, $pn, $size) {
        $status = Constants::ORDER_STATUS;
        $numsArr = Constants::MADE_NUMS_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $optionalArr = Constants::MADE_OPTIONAL_LOTTERY;
        $total = LotteryOrder::find()->where(['user_plan_id' => $userPlanId])->count();
        $pages = ceil($total / $size);
        $orderData = LotteryOrder::find()->select(['lottery_order_id', 'lottery_additional_id', 'lottery_order_code', 'user_id', 'lottery_name', 'lottery_id', 'bet_money', 'win_amount', 'status', 'create_time'])
                ->where(['user_plan_id' => $userPlanId])
                ->andWhere(['!=', 'status', 0])
                ->orderBy('create_time desc')
                ->limit($size)
                ->offset(($pn - 1) * $size)
                ->asArray()
                ->all();
        foreach ($orderData as &$val) {
            if (in_array($val['lottery_id'], $football)) {
                $val['lottery_name'] = '竞彩足球';
                $val['lottery_type'] = 2;
            } elseif (in_array($val['lottery_id'], $numsArr)) {
                $val['lottery_type'] = 1;
            } elseif (in_array($val['lottery_id'], $optionalArr)) {
                $val['lottery_type'] = 3;
            }
            $val['order_status'] = $status[$val['status']];
            if (in_array($val['status'], [4, 5])) {
                $profit = floatval($val['win_amount']) - intval($val['bet_money']);
                $val['profit'] = $profit;
                $val['margins'] = round($profit / intval($val['bet_money']) * 100, 2) . '%';
            } else {
                $val['profit'] = '';
                $val['margins'] = '';
                $val['win_amount'] = $status[$val['status']];
            }
        }
        $dataList = ['page' => $pn, 'size' => count($orderData), 'pages' => $pages, 'total' => $total, 'data' => $orderData];
        return $dataList;
    }

    /**
     * 获取投注订单详情
     * @auther GL zyl
     * @param type $userId
     * @param type $orderCode
     * @return boolean
     */
    public static function getCustomBet($userId, $orderCode) {
        $where = [];
        $status = Constants::ORDER_STATUS;
        $numsArr = Constants::MADE_NUMS_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $optionalArr = Constants::MADE_OPTIONAL_LOTTERY;
        $where['lottery_order.user_id'] = $userId;
        $where['lottery_order.lottery_order_code'] = $orderCode;
        $cWhere = ['!=', 'lottery_order.status', 1];
        $field = ['lottery_order.lottery_order_id', 'lottery_order.lottery_name', 'lottery_order.lottery_order_code', 'lottery_order.play_name', 'lottery_order.lottery_id', 'lottery_order.periods',
            'lottery_order.bet_val', 'lottery_order.bet_double', 'lottery_order.is_bet_add', 'lottery_order.bet_money', 'lottery_order.odds', 'lottery_order.count', 'lottery_order.win_amount', 'lottery_order.status',
            'lottery_order.create_time', 'l.lottery_pic'];
        $order = LotteryOrder::find()->select($field)
                ->leftJoin('lottery as l', 'l.lottery_code = lottery_order.lottery_id')
                ->where($where)
                ->andWhere($cWhere)
                ->asArray()
                ->one();
        if (empty($order)) {
            return false;
        }
        if (in_array($order['lottery_id'], $numsArr)) {
            $result = (new Query())->select(['lottery_time', 'lottery_numbers'])->from('lottery_record')->where(['lottery_code' => $order['lottery_id'], 'periods' => $order['periods']])->one();
            if (empty($result)) {
                return false;
            }
            $contents['open_time'] = $result['lottery_time'];
            $contents['open_nums'] = $result['lottery_numbers'];
        } elseif (in_array($order['lottery_id'], $football)) {
            $odds = [];
            if (!empty($order['odds'])) {
                $odds = json_decode($order['odds']);
            }
            $contents = TogetherService::getOdds($order['bet_val'], $odds, $order['lottery_id']);
        } elseif (in_array($order['lottery_id'], $optionalArr)) {
            $contents = TogetherService::getOptionalOrder($order['periods'], $order['bet_val']);
        }
        $order['status_name'] = $status[$order['status']];
        $order['contents'] = $contents;
        return $order;
    }

    /**
     * 保存计划
     * @auther GL zyl
     * @param type $userId
     * @param type $title
     * @param type $settleType
     * @param type $settleCycle
     * @param type $outsetMoney
     * @param type $increment
     * @param type $remark
     * @return type
     */
    public static function savePlan($userId, $title, $settleType, $settleCycle, $outsetMoney, $increment, $remark) {
        $storeData = (new Query)->select(['store_id', 'store_name', 'phone_num'])->from('store')->where(['user_id' => $userId])->one();
        $plan = new Plan;
        $plan->plan_code = Commonfun::getCode('PLAN', 'P');
        $plan->title = $title;
        $plan->store_id = $userId;
        $plan->store_name = $storeData['store_name'];
        $plan->store_tel = $storeData['phone_num'];
        $plan->settlement_type = $settleType;
        if ($settleType == 1) {
            $plan->settlement_periods = $settleCycle;
        }
        $plan->plan_buy_min = $outsetMoney;
        $plan->incr_money = $increment;
        $plan->plan_remark = $remark;
        $plan->create_time = date('Y-m-d H:i:s');
        $plan->status = 1;
        if (!$plan->validate()) {
            return ['code' => 109, 'msg' => '数据验证失败', 'data' => $plan->getFirstErrors()];
        }
        if (!$plan->save()) {
            return ['code' => 109, 'msg' => '数据写入失败', 'data' => $plan->getFirstErrors()];
        }
        return ['code' => 600, 'msg' => '写入成功'];
    }

    /**
     * 停止计划
     * @param type $userId
     * @param type $planId
     * @return type
     */
    public static function stopPlan($userId, $planId) {
        $plan = Plan::find()->where(['plan_id' => $planId, 'store_id' => $userId])->one();
        if (empty($plan)) {
            return ['code' => 109, 'msg' => '该计划不存在，请稍后再试', 'data' => false];
        }
        $plan->status = 2;
        $plan->modify_time = date('Y-m-d H:i:s');
        if (!$plan->save()) {
            return ['code' => 109, 'msg' => '数据写入失败', 'data' => $plan->getFirstErrors()];
        }
        return ['code' => 600, 'msg' => '写入成功'];
    }

    /**
     * 保存认购计划
     * @auther GL zyl
     * @param type $userId
     * @param type $planId
     * @param type $buyMoney
     * @return type
     */
    public static function saveUserPlan($userId, $planId, $buyMoney, $winType, $betScale) {
        $userData = (new Query)->select(['user_id', 'user_name', 'user_tel'])->from('user')->where(['user_id' => $userId])->one();
        $planData = Plan::find()->select(['settlement_type', 'settlement_periods', 'plan_buy_min', 'incr_money', 'store_id'])->where(['plan_id' => $planId, 'status' => 1])->asArray()->one();
        $maxBuy = Constants::PLAN_BUY_MAX;
        if (empty($planData)) {
            return ['code' => 109, 'msg' => '该计划已停止认购，请选择其他计划'];
        }
        if (intval($buyMoney) < intval($planData['plan_buy_min'])) {
            return ['code' => 109, 'msg' => '认购金额必须大于最低认购金额'];
        }
        if (intval($buyMoney) > intval($maxBuy)) {
            return ['code' => 109, 'msg' => '认购金额必须小于最高认购金额'];
        }
        $userPlan = new UserPlan;
        $userPlan->user_id = $userData['user_id'];
        $userPlan->user_name = $userData['user_name'];
        $userPlan->user_tel = $userData['user_tel'];
        $userPlan->user_plan_code = Commonfun::getCode('PLAN', 'U');
        $userPlan->plan_id = $planId;
        $userPlan->store_id = $planData['store_id'];
        $userPlan->win_type = $winType;
        $userPlan->bet_scale = $betScale;
        $userPlan->buy_money = $buyMoney;
        $userPlan->able_funds = $buyMoney;
        $userPlan->status = 0;
        if ($planData['settlement_type'] == 1) {
            $endTime = date('Y-m-d H:i:s', strtotime('+ ' . $planData['settlement_periods'] . 'day'));
            $userPlan->end_time = $endTime;
        }
        $userPlan->create_time = date('Y-m-d H:i:s');
        if (!$userPlan->validate()) {
            return ['code' => 109, 'msg' => $userPlan->getFirstErrors()];
        }
        if (!$userPlan->save()) {
            return ['code' => 109, 'msg' => $userPlan->getFirstErrors()];
        }
        return ['code' => 600, 'data' => $userPlan->user_plan_code];
    }

    /**
     * 获取认购的计划列表
     * @auther GL zyl
     * @param type $userId
     * @param type $pn
     * @param type $size
     * @param type $status
     * @return type
     */
    public static function getSubscribePlan($userId, $pn, $size, $status = '') {
        $where = [];
        $cWhere = [];
        $eqWhere = [];
        $eqcWhere = [];
        $where['up.user_id'] = $userId;
        $cWhere['user_id'] = $userId;
        if ($status != '') {
            $where['up.status'] = $status;
            $cWhere['status'] = $status;
        } else {
            $eqWhere = ['!=', 'up.status', 0];
            $eqcWhere = ['!=', 'status', 0];
        }
        $total = UserPlan::find()->where($cWhere)->andWhere($eqcWhere)->count();
        $pages = ceil($total / $size);
        $data = (new Query())->select(['up.user_plan_id', 'up.buy_money', 'up.total_profit', 'up.create_time', 'up.end_time', 'up.status', 'p.title', 'p.settlement_periods', 'p.settlement_type', 'p.store_name', 's.store_img'])
                ->from('user_plan as up')
                ->leftJoin('plan as p', 'p.plan_id = up.plan_id')
                ->leftJoin('store as s', 's.user_id = up.store_id')
                ->where($where)
                ->andWhere($eqWhere)
                ->limit($size)
                ->offset(($pn - 1) * $size)
                ->all();
        foreach ($data as &$val) {
            if ($val['settlement_type'] == 1) {
                $val['cycle'] = $val['end_time'];
            } else {
                $val['cycle'] = '不定期';
            }
            $val['status_name'] = $val['status'] == 1 ? '等待接单' : ($val['status'] == 2 ? '进行中' : ($val['status'] == 3 ? '结算' : ($val['status'] == 4 ? '拒绝接单' : '未支付')));
        }
        $dataList = ['page' => $pn, 'size' => count($data), 'pages' => $pages, 'total' => $total, 'data' => $data];
        return $dataList;
    }

    /**
     * 获取的认购计划的详情
     * @param type $userId
     * @param type $userPlanId
     * @return boolean
     */
    public static function getSubscribePlanDetail($userId, $userPlanId) {
        $status = Constants::ORDER_STATUS;
        $numsArr = Constants::MADE_NUMS_LOTTERY;
        $footBall = Constants::MADE_FOOTBALL_LOTTERY;
        $optionalArr = Constants::MADE_OPTIONAL_LOTTERY;
        $where = [];
        $where['user_plan.user_id'] = $userId;
        $where['user_plan.user_plan_id'] = $userPlanId;
        $field = ['user_plan.user_plan_id', 'user_plan.user_id', 'user_plan.buy_money', 'user_plan.able_funds', 'user_plan.betting_funds', 'user_plan.total_profit', 'user_plan.end_time', 'user_plan.status', 'user_plan.create_time',
            'p.store_name', 'p.store_tel', 'p.plan_code', 'p.settlement_type', 'p.settlement_periods', 'p.plan_remark', 'p.title'];
        $data = UserPlan::find()->select($field)->leftJoin('plan as p', 'p.plan_id = user_plan.plan_id')->where($where)->asArray()->one();
        if (empty($data)) {
            return false;
        }
        if ($data['settlement_type'] == 1) {
            $data['cycle'] = $data['end_time'];
        } else {
            $data['cycle'] = '不定期';
        }
        $totle = LotteryOrder::find()->where(['user_plan_id' => $data['user_plan_id']])->count();
        $orderData = LotteryOrder::find()->select(['lottery_order_id', 'lottery_additional_id', 'lottery_name', 'lottery_id', 'bet_money', 'win_amount', 'status', 'create_time', 'remark'])
                ->where(['user_plan_id' => $data['user_plan_id']])
                ->orderBy('create_time desc')
                ->limit(5)
                ->asArray()
                ->all();
        if (!empty($orderData)) {
            foreach ($orderData as &$val) {
                if (in_array($val['lottery_id'], $footBall)) {
                    $val['lottery_name'] = '竞彩足球';
                    $val['lottery_type'] = 2;
                } elseif (in_array($val['lottery_id'], $numsArr)) {
                    $val['lottery_type'] = 1;
                } elseif (in_array($val['lottery_id'], $optionalArr)) {
                    $val['lottery_type'] = 3;
                }
                $val['order_status'] = $status[$val['status']];
                if (in_array($val['status'], [4, 5])) {
                    $profit = floatval($val['win_amount']) - intval($val['bet_money']);
                    $val['profit'] = $profit;
                    $val['margins'] = round($profit / intval($val['bet_money']) * 100, 2) . '%';
                } else {
                    $val['profit'] = '';
                    $val['margins'] = '';
                }
            }
        } else {
            $orderData = null;
        }

        $data['status_name'] = $data['status'] == 1 ? '等待接单' : ($data['status'] == 2 ? '进行中' : ($data['status'] == 3 ? '结算' : ($data['status'] == 4 ? '拒绝接单' : '未支付')));
        $data['bet_list'] = $orderData;
        $data['bet_total'] = $totle;
        return $data;
    }

    /**
     * 认购计划
     * @param type $orderCode
     * @param type $outer_no
     * @param type $total_amount
     * @return boolean
     */
    public static function planNotify($orderCode, $outer_no, $total_amount, $payTime) {
        $tran = Yii::$app->db->beginTransaction();
		try
		{
			$userPlan = UserPlan::find()->where(["user_plan_code" => $orderCode])->andWhere(["status" => 0])-> // 0、未支付
one();
			if ($userPlan != null)
			{
				$userData = User::find()->select(['user.cust_no','uf.all_funds','user.user_name','user.user_tel'])->leftJoin('user_funds as uf', 'uf.cust_no = user.cust_no')->where(['user.user_id' => $userPlan->user_id])->asArray()
                        ->one();
                  $ret=PayRecord::upData([
                            "status" => 1,
                            "outer_no" => $outer_no,
                            "modify_time" => date("Y-m-d H:i:s"),
                            "pay_time" => $payTime,
                            "pay_money" => $total_amount,
                            "balance" => $userData["all_funds"]
                                ], [
                            "order_code" => $orderCode,
                            "pay_type" => 7
                        ]);
                /*$ret = \Yii::$app->db->createCommand()->update("pay_record", [
                            "status" => 1,
                            "outer_no" => $outer_no,
                            "modify_time" => date("Y-m-d H:i:s"),
                            "pay_time" => $payTime,
                            "pay_money" => $total_amount,
                            "balance" => $userData["all_funds"]
                                ], [
                            "order_code" => $orderCode,
                            "pay_type" => 7
                        ])->execute();
                        */
                if ($ret === false) {
                    return ["code" => 109, "msg" => "数据更新失败"];
                }
                /*$userPlan->status = 1; //等待接单
                $userPlan->modify_time = date('Y-m-d H:i:s');
                */
                if (!UserPlan::upData(['status'=>1,'modify_time'=>date('Y-m-d H:i:s')], ['user_plan_id'=>$userPlan['user_plan_id']])) {
                    return ["code" => 109, "msg" => json_encode($userPlan->getFirstErrors(), true)];
                }
            }
            $planData = Plan::find()->select(['title'])->where(['plan_id' => $userPlan->plan_id])->asArray()->one();
            $tran->commit();
            $redis = Yii::$app->redis;
            $arr = [];
            $storeData = User::find()->select(['user.user_id', 'user.cust_no', 'tu.third_uid'])
                    ->leftJoin('third_user as tu', 'tu.uid = user.user_id')
                    ->where(["user.user_id" => $userPlan->store_id])
                    ->asArray()
                    ->one();
            $redis->sadd("sockets:new_order_list", $storeData['cust_no']);
            $userOperators = StoreOperator::find()->select(['u.cust_no', 'tu.third_uid'])
                    ->leftJoin('user as u', 'u.user_id = store_operator.user_id')
                    ->leftJoin('third_user as tu', 'tu.uid = u.user_id')
                    ->where(['store_operator.store_id' => $storeData['user_id'], 'store_operator.status' => 1])
                    ->asArray()
                    ->all();
            foreach ($userOperators as $val) {
                $redis->sadd("sockets:new_order_list", $val["cust_no"]);
                array_push($arr, $val['third_uid']);
            }
            array_push($arr, $storeData['third_uid']);
            $wechatTool = new WechatTool();
            $title = '您有新的计划订单，请及时接单！';
            $code = $userPlan->user_plan_code;
            $userName = $userData['user_name'] . '(' . $userData['user_tel'] . '）';
            $planMoney = $userPlan->buy_money . '元';
            $planTitle = $planData['title'];
            if(empty($userPlan->end_time)){
                $endTime = '不定期';
            }  else {
                $endTime = $userPlan->end_time;           
            }
            $remark = '计划定投';
            foreach ($arr as $v) {
                if ($v) {
                    $wechatTool->sendTemplateMsgBetStore($title, $v, $code, $userName, $planMoney, $planTitle, $endTime, $remark);
                }            }
            return true;
        } catch (\yii\db\Exception $e) {
            return ["code" => 109, "msg" => json_encode($e, true)];
        }
    }

    public static function planPay($orderCode, $userPlanId) {
        $lotAddInfo = LotteryAdditional::find()
                ->join("left join", "lottery_order", "lottery_order.lottery_additional_id=lottery_additional.lottery_additional_id")
                ->where(["lottery_order.lottery_order_code" => $orderCode])
                ->andWhere(["lottery_additional.pay_status" => 0])
                ->asArray()
                ->one();
        if ($lotAddInfo == null) {
            return ["code" => "2", "msg" => "订单处理失败"];
        }
        $betMoney = $lotAddInfo["total_money"];
        $ret = self::operateUserPlanFunds($userPlanId, (0 - $betMoney), $betMoney);
        if ($ret["code"] != 0) {
            return $ret;
        }
        $ret = self::userPlanNotify($orderCode);
        if ($ret == false) {
            return ["code" => "2", "msg" => "订单处理失败"];
        } else {
            return ["code" => "600", "msg" => "投注成功", "lottery_order_code" => $orderCode];
        }
    }

    public static function operateUserPlanFunds($userPlanId, $ableFunds, $bettingFunds) {
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            $userPlan = (new Query())->select("*")->from("user_plan")->where(["user_plan_id" => $userPlanId])->one();
            if ($userPlan == null) {
                return ["code" => 2, "msg" => "计划用户错误"];
            }
            if (($userPlan["able_funds"] + $ableFunds) < 0) {
                return ["code" => 407, "msg" => "计划用户余额不足"];
            }
            if (($userPlan["betting_funds"] + $bettingFunds) < 0) {
                return ["code" => 407, "msg" => "计划用户余额不足"];
            }
            Yii::$app->db->createCommand("update user_plan set able_funds=able_funds+{$ableFunds},betting_funds=betting_funds+{$bettingFunds} where user_plan_id={$userPlanId}")->execute();
            $tran->commit();
            return ["code" => 0, "msg" => "支付成功"];
        } catch (yii\base\Exception $e) {
            $tran->rollBack();
            return ["code" => 2, "msg" => "错误"];
        }
    }

    public static function userPlanNotify($orderCode) {
        $db = \Yii::$app->db;
        $lotOrder = LotteryOrder::find()
                ->where(["lottery_order_code" => $orderCode])
                ->andWhere(["status" => "1"])
                ->asArray()
                ->one();
//        $lotAddInfo = LotteryAdditional::find()
//                ->where(["lottery_additional_id" => $lotOrder["lottery_additional_id"]])
//                ->andWhere(["pay_status" => "0"])
//                ->asArray()
//                ->one();
        if ($lotOrder != null) {
            LotteryAdditional::updateAll([
                "pay_status" => "1",
                "status" => "2"
                    ], "lottery_additional_id='{$lotOrder["lottery_additional_id"]}' and pay_status=0");
            LotteryOrder::upData([
                "status" => "2"
                    ],  [
                "lottery_order_id" => $lotOrder["lottery_order_id"]
            ]);
            /*$db->createCommand()->update("lottery_order", [
                "status" => "2"
                    ], [
                "lottery_order_id" => $lotOrder["lottery_order_id"]
            ])->execute();*/
            //$lotteryqueue = new \LotteryQueue();
            //$lotteryqueue->pushQueue('lottery_job', 'default', ["orderId" => $lotOrder["lottery_order_id"]]);
            KafkaService::addQue('LotteryJob', ["orderId" => $lotOrder["lottery_order_id"]],true);
        }
        return true;
    }

    /**
     * 结算计划
     * @param type $storeId
     * @param type $userPlanId
     * @return type
     * @throws Exception
     */
    public static function settle($storeId, $userPlanId) {
        $userPlan = UserPlan::find()->where(['user_plan_id' => $userPlanId, 'status' => 2])->one();
        $user = (new Query)->select(['cust_no', 'user_name', 'user_id'])->from('user')->where(['user_id' => $userPlan->user_id])->one();
        if (empty($userPlan)) {
            return ['code' => 109, 'msg' => '该计划已结算'];
        }
        $orderCode = $userPlan->user_plan_code;
        if (!empty($userPlan->betting_funds)) {
            return ['code' => 109, 'msg' => '该计划还在购彩中,不可结算'];
        }
        $jsval = $userPlan->able_funds;
        $funds = new FundsService();
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            $store = Store::find()->select(['cust_no', 'store_type', 'store_name', 'user_id'])->where(['user_id' => $storeId])->asArray()->one();
            $ret = $funds->operateUserFunds($store['cust_no'], -$jsval, -$jsval, 0, true, '结算计划');
            if ($ret['code'] != 0) {
                throw new Exception($ret['msg']);
            }
            $storeFunds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $store['cust_no']])->one();
            $payRecord = new PayRecord();
            $payRecord->order_code = $orderCode;
            $payRecord->pay_no = Commonfun::getCode("PAY", "L");
            $payRecord->outer_no = Commonfun::getCode("DT", "JSFK");
            $payRecord->user_id = $store['user_id'];
            $payRecord->cust_no = $store['cust_no'];
            $payRecord->cust_type = 2;
            $payRecord->user_name = $store['store_name'];
            $payRecord->pay_pre_money = $jsval;
            $payRecord->pay_name = '余额';
            $payRecord->way_name = '余额';
            $payRecord->way_type = 'YE';
            $payRecord->pay_way = 3;
            $payRecord->pay_type_name = '定投计划-结算付款';
            $payRecord->pay_type = 13;
            $payRecord->body = '定投计划-结算付款';
            $payRecord->status = 1;
            $payRecord->balance = $storeFunds["all_funds"];
            $payRecord->pay_time = date('Y-m-d H:i:s');
            $payRecord->modify_time = date('Y-m-d H:i:s');
            $payRecord->create_time = date('Y-m-d H:i:s');
            if (!$payRecord->validate()) {
                throw new Exception('门店支出写入验证失败');
            }
            if (!$payRecord->save()) {
                throw new Exception('门店支出写入保存失败');
            }
            $ret1 = $funds->operateUserFunds($user['cust_no'], $jsval, $jsval, 0, false, '结算计划');
            if ($ret1['code'] != 0) {
                throw new Exception($ret1['msg']);
            }
            $userFunds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $user['cust_no']])->one();
            $upayRecord = new PayRecord();
            $upayRecord->order_code = $orderCode;
            $upayRecord->pay_no = Commonfun::getCode("PAY", "L");
            $upayRecord->outer_no = Commonfun::getCode("DT", "JSSK");
            $upayRecord->user_id = $user['user_id'];
            $upayRecord->cust_no = $user['cust_no'];
            $upayRecord->cust_type = 1;
            $upayRecord->user_name = $user['user_name'];
            $upayRecord->pay_pre_money = $jsval;
            $upayRecord->pay_name = '余额';
            $upayRecord->way_name = '余额';
            $upayRecord->way_type = 'YE';
            $upayRecord->pay_way = 3;
            $upayRecord->pay_type_name = '定投计划-结算收款';
            $upayRecord->pay_type = 12;
            $upayRecord->body = '定投计划-结算收款';
            $upayRecord->status = 1;
            $upayRecord->balance = $userFunds["all_funds"];
            $upayRecord->pay_time = date('Y-m-d H:i:s');
            $upayRecord->modify_time = date('Y-m-d H:i:s');
            $upayRecord->create_time = date('Y-m-d H:i:s');
            if (!$upayRecord->validate()) {
                throw new Exception('会员收入写入验证失败');
            }
            if (!$upayRecord->save()) {
                throw new Exception('会员收入写入保存失败');
            }

            $userPlan->able_funds = 0;
            $userPlan->status = 3;
            $userPlan->modify_time = date('Y-m-d H:i:s');
            if (!$userPlan->save()) {
                throw new Exception('数据更新失败');
            }
            $tran->commit();
            return ['code' => 600, 'msg' => '结算成功'];
        } catch (Exception $ex) {
            $tran->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }

    /**
     * 出票失败，退款
     * @param type $userPlanId
     * @param type $betMoney
     * @return type
     */
    public static function outFalse($userPlanId, $betMoney) {
        $userPlan = UserPlan::find()->select(['user_plan_id'])->where(['user_plan_id' => $userPlanId])->asArray()->one();
        if (empty($userPlan)) {
            return false;
        }
        $planResult = self::operateUserPlanFunds($userPlanId, $betMoney, -$betMoney);
        if ($planResult["code"] != 0) {
            return false;
        }
        return $planResult;
    }

    /**
     * 拒绝计划接单
     * @param type $storeId
     * @param type $userPlanId
     * @return type
     * @throws Exception
     */
    public static function refuse($userPlanId) {
        $userPlan = UserPlan::find()->where(['user_plan_id' => $userPlanId, 'status' => 1])->one();
        $user = (new Query)->select(['cust_no', 'user_name', 'user_id'])->from('user')->where(['user_id' => $userPlan->user_id])->one();
        if (empty($userPlan)) {
            return ['code' => 109, 'msg' => '该计划已处理'];
        }
        $orderCode = $userPlan->user_plan_code;
        $jsval = $userPlan->able_funds;
        $funds = new FundsService();
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            $ret1 = $funds->operateUserFunds($user['cust_no'], $jsval, $jsval, 0, true, '结算计划');
            if ($ret1['code'] != 0) {
                throw new Exception($ret1['msg']);
            }
            $userFunds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $user['cust_no']])->one();
            $upayRecord = new PayRecord();
            $upayRecord->order_code = $orderCode;
            $upayRecord->pay_no = Commonfun::getCode("PAY", "L");
            $upayRecord->outer_no = Commonfun::getCode("DT", "JJJD");
            $upayRecord->user_id = $user['user_id'];
            $upayRecord->cust_no = $user['cust_no'];
            $upayRecord->cust_type = 1;
            $upayRecord->user_name = $user['user_name'];
            $upayRecord->pay_pre_money = $jsval;
            $upayRecord->pay_name = '余额';
            $upayRecord->way_name = '余额';
            $upayRecord->way_type = 'YE';
            $upayRecord->pay_way = 3;
            $upayRecord->pay_type_name = '定投计划-拒绝接单';
            $upayRecord->pay_type = 17;
            $upayRecord->body = '定投计划-拒绝接单';
            $upayRecord->status = 1;
            $upayRecord->balance = $userFunds["all_funds"];
            $upayRecord->pay_time = date('Y-m-d H:i:s');
            $upayRecord->modify_time = date('Y-m-d H:i:s');
            $upayRecord->create_time = date('Y-m-d H:i:s');
            if (!$upayRecord->validate()) {
                throw new Exception('会员收入写入验证失败');
            }
            if (!$upayRecord->save()) {
                throw new Exception('会员收入写入保存失败');
            }

            $userPlan->able_funds = 0;
            $userPlan->status = 4;
            $userPlan->modify_time = date('Y-m-d H:i:s');
            if (!$userPlan->save()) {
                throw new Exception('数据更新失败');
            }
            $tran->commit();
            return ['code' => 600, 'msg' => '拒绝接单成功'];
        } catch (Exception $ex) {
            $tran->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }

    /**
     * 客户认购计划接单
     * @param type $userPlanId 客户计划ID
     * @return boolean
     */
    public static function accept($storeId, $userPlanId) {
        $userPlan = UserPlan::find()->where(['user_plan_id' => $userPlanId, 'status' => 1])->one();
        if (empty($userPlan)) {
            return ['code' => 109, 'msg' => '该计划已处理'];
        }
        $orderCode = $userPlan->user_plan_code;
        $db = Yii::$app->db;
        $store = Store::find()->select(['cust_no', 'user_id', 'store_name'])->where(['user_id' => $storeId])->asArray()->one();
        $total = $userPlan->able_funds;
        $funds = new FundsService();
        $trans = $db->beginTransaction();
        try {
            $retStore = $funds->operateUserFunds($store['cust_no'], $total, $total, 0, true, '认购计划');
            if ($retStore['code'] != 0) {
                return ["code" => 109, "msg" => $retStore["msg"]];
            }
            $storeFunds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $store['cust_no']])->one();
            $payRecord = new PayRecord();
            $payRecord->order_code = $orderCode;
            $payRecord->pay_no = Commonfun::getCode("PAY", "L");
            $payRecord->outer_no = Commonfun::getCode("DT", "SK");
            $payRecord->user_id = $store['user_id'];
            $payRecord->cust_no = $store['cust_no'];
            $payRecord->cust_type = 2;
            $payRecord->user_name = $store['store_name'];
            $payRecord->pay_pre_money = $total;
            $payRecord->pay_money = $total;
            $payRecord->pay_name = '余额';
            $payRecord->way_name = '余额';
            $payRecord->way_type = 'YE';
            $payRecord->pay_way = 3;
            $payRecord->pay_type_name = '定投计划-收款';
            $payRecord->pay_type = 8;
            $payRecord->balance = $storeFunds["all_funds"];
            $payRecord->body = '定投计划-收款';
            $payRecord->status = 1;
            $payRecord->pay_time = date('Y-m-d H:i:s');
            $payRecord->modify_time = date('Y-m-d H:i:s');
            $payRecord->create_time = date('Y-m-d H:i:s');
            if (!$payRecord->validate()) {
                return ["code" => 109, "msg" => json_encode($payRecord->getFirstErrors(), true)];
            }
            if (!$payRecord->saveData()) {
                return false;
            }
            $plan = Plan::find()->where(['plan_id' => $userPlan->plan_id])->one();
            $plan->buy_nums = $plan->buy_nums + 1;
            $plan->buy_amount = $plan->buy_amount + $total;
            $plan->modify_time = date('Y-m-d H:i:s');
            if (!$plan->save()) {
                return ["code" => 109, "msg" => json_encode($plan->getFirstErrors(), true)];
            }
            $userPlan->status = 2;
            $userPlan->modify_time = date('Y-m-d H:i:s');
            if (!$userPlan->save()) {
                return ["code" => 109, "msg" => json_encode($userPlan->getFirstErrors(), true)];
            }

            $fundsSer = new FundsService();
            $serviceCharge = (ceil($total * 0.2) / 100);
            $ret = $fundsSer->operateUserFunds($store['cust_no'], 0 - $serviceCharge, 0 - $serviceCharge, 0, false);
            if ($ret["code"] != 0) {
                $trans->rollBack();
                return ['code' => 109, 'msg' => '接单失败'];
            }
            $chargeFunds = (new Query())->select("all_funds")->from("user_funds")->where(["cust_no" => $store['cust_no']])->one();
            $chargePayRecord = new PayRecord();
            $chargePayRecord->order_code = $orderCode;
            $chargePayRecord->pay_no = Commonfun::getCode("PAY", "L");
            $chargePayRecord->user_id = $store['user_id'];
            $chargePayRecord->cust_no = $store['cust_no'];
            $chargePayRecord->cust_type = 2;
            $chargePayRecord->user_name = $store['store_name'];
            $chargePayRecord->pay_pre_money = $serviceCharge;
            $chargePayRecord->pay_money = $serviceCharge;
            $chargePayRecord->pay_name = '余额';
            $chargePayRecord->way_name = '余额';
            $chargePayRecord->way_type = 'YE';
            $chargePayRecord->pay_way = 3;
            $chargePayRecord->pay_type_name = '出票服务费';
            $chargePayRecord->pay_type = 16;
            $chargePayRecord->body = '计划服务费';
            $chargePayRecord->status = 1;
            $chargePayRecord->balance = $chargeFunds["all_funds"];
            $chargePayRecord->pay_time = date('Y-m-d H:i:s');
            $chargePayRecord->modify_time = date("Y-m-d H:i:s");
            $chargePayRecord->create_time = date('Y-m-d H:i:s');
            if ($chargePayRecord->validate()) {
                $ret = $chargePayRecord->saveData();
                if ($ret == false) {
                    $trans->rollBack();
                    return ['code' => 109, 'msg' => '接单失败'];
                }
            } else {
                $trans->rollBack();
                return ['code' => 109, 'msg' => '接单失败'];
            }
            $trans->commit();
            return ['code' => 600, 'msg' => '接单成功'];
        } catch (Exception $ex) {
            $trans->rollBack();
            return ['code' => 109, 'msg' => '接单失败'];
        }
    }

}
