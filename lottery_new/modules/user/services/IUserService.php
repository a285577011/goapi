<?php

namespace app\modules\user\services;

use app\modules\user\models\CouponsDetail;
use app\modules\user\models\UserToken;
use app\modules\user\models\User;
use app\modules\user\models\UserFollow;
use yii\base\Exception;
use app\modules\common\models\UserFunds;
use app\modules\common\helpers\Constants;
use app\modules\common\models\Store;
use app\modules\user\helpers\UserTool;
use app\modules\common\services\KafkaService;
use app\modules\user\models\Coupons;
use app\modules\common\models\ActivityLucky;
use app\modules\common\models\ActivityReceiveRecode;
use app\modules\orders\helpers\OrderDeal;
use app\modules\common\services\OrderService;
use yii\db\Expression;

//use Yii;

interface IUserService {

    public function getJavaUser($userTel, $password);

    public function javaRegister($userTel, $password);

    public function createOrUpdateUser($userTel, $custNo, $javaUserDetail, $regType = 1, $invitCode = '', $agentCode = '');

    public function register($userTel, $password, $custNo, $regFrom);

    public function saveUserToken($userId, $token, $expire = 7200);

    public function javaUpdatePwd($userTel, $password);

    public function getJavaUserDetail($custNo);

    public function recieiveDail($openId,$custNo, $orderCode, $activeCode);

    /**
     * 说明: 自动登录
     * @author  kevi
     * @date 2017年8月17日 上午11:46:15
     * @param   not null    $custNo
     * @param   not null    $userId 用户id
     * @param   not null    $objStr 'user'或者'store'
     * @return
     */
    public function autoLogin($custNo, $userId, $objStr = "user");

    /**
     * 说明: 门店后台自动登录
     * @param type $custNo   门店custNo
     * @param type $userId   门店用户id
     * @param type $storeOperatorId   门店操作员id
     * @param type $objStr
     */
    public function autoStoreLogin($custNo, $userId, $storeOperatorId = 0, $objStr = 'storeback');

    /**
     * 说明: 根据手机号获取java用户信息
     * @author  kevi
     * @date 2017年8月10日 上午9:25:02
     * @param  not null $userTel 手机号
     * @return Array
     */
    public function getJavaUserDetailByTel($userTel);

    public function setRegisterData($JavaUser);

    public function setStatus($custNo, $storeNo, $status, $type);

    /**
     * 获取省份
     * @auther GL zyl
     * @param $custNo cust_no
     */
    public function getProvince($custNo);

    /**
     * 获取城市
     * @auther GL zyl
     * @param type $custNo cust_no
     * @param type $provinceId  省份id
     */
    public function getCity($custNo, $provinceId);

    /**
     * 获取区县
     * @auther GL zyl
     * @param type $custNo cust_no
     * @param type $cityId 城市ID
     */
    public function getArea($custNo, $cityId);

    /**
     * 获取开户行
     * @auther GL zyl
     * @param type $custNo cust_no
     */
    public function getBank($custNo);

    /**
     * 获取开户行支行
     * @auther GL zyl
     * @param type $custNo cust_no
     * @param type $bankClsCode 开户行编号
     * @param type $cityCode 城市编号
     */
    public function getBankInfo($custNo, $bankClsCode, $cityCode);

    /**
     * 实名认证第一步
     * @auther GL zyl
     * @param type $custNo cust_no
     * @param type $realName 实名
     * @param type $cardNo 身份证号
     * @param type $cardFront 正面链接
     * @param type $cardBack 背面链接
     * @param type $cardWith 手持身份证
     */
    public function javaRealNameAuthOne($custNo, $realName, $cardNo, $cardFront, $cardBack, $cardWith, $bankCardImg);

    /**
     * 实名认证第二步
     * @auther GL zyl
     * @param type $custNo cust_no
     * @param type $depositBank 开户行
     * @param type $bankNo 银行卡号
     * @param type $depositProvince 开户省
     * @param type $depositCity 开户市
     * @param type $bankOutlets 开户区县
     * @param type $reservedPhone 预留电话
     * @param type $sBankCode 超级网银号
     * @param type $bankCode 银行编号 大小额
     * @param type $bankname 银行名称
     */
    public function javaRealNameAuthTwo($custNo, $depositBank, $bankNo, $depositProvince, $depositCity, $bankOutlets, $reservedPhone, $sBankCode, $bankCode, $bankname);

    /**
     * 实名认证第三步
     * @auther GL zyl
     * @param type $custNo cust_no
     * @param type $depositBank 开户行
     * @param type $bankNo 银行卡号
     * @param type $depositProvince  开户省
     * @param type $depositCity 开户市
     * @param type $bankOutlets 开户区县
     * @param type $reservedPhone  预留电话
     * @param type $sBankCode 超级网银号
     * @param type $bankCode 银行编号 大小额
     * @param type $bankname 银行名称
     * @param type $verifyCode 验证码
     * @param type $jzb_regist 见证宝是否开户
     */
    public function javaRealNameAuthThree($custNo, $depositBank, $bankNo, $depositProvince, $depositCity, $bankOutlets, $reservedPhone, $sBankCode, $bankCode, $bankname, $verifyCode, $jzb_regist);

    /**
     * 解绑银行卡
     * @auther GL zyl
     * @param type $custNo cust_no
     */
    public function javaUnbindBankCard($custNo);

    /**
     * 获取银行验证码
     * @auther GL zyl
     * @param type $custNo cust_no
     * @param type $depositBank 开户行
     * @param type $bankNo 银行卡号
     * @param type $depositProvince 开户省
     * @param type $depositCity 开户市
     * @param type $bankOutlets 开户区县
     * @param type $reservedPhone 预留电话
     * @param type $sBankCode 超级网银号
     * @param type $bankCode 银行编号 大小额
     * @param type $bankname 银行名称
     */
    public function javaSmsCodeForBoundBankCard($custNo, $depositBank, $bankNo, $depositProvince, $depositCity, $bankOutlets, $reservedPhone, $sBankCode, $bankCode, $bankname);

    /**
     * 重新绑定银行卡
     * @auther GL zyl
     * @param type $custNo cust_no
     * @param type $depositBank 开户行
     * @param type $bankNo 银行卡号
     * @param type $depositProvince  开户省
     * @param type $depositCity 开户市
     * @param type $bankOutlets 开户区县
     * @param type $reservedPhone  预留电话
     * @param type $sBankCode 超级网银号
     * @param type $bankCode 银行编号 大小额
     * @param type $bankname 银行名称
     * @param type $verifyCode 验证码
     */
    public function javaBindBankCard($custNo, $depositBank, $bankNo, $depositProvince, $depositCity, $bankOutlets, $reservedPhone, $sBankCode, $bankCode, $bankname, $verifyCode);

    /**
     * 获取实名认证信息
     * @auther GL zyl
     * @param type $custNo cust_no
     */
    public function javaGetAuthInfo($custNo);

    /**
     * 上传图片
     * @auther GL zyl
     * @param type $imgBase64 base64图片
     */
    public function javaUploadImg($imgBase64);

    /**
     * 获取收款账户信息
     * @auther GL zyl
     * @param type $custNo
     */
    public function javaGetAccountDetail($custNo);

    /**
     * 获取收款账户信息
     * @auther GL zyl
     * @param type $custNo
     */
    public function javaGetRealName($custNo);

    /**
     * 注册默认关注公司旗下门店
     * @param type $custNo
     */
    public function followCompanyStore($custNo);

    /**
     * 获取会员状态
     * @param type $custNo
     */
    public function javaGetStatus($custNo);

    /**
     * 绑定下级商户
     * @param type $bindCustNo 要绑定的商户编号
     */
    public function javaAddMchCustFlushCache($bindCustNo);

    /**
     * 获取转盘结果
     * @param type $activeCode 活动编号
     * @param type $custNo 会员编号
     */
    public function getDailResult($orderCode, $activeCode, $openId);

    /**
     * 根据ip获取省份
     * @param type $ip
     */
    public function getProvinceByIp($ip, $userLand);
}

class UserService implements IUserService {

    public function getUsers() {
        return 'users';
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::saveUserToken()
     */
    public function saveUserToken($custNo, $token, $expire = 7200) {
        UserToken::deleteAll(['cust_no' => $custNo]);

        $userTokenModel = new UserToken();
        $userTokenModel->cust_no = $custNo;
        $userTokenModel->token = $token;
        $userTokenModel->expire_time = date('Y-m-d H:i:s', time() + 7200);
        $userTokenModel->create_time = date('Y-m-d H:i:s', time());
        if ($userTokenModel->save()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::register()
     */
    public function register($userTel, $password, $custNo, $regFrom) {
        $user = User::findOne(['user_tel' => $userTel]);

        if ($user) {
            return false;
        }
        $user = new User();
        $user->user_tel = $userTel;
        $user->user_name = $userTel;
        $user->password = md5('lottery_' . md5($password));
        $user->cust_no = $custNo;
        $user->invite_code = 'daiding';
        $user->register_from = $regFrom;
        $user->create_time = date('Y-m-d H:i:s');
        $user->modify_time = date('Y-m-d H:i:s');
        $db = \Yii::$app->db;
        $train = $db->beginTransaction();
        try { // 日志
            if (!$user->validate()) {
                throw new Exception('数据验证失败');
            }
            if (!$user->save()) {
                throw new Exception('数据保存失败');
            }
            $funds = UserFunds::find()->where(['cust_no' => $custNo])->one();
            if (empty($funds)) {
                $funds = new UserFunds;
                $funds->cust_no = $custNo;
                $funds->create_time = date('Y-m-d H:i:s');
                if (!$funds->validate()) {
                    throw new Exception('资金表数据验证失败');
                }
                if (!$funds->save()) {
                    throw new Exception('资金表写入失败');
                }
            }
            $train->commit();
            return ['code' => 600, 'data' => $user->attributes];
        } catch (Exception $ex) {
            $train->rollBack();
            return ['code' => 109, 'msg' => '注册失败'];
        }
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::createOrUpdateUser()
     */
    public function createOrUpdateUser($userTel, $custNo, $javaUserDetail, $regType = 1, $inviteCode = '', $agentCode = '') {
        $user = User::findOne(['user_tel' => $userTel, 'cust_no' => $custNo]);
        $isNew = 0;
        if (empty($user)) {
            $isNew = 1;
            $user = new User();
            $user->user_tel = $userTel;
            $user->cust_no = $custNo;
            $user->user_name = $userTel;
            $user->user_type = 1;
            $user->invite_code = 'daiding';
            if (empty($inviteCode)) {
                $pNo = \Yii::$app->request->post('pNo');

                if (!empty($javaUserDetail)) {
                    foreach ($javaUserDetail as $k => $v) {
                        $user->$k = $v;
                        if ($k == 'agent_code') {
                            $pNo = $v;
                        }
                    }
                }
                if (!$pNo) {
                    $pNo = UserTool::ADMIN_NO;
                }
                $pTree = UserTool::getUserTree($pNo, $custNo);
            } else {
                $pData = User::find()->select(['user_id', 'cust_no', 'p_tree'])->where(['invite_code' => $inviteCode])->andWhere(['>', 'spread_type', 0])->asArray()->one();
                if (empty($pData)) {
                    return ['code' => 109, 'msg' => '推广邀请失败，注册失败！'];
                }
                $pNo = $pData['cust_no'];
                $pTree = $pData['p_tree'] . '-' . $custNo;
                $user->register_from = User::REG_FTOM_TG;
                $user->from_id = $pData['user_id'];
            }
            if (!empty($agentCode) && $agentCode != "GL") {
                $user->from_id = $agentCode;
                $user->register_from = $regType;
            }
            $user->p_tree = $pTree;
            $user->agent_code = $pNo;
//             $user->register_from = $javaUserDetail['register_from'];
//             $user->province = $javaUserDetail['province'];
//             $user->city = $javaUserDetail['city'];
//             $user->area = $javaUserDetail['country'];
//             $user->address = $javaUserDetail['address'];
//             $user->authen_status = $javaUserDetail['check_status'];
//             $user->user_pic = $javaUserDetail['user_pic'];
//             $user->user_name = $javaUserDetail['user_name'];

            $user->create_time = date('Y-m-d H:i:s');
            $user->modify_time = date('Y-m-d H:i:s');
        }
        $user->authen_status = $javaUserDetail['authen_status'];
        $user->last_login = date('Y-m-d H:i:s');
        $db = \Yii::$app->db;
        $train = $db->beginTransaction();
        try { // 日志
            if (!$user->validate()) {
                throw new Exception('数据验证失败');
            }
            if (!$user->saveData()) {
                throw new Exception('数据保存失败');
            }
            $funds = UserFunds::find()->where(['cust_no' => $custNo])->one();
            if (empty($funds)) {
                $funds = new UserFunds;
                $funds->user_id = $user->user_id;
                $funds->cust_no = $custNo;
                $funds->create_time = date('Y-m-d H:i:s');
                if (!$funds->validate()) {
                    throw new Exception('资金表数据验证失败');
                }
                if (!$funds->save()) {
                    throw new Exception('资金表写入失败');
                }
            }
            $train->commit();
//            if ($isNew && $javaUserDetail['register_from'] != 3 && $regType != 2) {//如果是新注册的用户，则加入到redis队列中，默认关注咕啦旗舰店
//                KafkaService::addQue('NewuserFollow', ['custNo' => $custNo]);
//            }
            //注册成功赠送用户优惠券(只有推广平台注册的才有赠送):代理商的活动配置
            if (!empty($agentCode)) {
                CouponsDetail::activitySendCoupons($agentCode, 1, $custNo);
            }
            return ['code' => 600, 'data' => $user->attributes];
        } catch (Exception $ex) {
            $train->rollBack();
            return ['code' => 109, 'msg' => $ex->getMessage()];
        }
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::getJavaUser()
     */
    public function getJavaUser($userTel, $password) {
        $surl = \Yii::$app->params['java_login'];
        $post_data = ["account" => $userTel, "password" => $password, 'checkType' => 1];
        $curl_ret = \Yii::sendCurlPost($surl, $post_data);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaRegister()
     */
    public function javaRegister($userTel, $password) {
        $surl = \Yii::$app->params['java_register'];
        $invitationCode = \Yii::$app->params['invitation_code'];
        $post_data = ["account" => $userTel, "password" => $password, 'inviteCode' => $invitationCode, 'addPlatform' => 1];
        $curl_ret = \Yii::sendCurlPost($surl, $post_data);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaUpdatePwd()
     */
    public function javaUpdatePwd($userTel, $password) {
        $surl = \Yii::$app->params['java_updatepwd'];
        $post_data = ["username" => $userTel, "password" => $password];
        $curl_ret = \Yii::sendCurlPost($surl, $post_data);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::getJavaUserDetail()
     */
    public function getJavaUserDetail($custNo) {
        $surl = \Yii::$app->params['java_userDetail'];
        $post_data = ["custNo" => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $post_data);
        if ($curl_ret['httpCode'] == 200) {
            $curl_ret['data']['account'] = $curl_ret['data']['custNo'];
        }
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::getJavaUser()
     */
    public function getJavaUserDetailByTel($userTel) {
        $surl = \Yii::$app->params['java_userDetail_tel'];
        $post_data = ["phone" => $userTel];
        $curl_ret = \Yii::sendCurlPost($surl, $post_data);
        if ($curl_ret['httpCode'] == 200) {
            $curl_ret['data']['account'] = $curl_ret['data']['custNo'];
        }
        return $curl_ret;
    }

    /**
     * 设置关注门店
     * @param string $custNo
     * @param string $storeNo
     * @param integer $status
     * @param integer $type
     * @return json
     */
    public function setStatus($custNo, $storeId, $status, $type) {
        $result = [];
        $follow = UserFollow::find()->where(['cust_no' => $custNo, 'store_id' => $storeId])->one();
        if (empty($follow)) {
            $result = ['code' => 401, 'msg' => '数据错误，请重新操作'];
            return $result;
        }
        if ($type == 1) {
            $follow->default_status = $status;
        } elseif ($type == 2) {
            $follow->follow_status = $status;
        }
        $follow->modify_time = date('Y-m-d H:i:s');
        if ($follow->save()) {
            $result = ['code' => 600, 'msg' => '操作成功'];
        } else {
            $result = ['code' => 109, 'msg' => '操作失败'];
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::getProvince()
     */
    public function getProvince($custNo) {
        $surl = \Yii::$app->params['java_getBankProvince'];
        $postData = ['custNo' => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::getCity()
     */
    public function getCity($custNo, $provinceId) {
        $surl = \Yii::$app->params['java_getBankCity'];
        $postData = ['custNo' => $custNo, 'provinceId' => $provinceId];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::getArea()
     */
    public function getArea($custNo, $cityId) {
        $surl = \Yii::$app->params['java_getBankArea'];
        $postData = ['custNo' => $custNo, 'cityId' => $cityId];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::getBank()
     */
    public function getBank($custNo) {
        $surl = \Yii::$app->params['java_getBank'];
        $postData = ['custNo' => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::getBankInfo()
     */
    public function getBankInfo($custNo, $bankClsCode, $cityCode) {
        $surl = \Yii::$app->params['java_getBankInfo'];
        $postData = ['custNo' => $custNo, 'bankclscode' => $bankClsCode, 'citycode' => $cityCode];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaRealNameAuthOne()
     */
    public function javaRealNameAuthOne($custNo, $realName, $cardNo, $cardFront, $cardBack, $cardWith, $bankCardImg) {
        $surl = \Yii::$app->params['java_realNameAuthenticationOne'];
        $postData = ['custNo' => $custNo, 'realName' => $realName, 'cardNo' => $cardNo, 'cardFrontImg' => $cardFront, 'cardBackImg' => $cardBack, 'cardWithPeopleImg' => $cardWith, 'bankCardImg' => $bankCardImg];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaRealNameAuthTwo()
     */
    public function javaRealNameAuthTwo($custNo, $depositBank, $bankNo, $depositProvince, $depositCity, $bankOutlets, $reservedPhone, $sBankCode, $bankCode, $bankname) {
        $surl = \Yii::$app->params['java_realNameAuthenticationTwo'];
        $postData = ['custNo' => $custNo, 'depositBank' => $depositBank, 'bankNo' => $bankNo, 'depositProvince' => $depositProvince, 'depositCity' => $depositCity, 'bankOutlets' => $bankOutlets, 'reservedPhone' => $reservedPhone, 'sBankCode' => $sBankCode, 'bankCode' => $bankCode, 'bankname' => $bankname];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaRealNameAuthThree()
     */
    public function javaRealNameAuthThree($custNo, $depositBank, $bankNo, $depositProvince, $depositCity, $bankOutlets, $reservedPhone, $sBankCode, $bankCode, $bankname, $verifyCode, $jzb_regist) {
        $surl = \Yii::$app->params['java_realNameAuthenticationThree'];
        $postData = ['custNo' => $custNo, 'depositBank' => $depositBank, 'bankNo' => $bankNo, 'depositProvince' => $depositProvince, 'depositCity' => $depositCity, 'bankOutlets' => $bankOutlets, 'reservedPhone' => $reservedPhone, 'sBankCode' => $sBankCode, 'bankCode' => $bankCode, 'bankname' => $bankname, 'verifyCode' => $verifyCode, 'jzb_regist' => $jzb_regist];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaUnbindBankCard()
     */
    public function javaUnbindBankCard($custNo) {
        $surl = \Yii::$app->params['java_unbindBankCard'];
        $postData = ['custNo' => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaSmsCodeForBoundBankCard()
     */
    public function javaSmsCodeForBoundBankCard($custNo, $depositBank, $bankNo, $depositProvince, $depositCity, $bankOutlets, $reservedPhone, $sBankCode, $bankCode, $bankname) {
        $surl = \Yii::$app->params['java_smsCodeForBoundBankCard'];
        $postData = ['custNo' => $custNo, 'depositBank' => $depositBank, 'bankNo' => $bankNo, 'depositProvince' => $depositProvince, 'depositCity' => $depositCity, 'bankOutlets' => $bankOutlets, 'reservedPhone' => $reservedPhone, 'sBankCode' => $sBankCode, 'bankCode' => $bankCode, 'bankname' => $bankname];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaBindBankCard()
     */
    public function javaBindBankCard($custNo, $depositBank, $bankNo, $depositProvince, $depositCity, $bankOutlets, $reservedPhone, $sBankCode, $bankCode, $bankname, $verifyCode) {
        $surl = \Yii::$app->params['java_bindBankCard'];
        $postData = ['custNo' => $custNo, 'depositBank' => $depositBank, 'bankNo' => $bankNo, 'depositProvince' => $depositProvince, 'depositCity' => $depositCity, 'outlets' => $bankOutlets, 'reservedPhone' => $reservedPhone, 'sBankCode' => $sBankCode, 'verifyCode' => $verifyCode, 'bankCode' => $bankCode, 'bankname' => $bankname];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaGetAuthInfo()
     */
    public function javaGetAuthInfo($custNo) {
        $surl = \Yii::$app->params['java_getAuthInfo'];
        $postData = ['custNo' => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaUploadImg()
     */
    public function javaUploadImg($imgBase64) {
        $surl = \Yii::$app->params['java_imgBase64'];
        $postData = ['myImage' => $imgBase64];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaGetAccountDetail()
     */
    public function javaGetAccountDetail($custNo) {
        $surl = \Yii::$app->params['java_getAccountDetail'];
        $postData = ['custNo' => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::autoLogin()
     */
    public function autoLogin($custNo, $userId, $objStr = 'user') {
        $token = $this->createToken($custNo); //生成token
        $oldToken = \Yii::tokenGet("{$objStr}_token:{$custNo}");
        \Yii::tokenDel("token_{$objStr}:{$oldToken}");
        \Yii::tokenSet("token_{$objStr}:{$token}", "{$custNo}|{$userId}"); //保存token
        \Yii::tokenSet("{$objStr}_token:{$custNo}", "{$token}"); //保存token
        return $token;
    }

    /**
     * 门店后台登录
     * @param type $custNo
     * @param type $userId
     * @param type $storeOperator
     * @param type $objStr
     * @return type
     */
    public function autoStoreLogin($custNo, $userId, $storeOperatorId = 0, $objStr = 'storeback') {
        $token = $this->createToken($custNo); //生成token
        $oldToken = \Yii::tokenGet("{$objStr}_token_{$storeOperatorId}:{$custNo}");
        \Yii::tokenDel("token_{$objStr}:{$oldToken}");
        \Yii::tokenSet("token_{$objStr}:{$token}", "{$custNo}|{$userId}|{$storeOperatorId}"); //保存token
        \Yii::tokenSet("{$objStr}_token_{$storeOperatorId}:{$custNo}", "{$token}"); //保存token
        return $token;
    }

    /**
     * 说明: 生成token方法
     * @author  kevi
     * @date 2017年5月27日 上午10:37:46
     * @param
     * @return
     */
    public function createToken($userId) {
        $salt = 'GL_token_php';
        $all = $userId . $salt . time();
        return md5($all);
    }

    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::javaGetRealName()
     */
    public function javaGetRealName($custNo) {
        $surl = \Yii::$app->params['java_getRealName'];
        $postData = ['custNo' => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    //注册时 封装java返回的用户数据
    public function setRegisterData($JavaUser) {
        $result = [];
        $result['province'] = isset($JavaUser['data']['province']) ? $JavaUser['data']['province'] : '';
        $result['city'] = isset($JavaUser['data']['city']) ? $JavaUser['data']['city'] : '';
        $result['area'] = isset($JavaUser['data']['country']) ? $JavaUser['data']['country'] : '';
        $result['address'] = isset($JavaUser['data']['address']) ? $JavaUser['data']['address'] : '';
        $result['user_pic'] = isset($JavaUser['data']['avatar']) ? $JavaUser['data']['avatar'] : '';
        $result['user_name'] = isset($JavaUser['data']['userName']) ? $JavaUser['data']['userName'] : '';
        $result['authen_status'] = isset($JavaUser['data']['checkStatus']) ? $JavaUser['data']['checkStatus'] : '';
//         $result['account'] = $JavaUser['data']['account'];
        return $result;
    }

    /**
     * 旗下门店默认关注
     * @param not null $custNo // 会员编号
     * @return boolean
     */
    public function followCompanyStore($custNo) {
        $userFollow = UserFollow::find()->select(['store_id'])->where(['cust_no' => $custNo]);
        $companyStores = Store::find()->select(['store.store_code', 'store.cust_no'])
                ->where(['store.cert_status' => 3, 'company_id' => 1, 'store.status' => 1])
                ->andWhere(['not in', 'store_code', $userFollow])
                ->asArray()
                ->all();
        $format = date('Y-m-d H:i:s');
        $data = [];
        foreach ($companyStores as $val) {
            $data[] = [$custNo, $val['cust_no'], $val['store_code'], $format];
        }
        $follows = \Yii::$app->db->createCommand()->batchInsert('user_follow', ['cust_no', 'store_no', 'store_id', 'create_time'], $data)->execute();
        if ($follows === false) {
            return false;
        }
        return true;
    }

    public function javaGetStatus($custNo) {
        $surl = \Yii::$app->params['java_getStatus'];
        $postData = ['custNo' => $custNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * 绑定下级商户
     * @param type $bindCustNo 要绑定的商户编号
     * @return type
     */
    public function javaAddMchCustFlushCache($bindCustNo) {
        $surl = \Yii::$app->params['band_subordinates'];
        $appId = \Yii::$app->params['withdraw_AppId'];
        $custNo = \Yii::$app->params['withdraw_custNo'];
        $postData = ['appId' => $appId, 'custNo' => $custNo, 'bindCustNo' => $bindCustNo];
        $curl_ret = \Yii::sendCurlPost($surl, $postData);
        return $curl_ret;
    }

    /**
     * 根据IP获取对应省份
     * @param type $ip
     * @return string
     */
    public function getProvinceByIp($ip = '', $province = '') {
        if (!empty($ip)) {
            $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
            $addresData = file_get_contents($url);
            $ipData = json_decode($addresData, true);
            if ($ipData['code'] != 0) {
                $provice = '江苏';
                return $provice;
            }
            $province = $ipData['data']['region'];
        }
        $isExist = Store::find()->select(['store_code'])->where(['status' => 1, 'company_id' => 1])->andWhere(['like', 'province', $province])->count();
        if ($isExist == 0) {
            $province = '江苏';
        }
        return $province;
    }

    /**
     * 获取转盘结果
     * @param type $activeCode 活动编号
     * @param type $custNo 会员编号
     * @return type
     */
    public function getDailResult($orderCode,$activeCode, $openId) {
        if(YII_ENV_PROD){//正式环境-限制用户每日一次机会
            $key = 'ActiveRecord' . date('Ymd');
            if (\Yii::$app->redis->SISMEMBER($key, $openId)) {
                return ['code' => 109, 'msg' => '每人一天仅有一次参与机会！您已参与过了！！'];
            }
        }
        $isExist = ActivityReceiveRecode::find()->where(['qb_order_code'=>$orderCode])->one();
        if($isExist){
            return ['code' => 109, 'msg' => '该订单已经使用过抽奖机会。'];
        }
        $activeData = ActivityLucky::find()
            ->select(['active_code', 'content_code', 'content_name', 'weight', 'prize_code', 'prize_nums', 'all_prize_nums'])
            ->where(['active_code' => $activeCode, 'status' => 1])
            ->andWhere(['>','all_prize_nums',0])->asArray()->all();
        $retData = [
            'active_code'=>'',
            'content_code'=>'',
            'content_name'=>'',
        ];
        if (empty($activeData)) {
            $retData['active_code'] = $activeCode;
            $retData['content_code'] = 0;
            $retData['content_name'] = '谢谢参与!';
            return ['code' => 600, 'msg' => '获取成功', 'data' => $retData];
        }
        $weight = 0;
        foreach ($activeData as $one) {
            $oneWeight = (int) $one['weight'];
            $weight += $oneWeight;
            for ($i = 0; $i < $oneWeight; $i ++) {
                $subData[] = $one;
            }
        }
        $data = $subData[rand(0, $weight - 1)];

        $update=['all_prize_nums'=>new Expression('all_prize_nums-1'),'modify_time'=>date('Y-m-d H:i:s')];
        $a = ActivityLucky::updateAll($update,['and', ['active_code' => $activeCode, 'content_code' => $data['content_code']], ['>','all_prize_nums', 0]]);
        if($a != 1){
            $retData['active_code'] = $activeCode;
            $retData['content_code'] = 0;
            $retData['content_name'] = '谢谢参与!';
            return ['code' => 600, 'msg' => '获取成功', 'data' => $retData];
        }


        $receiveRecode = new ActivityReceiveRecode();
        $receiveRecode->active_code = $activeCode;
        $receiveRecode->qb_order_code = $orderCode;
        $receiveRecode->open_id = $openId;
        $receiveRecode->content_code = $data['content_code'];
        $receiveRecode->content_name = $data['content_name'];
        $receiveRecode->create_time = date('Y-m-d H:i:s');
        $receiveRecode->save();
        \Yii::$app->redis->sadd($key, $openId);
        $retData['active_code'] = $activeCode;
        $retData['content_code'] = $data['content_code'];
        $retData['content_name'] = $data['content_name'];
        return ['code' => 600, 'msg' => '获取成功', 'data' => $retData];

    }

    public function recieiveDail($openId,$custNo, $orderCode, $activeCode) {
        $receiveData = ActivityReceiveRecode::findOne(['qb_order_code' => $orderCode, 'active_code' => $activeCode,'open_id'=>$openId]);
        if(empty($receiveData)) {
            return ['code' => 109, 'msg' => '该次抽奖领取失败，信息异常！'];
        }
        if(!empty($receiveData->cust_no)) {
            return ['code' => 109, 'msg' => '该订单已经使用过抽奖机会！'];
        }
        $data = ActivityLucky::find()->select(['prize_code', 'prize_nums'])->where(['active_code' => $activeCode, 'content_code' => $receiveData->content_code])->asArray()->one();
//        switch ($receiveData->content_code) {
//            case '1':
//            case '2':
//                $store = OrderDeal::getOutStore($data['prize_code'], $data['prize_nums'], 1);
//                OrderService::giftLottery($data['prize_code'], $data['prize_nums'] * 2, $store['data']['store_id'], $store['data']['store_no'], $custNo, '');
//                break;
//            case '3':
//            case '4':
//            case '5':
//            case '6':
//                UserTool::regSendCoupons($data['prize_code'], [$custNo]);
//                break;
//        }
        $receiveData->cust_no = $custNo;
        $receiveData->modify_time = date('Y-m-d H:i:s');
        $receiveData->save();
        return ['code' => 600, 'msg' => '领取成功'];
    }

}
