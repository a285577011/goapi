<?php

namespace app\modules\openapi\controllers;

use yii\web\Controller;
use app\modules\openapi\services\LanScheduleService;

class BasketballController extends Controller {
    
    /**
     * 篮球即时比赛列表
     * @return type
     */
    public function actionGetLiveSchedule() {
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getSchedule($status = 0);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 篮球赛程
     * @return type
     */
    public function actionGetDateSchedule() {
        $request = \Yii::$app->request;
        $date = $request->post('schedule_date', '');
        if(empty($date)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getSchedule('', $date);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 篮球即时比分数据
     * @return type
     */
    public function actionGetLiveData() {
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getSchedule($status = 1);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 比赛基础信息
     * @return type
     */
    public function actionGetSchedule() {
        $request = \Yii::$app->request;
        $mid = $request->post('schedule_id', '');
        if(empty($mid)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getScheduleDetail($mid);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 篮球比赛往绩分析
     * @return type
     */
    public function actionGetScheduleAnalyze() {
        $request = \Yii::$app->request;
        $mid = $request->post('schedule_id', '');
        if(empty($mid)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getScheduleDetail($mid);
        $analyze = $lanScheduleService->getScheduleAnalyze($mid);
        $against = $lanScheduleService->getTeamSchedule($mid);
        $result['league'] = $data['league'];
        $result['team'] = $data['team'];
        $result['teamAnalyze'] = $analyze;
        $result['teamHistory']['home'] = $against['homeHistory'];
        $result['teamHistory']['visit'] = $against['visitHistory'];
        $result['teamFuture']['home'] = $against['homeFuture'];
        $result['teamFuture']['visit'] = $against['visitFuture'];
        return $this->jsonResult(600, '获取成功', $result);
    }
    
    /**
     * 篮球比赛预测
     * @return type
     */
    public function actionGetScheduleForecast() {
        $request = \Yii::$app->request;
        $mid = $request->post('schedule_id', '');
        if(empty($mid)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getScheduleForecast($mid);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 篮球赛程技术统计
     * @return type
     */
    public function actionGetScheduleCount() {
        $request = \Yii::$app->request;
        $mid = $request->post('schedule_id', '');
        if(empty($mid)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getScheduleCount($mid);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取可售赛程列表
     * @return type
     */
    public function actionSaleScheduleList() {
        $request = \Yii::$app->request;
        $date = $request->post('schedule_date', '');
        if(empty($date)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getSaleSchedule($date);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取带赔率的可售赛程列表
     * @return type
     */
    public function actionSaleScheduleListSp() {
        $request = \Yii::$app->request;
        $date = $request->post('schedule_date', '');
        if(empty($date)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getSaleSchedule($date, true);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取篮球比赛赔率值变化
     * @return type
     */
    public function actionGetScheduleSp() {
        $request = \Yii::$app->request;
        $openMid = $request->post('schedule_id', '');
        if(empty($openMid)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getScheduleSp($openMid);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取篮球球队基础信息
     * @return type
     */
    public function actionGetTeam() {
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getTeam();
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取篮球联赛基础信息
     * @return type
     */
    public function actionGetLeague() {
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getLeague();
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取球队相关赛程信息
     * @return type
     */
    public function actionGetTeamSchedule() {
        $request = \Yii::$app->request;
        $teamId = $request->post('team_id', '');
        if(empty($teamId)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getTeamCount($teamId);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取球队积分榜
     * @return type
     */
    public function actionGetLeagueTeamRank() {
        $request = \Yii::$app->request;
        $leagueId = $request->post('league_id', '');
        if(empty($leagueId)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getLeagueTeamRank($leagueId);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取赛程欧赔
     * @return type
     */
    public function actionGetEuropeOdds() {
        $request = \Yii::$app->request;
        $openMid = $request->post('schedule_id', '');
        if(empty($openMid)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getEuropeOdds($openMid);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取赛程亚赔
     * @return type
     */
    public function actionGetAsiaOdds(){
        $request = \Yii::$app->request;
        $openMid = $request->post('schedule_id', '');
        if(empty($openMid)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getAsiaOdds($openMid);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取赛程总分（大小分）赔率
     * @return type
     */
    public function actionGetTotalScoreOdds() {
        $request = \Yii::$app->request;
        $openMid = $request->post('schedule_id', '');
        if(empty($openMid)) {
            return $this->jsonError(109, '查询参数缺失');
        }
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getTotalScoreOdds($openMid);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取亚赔列表
     * @return type
     */
    public function actionGetAsiaOddsList() {
        $request = \Yii::$app->request;
        $type = $request->post('type', 2);
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getAsiaList($type);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取欧赔列表
     * @return type
     */
    public function actionGetEuropeOddsList() {
        $request = \Yii::$app->request;
        $type = $request->post('type', 2);
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getEuropeList($type);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取总分（大小分）赔率列表
     * @return type
     */
    public function actionGetTotalScoreOddsList() {
        $request = \Yii::$app->request;
        $type = $request->post('type', 2);
        $lanScheduleService = new LanScheduleService();
        $data = $lanScheduleService->getTotalScoreList($type);
        return $this->jsonResult(600, '获取成功', $data);
    }
}

