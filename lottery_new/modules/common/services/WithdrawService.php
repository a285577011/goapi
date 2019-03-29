<?php

namespace app\modules\common\services;

use Yii;
use app\modules\common\services\FundsService;
use app\modules\common\models\PayRecord;
use yii\base\Exception;
use app\modules\common\models\Withdraw;
use app\modules\common\models\UserFunds;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\Constants;

class WithdrawService {

    /**
     * 余额提现
     * @auther GL zyl
     * @param type $custNo // 会员编号
     * @param type $total // 提现金额
     * @param type $custType // 提现人类型
     * @return type
     * @throws Exception
     */
    public function balanceWithdraw($custNo, $total, $custType) {
        $withdraw = new Withdraw;
        $withdraw->cust_no = $custNo;
        $withdraw->cust_type = $custType;
        $withdraw->withdraw_code = Commonfun::getCode('TX', 'T');
        $withdraw->withdraw_money = $total;
        $withdraw->status = 0;
        $withdraw->create_time = date('Y-m-d H:i:s');
        if (!$withdraw->validate()) {
            return['code' => 109, 'msg' => '提现表验证失败1'];
        }
        if (!$withdraw->save()) {
            return['code' => 109, 'msg' => '提现表写入失败1'];
        }
        $db = Yii::$app->db;
        $txTran = $db->beginTransaction();
        try {
            $funds = new FundsService();
            $userFunds = $funds->operateUserFunds($custNo, 0, -$total, $total, false, '提现-冻结');
            if ($userFunds['code'] != 0) {
                throw new Exception($userFunds['msg']);
            }
            $funds->iceRecord($custNo, $custType, $withdraw->withdraw_code, $total, 1, "提现-冻结");
            $isEQ = UserFunds::find()->select(['all_funds', 'ice_funds'])->where(['cust_no' => $custNo])->asArray()->one();
            if (floatval($isEQ['all_funds']) < floatval($total)) {
                $withdraw->status = 5;
                $withdraw->remark = '余额不足,无法提现';
                throw new Exception('余额不足,无法提现');
            }
            if (floatval($isEQ['ice_funds']) < floatval($total)) {
                $withdraw->status = 5;
                $withdraw->remark = '冻结资金不足,无法提现';
                throw new Exception('冻结资金不足,无法提现');
            }
            $fee = Constants::WITHDRAW_FEE;

            $javaResult = $this->getJavaWithdraw($custNo, $total, $withdraw->withdraw_code, $fee);
            if ($javaResult == 401) {
                $txTran->rollBack();
                return ['code' => 401, 'msg' => '服务器连接失败，请稍后再试'];
            }
            if ($javaResult['code'] != 1) {
                $withdraw->status = 4;
                $withdraw->remark = $javaResult['msg'];
                throw new Exception($javaResult['msg']);
            }
            $txTran->commit();
        } catch (Exception $ex) {
            $txTran->rollBack();
            $withdraw->modify_time = date('Y-m-d H:i:s');
            $withdraw->save();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
        $withdraw->status = 1;
        $withdraw->modify_time = date('Y-m-d H:i:s');
        if (!$withdraw->save()) {
            return ['code' => 109, 'msg' => '提现失败'];
        }
        $tran = $db->beginTransaction();
        try { // 日志
            $withData = $withdraw->attributes;
            $payRecord = new PayRecord;
            $payRecord->order_code = $withData['withdraw_code'];
            $payRecord->cust_no = $custNo;
            $payRecord->cust_type = $custType;
            $payRecord->pay_no = Commonfun::getCode('PAY', 'L');
            $payRecord->outer_no = $withData['outer_no'];
            $payRecord->pay_pre_money = $total;
            $payRecord->pay_money = $total - $fee;
            $payRecord->pay_name = '余额';
            $payRecord->way_name = '余额';
            $payRecord->way_type = 'YE';
            $payRecord->pay_way = 3;
            $payRecord->pay_type_name = '提现';
            $payRecord->pay_type = 4;
            $payRecord->body = '快捷提现';
            $payRecord->status = 0;
            $payRecord->create_time = date('Y-m-d H:i:s');
            if (!$payRecord->validate()) {
                throw new Exception('明细表验证失败');
            }
            if (!$payRecord->saveData()) {
                throw new Exception('保存失败');
            }
            $feeRecord = new PayRecord;
            $feeRecord->order_code = $withData['withdraw_code'];
            $feeRecord->cust_no = $custNo;
            $feeRecord->cust_type = $custType;
            $feeRecord->pay_no = Commonfun::getCode('PAY', 'L');
            $feeRecord->outer_no = $withData['outer_no'];
            $feeRecord->pay_pre_money = $fee;
            $feeRecord->pay_money = $fee;
            $feeRecord->pay_name = '余额';
            $feeRecord->way_name = '余额';
            $feeRecord->way_type = 'YE';
            $feeRecord->pay_way = 3;
            $feeRecord->pay_type_name = '提现手续费';
            $feeRecord->pay_type = 10;
            $feeRecord->body = '提现手续费';
            $feeRecord->status = 0;
            $feeRecord->create_time = date('Y-m-d H:i:s');
            if (!$feeRecord->validate()) {
                throw new Exception('明细表费用验证失败');
            }
            if (!$feeRecord->saveData()) {
                throw new Exception('保存失败');
            }
            $tran->commit();
            return ['code' => 600, 'msg' => '提现申请已提交,请等待银行处理', 'data' => $withdraw->withdraw_code];
        } catch (Exception $ex) {
            $tran->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }

    /**
     * 提现
     * @param type $custNo // 会员编号
     * @param type $money // 提现金额
     * @return type
     */
    public function getJavaWithdraw($custNo, $money, $withdrawCode, $fee) {
        $surl = \Yii::$app->params['java_doWithdrawals'];
        $AppId = \yii::$app->params['withdraw_AppId'];
        $sanCustNo = \yii::$app->params['withdraw_custNo'];
        $postData = ['appId' => $AppId, 'custNo' => $sanCustNo, 'money' => $money, 'withdrawCustNo' => $custNo, 'thirdFlowNo' => $withdrawCode];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * 提现到账查询
     * @auther GL zyl
     * @return boolean
     */
    public function getWithdrawToAccoutn($withdrawCode) {
//         $withdrawData = Withdraw::find()->where(['status' => 1])->asArray()->one();
//         if (empty($withdrawData)) {
//             return ['code' => 109, 'msg' => '暂无提现数据'];
//         }

        $toAccount = $this->getJavaToAccount($withdrawCode);
        if ($toAccount == 401) {
            return ['code' => 401, 'msg' => '服务器连接失败'];
        }
        if ($toAccount['code'] != 1) {
            return ['code' => 109, 'msg' => $toAccount['msg']];
        }
        if ($toAccount['data']['status'] == '02') {
            return ['code' => 109, 'msg' => '此单还在处理中'];
        }
        if ($toAccount['data']['status'] == '01') {
            if ($toAccount['data']['isArrival'] == '02') {
                return ['code' => 109, 'msg' => '此单还在处理中'];
            }
        }
        $data = $this->withdrawToAccount($withdrawCode, $toAccount['data']);
        return $data;
    }

    /**
     * Java提现查询接口
     * @auther GL zyl
     * @param type $withdrawCode
     * @return type
     */
    public function getJavaToAccount($withdrawCode) {
        $surl = \Yii::$app->params['java_toAccount'];
        $AppId = \yii::$app->params['withdraw_AppId'];
        $sanCustNo = \yii::$app->params['withdraw_custNo'];
        $postData = ['appId' => $AppId, 'custNo' => $sanCustNo, 'thirdFlowNo' => $withdrawCode];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * 提现到账回调
     * @param type $custNo
     * @param type $withdrawCode
     * @param type $type
     * @return type
     * @throws Exception
     */
    public function withdrawToAccount($withdrawCode, $toAccount) {
        $withdrawData = Withdraw::find()->where([ 'withdraw_code' => $withdrawCode])->one();
        $total = $withdrawData->withdraw_money;
        $withdrawData->outer_no = $toAccount['cashNo'];  // 第三方交易号
        $withdrawData->bank_info = $toAccount['cashBankNo']; // 银行卡号
        $withdrawData->cardholder = $toAccount['cashName']; // 持卡人
        $withdrawData->bank_name = $toAccount['cashBank']; // 银行
        $withdrawData->actual_money = $total - $toAccount['cashFee']; // 实际到账金额
        $withdrawData->fee_money = $toAccount['cashFee']; // 提现费用
        if ($toAccount['status'] == '01') { // status: 01:成功 02:处理中 03:失败
            if ($toAccount['isArrival'] == '01') { // isArrival: 01:成功 02:处理中 03:失败
                $paySta = 1;
                $withdrawData->toaccount_time = $toAccount['successTime'];  // 到账时间
                $withdrawData->status = 2;
                $withdrawData->modify_time = date('Y-m-d H:i:s');
            } elseif ($toAccount['isArrival'] == '03') {
                $paySta = 2;
                $withdrawData->status = 3;
                $withdrawData->modify_time = date('Y-m-d H:i:s');
            }
        } elseif ($toAccount['status'] == '03') {
            $paySta = 2;
            $withdrawData->status = 3;
            $withdrawData->modify_time = date('Y-m-d H:i:s');
        } else {
            return ['code' => 109, 'msg' => '操作错误'];
        }
        if (!$withdrawData->save()) {
            return $this->jsonError(109, '提现表数据写入失败');
        }
        $funds = new FundsService();
        if ($toAccount['status'] == '01') {
            if ($toAccount['isArrival'] == '01') {
                $userFunds = $funds->operateUserFunds($withdrawData['cust_no'], -$total, 0, -$total, false, '提现-划账');
            } elseif ($toAccount['isArrival'] == '03') {
                $userFunds = $funds->operateUserFunds($withdrawData['cust_no'], 0, $total, -$total, false, '提现-划账');
            }
        } elseif ($toAccount['status'] == '03') {
            $userFunds = $funds->operateUserFunds($withdrawData['cust_no'], 0, $total, -$total, false, '提现-划账');
        }
        if ($userFunds['code'] != 0) {
            return ['code' => 109, 'msg' => $userFunds['msg']];
        }
        $funds->iceRecord($withdrawData['cust_no'], $withdrawData->cust_type, $withdrawData->withdraw_code, $total, 2, "提现-划账");

        $balance = UserFunds::find()->select(['all_funds'])->where(['cust_no' => $withdrawData['cust_no']])->asArray()->one();

        $withdrawRecord = PayRecord::updateAll(['status' => $paySta, 'balance' => $balance['all_funds'], 'pay_time' => date('Y-m-d H:i:s'), 'modify_time' => date('Y-m-d H:i:s')], ['order_code' => $withdrawCode]);
        if ($withdrawRecord == false) {
            return ['code' => 109, 'msg' => '明细表写入失败'];
        }
        return ['code' => 600, 'msg' => '提现数据写入成功'];
    }

}
