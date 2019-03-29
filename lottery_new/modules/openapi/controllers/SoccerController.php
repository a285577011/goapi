<?php

namespace app\modules\openapi\controllers;

use Yii;
use yii\web\Controller;
use app\modules\openapi\services\SoccerScheduleService;

class SoccerController extends Controller {

    /**
     * 获取赛事列表
     * @return type
     */
    public function actionGetSoccerLeague() {
        $request = \Yii::$app->request;
        $leagueId = $request->post('league_id', '');
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getLeague($leagueId);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取球队列表
     * @return type
     */
    public function actionGetSoccerTeam() {
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getTeam();
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 根据日期获取赛程列表
     * @return type
     */
    public function actionGetSoccerSchedule() {
        $request = \Yii::$app->request;
        $scheduleDate = $request->post('schedule_date', '');
        if(empty($scheduleDate)) {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $scheduleDate = str_replace('-', '', $scheduleDate);
        $data = $scheduleService->getScheduleByDate($scheduleDate);
        if(empty($data)) {
            return $this->jsonResult(109, '该天无赛程', true);
        }
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取足球球队列表及对应体彩名称
     */
    public function actionGetTeamInfo() {
        $request = \Yii::$app->request;
        $teamId = $request->post('team_id', '');
        if($teamId=='') {
            return $this->jsonError(109, '参数缺失');
        }
        $soccerService = new SoccerScheduleService();
        $data = $soccerService->getSoccerTeamInfo($teamId);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 即时比赛比分列表
     */
    public function actionGetSoccerScoreList(){
        $scheduleService = new SoccerScheduleService();
        $res = $scheduleService -> getScheduleList(2);
        return $this->jsonResult(600, '即时比赛比分列表', $res);
    }
     /**
     * 根据赛事ID获取足球亚盘指数
     */
    public function actionGetAsianInfo() {
        $request = \Yii::$app->request;
        $openMid = $request->post('open_mid', '');
        if($openMid=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getAsianHandicap($openMid);
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
     * 根据赛事mid获取实况
     */
    public function actionGetScheduleLive(){
        $request = \Yii::$app->request;
        $openMid = $request->post('open_mid', '');
        if($openMid=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getScheduleLive($openMid);
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
        $openMid = $request->post('open_mid', '');
        if($openMid=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getEuropeHandicap($openMid);
        return $this->jsonResult(600, '获取成功', $data);
    }
    /**
     * 获取欧赔指数变化历史
     */
    public function actionGetCompanyEuropeChange() {
        $request = \Yii::$app->request;
        $openMid = $request->post('open_mid', '');
        $companyName = $request->post('company_name', '');
        if($openMid==""||$companyName=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getEuropeHandicapChange($openMid,$companyName);
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

    /**
     * 实时比分数据信息
     */
    public function actionGetSoccerScoreRealtime(){
        $scheduleService = new SoccerScheduleService();
        $res = $scheduleService -> getScheduleList(1);
        return $this->jsonResult(600, '实时比分数据信息', $res);
    }

    /**
     * 单个赛程比分详情
     */
        public function actionGetSoccerScoreOne(){
        $request = \Yii::$app->request;
        $open_mid = $request->post('open_mid', '');
        if(empty($open_mid)) {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $res = $scheduleService -> getScheduledsp($open_mid);
        return $this->jsonResult(600, '赛程比分', $res['data']);
    }

    /**
     * 近期取消、延期、腰斩的赛事
     */
    public function actionGetSoccerAccident() {
        $scheduleService = new SoccerScheduleService();
        $sWhere =  ['in', 'sr.status', [3, 4]];
        $res = $scheduleService -> getScheduleList(2, '', $sWhere);
        return $this->jsonResult(600, '取消和延期赛程', $res);
    }

    /**
     * 单个比赛基本信息
     */
    public function actionSoccerBasicInfo(){
        $request = \Yii::$app->request;
        $open_mid = $request->post('open_mid', '');
        if(empty($open_mid)) {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $res = $scheduleService -> getScheduleBasicInfo($open_mid);
        return $this->jsonResult(600, '比赛基本信息', $res);
    }

    /**
     * 比赛预测:比分，实力对比
     */
    public function actionGetPreResult(){
        $request = \Yii::$app->request;
        $open_mid = $request->post('open_mid', '');
        if(empty($open_mid)) {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService -> getPreResult($open_mid);
        return $this->jsonResult(600, "获取成功", $data);
    }


    /**
     * 比赛往绩数据
     * @param $teamId 团队id schedule表 home_team_mid or visit_team_mid
     */
    public function actionGetScheduleHistory(){
        $request = \Yii::$app->request;
        $teamId = $request->post('team_id', '');
        if(empty($teamId)) {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService -> getScheduleHistory($teamId);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取竞彩赛程关联比赛
     * @param date 日期
     */
    public function actionGetScheduleAgenda(){
        $request = \Yii::$app->request;
        $date = $request->post('date', '');
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService -> getScheduleAgenda($date, 1);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取竞彩赛程关联比赛带sp
     * @param date 日期
     */
    public function actionGetScheduleAgendasp(){
        $request = \Yii::$app->request;
        $date = $request->post('date', '');
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService -> getScheduleAgenda($date, 2);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取胜负彩赛程
     * @param $periods 期数
     * @return array
     */
    public function actionGetFourteenByPeriods(){
        $request = \Yii::$app->request;
        $periods = $request->post('periods', '');
        if(empty($periods)) {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getFourteenByPeriods($periods);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 足球情报列表
     */
    public function actionGetFbIntelligence(){
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getFbIntelligence();
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 足球情报单个
     */
    public function actionGetFbIntelligenceOne(){
        $request = \Yii::$app->request;
        $open_mid = $request->post('open_mid', '');
        if(empty($open_mid)) {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getFbIntelligenceOne($open_mid);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 足球资讯列表
     */
    public function actionGetFbmsgList(){
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getFbmsgList();
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 足球资讯单个
     */
    public function actionGetFbmsgOne(){
        $request = \Yii::$app->request;
        $open_mid = $request->post('open_mid', '');
        if(empty($open_mid)) {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getFbmsgOne($open_mid);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取球队近两年比赛、未来赛程、数据统计
     */
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
     * 根据公司名称获取亚盘列表
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
     * 获取单场比赛详细信息
     */
    public function actionGetOneSoccerSchedule() {
        $request = \Yii::$app->request;
        $openMid = $request->post('open_mid', '');
        if($openMid=="") {
            return $this->jsonError(109, '参数缺失');
        }
        $scheduleService = new SoccerScheduleService();
        $data = $scheduleService->getScheduleInfo($openMid);
        return $this->jsonResult(600, '获取成功', $data);
    }
}
