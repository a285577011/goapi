<?php

namespace app\modules\user\controllers;

use app\modules\user\models\CouponsDetail;
use yii\db\Query;
use yii\web\Controller;
use app\modules\user\models\UserGrowthRecord;
use app\modules\user\models\User;
use app\modules\user\models\UserSginRecord;
use app\modules\user\models\Gift;
use app\modules\user\helpers\UserCoinHelper;

/**
 * 会员俱乐部控制器
 */
class UserclubController extends Controller {

    /* 获取会员等级详情 */
    public function actionUserClubIndex(){
        $userId = $this->userId;
        $custNo = $this->custNo;
        $fields = 'user.level_id, user.user_name, user.user_pic, l.level_name, f.user_glcoin, f.user_growth';
        $data = User::find()->select($fields)
            ->leftJoin('user_levels as l', 'l.user_level_id = user.level_id')
            ->leftJoin('user_funds as f', 'f.user_id = user.user_id')
            ->where(['user.user_id' => $userId])
            ->asArray()
            ->one();

        //获取优惠券数量
        $CouponsDetail = new CouponsDetail();
        $couponNum = $CouponsDetail-> userCouponNum($custNo);
        $data['level_id'] = (int)$data['level_id'];
        $data['user_glcoin'] = (int)$data['user_glcoin'];
        $data['user_growth'] = (int)$data['user_growth'];
        $data['couponNum'] = (int)$couponNum['unusedNum'];
        return $this->jsonResult(600, '获取成功', $data, '');
    }

    /* 获取成长值 */
    public function actionUserGrowth(){
        $userId = $this->userId;
        $query = new Query();
        $res1 = $query->select('user_growth')->from('user_funds')->where(['user_id'=>$userId])->one();
        $res2 = $query->select('level_id,level_name')->from('user')->where(['user_id'=>$userId])->one();
        $data = array_merge($res1,$res2);
        return $this->jsonResult(600, '获取成功', $data);
    }

    /* 获取咕啦币 */
    public function actionUserGlcoin(){
        $userId = $this->userId;
        $user_glcoin = (new Query())->select('user_glcoin')->from('user_funds')->where(['user_id'=>$userId])->one();
        return $this->jsonResult(600, '获取成功', $user_glcoin);
    }

    /* 获取成长值明细列表 */
    public function actionGrowthList(){
        $userId = $this->userId;
        $request = \yii::$app->request;
        $page_num = $request->post('page_num',1);
        $size = $request->post('size',10);
        $type = $request->post('type', 0);//0=全部，1=获取得到的，2=使用掉的
//        $UserGrowthRecord = new UserGrowthRecord();
        $res = $UserGrowthRecord->getUserGrowthRecord($userId, $page_num, $size, $type);
        return $this->jsonResult(600, '获取成功', $res);
    }

    /* 获取咕啦币明细列表 */
    public function actionGetCoinList(){
        $userId = $this->userId;
        $request = \yii::$app->request;
        $page_num = $request->post('page_num',1);
        $size = $request->post('size',10);
        $type = $request->post('type', 0);//0=全部，1=获取得到的，2=使用掉的
//        $userGlCoin = new UserGlCoinRecord();
        $res = UserCoinHelper::getCoinRecordList($userId, $page_num, $size, $type);
        return $this->jsonResult(600, '获取成功', $res);
    }

    /* 当天是否签到 */
    public function actionTodaySgin(){
        $userId = $this->userId;
        //查询今天是否签到
        $UserSgin = new UserSginRecord();
        $res = $UserSgin->todaySginData($userId);
        if($res === 1){
            return $this->jsonResult(600, '今天已经签到！', 1);
        } else {
            return $this->jsonResult(600, '今天未签到', 0);
        }
    }

    /* 签到详情页 */
    public function actionSignDetail(){
        $userId = $this->userId;
        //查询今天是否签到
        $UserSgin = new UserSginRecord();
        $is_sign = $UserSgin->todaySginData($userId);
        //上次签到记录
        $lastSginDate = $UserSgin -> lastSginDate($userId);
        //连续签到次数
        $continuous_num = $UserSgin -> keepSginDate($lastSginDate['create_time'], $lastSginDate['continuous_num']);
        //当月签到数据
        $signData = $UserSgin -> monthSginDate($userId);
        $data = [
            'is_sign' => $is_sign,  //0 = 未签到 1 = 已签到
            'continuous_num' => $continuous_num==0 ? 0 : $continuous_num-1 , //连续签到数
            'signData' => $signData
        ];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /* 增加签到 */
    public function actionAddSignin(){
        $userId = $this->userId;
        $custNo = $this->custNo;
        //查询今天是否签到
        $UserSgin = new UserSginRecord();
        $bool = $UserSgin->todaySginData($userId);
        if($bool === 1) return $this->jsonResult(600, '今天已经签到！', '');
        //上次签到记录
        $lastSginDate = $UserSgin -> lastSginDate($userId);
        //连续签到次数
        $continuous_num = $UserSgin -> keepSginDate($lastSginDate['create_time'], $lastSginDate['continuous_num']);
        //新增签到表
        $res = $UserSgin->addSgin($userId, $continuous_num);
        if($res['code'] === 109) return $this->jsonError(109, $res['msg']);
        //新增成长值
        $UserGrowthRecord = new UserGrowthRecord;
        $ret = $UserGrowthRecord -> updateGrowth($custNo, '', 1);
        if($ret['code'] == 600){
            return $this->jsonResult(600, '签到成功,恭喜您获得10点成长值', 0);
        } else {
            return $this->jsonError(109, '签到失败，请刷新再试',1);
        }
    }

    /*用户完善资料获取成长值*/
    public function actionRealInfo(){
        $userId = $this->userId;
        $UserGrowthRecord = new UserGrowthRecord;
        $res = $UserGrowthRecord->addInfoPerfect($userId);
        if($res['code'] == 600){
            return $this->jsonResult(600, $res['msg'], '');
        } else {
            return $this->jsonError(109, $res['msg']);
        }
    }

    /**
     * 用户实名认证送成长值
     * authen_status 认证状态 0=未认证 1=已认证
     */
    public function actionRealAuthen(){
        $userId = $this->userId;
        $request = \yii::$app->request;
        $authen_status = $request->post('authen_status', 0);
        if($authen_status != 1){
            return $this->jsonError(109, '未实名认证');
        }
        $UserGrowthRecord = new UserGrowthRecord;
        $res = $UserGrowthRecord->addRealName($userId, $authen_status);
        if($res['code'] == 600){
            return $this->jsonResult(600, $res['msg'], '');
        } else {
            return $this->jsonError(109, $res['msg']);
        }
    }

    /**
     * 用户优惠券数量
     */
    public function actionUserCouponNum(){
        $custNo = $this->custNo;
        $CouponsDetail = new CouponsDetail();
        $res = $CouponsDetail->userCouponNum($custNo);
        return $this->jsonResult(600, '获取成功', $res);
//        if($res['code'] == 600){
//            return $this->jsonResult(109, $res['msg'],'');
//        } else {
//            return $this->jsonResult(600, '获取成功', $res);
//        }
    }

    /**
     * 用户优惠券列表
     */
    public function actionUserCouponLists(){
        $custNo = $this->custNo;
        $request = \yii::$app->request;
        $page_num = $request->post('page_num',1);
        $size = $request->post('size',10);
        $type = $request->post('type', 1);//1=未使用，2=使用记录，3=已过期
        $CouponsDetail = new CouponsDetail();
        $res = $CouponsDetail->userCouponLists($custNo, $page_num, $size, $type);
        return $this->jsonResult(600, '获取成功', $res);
    }

    /**
     * 优惠券编码兑换
     */
    public function actionGetCouponsOn(){
        $custNo = $this->custNo;
        $request = \yii::$app->request;
        $conversion_code = $request->post('conversion_code');
        if(empty($conversion_code)){
            return $this->jsonError(109, '优惠券码不能为空！');
        }
        $CouponsDetail = new CouponsDetail();
        $res = $CouponsDetail->getCouponsOn($conversion_code, $custNo);
        if($res['code'] == 600){
            return $this->jsonResult(600, $res['msg'], '');
        } else {
            return $this->jsonError(109, $res['msg']);
        }
    }

    /**
     * 咕啦币兑换礼品列表
     */
    public function actionGiftLists(){
        $userId = $this->userId;
        $request = \yii::$app->request;
        $page_num = $request->post('page_num',1);
        $size = $request->post('size',10);
        //获取咕啦币
        $user_glcoin = (new Query())->select('user_glcoin')->from('user_funds')->where(['user_id'=>$userId])->one();
        //获取礼品列表
        $gift = new Gift();
        $data = $gift -> getGiftLists($page_num, $size);
        $data['user_glcoin'] = $user_glcoin['user_glcoin'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 单个礼品详情
     */
    public function actionGiftDetail(){
        $request = \yii::$app->request;
        $giftId = $request->post('giftId');
        if(!$giftId || !is_int($giftId)){
            return $this->jsonError(109, '参数错误');
        }
        $gift = new Gift();
        $data = $gift -> getGiftById($giftId);
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 礼品兑换
     * @param int $giftId  必填 礼品id
     * @param int $giftNum 必填 礼品数
     * @param string token
     */
    public function actionRedeemGift(){
        $userId = $this->userId;
        $request = \yii::$app->request;
        $giftId = $request->post('giftId');
        $giftNum = $request->post('giftNum', 1);
        if(!$giftId){
            return $this->jsonError(109, '参数错误');
        }
        $gift = new Gift();
        $res = $gift -> exchange($userId, $giftId, $giftNum);
        if($res['code'] == 600){
            return $this->jsonResult(600, $res['msg'], '');
        } else {
            return $this->jsonError(109, $res['msg']);
        }
    }

    /**
     * 说明:幸运大抽奖 用户/日/3
     * @author chenqiwei
     * @date 2018/4/19 下午3:32
     * @param
     * @return
     */
    public function actionRandomAward(){
        $userId = $this->userId;
        $redis = \yii::$app->redis;
        $type = \yii::$app->request->post('type',0);
        $num = 1;
        if(empty($type)){
            $num = 0;
        }
        $ret = $redis->executeCommand('zincrby', ["randomWard",$num,date('Ymd').'-'.$userId]);
        return $this->jsonResult(600,'获取成功',bcsub(3,$ret));
    }


}
