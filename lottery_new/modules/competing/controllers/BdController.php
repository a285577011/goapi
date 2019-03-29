<?php

namespace app\modules\competing\controllers;

use Yii;
use yii\web\Controller;
use app\modules\competing\services\BdService;
use app\modules\common\services\ResultService;

class BdController extends Controller {
    
     /**
     * 获取北单可投注赛程
     * @return type
     */
    public function actionGetBdSchedule() {
        $request = Yii::$app->request;
        $lotteryCode = $request->post('lottery_code', '');
        if (empty($lotteryCode)) {
            return $this->jsonError(100, '请先选择玩法');
        }
        $bdService = new BdService();
        $data = $bdService->getBetSchedule($lotteryCode);
        if ($data['code'] != 600) {
            return $this->jsonError(109, $data['msg']);
        }
        return $this->jsonResult(600, $data['msg'], $data['data']);
    }
    
    /**
     * 获取北单赛程
     * @return type
     */
    public function actionGetBdLeague() {
        $bdService = new BdService();
        $request = Yii::$app->request;
        $lotteryCode = $request->post('lottery_code', '');
        $data['data'] = $bdService->getBdLeague($lotteryCode);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取北单赛果
     * @return type
     */
    public function actionGetBdResult() {
        $request = Yii::$app->request;
        $lotteryCode = $request->post('lottery_code', '');
        $periods = $request->post('periods', '');
        $page = $request->post('page', 0);
        $size = $request->post('size', 10);
        $data = ResultService::getBdResult($lotteryCode, $periods, $page, $size);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 历史交锋统计
     * @auther GL ctx
     * @return json
     */
    public function actionGetHistoryCount() {
        $request = \Yii::$app->request;
        $scheduleMid = $request->post("open_mid");
        $scheduleService = new BdService();
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
        $post = \Yii::$app->request;
        $scheduleMid = $post->post("open_mid");
        $scheduleService = new BdService();
        $data = $scheduleService->getScheduleInfo($scheduleMid);
        if ($data['data'] == null) {
            return $this->jsonResult(600, "获取成功", $data['data']);
        }
        return $this->jsonResult(600, "获取成功", ["scheduel_info" => $data['data']['info'], 'scheduel_result' => $data['data']['result']]);
    }

    /**
     * 双方历史交战比赛
     * @auther GL ctx
     * @return json
     */
    public function actionDoubleHistoryMatch() {
        $post = \Yii::$app->request->post();
        $mid = $post["open_mid"];
        $teamType = isset($post["team_type"]) ? $post["team_type"] : "";
        $size = isset($post["size"]) ? $post["size"] : 10;
//        $sameLeague = isset($post["same_league"]) ? $post["same_league"] : "";
        $scheduleService = new BdService();
        $data = $scheduleService->getDoubleHistoryMatch($mid, $teamType, $size);
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 历史交战比赛
     * @auther GL ctx
     * @return json
     */
    public function actionHistoryMatch() {
        $post = \Yii::$app->request->post();
        $mid = $post["open_mid"];
        $teamMid = $post["team_mid"];
        $teamType = isset($post["team_type"]) ? $post["team_type"] : "";
//        $sameLeague = isset($post["same_league"]) ? $post["same_league"] : "";
        $size = isset($post["size"]) ? $post["size"] : 10;
        $scheduleService = new BdService();
        $data = $scheduleService->getHistoryMatch($mid, $teamMid, $size, $teamType);
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 实力对比
     * @auther GL ctx
     * @return json
     */
    public function actionStrengthContrast() {
        $post = \Yii::$app->request->post();
        $mid = $post["open_mid"];
        $scheduleService = new BdService();
        $data = $scheduleService->getStrengthContrast($mid);
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 球队未来赛程
     * @return json
     */
    public function actionGetFutureSchedule() {
        $post = \Yii::$app->request->post();
        $mid = $post["open_mid"];
        $scheduleService = new BdService();
        $data = $scheduleService->getFutureSchedule($mid);
        return $this->jsonResult(600, "获取成功", $data['data']);
    }

    /**
     * 获取预测赛果
     * @return json
     */
    public function actionGetPreResult() {
        $post = \Yii::$app->request->post();
        $mid = $post["open_mid"];
        $scheduleService = new BdService();
        $data = $scheduleService->getPreResult($mid);
        return $this->jsonResult(600, "获取成功", $data);
    }

    /**
     * 获取亚盘
     * @return json
     */
    public function actionGetAsianHandicap() {
        $post = \Yii::$app->request->post();
        $mid = $post["open_mid"];
//        return $this->jsonResult(600, "获取成功", true);
        $scheduleService = new BdService();
        $data = $scheduleService->getAsianHandicap($mid);
        return $this->jsonResult(600, "获取成功", $data,true);
    }

    /**
     * 获取欧赔
     * @return json
     */
    public function actionGetEuropeOdds() {
        $post = \Yii::$app->request->post();
        $mid = $post["open_mid"];
//        return $this->jsonResult(600, "获取成功", true,true);
        $scheduleService = new BdService();
        $data = $scheduleService->getEuropeOdds($mid);
        return $this->jsonResult(600, "获取成功", $data,true);
    }

    /**
     * 获取实况信息
     * @return json
     */
    public function actionGetScheduleLives() {
        $post = \Yii::$app->request->post();
        $mid = $post["open_mid"];
        $scheduleService = new BdService();
        $data = $scheduleService->getScheduleLives($mid);
        return $this->jsonResult(600, "实况获取成功", $data);
    }
}

