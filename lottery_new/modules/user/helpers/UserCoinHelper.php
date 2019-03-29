<?php

namespace app\modules\user\helpers;

use app\modules\user\models\UserGlCoinRecord;
use app\modules\common\models\UserFunds;
use app\modules\user\models\User;
use app\modules\common\helpers\Commonfun;
use app\modules\user\models\ThirdUser;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\PayRecord;
use app\modules\user\models\UserActive;
use app\modules\user\models\UserCoinCzType;
use Yii;
use yii\db\Expression;
use yii\base\Exception;
use app\modules\common\services\PayService;
use yii\db\Query;

class UserCoinHelper {

    /**
     * 免费咕币来源
     */
    const FEE_COIN_SOURCE_NAME = [1 => '注册', 2 => '补全资料', 3 => '实名', 4 => 'APP好评', 5 => '微信绑定', 6 => '每日登录', 7 => '每日首购分享', 8 => '每日使用666个咕币', 9 => '每日分享APP', 11 => '充值赠送'];
    const FEE_COIN_SOURCE = [1 => 'register', 2 => 'info', 3 => 'auth', 4 => 'appwell', 5 => 'bandwechat', 6 => 'login', 7 => 'betday', 8 => 'usecoin', 9 => 'shareapp', 11 => 'czgive'];

    /**
     * 来源获得值
     */
    const FEE_COIN_SOURCE_VALUE = [1 => 10, 2 => 15, 3 => 20, 4 => 15, 5 => 10, 6 => 5, 7 => 10, 8 => 66, 9 => 10];

    /**
     * 旧咕币的兑换
     * @return boolean
     */
    public static function replaceCoin() {
        $userCoin = UserFunds::find()->select(['user_glcoin', 'cust_no', 'user_id'])->asArray()->all();
        $sql = '';
        $formate = date('Y-m-d H:i:s');
        foreach ($userCoin as $val) {
            if (!empty($val['user_glcoin'])) {
                $newGlcoin = ceil($val['user_glcoin'] / 10);
                $sql .= "update user_funds set user_glcoin = {$newGlcoin}, modify_time = '{$formate}' where cust_no = {$val['cust_no']} and user_id = {$val['user_id']} and user_glcoin = {$val['user_glcoin']};";
            }
        }
        $db = \Yii::$app->db;
        $ret = $db->createCommand($sql)->execute();
        if ($ret === false) {
            return false;
        }
        return true;
    }

    /**
     * 咕币变化操作
     * @param type $custNo
     * @param type $coinValue 变化值
     * @return type
     * @throws Exception
     */
    public static function operateUserCoin($custNo, $coinValue) {
        $db = Yii::$app->db;
        $tran = $db->beginTransaction();
        try {
            $userFunds = UserFunds::find()->select(['user_glcoin'])->where(['cust_no' => $custNo])->asArray()->one();
            if (empty($userFunds)) {
                throw new Exception('用户错误', 109);
            }
            $where = ['and', ['=', 'cust_no', $custNo]];
            if ($coinValue < 0) {
                $where[] = ['>=', 'user_glcoin', abs($coinValue)];
            }
            if (bcadd($userFunds["user_glcoin"], $coinValue) < 0) {
                throw new Exception('咕币余额不足', 407);
            }
            $update = ['user_glcoin' => new Expression('user_glcoin+' . $coinValue), 'modify_time' => date('Y-m-d H:i:s')];
            if (!UserFunds::upData($update, $where)) {
                throw new Exception('咕币余额不足', 110);
            }
            $tran->commit();
            return ["code" => 600, "msg" => "保存成功"];
        } catch (Exception $e) {
            $tran->rollBack();
            return ["code" => $e->getCode(), "msg" => $e->getMessage()];
        }
    }

    /**
     * 获取领取列表
     * @param type $userId
     */
    public static function getUserCoinList($userId, $custNo) {
        $basicCoin = self::getBasicCoin($userId, $custNo);

        return $basicCoin;
    }

    /**
     * 获取基础任务领取状态
     * @param type $userId
     * @param type $custNo
     * @return int
     */
    public static function getBasicCoin($userId, $custNo) {
        $feeCoinSource = self::FEE_COIN_SOURCE;
        $feeCoinSourceName = self::FEE_COIN_SOURCE_NAME;
        $coinValue = self::FEE_COIN_SOURCE_VALUE;
        $coinList = UserActive::find()->select(['active_type', 'receive_status', 'status'])->where(['user_id' => $userId])->andWhere(['in', 'active_type', $feeCoinSource])->indexBy('active_type')->asArray()->all();
        $basicsList = [];
        $date = date('Y-m-d');
        $start = $date . ' 00:00:00';
        $end = $date . '23:59:59';
        foreach ($feeCoinSource as $k => $val) {
            switch ($val) {
                case 'register':
                    $isReceice = isset($coinList[$val]) ? 3 : 2;  // 1：表示未完成任务 2：已完成未领取 3：已领取
                    break;
                case 'info':
                    if (isset($coinList[$val])) {
                        $isReceice = 3;
                    } else {
                        $userInfo = User::find()->select(['user_name', 'user_pic', 'province', 'city', 'area', 'address'])->where(['user_id' => $userId])->asArray()->one();
                        $isReceice = (empty($userInfo['user_pic']) || empty($userInfo['province']) || empty($userInfo['address'])) ? 1 : 2;
                    }
                    break;
                case 'auth':
                    if (isset($coinList[$val])) {
                        $isReceice = 3;
                    } else {
                        $authInfo = Commonfun::javaGetStatus($custNo);
                        $isReceice = $authInfo['data']['checkStatus'] != 1 ? 1 : 2;
                    }
                    break;
                case 'appwell':
                    $isReceice = isset($coinList[$val]) ? 3 : 1;
                    break;
                case 'bandwechat':
                    if (isset($coinList[$val])) {
                        $isReceice = 3;
                    } else {
                        $thirdUser = ThirdUser::find()->select(['uid'])->where(['uid' => $userId])->asArray()->one();
                        $isReceice = empty($thirdUser) ? 1 : 2;
                    }
                    break;
                case 'login':
                    $isReceice = (isset($coinList[$val]) && $coinList[$val]['receive_status'] == 2 ) ? 3 : 2;
                    break;
                case 'betday':
                    if (isset($coinList[$val]) && $coinList[$val]['receive_status'] == 2) {
                        $isReceice = 3;
                    } else {
                        $shareInfo = LotteryOrder::find()->select(['cust_no'])
                                ->innerJoin('order_share s', 's.order_id = lottery_order.lottery_order_id')
                                ->where(['user_id' => $userId])
                                ->andWhere(['between', 'lottery_order.create_time', $start, $end])
                                ->asArray()
                                ->one();
                        $isReceice = empty($shareInfo) ? 1 : 2;
                    }
                    break;
                case 'usecoin':
                    if (isset($coinList[$val]) && $coinList[$val]['receive_status'] == 2) {
                        $isReceice = 3;
                    } else {
                        $payCoin = UserGlCoinRecord::find()->select(['sum(coin_value) sum_coin_value'])->where(['user_id' => $userId, 'type' => 2, 'status' => 1])->andWhere(['between', 'create_time', $start, $end])->asArray()->one();
                        $isReceice = empty($payCoin) ? 1 : (bccomp($payCoin['sum_coin_value'], 666) != '-1' ? 2 : 1);
                    }
                    break;
                case 'shareapp':
                    $isReceice = 1;
                    break;
                case 'czgive':
                    if (isset($coinList[$val]) && $coinList[$val]['status'] == 1) {
                        $isReceice = $coinList[$val]['receive_status'] == 2 ? 3 : 2;
                    } else {
                        $isReceice = 1;
                    }
            }
            $basicsList[] = ['coin_source_type' => $k, 'coin_source' => $val, 'coin_source_neme' => $feeCoinSourceName[$k], 'coin_value' => $coinValue[$k], 'is_receive' => $isReceice];
        }
        return $basicsList;
    }

    /**
     * 咕币充值
     * @return boolean
     */
    public static function coinRecharge($coinCzType, $custNo, $userId, $custType) {
        if ($coinCzType == 'ccz009' || $coinCzType == 'ccz010') {
            $where = ['and', ['user_id' => $userId, 'status' => 2], ['or', ['active_type' => 'ccz009'], ['active_type' => 'ccz010']]];
            $wmData = UserActive::find()->select(['active_type'])->where($where)->asArray()->one();
            if (!empty($wmData)) {
                $remark = $wmData['active_type'] == 'ccz009' ? '周卡' : '月卡';
                return ['code' => 109, 'msg' => '您还有享有' . $remark . '特权,暂时无法购买周卡月卡！'];
            }
        }
        $coinCzInfo = self::getCoinCzType($coinCzType);
        $ret = self::createCoinRecord($custNo, $userId, $coinCzType, 10, 1,$coinCzInfo[$coinCzType]['cz_money']);
        if ($ret['code'] != 600) {
            return $ret;
        }
        $orderCode = $ret['orderCode'];
        $payService = new PayService();
        $payService->productPayRecord($custNo, $orderCode, 23, $custType, $coinCzInfo[$coinCzType]['cz_money'], 13, $userId);
        $data['coin_cz_code'] = $orderCode;
        return ['code' => 600, 'msg' => '下单成功', 'result' => $data];
    }

    /**
     * 写入记录表
     * @param type $custNo
     * @param type $userId
     * @param type $coinCzType
     * @param type $money
     * @return type
     */
    public static function createCoinRecord($custNo, $userId, $sourceType, $source, $recordType, $money = 0) {
        $orderCode = Commonfun::getCode("CRC", "C");
        $userCoinRecord = new UserGlCoinRecord();
        $userCoinRecord->order_code = $orderCode;
        $userCoinRecord->cust_no = $custNo;
        $userCoinRecord->user_id = $userId;
        $userCoinRecord->type = $recordType;
        $userCoinRecord->coin_source = $source;
        $userCoinRecord->value_money = $money;
        $userCoinRecord->source_type = $sourceType;
        $userCoinRecord->status = 0;
        $userCoinRecord->create_time = date('Y-m-d');
        if ($userCoinRecord->validate()) {
            return ['code' => 109, 'msg' => '咕币变化记录验证失败'];
        }
        if (!$userCoinRecord->save()) {
            return ['code' => 109, 'msg' => '咕币变化记录存储失败'];
        }
        return ['code' => 600, 'msg' => 'succ', 'orderCode' => $orderCode, 'orderId' => $userCoinRecord->gl_coin_record_id];
    }

    /**
     * 咕币充值回调
     * @param type $payCode
     * @param type $outerNo
     * @return boolean
     * @throws Exception
     */
    public static function coinRechargeNotify($payCode, $outerNo) {
        $coinRecord = UserGlCoinRecord::findOne(['order_code' => $payCode, 'status' => 0]);
        if (empty($coinRecord)) {
            return ['code' => 109, 'msg' => '该订单不存在'];
        }
        $coinCzType = self::getCoinCzType($coinRecord->source_type);
        if (empty($coinCzType)) {
            return ['code' => 109, 'msg' => '该充值类型已暂停销售！！'];
        }
        $userActive = UserActive::findOne(['user_id' => $coinRecord->user_id, 'active_type' => $coinRecord->source_type]);
        $typeInfo = $coinCzType[$coinRecord->source_type];
        $coinValue = 0;
        $startTime = $endTime = date('Y-m-d H:i:s');
        $receiveCoin = 0;
        $receiveStatus = 1;
        switch ($typeInfo['weal_type']) {
            case 1:
                $coinValue = $typeInfo['cz_coin'];
                $receiveCoin = 0;
                $receiveStatus = 2;
                break;
            case 2:
                $coinValue = bcmul(bcadd(1, bcdiv($typeInfo['weal_value'], 100, 2), 2), $typeInfo['cz_coin']);
                $receiveCoin = empty($userActive) ? bcmul($typeInfo['weal_value'], $typeInfo['cz_coin']) : 0;
                $receiveStatus = 2;
                break;
            case 3:
                $coinValue = empty($userActive) ? bcmul(bcadd(1, bcdiv($typeInfo['weal_value'], 100, 2), 2), $typeInfo['cz_coin']) : $typeInfo['cz_coin'];
                $receiveCoin = empty($userActive) ? bcmul($typeInfo['weal_value'], $typeInfo['cz_coin']) : 0;
                $receiveStatus = 2;
                break;
            case 4:
                $coinValue = $typeInfo['cz_coin'];
                $receiveCoin = $typeInfo['weal_value'];
                $startTime = date('Y-m-d 00:00:00', strtotime('+1 day'));
                $endTime = date('Y-m-d 23:59:59', strtotime("+{$typeInfo['weal_time']} day"));
                $czActive = UserActive::findOne(['active_type' => 'czgive']);
                if (empty($czActive)) {
                    $czActive = new UserActive();
                    $czActive->user_id = $coinRecord->user_id;
                    $czActive->active_type = 'czgive';
                    $czActive->create_time = date('Y-m-d H:i:s');
                }
                $czActive->source_id = $coinRecord->gl_coin_record_id;
                $czActive->active_coin_value = $receiveCoin;
                $czActive->receive_status = $receiveStatus;
                $czActive->status = 1;
                $czActive->start_time = $startTime;
                $czActive->end_time = $endTime;
                $czActive->modify_time = date('Y-m-d H:i:s');
                if (!$czActive->save()) {
                    throw new Exception('会员行为表保存失败', 109);
                }
                break;
            default :
                $coinValue = $typeInfo['cz_coin'];
                break;
        }
        $db = \Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            $coinFunds = self::operateUserCoin($coinRecord->cust_no, $coinValue);
            if ($coinFunds['code'] != 600) {
                throw new Exception($coinFunds['msg'], $coinValue['code']);
            }
            $balanceCoin = UserFunds::find()->select(['user_glcoin'])->where(['cust_no' => $coinRecord->cust_no])->asArray()->one();
            $coinRecord->coin_value = $coinValue;
            $coinRecord->totle_balance = $balanceCoin['user_glcoin'];
            $coinRecord->status = 1;
            $coinRecord->modify_time = date('Y-m-d');
            if (!$coinRecord->validate()) {
                throw new Exception('咕币变化记录验证失败', 109);
            }
            if (!$coinRecord->save()) {
                throw new Exception('咕币变化记录存储失败', 109);
            }
            if (empty($userActive)) {
                $userActive = new UserActive();
                $userActive->user_id = $coinRecord->user_id;
                $userActive->active_type = $coinRecord->source_type;
                $userActive->create_time = date('Y-m-d H:i:s');
            }
            $userActive->source_id = $coinRecord->gl_coin_record_id;
            $userActive->active_coin_value = $receiveCoin;
            $userActive->receive_status = $receiveStatus;
            $userActive->status = 1;
            $userActive->start_time = $startTime;
            $userActive->end_time = $endTime;
            $userActive->modify_time = date('Y-m-d');
            if (!$userActive->save()) {
                throw new Exception('会员行为表保存失败', 109);
            }
            $funds = UserFunds::find()->select(['all_funds'])->where(['cust_no' => $coinRecord->cust_no])->asArray()->one();
            PayRecord::upData([
                "status" => 1,
                "outer_no" => $outerNo,
                "modify_time" => date("Y-m-d H:i:s"),
                "pay_time" => date("Y-m-d H:i:s"),
                "pay_money" => $typeInfo['cz_money'],
                "balance" => $funds["all_funds"]
                    ], [
                "order_code" => $payCode
            ]);
            $trans->commit();
            return true;
        } catch (Exception $ex) {
            $trans->rollBack();
            return ['code' => $ex->getCode(), 'msg' => $ex->getMessage()];
        }
    }

    /**
     * 获取充值类型
     * @param type $coinCzType
     * @return type
     */
    public static function getCoinCzType($coinCzType = '') {
        if (!empty($coinCzType)) {
            $where['cz_type'] = $coinCzType;
        } else {
            $where['status'] = 1;
        }
        $czType = UserCoinCzType::find()->select(['cz_type', 'cz_type_name', 'cz_money', 'cz_coin', 'weal_type', 'weal_value', 'weal_time', 'status'])->where($where)->indexBy('cz_type')->asArray()->all();
        return $czType;
    }

    /**
     * 获取咕啦币明细
     * @param $user_id  用户id
     * @param $page     当前分页
     * @param $size     每页条数
     * @param $type     筛选：0=全部，1=获取，2=使用
     * @return array
     */
    public static function getCoinRecordList($userId, $pn, $size, $type) {
        $where = ['user_id' => $userId];
        if ($type != 0) {
            $where['type'] = $type;
        }
        $where['status'] = 1;
        $offset = ($pn - 1) * $size;
        $fields = ['order_code', 'cust_no', 'type', 'coin_source', 'coin_value', 'totle_balance', 'remark', 'status', 'source_type'];
        $query = new Query();
        $query = $query->select($fields)->from('user_gl_coin_record')
                ->where($where);
        $total = $query->count();
        $pages = ceil($total / $size);
        $infos = $query->orderBy("create_time desc")->offset($offset)->limit($size)->all();
        $count = count($infos);
        //获取的
        $income = UserGlCoinRecord::find()->where(['user_id' => $userId, 'type' => 1])->sum('coin_value');
        //已使用的
        $use = UserGlCoinRecord::find()->where(['user_id' => $userId, 'type' => 2])->sum('coin_value');
        return ['page' => $pn, 'pages' => (int) $pages, 'size' => $count, 'income' => round($income), 'use' => round($use), 'total' => (int) $total, 'data' => $infos];
    }

    /**
     * 领取咕币
     * @param type $userId
     * @param type $custNo
     * @param type $sourceType
     * @return type
     * @throws Exception
     */
    public static function receiveCoin($userId, $custNo, $sourceType) {
        $feeCoinSource = self::FEE_COIN_SOURCE;
        $feeCoinValue = self::FEE_COIN_SOURCE_VALUE;
        $feeSource = $feeCoinSource[$sourceType];
        $userActive = UserActive::findOne(['user_id' => $userId, 'active_type' => $feeSource, 'receive_status' => 2]);
        if (!empty($userActive)) {
            return ['code' => 109, 'msg' => '已领取该奖励'];
        }
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        $sourceId = 0;
        try {
            $active = UserActive::findOne(['user_id' => $userId, 'active_type' => $feeSource]);
            if (empty($active)) {
                $active = new UserActive();
                $active->user_id = $userId;
                $active->active_type = $feeSource;
                $active->active_coin_value = $feeCoinValue[$sourceType];
                $active->create_time = date('Y-m-d H:i:s');
                $coinValue = $feeCoinValue[$sourceType];
            } else {
                if ($active->status == 2) {
                    return ['code' => 109, 'msg' => '该奖励已过有效期, 无法领取'];
                }
                $sourceId = $active->source_id;
                $coinValue = $active->active_coin_value;
            }
            $active->receive_status = 2;
            $active->modify_time = date('Y-m-d H:i:s');
            if (!$active->save()) {
                throw new Exception('领取失败！！行为记录失败');
            }
            $ret = self::createCoinRecord($custNo, $userId, $feeSource, $sourceType, 1);
            if($ret['code'] != 600) {
                throw new Exception('领取失败！！咕币变化记录失败');
            } 
            $upCoin = self::operateUserCoin($custNo, $coinValue);
            if($upCoin['code'] != 600) {
                throw new Exception($upCoin['msg']);
            }
            $userCoin = UserFunds::find()->select(['user_glcoin'])->where(['cust_no' => $custNo])->asArray()->one();
            $coinRecord = UserGlCoinRecord::findOne(['gl_coin_record_id' => $ret['orderId']]);
            $coinRecord->coin_value = $coinValue;
            $coinRecord->source_id = $sourceId;
            $coinRecord->totle_balance = $userCoin['user_glcoin'];
            $coinRecord->status = 1;
            $coinRecord->modify_time = date('Y-m-d H:i:s');
            if(!$coinRecord->save()) {
                throw new Exception('领取失败！！咕币变化记录失败');
            }
            $trans->commit();
            return ['code' => 600, 'msg' => '获取成功'];
        } catch (Exception $ex) {
            $trans->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }
    
    /**
     * 定时重置会员行为
     * @return boolean
     */
    public static function resetUserActive() {
        $nowTime = date('Y-m-d H:i:s');
        UserActive::updateAll(['receive_status' => 1], ['in', 'active_type', ['login', 'betday',  'usecoin', 'shareapp']]);
        $czgive = UserActive::find()->select(['user_active_id', 'active_type', 'end_time'])->where(['status' => 1])->andWhere(['>', 'end_time', $nowTime])->indexBy(['user_active_id'])->asArray()->all();
        $czId = array_keys($czgive);
        UserActive::updateAll(['receive_status' => 1], ['in', 'user_active_id', $czId]);
        UserActive::updateAll(['status' => 2], ['<', 'end_time', $nowTime]);
        return true;
    }

}
            