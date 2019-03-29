<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\publicinterface\controllers;

use app\modules\common\models\LotteryRecord;
use yii\web\Controller;
use Yii;
use app\modules\common\helpers\Constants;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\LotteryAdditional;
use app\modules\common\models\Lottery;
use app\modules\common\models\DirectTrendChart;
use app\modules\common\models\GroupTrendChart;
use app\modules\common\models\MultidigitTrendChart;
use yii\db\Query;
use app\modules\common\services\PayService;
use app\modules\common\services\TogetherService;
use app\modules\common\models\ProgrammeUser;
use app\modules\common\models\Programme;
use app\modules\common\models\DiyFollow;
use app\modules\common\models\Store;
use yii\base\Exception;
use app\modules\common\services\OrderService;
use app\modules\common\models\PayType;
use app\modules\common\services\ResultService;
use app\modules\common\services\FundsService;
use app\modules\common\models\OutOrderPic;
use app\modules\common\models\ExpertLevel;
use app\modules\user\models\User;
use app\modules\store\helpers\StoreConstants;
use app\modules\user\services\IUserService;
use app\modules\store\models\StoreDetail;
use app\modules\tools\helpers\Uploadfile;
use app\modules\store\helpers\Storefun;
use app\modules\experts\services\ExpertService;
use app\modules\common\models\ElevenTrendChart;
use app\modules\common\helpers\TrendFall;
use app\modules\competing\services\OptionalService;
use app\modules\competing\helpers\CompetConst;
use app\modules\competing\services\BasketService;
use app\modules\common\services\ScheduleService;
use app\modules\common\services\ProgrammeService;
use app\modules\common\models\Bananer;
use app\modules\orders\helpers\OrderDeal;
use app\modules\competing\services\BdService;
use app\modules\common\services\SyncService;
use app\modules\competing\services\FootballService;
use app\modules\orders\services\InquireService;
use app\modules\competing\services\WorldcupService;

class InterfaceController extends Controller {

    private $userService;

    public function __construct($id, $module, $config = [], IUserService $userService) {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    /**
     * 下订单
     * auther GL ctx
     * @return json
     */
    public function actionPlayOrder() {
        $request = Yii::$app->request;
        $post = $request->post();
        $orderType = $request->post('order_type', '1');
        $orderData = json_decode($post["order_data"], JSON_UNESCAPED_UNICODE);
        if (empty($orderType)) {
            $orderType = 1;
        }
        //payway //检查优惠券
        //检查优惠信息是否正确
        if ($orderType == 1) {
            $source = $request->post('source', 1);
            $sourceId = $request->post('source_id', '');
            if ($source == 5) {
                if (empty($sourceId)) {
                    return $this->jsonError(2, '分享跟单来源不可为空');
                }
            }
            if (!isset($orderData["lottery_type"]) || empty($orderData["lottery_type"])) {
                return $this->jsonResult(2, '投注彩种未设置', '');
            }
            if (!is_int($orderData['total']) || $orderData['total'] <= 0) {
                return $this->jsonError(2, '投注金额格式不对');
            }
            if (!array_key_exists('major_type', $orderData)) {
                if (!is_int($orderData['multiple']) || $orderData['multiple'] <= 0) {
                    return $this->jsonError(2, '投注倍数格式不对');
                }
            }
            if ($orderData["count_bet"] > 10000) {
                return $this->jsonResult(2, '单注单倍金额不可超过20000', '');
            }
            $lotteryCode = $orderData['lottery_code'];
//            $store = (new Query())->select("cust_no,store_code,sale_lottery,business_status")->from("store")->where(["user_id" => $storeId])->one();
            $isSale = Lottery::find()->select(['lottery_code'])->where(['lottery_code' => $lotteryCode, 'sale_status' => 1])->asArray()->one();
            if (empty($isSale)) {
                return $this->jsonError(2, '此彩种暂未开售！！');
            }
            $agent = \Yii::$agentId;
//            $autoLottery = Constants::AUTO_LOTTERY;
//            $autoCust = \Yii::$app->params['auto_cust'];
            if ($agent == 0) {
                if (!isset($post["store_id"]) || empty($post["store_id"])) {
                    return $this->jsonResult(2, '投注门店未设置', '');
                }
                $storeNo = $post["store_id"];
                if ($storeNo == 'PC0419') { // PC专属 TV专属
                    $outStore = OrderDeal::getOutStore($lotteryCode, $orderData['count_bet'], 1);
                    if ($outStore['code'] != 600) {
                        return $this->jsonError(2, $outStore['msg']);
                    }
                    $storeNo = $outStore['data']['store_no'];
                } 
            } else {
                $storeNo = '';
            }
            $outTicket = OrderDeal::judgeOutType($orderData);
            $overTime = OrderDeal::judgeTimeout($outTicket['outNums'], $outTicket['endTime'], $outTicket['outType'], $storeNo);
            if ($overTime['code'] != 600) {
                return $this->jsonError(415, $overTime['msg']);
            }
            $outType = $overTime['data'];
            if ($outType == 2 || $agent != 0) {
                $ipProvince = $request->post('ip_province', ''); // 下单省份
                $outStore = OrderDeal::getOutStore($lotteryCode, $outTicket['outNums'], $outType, $storeNo, $ipProvince);
                if ($outStore['code'] != 600) {
                    return $this->jsonError(2, $outStore['msg']);
                }
//                $storeId = $outStore['data']['store_id'];
                $storeNo = $outStore['data']['store_no'];
            }
            $storeData = OrderDeal::judgeStoreData($storeNo, $lotteryCode);
            if($storeData['code'] != 600) {
                return $this->jsonError($storeData['code'], $storeData['msg']);
            }
            $autoLottery = $storeData['autoLottery'];
            $storeId = $storeData['data']['user_id'];
            if ($storeNo == \Yii::$app->params['auto_store_no'] && in_array($lotteryCode, $autoLottery)) {
                $outType = 2;
//                $storeId =  \Yii::$app->params['auto_store_id'];
//                $storeNo =  \Yii::$app->params['auto_store_no'];
            }
            switch ($orderData["lottery_type"]) {
                case "1":
                    $ret = OrderService::numsOrder($this->custNo, $this->userId, $storeId, $storeNo, $source, $sourceId, $outType);
                    break;
                case "2":
                    $ret = OrderService::competingOrder($this->custNo, $this->userId, $storeId, $storeNo, $source, $sourceId, $outType);
                    break;
                case "3":
                    $ret = OrderService::optionalOrder($this->custNo, $this->userId, $storeId, $storeNo, $source, $sourceId, $outType);
                    break;
                case '4':
                    $ret = OrderService::basketOrder($this->custNo, $this->userId, $storeId, $storeNo, $source, $sourceId, $outType);
                    break;
                case '5':
                    $ret = OrderService::bdOrder($this->custNo, $this->userId, $storeId, $storeNo, $source, $sourceId, $outType);
                    break;
                case '6':
                    $ret = WorldcupService::playOrder($this->custNo, $this->userId, $storeId, $storeNo, $source, $sourceId, $outType);
                    break;
                default :
                    return $this->jsonResult(2, '无该彩种类型', '');
            }
        } elseif ($orderType == 2) {
            $expertService = new ExpertService();
            $ret = $expertService->BuyArticle($orderData, $this->userId, $this->custNo);
        }
        switch ($orderType) {//订单类型1购彩订单2文章订单3发起合买订单
            case 3:
                $params = array_merge($post, (array) $orderData);
                \Yii::$app->request->setBodyParams($params);
                $ret = ProgrammeService::creatProgramOrder($this->custNo, $this->userId, $params);
                break;
            case 4://跟合买订单
                $params = array_merge($post, (array) $orderData);
                $ret = ProgrammeService::creatProgramUserOrder($this->custNo, $this->userId, $params);
                break;
        }
        if ($ret['code'] != 600) {
            return $this->jsonError($ret['code'], $ret['msg']);
        }
        $orderType == 1 && SyncService::syncFromHttp();
        return $this->jsonResult(600, '下注成功！', $ret['result']);
    }

    /**
     * 期数提供
     * auther ctx
     * create_time 2017-5-25
     * @return json
     */
    public function actionProPeriod() {
        $post = Yii::$app->request->post();
        $ret = Commonfun::currentPeriods($post["lottery_code"]);
        if ($ret["error"] == false) {
            return $this->jsonResult(2, '此彩种暂时无法进行投注,请选择其他彩种', '');
        } else {
            $data = $ret["data"];
            $pic = Lottery::find()->select(['lottery_pic', 'lr.pool'])
                    ->leftJoin('lottery_record lr', 'lr.lottery_code = lottery.lottery_code')
                    ->where(['lottery.lottery_code' => $post["lottery_code"], 'lr.status' => 1])
                    ->asArray()
                    ->one();
            $data['picture'] = $pic['lottery_pic'];
            $data['pool'] = $pic['pool'];
            $data["server_time"] = time();
            return $this->jsonResult(600, '获取成功', $data);
        }
    }

    /**
     * 说明:获取当前期
     * @author chenqiwei
     * @date 2018/3/30 下午4:37
     * @param lottery_code  not null 彩种编码
     * @return
     */
    public function actionNowPeriod() {
        $post = Yii::$app->request->post();
        $nowPeriods = LotteryRecord::find()->select(['lottery_code', 'periods', 'limit_time', 'lottery_time', 'status'])
                        ->where(['lottery_code' => $post["lottery_code"]])
                        ->andWhere(['in', 'status', [2, 1, 0]])
                        ->indexBy('status')
                        ->asArray()->all();
        $ret = [];
        foreach ($nowPeriods as $nowPeriod) {
            if ($nowPeriod['status'] == 0) {
                $ret['next'] = $nowPeriod;
            } else if ($nowPeriod['status'] == 1) {
                $ret['now'] = $nowPeriod;
            } else if ($nowPeriod['status'] == 2) {
                $ret['pass'] = $nowPeriod;
            }
        }
        $ret['server_time'] = date('Y-m-d H:i:s');
        return $this->jsonResult(600, '获取成功', $ret);
    }

    /**
     * 投注查询
     * auther 咕啦 zyl
     * create_time 2017-5-27
     * @return json
     */
    public function actionInquireOrder() {
        $request = Yii::$app->request;
        $post = $request->post();
        $custNo = $this->custNo;
        $pn = $request->post('page_num', 1);
        $pageSize = $request->post('page_size', 10);
        $sourceType = $request->post('source_type', '');
        $orderList = InquireService::getOrderList($post, $custNo, $pn, $pageSize, $sourceType);
        return $this->jsonResult(600, '获取成功', $orderList);
    }

    /**
     * 追号查询
     * auther 咕啦 zyl
     * create_time 2017-5-27
     * @return json
     */
    public function actionInquireTrace() {
        $request = Yii::$app->request;
        $custNo = $this->custNo;
        $pn = $request->post('page_num', 1);
        $pageSize = $request->post('page_size', 10);
        $traceList = InquireService::getTraceList($custNo, $pn, $pageSize);
        return $this->jsonResult(600, '获取成功', $traceList);
    }

    /**
     * 投注单的详细信息
     * auther 咕啦 zyl
     * create_time 2017-5-27
     * @return json
     */
    public function actionOrderDetail() {
        $post = Yii::$app->request->post();
        $custNo = $this->custNo;
        $orderCode = $post['order_code'];
        $orderDet = InquireService::getOrderDetail($custNo, $orderCode);
        return $this->jsonResult(600, '获取成功', $orderDet);
    }

    /**
     * 追号单的详细信息
     * auther 咕啦 zyl
     * create_time 2017-5-27
     * @return json
     */
    public function actionTraceDetail() {
        $post = Yii::$app->request->post();
        $custNo = $this->custNo;
        $orderCode = $post['order_code'];
        $traceDet = InquireService::getTraceDetail($custNo, $orderCode);
        return $this->jsonResult(600, '获取成功', $traceDet);
    }

    /**
     * 彩种历史往期的开奖记录
     * auther 咕啦 zyl
     * create_time 2017-05-31
     * @return array
     */
    public function actionHisResult() {
        $request = Yii::$app->request;
        $post = $request->post();
        $code = $post['lottery_code'];
        $size = $request->post('size', 10);
        $pn = $request->post('page_num', 1);
        $numsCode = Constants::MADE_NUMS_LOTTERY;
        if (in_array($code, $numsCode)) {
            $data = ResultService::getNumsResult($code, $pn, $size);
        } else {
            $data = ResultService::getWinsResult($pn, $size);
        }
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 数字彩的走势图
     * auther GL ZYL
     * @return json
     */
    public function actionTrend() {
        $request = Yii::$app->request;
        $code = $request->post('lottery_code', '');
        $offest = $request->post('in_recent', 30);
        $interval = $request->post('interval', '');
        $trendList = [];
        $andwhere = [];
        $missList = [];
        if ($code == '') {
            return $this->jsonResult(109, '参数不可为空', '');
        }
        if ($interval != '') {
            $condition = ['between', 'periods'];
            $andwhere = array_merge($condition, $interval);
            $offest = '';
        }
        $pic = Lottery::find()->where(['lottery_code' => $code])->select(['lottery_pic'])->asArray()->one();
        $trendList['picture'] = $pic['lottery_pic'];
        $directArr = Constants::DIRECT_TREND;
        $groupArr = Constants::GROUP_TREND;
        $multiArr = Constants::MULTIDIGIT_TREND;
        $elevenArr = Constants::ELEVEN_TREND;
        if (in_array($code, $directArr)) {
            $trend = DirectTrendChart::find()->where(['lottery_code' => $code])->andWhere($andwhere)->orderBy('periods desc')->limit($offest)->asArray()->all();
            if (!empty($trend)) {
                foreach ($trend as $k => &$v) {
                    $periods[] = $v['periods'];
                    $v['red_analysis'] = json_decode($v['red_analysis'], true);
                    $v['blue_analysis'] = json_decode($v['blue_analysis'], true);
                }
                array_multisort($periods, SORT_ASC, $trend);
                $missList = TrendFall::getDirectMissing($trend);
            }
        } elseif (in_array($code, $groupArr)) {
            $trend = GroupTrendChart::find()->where(['lottery_code' => $code])->andWhere($andwhere)->orderBy('periods desc')->limit($offest)->asArray()->all();
            if (!empty($trend)) {
                foreach ($trend as $k => &$v) {
                    $periods[] = $v['periods'];
                    $v['analysis'] = json_decode($v['analysis'], true);
                    if ($code == '2009') {
                        $openType = Constants::PUKE_OPEN_TYPE;
                        $v['open_type'] = Commonfun::openType($v['open_code']);
                        $v['open_name'] = $openType[$v['open_type']];
                    }
                }
                array_multisort($periods, SORT_ASC, $trend);
                $missList = TrendFall::getGroupMissing($trend);
            }
        } elseif (in_array($code, $multiArr)) {
            $trend = MultidigitTrendChart::find()->where(['lottery_code' => $code])->andWhere($andwhere)->orderBy('periods desc')->limit($offest)->asArray()->all();
            if (!empty($trend)) {
                foreach ($trend as $k => $v) {
                    $periods[] = $v['periods'];
                    $v['analysis'] = json_decode($v['analysis'], true);
                }
                array_multisort($periods, SORT_ASC, $trend);
                $missList = TrendFall::getMultiMissing($trend);
            }
        } elseif (in_array($code, $elevenArr)) {
            $trend = ElevenTrendChart::find()->where(['lottery_code' => $code])->andWhere($andwhere)->orderBy('periods desc')->limit($offest)->asArray()->all();
            if (!empty($trend)) {
                foreach ($trend as $k => &$v) {
                    $periods[] = $v['periods'];
                    $v['analysis'] = json_decode($v['analysis'], true);
                }
                array_multisort($periods, SORT_ASC, $trend);
                $missList = TrendFall::getElevenMissing($trend);
            }
        } else {
            return $this->jsonResult(109, '此彩种不存在', '');
        }
        $trendList['trend'] = $trend;
        $trendList['missing'] = $missList;
        return $this->jsonResult(600, '走势图', $trendList);
    }

    /**
     * 可投注赛程
     * auther GL ZYL
     * @return json
     */
    public function actionBetGame() {
        $request = Yii::$app->request;
        $lotteryCode = $request->post('lottery_code', '');
        $playType = $request->post('schedule_dg', 1); // 过关方式 1：过关 2：单关
        if ($lotteryCode == '') {
            return $this->jsonResult(109, '请选择玩法', '');
        }
        $footballService = new FootballService();
        $scheduleList = $footballService->getBetSchedule($lotteryCode, $playType);
        return $this->jsonResult(600, '可投注赛程', $scheduleList);
    }

    /**
     * 
     * 竞彩获取订单
     * auther GL ctx
     * @return json
     */
    public function actionGetCompetingOrder() {
        $request = Yii::$app->request;
        $post = $request->post();
        $lotteryOrderCode = $post["lottery_order_code"];
        $lotteryCode = $request->post('lottery_code', 3011);
        if (empty($lotteryOrderCode) || empty($lotteryCode)) {
            return $this->jsonError(100, '参数缺失');
        }
        $custNo = $this->custNo;
        $zqArr = Constants::MADE_FOOTBALL_LOTTERY;
        $lqArr = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdArr = CompetConst::MADE_BD_LOTTERY;
        $worldCupArr = CompetConst::MADE_WCUP_LOTTERY;
        if (in_array($lotteryCode, $zqArr)) {
            $classCopeting = new FootballService();
            $ret = $classCopeting->getOrder($lotteryOrderCode, $custNo);
        } elseif (in_array($lotteryCode, $lqArr)) {
            $classCopeting = new BasketService();
            $ret = $classCopeting->getOrder($lotteryOrderCode, $custNo);
        } elseif (in_array($lotteryCode, $bdArr)) {
            $bdService = new BdService();
            $ret = $bdService->getOrder($lotteryOrderCode, $custNo);
        } elseif (in_array($lotteryCode, $worldCupArr)) {
            $ret = WorldcupService::getOrder($lotteryOrderCode, $custNo);
        } else {
            return $this->jsonError(109, '查询结果不存在');
        }

        return json_encode($ret);
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
        $lotteryOrderCode = $post["lottery_order_code"];
        $lotteryCode = $request->post('lottery_code', 3011);
        $size = $request->post('size', 10);
        $pn = $request->post('page_num', 1);
        $zqArr = Constants::MADE_FOOTBALL_LOTTERY;
        $lqArr = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdArr = CompetConst::MADE_BD_LOTTERY;
        if (in_array($lotteryCode, $zqArr)) {
            $classCopeting = new FootballService();
            $ret = $classCopeting->getDetail($lotteryOrderCode, $this->custNo, $pn, $size);
        } elseif (in_array($lotteryCode, $lqArr)) {
            $basketballService = new BasketService();
            $ret = $basketballService->getDetail($lotteryOrderCode, $pn, $size);
        } elseif (in_array($lotteryCode, $bdArr)) {
            $bdService = new BdService();
            $ret = $bdService->getDetail($lotteryOrderCode, $pn, $size);
        } else {
            return $this->jsonError(100, '参数错误');
        }
        if ($ret['code'] != 600) {
            return $this->jsonError(109, $ret['msg']);
        }
        return $this->jsonResult(600, '获取成功', $ret['result']);
    }

    /**
     * 所有联赛列表
     * auther GL ZYL
     * @return json
     */
    public function actionGetLeague() {
        $request = \Yii::$app->request;
        $status = $request->post('status', 0);
        $where = [];
        if ($status == 1) {
            $where = ['in', 'sr.status', [0, 1, 4, 7]];
        } elseif ($status == 2) {
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-7 day'));
            $where = ['and', ['in', 'sr.status', [2, 3]], ['>=', 's.start_time', $startDate . ' 00:00:00'], ['<', 's.start_time', $endDate . ' 23:59:59']];
        } else {
            $where['sr.status'] = 0;
        }
        $query = new Query;
        $leagueData = $query->select('l.league_id,l.league_category_id,l.league_code,l.league_long_name,l.league_short_name')
                ->from('schedule as s')
                ->innerJoin('schedule_result sr', 'sr.schedule_mid = s.schedule_mid')
                ->innerJoin('league as l', 'l.league_id = s.league_id and league_status = 1')
                ->groupBy('l.league_id')
                ->where($where)
                ->orderBy('l.league_id')
                ->all();
        return $this->jsonResult(600, '所有联赛', $leagueData);
    }

    /**
     * 支付宝异步返回
     * auther GL ctx
     */
    public function actionNotify_ali() {
        $alipay = new \app\modules\components\alipay\alipay();
        $alipay->Notify();
        exit();
    }

    /**
     * 微信支付异步返回
     * auther GL ctx
     */
    public function actionNotify_wxpay() {
        $wxpay = new \app\modules\components\wxpay\wxpay();
        $wxpay->Notify();
        exit();
    }

    /**
     * 微信app支付异步返回
     * auther GL ctx
     */
    public function actionNotify_wxpayapp() {
        $wxpay = new \app\modules\components\wxpay\wxpayapp();
        $wxpay->Notify();
        exit();
    }

    /**
     * 支付宝同步返回
     * auther GL ctx
     */
    public function actionReturn_ali() {
        $alipay = new \app\modules\components\alipay\alipay();
        $alipay->returnUrl();
        exit();
    }

    /**
     * 微信、支付宝、钱包支付
     * auther GL ctx
     * @return json
     */
    public function actionPay() {
        $get = \Yii::$app->request->get();
        $service = new PayService();
        $service->order_code = $get["lottery_order_code"];
        $service->way_type = $get["way_type"];
        $service->pay_way = $get["pay_way"];
        $ret = $service->Pay();
        if ($ret !== true) {
            if ($get["way_type"] == "JSAPI") {
                return $this->render("jsapi", ["data" => $ret]);
            }
            return json_encode($ret);
        }
    }

    /**
     * 余额支付
     * auther GL ctx
     * @return json
     */
    public function actionFundsPay() {
        $post = \Yii::$app->request->post();
//        $this->custNo = "gl00002100";
        if (!isset($post["pay_password"]) || empty($post["pay_password"])) {
            return $this->jsonResult(2, "未输入密码", "");
        }
        $service = new PayService();
        $service->order_code = $post["lottery_order_code"];
        $service->way_type = "YE";
        $service->pay_way = "3";
        $service->cust_no = $this->custNo;
        $service->payPassword = $post["pay_password"];
        $ret = $service->Pay();
        SyncService::syncFromHttp();
        if ($ret !== true) {
            return json_encode($ret);
        }
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
        $service->custType = 1;
        $service->user_id = $this->userId;
        $service->qbH5PayType = $get['qbh5_pay_type'];
        $ret = $service->recharge();
        SyncService::syncFromHttp();
        if ($ret !== true) {
            if ($get["way_type"] == "JSAPI") {
                return $this->render("jsapi", ["data" => $ret, "recharge" => 1]);
            }
            return json_encode($ret);
        }
    }

    /**
     * 获取订单信息
     * auther GL ctx
     * @return json
     */
    public function actionGetorderinfo() {
        $post = \Yii::$app->request->post();
        $lotOrder = LotteryOrder::findOne(["lottery_order_code" => $post["lottery_order_code"]]);
        if ($lotOrder["status"] > 1) {
            return $this->jsonResult(2, "订单已支付", "");
        }
        if ($lotOrder->lottery_additional_id == 0) {
            $betMoney = $lotOrder->bet_money;
            $orderCode = $lotOrder->lottery_order_code;
        } else {
            $lotAddInfo = LotteryAdditional::findOne(["lottery_additional_id" => $lotOrder->lottery_additional_id]);
            $betMoney = $lotAddInfo->total_money;
            $orderCode = $lotAddInfo->lottery_additional_code;
        }
        return $this->jsonResult(600, "获取订单信息", ["total_money" => $betMoney, "order_code" => $orderCode]);
    }

    /**
     * 获取交易记录
     * auther GL ctx
     * @return json
     */
    public function actionGetPayRecord() {
        $post = \Yii::$app->request->post();
        $status = [
            '0' => '未支付',
            '1' => '已支付',
            '2' => '支付失败',
            '3' => '退款成功'
        ];
        $info = (new Query())->select("*")->from("pay_record")->where([
                    "cust_no" => $this->custNo,
                    "order_code" => $post["order_code"]
                ])->one();
        if ($info == null) {
            return $this->jsonResult(2, "未查找到该订单", "");
        }
        $info["status"] = $status[$info["status"]];
        return $this->jsonResult(600, "获取成功", $info);
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
     * 交易明细
     * auther GL ctx
     * @return json
     */
    public function actionGetpayrecordlist() {
        $service = new PayService();
//        $this->custNo = "comorange";
        $service->cust_no = $this->custNo;
        $ret = $service->getPayRecordList();
        return json_encode($ret);
    }

    /**
     * 获取交易状态
     * auther GL ctx
     * @return json
     */
    public function actionGetpaystatus() {
        $post = Yii::$app->request->post();
        $data = (new Query())->select("status")
                ->from("pay_record")
                ->where(["cust_no" => $this->custNo, "order_code" => $post["order_code"]])
                ->one();
        if ($data == null) {
            return $this->jsonResult(2, "获取失败", "");
        }
        $status = [
            '0' => '未支付',
            '1' => '已支付',
            '2' => '支付失败',
            '3' => '退款成功',
            '4' => '订单取消'
        ];
        if ($data['status'] == 0) {
            $data['info'] = '未支付成功.若已付款,可能是银行反应延迟了,请重新检测.有疑问请联系客服.';
        } else {
            $data['info'] = $status[$data["status"]];
        }
        return $this->jsonResult(600, $status[$data["status"]], $data);
    }

    /**
     * 获取开奖结果
     * auther GL ctx
     * @return json
     */
    public function actionGetResult() {
        $result = ResultService::getResult();
        return $this->jsonResult(600, "获取成功", $result, true);
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
                return $this->jsonResult(403, "未设置支付密码", ["able_funds" => $userFunds->able_funds]);
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
    public function actionGetProgrammeDetail() {
        $custNo = $this->custNo;
        $userId = $this->userId;
//        print_r($custNo);die;
//        $custNo = 'gl00004371';
        $request = Yii::$app->request;
        $pId = $request->post('programme_id', '');
        $programmeCode = $request->post('programme_code', '');
        $listType = $request->post('list_type', '');
        if ($listType == '') {
            return $this->jsonError(100, '参数缺失');
        }
        if ($pId == '' && $programmeCode == '') {
            return $this->jsonError(100, '参数缺失');
        }
        if ($pId != '') {
            $where['programme_id'] = $pId;
        }
        if ($programmeCode != '') {
            $where['programme_code'] = $programmeCode;
        }
        $where['cust_no'] = $custNo;
        if (!empty($custNo)) {
            $andWhere = ['>', 'status', 1];
            $withData = ProgrammeUser::find()->select(['sum(bet_money) as bet_money', 'sum(buy_number) as buy_number', 'create_time'])->where($where)->andWhere($andWhere)->asArray()->one();
//            print_r($withData);die;
            if (!empty($withData['bet_money'])) {
                $isWith = 1;
            } else {
                $isWith = 0;
            }
        } else {
            $isWith = 0;
        }
        if ($listType == 1) {
            $data = TogetherService::getListDetail($pId, $userId, $isWith, $programmeCode);
        } elseif ($listType == 2) {
            $data = TogetherService::getSubscribeDetail($pId, $userId, $isWith, $programmeCode);
        }
        if ($data['code'] != 600) {
            return $this->jsonError($data['code'], $data['msg']);
        }
        $detailList = $data['data'];
        if (empty($withData['bet_money'])) {
            $detailList['with_number'] = 0;
            $detailList['with_money'] = 0;
            $detailList['with_time'] = '';
        } else {
            $detailList['with_number'] = $withData['buy_number'];
            $detailList['with_money'] = $withData['bet_money'];
            $detailList['with_time'] = $withData['create_time'];
        }
        $dataList['data'] = $detailList;
        return $this->jsonResult(600, '方案详情', $dataList);
    }

    /**
     * 认购方案
     * @auther GL ctx
     * @return type
     */
    public function actionBuyProgrammme() {
        $post = \Yii::$app->request->post();
        $custNo = $this->custNo;
        $user = (new Query())->select("user_name")->from("user")->where(["cust_no" => $custNo])->one();
        $betMoney = $post["bet_money"];
        $betNums = $post['bet_nums'];
        $programmeCode = $post["programme_code"];
        $programme = Programme::findOne(["programme_code" => $programmeCode]);
        ($betMoney <= 0 || $betNums <= 0) && $this->jsonError(109, '金额或数量不能小于');
        if ($programme->programme_last_amount < $betMoney) {
            return $this->jsonResult(109, "认购金额超过剩余合买金额", "");
        }
        $preMoney = floatval($programme->programme_univalent) * $betNums;
        if ($preMoney != $betMoney) {
            return $this->jsonError(109, '认购金额不对');
        }
        $programmeUser = new ProgrammeUser();
        $programmeUser->expert_no = $programme->expert_no;
        $programmeUser->store_id = $programme->store_id;
        $programmeUser->programme_id = $programme->programme_id;
        $programmeUser->bet_money = $betMoney;
        $programmeUser->buy_number = $betNums;
        $programmeUser->programme_code = $programmeCode;
        $programmeUser->lottery_code = $programme->lottery_code;
        $programmeUser->lottery_name = $programme->lottery_name;
        $programmeUser->buy_type = 1;
        $programmeUser->programme_user_code = Commonfun::getCode("FL", "G");
        $programmeUser->cust_no = $custNo;
        $programmeUser->user_id = $this->userId;
        $programmeUser->user_name = $user["user_name"];
        $programmeUser->status = 1; //未支付状态
        $programmeUser->create_time = date("Y-m-d H:i:s");
        $paySer = new PayService();
        $paySer->productPayRecord($custNo, $programmeUser->programme_user_code, 5, 1, $betMoney, 2);
        if ($programmeUser->validate()) {
            $ret = $programmeUser->save();
            if ($ret === false) {
                return $this->jsonResult(109, "数据错误", "");
            }
        } else {
            return $this->jsonResult(109, "数据错误", $programmeUser->getFirstErrors());
        }
        return $this->jsonResult(600, "合买下单成功", ["programme_user_code" => $programmeUser->programme_user_code]);
    }

    /**
     * 定制跟单
     * @auther GL zyl
     * @return type
     * @throws Exception
     */
    public function actionSetCustomMade() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004371';
        $request = Yii::$app->request;
        $expertNo = $request->post('expert_no', '');
        $codes = $request->post('lottery_code', '');
        $type = $request->post('type', '');
        $madeNums = $request->post('made_nums', '');
        $madeAmount = $request->post('made_amount', '');
        $funds = $request->post('lowest_funds', '');
        $madeSpeed = $request->post('made_percent', '');
        $maxAmount = $request->post('max_amount', 0);
        $format = date('Y-m-d H:i:s');
        if ($expertNo == '' || $type == '' || $codes == '' || $madeNums == '') {
            return $this->jsonError(100, '参数缺失');
        }
        if ($type == 1) {
            if ($madeAmount == '') {
                return $this->jsonError(100, '请填写认购份额');
            }
        } elseif ($type == 2) {
            if ($madeSpeed == '' || $maxAmount == '') {
                return $this->jsonError(100, '请确定跟单百分比和最大跟单额');
            }
        }
        $db = Yii::$app->db;
        $train = $db->beginTransaction();
        try {
            $DIYFollow = DiyFollow::find()->where(['cust_no' => $custNo, 'expert_no' => $expertNo])->one();
            if (empty($DIYFollow)) {
                $expertData = ExpertLevel::find()->where(['cust_no' => $expertNo])->one();
                if (empty($expertData)) {
                    throw new Exception('该方案的提供者暂时无法定制跟单');
                }
                $updateStr = "update expert_level set made_nums = made_nums + 1, modify_time = '" . $format . "' where  expert_level_id = {$expertData->expert_level_id}; ";
                $upId = $db->createCommand($updateStr)->execute();
                if ($upId === false) {
                    throw new Exception('方案提供者的数据更新失败');
                }
                $DIYFollow = new DiyFollow();
                $DIYFollow->create_time = $format;
            } else {
                $DIYFollow->modify_time = $format;
            }
            $DIYFollow->expert_no = $expertNo;
            $DIYFollow->cust_no = $custNo;
            $DIYFollow->lottery_codes = $codes;
            $DIYFollow->follow_num = $madeNums;
            $DIYFollow->follow_type = $type;
            $DIYFollow->bet_nums = $madeAmount;
            $DIYFollow->follow_percent = $madeSpeed;
            $DIYFollow->max_bet_money = $maxAmount;
            $DIYFollow->stop_money = $funds;
            if (!($DIYFollow->validate())) {
                throw new Exception('定制验证失败');
            }
            if (!($DIYFollow->save())) {
                throw new Exception('定制写入失败');
            }
            $train->commit();
            return $this->jsonError(600, '定制成功');
        } catch (Exception $ex) {
            $train->rollBack();
            return $this->jsonError(109, $ex->getMessage());
        }
    }

    /**
     * 获取定制跟单列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetCustomMade() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004371';
        $request = Yii::$app->request;
        $madeList = [];
        $page = $request->post('page_num', 1);
        $size = $request->post('page_size', 10);
        $total = DiyFollow::find()->where(['cust_no' => $custNo])->count();
        $pages = ceil($total / $size);
        $data = DiyFollow::find()->select(['diy_follow.follow_type', 'diy_follow.follow_num', 'diy_follow.bet_nums', 'diy_follow.follow_percent', 'u.user_name', 'el.cust_no', 'el.level'])
                ->leftJoin('expert_level as el', 'diy_follow.expert_no = el.cust_no')
                ->leftJoin('user as u', 'u.cust_no = el.cust_no')
                ->where(['diy_follow.cust_no' => $custNo])
                ->limit($size)
                ->offset(($page - 1) * $size)
                ->orderBy('diy_follow.create_time desc')
                ->asArray()
                ->all();
        $madeList['page'] = $page;
        $madeList['size'] = count($data);
        $madeList['pages'] = $pages;
        $madeList['total'] = $total;
        $madeList['data'] = $data;
        return $this->jsonResult(600, '方案列表', $madeList);
    }

    /**
     * 定制跟单
     * @auther GL zyl
     * @return type
     */
    public function actionCustomMadeDetail() {
        $custNo = $this->custNo;
//         $custNo = 'gl00004371';
        $request = Yii::$app->request;
        $info = [];
        $expertNo = $request->post('expert_no', '');
        if ($expertNo == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $data = TogetherService::getExpertInfo($expertNo, $custNo);
        if (empty($data)) {
            return $this->jsonError(109, "抱歉！该方案所属门店不存在");
        }
        $info['data'] = $data;
        return $this->jsonResult(600, '定制跟单', $info);
    }

    /**
     * 取消定制
     * @auther GL zyl
     * @return type
     * @throws Exception
     */
    public function actionCancelCustomMade() {
        $custNo = $this->custNo;
        $request = Yii::$app->request;
        $expertNo = $request->post('expert_no', '');
        if ($expertNo == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $format = date('Y-m-d H:i:s');
        $db = Yii::$app->db;
        $train = $db->beginTransaction();
        try {
            $del = DiyFollow::deleteAll(['cust_no' => $custNo, 'expert_no' => $expertNo]);
            if (!$del) {
                throw new Exception('取消定制失败');
            }
            $updateStr = "update expert_level set made_nums = made_nums - 1, modify_time = '" . $format . "' where  cust_no = '" . $expertNo . "'; ";
            $upId = $db->createCommand($updateStr)->execute();
            if ($upId === false) {
                throw new Exception('方案提供者的数据更新失败');
            }
            $train->commit();
            return $this->jsonResult(600, '取消定制成功', true);
        } catch (Exception $ex) {
            $train->rollBack();
            return $this->jsonError(109, $ex->getMessage());
        }
    }

    /**
     * 获取跟单人员列表
     * @auther GL zyl
     * @return type
     */
    public function actionGetWithPeople() {
        $request = Yii::$app->request;
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $programmeId = $request->post('programme_id', '');
        $programmeCode = $request->post('programme_code', '');
        if ($programmeId == '' && $programmeCode == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $withList = TogetherService::getWithPeople($page, $size, $programmeId, $programmeCode);
        return $this->jsonResult(600, '跟单人员', $withList);
    }

    /**
     * 余额明细
     * @auther GL ctx
     * @return json
     */
    public function actionGetTransDetail() {
        $post = \Yii::$app->request->post();
        $custNo = $this->custNo;
        $expenditureTypeArr = Constants::EXPENDITURE_TYPE;
        $size = isset($post["page_size"]) ? $post["page_size"] : 10;
        $page = isset($post["page_num"]) ? $post["page_num"] : 0;

        $query = (new Query())->select("cust_no,order_code,pay_name,way_name,pay_money,pay_pre_money,pay_type,pay_type_name,body,balance,status,create_time")->from("pay_record")->where([
                    "cust_no" => $custNo
                ])->andWhere(["in", "status", [1, 3]])->andWhere(['or', ["pay_way" => 3], ["in", "pay_type", [2, 3, 4]]]);
        if (isset($post["pType"]) && !empty($post["pType"])) {
            if ($post["pType"] == 1) {
                $query = $query->andWhere(["or", ["not in", "pay_type", $expenditureTypeArr], ["status" => 3]]);
            } else {
                $query = $query->andWhere(["and", ["in", "pay_type", $expenditureTypeArr], ["!=", "status", 3]]);
            }
        }
        $total = $query->count();
        $infos = $query->orderBy("modify_time desc,pay_record_id desc")->offset(($page - 1) * $size)->limit($size)->all();
        foreach ($infos as &$val) {
            if ($val["status"] == 3) {
                $val["pay_type"] = "6";
            }
            if ($val['pay_type'] == 4) {
                $val['pay_pre_money'] = $val['pay_money'];
            }
            if (in_array($val["pay_type"], $expenditureTypeArr) && $val["status"] == 1) {
                $val["pay_money"] = "-" . $val["pay_pre_money"];
            } else {
                $val["pay_money"] = "+" . $val["pay_pre_money"];
            }
        }
        $count = count($infos);
        $data = ['page_num' => $page, 'records' => $infos, 'size' => $count, 'pages' => ceil($total / $size), 'total' => $total];
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取支付方式
     * @auther GL zyl
     * @return type
     */
    public function actionGetPayType() {
        $userId = $this->userId;
        $payType = PayType::find()->select(['pay_type', 'pay_type_code', 'pay_type_name', 'remark', 'default'])->where(['status' => 2, 'parent_id' => 0])->orderBy('default desc, pay_type_sort desc, pay_type desc')->asArray()->all();
        if (empty($payType)) {
            return $this->jsonError(109, '暂时不支持任何支付，请稍后再试');
        }
        $userType = User::find()->select(['user_type', 'user_funds.able_funds as pay_available_balance'])
                        ->leftJoin('user_funds', 'user_funds.user_id=user.user_id')
                        ->where(['user.user_id' => $userId])->asArray()->one();
        $data = [];
        if ($userType['user_type'] == 3) {
            foreach ($payType as $key => $val) {
                if ($val['pay_type'] != 3) {
                    $data[] = $val;
                }
            }
        } else {
            $data = $payType;
        }
        foreach ($data as $k => $v) {
            if ($data[$k]['pay_type'] == 3) {
                if (empty($userType['pay_available_balance'])) {
                    $userType['pay_available_balance'] = 0;
                }
                $data[$k]['pay_available_balance'] = $userType['pay_available_balance'] . ' 元';
            } else {
                $data[$k]['pay_available_balance'] = '';
            }
        }
        $ret['data'] = $data;
        return $this->jsonResult(600, '支付方式', $ret);
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
//        $custNo = 'gl00002100';
//        $storeId = $post['store_id'];
        $storeNo = $post['store_id'];
        if (empty($storeNo)) {
            return $this->jsonError(109, '出票门店未设置');
        }
        $store = Store::find()->select(['user_id', 'store_code', 'sale_lottery', 'business_status'])->where(['store_code' => $storeNo, 'status' => 1])->asArray()->one();
        if (empty($store)) {
            return $this->jsonError(109, '设置的出票门店不存在');
        }
        if ($store['business_status'] != 1) {
            return $this->jsonError(2, '该门店已暂停营业！！');
        }
        $storeId = $store['user_id'];
        $lotteryCode = $post['lottery_code'];
        $saleLotteryArr = explode(',', $store['sale_lottery']);
        if (in_array(3000, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3006', '3007', '3008', '3009', '3010', '3011');
        }
        if (in_array(3100, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3001', '3002', '3003', '3004', '3005');
        }
        if (!in_array($lotteryCode, $saleLotteryArr)) {
            return $this->jsonError(488, '你所购买的彩种，该门店不可接单！');
        }
        $user = \app\modules\user\models\User::findOne(['cust_no' => $custNo]);
        $userFunds = \app\modules\common\models\UserFunds::findOne(['cust_no' => $custNo]);
        if (empty($userFunds->pay_password)) {
            return $this->jsonResult(403, '未设置支付密码', '');
        }
        if (md5($post['pay_password']) != $userFunds->pay_password) {
            return $this->jsonError(406, '密码错误');
        }
        $onePrice = intval($post['total']) / intval($post['programme_all_number']);
        if (!is_int($onePrice)) {
            return $this->jsonError(109, '份额配比不对');
        }
//        $owner_buy_number = ceil($post["total"] * 0.1);
//        if ($owner_buy_number < $post['owner_buy_number']) {
//            $owner_buy_number = $post['owner_buy_number'];
//        }
        if ($post['owner_buy_number'] > $post["total"]) {
            return $this->jsonError(109, '自购金额错误');
        }
        $payAmount = ($post['minimum_guarantee'] + $post['owner_buy_number']) * $onePrice;
        if ($userFunds->able_funds <= $payAmount) {
            return $this->jsonError(407, '余额不足');
        }
        if (isset($post["lottery_type"])) {
            $programme = new \app\modules\common\services\ProgrammeService;
            $ret = $programme->addProgramme($post, $onePrice, $custNo, $storeId, $payAmount, $user->user_name, 1, $userId, $store['store_code']);
        } else {
            return $this->jsonError(109, '未设置彩票类型');
        }
    }

    /**
     * 预发布方案
     * @auther GL ctx 
     * @return json
     */
    public function actionAddPreProgramme() {
        $post = Yii::$app->request->post();
        $custNo = $this->custNo;
        $userId = $this->userId;
        $storeNo = $post['store_id'];
        if (empty($storeNo)) {
            return $this->jsonError(109, '出票门店未设置');
        }
//        $store = Store::find()->select(['store_id', 'store_code', 'sale_lottery', 'business_status'])->where(['user_id' => $storeId])->asArray()->one();
        $store = Store::find()->select(['user_id', 'store_code', 'sale_lottery', 'business_status'])->where(['store_code' => $storeNo, 'status' => 1])->asArray()->one();
        if (empty($store)) {
            return $this->jsonError(488, '设置的出票门店不存在');
        }
        if ($store['business_status'] != 1) {
            return $this->jsonError(2, '该门店已暂停营业！！');
        }
        $storeId = $store['user_id'];
        $lotteryCode = $post['lottery_code'];
        $saleLotteryArr = explode(',', $store['sale_lottery']);
        if (in_array(3000, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3006', '3007', '3008', '3009', '3010', '3011');
        }
        if (in_array(3100, $saleLotteryArr)) {
            array_push($saleLotteryArr, '3001', '3002', '3003', '3004', '3005');
        }
        if (!in_array($lotteryCode, $saleLotteryArr)) {
            return $this->jsonError(109, '你所购买的彩种，该门店不可接单！');
        }
        $onePrice = intval($post['total']) / intval($post['programme_all_number']);
        if (!is_int($onePrice)) {
            return $this->jsonError(109, '份额配比不对');
        }
        $user = \app\modules\user\models\User::findOne(['cust_no' => $custNo]);
        $userFunds = \app\modules\common\models\UserFunds::findOne(['cust_no' => $custNo]);
        if (empty($userFunds->pay_password)) {
            return $this->jsonResult(403, '未设置支付密码', '');
        }
        if (md5($post['pay_password']) != $userFunds->pay_password) {
            return $this->jsonError(406, '密码错误');
        }

//        $owner_buy_number = ceil($post["total"] * 0.1);
//        if ($owner_buy_number < $post['owner_buy_number']) {
//            $owner_buy_number = $post['owner_buy_number'];
//        }
        $payAmount = ($post['minimum_guarantee'] + $post['owner_buy_number']) * $onePrice;
        if ($userFunds->able_funds <= $payAmount) {
            return $this->jsonError(407, '余额不足');
        }
        $programme = new \app\modules\common\services\ProgrammeService;
        $programme->addPreProgramme($post, $onePrice, $custNo, $storeId, $payAmount, $user->user_name, 1, $userId, $store['store_code']);
    }

    /**
     * 预发布方案上传投注内容
     * @auther GL ctx 
     * @return json
     */
    public function actionPlayProgrammeVal() {
        $post = Yii::$app->request->post();
        $custNo = $this->custNo;
        if (!isset($post["programme_code"])) {
            return $this->jsonResult(109, "参数缺失", "");
        }
        $programmeCode = $post["programme_code"];
        $programme = new \app\modules\common\services\ProgrammeService;
        $ret = $programme->playBetVal($custNo, $programmeCode, $post);
    }

    /**
     * 中奖历史合买方案
     * @return type
     */
    public function actionWinHistoryProgrammes() {
        $post = Yii::$app->request->post();
        if (isset($post["page_num"])) {
            $pageNum = $post["page_num"];
        } else {
            $pageNum = 1;
        }
        if (isset($post["page_size"])) {
            $size = $post["page_size"];
        } else {
            $size = 10;
        }
        $programme = new \app\modules\common\services\ProgrammeService;
        $data = $programme->getWinHistoryProgrammes($post["expert_no"], $pageNum, $size);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 最近合买方案
     * @return type
     */
    public function actionRecentProgrammes() {
        $post = Yii::$app->request->post();
        if (isset($post["page_num"])) {
            $pageNum = $post["page_num"];
        } else {
            $pageNum = 1;
        }
        if (isset($post["page_size"])) {
            $size = $post["page_size"];
        } else {
            $size = 10;
        }
        $programme = new \app\modules\common\services\ProgrammeService;
        $data = $programme->getRecentProgrammes($post["expert_no"], $pageNum, $size);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取彩种信息
     * @auther GL zyl
     */
    public function actionGetLottery() {
        $lotteryList = Lottery::find()->select(['lottery.lottery_code', 'lottery.lottery_name', 'lottery.status', 'lottery.sale_status', 'lottery.lottery_pic', 'lottery.description', 'lr.lottery_time', 'lottery.lottery_sort'])
                ->leftJoin('lottery_record lr', 'lr.lottery_code = lottery.lottery_code and lr.status = 1')
                ->where(['lottery.status' => 1])
                ->groupBy('lottery_code')
                ->orderBy('lottery.lottery_sort, lottery.lottery_code')
                ->asArray()
                ->all();
        $fucai = Constants::FUCAI_LOTTERY;
        $ticai = Constants::TICAI_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bd = CompetConst::MADE_BD_LOTTERY;
        $wc = CompetConst::MADE_WCUP_LOTTERY;
        foreach ($lotteryList as $key => &$val) {
            if ($val['sale_status'] == 0) {
                $val['description'] = '暂停销售';
            }
            if (in_array($val['lottery_code'], $football)) {
                unset($lotteryList[$key]);
            }
            if (in_array($val['lottery_code'], $basketball)) {
                unset($lotteryList[$key]);
            }
            if (in_array($val['lottery_code'], $bd)) {
                unset($lotteryList[$key]);
            }
            if (in_array($val['lottery_code'], $wc)) {
                unset($lotteryList[$key]);
            }
            if (in_array($val['lottery_code'], $fucai)) {
                $val['type'] = 1;
            } elseif (in_array($val['lottery_code'], $ticai)) {
                $val['type'] = 2;
            }
            $val['lottery_time'] = date('Y-m-d', strtotime($val['lottery_time']));
        }
        $data['data'] = array_values($lotteryList);
        return $this->jsonResult(600, '获取成功', $data);
    }

     /**
     * 获取竞彩的历史开奖数据
     * @auther GL zyl
     * @return type
     */
    public function actionGetBallResult() {
        $request = \Yii::$app->request;
        $dateStr = $request->post('date', '');
        $lotteryCode = $request->post('lotteryCode', '3000');
//        if ($dateStr == '') {
//            $dateStr = date('Ymd', strtotime('-3 day'));
//        }  else {
//            $dateStr = date('Ymd', strtotime('-1 day'));
//        }
        if ($lotteryCode == '3100') {
            $data = ResultService::getBastketResult($dateStr);
        } elseif ($lotteryCode == 3000) {
            $data = ResultService::getFootballResult($dateStr);
        }
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 历史交锋统计
     * @auther GL ctx
     * @return json
     */
    public function actionGetHistoryCount() {
        $request = \Yii::$app->request;
        $scheduleMid = $request->post("schedule_mid");
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getHistoryCount($scheduleMid);
        if ($data['code'] == 109) {
            return $this->jsonResult(600, $data['msg'], null);
        }
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 赛程信息
     * @auther GL ctx
     * @return json
     */
    public function actionScheduleInfo() {
        $request = \Yii::$app->request;
        $scheduleMid = $request->post("schedule_mid");
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getScheduleInfo($scheduleMid);
        if ($data['data'] == null) {
            return $this->jsonResult(600, "获取成功", $data['data']);
        }
        return $this->jsonResult(600, "获取成功", ["scheduel_info" => $data['data']['info'], "scheduel_result" => $data['data']['result']]);
    }

    /**
     * 双方历史交战比赛
     * @auther GL ctx
     * @return json
     */
    public function actionDoubleHistoryMatch() {
        $post = \Yii::$app->request->post();
        $mid = $post["schedule_mid"];
        $teamType = isset($post["team_type"]) ? $post["team_type"] : "";
        $size = isset($post["size"]) ? $post["size"] : 10;
        $sameLeague = isset($post["same_league"]) ? $post["same_league"] : "";
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getDoubleHistoryMatch($mid, $teamType, $size, $sameLeague);
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 历史交战比赛
     * @auther GL ctx
     * @return json
     */
    public function actionHistoryMatch() {
        $post = \Yii::$app->request->post();
        $mid = $post["schedule_mid"];
        $teamMid = $post["team_mid"];
        $teamType = isset($post["team_type"]) ? $post["team_type"] : "";
        $sameLeague = isset($post["same_league"]) ? $post["same_league"] : "";
        $size = isset($post["size"]) ? $post["size"] : 10;
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getHistoryMatch($mid, $teamMid, $size, $sameLeague, $teamType);
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 实力对比
     * @auther GL ctx
     * @return json
     */
    public function actionStrengthContrast() {
        $request = \Yii::$app->request;
        $mid = $request->post("schedule_mid");
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getStrengthContrast($mid);
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 球队未来赛程
     * @return json
     */
    public function actionGetFutureSchedule() {
        $request = \Yii::$app->request;
        $mid = $request->post("schedule_mid");
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getFutureSchedule($mid);
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 获取预测赛果
     * @return json
     */
    public function actionGetPreResult() {
        $request = \Yii::$app->request;
        $mid = $request->post("schedule_mid");
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getPreResult($mid);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取亚盘
     * @return json
     */
    public function actionGetAsianHandicap() {
        $request = \Yii::$app->request;
        $mid = $request->post("schedule_mid");
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getAsianHandicap($mid);
        return $this->jsonResult(600, "获取成功", $data, true);
    }

    /**
     * 获取欧赔
     * @return json
     */
    public function actionGetEuropeOdds() {
        $request = \Yii::$app->request;
        $mid = $request->post("schedule_mid");
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getEuropeOdds($mid);
        return $this->jsonResult(600, "获取成功", $data, true);
    }

    /**
     * 获取实况信息
     * @return json
     */
    public function actionGetScheduleLives() {
        $request = \Yii::$app->request;
        $mid = $request->post("schedule_mid");
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getScheduleLives($mid);
        return $this->jsonResult(600, "实况获取成功", $data);
    }

    /**
     * 获取首页竞彩
     * @auther GL zyl
     * @param not null s_status 状态0:即时 1:赛果2：赛程 
     * @param not null league_id
     * @param not null page
     * @param not null size
     * @return type
     */
    public function actionGetCompting() {
        $request = Yii::$app->request;
        $type = $request->post('s_status', 0);
        $league = $request->post('league_id');
        $date = $request->post('s_date', '');
        $mids = $request->post('mids', []);
        $page = $request->post('page', 1);
        $size = $request->post('size', 100);
        $payType = $request->post('pay_type', '');
        $actionType = $request->post('action_type', 1);
        $where = $lWhere = $sWhere = $gWhere = $eWhere = [];
        $service = new ScheduleService();
//        
        if (!empty($league)) {
            if(!is_array($league)) {
                $league = json_decode($league, true);
            }
            $lWhere = ['in', 'schedule.league_id', $league];
        }
        if ($type == 0) {//即时
            $sWhere = ['in', 'sr.status', [0, 1, 4]];
            $orderBy = 'schedule.schedule_date,schedule.start_time, schedule.schedule_mid';
            $data = $service->getScheduleList($page, $size, $where, $lWhere, $sWhere, $orderBy, $gWhere, $eWhere, $payType);
        } elseif ($type == 1) {//赛果
            $where = ['in', 'sr.status', [2, 3, 6, 7]];
            $orderBy = 'schedule.start_time desc,schedule.schedule_mid desc';
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-7 day'));
            $gWhere = ['>=', 'start_time', $startDate . ' 00:00:00'];
            $eWhere = ['<', 'start_time', $endDate . ' 23:59:59'];
            $data = $service->getEndScheduleList($page, $size, $where, $lWhere, $sWhere, $orderBy, $gWhere, $eWhere, $payType);
        } elseif ($type == 2) {//赛程
            $data = $service->getNoEndSchedule($page, $size, $lWhere, $date, $payType);
        } elseif ($type == 3) {//关注
            if(!is_array($mids)) {
                $mids = json_decode($mids, true);
            }
            $sWhere = ['in', 'sr.schedule_mid', $mids];
            $orderBy = 'schedule.schedule_date, schedule.schedule_mid';
            $data = $service->getAttentionList($sWhere, $payType);
        } elseif ($type == 5) {//代理商（未知）比赛中
            $sWhere = ['sr.status' => 1];
            $orderBy = 'schedule.schedule_date, schedule.schedule_mid';
            $data = $service->getScheduleList($page, $size, $where, $lWhere, $sWhere, $orderBy, $gWhere, $eWhere, $payType);
        }elseif ($type == 10) {//TV特有
            if(empty($date)) {
                $startDate = date('Y-m-d 00:00:00');
                $endDate = date('Y-m-d 23:59:59');
            } else {
                $startDate = $date . ' 00:00:00';
                $endDate = $date . ' 23:59:59';
            }
//            $startDate = date('Y-m-d', strtotime('-5 day'));
            $gWhere = ['>=', 'start_time', $startDate];
            $eWhere = ['<=', 'start_time', $endDate];
            $orderBy = 'schedule.schedule_date,schedule.start_time, schedule.schedule_mid';
            $data = $service->getScheduleList($page, $size, $where, $lWhere, $sWhere, $orderBy, $gWhere, $eWhere, $payType);
        } elseif ($type == 11) {//新首页
            $orderBy = 'schedule.start_time,schedule.schedule_mid';
            if(empty($date)) {
                $date = date('Y-m-d');
            } 
            $data = $service->getNewZuScheduleList($page, $size, $date, $lWhere, $payType, $actionType);
        } else {
            return $this->jsonError(109, '参数错误');
        }
        if ($type == 2) {
            foreach ($data['data']['scheDetail'] as $item) {
                if ($item['hot_status'] == 1) {
                    $data['data']['hotSchedule'][] = $item;
                } else {
                    $data['data']['plainSchedule'][] = $item;
                }
            }
            unset($data['data']['scheDetail']);
        }
//        $attention['count'] = 0;
//        if ($this->userId) {
//            $list = [];
//            $attenArr = UserAttention::find()->select(['schedule_mid'])->where(['user_id' => $this->userId])->asArray()->all();
//            if (!empty($attenArr)) {
//                foreach ($attenArr as $val) {
//                    $list[] = $val['schedule_mid'];
//                }
//            }
//            $attention['count'] = count($attenArr);
//            $attention['attenArr'] = $list;
//        }
//        $data['data']['attenList'] = [];

        return $this->jsonResult(600, '竞彩列表', $data);
    }

    /**
     * 获取冻结金额记录
     * @return json
     */
    public function actionGetIceRecords() {
        $post = \Yii::$app->request->post();
        $params = $post;
        $params["cust_no"] = $this->custNo;
        $fundsSer = new \app\modules\common\services\FundsService();
        $data = $fundsSer->getIceRecord($params);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 赛程关注操作
     * @auther GL zyl
     * @return type
     */
    public function actionAttention() {
        $userId = $this->userId;
//        $userId = 66;
        $request = Yii::$app->request;
        $mid = $request->post('mid', '');
        $type = $request->post('operate_type', '');
        if ($mid == '' || $type == '') {
            return $this->jsonError(100, '参数缺失');
        }
        $service = new ScheduleService();
        if ($type == 0) {
            $data = $service->deleteAttention($userId, $mid);
        } elseif ($type == 1) {
            $data = $service->setAttention($userId, $mid);
        } else {
            return $this->jsonError(109, '参数错误');
        }
        return $this->jsonResult($data['code'], $data['msg'], true);
    }

    /**
     * 追加保底金额
     * @return json
     */
    public function actionAppendGuarantee() {
        $post = \Yii::$app->request->post();
        $userFunds = \app\modules\common\models\UserFunds::findOne(['cust_no' => $this->custNo]);
        if (empty($userFunds->pay_password)) {
            return $this->jsonResult(403, '未设置支付密码', '');
        }
        if (md5($post['pay_password']) != $userFunds->pay_password) {
            return $this->jsonError(406, '密码错误');
        }
        $proSer = new \app\modules\common\services\ProgrammeService();
        $ret = $proSer->appendMinimumGuarantee($this->custNo, $post["programme_code"], $post["money"]);
        if ($ret === true) {
            SyncService::syncFromHttp();
            return $this->jsonResult(600, "追加成功", "");
        }
        return $this->jsonResult(109, "追加失败", "");
    }

    /**
     * 获取专家合买统计
     * @return type
     */
    public function actionGetProgrammeCount() {
        $post = \Yii::$app->request->post();
        $expertNo = $post["expert_no"];
        $field = ['user.cust_no', 'user.user_name', 'user.user_pic as userPic', 'el.level', 'el.level_name', 'el.made_nums', 'el.win_nums', 'el.issue_nums', 'el.succ_issue_nums', 'el.win_amount'];
        $info = User::find()->select($field)
                ->innerJoin('expert_level as el', 'el.user_id = user.user_id')
                ->where(['user.cust_no' => $expertNo])
                ->asArray()
                ->one();
        $info['succ_issue_rate'] = round(floatval($info['succ_issue_nums']) / floatval($info['issue_nums']) * 100, 2);
        $goodInfos = Programme::find()->select(["lottery_code"])->where(["expert_no" => $expertNo])->andWhere(["status" => 6])->groupBy("lottery_code")->asArray()->all();
        $info["goodCode"] = [];
        $info["goodName"] = [];
        $lotterys = Constants::LOTTERY;
        foreach ($goodInfos as $val) {
            if ($val["lottery_code"] >= '3000' && $val["lottery_code"] <= '3012') {
                $code = '3000';
            } else {
                $code = $val["lottery_code"];
            }
            if (!in_array($code, $info["goodCode"])) {
                $info["goodCode"][] = $code;
                $info["goodName"][] = $lotterys[$code];
            }
        }
        $data['data'] = $info;
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 任选赛程
     * @auther GL zyl
     * @return type
     */
    public function actionOptionalSchedule() {
        $optionalService = new OptionalService();
        $data = $optionalService->getSchedule();
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, '获取成功', $data['data']);
    }

    /**
     * 取消追期
     * @auther GL zyl
     * @return type
     */
    public function actionCancelTrace() {
        $custNo = $this->custNo;
        $request = Yii::$app->request;
        $additionalId = $request->post('additional_id', '');
        if (empty($additionalId)) {
            return $this->jsonError(100, '参数缺失');
        }
        $additional = LotteryAdditional::find()->where(['lottery_additional_id' => $additionalId, 'cust_no' => $custNo, 'status' => 2, 'pay_status' => 1])->asArray()->one();
        if (empty($additional)) {
            return $this->jsonError(109, '此追期单暂时无法取消追期,请稍后再试');
        }
        if (intval($additional['chased_num']) >= intval($additional['periods_total'])) {
            return $this->jsonError(109, '此追期单已完成所有追期，不可取消');
        }
        $lastNums = intval($additional['periods_total']) - intval($additional['chased_num']);
        $lastMoney = $lastNums * floatval($additional['bet_money']);
        $funds = new FundsService();
        $userFunds = $funds->operateUserFunds($custNo, 0, $lastMoney, -$lastMoney, true, '追期-解冻');
        if ($userFunds['code'] != 0) {
            return $this->jsonError(109, '冻结资金有误,请联系客服');
        }
        $funds->iceRecord($custNo, 1, $additional['lottery_additional_code'], $lastMoney, 2, "追期-解冻");
        $model = LotteryAdditional::findOne(['lottery_additional_id' => $additionalId]);
        $model->status = 0;
        $model->modify_time = date('Y-m-d H:i:s');
        if ($model->save() === false) {
            return $this->jsonError(109, '取消失败');
        }
        SyncService::syncFromHttp();
        return $this->jsonResult(600, '取消成功', true);
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
        $classCopeting = new OptionalService();
        $ret = $classCopeting->getOptionalOrder($orderCode, $this->custNo);
        if ($ret['code'] != 600) {
            return $this->jsonError(109, $ret['data']);
        }
        $data['data'] = $ret['result'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取订单票根图片
     * @auther  GL zyl
     * @return type
     */
    public function actionGetOrderImg() {
        $request = Yii::$app->request;
        $orderId = $request->post('order_id', '');
        if (empty($orderId)) {
            return $this->jsonError(100, '参数缺失');
        }
        $imgData = OutOrderPic::find()->select(['out_order_pic_id', 'order_img1', 'order_img2', 'order_img3', 'order_img4'])->where(['order_id' => $orderId])->asArray()->one();
        if (empty($imgData)) {
            $imgData = [];
        }
        $data['data'] = $imgData;
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取门店认证信息
     * @auther GL zyl
     * @return type
     */
    public function actionGetReviewInfo() {
        $custNo = $this->custNo;
        $query = new Query;
        $reviewData = $query->select(['s.cert_status', 's.store_name', 's.province', 's.city', 's.area', 's.address', 's.coordinate', 's.open_time', 's.close_time', 's.consignment_type', 'd.consignee_name',
                    'd.consignee_card', 'd.sports_consignee_code', 'd.welfare_consignee_code', 'd.company_name', 'd.business_license', 'd.old_owner_name', 'd.old_owner_card', 'd.consignee_img', 'd.consignee_img2',
                    'd.consignee_card_img1', 'd.consignee_card_img2', 'd.consignee_card_img3', 'd.consignee_card_img4', 'd.old_owner_card_img1', 'd.old_owner_card_img2', 'd.business_license_img', 'd.competing_img',
                    'd.football_img', 'd.sports_nums_img', 'd.sports_fre_img', 'd.north_single_img', 'd.welfare_nums_img', 'd.welfare_fre_img', 's.real_name_status', 's.status'])
                ->from('store as s')
                ->leftJoin('store_detail as d', 'd.store_id = s.store_id')
                ->where(['s.cust_no' => $custNo])
                ->one();
        if (empty($reviewData)) {
            $reviewData = null;
        }
        return $this->jsonResult(600, '认证基础信息', $reviewData);
    }

    /**
     * 门店认证基础信息
     * @auther GL zyl
     * @return type
     */
    public function actionSetReviewInfo() {
        $request = Yii::$app->request;
        $custNo = $this->custNo;
        $userId = $this->userId;
        $user = User::findOne(['user_id' => $userId]);
        $openTime = $request->post('open_time', '');
        $closeTime = $request->post('close_time', '');
        $remark = $request->post('store_remark', '');
        $type = $request->post('store_type', '');
        $sName = $request->post('store_name', '');
        $cName = $request->post('consignee_name', '');
        $cCard = $request->post('consignee_card', '');
        $scCode = $request->post('sport_code', '');
        $wcCode = $request->post('welfare_code', '');
        $sProvince = $request->post('province', '');
        $sCity = $request->post('city', '');
        $sArea = $request->post('area', '');
        $sAddress = $request->post('address', '');
        $sCoordinate = $request->post('coordinate', '');
        $supportBonus = $request->post('support_bonus', '');
        $oldName = $request->post('old_owner_name');
        $oldCard = $request->post('old_owner_card');
//        $nowName = $request->post('now_owner_name');
//        $nowCard = $request->post('now_owner_card');
        $fName = $request->post('firm_name', '');
        $fLicense = $request->post('firm_license', '');
//        $oName = $request->post('operator_name', '');
//        $oCard = $request->post('operator_card', '');
        $saleType = $request->post('consignment_type', '');
        $saleLottery = $request->post('sale_lottery', '');
        if ($user->authen_status == 1) {
            $realData = $this->userService->javaGetRealName($custNo);
            $cName = $realData['data']['realName'];
            $cCard = $realData['data']['cardNo'];
        }
        $storeModel = Store::find()->where(['user_id' => $userId, 'status' => 1])->one();
        if (empty($storeModel)) {
            $storeModel = new Store;
            $detailModel = new StoreDetail;
            $storeCode = Store::find()->select(['max(store_code) as store_code'])->asArray()->one();
            if (empty($storeCode['store_code'])) {
                $storeCode = 10001;
            } else {
                $storeCode = intval($storeCode['store_code']) + 1;
            }
            $storeModel->store_code = $storeCode;
            $storeModel->user_id = $userId;
            $storeModel->cust_no = $custNo;
            $storeModel->phone_num = $user->user_tel;
            $storeModel->telephone = $user->user_tel;
            $storeModel->create_time = date('Y-m-d H:i:s');
        } else {
            $detailModel = StoreDetail::find()->where(['store_id' => $storeModel->store_id])->one();
            if (empty($detailModel)) {
                $detailModel = new StoreDetail;
            }
        }
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
        if ($supportBonus != '') {
            $storeModel->support_bonus = $supportBonus;
        }
        if ($type != '') {
            $storeModel->store_type = $type;
        }
        if ($scCode != '') {
            $detailModel->sports_consignee_code = $scCode;
        }
        if ($wcCode != '') {
            $detailModel->welfare_consignee_code = $wcCode;
        }
        if ($cName != '') {
            $detailModel->consignee_name = $cName;
        }

        if ($oldName != '') {
            $detailModel->old_owner_name = $oldName;
        }
        if ($oldCard != '') {
            $detailModel->old_owner_card = $oldCard;
        }
//        if ($nowName != '') {
//            $detailModel->now_owner_card = $nowCard;
//        }
//        if ($nowCard != '') {
//            $detailModel->now_owner_name = $nowName;
//        }
        if ($fName != '') {
            $detailModel->company_name = $fName;
        }
        if ($fLicense != '') {
            $detailModel->business_license = $fLicense;
        }
//        if ($oName != '') {
//            $detailModel->operator_card = $oCard;
//        }
//        if ($oCard != '') {
//            $detailModel->operator_name = $oName;
//        }
        if ($saleType != '') {
            $storeModel->consignment_type = $saleType;
            $storeModel->cert_status = 2;
            $user->user_type = 2;
            $user->saveData();
        }
        if ($saleLottery != '') {
            $storeModel->sale_lottery = $saleLottery;
        }
        $storeModel->real_name_status = $user['authen_status'];
        $storeModel->modify_time = date('Y-m-d H:i:s');
        if ($storeModel->validate()) {
            $upStore = $storeModel->save();
            if ($upStore == false) {
                return $this->jsonError(100, '参数缺失');
            }
        } else {
            return $this->jsonError(109, '认证资料上传失败');
        }
        if ($cCard != '') {
            $where = [];
            if (!empty($storeModel->store_id)) {
                $where = ['!=', 'store_id', $storeModel->store_id];
            }
            $exist = StoreDetail::find()->select(['store_id'])->where(['consignee_card' => $cCard])->andWhere($where)->indexBy('store_id')->asArray()->all();
            if (!empty($exist)) {
                $allId = array_keys($exist);
                $storeExist = Store::find()->where(['in', 'store_id', $allId])->andWhere(['status' => 1])->count();
                if ($storeExist != 0) {
                    return $this->jsonError(109, '该身份证号已被注册');
                }
            }
            $detailModel->consignee_card = $cCard;
        }
        $detailModel->store_id = $storeModel->store_id;
        $detailModel->cust_no = $custNo;
        $detailModel->create_time = date('Y-m-d H:i:s');
        $detailModel->modify_time = date('Y-m-d H:i:s');
        if ($detailModel->validate()) {
            $upDetail = $detailModel->save();
            if ($upDetail == false) {
                return $this->jsonError(100, '参数缺失');
            }
        } else {
            return $this->jsonError(109, '验证失败');
        }
        return $this->jsonResult(600, '认证资料上传成功', '');
    }

    /**
     * 认证图片上传
     * @auther GL zyl 
     * @return type
     */
    public function actionSetReviewImg() {
        $request = Yii::$app->request;
        $custNo = $this->custNo;
        $saveDir = '/store/' . $custNo . '/';
        $day = date('ymdHis', time());
        $imgKey = $request->post('img_key', '');
        $fieldArr = StoreConstants::IMG_FIELD;
        if ($imgKey == '' || (!array_key_exists($imgKey, $fieldArr))) {
            return $this->jsonError(100, '参数缺失');
        }
        $field = $fieldArr[$imgKey];
        $name = $field;
        $detailModel = StoreDetail::find()->where(['cust_no' => $custNo])->one();
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
            $detailModel->$field = $path['data'];
            if ($detailModel->save()) {
                return $this->jsonResult(600, '上传成功', $path['data']);
            } else {
                return $this->jsonError(109, '上传失败');
            }
        } else {
            return $this->jsonError(100, '未上传图片');
        }
    }

    /**
     * 门店认证基础信息
     * @auther GL zyl
     * @return type
     */
    public function actionReviewInfo() {
        $custNo = $this->custNo;
//        $custNo = 'gl00004278';
        $certStatus = StoreConstants::CERT_STATUS;
        $query = new Query;
        $dataList = [];
        $storeData = $query->select(['s.cert_status', 's.store_img', 'd.consignee_name', 'd.consignee_card', 's.review_remark'])
                ->from('store as s')
                ->leftJoin('store_detail as d', 'd.store_id = s.store_id')
                ->where(['s.cust_no' => $custNo, 's.status' => 1])
                ->one();
        if (!empty($storeData)) {
            $dataList['cert_status'] = $storeData['cert_status'];
            $dataList['store_img'] = $storeData['store_img'];
            $dataList['cert_status_name'] = $certStatus[$storeData['cert_status']];
            $dataList['consignee_name'] = str_replace(substr($storeData['consignee_name'], 0, 3), '*', $storeData['consignee_name']);
            $cSub = substr($storeData['consignee_card'], 3, strlen($storeData['consignee_card']) - 4);
            $cLen = strlen($cSub);
            $dataList['consignee_card'] = str_replace($cSub, '************', $storeData['consignee_card'], $cLen);
            $dataList['review_remark'] = $storeData['review_remark'];
        } else {
            $dataList['cert_status'] = 1;
            $dataList['cert_status_name'] = $certStatus[1];
        }
        return $this->jsonResult(600, '认证基础信息', $dataList);
    }

    /**
     * 获取我的方案
     * @auther GL zyl
     * @return type
     */
    public function actionGetMyProgramme() {
        $custNo = $this->custNo;
//        $custNo = 'gl00002100';
        $request = Yii::$app->request;
        $post = Yii::$app->request->post();
        $page = $request->post('page_num', 1);
        $size = $request->post('page_size', 10);
        $footballs = Constants::MADE_FOOTBALL_LOTTERY;
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $status = [
            '1' => '未发布',
            '2' => '招募中',
            '3' => '处理中',
            '4' => '待开奖',
            '5' => '未中奖',
            '6' => '中奖',
            '7' => '未满员撤单',
            '8' => '方案失败',
            '9' => '过点撤销',
            '10' => '拒绝出票',
            '11' => '未上传方案撤单'
        ];
        $query = (new Query())->select(['programme_id', 'programme_code', 'programme_title', 'bet_money', 'lottery_code', 'lottery_name', 'programme_peoples', 'programme_speed', 'programme_last_amount', 'status', 'create_time'])
                ->from('programme')
                ->where(['expert_no' => $custNo]);

        if (isset($post['lottery_code']) && !empty($post['lottery_code'])) {
            if ($post['lottery_code'] == 3000) {
                array_push($footballs, 3000);
                $query->andWhere(['in', 'lottery_code', $footballs]);
            } elseif ($post['lottery_code'] == 3100) {
                array_push($basketball, 3100);
                $query->andWhere(['in', 'lottery_code', $basketball]);
            } else {
                $query->andWhere(['lottery_code' => $post['lottery_code']]);
            }
        }
        if (isset($post['programme_code']) && !empty($post['programme_code'])) {
            $query = $query->andWhere(['programme_code' => $post['programme_code']]);
        }
        if (isset($post['status']) && !empty($post['status']) && isset($status[$post['status']])) {
            if ($post['status'] == 8) {
                $query = $query->andWhere(['in', 'status', [8, 9, 10, 11]]);
            } else {
//                if ($post['status']) {
                $query = $query->andWhere(['status' => $post['status']]);
//                } 
            }
        } else {
            $query = $query->andWhere(['>', 'status', 1]); //过滤未发布（未支付，ps没有继续支付的情况）
        }

        if (isset($post['month']) && !empty($post['month'])) {
            $query = $query->andWhere(['>=', 'create_time', $post['month'] . '-01 00:00:00']);
            $query = $query->andWhere(['<', 'create_time', date('Y-m-d H:i:s', strtotime($post['month'] . '-01 00:00:00 +1 month'))]);
        }
        if (isset($post['start_date']) && !empty($post['start_date'])) {
            $query = $query->andWhere(['>=', 'create_time', $post['start_date'] . ' 00:00:00']);
        }
        if (isset($post['end_date']) && !empty($post['end_date'])) {
            $query = $query->andWhere(['<', 'create_time', $post["end_date"] . ' 23:59:59']);
        }

        $offset = $size * ($page - 1);
        $programmeList['total'] = (int) $query->count();
        $programmeList['page'] = $page;
        $programmeList['pages'] = ceil($programmeList['total'] / $size);
        if (isset($post["time_type"]) && $post["time_type"] == "1") {
            $programmeList['data'] = $query->orderBy("create_time desc")->offset($offset)->limit($size)->all();
        } else {
            $programmeList['data'] = $query->orderBy("programme_end_time desc, create_time desc")->offset($offset)->limit($size)->all();
        }
        $programmeList['size'] = count($programmeList['data']);
        foreach ($programmeList["data"] as &$val) {
            $val['status_name'] = $status[$val['status']];
        }
        return $this->jsonResult(600, "获取成功", $programmeList);
    }

    /**
     * 微信订单（数字彩）
     * @return type
     */
    public function actionWechatOrderDetail() {
        $request = Yii::$app->request;
        $orderId = $request->post('order_id', '');
        $orderCode = $request->post('order_code', '');
        if (empty($orderId) || empty($orderCode)) {
            return $this->jsonError(100, '参数缺失');
        }
        $orderDet = InquireService::getOrderDetail('', $orderCode, $orderId);
        return $this->jsonResult(600, '获取成功', $orderDet);
    }

    /**
     * 
     * 竞彩获取订单（微信）
     * auther GL ctx
     * @return json
     */
    public function actionWechatCompetingOrder() {
        $request = Yii::$app->request;
        $orderId = $request->post('lottery_order_id', '');
        $orderCode = $request->post('lottery_order_code', '');
        $lotteryCode = $request->post('lottery_code', '3011');
        if (empty($orderId) || empty($orderCode)) {
            return $this->jsonError(100, '参数缺失');
        }
        $zqArr = Constants::MADE_FOOTBALL_LOTTERY;
        $lqArr = CompetConst::MADE_BASKETBALL_LOTTERY;
        $bdArr = CompetConst::MADE_BD_LOTTERY;
        $worldCupArr = CompetConst::MADE_WCUP_LOTTERY;
        if (in_array($lotteryCode, $zqArr)) {
            $classCopeting = new FootballService();
            $ret = $classCopeting->getOrder($orderCode, '', $orderId);
        } elseif (in_array($lotteryCode, $lqArr)) {
            $classCopeting = new BasketService();
            $ret = $classCopeting->getOrder($orderCode, '', $orderId);
        } elseif (in_array($lotteryCode, $bdArr)) {
            $classCopeting = new BdService();
            $ret = $classCopeting->getOrder($orderCode, '', $orderId);
        } elseif (in_array($lotteryCode, $worldCupArr)) {
            $ret = WorldcupService::getOrder($orderCode, '', $orderId);
        } else {
            return $this->jsonError(109, '查询结果不存在');
        }
        return $this->jsonResult(600, '获取成功', $ret['result']);
    }

    /**
     * 获取任选订单详情(微信)
     * @auther GL zyl
     * @return type
     */
    public function actionWechatOptionalOrder() {
        $request = Yii::$app->request;
        $orderId = $request->post('lottery_order_id', '');
        $orderCode = $request->post('lottery_order_code', '');
        if (empty($orderId) || empty($orderCode)) {
            return $this->jsonError(100, '参数缺失');
        }
        $classCopeting = new OptionalService();
        $ret = $classCopeting->getOptionalOrder($orderCode, $this->custNo, $orderId);
        if ($ret['code'] != 600) {
            return $this->jsonError(109, $ret['data']);
        }
        $data['data'] = $ret['result'];
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取篮球可投注赛程
     * @auther GL zyl
     * @return type
     */
    public function actionGetLanSchedule() {
        $request = Yii::$app->request;
        $lotteryCode = $request->post('lottery_code', '');
        $playType = $request->post('schedule_dg', 1); // 过关方式 1：过关 2：单关
        if (empty($lotteryCode)) {
            return $this->jsonError(100, '请先选择玩法');
        }
        $basketService = new BasketService();
        $data = $basketService->getBetSchedule($lotteryCode, $playType);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, $data['msg'], $data['data']);
    }

    /**
     * 获取首页竞彩篮球
     * @auther xiejh
     * @param not null s_status 状态0:即时 1:赛果2：赛程 
     * @param not null league_id
     * @param not null page
     * @param not null size
     * @return type
     */
    public function actionGetComptingBasketBall() {
        $request = Yii::$app->request;
        $type = $request->post('s_status', 0);
        $date = $request->post('s_date', '');
        $mids = $request->post('mids', []);
        $league = $request->post('league_id');
        $page = $request->post('page', 1);
        $size = $request->post('size', 100);
        $payType = $request->post('pay_type', '');
        $actionType = $request->post('action_type', 1);
//        $redisRet = \Yii::redisGet("interface_getcompting_lan:{$type}_{$page}_{$date}_{$size}", 2);
//        if (!empty($redisRet) && empty($league) && $type != 3) {//redis中有缓存值则直接赋值
//            $data = $redisRet;
//        } else {//查数据库
        $where = $lWhere = $sWhere = $gWhere = $eWhere = [];
        if (!empty($league)) {
            $lWhere = ['in', 'lan_schedule.league_id', $league];
        }
        $service = new ScheduleService();
        if ($type == 0) {
            $gWhere = ['in', 'sr.result_status', [0, 1]];
            $orderBy = 'lan_schedule.schedule_date,lan_schedule.schedule_mid';
            $data = $service->getLanScheduleList($payType, $page, $size, $where, $lWhere, $orderBy, $gWhere, $eWhere);
        } elseif ($type == 1) {
            $where = ['in', 'sr.result_status', [2, 3, 6, 7]];
            $orderBy = 'lan_schedule.start_time desc,lan_schedule.schedule_mid desc';
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-7 day'));
            $gWhere = ['>=', 'start_time', $startDate . ' 00:00:00'];
            $eWhere = ['<', 'start_time', $endDate . ' 23:59:59'];
            $data = $service->getLanScheduleList($payType, $page, $size, $where, $lWhere, $orderBy, $gWhere, $eWhere);
        } elseif ($type == 2) {
            $where['sr.result_status'] = 0;
            $where['schedule_status'] = 1;
            $orderBy = 'lan_schedule.schedule_date,lan_schedule.schedule_mid';
            $data = $service->getLanScheduleList($payType, $page, $size, $where, $lWhere, $orderBy, $gWhere, $eWhere, $type, $date);
        } elseif ($type == 3) {
            if(!is_array($mids)) {
                $mids = json_decode($mids, true);
            }
            $gWhere = ['in', 'sr.schedule_mid', $mids];
            $orderBy = 'lan_schedule.start_time desc,lan_schedule.schedule_mid desc';
            $data = $service->getLanScheduleList($payType, $page, $size, $where, $lWhere, $orderBy, $gWhere, $eWhere, $type);
//                $userId = $this->userId;
//                if (empty($userId)) {
//                    return $this->jsonError(400, '该帐号登录失效，请重新登录');
//                }
//                $data = $service->getAttentionList($userId, $league);
        } elseif ($type == 11) {//新首页
            $orderBy = 'schedule.start_time,schedule.schedule_mid';
            if(empty($date)) {
                $date = date('Y-m-d');
            } 
            $data = $service->getNewLanScheduleList($page, $size, $date, $lWhere, $payType, $actionType);
        } else {
            return $this->jsonError(109, '参数错误');
        }
//            \Yii::redisSet("interface_getcompting_lan:{$type}_{$page}_{$date}_{$size}", $data, 60); //加入redis缓存
//        }
        return $this->jsonResult(600, '篮球列表', $data);
    }

    /**
     * 所有篮球联赛列表
     * auther GL xiejh
     * @return json
     */
    public function actionGetLanLeague() {
        $request = \Yii::$app->request;
        $status = $request->post('status', 0);
        $where = [];
        if ($status == 1) {
            $where = ['in', 'sr.result_status', [0, 1, 4, 7]];
        } elseif ($status == 2) {
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-7 day'));
            $where = ['and', ['in', 'sr.result_status', [2, 3]], ['>=', 's.start_time', $startDate . ' 00:00:00'], ['<', 's.start_time', $endDate . ' 23:59:59']];
        } else {
            $where['sr.result_status'] = 0;
        }
        $query = new Query;
        $leagueData = $query->select('l.league_code as league_id,l.league_category_id,l.league_long_name,l.league_short_name')
                ->from('lan_schedule s')
                ->innerJoin('lan_schedule_result sr', 'sr.schedule_mid = s.schedule_mid')
                ->leftJoin('league l', 'l.league_code = s.league_id and l.league_type = 2')
                ->where($where)
                ->groupBy('l.league_id')
                ->orderBy('l.league_id')
                ->all();
        return $this->jsonResult(600, '所有篮球联赛', $leagueData);
    }

    /**
     * 篮球赛程信息
     * @auther GL xiejh
     * @return json
     */
    public function actionLanScheduleInfo() {
        $post = \Yii::$app->request;
        $scheduleMid = $post->post('schedule_mid');
        if (empty($scheduleMid)) {
            return $this->jsonError(109, '缺少参数');
        }
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getLanScheduleInfo($scheduleMid);
        if ($data['data'] != null) {
            return $this->jsonResult(600, "获取成功", $data['data']);
        }
    }

    /**
     * 获取篮球预测赛果
     * @return json
     */
    public function actionGetLanPreResult() {
        $post = \Yii::$app->request->post();
        $mid = $post["schedule_mid"];
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getLanPreResult($mid);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取队伍历史交锋
     * @auther GL ljn
     * @return json
     */
    public function actionGetLanDoubleHisResult() {
        $post = \Yii::$app->request->post();
        $leagueCode = isset($post["leagueCode"]) ? $post["leagueCode"] : "";
        $homeCode = $post["homeCode"];
        $visitCode = $post["visitCode"];
        $choose = isset($post["choose"]) ? $post["choose"] : "";
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getLanDoubleHistoryMatch($leagueCode, $homeCode, $visitCode, $choose);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取亚盘
     * @return json
     */
    public function actionGetLanAsianHandicap() {
        $post = \Yii::$app->request->post();
        $mid = $post["schedule_mid"];
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getLanAsianHandicap($mid);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取篮球欧赔
     * @return json
     */
    public function actionGetLanEuropeOdds() {
        $post = \Yii::$app->request->post();
        $mid = $post["schedule_mid"];
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getLanEuropeOdds($mid);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取篮球大小
     * @return json
     */
    public function actionGetLanDaxiaoOdds() {
        $post = \Yii::$app->request->post();
        $mid = $post["schedule_mid"];
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getLanDaxiaoOdds($mid);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取队伍近期战绩
     * @auther GL ljn
     * @return json
     */
    public function actionGetLanHisResult() {
        $post = \Yii::$app->request->post();
        $leagueCode = isset($post["leagueCode"]) ? $post["leagueCode"] : "";
        $teamCodeOne = $post["teamCodeOne"];
        $teamCodeTwo = $post["teamCodeTwo"];
        $choose = isset($post["choose"]) ? $post["choose"] : "";
        $scheduleService = new ScheduleService();
        if ($choose == 1) {
            $res = $scheduleService->getLanHistoryMatch($leagueCode, $teamCodeOne, 1, $choose);
            $info = $scheduleService->getLanHistoryMatch($leagueCode, $teamCodeTwo, 2, $choose);
        } else {
            $res = $scheduleService->getLanHistoryMatch($leagueCode, $teamCodeOne, 0, $choose);
            $info = $scheduleService->getLanHistoryMatch($leagueCode, $teamCodeTwo, 0, $choose);
        }
        $data = array("teamOne" => $res, "teamTwo" => $info);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取联赛战绩
     * @return json
     */
    public function actionGetLanTeamResult() {
        $post = \Yii::$app->request->post();
        $home_team_code = $post["home_team_mid"];
        $visit_team_code = $post["visit_team_mid"];
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getLanTeamResult($home_team_code, $visit_team_code);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取东西部排名
     * @return json
     */
    public function actionGetLanTeamRank() {
        $post = \Yii::$app->request->post();
        $league_id = $post["league_id"];
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getLanTeamRank($league_id);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取队伍未来赛事信息
     * @auther GL ljn
     * @return json
     */
    public function actionGetLanFutureResult() {
        $post = \Yii::$app->request->post();
        $teamCodeOne = $post["teamCodeOne"];
        $teamCodeTwo = $post["teamCodeTwo"];
        $scheduleService = new ScheduleService();
        $res = $scheduleService->getLanFutureMatch($teamCodeOne);
        $info = $scheduleService->getLanFutureMatch($teamCodeTwo);
        $data = array("teamOne" => $res, "teamTwo" => $info);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取联赛盘路走势信息
     * @auther GL ljn
     * @return json
     */
    public function actionGetLanMentsRoad() {
        $post = \Yii::$app->request->post();
        $teamCodeOne = $post["teamCodeOne"];
        $teamCodeTwo = $post["teamCodeTwo"];
        $scheduleService = new ScheduleService();
        $res = $scheduleService->getLanMentsRoadRes($teamCodeOne);
        $info = $scheduleService->getLanMentsRoadRes($teamCodeTwo);
        $data = array("teamOne" => $res, "teamTwo" => $info);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     *  获取篮球赛程基础分析信息
     * @auther GL zyl
     * @return type
     */
    public function actionGetLanAnalyze() {
        $request = Yii::$app->request;
        $mid = $request->post('mid', '');
        if (empty($mid)) {
            return $this->jsonError(100, '参数缺失');
        }
        $scheduleService = new ScheduleService();
        $analyze = $scheduleService->getLanAnaylsis($mid);
        $data['data'] = $analyze;
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取篮球实况信息
     * @auther GL zyl
     * @return type
     */
    public function actionGetLanCount() {
        $request = Yii::$app->request;
        $mid = $request->post('mid', '');
        if (empty($mid)) {
            return $this->jsonError(100, '参数缺失');
        }
        $scheduleService = new ScheduleService();
        $countData = $scheduleService->getLanCount($mid);
        return $this->jsonResult(600, '获取成功', $countData);
    }

    /**
     * 篮球文字直播
     * @auther GL zyl
     * @return type
     */
    public function actionGetLanLive() {
        $request = Yii::$app->request;
        $mid = $request->post('mid', '');
        $page = $request->post('page', 1);
        $size = $request->post('size', 20);
        if (empty($mid)) {
            return $this->jsonError(100, '参数缺失');
        }
        $scheduleService = new ScheduleService();
        $liveData = $scheduleService->getLiveList($mid, $page, $size);
        return $this->jsonResult(600, '获取成功', $liveData);
    }

    /**
     * 获取首页、发现界面广告图片
     * @auther GL ljn
     * 发现界面广告图片type=3 需要区分是咕啦自用还是代理商所用use_type:1咕啦 2代理商
     */
    public function actionGetBananerPic() {
        $request = Yii::$app->request;
        $type = $request->post('type', '');
        $size = $request->post('size', 1);
        $useType = $request->post('source', 1);
        if (empty($type)) {
            return $this->jsonError(109, '参数缺失');
        }
        if ($type == 2) {
            $bananer = Bananer::find()->select(["pic_url as ad_pic"])
                    ->where(["type" => $type, "status" => 1])
                    ->asArray()
                    ->one();
        } elseif($type==3){
            $bananer = Bananer::find()->select(["bananer_id", "pic_url", "content","pic_name","pc_pic_url"])
                ->where(["type" => $type, "status" => 1,"use_type"=>$useType])
                ->orderBy("bananer_id desc")
                ->limit($size)
                ->asArray()
                ->all();
        }else{
            $bananer = Bananer::find()->select(["bananer_id", "pic_url", "content","pic_name","pc_pic_url"])->where(["type" => $type, "status" => 1])
                ->orderBy("bananer_id desc")
                ->limit($size)
                ->asArray()
                ->all();
        }
        return $this->jsonResult(600, '获取成功', $bananer);
    }

    /**
     * 获取首页、发现界面广告图片内容
     * @auther GL ljn
     */
    public function actionGetBananerContent() {
        $request = Yii::$app->request;
        $id = $request->post('id', '');
        if (empty($id)) {
            return $this->jsonError(109, '参数缺失');
        }
        $bananer = Bananer::find()->where(["bananer_id" => $id])
                ->asArray()
                ->one();
        if (!empty($bananer)) {
            return $this->jsonResult(600, '获取成功', $bananer);
        } else {
            return $this->jsonError(109, '不存在数据');
        }
    }

    public function actionGetWebConf(){
        $request = Yii::$app->request;
        $type = $request->post('type', 2);
        if ($type == 2) {
            $bananer = Bananer::find()->select(["pic_url as ad_pic"])
                ->where(["type" => $type, "status" => 1])
                ->asArray()
                ->one();
        }
        return $this->jsonResult(600, '获取成功', $bananer);
    }

}
