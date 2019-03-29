<?php
namespace app\modules\cron\controllers;

use app\modules\common\models\LotteryTime;
use yii\web\Controller;
use app\modules\common\models\LotteryRecord;
use app\modules\common\helpers\Trend;
use app\modules\common\models\Lottery;
use app\modules\common\helpers\Winning;
use app\modules\common\services\AdditionalService;
use app\modules\common\helpers\TrendFall;
use app\modules\common\helpers\Jpush;

/**
 * 说明: 数字彩定时脚本
 * @author  kevi
 * @date 2017年6月15日 下午2:34:13
 */
class SzcController extends Controller
{
    public $defaultAction='index';
    private $userService = null;
    
    public function __construct($id,$module,$config=[])
    {
        parent::__construct($id,$module,$config);
    }
    
    /**
     * 说明: 测试访问是否正常
     * @author  kevi
     * @date 2017年10月25日 上午11:17:42
     * @param
     * @return 
     */
    public function actionIndex(){
        echo 'this is /cron/szc controller';
    }
    
    
    /**
     * 说明: 获取 体彩-数字彩-大乐透 开奖结果
     * @author  kevi
     * @date 2017年6月14日 上午10:51:13
     * @param
     * @return
     */
    public function actionGenerateRecordDlt2(){
        $request = \Yii::$app->request;
        $periods = $request->post('periods');
        $openNumber = $request->post('openNumber');
        $lotteryCode = Lottery::CODE_DLT;
        //确认数据格式是否正确
        $isCheck = $this->checkFormater($lotteryCode, $periods, $openNumber);
        $weekarray=array("周日","周一","周二","周三","周四","周五","周六");
        $nowPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'periods'=>$periods])->asArray()->one();
        if($nowPeriods['status']==LotteryRecord::STATUS_CURRENT){//如果是当前期数
            //更新开奖结果、生成下一期、生成趋势图、开始兑奖
            $nextPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'status'=>0])->one();
            if(empty($nextPeriods)){
                $this->jsonError(101, '此彩种无下期记录，请先检查数据,无操作');
            }
            //新下期数据
            $lotteryName = '大乐透';
            if($nextPeriods->week == '周三'){
                $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +3 day"));
            }else{
                $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +2 day"));
            }
            $limitTime = LotteryTime::find()->where(['lottery_code'=>$lotteryCode])->one()->limit_time;
            $newLimitTime = date("Y-m-d {$limitTime}",strtotime($newLotteryTime)) ;
            $nextYear = $this->isNextYear($nextPeriods->lottery_time, $newLotteryTime,'y');
            if($nextYear){
                $newPeriods = intval("{$nextYear}001");
            }else{
                $newPeriods = $nextPeriods->periods +1;
            }
            $newWeek = $weekarray[date('w',strtotime($newLotteryTime))];
            
            //更新开奖号码、生成下一期
            $this->updateRecord($lotteryCode, $lotteryName, $openNumber, $newPeriods, $newLotteryTime,$newLimitTime, $newWeek, $nowPeriods['lottery_record_id']);
            
            //生成趋势记录
            $trend = new Trend();
            $trend->getCreateTrend($lotteryCode, $periods, $openNumber);
            
            //调起兑奖任务
            $winHelper = new Winning();
            $winHelper->lottery2001Level($periods, $openNumber);
            
            //追号订单追期
            $traceService = new AdditionalService();
            $traceService->traceJob($lotteryCode, $nextPeriods->periods, $nextPeriods->lottery_time);
            
            //开奖推送
            $trendFall = new TrendFall();
            $trendFall->trendWebsocket($lotteryCode, $periods, $openNumber);
             //极光推送
            $reString=str_replace(","," ",$openNumber);
            $string = str_replace("|","+",$reString);
            $Jpush = new Jpush();
            $title = "开奖通知";
            $msg = "第".$periods."期大乐透开奖号码 ".$string;
            $Jpush->JpushSzcDrawNotice($title,$msg);
            
            $this->jsonResult(600, '开奖成功', $nowPeriods);
        }elseif($nowPeriods['status']==LotteryRecord::STATUS_LAST){
            $this->jsonError(101, '此期数已开奖，无操作');
        }else{
            $this->jsonError(101, '此期数还未开奖，无操作');
        }
    }
    
    
    /**
     * 说明: 获取 体彩-数字彩-大乐透 开奖结果
     * @author  kevi
     * @date 2017年6月14日 上午10:51:13
     * @param
     * @return
     */
    public function actionGenerateRecordPls2(){
        $request = \Yii::$app->request;
        $periods = $request->post('periods');
        $openNumber = $request->post('openNumber');
        $lotteryCode = Lottery::CODE_PL3;
        //确认数据格式是否正确
        $isCheck = $this->checkFormater($lotteryCode, $periods, $openNumber);
        $weekarray=array("周日","周一","周二","周三","周四","周五","周六");
        $nowPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'periods'=>$periods])->asArray()->one();
        if($nowPeriods['status']==LotteryRecord::STATUS_CURRENT){//如果是当前期数
            //更新开奖结果、生成下一期、生成趋势图、开始兑奖
            $nextPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'status'=>0])->one();
            if(empty($nextPeriods)){
                $this->jsonError(101, '此彩种无下期记录，请先检查数据,无操作');
            }
            //新下期数据
            $lotteryName = '排列三';
            $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +1 day"));
            $limitTime = LotteryTime::find()->where(['lottery_code'=>$lotteryCode])->one()->limit_time;
            $newLimitTime = date("Y-m-d {$limitTime}",strtotime($newLotteryTime)) ;
            $nextYear = $this->isNextYear($nextPeriods->lottery_time, $newLotteryTime,'y');
            if($nextYear){
                $newPeriods = intval("{$nextYear}001");
            }else{
                $newPeriods = $nextPeriods->periods +1;
            }
            $newWeek = $weekarray[date('w',strtotime($newLotteryTime))];
            //更新开奖号码、生成下一期
            $this->updateRecord($lotteryCode, $lotteryName, $openNumber, $newPeriods, $newLotteryTime,$newLimitTime, $newWeek, $nowPeriods['lottery_record_id']);
                        
            //生成趋势记录
            $trend = new Trend();
            $trend->getCreateTrend($lotteryCode, $periods, $openNumber);
            
            //调起兑奖任务
            $winHelper = new Winning();
            $winHelper->lottery2002Level($periods, $openNumber);
            
            //追号订单追期
            $traceService = new AdditionalService();
            $traceService->traceJob($lotteryCode, $nextPeriods->periods, $nextPeriods->lottery_time);
            
            //开奖推送
            $trendFall = new TrendFall();
            $trendFall->trendWebsocket($lotteryCode, $periods, $openNumber);
            //极光推送
//            $reString=str_replace(","," ",$openNumber);
//            $Jpush = new Jpush();
//            $title = "开奖通知";
//            $msg = "第".$periods."期排列三开奖号码 ".$reString;
//            $Jpush->JpushSzcDrawNotice($title,$msg);
            
            $this->jsonResult(600, '开奖成功', $nowPeriods);
        }elseif($nowPeriods['status']==LotteryRecord::STATUS_LAST){
            $this->jsonError(101, '此期数已开奖，无操作');
        }else{
            $this->jsonError(101, '此期数还未开奖，无操作');
        }
    }
    
    
    /**
     * 说明: 获取 体彩-数字彩-排列五 开奖结果
     * @author  kevi
     * @date 2017年6月14日 上午10:51:13
     * @param
     * @return
     */
    public function actionGenerateRecordPlw2(){
        $request = \Yii::$app->request;
        $periods = $request->post('periods');
        $openNumber = $request->post('openNumber');
        $lotteryCode = Lottery::CODE_PL5;
        //确认数据格式是否正确
        $isCheck = $this->checkFormater($lotteryCode, $periods, $openNumber);
        $weekarray=array("周日","周一","周二","周三","周四","周五","周六");
        $nowPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'periods'=>$periods])->asArray()->one();
        if($nowPeriods['status']==LotteryRecord::STATUS_CURRENT){//如果是当前期数
            //更新开奖结果、生成下一期、生成趋势图、开始兑奖
            $nextPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'status'=>0])->one();
            if(empty($nextPeriods)){
                $this->jsonError(101, '此彩种无下期记录，请先检查数据,无操作');
            }
            //新下期数据
            $lotteryName = '排列五';
            $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +1 day"));
            $limitTime = LotteryTime::find()->where(['lottery_code'=>$lotteryCode])->one()->limit_time;
            $newLimitTime = date("Y-m-d {$limitTime}",strtotime($newLotteryTime)) ;
            $nextYear = $this->isNextYear($nextPeriods->lottery_time, $newLotteryTime,'y');
            if($nextYear){
                $newPeriods = intval("{$nextYear}001");
            }else{
                $newPeriods = $nextPeriods->periods +1;
            }
            $newWeek = $weekarray[date('w',strtotime($newLotteryTime))];
            //更新开奖号码、生成下一期
            $this->updateRecord($lotteryCode, $lotteryName, $openNumber, $newPeriods, $newLotteryTime, $newLimitTime,$newWeek, $nowPeriods['lottery_record_id']);
            
            //生成趋势记录
            $trend = new Trend();
            $trend->getCreateTrend($lotteryCode, $periods, $openNumber);
            //调起兑奖任务
            $winHelper = new Winning();
            $winHelper->lottery2003Level($periods, $openNumber);
            //追号订单追期
            $traceService = new AdditionalService();
            $traceService->traceJob($lotteryCode, $nextPeriods->periods, $nextPeriods->lottery_time);
            
            //开奖推送
            $trendFall = new TrendFall();
            $trendFall->trendWebsocket($lotteryCode, $periods, $openNumber);
            //极光推送
            $reString=str_replace(","," ",$openNumber);
            $Jpush = new Jpush();
            $title = "开奖通知";
            $msg = "第".$periods."期排列五开奖号码 ".$reString;
            $Jpush->JpushSzcDrawNotice($title,$msg);
            
            $this->jsonResult(600, '开奖成功', $nowPeriods);
        }elseif($nowPeriods['status']==LotteryRecord::STATUS_LAST){
            $this->jsonError(101, '此期数已开奖，无操作');
        }else{
            $this->jsonError(101, '此期数还未开奖，无操作');
        }
    }
    
    
    /**
     * 说明: 获取 体彩-数字彩-七星彩 开奖结果
     * @author  kevi
     * @date 2017年6月14日 上午10:51:13
     * @param
     * @return
     */
    public function actionGenerateRecordQxc2(){
        $request = \Yii::$app->request;
        $periods = $request->post('periods');
        $openNumber = $request->post('openNumber');
        $lotteryCode = Lottery::CODE_QXC;
        //确认数据格式是否正确
        $isCheck = $this->checkFormater($lotteryCode, $periods, $openNumber);
        $weekarray=array("周日","周一","周二","周三","周四","周五","周六");
        $nowPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'periods'=>$periods])->asArray()->one();
        if($nowPeriods['status']==LotteryRecord::STATUS_CURRENT){//如果是当前期数
            //更新开奖结果、生成下一期、生成趋势图、开始兑奖
            $nextPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'status'=>0])->one();
            if(empty($nextPeriods)){
                $this->jsonError(101, '此彩种无下期记录，请先检查数据,无操作');
            }
            //新下期数据
            $lotteryName = '七星彩';
            if($nextPeriods->week == '周二'){
                $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +3 day"));
            }else{
                $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +2 day"));
            }
            $limitTime = LotteryTime::find()->where(['lottery_code'=>$lotteryCode])->one()->limit_time;
            $newLimitTime = date("Y-m-d {$limitTime}",strtotime($newLotteryTime)) ;
            $nextYear = $this->isNextYear($nextPeriods->lottery_time, $newLotteryTime,'y');
            if($nextYear){
                $newPeriods = intval("{$nextYear}001");
            }else{
                $newPeriods = $nextPeriods->periods +1;
            }
            $newWeek = $weekarray[date('w',strtotime($newLotteryTime))];
            //更新开奖号码、生成下一期
            $this->updateRecord($lotteryCode, $lotteryName, $openNumber, $newPeriods, $newLotteryTime,$newLimitTime, $newWeek, $nowPeriods['lottery_record_id']);
            
            //生成趋势记录
            $trend = new Trend();
            $trend->getCreateTrend($lotteryCode, $periods, $openNumber);
            //调起兑奖任务
            $winHelper = new Winning();
            $winHelper->lottery2004Level($periods, $openNumber);
            //追号订单追期
            $traceService = new AdditionalService();
            $traceService->traceJob($lotteryCode, $nextPeriods->periods, $nextPeriods->lottery_time);
            
            //开奖推送
            $trendFall = new TrendFall();
            $trendFall->trendWebsocket($lotteryCode, $periods, $openNumber);
            //极光推送
            $reString=str_replace(","," ",$openNumber);
            $Jpush = new Jpush();
            $title = "开奖通知";
            $msg = "第".$periods."期七星彩开奖号码 ".$reString;
            $Jpush->JpushSzcDrawNotice($title,$msg);
            
            $this->jsonResult(600, '开奖成功', $nowPeriods);
        }elseif($nowPeriods['status']==LotteryRecord::STATUS_LAST){
            $this->jsonError(101, '此期数已开奖，无操作');
        }else{
            $this->jsonError(101, '此期数还未开奖，无操作');
        }
    }
    
    /**
     * 说明: 获取 福彩-双色球 开奖结果
     * @author  kevi
     * @date 2017年6月14日 上午10:51:13
     * @param
     * @return
     */
    public function actionGenerateRecordSsq(){
        $request = \Yii::$app->request;
        $periods = $request->post('periods');
        $openNumber = $request->post('openNumber');
        $lotteryCode = Lottery::CODE_SSQ;
        //确认数据格式是否正确
        $isCheck = $this->checkFormater($lotteryCode, $periods, $openNumber);
        $weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        $nowPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'periods'=>$periods])->asArray()->one();
        if($nowPeriods['status']==LotteryRecord::STATUS_CURRENT){//如果是当前期数
            //更新开奖结果、生成下一期、生成趋势图、开始兑奖
            $nextPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'status'=>0])->one();
            if(empty($nextPeriods)){//无下期期数，可能引发错误，如有必要最好手动生成下期
                $this->jsonError(101, '此彩种无下期记录，请先检查数据,无操作');
            }
            //新下期数据
            $lotteryName = '双色球';
            if($nextPeriods->week == '星期四'){
                $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +3 day"));
            }else{
                $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +2 day"));
            }
            $limitTime = LotteryTime::find()->where(['lottery_code'=>$lotteryCode])->one()->limit_time;
            $newLimitTime = date("Y-m-d {$limitTime}",strtotime($newLotteryTime)) ;
            $nextYear = $this->isNextYear($nextPeriods->lottery_time, $newLotteryTime,'y');
            if($nextYear){
                $newPeriods = intval("{$nextYear}001");
            }else{
                $newPeriods = $nextPeriods->periods +1;
            }
            $newWeek = $weekarray[date('w',strtotime($newLotteryTime))];
            //更新开奖号码、生成下一期
            $this->updateRecord($lotteryCode, $lotteryName, $openNumber, $newPeriods, $newLotteryTime,$newLimitTime, $newWeek, $nowPeriods['lottery_record_id']);
            
            //生成趋势记录
            $trend = new Trend();
            $trend->getCreateTrend($lotteryCode, $periods, $openNumber);
            //调起兑奖任务
            $winHelper = new Winning();
            $winHelper->lottery1001Level($periods, $openNumber);
            //追号订单追期
            $traceService = new AdditionalService();
            $traceService->traceJob($lotteryCode, $nextPeriods->periods, $nextPeriods->lottery_time);
            
            //开奖推送
            $trendFall = new TrendFall();
            $trendFall->trendWebsocket($lotteryCode, $periods, $openNumber);
             //极光推送
            $reString=str_replace(","," ",$openNumber);
            $string = str_replace("|","+",$reString);
            $Jpush = new Jpush();
            $title = "开奖通知";
            $msg = "第".$periods."期双色球开奖号码 ".$string;
            $Jpush->JpushSzcDrawNotice($title,$msg);
            
            $this->jsonResult(600, '开奖成功', $nowPeriods);
        }elseif($nowPeriods['status']==LotteryRecord::STATUS_LAST){
            $this->jsonError(101, '此期数已开奖，无操作');
        }else{
            $this->jsonError(101, '此期数还未开奖，无操作');
        }
    }
    
    /**
     * 说明: 获取 福彩-3D 开奖结果
     * @author  kevi
     * @date 2017年6月14日 上午10:51:13
     * @param
     * @return
     */
    public function actionGenerateRecord3d(){
        $request = \Yii::$app->request;
        $periods = $request->post('periods');
        $openNumber = $request->post('openNumber');
        $testNumber = $request->post('testNumber');
        $lotteryCode = Lottery::CODE_FC_3D;
        //确认数据格式是否正确
        $isCheck = $this->checkFormater($lotteryCode, $periods, $openNumber);
        $weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        $nowPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'periods'=>$periods])->asArray()->one();
        if($nowPeriods['status']==LotteryRecord::STATUS_CURRENT){//如果是当前期数？更新开奖结果、生成下一期、生成趋势图、开始兑奖
            $nextPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'status'=>0])->one();
            if(empty($nextPeriods)){//无下期期数，可能引发错误，如有必要最好手动生成下期后重试
                $this->jsonError(101, '此彩种无下期记录，请先检查数据,无操作');
            }
            //新下期数据
            $lotteryName = '福彩3D';
            $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +1 day"));
            $limitTime = LotteryTime::find()->where(['lottery_code'=>$lotteryCode])->one()->limit_time;
            $newLimitTime = date("Y-m-d {$limitTime}",strtotime($newLotteryTime)) ;
            $nextYear = $this->isNextYear($nextPeriods->lottery_time, $newLotteryTime,'Y');
            if($nextYear){
                $newPeriods = intval("{$nextYear}001");
            }else{
                $newPeriods = $nextPeriods->periods +1;
            }
            $newWeek = $weekarray[date('w',strtotime($newLotteryTime))];
            //更新开奖号码、生成下一期
            $this->updateRecord($lotteryCode, $lotteryName, $openNumber, $newPeriods, $newLotteryTime,$newLimitTime, $newWeek, $nowPeriods['lottery_record_id'],$testNumber);
            
            //生成趋势记录
            $trend = new Trend();
            $trend->getCreateTrend($lotteryCode, $periods, $openNumber, 0, $testNumber);
            //调起兑奖任务
            $winHelper = new Winning();
            $winHelper->lottery1002Level($periods, $openNumber);
            //追号订单追期
            $traceService = new AdditionalService();
            $traceService->traceJob($lotteryCode, $nextPeriods->periods, $nextPeriods->lottery_time);
            
            //开奖推送
            $trendFall = new TrendFall();
            $trendFall->trendWebsocket($lotteryCode, $periods, $openNumber);
            
             //极光推送
//            $reString=str_replace(","," ",$openNumber);
//            $Jpush = new Jpush();
//            $title = "开奖通知";
//            $msg = "第".$periods."期福彩3D开奖号码 ".$reString;
//            $Jpush->JpushSzcDrawNotice($title,$msg);
            
            $this->jsonResult(600, '开奖成功', $nowPeriods);
        }elseif($nowPeriods['status']==LotteryRecord::STATUS_LAST){
            $this->jsonError(101, '此期数已开奖，无操作');
        }else{
            $this->jsonError(101, '此期数还未开奖，无操作');
        }
    }
    
    /**
     * 说明: 获取 福彩-七乐彩 开奖结果
     * @author  kevi
     * @date 2017年6月14日 上午10:51:13
     * @param
     * @return
     */
    public function actionGenerateRecordQlc(){
        $request = \Yii::$app->request;
        $periods = $request->post('periods');
        $openNumber = $request->post('openNumber');
        $lotteryCode = Lottery::CODE_QLC;
        //确认数据格式是否正确
        $isCheck = $this->checkFormater($lotteryCode, $periods, $openNumber);
        $weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        $nowPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'periods'=>$periods])->asArray()->one();
        if($nowPeriods['status']==LotteryRecord::STATUS_CURRENT){//如果是当前期数？更新开奖结果、生成下一期、生成趋势图、开始兑奖
            $nextPeriods = LotteryRecord::find()->where(['lottery_code'=>$lotteryCode,'status'=>0])->one();
            if(empty($nextPeriods)){//无下期期数，可能引发错误，如有必要最好手动生成下期
                $this->jsonError(101, '此彩种无下期记录，请先检查数据,无操作');
            }
            //新下期数据
            $lotteryName = '七乐彩';
            if($nextPeriods->week == '星期五'){
                $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +3 day"));
            }else{
                $newLotteryTime = date('Y-m-d H:i:s',strtotime("{$nextPeriods->lottery_time} +2 day"));
            }
            $limitTime = LotteryTime::find()->where(['lottery_code'=>$lotteryCode])->one()->limit_time;
            $newLimitTime = date("Y-m-d {$limitTime}",strtotime($newLotteryTime)) ;
            $nextYear = $this->isNextYear($nextPeriods->lottery_time, $newLotteryTime,'y');
            if($nextYear){
                $newPeriods = intval("{$nextYear}001");
            }else{
                $newPeriods = $nextPeriods->periods +1;
            }
            $newWeek = $weekarray[date('w',strtotime($newLotteryTime))];
            //更新开奖号码、生成下一期
            $this->updateRecord($lotteryCode, $lotteryName, $openNumber, $newPeriods, $newLotteryTime,$newLimitTime, $newWeek, $nowPeriods['lottery_record_id']);
            
            //生成趋势记录
            $trend = new Trend();
            $trend->getCreateTrend($lotteryCode, $periods, $openNumber);
            //调起兑奖任务
            $winHelper = new Winning();
            $winHelper->lottery1003Level($periods, $openNumber);
            //追号订单追期
            $traceService = new AdditionalService();
            $traceService->traceJob($lotteryCode, $nextPeriods->periods, $nextPeriods->lottery_time);
            
            //开奖推送
            $trendFall = new TrendFall();
            $trendFall->trendWebsocket($lotteryCode, $periods, $openNumber);
            //极光推送
//            $reString=str_replace(","," ",$openNumber);
//            $string = str_replace("|","+",$reString);
//            $Jpush = new Jpush();
//            $title = "开奖通知";
//            $msg = "第".$periods."期七乐彩开奖号码 ".$string;
//            $Jpush->JpushSzcDrawNotice($title,$msg);
            
            $this->jsonResult(600, '开奖成功', $nowPeriods);
        }elseif($nowPeriods['status']==LotteryRecord::STATUS_LAST){
            $this->jsonError(101, '此期数已开奖，无操作');
        }else{
            $this->jsonError(101, '此期数还未开奖，无操作');
        }
    }
    
    /**
     * 说明: 访问 体彩中心接口（停用）
     * @author  kevi
     * @date 2017年6月16日 下午2:55:14
     * @param $ltype 类型 (4-大乐透,5-排列三,6-排列五,8-七星彩)
     * @param $periods 期数
     * @return 
     */
    public function getTcNumber($ltype,$periods){
        $surl = "http://www.lottery.gov.cn/api/lottery_kj_detail_new.jspx?_ltype={$ltype}&_term={$periods}";
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
        $res = json_decode($output,true);//json转数组
        $lotteryObject = $res[0];//彩票对象
        return $lotteryObject;
    }
    
    
    /**
     * 说明: 确认开奖结果是否格式正确
     * @author  kevi
     * @date 2017年10月12日 下午2:18:37
     * @param   $lotteryCode    
     * @param   $periods    
     * @param   $openNumber    
     * @return  boolean
     */
    public function checkFormater($lotteryCode,$periods,$openNumber){
        if(empty($lotteryCode)||empty($periods)||empty($openNumber)){
            $this->jsonError(101, '参数错误，不允许为空');
        }
        $ret = false;
        switch ($lotteryCode){
            case 1001:
                if(strlen($openNumber)==20){
                    $ret = true;
                }
                break;
            case 1002:
                if(strlen($openNumber)==5){
                    $ret = true;
                }
                break;
            case 1003:
                if(strlen($openNumber)==23){
                    $ret = true;
                }
                break;
            case 2001:
                if(strlen($openNumber)==20){
                    $ret = true;
                }
                break;
            case 2002:
                if(strlen($openNumber)==5){
                    $ret = true;
                }
                break;
            case 2003:
                if(strlen($openNumber)==9){
                    $ret = true;
                }
                break;
            case 2004:
                if(strlen($openNumber)==13){
                    $ret = true;
                }
                break;
            default:
                $ret = false;
        }
        if(!$ret){
            $this->jsonError(101, '该数据不合法，请检查数据格式');
        }
        return $ret;
    }
    
    /**
     * 说明: 判断两时间是否跨年
     * @author  kevi
     * @date 2017年9月6日 下午3:14:36
     * @param not null $nowTime
     * @param not null $nextTime
     * @param not null $type  'Y' or 'y' 时间格式
     * @return  false或者下期年份
     */
    public function isNextYear($nowTime,$nextTime,$type){
       $ret = false;
       $nowYear = date($type,strtotime($nowTime));
       $nextYear = date($type,strtotime($nextTime));
       if($nowYear != $nextYear){
           $ret = $nextYear;
       }
        return $ret;
    }
    
    /**
     * 说明: 更新开奖结果、生成下一期、
     * @author  kevi
     * @date 2017年10月12日 下午1:43:42
     * @param $lotteryCode  彩种编号
     * @param $lotteryName  彩种名称
     * @param $openNumber   开奖号码
     * @param $newPeriods   下一期期数
     * @param $newLotteryTime   下一期开奖时间
     * @param $newWeek  下一期周几 
     * @param $nowLotteryRecordId
     * @return 
     */
    public function updateRecord($lotteryCode,$lotteryName,$openNumber,$newPeriods,$newLotteryTime,$newLimitTime,$newWeek,$nowLotteryRecordId,$testNumber = ''){
        $createTime = date('Y-m-d H:i:s');
        $sql = "
        UPDATE lottery_record SET lottery_numbers = '{$openNumber}', test_numbers = '{$testNumber}', status = 2 WHERE lottery_record_id = {$nowLotteryRecordId};
        UPDATE lottery_record SET status = 1  WHERE lottery_code = {$lotteryCode} and status = 0;
        INSERT INTO lottery_record(lottery_code,lottery_name,periods,lottery_time,limit_time,week,status,create_time) VALUES({$lotteryCode},'{$lotteryName}','{$newPeriods}','{$newLotteryTime}','{$newLimitTime}','{$newWeek}',0,'{$createTime}');
        ";
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        return $ret;
    }
    
    /**
     * 更新奖池
     * @return type
     */
    public function actionUpdatePool() {
        $request = \Yii::$app->request;
        $lotteryCode = $request->post('lottery_code');
        $pool = $request->post('pool');
        $lotteryRecord = LotteryRecord::findOne(['lottery_code' => $lotteryCode, 'status' => 1]);
        if(empty($lotteryRecord)) {
            return $this->jsonError(109, '该彩种暂无当前期');
        }
        $lotteryRecord->pool = $pool;
        $lotteryRecord->modify_time = date('Y-m-d H:i:s');
        if(!$lotteryRecord->save()){
            return $this->jsonError(109, '数据写入失败');
        }
        return $this->jsonResult(600, '数据写入成功', true);
    }
}
