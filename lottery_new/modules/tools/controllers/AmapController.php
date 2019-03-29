<?php

namespace app\modules\tools\controllers;

use yii\web\Controller;
use app\modules\tools\helpers\Toolfun;
use app\modules\common\helpers\Winning;
use app\modules\common\services\ResultService;
use app\modules\common\services\AdditionalService;
use app\modules\common\models\LotteryAdditional;
use app\modules\tools\helpers\UpdateOdds;
use app\modules\common\services\OrderService;
use app\modules\common\models\Store;
use app\modules\common\models\LotteryOrder;
use app\modules\user\models\User;
use app\modules\user\models\UserFollow;
use app\modules\competing\models\LanScheduleResult;
use app\modules\orders\services\MajorService;
use app\modules\common\models\ScheduleResult;
use app\modules\common\helpers\Constants;
use app\modules\common\helpers\TrendFall;
use app\modules\common\models\ScheduleEvent;
use app\modules\common\services\KafkaService;

class AmapController extends Controller {

    /**
     * 上传到高德地图
     * @return type
     */
    public function actionAmapCreate() {
        $storeData = Store::find()->where(['cert_status' => 3])->andWhere(['!=', 'coordinate', 'null'])->one();
        $data['store_name'] = $storeData->store_name;
        $data['province'] = $storeData->province;
        $data['city'] = $storeData->city;
        $data['area'] = $storeData->area;
        $data['address'] = $storeData->address;
        $data['coordinate'] = $storeData->coordinate;
        $data['store_img'] = $storeData->store_img;
        $data['phone_num'] = $storeData->phone_num;
        $data['store_code'] = $storeData->store_code;
        if (empty($storeData->amap_id)) {
            $ret = Toolfun::setLbsAddress($data);
            if ($ret['status'] == 1) {
                $storeData->amap_id = $ret['_id'];
                if (!$storeData->save()) {
                    return $this->jsonError(109, $storeData->getFirstErrors());
                }
            }
        } else {
            $data['amap_id'] = $storeData->amap_id;
            $ret = Toolfun::updateLbsAddress($data);
        }
        return $this->jsonError($ret['status'], $ret['info']);
    }

    /**
     * 足彩对奖
     * @return type
     */
    public function actionTest() {
        $winning = new Winning();
//        $results = ScheduleResult::find()->select(['schedule_mid', 'schedule_result_3010', 'schedule_result_3006', 'schedule_result_3007', 'schedule_result_3008', 'schedule_result_3009', 'odds_3006', 'odds_3007', 'odds_3008', 'odds_3009', 'odds_3010'])->where(['schedule_mid' => '97118', 'status' => 1])->asArray()->one(); 
        $ret = $winning->getWinningCompeting('99472');
        return $this->jsonResult(600, '操作成功', $ret);
    }

    /**
     * 获取开奖结果
     */
    public function actionResult() {
        ResultService::getResult();
    }

    /**
     * 生成明细
     */
    public function actionOut() {
        $request = \Yii::$app->request;
        $orderCode = $request->get('order_code') ? $request->get('order_code') : $request->post('order_code');
        $model = LotteryOrder::findOne(['lottery_order_code' => $orderCode]);
        $control = new OrderService();
        $ret = $control->proSuborder($model);
        if ($ret["code"] != "0") {
            $model->suborder_status = 2;
            $model->status = 6;
            $model->save();
            BettingDetail::updateAll([
                "status" => 6
                    ], 'lottery_order_id=' . $model->lottery_order_id);
            $ret = OrderService::outOrderFalse($model->lottery_order_code, 6, null, "详情订单生成出错");
        } else {
            $model->suborder_status = 1;
            $model->save();
        }
        return $this->jsonResult(600, '操作成功', $ret);
    }

    /**
     * 中奖订单派奖
     * @return type
     */
    public function actionAward() {
        $winning = new Winning();
        $ret = $winning->getAwardsFunds();
        return $this->jsonResult(600, 'sss', $ret);
    }

    /**
     * 旗舰店关注门店
     * @return type
     */
    public function actionSetFollow() {
        $storeNo = Store::find()->select(['cust_no', 'user_id'])->where(['user_id' => 108])->asArray()->one();
        $user = User::find()->asArray()->all();
        foreach ($user as $val) {
            $follow = UserFollow::find()->where(['cust_no' => $val['cust_no'], 'store_id' => $storeNo['user_id']])->one();
            if (empty($follow)) {
                $follow = new UserFollow();
                $follow->create_time = date('Y-m-d H:i:s');
            } else {
                $follow->modify_time = date('Y-m-d H:i:s');
                $follow->follow_status = 1;
                $follow->default_status = 1;
            }
            $follow->cust_no = $val['cust_no'];
            $follow->store_id = $storeNo['user_id'];
            $follow->store_no = $storeNo['cust_no'];
            if ($follow->validate()) {
                $insertId = $follow->save();
            }
        }
        if ($insertId == false) {
            return $this->jsonError(109, '关注失败，请稍后再关注');
        }
        return $this->jsonResult(600, '关注成功', '');
    }

    /**
     * 篮球对奖
     * @return type
     */
    public function actionLanWin() {
        $request = \Yii::$app->request;
        $mid = $request->post('mid', '');
        $data = LanScheduleResult::find()->select(['result_qcbf'])->where(['schedule_mid' => $mid, 'result_status' => 2])->asArray()->one();
        if (empty($data)) {
            return $this->jsonError(109, '此场次暂未开始');
        }
        $bifen = explode(':', $data['result_qcbf']);
        $winning = new Winning();
        $ret = $winning->basketballLevel($mid, (int) $bifen[1], (int) $bifen[0]);
        return $this->jsonResult(600, 'succ', $ret);
    }

    /**
     * 追号
     * @return type
     */
    public function actionAdd() {
        $field = ['lottery_additional_id', 'lottery_name', 'lottery_id', 'play_name', 'play_code', 'lottery_additional_code', 'chased_num', 'periods_total', 'cust_no', 'user_id', 'store_id', 'store_no', 'bet_val',
            'bet_double', 'is_bet_add', 'bet_money', 'total_money', 'count', 'opt_id', 'is_random', 'is_limit', 'win_limit'];
        $addOrder = LotteryAdditional::find()->select($field)->where(['lottery_additional_id' => 1271, 'status' => 2])->asArray()->one();
        $orderService = new AdditionalService();
        $ret = $orderService->doTrace($addOrder);
        return $this->jsonResult(600, 'xxx', $ret);
    }

    /**
     * 奖金优化生成子单
     * @return type
     */
    public function actionMajorOut() {
        $request = \Yii::$app->request;
        $orderId = $request->post('order_id', '');
        $model = LotteryOrder::findOne(['lottery_order_id' => $orderId]);
        $control = new MajorService;
        $ret = $control->proSuborder($model);
        return $this->jsonResult(600, '操作成功', $ret);
    }

    /**
     * 足彩订单子单修改赔率
     * @return type
     */
    public function actionUpdateZqOdds() {
        $request = \Yii::$app->request;
        $orderId = $request->post('order_id', '');
        $competing = new UpdateOdds();
        $ret = $competing->updateOdds($orderId);
        return $this->jsonResult(600, '操作成功', $ret);
    }

    /**
     * 文章对奖
     * @return type
     */
    public function actionArticleRed() {
        $request = \Yii::$app->request;
        $mid = $request->post('mid', '');
        $r_3006 = $request->post('r_3006', '');
        $r_3010 = $request->post('r_3010', '');
        $redArticle = new ArticleRed();
        $ret = $redArticle->acticlePreResult2($mid, $r_3006, $r_3010);
        return $this->jsonResult(600, '操作成功', $ret);
    }

    /**
     * 足球对奖
     * @return type
     */
    public function actionZuWin() {
        $request = \Yii::$app->request;
        $mid = $request->post('mid', '');
        $data = ScheduleResult::find()->select(['schedule_result_3006', 'schedule_result_3007', 'schedule_result_3008', 'schedule_result_3009', 'schedule_result_3010'])->where(['schedule_mid' => $mid, 'status' => 2])->asArray()->one();
        if (empty($data)) {
            return $this->jsonError(109, '此场次暂未开始');
        }
        $bifen = Constants::BIFEN_ARR;
        $result3007 = str_replace(':', '', $data['schedule_result_3007']);
        if ($data['schedule_result_3010'] == 0) {
            if (!in_array($result3007, $bifen[0])) {
                $result3007 = '09';
            }
        } elseif ($data['schedule_result_3010'] == 1) {
            if (!in_array($result3007, $bifen[1])) {
                $result3007 = '99';
            }
        } elseif ($data['schedule_result_3010'] == 3) {
            if (!in_array($result3007, $bifen[3])) {
                $result3007 = '90';
            }
        }
        if ($data['schedule_result_3008'] > 7) {
            $result3008 = 7;
        } else {
            $result3008 = $data['schedule_result_3008'];
        }
        $wining = new Winning();
        $ret = $wining->footballLevel($mid, $data['schedule_result_3006'], "'" . $result3007 . "'", "'" . $result3008 . "'", "'" . $data['schedule_result_3009'] . "'", "'" . $data['schedule_result_3010'] . "'");
        return $this->jsonResult(600, 'succ', $ret);
    }

    public function actionUpdateFollow() {
        $store = Store::find()->select(['user_id', 'store_code'])->where(['status' => 1])->asArray()->all();
        $follow = UserFollow::find()->select(['user_follow_id', 'store_id'])->asArray()->all();
        $updateStr = '';
        foreach ($store as $val) {
            foreach ($follow as $v) {
                if ($val['user_id'] == $v['store_id']) {
                    $updateStr .= "update user_follow set store_id = {$val['store_code']} where user_follow_id = {$v['user_follow_id']};";
                }
            }
        }
        $data = \Yii::$app->db->createCommand($updateStr)->execute();
        return $this->jsonResult(600, 'succ', $data);
    }

    public function actionDoPro() {
        $request = \Yii::$app->request;
        $proId = $request->post('pid');
        $proSer = new \app\modules\common\services\ProgrammeService();
        $pro = $proSer->playProgramme($proId);
        return $this->jsonResult(600, 'succ', $pro);
    }

    public function actionZuWinning() {
        $request = \Yii::$app->request;
        $mid = $request->post('mid', '');
        $winning = new \app\modules\common\helpers\Winning();
        $ret = $winning->getWinningCompeting($mid);
        return $this->jsonResult(600, 'xxx', $ret);
    }

    public function actionSzWinning() {
        $request = \Yii::$app->request;
        $periods = $request->post('periods', '');
        $openNumber = $request->post('open_nums', '');
        $lotteryCode = $request->post('lottery_code', '');
        $winHelper = new Winning();
        if ($lotteryCode == 1001) {
            $ret = $winHelper->lottery1001Level($periods, $openNumber);
        } elseif ($lotteryCode == 1002) {
            $ret = $winHelper->lottery1002Level($periods, $openNumber);
        } elseif ($lotteryCode == 1003) {
            $ret = $winHelper->lottery1003Level($periods, $openNumber);
        } elseif ($lotteryCode == 2001) {
            $ret = $winHelper->lottery2001Level($periods, $openNumber);
        } elseif ($lotteryCode == 2002) {
            $ret = $winHelper->lottery2002Level($periods, $openNumber);
        } elseif ($lotteryCode == 2003) {
            $ret = $winHelper->lottery2003Level($periods, $openNumber);
        } elseif ($lotteryCode == 2004) {
            $ret = $winHelper->lottery2004Level($periods, $openNumber);
        }
        return $this->jsonResult(600, 'succ', $ret);
    }

    public function actionProgrammePlay() {
        $request = \Yii::$app->request;
        $proId = $request->post('proId');
        ;
        $proService = new \app\modules\common\services\ProgrammeService();
        $ret = $proService->playProgramme($proId);
        return $this->jsonResult(600, 'sss', $ret);
    }

    public function actionDealOrder() {
        $request = \Yii::$app->request;
        $mid = $request->post('mid');
        ;
        $dealOrder = new \app\modules\orders\helpers\DealOrder();
        $ret = $dealOrder->dealDelayScheduleOrder($mid);
        return $this->jsonResult(600, 'sss', $ret);
    }

    public function actionDealDelayOrder() {
        $request = \Yii::$app->request;
        $mid = $request->post('mid');
        ;
        $dealOrder = new \app\modules\orders\helpers\DealOrder();
        $ret = $dealOrder->dealDelayAward($mid);
        return $this->jsonResult(600, 'sss', $ret);
    }

    public function actionTex() {
        $request = \Yii::$app->request;
        $mid = $request->post('mid');
        $field = ['event_type', 'count(event_type_name) as total'];
        $homeEvent = ScheduleEvent::find()->select($field)->where(['schedule_mid' => $mid, 'team_type' => 1])->andWhere(['in', 'event_type', [4, 6, 7]])->groupBy('event_type')->asArray()->all();
        $visitEvent = ScheduleEvent::find()->select($field)->where(['schedule_mid' => $mid, 'team_type' => 2])->andWhere(['in', 'event_type', [4, 6, 7]])->groupBy('event_type')->asArray()->all();
        print_r($homeEvent);
        print_r($visitEvent);
    }

    public function actionTrendWeb() {
        $request = \Yii::$app->request;
        $lotteryCode = $request->get('lottery_code') ? $request->get('lottery_code') : $request->post('lottery_code');
        $periods = $request->get('periods') ? $request->get('periods') : $request->post('periods');
        $openNums = $request->get('open_nums') ? $request->get('open_nums') : $request->post('open_nums');
        $trendFall = new TrendFall();
        $ret = $trendFall->trendWebsocket($lotteryCode, $periods, $openNums);
        return $this->jsonResult(600, 'sss', $ret);
    }

    public function actionPlay() {
//        $ret = new \app\modules\tools\kafka\ThirdOrderCreate();
//        $param = ["apiOrderId" => 489, 'thirdOrderCode' => 'ceshi20180123002', 'userId' => 195, 'custNo' => 'gl00005091'];
        $playOrderService = new \app\modules\openapi\services\PlayOrderService();
        $result = $playOrderService->playOrder(620, 'ceshi20180124007', 195, 'gl00005091');
//        $data = $ret->run($param);
        print_r($result);
//        $params1 = $data;
//        
//        $ret1 = new \app\modules\tools\kafka\AutoOrderCreate();
//        $data1 = $ret1->run($params1);
//        print_r($data1);
//        $str = '周001';
//        echo date('w', strtotime('2018-01-07'));
    }

    public function actionFunds() {
//        $ret = new \app\modules\common\services\FundsService();
//        $data = $ret->operateUserFunds('gl00005079', -10, 0, -10);
//        print_r($data);
        echo intval('周001');
    }

    public function actionAutoOrder() {
//        $request = \Yii::$app->request;
//        $orderCode = $request->post('order_code');
//        $order = LotteryOrder::findOne(['lottery_order_code' => $orderCode]);
        $params = ["orderId" => "13551", "majorData" => [], 'queueId' => 67839];
        $ret = new \app\modules\tools\kafka\AutoOrderCreate();
        $data = $ret->run($params);
        print_r($data);
        die;
//        $data = \app\modules\orders\models\AutoOutOrder::find()->select(['out_order_code'])->where(['order_code' => $orderCode, 'status' => 1])->asArray()->all();
        $auto = new \app\modules\tools\kafka\AutoOutTicket();
//        foreach ($data as $val) {
        $param = ['autoCode' => 'GLCAUTO18011916AI0000293', 'thirdOrderCode' => '', 'queueId' => 67730];
        $data1 = $auto->run($param);
        print_r($data1);
        die;
//        }
        print_r($data1);
    }

    public function actionA() {
        $user = User::findOne(['cust_no' => 'gl00030106']);
        $gainCoupons = 0;
        $key = 'usercoupons';
        if (strtotime($user->create_time) < strtotime('2018-05-20 00:00:00')) {
            if (!\Yii::$app->redis->SISMEMBER($key, $user->cust_no)) {
                \Yii::$app->redis->sadd($key, $user->cust_no);
                $gainCoupons = 1;
            }
        }
        echo $gainCoupons;
//        print_r($a);
    }

    public function actionB() {
//        $des = new \app\modules\tools\helpers\Des('F6947E7B403E590BD4A38E9F', '20180101');
//        $r = $des->decrypt('cu4KWII\/6ijlih283Sa52tGkwZaxrOiIQ4DSqKTMQLoBx2GH6BjR67E+BgaDVhnKpBmRON1e7xyLYx4Cx9z1dfSul8tBFEOyorHQIxwfwn96u\/\/ER3HsFeaXE8AGMT81pphvUJI5f5hUVtG+isGjiNmBuuNAHkzSlBVxTXFFRxjAdU5Gk3ReKdvZsnD+10NBPaWf\/ZB5XNstMUK+D114tzb3A2bHTFmGLf8eRPAmPhwGXbcdGEg6FLZKYv1FtJRW7okCgwLcKGa6QXQB4eK8VIfcEJ3\/j2wxnTCm2bRX3yNae1Eov0dD1JCwY50A3Tyt1ZST17kR1NicWCi150qksgQyBiFAM6HuRUNA3q84QyqG6CuwD1NzUA+XQQXrFiDLDthgV+BIvQRYxnLKBlrp3ilL5Cd4JjqF9fUjUWaOofC0wzX1agnfmA==');
//        $third = new \app\modules\openapi\services\ThirdApiService();
//        $d = $third->playOrder(json_decode($r, true), '14c3ef5cbe11406193031f3d0c646725');
//        $d = new \app\modules\tools\kafka\NmOutTicket();
//        $r = $d->run(['autoCode' => 'GLCAUTO18051711NM0000010', 'thirdOrderCode' => 'XXCHHGG18051618T0000001', 'queueId' => '18813']);
//        $d = new \app\modules\tools\kafka\AutoOrderCreate();
//        $r = $d->run(['orderId'=> '23167', 'majorData' => [], 'queueId' => '65066']);
//        $d = new \app\modules\tools\kafka\LotteryJob();
//        $r =  $d->run(['orderId' => '18472', 'queueId' => '18824']);
//        $d = new \app\modules\tools\kafka\JlOutTicket();
//        $r = $d->run(['autoCode' => 'XXCAUTO18052509JL0000001', 'thirdOrderCode' => '', 'queueId' => '573']);
//        $r = \app\modules\orders\helpers\OrderDeal::confirmOutTicket('GLCDLT18052316T0000004');
//        $d = new \app\modules\tools\kafka\CancelScheduleAward();
//        $r = $d->run(['mid' => '108107', 'code' => 3000, 'queueId' => '20497']);
//        $d = new \app\modules\tools\kafka\ThirdOrderCreate();
//        $r = $d->run(['apiOrderId' => 639, 'thirdOrderCode' => 'XXCDLT18052317T0000001', 'userId' => '272', 'custNo' => 'gl00030106', 'queueId' => 21624]);
//        $key = 'testGLCDLT18052316T0000002';
//        $d = \app\modules\common\services\RedisService::lock($key);
//        $d = UpdateOdds::outNmUpdateOdds('GLCBQC18052416T0000001');
//        $d = new \app\modules\tools\kafka\ThirdOrderCreate;
//        $r = $d->run(['apiOrderId' => 718, 'thirdOrderCode' => 'XXCDLT18060714T0000001', 'userId' => 272, 'custNo' => 'gl00030106', 'queueId' => 26938]);
        $d = new \app\modules\tools\kafka\OrderPollingStore();
        $r = $d->run(['orderCode' => 'GLCHHGG18061315T0000002', 'queueId' =>29197]);
        print_r($r);
    }
    
    public function actionC() {
        $ip = '14.105.107.252';
        $url = 'http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;
//        $postData = ['ip' => $ip];
        $addresData = file_get_contents($url);
        print_r(json_decode($addresData, true));
    }

}
