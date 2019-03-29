<?php

namespace app\modules\openapi\controllers;

use Yii;
use yii\web\Controller;
use app\modules\openapi\models\RedeemCode;
use app\modules\common\helpers\Constants;

class RedeemController extends Controller {
    
    /**
     * 获取兑换码
     * @auther GL zyl
     * @return type
     */
    public function actionGetRedeemCode() {
        $request = Yii::$app->request;
        $codeVal = $request->post('code_value', '');
        $outNo = $request->post('out_trade_no', '');
        $source = $request->post('plaform_source', 1);
        if(empty($codeVal) || empty($outNo)) {
            return $this->jsonError(481, '请输入有效的兑换码金额');
        }
        $exits = RedeemCode::find()->select(['redeem_code_id'])->where(['out_trade_no' => $outNo, 'platform_source' => $source])->asArray()->one();
        if(!empty($exits)) {
            return $this->jsonError(483, '此单号已生成兑换码');
        }
        $redeemCode = $this->generateCode($codeVal, $outNo, $source);
        if($redeemCode == 482){
            return $this->jsonError(482, '兑换码生成失败');
        }
        $data['data'] = $redeemCode;
        return $this->jsonResult(600, '兑换码', $data);
    }

    /**
     * 生成写入兑换码
     * @author GL zyl
     * @return int
     */
    public function generateCode($codeVal, $outNo, $source) {
        $chart = 'GLC0123456789';
        $codeLen = Constants::CODE_LENGTH;
        $redeemCode = '';
        for($i=0;$i<$codeLen;$i++){
            $chart = str_shuffle($chart);
            $num = mt_rand(0, strlen($chart)-1);
            $redeemCode .= $chart[$num];
        }
        $exits = RedeemCode::find()->select(['redeem_code_id'])->where(['redeem_code' => $redeemCode])->asArray()->one();
        if(!empty($exits)){
            $redeemCode = $this->generateCode($codeVal, $outNo);
        }
        $redeem = new RedeemCode;
        $redeem->out_trade_no = $outNo;
        $redeem->redeem_code = $redeemCode;
        $redeem->value_amount = $codeVal;
        $redeem->platform_source = $source;
        $redeem->status = 1;
        $redeem->create_time = date('Y-m-d H:i:s');
        if(!$redeem->validate()){
            return 482;
        }
        if(!$redeem->save()){
            return 482;
        }
        return $redeemCode;
    }
    
    /**
     * 校验兑换码
     * @auther GL zyl
     * @return type
     * 
     */
    public function actionCheckRedeemCode() {
        $request = Yii::$app->request;
        $code = $request->post('redeemCode', '');
        if(empty($code)){
            return $this->jsonError(100, '参数缺失');
        }
        $checkCode = RedeemCode::find()->where(['redeem_code' => $code])->asArray()->one();
        if(empty($checkCode)){
            return $this->jsonError(484, '兑换码校验失败');
        }
        if($checkCode['status'] == 2 ){
            return $this->jsonError(484, '此兑换码已兑换');
        }
        if($checkCode['status'] != 1) {
            return $this->jsonError(484, '此兑换码已失效');
        }
        $data['redeem_code_id'] = $checkCode['redeem_code_id'];
        $data['redeem_code'] = $checkCode['redeem_code'];
        $data['value_amount'] = $checkCode['value_amount'];
        return $this->jsonResult(600, '校验成功', $data);
    }
    
    /**
     * Java 兑换数据回调写入
     * @auther GL zyl
     * @return type
     */
    public function actionSetRedeem() {
        $request = Yii::$app->request;
        $codeId = $request->post('redeemCodeId', '');
        $storeCustNo = $request->post('storeCustNo', '');
        $storeId = $request->post('storeId', '');
        if(empty($codeId) || (empty($storeCustNo) && empty($storeId))) {
            return $this->jsonError(100, '参数缺失');
        }
        $redeemCode = RedeemCode::find()->where(['redeem_code_id' => $codeId])->one();
        if(empty($redeemCode)){
            return $this->jsonError(484, '非法兑换码');
        }
        $redeemCode->status = 2;
        if($storeCustNo != ''){
            $redeemCode->store_cust_no = $storeCustNo;
        }
        if($storeId != ''){
            $redeemCode->store_id = $storeId;
        }
        $redeemCode->redeem_time = date('Y-m-d H:i:s');
        $redeemCode->settle_status = 1;
        $redeemCode->settle_date = date('Y-m-d', strtotime('+1 day'));
        $redeemCode->modify_time = date('Y-m-d H:i:s');
        if(!$redeemCode->save()){
            return $this->jsonError(484, '操作失败,请稍后再试');
        }
        return $this->jsonResult(600, '操作成功', true);
    }
    
    /**
     * Java 获取需计算列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetUnsettleList() {
        $where['status'] = 2;
        $where['settle_status'] = 1;
        $where['settle_date'] = date('Y-m-d');
        $redeemCode = RedeemCode::find()->select(['redeem_code_id', 'value_amount', 'store_cust_no'])->where($where)->asArray()->all();
        $data['data'] = $redeemCode;
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * Java 结算回调数据写入
     * @auther GL zyl
     * @return type
     */
    public function actionSetSettleRedeem() {
        $request = Yii::$app->request;
        $codeId = $request->post('redeemCodeId', '');
        $custNo = $request->post('storeCustNo', '');
        if(empty($codeId) || empty($custNo)) {
            return $this->jsonError(100, '参数缺失');
        }
        $redeemCode = RedeemCode::find()->where(['redeem_code_id' => $codeId, 'store_cust_no' => $custNo])->one();
        if(empty($redeemCode)){
            return $this->jsonError(484, '非法兑换门店');
        }
        $redeemCode->settle_status = 2;
        $redeemCode->modify_time = date('Y-m-d H:i:s');
        if(!$redeemCode->save()){
            return $this->jsonError(484, '操作失败,请稍后再试');
        }
        return $this->jsonResult(600, '操作成功', true);
    }
    
    /**
     * 获取门店兑换列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetStoreRedeemList(){
        $request = Yii::$app->request;
        $storeId = $request->post('storeId', '');
        $storeNo = $request->post('storeCustNo', '');
        $page = $request->post('page', 1);
        $size = $request->post('size', 10);
        if($storeId == '' && $storeNo == ''){
            return $this->jsonError(100, '参数缺失');
        }
        if($storeId != '') {
            $where['store_id'] = $storeId;
        }
        if($storeNo != ''){
            $where['store_cust_no'] = $storeNo;
        }
        $total = RedeemCode::find()->where($where)->count();
        $pages = ceil($total / $size);
        $list = RedeemCode::find()->select(['redeem_code_id', 'redeem_code', 'value_amount', 'status', 'settle_status', 'redeem_time', 'settle_date'])->where($where)->limit($size)->offset(($page-1) * $size)->asArray()->all();
        $data['data'] = $list;
        $data['page'] = $page;
        $data['pages'] = $pages;
        $data['size'] = count($list);
        $data['total'] = $total;
        return $this->jsonResult(600, '获取成功', $data);
    }
}

