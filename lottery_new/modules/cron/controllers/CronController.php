<?php

namespace app\modules\cron\controllers;

use yii\web\Controller;
use app\modules\common\models\LotteryRecord;
use app\modules\common\models\Lottery;
use app\modules\common\helpers\Winning;
use app\modules\common\helpers\Constants;
use app\modules\common\models\FootballFourteen;
use app\modules\common\models\Queue;
use app\modules\common\models\BettingDetail;
use app\modules\common\services\OrderService;
use app\modules\common\models\LotteryOrder;
use app\modules\common\models\PayRecord;
use app\modules\common\services\PayService;
use app\modules\common\services\KafkaService;
use app\modules\common\helpers\Commonfun;
use app\modules\common\services\SyncService;
use app\modules\common\services\SyncApiRequestService;
use app\modules\user\helpers\UserTool;
use app\modules\common\models\ProgrammeUser;
use app\modules\user\models\UserStatistics;
use yii\db\Expression;
use app\modules\orders\models\AutoOutOrder;
use app\modules\tools\kafka\AutoWinMoney;

class CronController extends Controller {

    public $defaultAction = 'main';
    private $userService = null;

    public function __construct($id, $module, $config = []) {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex() {
        echo 'sss' . $this->custNo;
        die;
    }

    public function getTcNumber($ltype, $nextPeriods) {
        $surl = "http://www.lottery.gov.cn/api/lottery_kj_detail_new.jspx?_ltype={$ltype}&_term={$nextPeriods}";
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $surl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        //打印获得的数据
        $res = json_decode($output, true); //json转数组
        $lotteryObject = $res[0]; //彩票对象
        return $lotteryObject;
    }

    /**
     * 说明: 定时执行兑奖-详情处理接口
     * @author  kevi
     * @date 2017年6月19日 上午9:56:48
     * @param lottery_code
     * @return 
     */
    public function actionWinningDetailStart() {
        $request = \Yii::$app->request;
        $lotteryCode = $request->get('lottery_code');
        if (empty($lotteryCode)) {
            return '未提供兑奖彩种';
        }
        $numsArr = Constants::MADE_NUMS_LOTTERY;
        if (in_array($lotteryCode, $numsArr)) {
            $record = LotteryRecord::find()->select(['periods'])->where(['lottery_code' => $lotteryCode, 'status' => LotteryRecord::STATUS_LAST])->andWhere(['in', 'win_status', [LotteryRecord::WIN_STATUS_UNWIN, LotteryRecord::WIN_STATUS_WINNING]])->asArray()->one();
        } else {
            $record = FootballFourteen::find()->select(['periods'])->where(['status' => FootballFourteen::STATUS_HAS_PZIRE])->andWhere(['in', 'win_status', [FootballFourteen::WIN_STATUS_UNWIN, FootballFourteen::WIN_STATUS_WINNING]])->asArray()->one();
        }
        $periods = $record['periods'];
        if (empty($periods)) {
            return '未有可以兑奖的期数';
        }
        $winningHelper = new Winning();
        switch ($lotteryCode) {
            case Lottery::CODE_DLT :
                $ret = $winningHelper->getDltWinning($lotteryCode, $periods);
                break;
            case Lottery::CODE_PL3:
                $ret = $winningHelper->getPltWinning($lotteryCode, $periods);
                break;
            case Lottery::CODE_PL5 :
                $ret = $winningHelper->getPlfWinning($lotteryCode, $periods);
                break;
            case Lottery::CODE_QXC:
                $ret = $winningHelper->getQxcWinning($lotteryCode, $periods);
                break;
            case Lottery::CODE_OPTIONAL:
                $ret = $winningHelper->getOptionalWinning($periods);
                break;
            case Lottery::CODE_SSQ:
                $ret = $winningHelper->getSsqWinning($lotteryCode, $periods);
                break;
            case Lottery::CODE_FC_3D:
                $ret = $winningHelper->getFcTdWinning($lotteryCode, $periods);
                break;
            case Lottery::CODE_QLC:
                $ret = $winningHelper->getQlcWinning($lotteryCode, $periods);
                break;
        }

        if ($ret['code'] == 0) {
            //失败日志
            \Yii::error("彩种:{$lotteryCode} 期数:{$periods} 兑奖详情过程失败:{$ret['data']}", 'winning_log');
            echo '失败';
        } else if ($ret['code'] == 1) {
            //成功日子
            \Yii::info("彩种:{$lotteryCode} 期数:{$periods} 兑奖详情完成: {$ret['data']}", 'winning_log');
            echo '成功';
        } else if ($ret['code'] == 2) {
            echo '';
        }
    }

    /**
     * 说明: 定时执行兑奖-订单处理接口
     * @author  kevi
     * @date 2017年6月19日 上午9:56:48
     * @param lottery_code
     * @return
     */
    public function actionWinningOrderStart() {
        $request = \Yii::$app->request;
        $lotteryCode = $request->get('lottery_code');
        if (empty($lotteryCode)) {
            return '未提供兑奖彩种';
        }
        $numsArr = Constants::MADE_NUMS_LOTTERY;
        if (in_array($lotteryCode, $numsArr)) {
            $record = LotteryRecord::find()->where(['lottery_code' => $lotteryCode, 'status' => LotteryRecord::STATUS_LAST, 'win_status' => LotteryRecord::WIN_STATUS_WON])->asArray()->one();
        } else {
            $record = FootballFourteen::find()->where(['status' => FootballFourteen::STATUS_HAS_PZIRE, 'win_status' => FootballFourteen::WIN_STATUS_WON])->asArray()->one();
        }
        $periods = $record['periods'];
        if (empty($periods)) {
            return '未有可以兑奖的期数';
        }

        $winningHelper = new Winning();

        $ret = $winningHelper->getUpdateOrder($lotteryCode, $periods);
        if ($ret['code'] == 0) {
            //失败日志
            \Yii::error("彩种:{$lotteryCode} 期数:{$periods} 兑奖订单过程失败:{$ret['data']}", 'winning_log');
            echo '失败';
        } else if ($ret['code'] == 1) {
            //成功日子
            \Yii::info("彩种:{$lotteryCode} 期数:{$periods} 兑奖订单完成: {$ret['data']}", 'winning_log');
            echo '成功';
        } else if ($ret['code'] == 2) {
            echo '';
        }
    }

    /**
     * 手动生成子单
     * @auther GL zyl
     * @return type
     */
    public function actionSubOrder() {
        $request = \Yii::$app->request;
        $orderId = $request->get('order_id', '');
        if (empty($orderId)) {
            return $this->jsonError(100, '参数缺失');
        }
        $detail = BettingDetail::find()->select(['betting_detail_id'])->where(['lottery_order_id' => $orderId])->all();
        if (!empty($detail)) {
            $str = $detail[0]['betting_detail_id'] . '-' . $detail[count($detail) - 1]['betting_detail_id'];
            return $this->jsonResult(109, '该订单已生成子单', $str);
        }
        $model = LotteryOrder::findOne(['lottery_order_id' => $orderId, "suborder_status" => "0"]);
        $control = new OrderService;
        $ret = $control->proSuborder($model);
        if ($ret["code"] != "0") {
            $model->suborder_status = 2;
            $model->status = 6;
            $model->save();
            BettingDetail::updateAll([
                "status" => 6
                    ], 'lottery_order_id=' . $model->lottery_order_id);
            $ret = OrderService::outOrderFalse($model->lottery_order_code, 6, null, "详情订单生成出错");
            SyncService::syncFromHttp('cron/cron/sub-order-false');
        } else {
            $model->suborder_status = 1;
            $model->save();
            SyncService::syncFromHttp();
        }
        return $this->jsonResult(600, '操作成功', $ret);
    }

    /**
     * 线程重跑
     * @auther GL ctx
     * @return json
     */
    public function actionReQueue() {
        $get = \Yii::$app->request->get();
        if (!isset($get["queueId"])) {
            return $this->jsonResult(109, "参数缺失", "");
        }
        $queue = Queue::findOne(["queue_id" => $get["queueId"]]);
        $parms = json_decode($queue->args, true);
        // $parms['queueId']=$get["queueId"];
        KafkaService::addQue($queue->queue_name, $parms, true);
        $queue->status = 3;
        $queue->save();
        //$lotteryqueue = new \LotteryQueue();
        //$lotteryqueue->pushQueue($queue->job, $queue->queue_name, json_decode($queue->args, true));
        return $this->jsonResult(600, "已经发起重跑", "");
    }

    /**
     * 定时取消订单（退折扣）
     */
    public function actionCancleOrder() {
        $maxTime = date('Y-m-d H:i:s', time() - (PayService::ORDER_EXPIRE_TIME + 60));
        $where = ['status' => 0];
        foreach (PayRecord::find()->where($where)->andWhere(['<', 'create_time', $maxTime])->asArray()->batch(1000) as $data) { // 未支付时间超过6分钟的执行退优惠
            //$lotteryqueue = new \LotteryQueue();
            foreach ($data as $v) {
                KafkaService::addQue('CancleOrder', $v);
                //$lotteryqueue->pushQueue('cancle_order_job', 'order#cancle_order', $v);
            }
        }
        echo 'success';
    }

    /**
     * 线程重跑（kafka）
     * @auther GL ctx
     * @return json
     */
    public function actionReKafka() {
        $get = \Yii::$app->request->get();
        if (!isset($get["queueId"])) {
            return $this->jsonResult(109, "参数缺失", "");
        }
        $queue = Queue::findOne(["queue_id" => $get["queueId"]]);
        $parms = json_decode($queue->args, true);
        $parms['queueId'] = $get["queueId"];
        KafkaService::addQue($queue->queue_name, $parms);
        //$lotteryqueue = new \LotteryQueue();
        //$lotteryqueue->pushQueue($queue->job, $queue->queue_name, json_decode($queue->args, true));
        return $this->jsonResult(600, "已经发起重跑", "");
    }

    /**
     * kafka监控
     */
    public function actionMonitorKafka() {
        $api = 'http://211.149.170.87:8000';
        $topicUrl = '/v3/kafka/local/topic';
        $topicData = json_decode(\yii::sendCurlGet($api . $topicUrl), true);
        $skipTopic = '__consumer_offsets';
        $groupPix = "Hyy-";
        $maxlag = 100;
        if (!$topicData['error']) {
            $topicData = $topicData['topics'];
            if(is_array($topicData)){
            foreach ($topicData as $topic) {
                if ($topic == $skipTopic) {
                    continue;
                }
                $uri = "/v3/kafka/local/consumer/{$groupPix}{$topic}/lag";
                $res = json_decode(\yii::sendCurlGet($api . $uri), true);
                echo '<pre>';
                print_r($res['status']['group']);
                print_r($res['status']['status']);
                if (!$res['error']) {
                    if ($res['status']['status'] == 'OK') {
                        //Commonfun::sysAlert('kafka-消费通知','紧急','kafka消费者进程挂掉:'.$topic,'待处理','请立即处理');
                        if ($res['status']['totallag'] >= $maxlag) {
                            Commonfun::sysAlert('kafka-消费通知', '通知', 'kafka消费者数据延迟;topic:' . $topic, ';num:' . $res['status']['maxlag']['current_lag'], '待处理', '请立即处理');
                        }
                    }
                    /* elseif($res['status']['status']=='ERR'){//群主错误
                      $uri='/v3/kafka/local/consumer/'.$groupPix.$topic;
                      $res=json_decode(\yii::sendCurlDel($api.$uri),true);
                      Commonfun::sysAlert('kafka-消费通知','紧急','kafka消费者进程分组有误;topic:'.$topic,';num:'.$res['status']['maxlag']['current_lag'],'待处理','请立即处理');
                      } */
                    //print_r($res['status']['maxlag']['current_lag']);
                    //die;
                }
            }
            }
            echo 'success2';
        }
        echo 'success';
    }

    /**
     * 删除kafka分组
     */
    public function actionDelKafka() {
        $api = 'http://211.149.170.87:8000';
        $groupName = \Yii::$app->request->get('groupName');
        if (!$groupName) {
            echo 'no params groupName';
            exit;
        }
        $uri = '/v3/kafka/local/consumer/' . $groupName;
        $res = json_decode(\yii::sendCurlDel($api . $uri), true);
        print_r($res);
        die();
    }

    /**
     * kafka信息详情
     */
    public function actionKafkaConsumerInfo() {
        $api = 'http://211.149.170.87:8000';
        $topicUrl = '/v3/kafka/local/topic';
        $topicData = json_decode(\yii::sendCurlGet($api . $topicUrl), true);
        $skipTopic = '__consumer_offsets';
        $groupPix = "Hyy-";
        $maxlag = 100;
        if (!$topicData['error']) {
            $topicData = $topicData['topics'];
            foreach ($topicData as $topic) {
                if ($topic == $skipTopic) {
                    continue;
                }
                $uri = "/v3/kafka/local/consumer/{$groupPix}{$topic}/lag";
                $res = json_decode(\yii::sendCurlGet($api . $uri), true);
                echo '<pre>';
                print_r($res);
            }
        }
        exit();
    }

    /**
     * 延迟出票重新对奖 
     * @return type
     */
    public function actionDelayOutAward() {
        $request = \Yii::$app->request;
        $lotteryCode = $request->post('lotteryCode', '');
        $resultData = $request->post('resultData', '');
        if (empty($lotteryCode) || empty($resultData)) {
            return \Yii::jsonError(100, '参数缺失');
        }
        $retData = json_decode($resultData, true);
        SyncApiRequestService::awardLottery($lotteryCode, $retData);
        return \Yii::jsonResult(600, '成功', true);
    }

    /**
     * 更新微信access_token
     * @return type
     */
    public function actionUpdateAccess_token() {
        $appId = \Yii::$app->params['wechat']['appid'];
        $appSecret = \Yii::$app->params['wechat']['appsecret'];

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
        $access_token = '';
        $flag = 0;
        while (!$access_token) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result, true);
            if (isset($result['access_token'])) {
                $access_token = $result['access_token'];
            }
            $flag++;
            if ($flag == 10) {
                break;
            }
        }
        \Yii::redisSet('wxgzh_token', $access_token, 6000);
        return $this->jsonResult(600, 'succ', ['token' => $access_token, 'flag' => $flag]);
    }

    /**
     * 统计用户数据(每日更新)
     */
    public function actionUserStatis() {
        $sTime = \Yii::$app->request->get('s_time');
        $eTime = \Yii::$app->request->get('e_time');
        $all = \Yii::$app->request->get('all');
        if ($all) { // 全量统计
            $where = [
                'in',
                'status',
                LotteryOrder::$successOrderStatus
            ];
        } else {
            if (!$sTime || !$eTime) {
                $dataRange = [
                    date("Y-m-d 00:00:00", strtotime("-1 day")),
                    date("Y-m-d 23:59:59", strtotime("-1 day"))
                ];
            } else {
                $dataRange = [
                    $sTime,
                    $eTime
                ];
            }
            $where = ['and', ['in', 'status', LotteryOrder::$successOrderStatus], ['>=', 'out_time', $dataRange[0]], ['<=', 'out_time', $dataRange[1]]];
        }
        $udata = [];
        foreach (LotteryOrder::find()->where($where)->asArray()->batch(1000) as $data) {
            foreach ($data as $val) {
                if (!isset($udata[$val['cust_no']])) {
                    $udata[$val['cust_no']] = [];
                }
                if (!isset($udata[$val['cust_no']]['orderMoney'])) {
                    $udata[$val['cust_no']]['orderMoney'] = 0;
                }
                if (!isset($udata[$val['cust_no']]['order_num'])) {
                    $udata[$val['cust_no']]['order_num'] = 0;
                }
                if ($val['source'] == 4) {
                    $proUser = ProgrammeUser::find()->where([
                                'programme_id' => $val['source_id']
                            ])->all();
                    foreach ($proUser as $pu) {
                        if (!isset($udata[$pu['cust_no']]['pro_order_money'])) {
                            $udata[$pu['cust_no']]['pro_order_money'] = 0;
                        }
                        if (!isset($udata[$pu['cust_no']]['pro_order_num'])) {
                            $udata[$pu['cust_no']]['pro_order_num'] = 0;
                        }
                        $udata[$pu['cust_no']]['pro_order_money'] += $pu['bet_money'];
                        $udata[$pu['cust_no']]['pro_order_num'] += 1;
                    }
                } else {
                    $realMoney = PayRecord::findOne([
                                "order_code" => $val['lottery_order_code'],
                                'pay_type' => 1
                    ]);
                    $udata[$val['cust_no']]['orderMoney'] += $realMoney->pay_money;
                    $udata[$val['cust_no']]['order_num'] += 1;
                }
            }
        }
        if ($udata) {
            foreach ($udata as $cusNo => $v) {
            	if(!isset($v['pro_order_money'])){
            		$v['pro_order_money']=0;
            	}
            	if(!isset($v['pro_order_num'])){
            		$v['pro_order_num']=0;
            	}
                if (UserStatistics::findOne(['cust_no' => $cusNo])) {
                    if ($all) {
                        UserStatistics::updateAll(['order_money' => $v['orderMoney'], 'pro_order_money' => $v['pro_order_money'], 'order_num' => $v['order_num'], 'pro_order_num' => $v['pro_order_num'], 'u_time' => time()], ['cust_no' => $cusNo]);
                    } else {
                        UserStatistics::updateAll(['order_money' => new Expression('order_money+' . $v['orderMoney']), 'pro_order_money' => new Expression('pro_order_money+' . $v['pro_order_money']), 'order_num' => new Expression('order_num+' . $v['order_num']), 'pro_order_num' => new Expression('pro_order_num+' . $v['pro_order_num']), 'u_time' => time()], ['cust_no' => $cusNo]);
                    }
                } else {
                    $insert = ['cust_no' => $cusNo, 'order_money' => $v['orderMoney']??0, 'pro_order_money' => $v['pro_order_money'], 'order_num' => $v['order_num']??0, 'pro_order_num' => $v['pro_order_num'], 'c_time' => time(), 'u_time' => time()];
                    \Yii::$app->db->createCommand()->insert('user_statistics', $insert)->execute();
                }
            }
        }
        echo 'success';
        exit;
    }

    /**
     * 自动出票重新出票
     * @return type
     */
    public function actionAgainAutoOut() {
        $request = \Yii::$app->request;
        $autoCode = $request->post('autoCode', '');
        $thirdOrderCode = $request->post('thirdOrderCode', '');
        if (empty($autoCode)) {
            return \Yii::jsonError(100, '参数缺失');
        }
        KafkaService::addQue('AutoOutTicket', ['autoCode' => $autoCode, 'thirdOrderCode' => $thirdOrderCode], true);
        return \Yii::jsonResult(600, '成功', true);
    }
    /**
     * 定时更新自动出票中奖金额
     */
    public function actionGetWinMoney(){
    	$orders = LotteryOrder::find()
    	->select(['lottery_order_code','auto_type','zmf_award_money','deal_status'])
    	//            ->leftJoin()
    	->andwhere(['lottery_order.auto_type'=>2,'status' =>4])
    	->andWhere(['in', 'deal_status', [1, 2]])
    	->andWhere(['=','zmf_award_money',0])
    	->limit(100)
    	->asArray()
    	->all();
    	$ret=[];
    	foreach ($orders as $order){
    		$orderData = AutoOutOrder::find()->where([
    			'order_code' => $order['lottery_order_code']
    		])->asArray()->all();
    		if(!$orderData){
    			continue;
    		}
    		if($orderData[0]['source']=='NM'||$orderData[0]['source']=='JW'){
    			$ret[]=$order;
    			KafkaService::addQue('AutoWinMoney', ['order_code'=>$order['lottery_order_code']]);
    		}
    		//加入队列
    	}
    	return $this->jsonResult(600,'succ',$ret);
    }
    /**
     * 定时派奖(自动出票)
     */
    public function actionAutoAward(){
    	$orders = LotteryOrder::find()
    	->select(['lottery_order_code','auto_type','zmf_award_money','win_amount'])
    	//            ->leftJoin()
    	->andwhere(['lottery_order.auto_type'=>2,'status' =>4])
    	->andWhere(['in', 'deal_status', [1, 2]])
    	->andWhere(['>','zmf_award_money',0])
    	->andWhere(['=','source',7])
    	->limit(100)
    	->asArray()
    	->all();
    	if(!$orders){
    		echo 'nodata';
    		exit;
    	}
    	foreach ($orders as $v){
    		if($v['win_amount']<=10000){
    			KafkaService::addQue('AutoAward', ['order_code'=>$v['lottery_order_code']]);
    		}
    	}
    	return $this->jsonResult(600,'succ',1);
    }
    /**
     * 说明:主动查询竞彩出票结果接口 LV->LPS
     * @author chenqiwei
     * @date 2018/1/10 下午2:50
     * @param
     * @return json
     */
    public function actionAutoOrderCheck(){
    	$request = \Yii::$app->request;
    	$order_code = $request->get('order_code','');
    	$source = $request->get('source','');
    	if($order_code){
    		$data = [
    			'order_code'=>$order_code,//玩法代码
    			'source'=>$source,//来源
    		];
    		KafkaService::addQue('AutoOrderCheck', $data);
    		return $this->jsonResult(600,'队列添加成功',$data);
    	}
    
    	$startTime='2018-02-28 11:07:33';
    	$where=[];
    	array_push($where, 'and');
    	array_push($where, "create_time >'{$startTime}'");
    	array_push($where, 'status = 2');
    	$maxTime = date('Y-m-d H:i:s', time() - 600);
    	array_push($where, "create_time < '{$maxTime}'");
    	$unCallBack=AutoOutOrder::find()->where($where)->limit(100)->asArray()->all();
    	if(!$unCallBack){
    		echo 'nodata';
    		exit;
    	}
    	foreach ($unCallBack as $v){
    		$data = [
    			'order_code'=>$v['out_order_code'],//玩法代码
    			'source'=>$v['source'],//
    		];
    		KafkaService::addQue('AutoOrderCheck', $data);
    	}
    	echo 'complete';
    	exit;
    	// $this->jsonResult(600,'succ', $ret);
    
    }

}
