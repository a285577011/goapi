<?php

namespace app\modules\user\controllers;

use app\modules\user\models\RedeemCode;
use app\modules\user\models\RedeemRecord;
use app\modules\user\models\StoreCodeList;
use app\modules\user\models\StoreUser;
use Yii;
use yii\web\Controller;

/**
 * 用户控制器
 */
class FjtcController extends Controller {

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
    }

    /**
     * 说明:申请为推广门店，配发推广二维码
     * @author chenqiwei
     * @date 2018/2/9 上午10:06
     * @param
     * @return
     */
    public function actionApplyRedeemStore(){
        $request = \Yii::$app->request;
        $storeName = $request->post('store_name');
        $storeCode = $request->post('store_code');
        $userId = \Yii::$userId;

        $user = StoreUser::find()->where(['user_id'=>$userId])->one();
        if(!empty($user)){
            return $this->jsonError(403,'申请失败，该用户已进申请过推广门店');
        }
        $is = StoreUser::find()->where(['store_code'=>$storeCode])->one();
        if($is){
            return $this->jsonError(403,'该门店编码已申请过，请重新输入编码');
        }

        $qrUrl = \Yii::$app->params['userDomain'].'/api/user/wechat/store-get-code?store_code='.$storeCode;
        $user = new StoreUser();
        $user->user_id = $userId;
        $user->store_name = $storeName;
        $user->store_code = $storeCode;
        $user->qr_url = $qrUrl;
        $isCheck = StoreCodeList::find()->where(['store_code'=>$storeCode])->one();
        if($isCheck){
            $user->status = 2;
            $msg = '申请成功';
        }else{
            $user->status = 0;
            $msg = '申请成功，等待审核...';
        }
        if(!$user->save()){
            print_r($user->errors);die;
        }
        return $this->jsonResult(600,$msg,true);
    }

    /**
     * 说明:获取我的门店推广二维码
     * @author chenqiwei
     * @date 2018/2/9 上午10:50
     * @param
     * @return
     */
    public function actionGetStoreQr(){
        $userId = \Yii::$userId;
        $user = StoreUser::find()->where(['user_id'=>$userId])->one();
        if(empty($user)){
            return $this->jsonResult(600,'该用户未申请过门店',['status'=>4]);
        }

        $title = \Yii::redisGet('jftc_title');
        $content = \Yii::redisGet('jftc_content');
        if(empty($title)){
            $title = '体彩顶呱刮，新春嘉年华!';
        }
        if(empty($content)){
            $content = '关注成功之后，可免费获取一张2元即开票兑换码';
        }
        $ret = [
            'user_pic'=>'',
            'store_name'=>$user->store_name,
            'store_code'=>$user->store_code,
            'qr_url'=>$user->qr_url,
            'status'=>$user->status,
            'title'=>$title,
            'content'=>$content,
        ];
        return $this->jsonResult(600,'获取成功',$ret);
    }

    /**
     * 说明:领取兑换码
     * @author chenqiwei
     * @date 2018/2/9 下午2:30
     * @param
     * @return
     */
    public function actionGetRedeemCode(){
        $request = \Yii::$app->request;
        $openId = $request->post_nn('code');
        $trans = \Yii::$app->db->beginTransaction();
        try{
            //查找兑换记录表openId 是否有记录
            $redeemRecord = RedeemRecord::find()->where(['open_id'=>$openId])->one();
            if(empty($redeemRecord)){
                return $this->jsonError(107,'抱歉，您不是门店推广用户');
            }else if($redeemRecord->status == 0){//未领取
                $random = rand(0,9);
                $redeemCode = RedeemCode::find()->select(['redeem_code_id','redeem_code','status'])->where(['type'=>1,'status'=>0,'random'=>$random])->one();
                if(empty($redeemCode)) return $this->jsonError(107,'抱歉,兑换码已发完');
                $redeemRecord->redeem_code_id = $redeemCode['redeem_code_id'];
                $redeemRecord->status = 1;
                $redeemRecord->modify_time = date('Y-m-d H:i:s');
                if($redeemRecord->save()){//领取成功
                    $redeemCode->status = 1;
                    $redeemCode->modify_time = date('Y-m-d H:i:s');
                    if(!$redeemCode->save()){
                        print_r($redeemCode->errors);die;
                    }
                }
            }
            $rr = RedeemRecord::find()
                ->select(['redeem_record.status','redeem_code.redeem_code','redeem_code.status as use_status'])
                ->leftJoin('redeem_code','redeem_code.redeem_code_id = redeem_record.redeem_code_id')
                ->where(['id'=>$redeemRecord->id])
                ->asArray()->one();
            $trans->commit();
        }catch (Exception $e){
            $trans->rollBack();
            KafkaService::addLog('getRedeemCode', $e->getMessage());
        }
        return $this->jsonResult(600,'succ',$rr);
    }


    public function actionStoreCheckRedeemcode(){
        $userId = \Yii::$userId;
        $request = \Yii::$app->request;
        $redeemCode = $request->post_nn('redeem_code');
        $redeemRecord = StoreUser::find()
            ->select(['store_user.id','store_user.ticket_total','redeem_code.redeem_code_id','redeem_code.status'])
            ->leftJoin('redeem_record','redeem_record.store_code= store_user.store_code')
            ->leftJoin('redeem_code','redeem_code.redeem_code_id=redeem_record.redeem_code_id and type=1')
            ->where(['store_user.user_id'=>$userId,'redeem_code.redeem_code'=>$redeemCode])
            ->asArray()
            ->one();
        if(!$redeemRecord){
            return $this->jsonError(106,'该兑换码不是本门店推广码！');
        }elseif ($redeemRecord['status'] != RedeemCode::STATUS_NOT_USED){
            return $this->jsonResult(600,'兑换失败，该兑换码已使用',true);
        }elseif($redeemRecord['ticket_total'] == 0){
            return $this->jsonResult(600,'您的门店每天兑换额度不足',true);
        }
        $redeemCode = RedeemCode::find()->where(['redeem_code_id'=>$redeemRecord['redeem_code_id']])->one();
        $redeemCode->status = RedeemCode::STATUS_USED;
        $redeemCode->settle_date = date('Y-m-d H:i:s');
        $redeemCode->modify_time = date('Y-m-d H:i:s');
        $redeemCode->save();
        $storeUser = StoreUser::findOne($redeemRecord['id']);
        $storeUser->ticket_total = intval($storeUser->ticket_total)-1;
        $storeUser->save();
        return $this->jsonResult(600,'兑换成功',$redeemRecord);
    }

    public function actionGetRecordList(){
        $userId = \Yii::$userId;
        $request = \Yii::$app->request;
        //分页
        $page = $request->post('page_num','1');
        $pagesize = $request->post('size','20');
        if($pagesize > 50){
            $this->jsonError(109, '一次请求数不得超过50条');
        }

        $total = RedeemRecord::find()
            ->leftJoin('store_user','redeem_record.store_code= store_user.store_code')
            ->leftJoin('redeem_code','redeem_code.redeem_code_id=redeem_record.redeem_code_id')
            ->where(['store_user.user_id'=>$userId,'redeem_code.status'=>2])
            ->count();
        $redeemRecordList = RedeemRecord::find()
            ->select(['redeem_code.redeem_code','redeem_code.status as use_status','redeem_code.modify_time'])
            ->leftJoin('store_user','redeem_record.store_code= store_user.store_code')
            ->leftJoin('redeem_code','redeem_code.redeem_code_id=redeem_record.redeem_code_id')
            ->where(['store_user.user_id'=>$userId,'redeem_code.status'=>2])
            ->offset($pagesize*($page-1))
            ->limit($pagesize)
            ->asArray()
            ->all();


        $ret = [
            'page'=>$page,
            'pages'=>$pagesize,
            'total'=>$total,
            'dateList'=>$redeemRecordList,
        ];
        return $this->jsonResult(600,'succ',$ret);
    }

    public function actionCronDeal(){
        $db = \Yii::$app->db;
        $sql = 'update store_user set ticket_total = 100 ;';
        $ret = $db->createCommand($sql)->execute();
        return $this->jsonResult(600,'重置成功',true);
    }
}
