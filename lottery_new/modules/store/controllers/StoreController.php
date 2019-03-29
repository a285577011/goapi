<?php

namespace app\modules\store\controllers;

use app\modules\tools\helpers\CallBackTool;
use app\modules\user\models\UserGlCoinRecord;
use Yii;
use yii\web\Controller;
use app\modules\common\models\Store;
use yii\db\Query;
use app\modules\store\helpers\Storefun;
use app\modules\user\services\IUserService;
use app\modules\common\services\OrderService;
use app\modules\common\models\LotteryOrder;
use app\modules\tools\helpers\Uploadfile;
use app\modules\common\models\PayRecord;
use app\modules\user\models\UserFollow;
use app\modules\common\services\PayService;
use app\modules\common\models\UserFunds;
use app\modules\common\helpers\Constants;
use app\modules\store\helpers\StoreConstants;
use app\modules\common\services\TogetherService;
use app\modules\tools\helpers\SmsTool;
use app\modules\store\services\StoreService;
use app\modules\user\models\User;
use app\modules\common\models\BettingDetail;
use app\modules\common\helpers\Winning;
use app\modules\common\models\OutOrderPic;
use app\modules\common\models\StoreOperator;
use app\modules\competing\services\OptionalService;
use app\modules\competing\services\BasketService;
use app\modules\competing\helpers\CompetConst;
use app\modules\store\models\StoreOptLog;
use app\modules\orders\helpers\DetailDeal;
use app\modules\user\models\UserGrowthRecord;
use app\modules\common\models\ProgrammeUser;
use app\modules\tools\helpers\Toolfun;
use app\modules\common\services\SyncService;
use app\modules\competing\services\FootballService;
use app\modules\competing\services\BdService;
use app\modules\common\helpers\OrderNews;
use app\modules\competing\services\WorldcupService;
use app\modules\common\services\KafkaService;

require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';

class StoreController extends Controller {

    private $userService;

    public function __construct($id, $module, $config = [], IUserService $userService) {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    /**
     * 门店注册
     * @auther GL zyl
     * @return type
     */
    public function actionRegister() {
        $request = Yii::$app->request;
        $storeTel = $request->post('account', '');
        $smsCode = $request->post('smsCode', '');
        $pwd = $request->post('password', '');
        if ($storeTel == '' || $smsCode == '' || $pwd == '') {
            return $this->jsonError(100, '参数有误');
        }
        $saveKey = Constants::SMS_KEY_REGISTER;
        SmsTool::check_code($saveKey, $storeTel, $smsCode);
        $javaUser = $this->userService->javaRegister($storeTel, $pwd);
        if ($javaUser == 401) {
            //java接口请求失败
            return $this->jsonError(401, '注册失败,请稍后重试');
        }

        if ($javaUser['httpCode'] == 200) {
            return $this->jsonResult(600, '注册成功', true);
        }
        return $this->jsonError(414, '注册失败,该手机号已经注册');
    }

    /**
     * 门店登录
     * @auther GL zyl
     * @return type
     */
    public function actionLogin() {
        $request = Yii::$app->request;
        $storeTel = $request->post('account', '');
        $pwd = $request->post('password', '');
        if ($storeTel == '' || $pwd == '') {
            return $this->jsonResult(100, '参数缺失', '');
        }
        $JavaUser = $this->userService->getJavaUser($storeTel, $pwd); //java认证接口
        if (empty($JavaUser)) {//java接口请求失败
            return $this->jsonError(401, '登录失败,请稍后重试');
        }
        if ($JavaUser['httpCode'] != 1) {
            return $this->jsonError($JavaUser['httpCode'], $JavaUser['msg']);
        } else {//java接口认证成功--生成或者更新系统用户数据
            $storeDetail = $this->userService->getJavaUserDetail($JavaUser['custNo']); //获取java用户信息
            $storeService = new StoreService();
            $store = $storeService->createOrUpdateUser($storeTel, $JavaUser['custNo'], $storeDetail);
        }
        $token = $this->createToken($store['cust_no']); //生成token
        $oldToken = \Yii::redisGet("store_token:{$store['cust_no']}");
        \Yii::redisDel("token_store:{$oldToken}");
        \Yii::redisSet("token_store:{$token}", "{$store['cust_no']}|{$store['store_id']}"); //保存token
        \Yii::redisSet("store_token:{$store['cust_no']}", "{$token}"); //保存token
        return $this->jsonResult(600, '登录成功', ['token' => $token]);
    }

    /**
     * 获取短信验证码
     * @auther GL zyl
     * @return type
     */
    public function actionGetVerCode() {
        $request = Yii::$app->request;
        $userTel = $request->post('account', '');
        $cType = $request->post('cType', '');
        if ($userTel == '' || $cType == '') {
            return $this->jsonError(100, '参数缺失');
        }
        if ($cType == 1) {
            $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
            if ($javaUser['httpCode'] == 200) {
                return $this->jsonError(411, '该号码已注册');
            }
            $saveKey = Constants::SMS_KEY_REGISTER;
        } else {
            $saveKey = Constants::SMS_KEY_UPPWD;
        }
        $smstool = new SmsTool();
        $ret = $smstool->sendSmsCode($cType, $saveKey, $userTel);
        if ($ret) {
            return $this->jsonResult(600, '发送成功', true);
        }
    }

    /**
     * 密码忘记或修改密码
     * @auther GL zyl
     * @return type
     */
    public function actionModifyPwd() {
        $request = \Yii::$app->request;
        $storeTel = $request->post('account', '');
        $verCode = $request->post('smsCode', '');
        $pwd = $request->post('password', '');
        if (empty($storeTel) || empty($pwd) || empty($verCode)) {
            return $this->jsonError(100, '参数缺失');
        }
        $saveKey = Constants::SMS_KEY_UPPWD;
        SmsTool::check_code($saveKey, $storeTel, $verCode);
        $result = $this->userService->javaUpdatePwd($storeTel, $pwd, $verCode); //java认证接口
        if ($result['httpCode'] == 200) {
            return $this->jsonResult(600, '密码修改成功', true);
        } else {
            return $this->jsonError(401, '密码修改失败');
        }
    }

    /**
     * 门店基础信息
     * @auther GL zyl
     * @return type
     */
    public function actionBasicInfo() {
//        $custNo = $this->custNo;
//        $custNo = 'gl00004278';
        $storeCode = $this->storeCode;
        $certStatus = StoreConstants::CERT_STATUS;
        $realStatus = StoreConstants::REAL_STATUS;
        $optId = $this->storeOperatorId;
        $storeId = $this->userId;
        $data = (new Query)->select(['store.store_code', 'store.cust_no', 'store.telephone as phone_num', 'store.store_name', 'store.province', 'store.city', 'store.area', 'store.address', 'store.coordinate', 'f.able_funds', 'f.all_funds', 'f.ice_funds', 'f.no_withdraw', 'store.cert_status', 'store.real_name_status', 'store.store_remark', 'store.open_time', 'store.close_time', 'store.store_img'])
                ->from('store')
                ->leftJoin('user_funds as f', 'f.cust_no = store.cust_no')
                ->where(['store.store_code' => $storeCode, "status" => 1])
//                ->where(['store.cust_no' => $custNo,"status"=>1])
                ->one();
        $data['withdraw_funds'] = sprintf("%.2f", $data['able_funds'] - $data['no_withdraw']);
        $data['cert_status_name'] = $certStatus[$data['cert_status']];
        $data['realName_status_name'] = $realStatus[$data['real_name_status']];
        if ($optId != 0) {
            $optUser = User::find()->select("user_name,user_type,is_operator")->where(["user_id" => $optId])->asArray()->one();
            $data['optUser'] = $optUser;
        } else {
            $optUser = User::find()->select("user_name,user_type,is_operator")->where(["user_id" => $storeId])->asArray()->one();
            $data['optUser'] = $optUser;
        }
//        $data['store_img'] = 'http://api' . $data['store_img'];
        return $this->jsonResult(600, '店面基础信息', $data);
    }

    /**
     * 门店余额
     * @auther GL zyl
     * @return type 
     */
    public function actionGetFunds() {
        $custNo = $this->custNo;
        $funds = UserFunds::find()->select(['all_funds', 'able_funds', 'ice_funds'])->where(['cust_no' => $custNo])->asArray()->one();
        return $this->jsonResult(600, '门店余额', $funds);
    }

    /**
     * 门店余额明细账单
     * @auther GL zyl
     * @return type
     */
    public function actionGetTransDetail() {
        $custNo = $this->custNo;
        $request = Yii::$app->request;
        $pType = $request->post('pType', '');
        $page = $request->post('page', 1);
        $pageSize = $request->post('page_size', 15);
        $expenditureTypeArr = Constants::EXPENDITURE_TYPE;
        $where = [];
//        $where['cust_no'] = $custNo;
//        $where['pay_way'] = 3;
        if (isset($pType) && !empty($pType)) {
            if ($pType == 1) {
                $where = ["or", ["not in", "pay_type", $expenditureTypeArr], ["status" => 3]];
            } else {
                $where = ["and", ["in", "pay_type", $expenditureTypeArr], ["status" => 1]];
            }
        }
        $allCount = PayRecord::find()->where($where)->andWhere(["cust_no" => $custNo])->andWhere(["in", "status", [1, 3]])->andWhere(['or', ["pay_way" => 3], ["in", "pay_type", [2, 3, 4]]])->count();
        $pageCount = ceil($allCount / $pageSize);
        $offset = ($page - 1) * $pageSize;
        $detail = PayRecord::find()->select(['body', 'balance', 'pay_money', 'pay_type_name', 'pay_pre_money', 'status', 'pay_type', 'create_time'])->where($where)->andWhere(["cust_no" => $custNo])->andWhere(["in", "status", [1, 3]])->andWhere(['or', ["pay_way" => 3], ["in", "pay_type", [2, 3, 4]]])->limit($pageSize)->offset($offset)->orderBy('modify_time desc,pay_record_id desc')->asArray()->all();
        foreach ($detail as &$val) {
            if ($val["status"] == 3) {
                $val["pay_type"] = "6";
            }
            if (in_array($val["pay_type"], $expenditureTypeArr) && $val["status"] == 1) {
                $val["pay_money"] = "-" . $val["pay_pre_money"];
            } else {
                $val["pay_money"] = "+" . $val["pay_pre_money"];
            }
        }
        $transList = ['page_num' => $page, 'records' => $detail, 'size' => $pageSize, 'pages' => $pageCount, 'total' => $allCount];
        return $this->jsonResult(600, '交易明细', $transList);
    }

    /**
     * 交易类型
     * auther GL ctx
     * @return json
     */
    public function actionGetPayRecordType() {
        $service = new PayService();
        $data = $service->getPayRecordType();
        return $this->jsonResult(600, "交易类型", $data);
    }

    /**
     * 门店账单
     * @return json
     */
    public function actionGetTrans() {
        $custNo = $this->custNo;
//        $custNo = 'gl00002100';
        $request = Yii::$app->request;
        $pType = $request->post('pay_type', '');
        $body = $request->post('body', '');
        $date = $request->post('month', '');
        $startDate = $request->post('start_date', '');
        $endDate = $request->post('end_date', '');
        $page = $request->post('page_num', 1);
        $pageSize = $request->post('page_size', 10);
        $url = Constants::PAY_TYPE_PIC_URL;
        $expenditureTypeArr = Constants::EXPENDITURE_TYPE;
        $query = (new Query())->select("body,pay_money,pay_pre_money,pay_type,pay_type_name,status,create_time")->from("pay_record")->where([
                    "cust_no" => $custNo
                ])->andWhere(["in", "status", [1, 3]]);

        if (isset($pType) && !empty($pType)) {
            $payTypes = explode("|", $pType);
            $query = $query->andWhere(["in", "pay_type", $payTypes]);
        }
        if (isset($body) && !empty($body)) {
            $query = $query->andWhere(["like", "body", "%" . $body . "%", false]);
        }
        if (isset($date) && !empty($date)) {
            $query = $query->andWhere(["like", "create_time", $date . "%", false]);
        }
        if (isset($startDate) && !empty($startDate)) {
            $query = $query->andWhere([">=", "create_time", $startDate . " 00:00:00"]);
        }
        if (isset($endDate) && !empty($endDate)) {
            $query = $query->andWhere(["<", "create_time", $endDate . " 23:59:59"]);
        }
        $month = Constants::MONTH;
        $total = $query->count();
        $infos = $query->orderBy("create_time desc")->offset(($page - 1) * $pageSize)->limit($pageSize)->all();
        foreach ($infos as &$val) {
            if ($val["status"] == 3) {
                $val["pay_type"] = "6";
            }
            if (in_array($val["pay_type"], $expenditureTypeArr) && $val["status"] == 1) {
                $val["pay_money"] = "-" . $val["pay_pre_money"];
            } else {
                $val["pay_money"] = "+" . $val["pay_pre_money"];
            }
        }
        $pageCount = ceil($total / $pageSize);
        $data = [];
        $old_time = "";
        $num = -1;
        if (!empty($infos)) {
            foreach ($infos as &$info) {
                $time = substr($info["create_time"], 5, 2);
                if ($old_time != $time) {
                    $old_time = $time;
                    $num++;
                    $data[$num] = [];
                    $data[$num]["month"] = $month[$time];
                    $data[$num]["list"] = [];
                }
                $info['pay_type_pic'] = $url . $info['pay_type'];
                $data[$num]["list"][] = $info;
            }
        }
        $transList = ['page_num' => $page, 'records' => $data, 'size' => $pageSize, 'pages' => $pageCount, 'total' => $total];
        return $this->jsonResult(600, '门店账单', $transList);
    }

    /**
     * app门店账单
     * @return type
     */
    public function actionGetAppTrans() {
        $custNo = $this->custNo;
//        $custNo = 'gl00002100';
        $request = Yii::$app->request;
        $pType = $request->post('pay_type', '');
        $body = $request->post('body', '');
        $date = $request->post('month', '');
        $startDate = $request->post('start_date', '');
        $endDate = $request->post('end_date', '');
        $page = $request->post('page_num', 1);
        $pageSize = $request->post('page_size', 10);
        $expenditureTypeArr = Constants::EXPENDITURE_TYPE;
        $url = Constants::PAY_TYPE_PIC_URL;
        $query = (new Query())->select("body,pay_money,pay_pre_money,pay_type,pay_type_name,status,create_time")->from("pay_record")->where([
                    "cust_no" => $custNo
                ])->andWhere(["in", "status", [1, 3]]);

        if (isset($pType) && !empty($pType)) {
            $payTypes = explode("|", $pType);
            $query = $query->andWhere(["in", "pay_type", $payTypes]);
        }
        if (isset($body) && !empty($body)) {
            $query = $query->andWhere(["like", "body", "%" . $body . "%", false]);
        }
        if (isset($date) && !empty($date)) {
            $query = $query->andWhere(["like", "create_time", $date . "%", false]);
        }
        if (isset($startDate) && !empty($startDate)) {
            $query = $query->andWhere([">=", "create_time", $startDate . " 00:00:00"]);
        }
        if (isset($endDate) && !empty($endDate)) {
            $query = $query->andWhere(["<", "create_time", $endDate . " 23:59:59"]);
        }
        $total = $query->count();
        $infos = $query->orderBy("create_time desc")->offset(($page - 1) * $pageSize)->limit($pageSize)->all();
        foreach ($infos as &$val) {
            if ($val["status"] == 3) {
                $val["pay_type"] = "6";
            }
            if (in_array($val["pay_type"], $expenditureTypeArr) && $val["status"] == 1) {
                $val["pay_money"] = "-" . $val["pay_pre_money"];
            } else {
                $val["pay_money"] = "+" . $val["pay_pre_money"];
            }
            $val['pay_type_pic'] = $url . $val['pay_type'];
        }
        $pageCount = ceil($total / $pageSize);
        $transList = ['page_num' => $page, 'records' => $infos, 'size' => $pageSize, 'pages' => $pageCount, 'total' => $total];
        return $this->jsonResult(600, '门店账单', $transList);
    }

    /**
     * 订单详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetOrderDetail() {
        \Yii::$app->db->enableSlaves = false; //强制查询主库
        $storeId = $this->userId;
        $storeCode = $this->storeCode;
        $request = Yii::$app->request;
        $orderCode = $request->post('order_code', '');
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bd = CompetConst::MADE_BD_LOTTERY;
        $wcArr = CompetConst::MADE_WCUP_LOTTERY;
        $majorArr = CompetConst::MAJOR_ARR;
        $freeChuan = CompetConst::NO_FREE_SCHE;
        $status = [
            "2" => "等待接单",
            "3" => "待开奖",
            "4" => "中奖",
            "5" => "未中奖",
            "6" => "出票失败",
            '9' => '过点撤销',
            '10' => '拒绝出票',
            '11' => '等待出票',
            '12' => '等待出票'
        ];
        $orderDet = (new Query)
                ->select(['o.lottery_name', 'o.lottery_id', 'o.periods', 'o.lottery_name', 'o.bet_val', 'o.bet_money', 'o.count', 'o.deal_status', 'o.status', 'o.source', 'o.bet_double', 'o.create_time', 'o.store_id',
                    'o.cust_no', 'o.cust_type', 'o.win_amount', 'o.play_name', 'o.play_code', 'o.lottery_order_code', 'o.lottery_order_id', 'o.is_bet_add', 'l.lottery_pic', 'r.lottery_time', 'r.lottery_numbers',
                    'r.limit_time', "o.award_time", "u.user_name as opt_name", "o.opt_id", 'u.user_tel as opt_tel', 'us.user_name as store_name', 'us.user_tel as store_tel', 'o.build_code', 'o.build_name', 'o.major_type',
                    "o.out_time", 's.company_id', 'o.zmf_award_money', 'o.deal_status'])
                ->from('lottery_order as o')
                ->innerJoin('lottery as l', 'l.lottery_code = o.lottery_id')
                ->leftJoin('lottery_record as r', 'r.periods = o.periods and r.lottery_code = o.lottery_id')
                ->leftJoin('store as s', 's.store_code = o.store_no and s.status = 1')
                ->leftJoin('user as u', 'u.user_id = o.opt_id')
                ->leftJoin('user as us', 'us.user_id = s.user_id')
                ->where(['o.lottery_order_code' => $orderCode])
                ->andWhere(['!=', 'o.status', 2])
                ->one();
        if ($orderDet == null || $orderDet == false) {
            return $this->jsonResult(40007, '查询结果不存在', '');
        }
//        if ($orderDet["cust_type"] == 1) {
        $user = \app\modules\user\models\User::findOne(["cust_no" => $orderDet["cust_no"]]);
        $orderDet["user_name"] = $user->user_name;
        $orderDet["user_tel"] = $user->user_tel;
//        } else {
//            $store = Store::findOne(["cust_no" => $orderDet["cust_no"]]);
//            $orderDet["user_name"] = $store->store_name;
//            $orderDet["user_tel"] = $store->phone_num;
//        }
        if ($orderDet["opt_id"] === "0") {
            $orderDet["opt_name"] = $orderDet["store_name"];
            $orderDet["opt_tel"] = $orderDet['store_tel'];
        }
        $scheCount = 0;
        if (!in_array($orderDet['lottery_id'], ['1001', '1002', '1003', '2001', '2002', '2003', '2004', '2005', '2006', '2007'])) {
            if (in_array($orderDet['lottery_id'], $football)) {
                $classCopeting = new FootballService();
                $ret = $classCopeting->getOrder($orderCode, $orderDet['cust_no']);
                if ($ret['code'] == 600) {
                    $orderDet['compet_detail'] = $ret['result']['contents'];
                }
                $playCodeArr = explode(',', $orderDet['play_code']);
                if (in_array(1, $playCodeArr)) {
                    if ($orderDet['lottery_id'] == 3011) {
                        $orderDet['lottery_name'] = '混合单关';
                    } else {
                        $orderDet['lottery_name'] .= '(单)';
                    }
                }
                $scheCount = count(explode("|", rtrim($orderDet['bet_val'], '^')));
            } elseif (in_array($orderDet['lottery_id'], $basketball)) {
                $classCopeting = new BasketService();
                $ret = $classCopeting->getOrder($orderCode, $orderDet['cust_no']);
                if ($ret['code'] == 600) {
                    $orderDet['compet_detail'] = $ret['result']['contents'];
                }
                $playCodeArr = explode(',', $orderDet['play_code']);
                if (in_array(1, $playCodeArr)) {
                    if ($orderDet['lottery_id'] == 3005) {
                        $orderDet['lottery_name'] = '混合单关';
                    } else {
                        $orderDet['lottery_name'] .= '(单)';
                    }
                }
                $scheCount = count(explode("|", rtrim($orderDet['bet_val'], '^')));
            } elseif (in_array($orderDet['lottery_id'], $bd)) {
                $bdService = new BdService();
                $ret = $bdService->getOrder($orderCode, $orderDet['cust_no']);
                if ($ret['code'] == 600) {
                    $orderDet['compet_detail'] = $ret['result']['contents'];
                }
            } elseif (in_array($orderDet['lottery_id'], $wcArr)) {
                $ret = WorldcupService::getOrder($orderCode, $orderDet['cust_no']);
                if ($ret['code'] == 600) {
                    $orderDet['compet_detail'] = $ret['result']['betval_arr'];
                }
            } else {
                $classCopeting = new OptionalService();
                $ret = $classCopeting->getOptionalOrder($orderCode, $orderDet['cust_no']);
                if ($ret['code'] == 600) {
                    $orderDet['optional_detail'] = $ret['result']['betval_arr'];
                }
            }
        }
        $allTicket = LotteryOrder::find()->select(['sum(bet_money) as money'])->where(['store_no' => $storeCode, 'cust_no' => $orderDet['cust_no']])->andWhere(['in', 'status', [3, 4, 5]])->asArray()->one();
        $forbided = UserFollow::find()->where(['cust_no' => $orderDet['cust_no'], 'follow_status' => 3])->count();
        $orderDet['forbided'] = $forbided;
        $orderDet['is_bet_add'] = $orderDet['is_bet_add'] == 0 ? '未追加' : '追加';
        $orderDet['status_name'] = $status[$orderDet['status']];
        $orderDet['allTicket'] = empty($allTicket['money']) ? 0 : $allTicket['money'];
        $orderDet['major_name'] = $majorArr[$orderDet['major_type']];
        $orderDet['limit_time'] = empty($orderDet['limit_time']) ? $orderDet['lottery_time'] : $orderDet['limit_time'];
        $orderDet['zmf_award_money'] = empty($orderDet['zmf_award_money']) ? '0.00' : $orderDet['zmf_award_money'];
        if (empty($orderDet['build_code'])) {
            $lotOrder['build_code'] = '';
            $lotOrder['build_name'] = '';
        }
        $freeType = '';
        if ($scheCount != 0) {
            if (empty($orderDet['build_code'])) {
                if (in_array($orderDet['play_code'], $freeChuan[$scheCount])) {
                    $freeType = '非自由过关';
                } else {
                    $freeType = '自由过关';
                }
            } else {
                if (in_array($orderDet['build_code'], $freeChuan[$scheCount])) {
                    $freeType = '非自由过关';
                } else {
                    $freeType = '自由过关';
                }
            }
        }
        $orderDet['free_type'] = $freeType;
        $orderDet['server_time'] = date('Y-m-d H:i:s');
        return $this->jsonResult(600, '订单详情', $orderDet);
    }

    /**
     * 订单出票
     * @auther GL ctx
     * @return json
     */
    public function actionOutTicket() {
        $request = \Yii::$app->request;
        $orderCode = $request->post('lottery_order_code', '');
        if (empty($orderCode)) {
            return $this->jsonError(109, '参数缺失');
        }
//        $storeId = $this->userId;
        $storeCode = $this->storeCode;
        $field = ['lottery_order.lottery_order_code', 'lottery_order.lottery_id', 'lottery_order.lottery_name', 'lottery_order.create_time', 'lottery_order.end_time', 'lottery_order.bet_money', 'u.user_id', 'lottery_order.bet_val',
            'lottery_order.source_id', 'lottery_order.source', 'lottery_order.periods', 't.third_uid', 'lr.lottery_time', 'lr.week', 's.store_name', 's.telephone as phone_num', 'u.cust_no', 'lottery_order.lottery_order_id'];
        $userData = LotteryOrder::find()->select($field)
                ->innerJoin('user as u', 'u.cust_no = lottery_order.cust_no')
                ->leftJoin('third_user t', 't.uid = u.user_id')
                ->leftJoin('lottery_record as lr', 'lr.lottery_code = lottery_order.lottery_id and lr.periods = lottery_order.periods')
                ->leftJoin('store as s', 's.store_code = lottery_order.store_no and s.status = 1')
                ->where(['lottery_order_code' => $orderCode])
                ->asArray()
                ->one();
        $orderImg = OutOrderPic::find()->select(['order_img1', 'order_img2', 'order_img3', 'order_img4'])->where(['order_id' => $userData['lottery_order_id'], 'user_id' => $userData['user_id']])->asArray()->one();
        if (empty($orderImg['order_img1']) && empty($orderImg['order_img2']) && empty($orderImg['order_img3']) && empty($orderImg['order_img4'])) {
            return $this->jsonError(109, '请先 上传票根照片');
        }
        $ret = OrderService::outOrder($orderCode, $storeCode);
        $footballs = Constants::MADE_FOOTBALL_LOTTERY;
        $basketballs = CompetConst::MADE_BASKETBALL_LOTTERY;
        $worldcupArr = CompetConst::MADE_WCUP_LOTTERY;
        $userOpenId = $userData['third_uid'];
        $betMoney = $userData['bet_money'];
        if ($ret['code'] != 1) {//出票失败
            SyncService::syncFromHttp();
            return $this->jsonResult(109, "出票失败：" . $ret['msg'], '');
        } else {
            if (in_array($userData['lottery_id'], $basketballs)) {
                $basketService = new BasketService();
                $basketService->updateOdds($userData['lottery_id'], $userData['lottery_order_id'], $userData['bet_val']);
                KafkaService::addQue('CreateDealOrder', ['orderId' => $userData['lottery_order_id']], true);
            } elseif (in_array($userData['lottery_id'], $footballs)) {
                $footService = new FootballService();
                $footService->updateOdds($userData['lottery_id'], $userData['lottery_order_id'], $userData['bet_val']);
                KafkaService::addQue('CreateDealOrder', ['orderId' => $userData['lottery_order_id']], true);
            } elseif (in_array($userData['lottery_id'], $worldcupArr)) {
                WorldcupService::updateOdds($userData['lottery_id'], $userData['lottery_order_id'], $userData['bet_val']);
            }
            if ($userOpenId) {
                OrderNews::userOutOrder($orderCode, 1);
            }
            SyncService::syncFromHttp();
            $sql = "update user_follow set ticket_count = ticket_count + 1, ticket_amount = ticket_amount + {$betMoney}, modify_time = '" . date('Y-m-d H:i:s') . "' where cust_no = '" . $userData['cust_no'] . "' and store_id = {$storeCode};";
            $db = \Yii::$app->db;
            $db->createCommand($sql)->execute();
            CallBackTool::addCallBack(1, ['lottery_order_code' => $orderCode]);
            /**
             * 出票成功赠送咕啦币和成长值，合买的情况下需要为每位下单用户加：
             * source：1自购、2追号、3赠送、4合买 5、分享 6、计划购买
             */
            if ($userData['source'] && $userData['source'] != 3 && $userData['source'] != 7) {

//                $UserGlCoin = new UserGlCoinRecord();
                $UserGrowthRecord = new UserGrowthRecord();
                //4合买，分别查出合买人再赠送咕啦币
                if ($userData['source'] == 4) {
                    $data1 = ProgrammeUser::find()->select('programme_user_code')->where(['programme_id' => $userData['source_id'], 'status' => 4])->asArray()->all();
                    $proUserCode = array_column($data1, 'programme_user_code');
                    $data = PayRecord::find()->select('pay_money,cust_no')->where(['order_code' => $proUserCode])->asArray()->all();
                    foreach ($data as $v) {
                        //赠送咕啦币
//                        $glCoin = [
//                            'type' => 1,
//                            'coin_value' => $v['pay_money'],
//                            'remark' => '合买赠送',
//                            'coin_source' => 1,
//                            'order_code' => $userData['lottery_order_code'],
//                            'order_source' => $userData['source']
//                        ];
//                        $UserGlCoin->updateGlCoin($v['cust_no'], $glCoin);
                        //赠送成长值
                        $growth = [
                            'type' => 1,
                            'growth_value' => $v['pay_money'],
                            'growth_remark' => '购彩赠送',
                            'growth_source' => 2,
                            'order_code' => $userData['lottery_order_code'],
                            'order_source' => $userData['source'],
                        ];
                        $UserGrowthRecord->updateGrowth($v['cust_no'], $growth);
                    }
                } else {
                    $data = PayRecord::find()->select('pay_money,cust_no')
                            ->where(['order_code' => $orderCode, 'status' => 1, 'cust_no' => $userData['cust_no']])
                            ->asArray()
                            ->one();
                    //赠送咕啦币
//                    $glCoin = [
//                        'type' => 1,
//                        'coin_value' => $data['pay_money'], //实际支付金额
//                        'remark' => '购彩赠送',
//                        'coin_source' => 1,
//                        'order_code' => $userData['lottery_order_code'],
//                        'order_source' => $userData['source'],
//                    ];
//                    $UserGlCoin->updateGlCoin($userData['cust_no'], $glCoin);
                    //赠送成长值
                    $growth = [
                        'type' => 1,
                        'growth_value' => $betMoney,
                        'growth_remark' => '购彩赠送',
                        'growth_source' => 2,
                        'order_code' => $userData['lottery_order_code'],
                        'order_source' => $userData['source'],
                    ];
                    $UserGrowthRecord->updateGrowth($userData['cust_no'], $growth);
                    //订单金额满100 1000赠送优惠券
                    OrderService::sendCouponsVerify($data['pay_money'],$data["cust_no"]);
                }
            }
            return $this->jsonResult(600, '出票成功', '');
        }
    }

    /**
     * 订单出票失败（拒绝出票）
     * @author GL ctx
     * @return json
     */
    public function actionOutTicketFalse() {
        $post = Yii::$app->request->post();
//        $this->custNo = "gl00004278";
        $orderCode = $post["lottery_order_code"];
        $refuse_reason = !empty($post['refuse_reason']) ? $post['refuse_reason'] : '';
        $storeId = $this->userId;
        $storeCode = $this->storeCode;
        $optId = $this->storeOperatorId;
        $lotOrder = LotteryOrder::findOne(["lottery_order_code" => $orderCode, "status" => 11, "deal_status" => 0, "store_no" => $storeCode]);
        BettingDetail::updateAll([
            "status" => 6
                ], ['lottery_order_id' => $lotOrder->lottery_order_id, "status" => 11]);
        $lotOrder->opt_id = (string) $optId;
        $lotOrder->status = 10;
        $lotOrder->out_time = date('Y-m-d H:i:s');
        $lotOrder->refuse_reason = $refuse_reason;
        $ret1 = $lotOrder->saveData();
        $field = ['lottery_order.lottery_order_id', 'lottery_order.lottery_order_code', 'lottery_order.lottery_id', 'lottery_order.lottery_name', 'lottery_order.create_time', 'lottery_order.end_time', 'lottery_order.bet_money', 'u.user_id',
            'lottery_order.periods', 't.third_uid', 'lr.lottery_time', 'lr.week', 's.store_name', 's.telephone as phone_num', 'u.cust_no'];
        $userData = LotteryOrder::find()->select($field)
                ->innerJoin('user as u', 'u.cust_no = lottery_order.cust_no')
                ->leftJoin('third_user t', 't.uid = u.user_id')
                ->leftJoin('lottery_record as lr', 'lr.lottery_code = lottery_order.lottery_id and lr.periods = lottery_order.periods')
                ->leftJoin('store as s', 's.store_code = lottery_order.store_no and s.status = 1')
                ->where(['lottery_order_code' => $orderCode])
                ->asArray()
                ->one();
        $userOpenId = $userData['third_uid'];
        if ($ret1 == false) {
            return $this->jsonResult(109, '设置失败', '');
        } else {
            $ret2 = OrderService::outOrderFalse($orderCode, 10, $storeCode);
            if ($ret2 == false) {
                return $this->jsonResult(109, '退款失败', '');
            }
            SyncService::syncFromHttp();
            if ($userOpenId) {
                OrderNews::userOutOrder($orderCode, 2);
            }
            CallBackTool::addCallBack(1, ['lottery_order_code' => $orderCode]);
            return $this->jsonResult(600, '成功拒绝', '');
        }
    }

    /**
     * 订单列表
     * @auther GL ctx
     * @return json
     */
    public function actionOrderList() {
        $post = Yii::$app->request->post();
//        $this->custNo = "gl00004357";
        $size = 10;
        $status = [
            '1' => '未发布',
            '2' => '等待接单',
            '3' => '待开奖',
            '4' => '中奖',
            '5' => '未中奖',
            '6' => '出票失败',
            '9' => '出票失败',
            '10' => '出票失败',
            '11' => '等待出票',
            '12' => '等待出票'
        ];
        $deal_status = [
            "0" => "未处理",
            "1" => "未兑奖",
            "2" => "兑奖失败",
            "3" => "已兑奖",
            "4" => "退款失败",
            "5" => "退款成功"
        ]; //兑奖处理 0:未处理 ；1：已兑奖 ；2：派奖失败； 3：派奖成功   4:退款失败   5：退款成功
        $source = [
            "1" => "自购",
            "2" => "追号",
            "3" => "赠送",
            "4" => "合买",
            "5" => '分享',
            "6" => "计划",
            "7" => "接口订单"
        ];
        if (isset($post["statusArr"])) {
            $statusArr = explode(",", $post["statusArr"]);
        } else {
            $statusArr = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        }
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $storeNo = $this->storeCode;
        $data = [];
        $where = ['and', ["lottery_order.store_no" => $storeNo, "suborder_status" => 1], ["in", "lottery_order.status", $statusArr]];
        $where2 = ['or', ["!=", "lottery_order.source", 7], ['lottery_order.source' => 7, 'lottery_order.auto_type' => 1]];
        $query = (new Query())->select(["user.user_name", "user.user_tel", "user.cust_no", "store.store_name", "lottery_order.create_time", "lottery_order.cust_type", "lottery_order.bet_double",
                    "lottery_record.lottery_time", "lottery_record.limit_time", "lottery_order.lottery_name", "lottery_order.status", "lottery_order.deal_status",
                    "lottery_order.count", "lottery_order.source", "lottery_order.bet_money", "lottery_order.play_name", "lottery_order.win_amount", "lottery_order.lottery_order_code",
                    "lottery_order.end_time", "lottery.lottery_code", "lottery.lottery_pic", "lottery_order.lottery_id", "lottery_order.out_time", "lottery_order.opt_id", "pr.pay_pre_money", "lottery_order.award_amount"
                    , "lottery_order.award_time", "lottery_order.remark"])
                ->from("lottery_order")
                ->join("left join", "lottery_record", "lottery_record.periods=lottery_order.periods and lottery_record.lottery_code=lottery_order.lottery_id")
                ->join("left join", "user", "user.cust_no=lottery_order.cust_no")
                ->join("left join", "store", "store.user_id=lottery_order.store_id and store.status = 1")
                ->join("left join", "lottery", "lottery.lottery_code=lottery_order.lottery_id")
                ->leftJoin("pay_record as pr", "pr.order_code=lottery_order.lottery_order_code and pr.pay_type=16")
                ->where($where)
                ->andWhere($where2);
        if (isset($post["lottery_code"]) && !empty($post["lottery_code"])) {
            if ($post['lottery_code'] == 3000) {
                $codeWhere = ['in', 'lottery_order.lottery_id', $football];
            } elseif ($post['lottery_code'] == 3100) {
                $codeWhere = ['in', 'lottery_order.lottery_id', $basketball];
            } else {
                $codeWhere['lottery_order.lottery_id'] = $post['lottery_code'];
            }
            $query = $query->andWhere($codeWhere);
        }
        if (isset($post["lottery_order_code"]) && !empty($post["lottery_order_code"])) {
            $query = $query->andWhere(["lottery_order.lottery_order_code" => $post["lottery_order_code"]]);
        }
        if (isset($post["status"]) && !empty($post["status"]) && isset($status[$post["status"]])) {
            if ($post["status"] == 6) {
                $andwhere = ["in", "lottery_order.status", [6, 9, 10]];
            } elseif ($post['status'] == 2) {
                $andwhere = ["in", "lottery_order.status", [2, 11]];
            }else {
                $andwhere["lottery_order.status"] = $post["status"];
            }
            $query = $query->andWhere($andwhere);
        }
        if (isset($post["user_info"]) && !empty($post["user_info"])) {
            $queryUser = (new Query())->select("cust_no")
                    ->from("user")
                    ->where(["user_name" => $post["user_info"]])
                    ->orWhere(["user_tel" => $post["user_info"]])
                    ->orWhere(["cust_no" => $post["user_info"]]);
//            $queryStore = (new Query())->select("cust_no")
//                    ->from("store")
//                    ->where(["store_name" => $post["user_info"]])
//                    ->orWhere(["phone_num" => $post["user_info"]])
//                    ->orWhere(["cust_no" => $post["user_info"]]);
//            $queryDouble = $queryUser->union($queryStore);
            $query = $query->andWhere(["in", "lottery_order.cust_no", $queryUser]);
        }
        if (isset($post["month"]) && !empty($post["month"])) {
            $query = $query->andWhere([">=", "lottery_order.create_time", $post["month"] . "-01 00:00:00"]);
            $query = $query->andWhere(["<", "lottery_order.create_time", date("Y-m-d H:i:s", strtotime($post["month"] . "-01 00:00:00 +1 month"))]);
        }

        if (isset($post["deal_status"]) && !empty($post["deal_status"])) {
            $dealStatus = explode(",", $post["deal_status"]);
            $query = $query->andWhere(["in", "lottery_order.deal_status", $dealStatus]);
        }
        if (isset($post["start_date"]) && !empty($post["start_date"])) {
            $query = $query->andWhere([">=", "lottery_order.create_time", $post["start_date"] . " 00:00:00"]);
        }
        if (isset($post["end_date"]) && !empty($post["end_date"])) {
            $query = $query->andWhere(["<", "lottery_order.create_time", $post["end_date"] . " 23:59:59"]);
        }

        if (isset($post["start_end_time"]) && !empty($post["start_end_time"])) {
            $query = $query->andWhere([">=", "lottery_order.end_time", $post["start_end_time"] . " 00:00:00"]);
        }
        if (isset($post["end_end_time"]) && !empty($post["end_end_time"])) {
            $query = $query->andWhere(["<", "lottery_order.end_time", $post["end_end_time"] . " 23:59:59"]);
        }
        if (isset($post["page"])) {
            $page = $post["page"];
        } else {
            $page = 1;
        }
        $offset = $size * ($page - 1);
        $data["total"] = (int) $query->count();
        $data["page"] = $page;
        $data["pages"] = ceil($data["total"] / $size);
        $orderStrs = [];
        if ((isset($post["create_time"]) && !empty($post["create_time"]))) {
            if ($post["create_time"] == "up") {
                $orderStrs[] = " lottery_order.create_time asc";
            } else {
                $orderStrs[] = " lottery_order.create_time desc";
            }
        } elseif ((isset($post["end_time"]) && !empty($post["end_time"]))) {
            if ($post["end_time"] == "up") {
                $orderStrs[] = " lottery_order.end_time asc";
            } else {
                $orderStrs[] = " lottery_order.end_time desc";
            }
        } else {
            if (isset($post["time_type"]) && $post["time_type"] == "2") {
                $orderStrs[] = " lottery_order.end_time desc";
            } else {
                $orderStrs[] = " lottery_order.create_time desc";
            }
        }
        $orderStr = implode(",", $orderStrs);
        $data["list"] = $query->orderBy($orderStr)->offset($offset)->limit($size)->all();
        //查询订单操作人
        foreach ($data["list"] as $k => &$v) {
            if ($v["opt_id"] != "") {
                if ($v["opt_id"] == 0) {
                    $optInfo = (new Query())->select("user_id")
                            ->from("store")
                            ->where(["store_code" => $storeNo, "status" => 1])
                            ->one();
                } else {
                    $optInfo["user_id"] = $v["opt_id"];
                }
                $optName = (new Query())->select("user_name,user_tel")
                        ->from("user")
                        ->where(["user_id" => $optInfo["user_id"]])
                        ->one();
                $v["optName"] = $optName["user_name"] . "(" . $optName["user_tel"] . ")";
            }
        }
        $data["size"] = count($data["list"]);
        foreach ($data["list"] as &$val) {
            if ($val["cust_type"] != 1) {
                $val["user_name"] = $val["store_name"];
            }
            unset($val["store_name"]);
            if (in_array($val['lottery_id'], $football)) {
                $val['lottery_name'] = '竞彩足球';
            }
            if (in_array($val['lottery_id'], $basketball)) {
                $val['lottery_name'] = '竞彩篮球';
            }
            $val["status_name"] = $status[$val["status"]];
            $val["source_name"] = $source[$val["source"]];
            $val["deal_name"] = $deal_status[$val["deal_status"]];
        }
        //后台彩店票务-订单状态
        if (isset($post["statusArr"])) {
            $data["ticketSta"] = $post["statusArr"];
        }
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取彩种
     * @auther GL ctx
     * @return json
     */
    public function actionLotteryCategory() {
        $data = (new Query())->select("lottery_code,lottery_name")
                ->from("lottery")
                ->all();
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bd = CompetConst::MADE_BD_LOTTERY;
        $lottery = [];
        foreach ($data as $key => &$val) {
            if (in_array($val['lottery_code'], $football)) {
                unset($data[$key]);
            }
            if (in_array($val['lottery_code'], $basketball)) {
                unset($data[$key]);
            }
            if (in_array($val['lottery_code'], $bd)) {
                unset($data[$key]);
            }
        }
        $lottery = array_values($data);
        return $this->jsonResult(600, "获取成功", $lottery);
    }

    /**
     * 门店拉黑用户
     * @auther GL zyl
     * @return type
     */
    public function actionSetForbid() {
        $custNo = $this->custNo;
        $request = Yii::$app->request;
        $userNo = $request->post('user_no', '');
        if ($userNo == '') {
            return $this->jsonError(100, '参数错误');
        }
        $result = $this->userService->setStatus($userNo, $custNo, 3, 2);
        return $this->jsonError($result['code'], $result['msg']);
    }

    /**
     * 充值
     * auther GL ctx
     * @return json
     */
    public function actionRecharge() {
        $get = \Yii::$app->request->get();
        $betMoney = 0.01;
        $payPreMoney = $get["recharge_money"];
        $service = new PayService();
        $service->way_type = $get["way_type"];
        $service->pay_way = $get["pay_way"];
        $service->cust_no = $this->custNo;
        $service->payPreMoney = $payPreMoney;
        $service->betMoney = $betMoney;
        $service->body = "充值";
        $service->custType = 2;

        $ret = $service->recharge();
        if ($ret !== true) {
            if ($get["way_type"] == "JSAPI") {
                $domain = Yii::$app->params["storeDomain"];
                return $this->render("jsapi", ["data" => $ret, "domain" => $domain, "recharge" => 1]);
            }
            return json_encode($ret);
        }
    }

    /**
     * 
     * 竞彩获取处理明细
     * auther GL ctx
     * @return json
     */
    public function actionGetCompetingDetail() {
        $request = Yii::$app->request;
        $post = $request->post();
        $storeId = $this->userId;
        $lotteryOrderCode = $post["lottery_order_code"];
        $lotteryCode = $request->post('lottery_code', 3011);
        $size = $request->post('size', 10);
        $pn = $request->post('page_num', 1);
//        $zqArr = Constants::MADE_FOOTBALL_LOTTERY;
//        $lqArr = CompetConst::MADE_BASKETBALL_LOTTERY;

        $classCopeting = new DetailDeal();
        $ret = $classCopeting->getTicketing($lotteryCode, $lotteryOrderCode, $pn, $size);
        if ($ret['code'] != 600) {
            return $this->jsonError(109, $ret['msg']);
        }
//        if (in_array($lotteryCode, $zqArr)) {
//            $footballService = new footballService();
//            $ret = $footballService->getDetail($lotteryOrderCode, $storeId, $pn, $size, true);
//        } elseif (in_array($lotteryCode, $lqArr)) {
//            $basketballService = new BasketService();
//            $ret = $basketballService->getDetail($lotteryOrderCode, $pn, $size);
//        } else {
//            return $this->jsonError(100, '参数错误');
//        }
        return $this->jsonResult(600, '获取成功', $ret['data']);
    }

    /**
     * 设置基础信息
     * @auther GL zyl
     * @return type
     */
    public function actionSetStoreInfo() {
        $request = Yii::$app->request;
        $custNo = $this->custNo;
        if ($this->storeOperatorId != 0) {
            return $this->jsonResult(109, "操作员不可修改，门店信息", "");
        }
//        $custNo = 'gl00004278';
        $sName = $request->post('store_name', '');
        $openTime = $request->post('open_time', '');
        $closeTime = $request->post('close_time', '');
        $remark = $request->post('store_remark', '');
        $sProvince = $request->post('province', '');
        $sCity = $request->post('city', '');
        $sArea = $request->post('area', '');
        $sAddress = $request->post('address', '');
        $sCoordinate = $request->post('coordinate', '');
        $storeModel = Store::find()->where(['cust_no' => $custNo])->one();
        if ($sName != '') {
            $storeModel->store_name = $sName;
        }
        if ($openTime != '') {
            $storeModel->open_time = $openTime;
        }
        if ($closeTime != '') {
            $storeModel->close_time = $closeTime;
        }
        if ($remark != '') {
            $storeModel->store_remark = $remark;
        }
        if ($sProvince != '') {
            $storeModel->province = $sProvince;
        }
        if ($sCity != '') {
            $storeModel->city = $sCity;
        }
        if ($sArea != '') {
            $storeModel->area = $sArea;
        }
        if ($sAddress != '') {
            $storeModel->address = $sAddress;
        }
        if ($sCoordinate != '') {
            $storeModel->coordinate = $sCoordinate;
        }
        $storeModel->modify_time = date('Y-m-d H:i:s');
        if ($storeModel->validate()) {
            $upStore = $storeModel->save();
            if ($upStore == false) {
                return $this->jsonError(100, '参数缺失');
            }
        } else {
            return $this->jsonError(109, '门店基础信息验证失败');
        }
        return $this->jsonResult(600, '保存成功', true);
    }

    /**
     * 设置门店头像
     * @auther GL zyl
     * @return type
     */
    public function actionSetStoreImg() {
        $request = Yii::$app->request;
        $custNo = $this->custNo;
        if ($this->storeOperatorId != 0) {
            return $this->jsonResult(109, "操作员不可修改，门店信息", "");
        }
        $imgKey = $request->post('img_key', '');
        $fieldArr = StoreConstants::IMG_FIELD;
        if ($imgKey == '' || (!array_key_exists($imgKey, $fieldArr))) {
            return $this->jsonError(100, '参数缺失');
        }
        $field = $fieldArr[$imgKey];
        $storeModel = Store::find()->where(['cust_no' => $custNo])->one();
        if (isset($_FILES['file'])) {
            $storeImg = $_FILES['file'];
            $pic = $storeImg['tmp_name'];
            $day = date('ymdHis', time());
            $check = Uploadfile::check_upload_pic($storeImg);
            if ($check['code'] != 600) {
                return $this->jsonError($check['code'], $check['msg']);
            }
            $key = 'img/store/store_pic/' . $custNo . '/' . $day . '-' . $storeImg['name'];
            $storePath = Uploadfile::qiniu_upload($pic, $key);
            if ($storePath == 441) {
                return $this->jsonError(441, '上传失败');
            }
            $storeModel->$field = $storePath;
            if ($storeModel->save()) {
                return $this->jsonResult(600, '修改成功', $storePath);
            } else {
                return $this->jsonError(109, $storeModel->getFirstErrors());
            }
        } else {
            return $this->jsonError(100, '未上传图片');
        }
    }

    /**
     * 发布方案
     * @auther GL ctx 
     * @return json
     */
    public function actionAddProgramme() {
        $post = Yii::$app->request->post();
        $custNo = $this->custNo;
        $userId = $this->userId;
        $store = \app\modules\common\models\Store::findOne(["user_id" => $userId]);
        $userFunds = \app\modules\common\models\UserFunds::findOne(["cust_no" => $custNo]);
        if (empty($userFunds->pay_password)) {
            return $this->jsonResult(403, "未设置支付密码", "");
        }
        if (md5($post["pay_password"]) != $userFunds->pay_password) {
            return $this->jsonResult(406, "密码错误", "");
        }
        $owner_buy_number = ceil($post["total"] * 0.1);
        if ($owner_buy_number < $post["owner_buy_number"]) {
            $owner_buy_number = $post["owner_buy_number"];
        }
        $payAmount = $post["minimum_guarantee"] + $owner_buy_number;
        if ($userFunds->able_funds <= $payAmount) {
            return $this->jsonResult(407, "余额不足", "");
        }
        if (isset($post["lottery_type"])) {
            $programme = new \app\modules\common\services\ProgrammeService;
            $programme->addProgramme($post, $owner_buy_number, $custNo, $userId, $payAmount, $store->store_name, 2);
        } else {
            return $this->jsonResult(109, "未设置彩票类型", "");
        }
    }

    /**
     * 验证是否存在支付密码
     * @auther GL ctx
     * @return json
     */
    public function actionHasPayPossword() {
        $userFunds = \app\modules\common\models\UserFunds::findOne(["cust_no" => $this->custNo]);
        if (empty($userFunds)) {
            return $this->jsonResult(2, "用户错误", "");
        } else {
            if (empty($userFunds["pay_password"])) {
                return $this->jsonResult(403, "未设置支付密码", "");
            } else {
                return $this->jsonResult(600, "存在支付密码", ["able_funds" => $userFunds->able_funds]);
            }
        }
    }

    /**
     * 获取方案列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetProgramme() {
//        $custNo = $this->custNo;
        $request = Yii::$app->request;
        $code = $request->post('lottery_code', '');
        $orderBy = $request->post('order_by', '');
        $page = $request->post('page_num', 1);
        $size = $request->post('page_size', 10);
        $pregrammeList = TogetherService::getAllProgramme($page, $size, $orderBy, $code);
        return $this->jsonResult(600, '方案列表', $pregrammeList);
    }

    /**
     * 获取方案详情
     * @auther GL zyl
     * @return type
     */
//    public function actionGetProgrammeDetail() {
//        $storeId = $this->userId;
////        $custNo = 'gl00004278';
//        $request = Yii::$app->request;
//        $programmeId = $request->post('programme_id', '');
//        $programmeCode = $request->post('programme_code', '');
//        $listType = $request->post('list_type', '');
//        if ($listType == '') {
//            return $this->jsonError(100, '参数缺失');
//        }
//        if ($programmeId == '' && $programmeCode == '') {
//            return $this->jsonError(100, '参数缺失');
//        }
//        if ($listType == 1) {
//            $data = TogetherService::getListDetail($programmeId, $storeId, '', $programmeCode);
//        } elseif ($listType == 2) {
//            $data = TogetherService::getSubscribeDetail($programmeId, $storeId, '', $programmeCode);
//        }
//        if ($data['code'] != 600) {
//            return $this->jsonError($data['code'], $data['msg']);
//        }
//        $detailList['data'] = $data['data'];
//        return $this->jsonResult(600, '方案详情', $detailList);
//    }

    /**
     * 说明: 修改支付密码发送短信验证码
     * @return 
     */
    public function actionGetPayPasswordSmsCode() {
        $store = Store::findOne(["cust_no" => $this->custNo]);
        $phoneNum = $store->phone_num;
        $cType = 4; //1:注册 4:修改密码
        if (empty($phoneNum)) {
            return $this->jsonError(100, '参数缺失');
        }
        $saveKey = Constants::SMS_KEY_UP_PAY_PWD;
        $smstool = new SmsTool();
        $ret = $smstool->sendSmsCode($cType, $saveKey, $phoneNum);
        if ($ret) {
            return $this->jsonResult(600, '发送成功', true);
        } else {
            return $this->jsonError(100, '发送失败');
        }
    }

    /**
     * 支付密码修改
     * @return type
     */
    public function actionSettingpaypassword() {
        $store = Store::findOne(["cust_no" => $this->custNo]);
        $post = \Yii::$app->request->post();
        $db = \Yii::$app->db;
        $saveKey = Constants::SMS_KEY_UP_PAY_PWD;
        SmsTool::check_code($saveKey, $store->phone_num, $post["smsCode"]);
        $ret = $db->createCommand()->update('user_funds', [
                    "pay_password" => md5($post['pay_password'])
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
     * 获取跟单人员列表
     * @auther GL zyl
     * @return type
     */
//    public function actionGetWithPeople() {
//        $request = Yii::$app->request;
//        $page = $request->post('page_num', 1);
//        $size = $request->post('size', 10);
//        $programmeId = $request->post('programme_id', '');
//        $programmeCode = $request->post('programme_code', '');
//        if ($programmeId == '' && $programmeCode == '') {
//            return $this->jsonError(100, '参数缺失');
//        }
//        $withList = TogetherService::getWithPeople($page, $size, $programmeId, $programmeCode);
//        return $this->jsonResult(600, '跟单人员', $withList);
//    }

    /**
     * 门店二维码获取
     * @return json
     */
    public function actionGetQrcode() {
        $custNo = $this->custNo;
        $storeCode = $this->storeCode;
//        $userId = $this->userId;
//        $post = Yii::$app->request->post();
        $store = Store::findOne(["store_code" => $storeCode, 'status' => 1]);
        if ($store->cert_status != 3) {
            return $this->jsonError(109, '请先通过门店认证');
        }
//        $url = \Yii::$app->params["userDomain"] . "/scanToBind/" . $userId;
        $url = \Yii::$app->params["userDomain"] . "/scanToBind/" . $store->store_code;
//        if (empty($store->store_qrcode) || (isset($post["refresh"]) && $post["refresh"] == 1)) {
//            $qrcode = new \app\modules\components\qrcode\qrcode();
//            $qrcode->url = $url;
//            $qrcode->logo = $store->store_img;
//            $qrcode->imgName = \Yii::$app->basePath . "/web/" . "qrcode_images/" . $store->store_code . ".png";
//            $qrcode->productQrcode();
//            $ret = Uploadfile::sysUploadImg($qrcode->imgName, "/qrcode_images/", $store->store_code . ".png");
//            $ret = json_decode($ret, 256);
//            if ($ret["code"] == 600) {
//                $store->store_qrcode = $ret["result"]["ret_path"];
//            } else {
//                return $this->jsonResult(109, "二维码生成失败");
//            }
//            $store->save();
//            unlink($qrcode->imgName);
//        }

        if (empty($store->store_img)) {
            $img = '';
        } else {
            $img = $store->store_img;
        }
        return $this->jsonResult(600, "门店二维码", ["store_img" => $img, "store_code" => $store->store_code, "store_name" => $store->store_name, "province" => $store->province, "city" => $store->city, "area" => $store->area, "address" => $store->address, "url" => $url]);
    }

    /**
     * 获取任选订单详情
     * @auther GL zyl
     * @return type
     */
    public function actionGetOptionalOrder() {
        $request = Yii::$app->request;
        $post = $request->post();
        $orderCode = $post["lottery_order_code"];
        if (empty($orderCode)) {
            return $this->jsonError(100, "参数缺失");
        }
        $classCopeting = new FootballService();
        $ret = $classCopeting->getOptionalOrder($orderCode);
        if ($ret['code'] != 600) {
            return $this->jsonError(109, $ret['data']);
        }
        $data['data'] = $ret['result'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 手动派奖
     */
    public function actionPlayAwards() {
        $post = \Yii::$app->request->post();
        $userId = $this->userId;
        $orderCode = $post["lottery_order_code"];
        $awardAmount = (isset($post["award_amount"]) && is_numeric($post["award_amount"])) ? $post["award_amount"] : "";
        $winning = new Winning();
        $ret = $winning->playAwardsFunds($orderCode, $userId, $awardAmount);
    }

    /**
     * 是否有新的订单需要语音播报
     * @return json
     */
    public function actionGetSound() {
        $userId = $this->userId;
        $data = \yii::redisGet("sound:" . $userId);
        \yii::redisSet("sound:" . $userId, false);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 门店拍照票样上传
     * @auther GL zyl
     * @return type
     */
    public function actionUploadOrderImg() {
        $fieldArr = StoreConstants::OUT_IMG_FIELD;
        $request = Yii::$app->request;
//        $userId = $this->userId;
        $storeCode = $this->storeCode;
        $imgKey = $request->post('img_key', '');
        $orderId = $request->post('order_id', '');
        $orderStatus = $request->post('order_status', '');
        if ($imgKey == '' || (!array_key_exists($imgKey, $fieldArr)) || $orderId == '' || $orderStatus == "") {
            return $this->jsonError(100, '参数缺失');
        }
        $userData = LotteryOrder::find()->select(['u.user_id', 'u.cust_no'])
                ->innerJoin('user as u', 'u.cust_no = lottery_order.cust_no')
                ->where(['lottery_order_id' => $orderId, 'store_no' => $storeCode])
                ->asArray()
                ->one();
        if (empty($userData)) {
            return $this->jsonError(109, '此订单为错误订单');
        }
        $saveDir = '/order_img/' . $userData['cust_no'] . '/';
        $field = $fieldArr[$imgKey];
        $outImg = OutOrderPic::find()->where(['user_id' => $userData['user_id'], 'order_id' => $orderId])->one();
        if (empty($outImg)) {
            $outImg = new OutOrderPic;
            $outImg->user_id = $userData['user_id'];
            $outImg->order_id = $orderId;
            $outImg->create_time = date('Y-m-d H:i:s');
        }
        $name = $orderStatus . '_' . $orderId . '_' . $field;
        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
            $check = Uploadfile::check_upload_pic($file);
            if ($check['code'] != 600) {
                return $this->jsonError($check['code'], $check['msg']);
            }
            $path = Storefun::getImgPath($file, $saveDir, $name);
            if ($path['code'] != 600) {
                return $this->jsonError($path['code'], $path['msg']);
            }
            $outImg->$field = $path['data'];
            $outImg->modfiy_time = date('Y-m-d H:i:s');
            $data['data'] = $path['data'];
            if ($outImg->save()) {
                return $this->jsonResult(600, '上传成功', $data);
            } else {
                return $this->jsonResult(109, '上传失败,请重新上传', $outImg->getErrors());
            }
        } else {
            return $this->jsonError(100, '未上传图片');
        }
    }

    /**
     * 门店操作员列表
     * @return type
     */
    public function actionGetStoreOperator() {
        $post = Yii::$app->request->post();
        $statusNames = [
            "1" => "启用",
            "2" => "禁用"
        ];
        $query = (new Query())->select(["s.store_operator_id", "u.user_name", "u.cust_no", "u.user_sex", "u.user_tel", "s.status", "s.create_time"])
                ->from("store_operator s")
                ->join("left join", "user u", "u.user_id=s.user_id")
                ->where(["s.store_id" => $this->storeCode]);
        if (isset($post["conUserName"]) && !empty($post["conUserName"])) {
            $query = $query->andWhere(["u.user_name" => $post["conUserName"]]);
        }
        if (isset($post["conUserTel"]) && !empty($post["conUserTel"])) {
            $query = $query->andWhere(["u.user_tel" => $post["conUserTel"]]);
        }
        if (isset($post["conCustNo"]) && !empty($post["conCustNo"])) {
            $query = $query->andWhere(["u.cust_no" => $post["conCustNo"]]);
        }
        if (isset($post["conStatus"]) && !empty($post["conStatus"])) {
            $query = $query->andWhere(["s.status" => $post["conStatus"]]);
        }
        $storeOperator = $query->all();
        foreach ($storeOperator as &$val) {
            $val["status_name"] = isset($statusNames[$val["status"]]) ? $statusNames[$val["status"]] : "未知状态";
        }

        return $this->jsonResult(600, "门店操作员", $storeOperator);
    }

    /**
     * 新增操作员
     * @return type
     */
    public function actionInsertOperator() {
        $request = \Yii::$app->request;
        $db = Yii::$app->db;
        $optId = $this->storeOperatorId;
        if ($optId != 0) {
            return $this->jsonResult(109, "操作员没有该权限", "");
        }
        $tran = $db->beginTransaction();
        try {
            $userTel = $request->post("userTel", "");
            if (!empty($userTel)) {
                $user = User::findOne(["user_tel" => $userTel]);
                if ($user == null) {
                    $JavaUser = $this->userService->getJavaUserDetailByTel($userTel);
                    if ($JavaUser['httpCode'] == 200) {
                        $userDetail = $this->userService->getJavaUserDetail($JavaUser["data"]['account']); //获取java用户信息
                        if ($userDetail['httpCode'] == 200) {
                            $result = $this->userService->setRegisterData($userDetail);
                            $result['register_from'] = 2; //注册来源咕啦钱包
                            $ret = $this->userService->createOrUpdateUser($userTel, $JavaUser["data"]['account'], $result);
                            $user = User::findOne(["user_tel" => $userTel]);
                            if ($user == null) {
                                return $this->jsonError(109, '未找到该会员,请先注册');
                            }
                        } else {
                            return $this->jsonError(109, '未找到该会员,请先注册');
                        }
                    } else {
                        return $this->jsonResult(109, "未找到该会员,请先注册", "");
                    }
                }
                if ($user->is_operator == 2) {
                    return $this->jsonResult(109, "该会员已经是操作员，不可多方绑定", "");
                }
                if ($user->user_type != 1) {
                    return $this->jsonResult(109, "该会员非普通会员，不可升级为操作员", "");
                }
            } else {
                return $this->jsonResult(109, "手机号不可为空", "");
            }
            $storeOperator = new StoreOperator();
//            $storeOperator->store_id = $this->userId;
            $storeOperator->store_id = $this->storeCode;
            $storeOperator->user_id = $user->user_id;
            $storeOperator->modify_time = date("Y-m-d H:i:s");
            $storeOperator->create_time = date("Y-m-d H:i:s");
            if ($storeOperator->validate()) {
                $ret = $storeOperator->save();
                if ($ret == false) {
                    return $this->jsonResult(109, "新增操作员失败", "");
                }
                $user->is_operator = 2;
                if ($user->validate()) {
                    $ret = $user->save();
                    if ($ret == false) {
                        return $this->jsonResult(109, "新增操作员失败", "");
                    }
                } else {
                    return $this->jsonResult(109, "新增操作员失败", $user->getFirstErrors());
                }
                $tran->commit();
                // $lotteryqueue = new \LotteryQueue();//同步IM
                //$lotteryqueue->pushQueue('syncImUser_job', 'sync#'.StoreOperator::tableName(), ['tablename' => StoreOperator::tableName(), 'type'=>'update','data'=>['update'=>[],'where'=>['store_operator_id'=>$storeOperator->store_operator_id]],'pkField'=>'store_operator_id']);
                return $this->jsonResult(600, "新增操作员成功", "");
            } else {
                return $this->jsonResult(109, "新增操作员失败", $storeOperator->getFirstErrors());
            }
        } catch (yii\base\Exception $e) {
            $tran->rollBack();
            return $this->jsonResult(109, "新增操作员失败", $e);
        }
    }

    /**
     * 门店拍照票样删除
     * @auther GL zyl
     * @return type
     */
    public function actionDeleteOrderImg() {
        $fieldArr = StoreConstants::OUT_IMG_FIELD;
        $request = Yii::$app->request;
        $imgKey = $request->post('img_key', '');
        $orderId = $request->post('order_id', '');
        $orderStatus = $request->post('order_status', '');
        if ($imgKey == '' || (!array_key_exists($imgKey, $fieldArr)) || $orderId == '' || $orderStatus == "") {
            return $this->jsonError(100, '参数缺失');
        }
        $field = $fieldArr[$imgKey];
        $outImg = OutOrderPic::find()->where(['order_id' => $orderId])->one();
        if (empty($outImg)) {
            return $this->jsonError(109, '该订单未上传样票');
        }
        //判断图片是否出票前上传还是出票后上传,出票后之前票根不能被删除
        if ($orderStatus != 2) {
            $imgUrl = $outImg->$field;
            $imgAry = explode("/", $imgUrl);
            $nowImg = explode("_", end($imgAry));
            if ($nowImg[1] == 2) {
                return $this->jsonError(109, '出票前票根不可删除');
            }
        }
        $outImg->$field = '';
        $outImg->modfiy_time = date('Y-m-d H:i:s');
        if ($outImg->save()) {
            return $this->jsonResult(600, '删除成功', true);
        } else {
            return $this->jsonResult(109, '删除失败', $outImg->getErrors());
        }
    }

    /**
     * 删除门店操作员
     * @return type
     */
    public function actionDelStoreOperator() {
        $post = Yii::$app->request->post();
        $db = Yii::$app->db;
        $storeCode = $this->storeCode;
        $optId = $this->storeOperatorId;
        if (!isset($post["store_operator_id"])) {
            return $this->jsonResult(109, "参数缺失", "");
        }
        if ($optId == 0) {
            $storeOperator = StoreOperator::findOne(["store_operator_id" => $post["store_operator_id"], "store_id" => $storeCode]);
            if ($storeOperator == null) {
                return $this->jsonResult(109, "未找到该操作员", "");
            }
            $optUserId = $storeOperator->user_id;
            $ret = $storeOperator->delete();
//            $ret = $db->createCommand()->delete("store_operator", ["store_operator_id" => $post["store_operator_id"], "user_id" => $userId])->execute();
            if ($ret == true) {
                User::updateAll(["is_operator" => 1], ["user_id" => $optUserId]);
                // $lotteryqueue = new \LotteryQueue();//同步IM
                //$lotteryqueue->pushQueue('syncImUser_job', 'sync#'.StoreOperator::tableName(), ['tablename' => StoreOperator::tableName(), 'type'=>'delete','data'=>['where'=>['store_operator_id'=>$storeOperator->store_operator_id]],'pkField'=>'store_operator_id']);
                return $this->jsonResult(600, "删除操作员成功", "");
            }
            return $this->jsonResult(109, "删除操作员失败", "");
        } else {
            return $this->jsonResult(109, "操作员没有删除权限", "");
        }
    }

    /**
     * 启用禁用操作员
     * @return type
     */
    public function actionStatusSwitch() {
        $post = Yii::$app->request->post();
        $storeCode = $this->storeCode;
        $optId = $this->storeOperatorId;
        $statusNames = [
            "1" => "启用",
            "2" => "禁用"
        ];
        if (!isset($post["store_operator_id"]) || !isset($post["status"])) {
            return $this->jsonResult(109, "参数缺失", "");
        }
        if (!isset($statusNames[$post["status"]])) {
            return $this->jsonResult(109, "参数错误", "");
        }
        if ($optId != 0) {
            return $this->jsonResult(109, "操作员没有该权限", "");
        }
        $storeOperator = StoreOperator::findOne(["store_operator_id" => $post["store_operator_id"], "store_id" => $storeCode]);
        $storeOperator->status = $post["status"];
        if ($storeOperator->validate()) {
            $ret = $storeOperator->save();
            if ($ret == true) {
                //$lotteryqueue = new \LotteryQueue();//同步IM
                //$lotteryqueue->pushQueue('syncImUser_job', 'sync#'.StoreOperator::tableName(), ['tablename' => StoreOperator::tableName(), 'type'=>'update','data'=>['update'=>[],'where'=>['store_operator_id'=>$storeOperator->store_operator_id]],'pkField'=>'store_operator_id']);
                return $this->jsonResult(600, $statusNames[$post["status"]] . "操作员成功", "");
            }
            return $this->jsonResult(109, $statusNames[$post["status"]] . "操作员失败", "");
        } else {
            return $this->jsonResult(109, "修改失败", $storeOperator->getFirstErrors());
        }
    }

    /**
     * 获取操作员的信息
     * @return type
     */
    public function actionGetStoreOperatorInfo() {
        $post = Yii::$app->request->post();
        $storeCode = $this->storeCode;
        $storeOperator = StoreOperator::find()->where(["store_operator_id" => $post["store_operator_id"], "user_id" => $storeCode])->asArray()->one();
        if ($storeOperator == null) {
            return $this->jsonResult(109, "未找到该操作员", "");
        }
        return $this->jsonResult(600, "获取成功", $storeOperator);
    }

    /**
     * 操作员登录信息
     * @return type
     */
//    public function actionOperatorInfo() {
//        $storeOperatorId = $this->storeOperatorId;
//        if ($storeOperatorId == 0) {
//            $storeOperatorId = $this->userId;
//        }
//        $info = User::find()->where(["user_id" => $storeOperatorId])->asArray()->one();
//        if ($info == null) {
//            return $this->jsonResult(109, "未找到该操作员", "");
//        }
//        return $this->jsonResult(600, "获取成功", $info);
//    }

    /**
     * 获取在售彩种
     * @auther  GL zyl
     * @return type
     */
    public function actionGetSaleLottery() {
        $storeNo = $this->storeCode;
        $saleLottery = Store::find()->select(['sale_lottery'])->where(['store_code' => $storeNo, 'status' => 1])->asArray()->one();
        if (!empty($saleLottery)) {
            $data['data'] = explode(',', $saleLottery['sale_lottery']);
        } else {
            $data['data'] = [];
        }
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 设置在售彩种
     * @auther GL zyl
     * @return type
     */
    public function actionSetSaleLottery() {
        $storeNo = $this->storeCode;
        $optId = $this->storeOperatorId;
        if ($optId != 0) {
            return $this->jsonError(109, '暂无此操作权限');
        }
        $request = Yii::$app->request;
        $saleLottery = $request->post('sale_lottery', '');
        $store = Store::findOne(['store_code' => $storeNo, 'status' => 1]);
        $store->sale_lottery = $saleLottery;
        $store->modify_time = date('Y-m-d H:i:s');

        if (!$store->save()) {
            return $this->jsonError(109, '修改失败');
        }
        return $this->jsonResult(600, '修改成功', true);
    }

    /**
     * 获取报表日统计数据
     */
    public function actionGetReport() {
        $post = Yii::$app->request->post();
        $storeNo = $this->storeCode;
        $statusArr = [3, 4, 5];
        $query = (new Query())->select(["DATE_FORMAT(create_time,'%Y-%m-%d') days", "count(distinct cust_no) count", "sum(bet_money) salemoney", "count(lottery_order_id) ordernum", "sum(win_amount) winmoney", "sum(award_amount) award_amount"])
                ->from("lottery_order")
                ->where(["store_no" => $storeNo])
                ->andWhere(["in", "status", $statusArr]);

        if (isset($post["start_date"]) && !empty($post["start_date"])) {
            $query = $query->andWhere([">=", "lottery_order.create_time", $post["start_date"] . " 00:00:00"]);
        }

        if (isset($post["end_date"]) && !empty($post["end_date"])) {
            $query = $query->andWhere(["<=", "lottery_order.create_time", $post["end_date"] . " 23:59:59"]);
        }
        $query = $query->groupBy("days")
                ->orderBy("days desc")
                ->indexBy("days");
        $result = $query->all();
        $list = (new Query())->select(["DATE_FORMAT(lottery_order.create_time,'%Y-%m-%d') days", "sum(pay_record.pay_money) paymoney"])
                ->from("lottery_order,pay_record")
                ->where(["lottery_order.store_no" => $storeNo])
                ->andWhere("lottery_order.lottery_order_code=pay_record.order_code")
                ->andWhere("pay_record.pay_type=16")
                ->andWhere(["in", "lottery_order.status", $statusArr]);
        if (isset($post["start_date"]) && !empty($post["start_date"])) {
            $list = $list->andWhere([">=", "lottery_order.create_time", $post["start_date"] . " 00:00:00"]);
        }

        if (isset($post["end_date"]) && !empty($post["end_date"])) {
            $list = $list->andWhere(["<=", "lottery_order.create_time", $post["end_date"] . " 23:59:59"]);
        }
        $list = $list->groupBy("days")
                ->indexBy("days");
        $res = $list->all();
        //数据重组
        foreach ($result as $k => &$v) {
            if (isset($res[$k])) {
                $v["paymoney"] = $res[$k]["paymoney"];
            } else {
                $v["paymoney"] = 0;
            }
        }
        return $this->jsonResult(100, "获取成功", $result);
    }

    /**
     * 获取报表月统计数据
     */
    public function actionGetMonthReport() {
        $post = Yii::$app->request->post();
        $storeCode = $this->storeCode;
        $statusArr = [3, 4, 5];
        $years = $post['years'];
        $query = (new Query())->select(["DATE_FORMAT(create_time,'%Y-%m') months", "count(distinct cust_no) count", "sum(bet_money) salemoney", "count(lottery_order_id) ordernum", "sum(win_amount) winmoney", "sum(award_amount) award_amount"])
                ->from("lottery_order")
                ->where(["store_no" => $storeCode])
//                ->andWhere(["store_id" => $storeId])
                ->andWhere(["DATE_FORMAT(create_time,'%Y')" => $years])
                ->andWhere(["in", "status", $statusArr]);
        $query = $query->groupBy("months")
                ->orderBy("months desc")
                ->indexBy("months");
        $result = $query->all();
        $list = (new Query())->select(["DATE_FORMAT(lottery_order.create_time,'%Y-%m') months", "sum(pay_record.pay_money) paymoney"])
                ->from("lottery_order,pay_record")
                ->where(["lottery_order.store_no" => $storeCode])
                ->andWhere("lottery_order.lottery_order_code=pay_record.order_code")
                ->andWhere("pay_record.pay_type=16")
                ->andWhere(["DATE_FORMAT(lottery_order.create_time,'%Y')" => $years])
                ->andWhere(["in", "lottery_order.status", $statusArr]);
        $list = $list->groupBy("months")
                ->indexBy("months");
        $res = $list->all();
        foreach ($result as $k => &$v) {
            if (isset($res[$k])) {
                $v["paymoney"] = $res[$k]["paymoney"];
            } else {
                $v["paymoney"] = 0;
            }
        }
        return $this->jsonResult(100, "获取成功", $result);
    }

    /**
     * 获取彩种统计数据
     */
    public function actionGetLotteryReport() {
        $post = Yii::$app->request->post();
        $storeCode = $this->storeCode;
        $statusArr = [3, 4, 5];
        $timer = $post['timer'];
        $lotteryCode = $post['lottery_code'];
        $star = $post['star'];
        $end = $post['end'];
        $lotteryZu = [3006, 3007, 3008, 3009, 3010, 3011];
        $lotteryLan = [3001, 3002, 3003, 3004, 3005];
        $lotteryBd = [5001, 5002, 5003, 5004, 5005, 5006];
        $query = (new Query())->select(["lottery_name", "lottery_id", "count(distinct cust_no) count", "sum(bet_money) salemoney", "count(lottery_order_id) ordernum", "sum(win_amount) winmoney", "sum(award_amount) award_amount"])
                ->from("lottery_order")
                ->where(["store_no" => $storeCode])
                ->andWhere(["in", "status", $statusArr]);
        if ($timer != "") {
            if ($timer == 0) {
                $query = $query->andWhere(["between", "lottery_order.create_time", date("Y-m-d") . " 00:00:00", date("Y-m-d") . " 23:59:59"]);
            } else {
                $query = $query->andWhere(["between", "lottery_order.create_time", date("Y-m-d", strtotime("-" . $timer . "days")) . " 00:00:00", date("Y-m-d") . " 23:59:59"]);
            }
        }
        if (!empty($star) && !empty($end)) {
            $query = $query->andWhere(["between", "lottery_order.create_time", $star . " 00:00:00", $end . " 23:59:59"]);
        }
        if ($lotteryCode != 0) {
            if ($lotteryCode == 3000) {
                $query = $query->andWhere(["in", "lottery_id", $lotteryZu]);
            } elseif ($lotteryCode == 3100) {
                $query = $query->andWhere(["in", "lottery_id", $lotteryLan]);
            } elseif ($lotteryCode == 5000) {
                $query = $query->andWhere(["in", "lottery_id", $lotteryBd]);
            } else {
                $query = $query->andWhere(["lottery_id" => $lotteryCode]);
            }
        }
        $query = $query->groupBy("lottery_id ")
                ->orderBy("lottery_id  desc")
                ->indexBy("lottery_id");
        $result = $query->all();

        $list = (new Query())->select(["lottery_order.lottery_id", "sum(pay_record.pay_money) paymoney"])
                ->from("lottery_order")
                ->leftJoin("pay_record", "lottery_order.lottery_order_code=pay_record.order_code")
                ->where(["lottery_order.store_no" => $storeCode])
                ->andWhere("pay_record.pay_type=16")
                ->andWhere(["in", "lottery_order.status", $statusArr]);
        if ($timer != "") {
            if ($timer == 0) {
                $list = $list->andWhere(["between", "lottery_order.create_time", date('Y-m-d') . " 00:00:00", date('Y-m-d') . " 23:59:59"]);
            } else {
                $list = $list->andWhere(["between", "lottery_order.create_time", date("Y-m-d", strtotime("-" . $timer . "days")) . " 00:00:00", date("Y-m-d") . " 23:59:59"]);
            }
        }
        if (!empty($star) && !empty($end)) {
            $list = $list->andWhere(["between", "lottery_order.create_time", $star . " 00:00:00", $end . " 23:59:59"]);
        }
        if ($lotteryCode != 0) {
            if ($lotteryCode == 3000) {
                $list = $list->andWhere(["in", "lottery_order.lottery_id", $lotteryZu]);
            } elseif ($lotteryCode == 3100) {
                $list = $list->andWhere(["in", "lottery_order.lottery_id", $lotteryLan]);
            } elseif ($lotteryCode == 5000) {
                $query = $query->andWhere(["in", "lottery_id", $lotteryBd]);
            } else {
                $list = $list->andWhere(["lottery_order.lottery_id" => $lotteryCode]);
            }
        }
        $list = $list->groupBy("lottery_order.lottery_id")
                ->indexBy("lottery_id");
        $res = $list->all();
        //数据手续费重组
        foreach ($result as $k => &$v) {
            if (isset($res[$k])) {
                $v["paymoney"] = $res[$k]["paymoney"];
            } else {
                $v["paymoney"] = 0;
            }
        }
        //数组种类重组
        if ($lotteryCode == 0) {
            foreach ($result as $k => &$v) {
                if (in_array($k, $lotteryZu)) {
                    $result["3000"] ["lottery_name"] = "竞彩足球";
                    $result["3000"] ["lottery_id"] = "3000";
                    $result["3000"] ["count"] = 0;
                    $result["3000"] ["salemoney"] = 0;
                    $result["3000"]["ordernum"] = 0;
                    $result["3000"]["winmoney"] = 0;
                    $zuPay = 0;
                }
                if (in_array($k, $lotteryLan)) {
                    $result["3100"] ["lottery_name"] = "竞彩篮球";
                    $result["3100"] ["lottery_id"] = "3100";
                    $result["3100"] ["count"] = 0;
                    $result["3100"] ["salemoney"] = 0;
                    $result["3100"]["ordernum"] = 0;
                    $result["3100"]["winmoney"] = 0;
                    $lanPay = 0;
                }
                if (in_array($k, $lotteryBd)) {
                    $result["5000"] ["lottery_name"] = "北京单场";
                    $result["5000"] ["lottery_id"] = "5000";
                    $result["5000"] ["count"] = 0;
                    $result["5000"] ["salemoney"] = 0;
                    $result["5000"]["ordernum"] = 0;
                    $result["5000"]["winmoney"] = 0;
                    $bdPay = 0;
                }
            }
            foreach ($result as $k => &$v) {
                if (in_array($k, $lotteryZu)) {
                    $result["3000"]["count"] += $v["count"];
                    $result["3000"]["salemoney"] += $v["salemoney"];
                    $result["3000"]["ordernum"] += $v["ordernum"];
                    $result["3000"]["winmoney"] += $v["winmoney"];
                    $zuPay += $v["paymoney"];
                    unset($result[$k]);
                }
                if (in_array($k, $lotteryLan)) {
                    $result["3100"]["count"] += $v["count"];
                    $result["3100"]["salemoney"] += $v["salemoney"];
                    $result["3100"]["ordernum"] += $v["ordernum"];
                    $result["3100"]["winmoney"] += $v["winmoney"];
                    $lanPay += $v["paymoney"];
                    unset($result[$k]);
                }
                if (in_array($k, $lotteryBd)) {
                    $result["5000"]["count"] += $v["count"];
                    $result["5000"]["salemoney"] += $v["salemoney"];
                    $result["5000"]["ordernum"] += $v["ordernum"];
                    $result["5000"]["winmoney"] += $v["winmoney"];
                    $bdPay += $v["paymoney"];
                    unset($result[$k]);
                }
            }
            if (isset($zuPay)) {
                $result["3000"]["paymoney"] = sprintf("%.2f", $zuPay);
            }
            if (isset($lanPay)) {
                $result["3100"]["paymoney"] = sprintf("%.2f", $lanPay);
            }
            if (isset($bdPay)) {
                $result["5000"]["paymoney"] = sprintf("%.2f", $bdPay);
            }
        } else if ($lotteryCode == 3000) {
            $result["3000"] ["lottery_name"] = "竞彩足球";
            $result["3000"] ["lottery_id"] = "3000";
            $result["3000"] ["count"] = 0;
            $result["3000"] ["salemoney"] = 0;
            $result["3000"]["ordernum"] = 0;
            $result["3000"]["winmoney"] = 0;
            $zuPay = 0;
            foreach ($result as $k => &$v) {
                if (in_array($k, $lotteryZu)) {
                    $result["3000"]["count"] += $v["count"];
                    $result["3000"]["salemoney"] += $v["salemoney"];
                    $result["3000"]["ordernum"] += $v["ordernum"];
                    $result["3000"]["winmoney"] += $v["winmoney"];
                    $zuPay += $v["paymoney"];
                    unset($result[$k]);
                }
            }
            $result["3000"]["paymoney"] = sprintf("%.2f", $zuPay);
        } else if ($lotteryCode == 3100) {
            $result["3100"] ["lottery_name"] = "竞彩篮球";
            $result["3100"] ["lottery_id"] = "3100";
            $result["3100"] ["count"] = 0;
            $result["3100"] ["salemoney"] = 0;
            $result["3100"]["ordernum"] = 0;
            $result["3100"]["winmoney"] = 0;
            $lanPay = 0;
            foreach ($result as $k => &$v) {
                if (in_array($k, $lotteryLan)) {
                    $result["3100"]["count"] += $v["count"];
                    $result["3100"]["salemoney"] += $v["salemoney"];
                    $result["3100"]["ordernum"] += $v["ordernum"];
                    $result["3100"]["winmoney"] += $v["winmoney"];
                    $lanPay += $v["paymoney"];
                    unset($result[$k]);
                }
            }
            $result["3100"]["paymoney"] = sprintf("%.2f", $lanPay);
        } elseif ($lotteryCode == 5000) {
            $result["5000"] ["lottery_name"] = "北京单场";
            $result["5000"] ["lottery_id"] = "5000";
            $result["5000"] ["count"] = 0;
            $result["5000"] ["salemoney"] = 0;
            $result["5000"]["ordernum"] = 0;
            $result["5000"]["winmoney"] = 0;
            $bdPay = 0;
            foreach ($result as $k => &$v) {
                if (in_array($k, $lotteryBd)) {
                    $result["5000"]["count"] += $v["count"];
                    $result["5000"]["salemoney"] += $v["salemoney"];
                    $result["5000"]["ordernum"] += $v["ordernum"];
                    $result["5000"]["winmoney"] += $v["winmoney"];
                    $bdPay += $v["paymoney"];
                    unset($result[$k]);
                }
            }
            $result["5000"]["paymoney"] = sprintf("%.2f", $bdPay);
        }
        return $this->jsonResult(100, "获取成功", $result);
    }

    /**
     * 根据统计明细跳转到详细订单信息
     */
    public function actionGetSaleDetail() {
        $post = Yii::$app->request->post();
        $storeCode = $this->storeCode;
        $statusArr = [3, 4, 5];
        $lotteryZu = [3006, 3007, 3008, 3009, 3010, 3011];
        $lotteryLan = [3001, 3002, 3003, 3004, 3005];
        $lotteryBd = [5001, 5002, 5003, 5004, 5005, 5006];
        $query = (new Query())->select(["lottery_order.lottery_order_code", "lottery_order.create_time", "lottery_order.bet_val", "lottery_order.play_name", "lottery_order.lottery_name", "lottery_order.count", "lottery_order.bet_double", "lottery_order.bet_money", "lottery_order.win_amount", "lottery_order.award_amount", "lottery_order.cust_no", "user.user_tel"])
                ->from("lottery_order")
                ->leftJoin("user", "lottery_order.cust_no=user.cust_no")
                ->where(["store_no" => $storeCode])
                ->andWhere(["in", "lottery_order.status", $statusArr]);

        if (isset($post["timer"]) && !empty($post["timer"])) {
            $query = $query->andWhere(["between", "lottery_order.create_time", $post["timer"] . " 00:00:00", $post["timer"] . " 23:59:59"]);
        }
        if (isset($post["months"]) && !empty($post["months"])) {
            $query = $query->andWhere(["between", "lottery_order.create_time", $post["months"] . "-01" . " 00:00:00", $post["months"] . "-31" . " 23:59:59"]);
        }
        if (isset($post["lotteryId"]) && !empty($post["lotteryId"])) {
            if ($post["lotteryId"] == 3000) {
                $query = $query->andWhere(["in", "lottery_id", $lotteryZu]);
            } elseif ($post["lotteryId"] == 3100) {
                $query = $query->andWhere(["in", "lottery_id", $lotteryLan]);
            } elseif ($post["lotteryId"] == 5000) {
                $query = $query->andWhere(["in", "lottery_id", $lotteryBd]);
            } else {
                $query = $query->andWhere(["lottery_id" => intval($post["lotteryId"])]);
            }
        }
        if (isset($post["totaldays"]) && $post["totaldays"] != "") {
            if (intval($post["totaldays"]) == 0) {
                $query = $query->andWhere(["between", "lottery_order.create_time", date('Y-m-d') . " 00:00:00", date('Y-m-d') . " 23:59:59"]);
            } else {
                $query = $query->andWhere(["between", "lottery_order.create_time", date("Y-m-d", strtotime("-" . $post["totaldays"] . "days")) . " 00:00:00", date("Y-m-d") . " 23:59:59"]);
            }
        }
        if (!empty($post["star"]) && !empty($post["end"])) {
            $query = $query->andWhere(["between", "lottery_order.create_time", $post["star"] . " 00:00:00", $post["end"] . " 23:59:59"]);
        }
        if (isset($post["page"])) {
            $page = $post["page"];
        } else {
            $page = 1;
        }
        $size = 10;
        $offset = $size * ($page - 1);
        $data["total"] = (int) $query->count();
        $data["page"] = $page;
        $data["pages"] = ceil($data["total"] / $size);
        $query = $query->offset($offset)
                ->limit($size)
                ->orderBy("create_time desc");
        $data["list"] = $query->all();
        return $this->jsonResult(100, "获取成功", $data);
    }

    /**
     * 获取彩店销售订单数据
     */
    public function actionGetSaleOrderList() {
        $post = Yii::$app->request->post();
        $storeCode = $this->storeCode;
        $statusArr = [3, 4, 5];
        $dealStatusArr = [1, 3];
        $lotteryZu = [3006, 3007, 3008, 3009, 3010, 3011];
        $lotteryLan = [3001, 3002, 3003, 3004, 3005];
        $lotteryBd = [5001, 5002, 5003, 5004, 5005, 5006];
        $query = (new Query())->select(["lottery_order.lottery_order_code", "lottery_order.create_time", "lottery_order.bet_val", "lottery_order.award_amount", "lottery_order.play_name", "lottery_order.lottery_name", "lottery_order.count", "lottery_order.bet_double", "lottery_order.bet_money", "lottery_order.win_amount", "lottery_order.cust_no", "user.user_tel", "lottery_order.deal_status"])
                ->from("lottery_order")
                ->leftJoin("user", "lottery_order.cust_no=user.cust_no")
                ->where(["store_no" => $storeCode]);
        if (isset($post["lotteryOrderCode"]) && !empty($post["lotteryOrderCode"])) {
            $query = $query->andWhere(["lottery_order.lottery_order_code" => $post["lotteryOrderCode"]]);
        }
        if (isset($post["userInfo"]) && !empty($post["userInfo"])) {
            $query = $query->andWhere(["or", ["lottery_order.cust_no" => $post['userInfo']], ["user.user_name" => $post['userInfo']], ["user.user_tel" => $post['userInfo']]]);
        }
//        if (isset($post["optInfo"]) && !empty($post["optInfo"])) {
//            $query = $query->andWhere(["or", ["lottery_order.cust_no" => $post['userInfo']], ["user.user_name" => $post['userInfo']], ["user.user_tel" => $post['userInfo']]]);
//        }
        if ($post["lotteryId"] != 10) {
            if ($post["lotteryId"] == 3000) {
                $query = $query->andWhere(["in", "lottery_order.lottery_id", $lotteryZu]);
            } elseif ($post["lotteryId"] == 3100) {
                $query = $query->andWhere(["in", "lottery_order.lottery_id", $lotteryLan]);
            } elseif ($post['lotteryId'] == 5000) {
                $query = $query->andWhere(['in', 'lottery_order.lottery_id', $lotteryBd]);
            } else {
                $query = $query->andWhere(["lottery_order.lottery_id" => $post["lotteryId"]]);
            }
        }
        if (isset($post["star"]) && !empty($post["star"])) {
            $query = $query->andWhere([">=", "lottery_order.create_time", $post["star"] . " 00:00:00"]);
        }
        if (isset($post["end"]) && !empty($post["end"])) {
            $query = $query->andWhere(["<=", "lottery_order.create_time", $post["end"] . " 23:59:59"]);
        }
        if ($post["status"] == 10) {
            $query = $query->andWhere(["in", "lottery_order.status", $statusArr]);
        } else {
            $query = $query->andWhere(["lottery_order.status" => $post['status']]);
        }
        if ($post["dealStatus"] == 10) {
            $query = $query->andWhere(["in", "lottery_order.deal_status", $dealStatusArr]);
        } else {
            $query = $query->andWhere(["lottery_order.deal_status" => $post['dealStatus']]);
        }
        if (isset($post["page"])) {
            $page = $post["page"];
        } else {
            $page = 1;
        }
        $size = 10;
        $offset = $size * ($page - 1);
        $data["total"] = (int) $query->count();
        $data["page"] = $page;
        $data["pages"] = ceil($data["total"] / $size);
        $query = $query->offset($offset)
                ->limit($size)
                ->orderBy("create_time desc")
                ->indexBy("lottery_order_code");
        $result = $query->all();

        $list = (new Query())->select(["pay_record.order_code", "pay_record.pay_money"])
                ->from("lottery_order")
                ->leftJoin("pay_record", "lottery_order.lottery_order_code=pay_record.order_code")
                ->where(["lottery_order.store_no" => $storeCode])
                ->andWhere("pay_record.pay_type=16");
        if (isset($post["lotteryOrderCode"]) && !empty($post["lotteryOrderCode"])) {
            $list = $list->andWhere(["lottery_order.lottery_order_code" => $post["lotteryOrderCode"]]);
        }
        if (isset($post["userInfo"]) && !empty($post["userInfo"])) {
            $list = $list->andWhere(["or", ["lottery_order.cust_no" => $post['userInfo']], ["user.user_name" => $post['userInfo']], ["user.user_tel" => $post['userInfo']]]);
        }
        if ($post["lotteryId"] != 10) {
            if ($post["lotteryId"] == 3000) {
                $list = $list->andWhere(["in", "lottery_order.lottery_id", $lotteryZu]);
            } elseif ($post["lotteryId"] == 3100) {
                $list = $list->andWhere(["in", "lottery_order.lottery_id", $lotteryLan]);
            } elseif ($post['lotteryId'] == 5000) {
                $list = $list->andWhere(["in", "lottery_order.lottery_id", $lotteryBd]);
            } else {
                $list = $list->andWhere(["lottery_order.lottery_id" => $post["lotteryId"]]);
            }
        }
        if (isset($post["star"]) && !empty($post["star"])) {
            $list = $list->andWhere([">=", "lottery_order.create_time", $post["star"] . " 00:00:00"]);
        }
        if (isset($post["end"]) && !empty($post["end"])) {
            $list = $list->andWhere(["<=", "lottery_order.create_time", $post["end"] . " 23:59:59"]);
        }
        if ($post["status"] == 10) {
            $list = $list->andWhere(["in", "lottery_order.status", $statusArr]);
        } else {
            $list = $list->andWhere(["lottery_order.status" => $post['status']]);
        }
        if ($post["dealStatus"] == 10) {
            $list = $list->andWhere(["in", "lottery_order.deal_status", $dealStatusArr]);
        } else {
            $list = $list->andWhere(["lottery_order.deal_status" => $post['dealStatus']]);
        }
        $list = $list->offset($offset)
                ->limit($size)
                ->orderBy("lottery_order.create_time desc")
                ->indexBy("order_code");
        $res = $list->all();
        //数据手续费重组
        foreach ($result as $k => &$v) {
            if (isset($res[$k])) {
                $v["paymoney"] = $res[$k]["pay_money"];
            } else {
                $v["paymoney"] = "0.00";
            }
        }
        $data["result"] = $result;
        return $this->jsonResult(100, "获取成功", $data);
    }

    /**
     * 门店营业状态获取
     * @return json
     */
    public function actionGetStoreState() {
//        $custNo = $this->custNo;
        $userId = $this->userId;
//        $post = Yii::$app->request->post();
        $store = Store::findOne(["user_id" => $userId]);
        if ($store->cert_status != 3) {
            return $this->jsonError(109, '请先通过门店认证');
        }
        $data['business_status'] = $store->business_status;
        $state = [
            '1' => '营业中',
            '2' => '暂停营业'
        ];
        $data['status_name'] = $state[$store->business_status];
        return $this->jsonResult(600, "门店营业状态", $data);
    }

    /**
     * 更改门店营业状态并记录操作日志
     * @return json
     */
    public function actionChangeStoreState() {
        $request = Yii::$app->request;
        $business_state = $request->post('business_status', 1);
        if ($business_state == 1) {
            $business_state = 2;
        } else {
            $business_state = 1;
        }
        $state = [
            '1' => '营业中',
            '2' => '暂停营业'
        ];
        $custNo = $this->custNo;
        $storeId = $this->userId;
        $userId = $this->storeOperatorId;
        $store = Store::findOne(["user_id" => $storeId]);
        if ($store->cert_status != 3) {
            return $this->jsonError(109, '请先通过门店认证');
        }
        $store->business_status = $business_state;
        if (!$store->save()) {
            return $this->jsonError(109, '操作失败，请重新操作');
        }
        $StoreOptLog = new StoreOptLog();
        $StoreOptLog->create_time = date('Y-m-d H:i:s', time());
        $StoreOptLog->operator_id = $userId;
        $StoreOptLog->operator_name = '';
        $StoreOptLog->store_code = $store->store_code;
        $StoreOptLog->content = $state[$business_state];
        $StoreOptLog->cust_no = $custNo;
        $StoreOptLog->store_name = $store->store_name;
        $StoreOptLog->save();
        $data['business_status'] = $business_state;
        $data['status_name'] = $state[$business_state];
        return $this->jsonResult(600, "操作成功", $data);
    }

    /**
     * 修改对外电话
     * @auther GL zyl
     * @return type
     */
    public function actionSetTel() {
        $request = Yii::$app->request;
        $tel = $request->post('tel', '');
        if ($tel == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $userId = $this->storeOperatorId;
        $storeCode = $this->storeCode;
        $storeId = $this->userId;
        $outTel = str_replace(' ', '', $tel);
        $exist = Store::find()->select(['telephone'])->where(['telephone' => $outTel])->andWhere(['!=', 'store_code', $storeCode])->asArray()->one();
        if (!empty($exist)) {
            return $this->jsonError(109, '该号码已被征用啦！');
        }
        $storeData = Store::findOne(['store_code' => $storeCode, 'user_id' => $storeId]);
        $storeData->telephone = $tel;
        $storeData->opt_id = $userId;
        $storeData->modify_time = date('Y-m-d H:i:s');
        if (!$storeData->save()) {
            return $this->jsonError(109, '修改失败');
        }
        return $this->jsonResult(600, '修改成功', true);
    }

    /**
     * 说明:地图获取门店信息列表 
     * @author  kevi
     * @date 2017年12月14日 下午4:41:24
     * @param   lat 当前经度
     * @param   long 当前纬度
     * @return 
     */
    public function actionGetMapStoreList() {
        $request = \Yii::$app->request;
        $nowLat = $request->post('lat', 0);
        $nowLong = $request->post('long', 0);
        $storeList = Store::find()
                        ->select(['store_id', 'store_name', 'store_code', 'province', 'city', 'area', 'address', 'telephone', 'store_img', 'coordinate', 'business_status', 'company_id'])
                        ->where(['status' => 1, 'cert_status' => 3])->andWhere(['!=', 'store_code', '10004'])->andWhere('coordinate is not null')->asArray()->all();
        if ($nowLat && $nowLong) {
            $aparts = [];
            foreach ($storeList as $k => $store) {
                $position = explode(',', $store['coordinate']);
                $apart = Toolfun::getDistance($nowLat, $nowLong, $position[0], $position[1]);
                $storeList[$k]['apart'] = $apart;
                $aparts[] = $apart;
            }
            array_multisort($aparts, SORT_ASC, $storeList);
        }
        $this->jsonResult(600, '获取成功', $storeList);
    }

    /**
     * 获取APP统计报表今日、昨日数据
     */
    public function actionGetNowReport() {
        $post = Yii::$app->request->post();
        $storeCode = $this->storeCode;
        $storeService = new StoreService();
        $today = $storeService->getNowReport($storeCode, 0);
        $lastDay = $storeService->getNowReport($storeCode, 1);
        $data = array("today" => $today, "lastDay" => $lastDay);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取APP统计报表月份数据
     */
    public function actionGetNowMonthReport() {
        $request = Yii::$app->request;
        $storeCode = $this->storeCode;
        $date = $request->post("date", "");
        if ($date == "") {
            return $this->jsonError(109, "参数缺失");
        }
        $storeService = new StoreService();
        $data = $storeService->getMonthReport($storeCode, $date);
        return $this->jsonResult(600, "获取成功", $data);
    }
    
    /**
     * 门店接单
     * @return type
     */
    public function actionOrderTaking() {
        $request = \Yii::$app->request;
        $userId = $this->storeOperatorId;
        $storeCode = $this->storeCode;
        $orderCode = $request->post('orderCode', '');
        if(empty($orderCode)) {
            return $this->jsonError(109, '参数数据缺失');
        }
        $storeService = new StoreService();
        $res = $storeService->orderTaking($orderCode, $storeCode, $userId);
        if($res['code'] != 600) {
            return $this->jsonError(109, $res['msg']);
        }
        return $this->jsonResult(600, '接单成功', ['order_status' => 11, 'status_name' => '等待出票']);
    }

}
