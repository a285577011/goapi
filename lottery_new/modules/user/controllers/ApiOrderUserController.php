<?php

namespace app\modules\user\controllers;

use app\modules\common\helpers\Constants;
use app\modules\common\services\FundsService;
use app\modules\common\services\KafkaService;
use app\modules\openapi\models\Bussiness;
use app\modules\tools\helpers\SmsTool;
use app\modules\tools\helpers\Uploadfile;
use app\modules\user\helpers\WechatTool;
use app\modules\user\models\ApiUserBank;
use yii\web\Controller;
use app\modules\user\models\ApiUserApply;
use app\modules\common\models\UserFunds;

/**
 * 用户控制器
 */
class ApiOrderUserController extends Controller {

    private $userService;

//    public function __construct($id, $module, $config = []) {
//        parent::__construct($id, $module, $config);
//    }


    /**
     * 说明:
     * @author chenqiwei
     * @date 2018/3/26 下午5:24
     * @param
     * @return
     */
    public function actionUploadVoucherPic(){
        $file = $_FILES['file'];
        $day = date('ymdHis', time());
        $custNo = \Yii::$custNo;
        if (empty($custNo)) {
            $this->jsonError(100, '用户不存在');
        }
        if ($file) {
            $check = Uploadfile::check_upload_pic($file);
            if ($check['code'] != 600) {
                return $this->jsonError($check['code'], $check['msg']);
            }
            $saveDir = '/voucher_pic/' .$custNo  . '/';
            $type = strtolower(substr($file['name'],strrpos($file['name'],'.')+1));
            $name = $day.'.'.$type;
            $pathJson = Uploadfile::pic_host_upload($file, $saveDir, $name);
            $pathArr = json_decode($pathJson, true);
            if ($pathArr['code'] != 600) {
                return $this->jsonError(109, '上传失败,请重新上传');
            }
            $path = $pathArr['result']['ret_path'];
            return $this->jsonResult(600, '上传成功', $path);
        }else{
            return $this->jsonError(109, '上传失败,请重新上传');
        }
    }

    /**
     * 说明:充值申请
     * @author chenqiwei
     * @date 2018/3/26 上午11:43
     * @param   money   金额
     * @param   voucher_pic 凭证
     * @return
     */
    public function actionRecharge(){
        $request = \Yii::$app->request;
        $userId = \Yii::$userId;
        $custNo = \Yii::$custNo;
        $apiUserAppyId = $request->post('detail_id');
        $money = $request->post_nn('money');
        $voucher = $request->post('voucher_pic');
        $remark = $request->post('remark');
        $nowTime = date('Y-m-d H:i:s');
        $flag = true;
        $bussiness = $this->getBussinessInfo($userId);

        if($apiUserAppyId){
            $apiUserAppy = ApiUserApply::find()->where(['api_user_apply_id'=>$apiUserAppyId])->one();
            $apiUserAppy->money = $money;
            $apiUserAppy->voucher_pic = $voucher;
            $apiUserAppy->remark = $remark;
            $apiUserAppy->status = 1;
            $apiUserAppy->modify_time = date('Y-m-d H:i:s');
        }else{
            $apiUserAppy = new ApiUserApply();
            $apiUserAppy->apply_code = $bussiness['bussiness_appid'].'-'.date('ymdHis');
            $apiUserAppy->user_id = $userId;
            $apiUserAppy->cust_no = $custNo;
            $apiUserAppy->type = 1;
            $apiUserAppy->money = $money;
            $apiUserAppy->voucher_pic = $voucher;
            $apiUserAppy->remark = $remark;
            $apiUserAppy->status = 1;
            $apiUserAppy->create_time = date('Y-m-d H:i:s');
            $apiUserAppy->modify_time = date('Y-m-d H:i:s');
        }
        if(!$apiUserAppy->save()){
            $flag = false;
            $errorMsg =  $apiUserAppy->errors;
            KafkaService::addLog('table_save_error',$errorMsg);
        }
        //微信推送（给财务推送）
        $wechatTool = new WechatTool();
        $title = '您有一个待审核事项（请及时处理）！！！';
        $name = '充值审核';
        $userOpenId ='oV4Ujw-7Ymtu2vP8UCpWHje-v_iE';
        if(YII_ENV_DEV){
            $userOpenId ='otEbv0SK-oV4Ujw-7Ymtu2vP8UCpWHje-v_iE';
        }

        $cust_no ="{$bussiness['name']}({$custNo},{$bussiness['user_tel']})";
        $czMoney ='金额: ' .$money . ' 元';
        $czTime = $nowTime;
        $wechatTool->sendTemplateMsgRechargeApiUser($title, $userOpenId, $name ,$cust_no,$czMoney, $czTime);
        return $this->jsonResult(600,'充值已提交审核',$flag);
    }

    /**
     * 说明: 提现申请
     * @author chenqiwei
     * @date 2018/3/26 下午2:55
     * @param   money   金额
     * @return
     */
    public function actionWithdraw(){
        $request = \Yii::$app->request;
        $userId = \Yii::$userId;
        $custNo = \Yii::$custNo;
        $money = $request->post_nn('money');
        $bankId = $request->post('bank_id');
        $remark = $request->post('remark');
        $userTel = $request->post('user_tel');
        $smsCode = $request->post('sms_code');
        $voucher = $request->post('voucher_pic');
        SmsTool::check_code(Constants::SMS_KEY_WITHDRAW_APPLY, $userTel, $smsCode);
        $flag = true;
        $userFunds = UserFunds::find()->select(['withdraw_status'])->where(['cust_no' => $custNo])->asArray()->one();
        if($userFunds['withdraw_status'] != 1) {
            return $this->jsonError(488, '您已被禁止提现！！有疑问请联系客服~');
        }
        $bussiness = $this->getBussinessInfo($userId);
        if (bcsub($bussiness['able_funds'] , $bussiness['no_withdraw'],2) < floatval($money)) {
            return $this->jsonError(444,'提交失败，请输入正确金额');
        }
        $nowTime = date('Y-m-d H:i:s');
        $apiUserAppy = new ApiUserApply();
        $apiUserAppy->apply_code = $bussiness['bussiness_appid'].'-'.date('ymdHis');
        $apiUserAppy->user_id = $userId;
        $apiUserAppy->cust_no = $custNo;
        $apiUserAppy->type = 2;
        $apiUserAppy->money = $money;
        $apiUserAppy->remark = $remark;
        $apiUserAppy->status = 1;
        $apiUserAppy->api_user_bank_id =$bankId;
        $apiUserAppy->create_time = $nowTime;
        $apiUserAppy->modify_time = $nowTime;
        $apiUserAppy->voucher_pic = $voucher;
        if(!$apiUserAppy->save()){
            $flag = false;
            $errorMsg =  $apiUserAppy->errors;
            KafkaService::addLog('table_save_error',$errorMsg);
        }

        //可用余额到冻结余额
        $funds = new FundsService();
        $r = $funds->operateUserFunds($custNo, 0, -$money,  $money, $optWithdraw = false, $body = "");
        $funds->iceRecord($custNo, 1, $apiUserAppy->apply_code, $money, 1, $body = "提现冻结");
        if($r['code'] == 0){
            //微信推送（给财务推送）
            $wechatTool = new WechatTool();
            $title = '您有一个待审核事项（请及时处理）！！！';
            $name = '提现审核';
            $userOpenId ='otEbv0SK-A0T5dBi17TPIOA1dXkg';
            $cust_no ="{$bussiness['name']}({$custNo},{$bussiness['user_tel']})";
            $czMoney ='金额: ' .$money . ' 元';
            $czTime = $nowTime;
            $wechatTool->sendTemplateMsgRechargeApiUser($title, $userOpenId, $name ,$cust_no,$czMoney, $czTime);
            return $this->jsonResult(600,'提现已提交审核',$flag);
        }
        return $this->jsonError(600,'提交失败');

    }

    public function getBussinessInfo($userId){
        $bussiness = Bussiness::find()
            ->select(['bussiness_appid','name','user.cust_no','user.user_tel','user_funds.able_funds','no_withdraw'])
            ->leftjoin('user','user.user_id = bussiness.user_id')
            ->leftjoin('user_funds','user_funds.user_id = bussiness.user_id')
            ->where(['bussiness.user_id'=>$userId])
            ->asArray()->one();
        if(empty($bussiness)){
            return $this->jsonError(444,'提交失败，请重新登录再试');
        }
        return $bussiness;
    }

    /**
     * 说明: 添加银行卡
     * @author chenqiwei
     * @date 2018/3/27 上午9:25
     * @param
     * @return
     */
    public function actionAddBank(){
        $uerId = \Yii::$userId;
        $request = \Yii::$app->request;
        $userName = $request->post_nn('user_name');
        $bankOpen = $request->post_nn('bank_open');
        $cardNumber = $request->post_nn('card_number');
        $branch = $request->post('branch');
        $province = $request->post('province');
        $city = $request->post('city');

        $bussinessInfo = Bussiness::find()->select(['bussiness_id'])->where(['user_id'=>$uerId])->asArray()->one();

        $bank = new ApiUserBank();
        $bank->user_id = $uerId;
        $bank->bussiness_id = $bussinessInfo['bussiness_id'];
        $bank->user_name = $userName;
        $bank->bank_open = $bankOpen;
        $bank->branch = $branch;
        $bank->card_number = $cardNumber;
        $bank->province = $province;
        $bank->city = $city;
        if(!$bank->save()){
            $flag = false;
            $errorMsg =  $bank->errors;
            KafkaService::addLog('table_save_error',$errorMsg);
        }
        return $this->jsonResult(600,'添加成功',$bank->attributes);
    }

    /**
     * 说明:获取我的银行卡列表
     * @author chenqiwei
     * @date 2018/3/27 上午9:56
     * @param
     * @return
     */
    public function actionGetBankList(){
        $uerId = \Yii::$userId;
        $bankLists = ApiUserBank::find()
            ->leftJoin('bussiness','bussiness.bussiness_id = api_user_bank.bussiness_id')
            ->where(['bussiness.user_id'=>$uerId,'api_user_bank.status'=>1])
            ->orderBy('is_default desc , api_user_bank_id desc')
            ->asArray()
            ->all();
        return $this->jsonResult(600,'获取成功',$bankLists);
    }

    /**
     * 说明:设置默认银行卡
     * @author chenqiwei
     * @date 2018/3/27 上午10:11
     * @param
     * @return
     */
    public function actionSetDefultBank(){
        $request = \Yii::$app->request;
        $apiUserBankId = $request->post_nn('bank_id');
        $back = ApiUserBank::findone($apiUserBankId);
        ApiUserBank::updateAll(['is_default'=>0],['bussiness_id'=>$back['bussiness_id']]);
        $back->is_default =1;
        if(!$back->save()){
            return $this->jsonError(444,'设置失败');
        }
        return $this->jsonResult(600,'设置成功',true);
    }

    /**
     * 说明:我的充值/提现记录
     * @author chenqiwei
     * @date 2018/3/27 下午1:57
     * @param   type  类型 1：充值 2提现
     * @return
     */
    public function actionGetRecords(){
        $request = \Yii::$app->request;
        $type = $request->post('type',1);
        $where = ['user_id'=>\Yii::$userId];
        if($type){
            $where['type'] = $type;
        }
        $lists= ApiUserApply::find()
            ->where($where)
            ->orderBy('api_user_apply_id desc')
            ->asArray()
            ->all();
        return $this->jsonResult(600,'获取成功',$lists);
    }

    /**
     * 说明:获取充值详情
     * @author chenqiwei
     * @date 2018/3/27 下午2:06
     * @param
     * @return
     */
    public function actionGetDetail(){
        $request = \Yii::$app->request;
        $detailId = $request->post_nn('detail_id');
        $detail = ApiUserApply::find()
            ->select(['api_user_apply.*','api_user_bank.user_name','api_user_bank.user_name','bank_open','branch','card_number','province','city'])
            ->leftJoin('api_user_bank','api_user_bank.api_user_bank_id = api_user_apply.api_user_bank_id')
            ->where(['api_user_apply.user_id'=>\Yii::$userId,'api_user_apply_id'=>$detailId])
            ->asArray()
            ->one();
        return $this->jsonResult(600,'获取成功',$detail);
    }

    public function actionBankDel(){
        $request = \Yii::$app->request;
        $apiUserBankId = $request->post_nn('bank_id');
        $back = ApiUserBank::find()
            ->leftJoin('bussiness','bussiness.bussiness_id = api_user_bank.bussiness_id')
            ->where(['api_user_bank_id'=>$apiUserBankId , 'bussiness.user_id'=>\Yii::$userId])->one();
        if($back){
            $back->status = 0;
            $back->save();
//            $back->delete();
            return $this->jsonResult(600,'删除成功',true);
        }
        return $this->jsonError(444,'删除失败');
    }

}
