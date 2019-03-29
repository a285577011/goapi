<?php

namespace app\modules\user\controllers;

use app\modules\agents\helpers\AgentsTool;
use app\modules\agents\models\Agents;
use app\modules\agents\models\OpenId;
use app\modules\user\models\RedeemCode;
use app\modules\user\models\RedeemRecord;
use app\modules\user\models\StoreUser;
use app\modules\user\models\ThirdUser;
use Yii;
use yii\web\Controller;
use app\modules\user\services\IUserService;
use app\modules\user\models\User;
use yii\db\Query;
use app\modules\user\models\Gift;
use app\modules\tools\helpers\Uploadfile;
use app\modules\user\models\UserFollow;
use app\modules\common\models\Store;
use app\modules\common\services\WithdrawService;
use app\modules\common\helpers\Constants;
use app\modules\common\models\UserFunds;
use app\modules\tools\helpers\SmsTool;
use app\modules\experts\models\Expert;
use app\modules\user\models\UserGrowthRecord;
use app\modules\store\helpers\Storefun;
use app\modules\common\models\StoreOperator;
use app\modules\common\services\SyncService;
use app\modules\common\services\SyncApiRequestService;
use app\modules\common\services\KafkaService;
use app\modules\user\models\CouponsDetail;
use app\modules\common\models\Activity;
use app\modules\tools\helpers\Toolfun;

/**
 * 用户控制器
 */
class UserController extends Controller {

    private $userService;

    public function __construct($id, $module, $config = [], IUserService $userService) {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    /**
     * 说明: 会员注册
     * @author  kevi
     * @date 2017年5月18日 下午3:40:06
     * @param   string  account 手机号
     * @param   int  reg_type  注册类型 1：个人 2：门店
     * @param   string  $inviteCode 推广人员推广码
     * @param   string  $agents 推广平台标识码
     * @return 
     */
    public function actionRegister() {
        $request = Yii::$app->request;
        $userTel = $request->post('account');
        $regType = $request->post('reg_type');
        $inviteCode = $request->post('inviteCode', '');
        if (empty($userTel)) {
            $userTel = $request->post('username');
        }
        $password = $request->post('password');
        $smsCode = $request->post('smsCode');
        $sysPlatform = $request->post('sysPlatform', ''); //用于区分注册来源（有值代理APP注册）
        if (empty($userTel) || empty($password) || empty($smsCode)) {
            return $this->jsonError(100, '注册失败,参数缺失');
        }
        $saveKey = Constants::SMS_KEY_REGISTER;
        SmsTool::check_code($saveKey, $userTel, $smsCode);
        //判断是否有资格跑到java调用注册接口
        if (!empty($inviteCode)) {
            $res = User::find()->where(["invite_code" => $inviteCode])->one();
            if (empty($res)) {
                return $this->jsonError(109, '该邀请无效');
            }
        }
        $javaUser = $this->userService->javaRegister($userTel, $password);
        if ($javaUser['httpCode'] == 401) {//java接口请求失败
            return $this->jsonError(401, '注册失败,请稍后重试');
        } else if ($javaUser['httpCode'] == 411 || $javaUser['httpCode'] == 400) {
            return $this->jsonError(414, '注册失败,该手机号已经注册');
        } else if ($javaUser['httpCode'] == 200) {
            $userDetail = $this->userService->getJavaUserDetail($javaUser['data']); //获取用户信息
            $result = $this->userService->setRegisterData($userDetail); //封装php用户表信息
            $result['register_from'] = empty($inviteCode) ? User::REG_FROM_CP : User::REG_FTOM_TG; //注册来源彩票App
            $user = $this->userService->createOrUpdateUser($userTel, $javaUser['data'], $result, $regType, $inviteCode);
            if ($user['code'] != 600) {
                return $this->jsonError(401, $user['msg']);
            }
            $UserGrowthRecord = new UserGrowthRecord();
            $UserGrowthRecord->updateGrowth($javaUser['data'], '', 4);  //注册送成长值
            //2018-06-16之前APP注册赠送优惠券88礼包
            if (isset($sysPlatform)) {
                if (date("Y-m-d") < "2018-06-16") {
                    CouponsDetail::activitySendCoupons('GL', 1, $javaUser['data']);
                }
            }
            return $this->jsonResult(600, '注册成功', ['cust_no' => $javaUser['data']]);
        } else {
            return $this->jsonError($javaUser['httpCode'], $javaUser['msg']);
        }
    }

    /**
     * 会员更换手机
     * $type  1=验证原来手机号，2=验证新手机号
     */
    public function actionChangeTel() {
        $request = \Yii::$app->request;
        $userId = $this->userId;
        $tel = $request->post('tel');
        $smsCode = $request->post('smsCode');
        $type = $request->post('type');
        if (empty($tel) || empty($smsCode) || empty($type)) {
            return $this->jsonError(100, '参数错误');
        }
        $saveKey = Constants::SMS_KEY_WX_CHANGE_BOUNDING;
        SmsTool::check_code($saveKey, $tel, $smsCode);   //check短信验证码
        switch ($type) {
            case 1:
                $user = User::find()->where(['user_tel' => $tel, 'user_id' => $userId])->asArray()->one();
                if (empty($user)) {
                    return $this->jsonResult(109, '手机号错误', 2);
                } else {
                    return $this->jsonResult(600, '验证成功', 1);
                }
            case 2:
                //验证新手机号是否存在，不存在则提示
                $user = User::find()->select('user_id')->where(['user_tel' => $tel])->asArray()->one();
                if (empty($user)) {
                    return $this->jsonResult(109, '换绑手机未注册', 2);
                }
                //新手机号是否绑定过微信，绑定过则提示
                $thirdUser = ThirdUser::find()->select('id')->where(['uid' => $user['user_id']])->asArray()->one();
                if (!empty($thirdUser)) {
                    return $this->jsonResult(109, '该手机已被绑定', 2);
                }
                //存在并且没绑定，换绑第三方信息：查原来绑定信息
                $thirdUser = ThirdUser::find()->select('id')->where(['uid' => $userId])->asArray()->one();
                $res = ThirdUser::updateAll(['uid' => $user['user_id']], ['id' => $thirdUser['id']]);
                if (!$res) {
                    return $this->jsonResult(109, '换绑错误，请重试', 2);
                } else {
                    return $this->jsonResult(600, '换绑成功', 1);
                }
        }
    }

    /**
     * 说明: 会员登入
     * @author  kevi
     * @date 2017年5月18日 下午3:40:06
     * @param
     * @return 
     */
    public function actionLogin() {
        $request = \Yii::$app->request;
        $userTel = $request->post_nn('account');
        $password = $request->post_nn('password');
        $JavaUser = $this->userService->getJavaUser($userTel, $password); //java认证接口
        $gainCoupons = 0;
        if (empty($JavaUser)) {//java接口请求失败
            return $this->jsonError(401, '登录失败,请稍后重试');
        } else if ($JavaUser['httpCode'] != 1) {//java接口认证失败
            return $this->jsonError($JavaUser['httpCode'], $JavaUser['msg']);
        } else {//java接口认证成功--生成或者更新系统用户数据
            $userDetail = $this->userService->getJavaUserDetail($JavaUser['custNo']); //获取java用户信息
            $user = User::findOne(['cust_no' => $JavaUser['custNo']]);
            if (!$user) {//php未注册
                $user = $this->phpRegister($userTel, $userDetail);
            } else if ($user['status'] == 2) {
                return $this->jsonError(402, '该账户已禁用，请联系咕啦管理人员。');
            } else {// 正常登陆
                if (strtotime($user->create_time) < strtotime('2018-05-20 00:00:00')) {
                    $key = 'userCoupons';
                    if (!\Yii::$app->redis->SISMEMBER($key, $user->cust_no)) {
                        $gainCoupons = 1;
                    }
                }
                $user->authen_status = $userDetail['data']['checkStatus'];
                $user->save();
            }
        }
        $token = $this->userService->autoLogin($user['cust_no'], $user['user_id']); //自动登录
        return $this->jsonResult(600, '登录成功', ['token' => $token, 'user_type' => $user['user_type'], 'cust_no' => $user['cust_no'], 'gainCoupons' => $gainCoupons]);
    }

    /**
     * 说明: 发送短信验证码（注册）忘记密码
     * @author  kevi
     * @date 2017年6月5日 上午9:53:13
     * @param account //手机号
     * @param cType //1:注册 2:登录 4:忘记密码
     * @return 
     */
    public function actionGetSmsCode() {
        $request = \Yii::$app->request;
        $userTel = $request->post('account');
        $cType = $request->post('cType'); //1:注册 2:登录 4:忘记密码 5:提现申请
        if (empty($userTel) || empty($cType)) {
            return $this->jsonError(100, '参数缺失');
        }
        if ($cType == 1) {
            $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
            if ($javaUser['httpCode'] == 200) {
                return $this->jsonError(411, '该号码已注册');
            }
            $saveKey = Constants::SMS_KEY_REGISTER;
        } else if ($cType == 2) {//手机验证码登录
            $saveKey = Constants::SMS_KEY_LOGIN;
        } else if ($cType == 4) {
            $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
            if ($javaUser['httpCode'] != 200) {
                return $this->jsonError(109, '该号码未注册');
            }
            $saveKey = Constants::SMS_KEY_UPPWD;
        } else if ($cType == 5) {//渠道商户提现申请
            $saveKey = Constants::SMS_KEY_WITHDRAW_APPLY;
        } else {
            $saveKey = Constants::SMS_KEY_WX_CHANGE_BOUNDING;
        }
        $smstool = new SmsTool();
        $ret = $smstool->sendSmsCode($cType, $saveKey, $userTel);
        return $this->jsonResult(600, '发送成功', true);
    }

    /**
     * 说明: 忘记密码
     * @author  kevi
     * @date 2017年6月5日 上午9:54:41
     * @param account   手机号
     * @param smsCode   短信验证码
     * @param password  新密码
     * @return 
     */
    public function actionUpdatePwd() {
        $request = \Yii::$app->request;
        $userTel = $request->post('account');
        $smsCode = $request->post('smsCode');
        $password = $request->post('password');
        if (empty($userTel) || empty($password)) {
            return $this->jsonError(100, '参数缺失');
        }
        $saveKey = Constants::SMS_KEY_UPPWD;
        SmsTool::check_code($saveKey, $userTel, $smsCode);
        $result = $this->userService->javaUpdatePwd($userTel, $password); //java认证接口
        if ($result['httpCode'] == 200) {
            return $this->jsonResult(600, '修改成功!', true);
        } else if ($result['httpCode'] == 414) {
            return $this->jsonError(401, '修改失败，该手机号未注册!');
        } else {
            return $this->jsonError(401, '修改失败，请稍候再试!');
        }
    }

    /**
     * 说明: 获取用户个人信息
     * @author  kevi
     * @date 2017年6月6日 上午10:51:43
     * @return 
     */
    public function actionGetUserDetail() {
        $custNo = $this->custNo;
        $authStatus = Constants::CHECK_STATUS;
        $expertStatus = Constants::EXPERT_STATUS;
        $user = User::find()->select(['user.user_name', 'user.user_tel', 'user.province', 'user.city', 'user.user_pic', 'user.area', 'user.address', 'user.cust_no', 'user.user_name', 'user_funds.all_funds', 'user_funds.able_funds',
                            'user_funds.ice_funds', 'user_funds.user_glcoin', 'user_funds.no_withdraw', 'user.authen_status', 'user.is_operator', 's.cert_status', 'e.introduction', 'e.expert_status', 'e.remark', 's.business_status',
                            'user.is_operator', 'user.user_type', 'user.level_id', 'user.spread_type', 'bussiness.bussiness_id', 'e.identity', 'user.last_login', 'user.user_land'])
                        ->leftJoin('user_funds', 'user_funds.cust_no = user.cust_no')
                        ->leftJoin('store as s', 's.user_id = user.user_id')
                        ->leftJoin('expert as e', 'e.user_id = user.user_id')
                        ->leftJoin('bussiness', 'bussiness.user_id = user.user_id')
                        ->where(['user.cust_no' => $custNo])->asArray()->one();
        if (empty($user['cert_status'])) {
            $user['cert_status'] = 1;
        }
        if (empty($user['expert_status'])) {
            $user['expert_status'] = 0;
        }
        $user['user_land'] = empty($user['user_land']) ? 0 : 1;
        
        if ($user['is_operator'] == 2) {
            $business = StoreOperator::find()->select(['s.business_status'])
                    ->innerJoin('store s', 's.store_code = store_operator.store_id and s.status = 1')
                    ->where(['store_operator.user_id' => $this->userId, 'store_operator.status' => 1])
                    ->asArray()
                    ->one();
            if (empty($business)) {
                $user['is_operator'] = 1;
            } else {
                $user['business_status'] = $business['business_status'];
            }
        }
        $user['withdraw_funds'] = sprintf("%.2f", $user['able_funds'] - $user['no_withdraw']);
        $user['authen_status_name'] = $authStatus[$user['authen_status']];
        $user['expert_status_name'] = $expertStatus[$user['expert_status']];
        return $this->jsonResult(600, '获取成功', $user, true);
    }

    /**
     * 说明: 获取用户类型 1、普通该用户 2、可购彩用户
     * @author  kevi
     * @date 2017年6月30日 上午11:31:13
     * @param
     * @return 
     */
    public function actionGetUserType() {
        $custNo = $this->custNo;
        $user = User::find()->select('user_type,is_operator')->where(['cust_no' => $custNo])->asArray()->one();
        if (empty($user)) {
            return $this->jsonError(401, '获取失败');
        }
        return $this->jsonResult(600, '获取成功', ['user_type' => $user['user_type'], "is_operator" => $user['is_operator']]);
    }

    /**
     * 说明：获取用户的积分，和所属代理商热门礼品
     * @auther zyl
     * @return
     */
    public function actionGetUserIntergal() {
        $custNo = $this->custNo;
//        $custNo = 'gl00001040';
        $user = User::find()->select(['user.user_name', 'user.level_name', 'user.user_pic', 'user.agent_code', 'user_funds.user_integral'])
                ->leftJoin('user_funds', 'user_funds.cust_no = user.cust_no')
                ->where(['user.cust_no' => $custNo])
                ->asArray()
                ->one();
        $gift = Gift::find()->select(['gift_name', 'gift_picture', 'gift_integral', 'gift_remark'])->where(['agent_code' => $user['agent_code']])->andWhere(['>', 'in_stock', 0])->limit(3)->orderBy('exchange_nums')->asArray()->all();

        $list['user'] = $user;
        $list['gift'] = $gift;
        return $this->jsonResult(600, '获取成功', $list);
    }

    /**
     * 说明: 修改支付密码发送短信验证码
     * @return 
     */
    public function actionGetPayPasswordSmsCode() {
        $user = User::findOne(["cust_no" => $this->custNo]);
        $userTel = $user->user_tel;
        $cType = 4; //1:注册 4:修改密码
        if (empty($userTel)) {
            return $this->jsonError(100, '参数缺失');
        }
        $saveKey = Constants::SMS_KEY_UP_PAY_PWD;
        $smstool = new SmsTool();
        $ret = $smstool->sendSmsCode($cType, $saveKey, $userTel);
        if ($ret) {
            return $this->jsonResult(600, '发送成功', true);
        } else {
            return $this->jsonError(100, '发送失败');
        }
    }

    public function actionSettingpaypassword() {
        $user = User::findOne(["cust_no" => $this->custNo]);
        $request = \Yii::$app->request;
        $pwd1 = $request->post('pay_password', '');
        $pwd2 = $request->post('valid_password', '');
        $smsCode = $request->post('smsCode', '');
        if (empty($pwd1) || empty($pwd2) || empty($smsCode)) {
            return $this->jsonError(100, '参数缺失');
        }
        if ($pwd1 !== $pwd2) {
            return $this->jsonError(109, '两次输入的密码不一致！！');
        }
        $db = \Yii::$app->db;
        $saveKey = Constants::SMS_KEY_UP_PAY_PWD;
        SmsTool::check_code($saveKey, $user->user_tel, $smsCode);
        $ret = $db->createCommand()->update('user_funds', [
                    "pay_password" => md5($pwd1)
                        ], [
                    "cust_no" => $this->custNo
                ])->execute();
        if ($ret === false) {
            return $this->jsonResult(2, "支付密码设置错误", "");
        } else {
            return $this->jsonResult(600, "支付密码设置成功", "");
        }
    }

    /**
     * 说明:用户头像上传至七牛(form表单提交)
     * @author  kevi
     * @date 2017年1月12日 上午10:15:16
     * @param
     * @return
     */
    public function actionUploadUserPic() {
        $request = \Yii::$app->request;
        $file = $_FILES['file'];
        $pic = $file['tmp_name'];
        $day = date('ymdHis', time());
        $custNo = $this->custNo;
        $userId = $this->userId;
        if (empty($custNo)) {
            $this->jsonError(100, '用户不存在');
        }
        if ($file['name']) {
            $user = User::findOne(["cust_no" => $custNo]);
            if (!$user) {
                $this->jsonError(100, '用户不存在');
            }
            $type = strtolower(substr($file['name'], strrpos($file['name'], '.') + 1));
            if (!in_array($type, ['gif', 'jpg', 'jpeg', 'png'])) {
                $this->jsonError(440, '文件格式不正确');
            }
            $key = 'img/user/user_pic/' . $custNo . '/' . $day . '.' . $type;
            $picture = Uploadfile::qiniu_upload($pic, $key); //上传至七牛服务器
            if ($picture == 441) {
                $this->jsonError(441, '上传失败');
            }
            $user->user_pic = $picture;
            $user->saveData();
            $UserGrowthRecord = new UserGrowthRecord();
            $UserGrowthRecord->addInfoPerfect($userId);       //完善资料送成长值
            $this->jsonResult(600, 'cuss', ['user_pic' => $picture]);
        }
        $this->jsonError(442, '上传文件不存在');
    }

    /**
     * 会员关注门店
     * @auther GL zyl
     * @date 2017-07-11 
     * @return type
     */
    public function actionUserFollow() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004357';
        $request = Yii::$app->request;
        $storeId = $request->post('store_id', '');
        if ($storeId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $storeNo = Store::find()->select(['store_code', 'company_id', 'cust_no'])->where(['store_code' => $storeId, 'status' => 1])->asArray()->one();
        if (empty($custNo)) {
            $userTel = $request->post('user_tel', '');
            $smsCode = $request->post('smsCode', '');
            if ($userTel == '' || $smsCode == '') {
                return $this->jsonError(100, '参数缺失');
            }
            $saveKey = Constants::SMS_KEY_FOLLOW_STORE;
            SmsTool::check_code($saveKey, $userTel, $smsCode);
            $JavaUser = $this->userService->getJavaUserDetailByTel($userTel);
            if ($JavaUser['httpCode'] != 200) {
                $pwd = $request->post('password', '');
                if (empty($pwd)) {
                    return $this->jsonError(413, '该手机未注册, 请输入登入密码');
                }
                $JavaRegist = $this->userService->javaRegister($userTel, $pwd);
                if ($JavaRegist == 401) {//java接口请求失败
                    return $this->jsonError(401, '注册失败,请稍后重试');
                }
                $custNo = $JavaRegist['data'];
                $javaUserDetail = $this->userService->getJavaUserDetailByTel($userTel);
                $result = $this->userService->setRegisterData($javaUserDetail);
                if ($storeNo['company_id'] == 1) {
                    $result['register_from'] = 1;
                } else {
                    $result['register_from'] = 3;
                }

                $result['from_id'] = $storeId;
                $ret = $this->userService->createOrUpdateUser($userTel, $custNo, $result);
                //用户关注门店注册赠送优惠券
                CouponsDetail::activitySendCoupons('GL', 1, $custNo);
            } else {
                $custNo = $JavaUser['data']['account'];
            }
        }
//        $storeNo = Store::find()->select(['cust_no'])->where(['user_id' => $storeId])->asArray()->one();
        if (empty($storeNo)) {
            return $this->jsonError(109, '该门店还未入驻');
        }
        //判断是否有关注过门店，有就不能再关注其他门店
        $storeFollow = UserFollow::find()->where(['cust_no' => $custNo, "follow_status" => 1])->one();
        if (!empty($storeFollow)) {
            return $this->jsonError(109, '您已经关注过其他门店，不可再关注！');
        }

        $follow = UserFollow::find()->where(['cust_no' => $custNo, 'store_Id' => $storeId])->one();
        if (empty($follow)) {
            $follow = new UserFollow();
            $follow->create_time = date('Y-m-d H:i:s');
        } else {
            $follow->modify_time = date('Y-m-d H:i:s');
        }
        $follow->follow_status = 1;
        $follow->default_status = 2;
        $follow->cust_no = $custNo;
        $follow->store_id = $storeId;
        $follow->store_no = $storeNo['cust_no'];
        if ($follow->validate()) {
            $insertId = $follow->save();
            if ($insertId == false) {
                return $this->jsonError(109, '关注失败，请稍后再关注');
            }
            return $this->jsonResult(600, '关注成功', '');
        } else {
            return $this->jsonResult(109, '关注失败,验证失败', $follow->firstErrors);
        }
    }

    /**
     * 获取会员关注门店列表
     * @auther GL zyl
     * @date 2017-07-11 
     * @return type
     */
    public function actionGetFollowList() {
        $custNo = $this->custNo;
        $userInfo = User::find()->select(['from_id', 'user_land', 'register_from', 'uf.store_id'])
                ->leftJoin('user_follow uf', 'uf.cust_no = user.cust_no')
                ->where(['user.cust_no' => $custNo])
                ->asArray()
                ->one();
        $province = '';
        $where = ['or'];
        if (empty($userInfo['user_land'])) {
            $toolfun = new Toolfun();
            $userIp = $toolfun->getUserIp();
            $province = $this->userService->getProvinceByIp($userIp);
            if (empty($province)) {
                $province = '江苏';
            }
        } else {
            $province = $this->userService->getProvinceByIp('', $userInfo['user_land']);
        }
        if (!empty($userInfo['store_id'])) {
            $where[] = ['store.store_code' => $userInfo['store_id']];
        }
        if ($userInfo['register_from'] != 3) {
            $where[] = ['and', ['like', 'store.province', $province], ['store.company_id' => 1]];
        }

//        $custNo = 'gl00004278';
        $request = Yii::$app->request;
        $pageNum = $request->post('page_num', 1);
        $pageSize = $request->post('page_size', 10);
        $total = Store::find()->leftJoin('user_follow uf', "uf.store_id = store.store_code and uf.cust_no = '{$custNo}'")->where(['store.status' => 1])->andWhere($where)->count();
        $pages = ceil($total / $pageSize);
        $offset = ($pageNum - 1) * $pageSize;
        $followList = Store::find()->select(['uf.default_status', 'uf.ticket_amount', 'uf.ticket_count', 'store.store_code store_id', 'store.cust_no store_no', 'store.business_status', 'store.store_code', 'store.store_name', 'store.province', 'store.city', 'store.area', 'store.address', 'store.coordinate', 'store.telephone as phone_num', 'store.company_id', 'store.sale_lottery'])
                ->leftJoin('user_follow uf', "uf.store_id = store.store_code and uf.cust_no = '{$custNo}'")
                ->where(['store.status' => 1])
                ->andWhere($where)
                ->limit($pageSize)
                ->offset($offset)
                ->orderBy('uf.follow_status desc, store.store_code')
                ->asArray()
                ->all();
        $lotteryName = Constants::LOTTERY;
        foreach ($followList as &$val) {
            $val['address_str'] = $val['province'] . ' ' . $val['city'] . ' ' . $val['area'] . ' ' . $val['address'];
            $saleArr = [];
            $saleLotName = [];
            $saleLottery = explode(',', $val['sale_lottery']);
            if (empty($val['sale_lottery'])) {
                $val['sale_lottery'] = [];
                $val['sale_lottery_name'] = '';
                continue;
            }
            $val['ticket_amount'] = empty($val['ticket_amount']) ? 0 : $val['ticket_amount'];
            $val['ticket_count'] = empty($val['ticket_count']) ? 0 : $val['ticket_count'];
            foreach ($saleLottery as $v) {
                if ($v == '3000' || $v == '3200') {
                    array_push($saleArr, '3006', '3007', '3008', '3009', '3010', '3011');
                    $saleLotName[] = '竞彩足球';
                } elseif ($v == '3100') {
                    array_push($saleArr, '3001', '3002', '3003', '3004', '3005');
                    $saleLotName[] = '竞彩篮球';
                } elseif ($v == '5000') {
                    array_push($saleArr, '5001', '5002', '5003', '5004', '5005', '5006');
                    $saleLotName[] = '北京单场';
                } elseif ($v == '3300') {
                    array_push($saleArr, '301201', '301301');
                    $saleLotName[] = '竞彩冠亚军';
                } else {
                    $saleLotName[] = $lotteryName[$v];
                }
                array_push($saleArr, $v);
            }
            $val['sale_lottery'] = $saleArr;
            $val['sale_lottery_name'] = implode(',', $saleLotName);
        }
        $data = ['page_num' => $pageNum, 'data' => $followList, 'size' => $pageSize, 'pages' => $pages, 'total' => $total];
        return $this->jsonResult(600, '关注列表', $data);
    }

    /**
     * 获取会员默认关注门店
     * @auther GL ctx
     * @date 2017-07-11 
     * @return json
     */
    public function actionGetDefaultFollow() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004278';
        $request = Yii::$app->request;
        $source = $request->post('source', '');
        $code = $request->post('lottery_code', '');
        $land = $request->post('user_land', '');
        $user = User::findOne(['cust_no' => $custNo]);
        if (empty($user->user_land)) {
            $user->user_land = $land;
            $user->modify_time = date('Y-m-d H:i:s');
            $user->save();
        }
        if (empty($source)) {
            $followList = (new Query)->select(['s.store_name', 's.business_status', 'uf.store_id', 'uf.store_no', 's.sale_lottery'])
                    ->from('user_follow as uf')
                    ->leftJoin('store as s', 's.store_code = uf.store_id')
                    ->where(['uf.follow_status' => 1, 'uf.default_status' => 2, 's.status' => 1])
                    ->andWhere(['uf.cust_no' => $custNo])
                    ->one();
        } else {
//            $storeFun = new Storefun();
            $ipProvince = $request->post('ip_province', ''); // 下单省份
            $store = Storefun::getStore($code, 1, 1, '', $ipProvince);
            $followList['store_name'] = $store['data']['store_name'];
            $followList['business_status'] = $store['data']['business_status'];
            $followList['store_id'] = $store['data']['store_no'];
            $followList['store_no'] = $store['data']['cust_no'];
            $followList['sale_lottery'] = $store['data']['sale_lottery'];
        }

        if ($followList == null) {
            return $this->jsonError(433, '未设置默认彩店');
        }
        $saleLottery = explode(',', $followList['sale_lottery']);
        $saleArr = [];
        foreach ($saleLottery as $v) {
            if ($v == '3000') {
                array_push($saleArr, '3006', '3007', '3008', '3009', '3010', '3011');
            } elseif ($v == '3100') {
                array_push($saleArr, '3001', '3002', '3003', '3004', '3005');
            } elseif ($v == '5000') {
                array_push($saleArr, '5001', '5002', '5003', '5004', '5005', '5006');
            } elseif ($v == '3300') {
                array_push($saleArr, '301201', '301301');
            }
            array_push($saleArr, $v);
        }
        $followList['sale_lottery'] = $saleArr;
        return $this->jsonResult(600, '默认门店', $followList);
    }

    /**
     * 会员取消门店关注
     * @auther GL zyl
     * @date 2017-07-11 
     * @return type
     */
    public function actionCancelFollow() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004278';
        $request = Yii::$app->request;
        $storeNo = $request->post('store_id', '');
        if ($storeNo == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $result = $this->userService->setStatus($custNo, $storeNo, 0, 2);
        return $this->jsonError($result['code'], $result['msg']);
    }

    /**
     * 会员设置默认出票门店
     * @auther GL zyl
     * @date 2017-07-11 
     * @return type
     */
    public function actionSetDefaultFollow() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004357';    
        $request = Yii::$app->request;
        $storeId = $request->post('store_id', '');
        if ($storeId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $deal = UserFollow::updateAll(['default_status' => 1], "cust_no= '" . $custNo . "'");
        if ($deal === false) {
            return $this->jsonError(401, '操作失败');
        }
        $result = $this->userService->setStatus($custNo, $storeId, 2, 1);
        return $this->jsonError($result['code'], $result['msg']);
    }

    /**
     * 获取商户详情
     * @auther GL zyl
     * @return json
     */
    public function actionGetStoreDetail() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004278';
        $request = Yii::$app->request;
        $storeId = $request->post('store_id', '');
        if (!empty($custNo)) {
            $exits = UserFollow::find()->select(['follow_status'])->where(['cust_no' => $custNo, 'store_id' => $storeId])->asArray()->one();
            $user = User::findOne(["cust_no" => $custNo]);
            if (empty($exits)) {
                $userFollow = 0;
            } else {
                $userFollow = $exits['follow_status'];
            }
            $userTel = $user->user_tel;
            $registFrom = $user->register_from;
        } else {
            $userFollow = 0;
            $userTel = "";
            $registFrom = '';
        }
        $lotteryName = Constants::LOTTERY;
//        $detail = Store::find()->select(['store_id', 'cust_no', 'user_id', 'store_code', 'store_name', 'phone_num', 'telephone', 'province', 'city', 'area', 'address', 'coordinate', 'store_img', 'support_bonus', 'sale_lottery'])->where(['user_id' => $storeId])->asArray()->one();
        $detail = Store::find()->select(['store_id', 'cust_no', 'user_id', 'store_code', 'store_name', 'telephone as phone_num', 'province', 'city', 'area', 'address', 'coordinate', 'store_img', 'support_bonus', 'sale_lottery', 'company_id'])->where(['store_code' => $storeId, 'status' => 1])->asArray()->one();
        if (empty($detail)) {
            return $this->jsonError(109, '此门店不存在');
        }
        $saleLottery = explode(',', $detail['sale_lottery']);
        foreach ($saleLottery as $val) {
            if ($val == '3000') {
                $saleLotName[] = '竞彩足球';
            } else {
                $saleLotName[] = $lotteryName[$val];
            }
        }
        $detail['sale_lottery'] = implode(',', $saleLotName);
        $detail['user_follow_status'] = $userFollow;
        $detail['user_tel'] = $userTel;
        $detail['user_from'] = $registFrom;
        return $this->jsonResult(600, '商户详情', $detail);
    }

    /**
     * 获取认证基础信息
     * @authet GL zyl
     * @return type
     */
    public function actionGetAuthInfo() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004318';
        $check = Constants::CHECK_STATUS;
        $expertStatus = Constants::EXPERT_STATUS;
        $userInfo = User::find()->select(['user.user_name', 'user.user_pic', 'e.introduction', 'e.expert_status'])
                ->leftJoin('expert as e', 'e.user_id = user.user_id')
                ->where(['user.cust_no' => $custNo])
                ->asArray()
                ->one();

        $javaAuthInfo = $this->userService->javaGetAuthInfo($custNo);
        $javaStatus = $this->userService->javaGetStatus($custNo);
        if ($javaAuthInfo == 0 || $javaStatus == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }

        if ($javaAuthInfo['code'] != 1 || $javaStatus['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        if (empty($userInfo['expert_status'])) {
            $userInfo['expert_status'] = 0;
        }
        $data = [];
        $data['data']['user_pic'] = $userInfo['user_pic'];
        $data['data']['user_name'] = $userInfo['user_name'];
        $data['data']['introduction'] = $userInfo['introduction'];
        $data['data']['real_name'] = $javaAuthInfo['data']['realName'];
        $data['data']['card_no'] = $javaAuthInfo['data']['cardNo'];
        $data['data']['bank_info'] = $javaAuthInfo['data']['bankNo'];
        $data['data']['check_status'] = $javaAuthInfo['data']['checkStatus'];
        $data['data']['check_status_name'] = $check[$javaAuthInfo['data']['checkStatus']];
        $data['data']['haveBindCard'] = $javaStatus['data']['haveBindCard'];
        $data['data']['jzbStatus'] = $javaStatus['data']['jzbStatus'];
        $data['data']['expert_status'] = $userInfo['expert_status'];
        $data['data']['expert_status_name'] = $expertStatus[$userInfo['expert_status']];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取省份
     * @author GL zyl
     * @return type
     */
    public function actionGetProvince() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004357'; 
        $javaProvince = $this->userService->getProvince($custNo);
        if ($javaProvince == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaProvince['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        $data['data'] = $javaProvince['data'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取城市
     * @authet GL zyl
     * @return type
     */
    public function actionGetCity() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004357'; 
        $request = Yii::$app->request;
        $provinceId = $request->post('provinceId', '');
        if ($provinceId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $javaCity = $this->userService->getCity($custNo, $provinceId);
        if ($javaCity == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaCity['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        $data['data'] = $javaCity['data'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取区县
     * @authet GL zyl
     * @return type
     */
    public function actionGetArea() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004357'; 
        $request = Yii::$app->request;
        $cityId = $request->post('cityId', '');
        if ($cityId == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $javaArea = $this->userService->getArea($custNo, $cityId);
        if ($javaArea == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaArea['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        $data['data'] = $javaArea['data'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取开户行
     * @authet GL zyl
     * @return type
     */
    public function actionGetBank() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004357'; 
        $javaBank = $this->userService->getBank($custNo);
        if ($javaBank == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaBank['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        foreach ($javaBank['data'] as &$val) {
            $val['bankName'] = trim($val['bankName']);
        }
        $data['data'] = $javaBank['data'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取开户支行
     * @authet GL zyl
     * @return type
     */
    public function actionGetBankInfo() {
        $custNo = $this->custNo;
//         $custNo = 'gl00004357'; 
        $request = Yii::$app->request;
        $bankClsCode = $request->post('bankCode', '');
        $cityCode = $request->post('cityCode', '');
        if ($cityCode == '' || $bankClsCode == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $javaBankInfo = $this->userService->getBankInfo($custNo, $bankClsCode, $cityCode);
        if ($javaBankInfo == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaBankInfo['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        $data['data'] = $javaBankInfo['data'];
        foreach ($javaBankInfo['data'] as &$val) {
            $val['bankname'] = trim($val['bankname']);
        }
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 身份证图片上传
     * @authet GL zyl
     * @return type
     */
    public function actionAttestImgUpload() {
        $request = Yii::$app->request;
        $imgBase64 = $request->post('img_base64', '');
        if ($imgBase64 == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $javaUpload = $this->userService->javaUploadImg($imgBase64);
        $data['data'] = $javaUpload;
        return $this->jsonResult(600, '上传成功', $data);
    }

    /**
     * 提交 实名认证第一步
     * @authet GL zyl
     * @return type
     */
    public function actionRealNameOne() {
        $custNo = $this->custNo;
        $userId = $this->userId;
        $request = Yii::$app->request;
        $isCheck = User::find()->select(['authen_status'])->where(['user_id' => $userId])->asArray()->one();
        if (in_array($isCheck['authen_status'], [1, 2])) {
            return $this->jsonError(109, '非法操作,请勿重复提交');
        }
        $realName = $request->post('real_name', '');
        $cardNo = $request->post('card_no', '');
        $cardFront = $request->post('card_front', '');
        $cardBack = $request->post('card_back', '');
        $cardWith = $request->post('card_with', '');
        $bankImg = $request->post('bankCard_img', '');
        if ($realName == '' || $cardNo == '' || $cardFront == '' || $cardBack == '' || $cardWith == '' || $bankImg == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $javaRealOne = $this->userService->javaRealNameAuthOne($custNo, $realName, $cardNo, $cardFront, $cardBack, $cardWith, $bankImg);
        if ($javaRealOne == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaRealOne['code'] != 1) {
            return $this->jsonError(404, $javaRealOne['msg']);
        }
        return $this->jsonResult(600, '提交成功', true);
    }

    /**
     * 实名认证第二步
     * @authet GL zyl
     * @return type
     */
    public function actionRealNameTwo() {
        $custNo = $this->custNo;
        $userId = $this->userId;
//        $custNo = 'gl00004318';
        $request = Yii::$app->request;
        $bank = $request->post('bank', '');
        $bankNo = $request->post('bankNo', '');
        $province = $request->post('province', '');
        $city = $request->post('city', '');
        $area = $request->post('area', '');
        $tel = $request->post('tel', '');
        $sBankCode = $request->post('sBankCode', '');
        $bankCode = $request->post('bankCode', '');
        if ($bank == '' || $bankNo == '' || $province == '' || $city == '' || $area == '' || $tel == '' || $sBankCode == '' || $bankCode == '') {
            return $this->jsonError(100, '参数缺失');
        }

        $javaSmsCode = $this->userService->javaRealNameAuthTwo($custNo, $bank, $bankNo, $province, $city, $area, $tel, $sBankCode, $bankCode, $bank);
        if ($javaSmsCode == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaSmsCode['code'] != 1) {
            if ($javaSmsCode['code'] == '-1') {
                return $this->jsonError(109, '银行卡出错');
            } elseif ($javaSmsCode['code'] == '-100') {
                return $this->jsonError(109, '手机号错误或不存在用户');
            }
            return $this->jsonError(109, '验证码发送失败,请稍后再试');
        }
        $data = $javaSmsCode['data'];
        if ($data['code'] != '000000') {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '获取验证码成功', $data['msg']);
    }

    /**
     * 实名认证第三步
     * @authet GL zyl
     * @return type
     */
    public function actionRealNameThree() {
        $custNo = $this->custNo;
        $userId = $this->userId;
        $request = Yii::$app->request;
        $bank = $request->post('bank', ''); // 开户行
        $bankNo = $request->post('bankNo', ''); //银行卡号
        $province = $request->post('province', ''); //开户省
        $city = $request->post('city', ''); // 开户市
        $area = $request->post('area', ''); // 开户区县
        $tel = $request->post('tel', ''); //开户行预留电话
        $sBankCode = $request->post('sBankCode', ''); //超级网银号
        $bankCode = $request->post('bankCode', ''); //大小额 开户行编号
        $verCode = $request->post('verCode', ''); // 验证码 
        $jzbRegist = $request->post('jzeRegist', ''); //见证宝开户 1:已开户 0：未开户
        $outlets = $request->post('outlets', ''); //开户行网点名称
        if ($bank == '' || $bankNo == '' || $province == '' || $city == '' || $area == '' || $tel == '' || $sBankCode == '' || $bankCode == '' || $jzbRegist == '' || $outlets == '') {
            return $this->jsonError(100, '参数缺失');
        }
        if ($jzbRegist == 0) {
            if ($verCode == '') {
                return $this->jsonError(100, '参数缺失');
            }
        }
        $javaVerCode = $this->userService->javaRealNameAuthThree($custNo, $bank, $bankNo, $province, $city, $outlets, $tel, $sBankCode, $bankCode, $bank, $verCode, $jzbRegist);
        if ($javaVerCode == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaVerCode['code'] != 1) {
            return $this->jsonError(109, '绑定失败');
        }
        if ($javaVerCode['data']['code'] != '000000') {
            return $this->jsonError(109, '绑定失败');
        }
        $expert = Expert::findOne(['user_id' => $this->userId, 'expert_status' => 4]);
        if (!empty($expert)) {
            $expert->expert_status = 1;
            if (!$expert->save()) {
                return $this->jsonError(109, '提交失败');
            }
        }

        //实名认证送成长值
//        $UserGrowthRecord = new UserGrowthRecord();
//        $UserGrowthRecord->updateGrowth($custNo, '', 6);  //6=实名认证送1次性成长值
        return $this->jsonResult(600, '绑定成功', true);
    }

    /**
     * 解绑
     * @authet GL zyl
     * @return type
     */
    public function actionUnbind() {
        $custNo = $this->custNo;
        $javaUnbind = $this->userService->javaUnbindBankCard($custNo);
//        print_r($javaUnbind);die;
        if ($javaUnbind == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaUnbind['code'] != 1) {
            return $this->jsonError(109, '请求解绑失败');
        }
        if ($javaUnbind['data']['code'] != '000000') {
            return $this->jsonError(109, '银行解绑失败');
        }
        return $this->jsonResult(600, $javaUnbind['msg'], true);
    }

    /**
     * 获取验证码
     * @authet GL zyl
     * @return type
     */
    public function actionGetBankSmsCode() {
        $custNo = $this->custNo;
        $request = Yii::$app->request;
        $bank = $request->post('bank', '');
        $bankNo = $request->post('bankNo', '');
        $province = $request->post('province', '');
        $city = $request->post('city', '');
        $area = $request->post('area', '');
        $tel = $request->post('tel', '');
        $sBankCode = $request->post('sBankCode', '');
        $bankCode = $request->post('bankCode', '');
        if ($bank == '' || $bankNo == '' || $province == '' || $city == '' || $area == '' || $tel == '' || $sBankCode == '' || $bankCode == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $javaSmsCode = $this->userService->javaSmsCodeForBoundBankCard($custNo, $bank, $bankNo, $province, $city, $area, $tel, $sBankCode, $bankCode, $bank);
        if ($javaSmsCode == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaSmsCode['code'] != 1) {
            if ($javaSmsCode['code'] == '-1') {
                return $this->jsonError(109, '银行卡出错');
            } elseif ($javaSmsCode['code'] == '-100') {
                return $this->jsonError(109, '手机号错误或不存在用户');
            }
            return $this->jsonError(109, '验证码发送失败,请稍后再试');
            return $this->jsonError(109, '验证码发送失败,请稍后再试');
        }
        $data = $javaSmsCode['data'];
        if ($data['code'] != '000000') {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '获取验证码成功', $data['msg']);
    }

    /**
     * 绑定
     * @authet GL zyl
     * @return type
     */
    public function actionBinkBankCard() {
        $custNo = $this->custNo;
        $request = Yii::$app->request;
        $bank = $request->post('bank', '');
        $bankNo = $request->post('bankNo', '');
        $province = $request->post('province', '');
        $city = $request->post('city', '');
        $area = $request->post('area', '');
        $tel = $request->post('tel', '');
        $sBankCode = $request->post('sBankCode', '');
        $bankCode = $request->post('bankCode', '');
        $verCode = $request->post('verCode', '');
        $jzbRegist = $request->post('jzeRegist', '');
        if ($bank == '' || $bankNo == '' || $province == '' || $city == '' || $area == '' || $tel == '' || $sBankCode == '' || $bankCode == '' || $verCode == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $javaVerCode = $this->userService->javaBindBankCard($custNo, $bank, $bankNo, $province, $city, $area, $tel, $sBankCode, $bankCode, $bank, $verCode);
        if ($javaVerCode == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaVerCode['code'] != 1) {
            return $this->jsonError(109, '绑定失败');
        }
        if ($javaVerCode['data']['code'] != '000000') {
            return $this->jsonError(109, '绑定失败');
        }
        return $this->jsonResult(600, '绑定成功', true);
    }

    /**
     * 获取收款账户信息
     * @auther GL zyl
     * @return type
     */
    public function actionGetAccountInfo() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004318';
        $javaAccountInfo = $this->userService->javaGetAccountDetail($custNo);
        if ($javaAccountInfo['code'] != 1) {
            return $this->jsonError(109, '获取失败');
        }
        $data = [];
        $data['data']['realName'] = $javaAccountInfo['data']['realName'];
        $data['data']['bank'] = $javaAccountInfo['data']['depositBank'];
        $data['data']['outlets'] = $javaAccountInfo['data']['bankOutlets'];
        $data['data']['local'] = $javaAccountInfo['data']['depositProvince'] . ' ' . $javaAccountInfo['data']['depositCity'];
        $data['data']['bankNo'] = $javaAccountInfo['data']['bankNo'];
        $data['data']['tel'] = $javaAccountInfo['data']['reservedPhone'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 余额提现
     * @auther GL zyl
     * @return type
     */
    public function actionWithdraw() {
        $custNo = $this->custNo;
//        $userId = $this->userId;
//        $custNo = 'gl00001064';
//        $userId = 23;
//        return $this->jsonError(109, '功能暂时关闭');
        $acc = $this->userService->javaGetAccountDetail($custNo);
        if ($acc['code'] == '90005') {
            return $this->jsonError(109, $acc['msg']);
        }
        if ($acc['code'] == -1) {
            return $this->jsonError(109, $acc['msg'] . ',请先绑定银行卡');
        }
        $request = Yii::$app->request;
        $money = $request->post('money', '');
        $payPwd = $request->post('pay_pwd', '');
        if ($money == '' || $payPwd == '') {
            return $this->jsonError(100, '参数缺失');
        }
        if ($money < 5) {
            return $this->jsonError(109, '不可低于最小提现额度5块钱');
        }
//        $userPayPwd = User::find()->select(['user_id', 'authen_status'])->where(['user_id' => $userId])->asArray()->one();
//        if ($userPayPwd['authen_status'] != 1) {
//            return $this->jsonError(109, '请先通过实名认证');
//        }
        $javaStatus = $this->userService->javaGetStatus($custNo);
        if ($javaStatus == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }
        if ($javaStatus['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        if ($javaStatus['data']['checkStatus'] != 1) {
            return $this->jsonError(109, '请先通过实名认证');
        }

        $javaAddMch = $this->userService->javaAddMchCustFlushCache($custNo);
        if ($javaAddMch['code'] != 1) {
            return $this->jsonError(416, $javaAddMch['msg']);
        }
        $userFunds = UserFunds::find()->select(['cust_no', 'pay_password', 'able_funds', 'no_withdraw', 'all_funds', 'withdraw_status'])->where(['cust_no' => $custNo])->asArray()->one();
        if ($userFunds['withdraw_status'] != 1) {
            return $this->jsonError(488, '您已被禁止提现！！有疑问请联系客服~');
        }
        if (empty($userFunds['pay_password'])) {
            return $this->jsonError(403, '未设置支付密码');
        }
        if (md5($payPwd) != $userFunds['pay_password']) {
            return $this->jsonError(406, '支付密码错误');
        }
        if (bcsub($userFunds['able_funds'], $userFunds['no_withdraw'], 2) < floatval($money)) {
//        if ((floatval($userFunds['able_funds'] - floatval($userFunds['no_withdraw'])) < floatval($money))) {
            return $this->jsonError(407, '提现金额不可大于可提现余额');
        }
        $third_check = \Yii::$app->params['third_check'];
        if ($third_check) {//验证双方计算金额
            $chekc = SyncApiRequestService::getTotalAmount($custNo);
            if (!$chekc || $chekc['code'] != 600) {
                KafkaService::addLog('thirdGetTotalAmount_error', "第三方系统出错");
                return \Yii::jsonResult(109, "系统出错请联系客服", "");
            }
            $sysmoney = $userFunds['all_funds'];
            if ($sysmoney != $chekc['amount']) {
                KafkaService::addLog('thirdGetTotalAmount_money_error', 'sysmoney:' . $sysmoney . ';thirMoney:' . $chekc['amount']);
                return \Yii::jsonResult(110, "系统出错请联系客服", "");
            }
        }
        $withdrawService = new WithdrawService();
        $result = $withdrawService->balanceWithdraw($custNo, $money, $custType = 1);
        if ($result['code'] != 600) {
            return $this->jsonError($result['code'], '提现失败' . $result['msg']);
        }
        $redis = \Yii::$app->redis;
        $redis->executeCommand('zadd', ["waitting_callback_withdraw", date('ymdHis'), $result['data']]);
        SyncService::syncFromHttp();
        return $this->jsonResult(600, $result['msg'], true);
    }

    /**
     * 修改名称
     * @auther GL zyl
     * @return type
     */
    public function actionSetNickname() {
        $userId = $this->userId;
//        $userId = 22;
        $request = Yii::$app->request;
        $nickname = $request->post('nickname', '');
        if ($nickname == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $nickname = str_replace(' ', '', $nickname);
        $exist = User::find()->select(['user_name'])->where(['user_name' => $nickname])->andWhere(['!=', 'user_id', $userId])->asArray()->one();
        if (!empty($exist)) {
            return $this->jsonError(109, '该昵称已被征用啦！');
        }
        $userData = User::findOne(['user_id' => $userId]);
        $userData->user_name = $nickname;
        $userData->modify_time = date('Y-m-d H:i:s');
        if (!$userData->saveData()) {
            return $this->jsonError(109, '修改失败');
        }
        $UserGrowthRecord = new UserGrowthRecord();
        $UserGrowthRecord->addInfoPerfect($userId);       //完善资料送成长值
        return $this->jsonResult(600, '修改成功', true);
    }

    /**
     * 获取实名认证信息
     * @auther GL zyl
     * @return type
     */
    public function actionGetRealName() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004307';
        $javaRealName = $this->userService->javaGetRealName($custNo);
        if ($javaRealName['code'] != 1) {
            return $this->jsonError(109, '获取失败');
        }
        $data['data'] = $javaRealName['data'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 说明: 关注门店，获取验证码
     * @auther GL zyl
     * @return 
     */
    public function actionGetFollowSmsCode() {
        $request = Yii::$app->request;
        $userTel = $request->post('userTel', '');
        $cType = 4; //1:注册 4:修改密码
        if (empty($userTel)) {
            return $this->jsonError(100, '参数缺失');
        }
        $saveKey = Constants::SMS_KEY_FOLLOW_STORE;
        $smstool = new SmsTool();
        $ret = $smstool->sendSmsCode($cType, $saveKey, $userTel);
        if ($ret) {
            $userByTel = $this->userService->getJavaUserDetailByTel($userTel);
            if ($userByTel['httpCode'] == 412) {
                $data['is_regist'] = 0;
            } else {
                $data['is_regist'] = 1;
            }
            return $this->jsonResult(600, '发送成功', $data);
        } else {
            return $this->jsonError(109, '发送失败，请稍后再试！');
        }
    }

    /**
     * 提现界面,获取信息
     * @auther GL zyl
     * @return type
     */
    public function actionGetWithdrawInfo() {
        $custNo = $this->custNo;
//        $userId = $this->userId;
        $data = [];
//        $isCheck = User::find()->select(['authen_status'])->where(['user_id' => $userId])->asArray()->one();
        $javaStatus = $this->userService->javaGetStatus($custNo);
        if ($javaStatus == 0) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }

        if ($javaStatus['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }

        if ($javaStatus['data']['checkStatus'] != 1) {
            $data['data']['info_status'] = 0;
        } else {
            $acc = $this->userService->javaGetAccountDetail($custNo);
            if ($acc['code'] == 1) {
                $data['data']['account'] = $acc['data']['depositBank'];
                $data['data']['bankNo'] = '尾号 ' . substr($acc['data']['bankNo'], -4);
                $data['data']['info_status'] = 2;
            } else {
                $data['data']['account'] = '';
                $data['data']['bankNo'] = '';
                $data['data']['info_status'] = 1;
            }
        }
        $funds = UserFunds::find()->select(['able_funds', 'no_withdraw', 'withdraw_status'])->where(['cust_no' => $custNo])->asArray()->one();
        $min = Constants::MIN_WITHDRAW;
        $data['data']['withdraw_funds'] = sprintf("%.2f", $funds['able_funds'] - $funds["no_withdraw"]);
        $data['data']['no_withdraw_funds'] = $funds['no_withdraw'];
        $data['data']['able_funds'] = $funds['able_funds'];
        $data['data']['min'] = $min;
        $data['data']['withdraw_status'] = $funds['withdraw_status'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 设置地址
     * @auther GL zyl
     * @return type
     */
    public function actionSetAddress() {
        $userId = $this->userId;
        $request = \Yii::$app->request;
        $province = $request->post('province', '');
        $city = $request->post('city', '');
        $area = $request->post('area', '');
        $address = $request->post('address', '');
        if ($province == '' || $city == '' || $area == '' || $address == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $user = User::findOne(['user_id' => $userId]);
        $user->province = $province;
        $user->city = $city;
        $user->area = $area;
        $user->address = $address;
        $UserGrowthRecord = new UserGrowthRecord();
        $UserGrowthRecord->addInfoPerfect($userId);       //完善资料送成长值
        if (!$user->saveData()) {
            return $this->jsonError(109, '数据存储失败');
        }
        return $this->jsonResult(600, '设置成功', true);
    }

    /**
     * 门店登录
     * @auther GL zyl
     * @return type
     */
    public function actionGoinStore() {
        $request = \Yii::$app->request;
        $userTel = $request->post('account');
        $password = $request->post('password');
        $userType = $request->post('type');
        if (empty($userTel) || empty($password) || empty($userType)) {
            return $this->jsonError(100, '参数缺失');
        }
        if ($userType != 3) {
            return $this->jsonError(401, '登录失败,请稍后再试');
        }
        $JavaUser = $this->userService->getJavaUser($userTel, $password); //java认证接口
        if (empty($JavaUser)) {//java接口请求失败
            return $this->jsonError(401, '登录失败,请稍后重试');
        }
        if ($JavaUser['httpCode'] != 1) {//java接口认证失败
            return $this->jsonError($JavaUser['httpCode'], $JavaUser['msg']);
        } else {//java接口认证成功--生成或者更新系统用户数据
            $user = User::find()->select(['cust_no', 'user_id'])->where(['cust_no' => $JavaUser['custNo'], 'user_type' => $userType])->asArray()->one();
            if (empty($user)) {
                return $this->jsonError(401, '请稍后再试');
            }
        }
        $token = $this->userService->autoLogin($user['cust_no'], $user['user_id']); //自动登录
        return $this->jsonResult(600, '登录成功', ['token' => $token]);
    }

    /**
     * 门店后台登录
     * @auther GL ctx
     * @return type
     */
    public function actionStoreBackLogin() {
        $request = \Yii::$app->request;
        $userTel = $request->post('account');
        $password = $request->post('password');
        $strLength = strlen($userTel);
//        $loginType = $request->post('loginType');
        if (empty($userTel) || empty($password)) {
            return $this->jsonError(100, '参数缺失');
        }
        //手机号用户登录
        if ($strLength == 11) {
            $JavaUser = $this->userService->getJavaUser($userTel, $password); //java认证接口
            if (empty($JavaUser)) {//java接口请求失败
                return $this->jsonError(401, '登录失败,请稍后重试');
            }
            if ($JavaUser['httpCode'] != 1) {//java接口认证失败
                return $this->jsonError($JavaUser['httpCode'], $JavaUser['msg']);
            } else {//java接口认证成功--生成或者更新系统用户数据
                $user = User::find()->select(['cust_no', 'user_id'])->where(['cust_no' => $JavaUser['custNo']])->andWhere(["or", ['user_type' => 3], ["is_operator" => 2]])->asArray()->one();
                if (empty($user)) {
                    return $this->jsonError(401, '登录失败,请稍后再试');
                }
                $token = $this->userService->autoLogin($user['cust_no'], $user['user_id'], "storeback");
            }
        } elseif ($strLength == 5) {
            //门店编码登录
            $Store = Store::find()->where(['store_code' => $userTel, 'password' => $password, "status" => 1])->asArray()->one();
            if (empty($Store)) {
                return $this->jsonError(401, '登录失败,请检查账号密码后再试');
            }
            $token = $this->userService->autoLogin($Store['cust_no'], $Store['user_id'], "storeback");
        } else {
            return $this->jsonError(401, '登录失败,请检查账号密码后再试');
        }

        return $this->jsonResult(600, '登录成功', ['token' => $token]);
    }

    /**
     * 说明: 系统注册用户-默认需要关注咕啦公司旗舰店
     * @author  kevi
     * @date 2017年9月15日 下午2:19:19
     * @return
     */
    public function actionNewUserFollowStore() {
        $redis = \Yii::$app->redis;
        //随机获取10个来处理
        $members = $redis->srandmember(Constants::REDIS_KEY_REGLIST, 10);
        $succList[] = Constants::REDIS_KEY_REGLIST;
        if ($members) {
            foreach ($members as $custNo) {
                $ret = $this->userService->followCompanyStore($custNo);
                if ($ret) {
                    $succList[] = $custNo;
                }
            }
            $ret = $redis->executeCommand('SREM', $succList);
            //是否写入日志
        }
        return $this->jsonResult(600, '执行成功', $succList);
    }

    /**
     * 扫一扫注册关注门店
     * @auther GL zyl
     * @return type
     */
    public function actionRegistFollow() {
        $request = Yii::$app->request;
        $userTel = $request->post('user_tel', '');
        $storeId = $request->post('store_id', '');
        if (empty($userTel) || empty($storeId)) {
            return $this->jsonError('100', '参数缺失');
        }
        $JavaUser = $this->userService->getJavaUserDetailByTel($userTel);
        if ($JavaUser['httpCode'] != 200) {
            $pwd = $request->post('password', '');
            if (empty($pwd)) {
                return $this->jsonError(413, '该手机未注册, 请输入登入密码');
            }
            $JavaRegist = $this->userService->javaRegister($userTel, $pwd);
            if ($JavaRegist == 401) {//java接口请求失败
                return $this->jsonError(401, '注册失败,请稍后重试');
            }
            $custNo = $JavaRegist['data'];
        } else {
            $custNo = $JavaUser['data']['account'];
        }
        $storeNo = Store::find()->select(['cust_no', 'user_id'])->where(['store_code' => $storeId])->asArray()->one();
        if (empty($storeNo)) {
            return $this->jsonError(109, '该门店还未入驻');
        }
        $follow = UserFollow::find()->where(['cust_no' => $custNo, 'store_id' => $storeId])->one();
        if (empty($follow)) {
            $follow = new UserFollow();
            $follow->create_time = date('Y-m-d H:i:s');
        } else {
            $follow->modify_time = date('Y-m-d H:i:s');
            $follow->follow_status = 1;
        }

        $follow->default_status = 2;
        $follow->cust_no = $custNo;
        $follow->store_id = $storeId;
//        $follow->store_no = $storeNo['cust_no'];
        if ($follow->validate()) {
            $insertId = $follow->save();
            if ($insertId == false) {
                return $this->jsonError(109, '关注失败，请稍后再关注');
            }
            return $this->jsonResult(600, '关注成功', '');
        } else {
            return $this->jsonResult(109, '关注失败,验证失败', $follow->firstErrors);
        }
    }

    /**
     * 获取是否屏蔽内容
     * @return type
     */
    public function actionSetShield() {
        $key = "IsShield:shield";
        $shield = \Yii::redisGet($key);
        if (empty($shield)) {
            $shield = 0;
        }
        $data['data'] = $shield;
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 专家申请
     * @auther GL zyl
     * @return type
     */
    public function actionApply() {
        $userId = $this->userId;
        $request = Yii::$app->request;
        $nickName = $request->post('nick_name', '');
        $introduction = $request->post('introduction', '');
        if (empty($nickName) || empty($introduction)) {
            return $this->jsonError(100, '参数缺失');
        }
        $nickName = str_replace(' ', '', $nickName);
        $exist = User::find()->select(['user_name'])->where(['user_name' => $nickName])->andWhere(['!=', 'user_id', $userId])->asArray()->one();
        if (!empty($exist)) {
            return $this->jsonError(109, '该昵称已被征用啦！');
        }
        $userModel = User::findOne(['user_id' => $userId]);
        $userModel->user_name = $nickName;
        if (!$userModel->saveData()) {
            return $this->jsonError(109, '提交失败');
        }
        $expertModel = Expert::findOne(['user_id' => $userId]);
        if (empty($expertModel)) {
            $expertModel = new Expert;
            $expertModel->create_time = date('Y-m-d H:i:s');
            $expertModel->expert_status = 1;
        } else {
            if ($expertModel->expert_status != 2) {
                $expertModel->expert_status = 1;
            }
        }
        $javaAuthInfo = $this->userService->javaGetAuthInfo($userModel->cust_no);
        if (isset($javaAuthInfo['httpCode']) && $javaAuthInfo['httpCode'] == 500) {
            return $this->jsonError(401, '服务器连接失败，请稍后再试');
        }

        if ($javaAuthInfo['code'] != 1) {
            return $this->jsonError(404, '获取失败');
        }
        if (in_array($javaAuthInfo['data']['checkStatus'], [0, 3])) {
            $expertModel->expert_status = 4;
        }
        $expertModel->user_id = $userId;
        $expertModel->cust_no = $this->custNo;
        $expertModel->introduction = $introduction;
        $expertModel->lottery = 1;
        $expertModel->identity = 1;
        $expertModel->modify_time = date('Y-m-d H:i:s');
        if (!$expertModel->validate()) {
            return $this->jsonError(109, '提交验证失败');
        }
        if (!$expertModel->save()) {
            return $this->jsonError(109, '提交保存失败');
        }
        return $this->jsonResult(600, '提交成功', true);
    }

    /**
     * 检查token合法性
     * @return userID
     */
    public function actionCheckUserToken() {
        $request = Yii::$app->request;
        $token = $request->post('token');
        !$token && $this->jsonError(110, '缺少token参数');
        if (is_string($token) && $token != '0') {
            $redisToken = \Yii::tokenGet('token_user:' . $token);
            if (!empty($redisToken)) {
                $redisArr = explode('|', $redisToken);
                \Yii::jsonResult(600, '验证成功', ['userId' => $redisArr[1], 'userNo' => $redisArr[0]]);
            }
        }

        \Yii::jsonError(400, "验证失败,token不正确");
    }

    /**
     * 说明:获取我的门店推广二维码
     * @author chenqiwei
     * @date 2018/2/9 上午10:50
     * @param
     * @return
     */
    public function actionGetStoreQr() {
        $userId = \Yii::$userId;
        $user = StoreUser::find()->where(['user_id' => $userId])->one();
        if (empty($user)) {
            return $this->jsonResult(600, '该用户未申请过门店', ['status' => 4]);
        }

        $title = \Yii::redisGet('jftc_title');
        $content = \Yii::redisGet('jftc_content');
        if (empty($title)) {
            $title = '体彩顶呱刮，新春嘉年华!';
        }
        if (empty($content)) {
            $content = '关注成功之后，可免费获取一张2元即开票兑换码';
        }
        $ret = [
            'user_pic' => '',
            'store_name' => $user->store_name,
            'store_code' => $user->store_code,
            'qr_url' => $user->qr_url,
            'status' => $user->status,
            'title' => $title,
            'content' => $content,
        ];
        return $this->jsonResult(600, '获取成功', $ret);
    }

    /**
     * 说明:
     * @author chenqiwei
     * @date 2018/3/6 上午10:09
     * @param
     * @return
     */
    public function actionLoginByCode() {
        $request = Yii::$app->request;
        $userTel = $request->post('account'); //手机号
        $smsCode = $request->post('code', '');
        $loginType = $request->post('type', ''); //代理商用
        $agentCode = $request->post('agent', ''); //代理商用
        $sysPlatform = $request->post('sysPlatform', ''); //用于区分注册来源（有值代理APP注册）
        $custNo = $userId = $userType = '';
        $saveKey = Constants::SMS_KEY_LOGIN;
        SmsTool::check_code($saveKey, $userTel, $smsCode);
        $user = User::find()->where(['user_tel' => $userTel])->asArray()->one();
        if ($user && $user['status'] == 2) {//php 用户存在，但是被禁用
            return $this->jsonError(402, '该账户已禁用，请联系咕啦管理人员。');
        } else if ($user) {//php 用户存在，状态正常
            $custNo = $user['cust_no'];
            $userId = $user['user_id'];
            $userType = $user['user_type'];
        } else {
            $autoRet = $this->autoRegister($userTel);
            $custNo = $autoRet['data']['cust_no'];
            $userId = $autoRet['data']['user_id'];
            $userType = $autoRet['data']['user_type'];
            //2018-06-16之前APP注册赠送优惠券88礼包
            if (isset($sysPlatform)) {
                if (date("Y-m-d") < "2018-06-16") {
                    CouponsDetail::activitySendCoupons('GL', 1, $custNo);
                }
            }
        }
        if ($loginType == 2) {//代理商 电视app
            $agentId = Agents::find()->where(['agents_code' => $agentCode])->one()->agents_id;
            $data = json_encode(['agents_id' => $agentId, 'cust_no' => $custNo, 'user_id' => $userId, 'expert_time' => time() + 604800]);
            $key = 'GL_lottery';
            $agentsTool = new AgentsTool();
            $platformToken = $agentsTool->encrypt($data, $key); //用户信息加密
            \Yii::tokenSet("platform_user_{$agentId}:{$userId}", "{$platformToken}");
            $token = $platformToken;
//            $token = urlencode($platformToken.'|'.$agents['agents_code']);
        } else {
            $token = $this->userService->autoLogin($custNo, $userId); //自动登录
        }
        return $this->jsonResult(600, '登录成功', ['token' => $token, 'user_type' => $userType, 'cust_no' => $custNo]);
    }

    public function autoRegister($userTel, $register_from = '', $agentCode = '') {
        $javaRet = $this->javaRegister($userTel, '888888');
        if ($javaRet['code'] == 600) {//用户不存在，自动注册java、php用户
            $javaUser = $this->userService->getJavaUserDetailByTel($userTel); //重新获取该手机号注册后的java信息
        } else if ($javaRet['code'] == 411) {//java用户存在php用户不存在,注册php用户
            $javaUser = $this->userService->getJavaUserDetailByTel($userTel); //根据手机号获取java信息
        } else {
            KafkaService::addLog('login-by-code', json_encode($javaUser));
            return $this->jsonError(400, '服务器内部错误，请稍后再试');
        }
        $phpRet = $this->phpRegister($userTel, $javaUser, $register_from, $agentCode);
        $data = [
            'cust_no' => $phpRet['cust_no'],
            'user_id' => $phpRet['user_id'],
            'user_type' => $phpRet['user_type']
        ];
        return ['code' => 600, 'data' => $data];
    }

    public function javaRegister($userTel, $password) {
        $javaUser = $this->userService->javaRegister($userTel, $password);
        if ($javaUser['httpCode'] == 401) {//java接口请求失败
            return ['code' => 401, 'msg' => '注册失败,请稍后重试'];
        } else if ($javaUser['httpCode'] == 411 || $javaUser['httpCode'] == 400) {
            return ['code' => 411, 'msg' => '注册失败,该手机号已经注册'];
        } else if ($javaUser['httpCode'] == 200) {
            return ['code' => 600, 'msg' => '注册成功', 'data' => ['cust_no' => $javaUser['data']]];
        } else {
            KafkaService::addLog('java-register', json_encode($javaUser));
            return ['code' => 400, 'msg' => '未知错误，请稍后再试'];
        }
    }

    public function phpRegister($userTel, $javaUser, $register_from = '', $agentCode = '') {
        $result = $this->userService->setRegisterData($javaUser);
        $result['register_from'] = 2;
        $userArr = $this->userService->createOrUpdateUser($userTel, $javaUser['data']['account'], $result, $register_from, '', $agentCode);
        $user = $userArr['data'];
        $UserGrowthRecord = new UserGrowthRecord();
        $UserGrowthRecord->updateGrowth($user['cust_no'], '', 4);  //注册送成长值
        return $user;
    }

    /**
     * 推广平台注册
     */
    public function actionPromoteRegister() {
        $request = Yii::$app->request;
        $userTel = $request->post('account');
        $agentCode = $request->post('agentCode', ''); //用于区分注册来源（GL:自己推广 MT:美图）
        $smsCode = $request->post('code');
        if (empty($userTel) || empty($agentCode) || empty($smsCode)) {
            return $this->jsonError(100, '注册失败,参数缺失');
        }
        if ($agentCode == "GL") {
            $register_from = User::REG_FROM_H5;
        } else {
            $register_from = User::REG_FTOM_PT;
        }

        $saveKey = Constants::SMS_KEY_REGISTER;
        SmsTool::check_code($saveKey, $userTel, $smsCode);
        //自动注册
        $user = User::find()->where(['user_tel' => $userTel])->asArray()->one();
        if ($user && $user['status'] == 2) {//php 用户存在，但是被禁用
            return $this->jsonError(402, '该账户已禁用，请联系咕啦管理人员。');
        } else {
            $autoRet = $this->autoRegister($userTel, $register_from, $agentCode);
        }
        if ($autoRet["code"] == 600) {
            return $this->jsonResult(600, '注册成功', ['cust_no' => $autoRet["data"]['cust_no']]);
        } else {
            return $this->jsonResult(109, '注册失败', '');
        }
    }

    /**
     * 用户获取优惠券
     * @return type
     */
    public function actionUserGainCoupons() {
        $custNo = $this->custNo;
        $key = 'userCoupons';
        if (\Yii::$app->redis->SISMEMBER($key, $custNo)) {
            return $this->jsonError(109, '您已经领取过该优惠券了！！请勿重复领取！！');
        }
        $res = CouponsDetail::activitySendCoupons('GL', 2, $custNo);
        if ($res === true) {
            return $this->jsonError(109, '该活动还未开始！敬请期待~');
        }
        if ($res['code'] != 600) {
            return $this->jsonError(109, $res['msg']);
        }
        \Yii::$app->redis->sadd($key, $custNo);
        return $this->jsonResult(600, '恭喜领取成功！欢迎使用~~', true);
    }

    /**
     * 获取领取信息
     * @return type
     */
    public function actionGetGainInfo() {
        $custNo = $this->custNo;
        $user = User::findOne(['cust_no' => $custNo]);
        $gainCoupons = 0;
        $now = date('Y-m-d H:i:s');
        if (strtotime($user->create_time) < strtotime('2018-05-20 00:00:00')) {
            $key = 'userCoupons';
            if (!\Yii::$app->redis->SISMEMBER($key, $user->cust_no)) {
                $gainCoupons = 1;
            }
        }
        $couponsAry = Activity::find()
                ->where(["use_agents" => 'GL', "type_id" => 2, "status" => 1])
                ->andWhere(['and', ["<=", "start_date", $now], [">", "end_date", $now]])
                ->asArray()
                ->one();
        if (empty($couponsAry)) {
            $gainCoupons = 0;
        }
        return $this->jsonResult(600, '获取成功', ['gainCoupons' => $gainCoupons]);
    }

    /**
     * 说明:抽奖活动
     * @author chenqiwei
     * @date 2018/3/6 上午10:09
     * @param
     * @return
     */
    public function actionActivityLogin() {
        $request = Yii::$app->request;
        $userTel = $request->post('account'); //手机号
        $smsCode = $request->post('code', '');
        $custNo = $request->post('cust_no');
        $openId = $request->post_nn('open_id');
        $openCode = $request->post_nn('order_code');
        if($custNo){
            $ret = $this->userService->recieiveDail($openId,$custNo,$openCode,'01');
        }else{
            $saveKey = Constants::SMS_KEY_LOGIN;
            SmsTool::check_code($saveKey, $userTel, $smsCode);
            $user = User::find()->where(['user_tel' => $userTel])->asArray()->one();
            if ($user && $user['status'] == 2) {//php 用户存在，但是被禁用
                return $this->jsonError(402, '该账户已禁用，请联系咕啦管理人员。');
            } else if ($user) {//php 用户存在，状态正常
                $custNo = $user['cust_no'];
            } else {//用户不存在
                $autoRet = $this->autoRegister($userTel);
                $custNo = $autoRet['data']['cust_no'];
            }
            $ret = $this->userService->recieiveDail($openId,$custNo,$openCode,'01');
        }
        if($ret['code']==600){
            SyncService::syncFromHttp();
            return $this->jsonResult(600, '领取成功', ['cust_no' => $custNo]);
        }else{
            return $this->jsonError(109,$ret['msg']);
        }

//        $surl = 'http://wallet.goodluckchina.net/app/scanCodePay/commonQueryStatus';
//        $post_data = ["orderNo" => $orderCode,'money'=>$money];
//        $curl_ret = \Yii::sendCurlPost($surl, $post_data);
//        if($curl_ret['code'] == 1){//成功
//            $ret = $this->userService->getDailResult($orderCode,'01',$custNo);
//            if($ret['code']==109){
//                return $this->jsonError(109, $ret['msg']);
//            }
//            $result = $ret['data'];
//            return $this->jsonResult(600, '登录成功', $result);
//        }elseif($curl_ret['code'] == '-1'){
//            return $this->jsonError(109, '该订单支付失败，无法参与活动');
//        }else{
//            return $this->jsonError(109, '该订单支付未完成，请稍后再试');
//        }
//            $token = $this->userService->autoLogin($custNo, $userId); //自动登录
    }

    public function actionDoActivity() {
        $request = Yii::$app->request;
        $openId = $request->post_nn('open_id');
        $orderCode = $request->post_nn('order_code');
        $money = $request->post_nn('money');
        if($money<188){
            return $this->jsonError(109, '该订单金额不达标，无法参与活动');
        }
        $surl = 'http://wallet.goodluckchina.net/app/scanCodePay/commonQueryStatus';
        $post_data = ["orderNo" => $orderCode, 'money' => $money];
        $curl_ret = \Yii::sendCurlPost($surl, $post_data);
        if ($curl_ret['code'] == 1) {//成功
            $ret = $this->userService->getDailResult($orderCode, '01', $openId);
            if ($ret['code'] == 109) {
                return $this->jsonError(109, $ret['msg']);
            }
            $ret['callback_url'] = \Yii::$app->params['userDomain'] . '/activity/download-app';
            $result = $ret['data'];
            return $this->jsonResult(600, '抽奖成功', $result);
        } elseif ($curl_ret['code'] == '-1') {
            return $this->jsonError(109, '该订单支付失败，无法参与活动');
        } else {
            return $this->jsonError(109, '该订单支付未完成，请稍后再试');
        }
    }

    public function actionIsActivity(){
        $request = Yii::$app->request;
        $openId = $request->post('open_id');
        $key = 'ActiveRecord' . date('Ymd');
        if (\Yii::$app->redis->SISMEMBER($key, $openId)) {
            return $this->jsonError(109, '每人一天仅有一次参与机会！您已参与过了！！');
        }else{

            return $this->jsonResult(600, 'sucess',true);
        }
    }


}
