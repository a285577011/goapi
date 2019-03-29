<?php
namespace app\modules\test\controllers;

use app\modules\orders\models\ZmfOrder;
use app\modules\user\models\CouponsDetail;
use app\modules\user\models\User;
use app\modules\user\models\UserGlCoinRecord;
use yii\web\Controller;
use app\modules\common\models\BettingDetail;
use app\modules\common\models\LotteryRecord;
use app\modules\components\resque\demo\lottery_job;
use app\modules\welfare\models\SsqTrendChart;
use app\modules\test\services\ITestService;
use JPush\PushPayload;
use yii\redis\Connection;
use app\modules\common\helpers\OrderNews;
use app\modules\common\models\LotteryOrder;
use app\modules\common\helpers\Winning;
use app\modules\common\helpers\ArticleRed;
use Kafka\Producer;
use Monolog\Logger;
use Kafka\ProducerConfig;
use app\modules\tools\helpers\Kafkas;
use app\modules\common\models\ScheduleResult;
use app\modules\common\models\PayRecord;
use app\modules\user\models\UserGrowthRecord;
use app\modules\user\models\UserGlCoin;
use app\modules\user\models\userSgin;
use yii\db\Expression;
use yii\db\Query;
use app\modules\common\services\ProgrammeService;
use app\modules\user\models\Gift;
use app\modules\common\models\Store;
use app\modules\openapi\services\SoccerScheduleService;
use app\modules\common\services\KafkaService;
use app\modules\tools\helpers\Des;
use app\modules\tools\helpers\Zmf;
use yii\BaseYii;
use app\modules\tools\kafka\ApiTest;
use app\modules\tools\kafka\LotteryJob;
use app\modules\common\models\Queue;
use app\modules\openapi\services\ArticlesService;
use app\modules\common\services\CommonService;
use app\modules\tools\kafka\AutoOrderCreate;
use \JPush\Client;
use app\modules\user\helpers\WechatTool;
use \Yii;
use app\modules\orders\helpers\OrderDeal;
use app\modules\store\helpers\TicketPrint;
use app\modules\store\services\BanQuancService;
use app\modules\store\services\FootBifenService;
use app\modules\store\services\FootPrintService;
use app\modules\tools\kafka\AutoAward;
use app\modules\common\services\OrderService;


// use app\modules\components\resque\demo\LotteryQueue;
require_once \Yii::$app->basePath.'/vendor/resque/lottery/lottery_queue.php';
require_once \Yii::$app->basePath.'/vendor/resque/lottery/lottery_queue.php';


class TestController extends Controller
{
    private $key = 'DF65E8553A57119A219DF199'; //密钥
    private $iv = '12345678';//IV 向量
    private $testService;
    public $enableCsrfValidation = false;
   
    public function __construct($id,$module,$config=[],ITestService $testService)
    {
        parent::__construct($id,$module,$config);
        $this->testService = $testService;
    }
    public function actionTest(){
        //nihao

        $lotteryRecord = LotteryRecord::find()->select(['periods','lottery_numbers'])->where(['lottery_code'=>'1001','status'=>2])->orderBy('periods desc')->asArray()->one();
        if(empty($lotteryRecord)){
            
        }
//         $start = microtime(true);
//         $end= microtime(true);
//         echo ($end-$start)*1000;die;
        $details= BettingDetail::find()
            ->select(['betting_detail_id','lottery_id','bet_val'])
            ->where(['lottery_id'=>'1001'])
//             ->limit(100)
            ->asArray()
            ->all();
        $res = [];
        echo $lotteryRecord['lottery_numbers'];
        foreach ($details as $k=>$detail){
            $ret = [];
            $ret =$this->contrast($detail['bet_val'],$lotteryRecord['lottery_numbers']);
            $res[$k]="彩种:{$detail['lottery_id']}  号码：{$detail['bet_val']} 蓝球：{$ret['blue']}  红球：{$ret['red']} ";
        }
        print_r($res);die;
        
    }
    public function contrast($str1,$str){
        $Arr = explode('|', $str);
        $Arr2 = explode('|', $str1);
        $blueArr = explode(',', $Arr[0]);
        $blueArr1 = explode(',', $Arr2[0]);
        $redArr = explode(',', $Arr[1]);
        $redArr1 = explode(',', $Arr2[1]);
        $ret['blue'] = count(array_intersect($blueArr1,$blueArr));
        $ret['red'] = count(array_intersect($redArr1,$redArr));
        return $ret;
    }
    
    public function actionResqueTest(){
        $lotteryqueue = new \LotteryQueue();
        $s = $lotteryqueue->pushQueue('lottery_job','default');
        return $s;
    }
    
    public function actionCreateTrend(){
        $openCode = '11,15,20,22,25,30|05';
        $periods = '2017038';
        $ret = SsqTrendChart::createOmission($periods, $openCode);
        $this->jsonResult(600, 'chegng', $ret);
    }
    
    /**
     * 说明: 
     * @author  kevi
     * @date 2017年6月7日 下午9:00:06
     * @param
     * @return
     */
    public function actionGetTrend(){
        $request = \Yii::$app->request;
        // 分页
        $page = $request->post('page', '1');
        $pagesize = $request->post('page_size', '20');
        if ($pagesize > 50) {$this->jsonError(109, '一次请求数不得超过50条');}
        $pageConditions = ['page' => $pagesize * ($page - 1),'page_size' => $pagesize];
        
        $a = SsqTrendChart::find()->limit($pageConditions['page_size'])->offset($pageConditions['page'])->asArray()->all();
        $countArr = $avgOmArr = $maxOmArr = $maxLcArr = [];
        $count = $avgOm = $maxOm = $maxLc = 0;
        for ($i=0;$i<=32;$i++){
            $countArr[$i] = $avgOmArr[$i] = $maxOmArr[$i] =  0;
            $maxLcArr[$i] = 1;
        }
        foreach ($a as $k=>$v){
            $a[$k]['red_omission'] = explode(',',$v['red_omission']);
            $a[$k]['blue_omission'] = explode(',',$v['blue_omission']);
            foreach($a[$k]['red_omission'] as $kk=>$vv){
                if($vv == 0){//出现次数
                   $countArr[$kk]+=1;
                }
                if($vv>=$maxOmArr[$kk]){//最大遗漏
                    $maxOmArr[$kk] = $vv;
                }
                if($k>0 && ($vv==$a[$k-1]['red_omission'][$kk])){
                    $maxLcArr[$kk]+=1;
                }
            }
        }
        $ret = [];
        $ret['trend_data'] = $a;
        $ret['count'] = $countArr;
        $ret['avgOm'] = $avgOmArr;
        $ret['maxOm'] = $maxOmArr;
        $ret['maxLc'] = $maxLcArr;
        $this->jsonResult(600, 'succ', $ret);
    }
    
    public function actionPasswordHash(){
        $request = \Yii::$app->request;
        $password = $request->post('password');
        $newPassword = \Yii::$app->getSecurity()->generatePasswordHash($password);
        echo $newPassword;
    }
    
    
    public function actionIndex(){
//        \Yii::redisSet('a',date('YmdHis'));
//        echo 'test_index';die;
        return 1;
    }
    
    
    public function actionAcc(){
        $words= explode('|','97548*3010(3)|97549*3010(3)|97551*3010(3)|97553*3010(3)|97554*3010(3)|97555*3010(3)|97556*3010(3)|97557*3010(3)|97558*3010(3)|97559*3010(3)|97560*3010(3)|97562*3010(3)|97563*3010(3)|97564*3010(3)');
        
        $ret = $this->testService->aa($words,8);
        $this->jsonResult(600, 2, $ret);
    }
    
    public function actionL(){
        $redis = \yii::$app->redis;
        $ret = $redis->executeCommand('lpush',["kevi_test_cron",date('Y-m-d H:i:s')]);
//        echo \Yii::redisSet('kevi_test_cron', date('Y-m-d H:i:s'));
    }
    
    public function actionJpushTest(){
//            $client = new \JPush\Client();
//            $push_payload = $client->push()
//                ->setPlatform('all')
//                ->addAllAudience()
//                ->setNotificationAlert('Hi, JPush');
//            try {
//                $response = $push_payload->send();
//                print_r($response);
//            } catch (\JPush\Exceptions\APIConnectionException $e) {
//                print $e;
//            } catch (\JPush\Exceptions\APIRequestException $e) {
//                print $e;
//            }
//        $android_notification = array(
//        'title' => 'hello jpush',
//        'build_id' => 2,
//        'extras' => array(
//        'key' => 'value',
//        'jiguang'
//        ),
        $redis = \Yii::$app->redis;
        $jpush = new \JPush\Client();
        try {
            $response= $jpush->push()
                    ->setPlatform(array('ios','android'))
                    ->addAlias(array("gl00005091_test"))
                    ->androidNotification("第20180121期开奖结果为 01,05,06,07,08,13 10,15",array(
                        "title"=>"开奖通知",
                        "style"=>2,
                       "big_text"=>1,
                        "inbox"=>[
                            "0"=>"11111111111111",
                            "1"=>"22222222222222"
                        ],
                        'extras' => [
                            "url"=>"http://php.javaframework.cn/results",
//                            "android_pro"=>"这是什么参数",
//                            "open_type"=>11
                         ]
                    ))
                    ->iosNotification(array(
                        "title" => "开奖通知",
                        "body" =>"第20180121期开奖结果为01,05,06,07,08,13 10,15",
                    ),array(
                        'badge' => '+1',
                        'content-available' => true,
                        'mutable-content' => true,
                        'extras' => [
                            "url"=>"http://php.javaframework.cn/results",
//                            "android_pro"=>"这是什么参数",
//                            "open_type"=>11
                         ]
                    ))
//                    ->message('推送测试',[
//                        'title'=>'消息内容',
//                        'extras' => [
//                        'open_type' => 11,
//                         ]
//                     ])
                    ->options(array(
                               // sendno: 表示推送序号，纯粹用来作为 API 调用标识，
                               // API 返回时被原样返回，以方便 API 调用方匹配请求与返回
                               // 这里设置为 100 仅作为示例
                               // 'sendno' => 100,
                               // time_to_live: 表示离线消息保留时长(秒)，
                               // 推送当前用户不在线时，为该用户保留多长时间的离线消息，以便其上线时再次推送。
                               // 默认 86400 （1 天），最长 10 天。设置为 0 表示不保留离线消息，只有推送当前在线的用户可以收到
                               // 这里设置为 1 仅作为示例
                               // 'time_to_live' => 1,
                               // apns_production: 表示APNs是否生产环境，
                               // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境
                               'apns_production' => false,
                               // big_push_duration: 表示定速推送时长(分钟)，又名缓慢推送，把原本尽可能快的推送速度，降低下来，
                               // 给定的 n 分钟内，均匀地向这次推送的目标用户推送。最大值为1400.未设置则不是定速推送
                               // 这里设置为 1 仅作为示例
                               // 'big_push_duration' => 1
                           ))
                     ->send();
                    print_r($response);
                } catch (\JPush\Exceptions\APIConnectionException $e) {
                    print $e;
                } catch (\JPush\Exceptions\APIRequestException $e) {
                    print $e;
                }
    }
    
    
    public function actionMysqlTest(){
        $request = \Yii::$app->request;
        $periods = $request->get('periods','17114');
        $openNumber = $request->get('openNumber','05,08,17,18,23|04,12');
        $win = new Winning();
        //$ret 返回修改的 条数
        $ret = $win->lottery2001Level($periods, $openNumber);
        return $this->jsonResult(600, 'succ', $ret);
    }
    
    public function actionCashArticle() {
        $request = \yii::$app->request;
        $mid = $request->get('mid', '');
        if (empty($mid)) {
            return $this->jsonError(100, 'mid不可为空');
        }
        $articleRed = new ArticleRed();
        $ret = $articleRed->acticlePreResult($mid);
        return $this->jsonResult(600, '成功', $ret['data']);
//        return $ret;
    }
    
    public function actionCash() {
        $articleRed = new ArticleRed();
        $ret = $articleRed->cashArticle(172, 'gl00000045', 'gl00000045',1, 25, 'GLCART1710131745P0000001', '8', '收费方案-收款');
        return $this->jsonResult(600, 'succ', $ret['msg']);
    }
    
    public function actionElevenWin() {
        $lotteryCode = 2005;
        $periods = '2017101852';
        $openNums = '08,09,07,06,02';
        $winning = new Winning();
        $ret = $winning->lottery11X5Level($lotteryCode, $periods, $openNums);
        return $this->jsonResult(600, 'cheng', $ret);
    }
    
    
    /**
     * 获取订单加入备份队列
     * @auther GL xiejh
     * @return type
     */
    public function actionAddOrderList(){
        $scheduleService = new OrderService();
        $countData = $scheduleService->addresque();
        return $this->jsonResult(600, '操作成功', $countData);
    }
    public function actionTestQue(){
    	sleep(10);
    	echo 111;
    	die;
        	\Yii::info(LotteryOrder::find()->where(["lottery_order.lottery_order_code" => 1, 'lottery_order.store_id' => 1, 'lottery_order.deal_status' => 1, 'lottery_order.status' => 4])->createCommand()->getRawSql(), 'backuporder_log');
        	echo 111;die;
    }

    /**
     * 成长值增加或减少
     */
    public function actionTest10(){
        $info = [
            'type' => 1,
            'growth_source' => 10,
            'order_source' => 10,
            'order_code' => 101,
            'growth_value' => 10,
            'growth_remark' => '购彩赠送',
        ];
        $UserGrowthRecord = new UserGrowthRecord();
        $res = $UserGrowthRecord -> updateGrowth(208, '', 7);

        return $this->jsonResult(600, '操作成功', $res);

    }

    /**
     * 积分增加
     */
    public function actionTest11(){
        $info = [
            'type' => 1,
            'coin_value'  => 200,
            'remark'  => "购彩赠送",
            'coin_source'  => 1,
            'order_source'  => 1,
            'order_code'  => 1000001,
        ];
        $UserIntergal = new UserGlCoinRecord();
        $res = $UserIntergal -> updateGlCoin(208, $info);
        if($res){
            return $this->jsonResult(600, $res['msg'], '');
        } else {
            return $this->jsonError(100, $res['msg'], '');
        }
    }

    public function actionTest12(){
        $userId = 208;
        $request = \Yii::$app->request;
        $page = $request->post('page_num', 1);
        $size = $request->post('size', 10);
        $type = $request->post('type', 0);
        $UserGlCoin = new UserGlCoin();
//        $res = $UserGlCoin ->getGlCionNum($userId);
        $res = $UserGlCoin->getGlCionList($userId, $page, $size, $type);

        return $this->jsonResult(600, '操作成功', $res);
    }

    /**
     * 每日签到
     */
    public function actionAddsgin(){
        $userId = 208;
        //查询今天是否签到
        $UserSgin = new userSgin();
        $bool = $UserSgin->todaySginData($userId);
        if($bool === false){
            return $this->jsonError(109, '今天已经签到！');
        }

        //新增签到表
        $res = $UserSgin->addSgin($userId);
        if($res['code'] === 109){
            return $this->jsonError(109, $res['msg']);
        }

        //新增成长值
        $UserGrowthRecord = new UserGrowthRecord();
        $ret = $UserGrowthRecord -> updateGrowth($userId, '', 1);
        if($ret['code'] == 600){
            return $this->jsonResult(600, '签到成功！', '');
        } else {
            return $this->jsonError(109, '签到失败，请刷新再试');
        }
    }

    /*用户资料完善思送成长值*/
    public function actionChecktest(){
        $userId = 208;
        $UserGrowthRecord = new UserGrowthRecord();
        $res = $UserGrowthRecord ->addInfoPerfect($userId);
        var_dump($res);
    }

    public function actionGift(){
//        $userId = $this->userId;
//        $request = \yii::$app->request;
//        $page_num = $request->post('page_num',1);
//        $size = $request->post('size',10);
//        //获取咕啦币
//        $user_glcoin = (new Query())->select('user_glcoin')->from('user_funds')->where(['user_id'=>$userId])->one();
//        //获取礼品列表
        $gift = new Gift();
//        $data = $gift -> getGiftLists($page_num, $size);
//        $data['user_glcoin'] = $user_glcoin['user_glcoin'];
//        return $this->jsonResult(600, '获取成功', $data);
        $custNo = 'gl00025112';
        $request = \yii::$app->request;
        $page_num = $request->post('page_num',1);
        $size = $request->post('size',10);
        $type = $request->post('type', 1);//1=未使用，2=使用记录，3=已过期
        $CouponsDetail = new CouponsDetail();
        $res = $CouponsDetail->userCouponLists($custNo, $page_num, $size, $type);
        return $this->jsonResult(600, '获取成功', $res);

    }

    public function actionWebHook(){
        \Yii::redisSet('webhook_test', time());
        $this->jsonResult(600, 'succ', 1);
    }

    public function actionCoupons(){
        $custNo = 'gl00025112';
        $conversion_code = '2E12BA643F19EA9';
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
    
    public function actionC(){
//         118.140889,24.500645 118.127211,24.501314
        $lat1 = 118.140889;
        $lng1 =24.500645;
        $lat2 =118.127211;
        $lng2 =24.501314;
        $miles = true;
        
        $pi80 = M_PI / 180;
        $lat1 *= $pi80;
        $lng1 *= $pi80;
        $lat2 *= $pi80;
        $lng2 *= $pi80;
        $r = 6372.797; // mean radius of Earth in km
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        $a = sin($dlat/2)*sin($dlat/2)+cos($lat1)*cos($lat2)*sin($dlng/2)*sin($dlng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $km = $r * $c;
        return ($miles ? ($km * 0.621371192) : $km);
    }

    public function actionGllist(){
        $userId = 208;
        $request = \yii::$app->request;
        $page_num = $request->post('page_num',1);
        $size = $request->post('size',10);
        $type = $request->post('type', 0);//0=全部，1=获取得到的，2=使用掉的
        $userGlCoin = new UserGlCoinRecord();
        $res = $userGlCoin->getGlCionList($userId, $page_num, $size, $type);
        return $this->jsonResult(600, '获取成功', $res);
    }
    
     public function actionGetTeamHisMatch() {
        $request = \Yii::$app->request;
        $teamId = $request->post('team_id', '');
        if(empty($teamId)) {
            return $this->jsonError(109, '参数缺失');
        }
        $soccerService = new SoccerScheduleService();
        $data = $soccerService->getTwoYearsHistoryMatch($teamId);
        return $this->jsonResult(600, "获取成功", $data);
    }
    /**
     * 获取足球球队列表及对应体彩名称
     */
    public function actionGetTeamInfo() {
        $request = \Yii::$app->request;
        $teamId = $request->post('team_id', '');
        if($teamId=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $soccerService = new SoccerScheduleService();
        $data = $soccerService->getSoccerTeamInfo($teamId);
        return $this->jsonResult(600, "获取成功", $data);
    }
    public function actionGetSoccerLeague() {
        $request = \Yii::$app->request;
		$leagueId = $request->post('league_id', '');
		$scheduleService = new SoccerScheduleService();
		$data = $scheduleService->getLeague($leagueId);
		return $this->jsonResult(600, '获取成功', $data);
	}

	public function actionGetSoccerSchedule()
	{
        $request = \Yii::$app->request;
        $scheduleId = $request->post('schedule_id', '');
        if($scheduleId=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getScheduleInfo($scheduleId);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    public function actionGetAsianInfo() {
        $request = \Yii::$app->request;
        $scheduleId = $request->post('schedule_id', '');
        if($scheduleId=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getAsianHandicap($scheduleId);
        return $this->jsonResult(600, '获取成功', $data);
    }
	public function actionTestAddQue()
	{
		KafkaService::addQue('CallbackThird', ['code'=>'J7r1Q520dSG30w690oY3','params'=>[]]);
	}
    /**
     * 获取亚盘列表
     */
     public function actionGetAsianList() {
        $request = \Yii::$app->request;
        $name = $request->post('name', '');
        if($name=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $type = $request->post('type', '2');
        $date = $request->post('date', '');
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getCompanyAsianList($name,$type,$date);
        return $this->jsonResult(600, '获取成功', $data);
    }
    /**
     * 根据赛事ID获取实况
     */
    public function actionGetScheduleLive(){
        $request = \Yii::$app->request;
        $scheduleId = $request->post('schedule_id', '');
        if($scheduleId=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getScheduleLive($scheduleId);
        return $this->jsonResult(600, '获取成功', $data);
    }
    /**
     * 根据日期获取实况列表
     * 日期必须大于或等于当前日期的前一天
     */
    public function actionGetScheduleLiveList(){
       $request = \Yii::$app->request;
       $date = $request->post('date', '');
        if($date=="") {
           return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getScheduleLiveList($date);
        return $this->jsonResult(600, '获取成功', $data); 
    }
    /**
     * 获取队伍及所属联赛列表
     */
    public function actionGetTeamLeagueList(){
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getTeamLeagueList();
        return $this->jsonResult(600, '获取成功', $data); 
    }
    /**
     * 获取联赛队伍近10场的总入球数统计
     * 分主客场
     */
    public function actionGetTeamHisTotal(){
        $request = \Yii::$app->request;
        $leagueId = $request->post('league_id', '');
         if($leagueId=="") {
            return $this->jsonError(109, '参数缺失');
         }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getTeamHisTotal($leagueId);
        return $this->jsonResult(600, '获取成功', $data); 
    }
    /**
     * 获取联赛队伍近10场的全场入球统计
     */
    public function actionGetTeamBoalCount(){
        $request = \Yii::$app->request;
        $leagueId = $request->post('league_id', '');
         if($leagueId=="") {
            return $this->jsonError(109, '参数缺失');
         }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getTeamBoalCount($leagueId);
        return $this->jsonResult(600, '获取成功', $data); 
    }
    /**
     * 获取联赛常见赛果统计
     */
    public function actionGetLeagueTotal(){
        $request = \Yii::$app->request;
        $leagueId = $request->post('league_id', '');
         if($leagueId=="") {
            return $this->jsonError(109, '参数缺失');
         }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getTeamBoalCount($leagueId);
        return $this->jsonResult(600, '获取成功', $data); 
    }
    /**
     * 获取联赛队伍近10场赛果半全场数据统计
     */
    public function actionGetTeamBqcTotal(){
        $request = \Yii::$app->request;
        $leagueId = $request->post('league_id', '');
         if($leagueId=="") {
            return $this->jsonError(109, '参数缺失');
         }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getTeamBqcTotal($leagueId);
        return $this->jsonResult(600, '获取成功', $data); 
    }
    /**
     * 获取联赛队伍排名
     */
    public function actionGetTeamRank(){
        $request = \Yii::$app->request;
        $leagueId = $request->post('league_id', '');
         if($leagueId=="") {
            return $this->jsonError(109, '参数缺失');
         }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getTeamRank($leagueId);
        return $this->jsonResult(600, '获取成功', $data); 
    }
    /**
     * 获取联赛队伍入球总数及单双数统计
     */
    public function actionGetTeamBoalNumTotal(){
        $request = \Yii::$app->request;
        $leagueId = $request->post('league_id', '');
         if($leagueId=="") {
            return $this->jsonError(109, '参数缺失');
         }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getTeamBoalNumTotal($leagueId);
        return $this->jsonResult(600, '获取成功', $data); 
    }
    
    /**
     * 获取单场比赛欧赔指数
     */
    public function actionGetEuropeInfo() {
        $request = \Yii::$app->request;
        $scheduleId = $request->post('schedule_id', '');
        if($scheduleId=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getEuropeHandicap($scheduleId);
        return $this->jsonResult(600, '获取成功', $data);
    }
    /**
     * 获取欧赔指数变化历史
     */
    public function actionGetCompanyEuropeChange() {
        $request = \Yii::$app->request;
        $scheduleId = $request->post('schedule_id', '');
        $companyName = $request->post('company_name', '');
        if($scheduleId==""||$companyName=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getEuropeHandicapChange($scheduleId,$companyName);
        return $this->jsonResult(600, '获取成功', $data);
    }
    /**
     * 获取某公司某时间段的欧赔列表
     */
     public function actionGetEuropeList() {
        $request = \Yii::$app->request;
        $companyName = $request->post('company_name', '');
        if($companyName=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $type = $request->post('type', '2');
        $date = $request->post('date', '');
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getCompanyEuropeList($companyName,$type,$date);
        return $this->jsonResult(600, '获取成功', $data);
    }


    public function actionChangeret(){
        $query = new Query();
        $data = $query -> from('store') -> select('store_id,coordinate') -> all();
        foreach($data as $v){
            if(empty($v['coordinate'])){
                continue;
            }
            $url = "http://api.map.baidu.com/geoconv/v1/?coords=".$v['coordinate']."&from=3&to=5&ak=03LrXt6Cphqm9iCpYcj4fXC5qBR7nl4e";
            $res = \Yii::sendCurlGet2($url);
            $res = json_decode($res, true);
            $newData = [
                'coordinate' => $res['result'][0]['x'].','.$res['result'][0]['y'],
            ];
            $res1 = \Yii::$app->db->createCommand()->update('store', $newData, ['store_id' => $v['store_id']])->execute();
            if($res1 === false){
                echo '门店id-'.$v['store_id'].'：失败'.'<br>';
            } else {
                echo '门店id-'.$v['store_id'].'：成功'.'<br>';
            }
        }
    }

    public function actionKatest(){
    	//\Yii::$app->params['shop_host']
    	//$a=KafkaService::addLog('test', 111);
    	return 1;
    }
    public function actionKatest1(){
    	for ($i=0;$i<30;$i++){
    		$a=KafkaService::addQue('ApiTest', [],false);
    	}
    	
    	return 1;
    }
    public function getMillisecond() {
    	list($s1, $s2) = explode(' ', microtime());
    	return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
    public function actionKatest2(){
    	for ($i=0;$i<20;$i++){
    		$a=KafkaService::addProdQue('ApiTest', [],false);
    	}
    	return 2;
    }
    public function actionZmf1101(){
        //1接收zmf的xml参数
        $request = \Yii::$app->request;
        $paramsXml = $request->getRawBody();

        $zmfObj = new Zmf();
        $ret = $zmfObj->to1101($paramsXml);

        return $ret;
}


    public function actionTestMoney(){
    	try {
    		$a=\Yii::$app->db->beginTransaction();
    		$data=(new \yii\db\Query())->select('*')->from('test_3')->where(['id'=>1])->all();
    		if($data[0]['money']<=0){
    			throw new \Exception('余额不足1',1001);
    		}
    		$res=\Yii::$app->db->createCommand()->update('test_3', ['money'=>new Expression('money-10')], 'id=1 and money>0')->execute();
    		if(!$res){
    			throw new \Exception('余额不足',1000);
    		}
    		$a->commit();
    	} catch (\Exception $e) {
    		$a->rollBack();
    		\Yii::info($e->getMessage(), 'cron_log');
    		echo $e->getMessage();die;
    	}

    }
    public function actionTestRdkafka(){
    	$rk = new \RdKafka\Producer();
    	 var_dump($rk);
    	exit();
    }
    
     /**
     * 获取赛事方案数据列表
     * @return type
     */
    public function actionGetScheduleArticles() {
        $request = \Yii::$app->request;
        $startDate = $request->post('startDate', '');
        $endDate = $request->post('endDate', '');
        $publicService = new ArticlesService();
        $data = $publicService->getArticlesList($startDate,$endDate);
        return $this->jsonResult(600, '获取成功', $data);
    }
    public function actionTestCurl(){
    	
    	$url='http://php.javaframework.cn/api/test/test/katest';
    	$data=file_get_contents($url);
    	return json_encode($data);
    }
    
    
    public function actionJpushNotice(){
        $client = new Client();
        $push_payload = $client->push()
                ->setPlatform('all')
                ->addAllAudience()
                ->setNotificationAlert("开奖通知，第".$perios."期11选五开奖号码" .$lotteryNum);
        try {
            $response = $push_payload->send();
            print_r($response);
        } catch (\JPush\Exceptions\APIConnectionException $e) {
            print $e;
        } catch (\JPush\Exceptions\APIRequestException $e) {
            print $e;
        }
    }

    public function actionT(){
    	$a=new AutoAward();
    	print_r($a->run(['queueId'=>26442,'order_code'=>'GLCHHGG18052410T0000002']));die;
    	$a=FootPrintService::getRXCoord(LotteryOrder::findOne(['lottery_order_id'=>17445]),1);
    	echo '<pre>';
    	print_r($a);die;
    	$data=LotteryOrder::findOne(['lottery_order_id'=>16994]);
    	echo '<pre>';
    	print_r(OrderDeal::deal($data->lottery_id, $data->bet_val, $data->play_code, $data->build_code, $data->bet_double));die;
        //$request = \Yii::$app->request;
        //$messageId = $request->post('messageId');
        $startTime='2018-02-28 11:07:33';
        $where=[];
        array_push($where, 'and');
        //array_push($where, "create_time >'{$startTime}'");
        array_push($where, 'status = 0');
        $maxTime = date('Y-m-d H:i:s', time() - 600);
        array_push($where, "create_time < '{$maxTime}'");
        $unCallBack=ZmfOrder::find()->where($where)->limit(100)->asArray()->all();
        print_r($unCallBack);die;
    }

    /**
    * 说明: 
    * @author  kevi
    * @date 2018年
    * @param   
    * @return 
    */
    public function actionRedisGet(){
        $redis = \yii::$app->redis;
        $ret = $redis->executeCommand('lpop', ["numberlist"]);
        $redis->executeCommand('rpush', ["numberlist2", $ret]);

        return 1;    
    }


    public function actionE(){
    	/*$lottery_code=1001;
    	$money=6;
    	$mobile=13960774169;
    	$user=User::findOne(['user_tel'=>$mobile]);
    	if(!$user){
    		return $this->jsonError(100, '用户不存在');
    	}
    	$store = OrderDeal::getOutStore($lottery_code, 1, 1);
    	if($store['code']!=600){
    		return $this->jsonError($store['code'], $store['msg']);
    	}
		$data=OrderService::giftLottery($lottery_code,$money,$store['data']['store_id'],$store['data']['store_no'],$user->cust_no,$user->user_id);
		print_r($data);die;
		die;*/
        $a = 213941.80;
        $b = 213293.25;
        $c = bcsub($a,$b,2);
//        $a = ;


        return $c;
    }




}
