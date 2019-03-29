<?php

namespace app\modules\competing\controllers;

use Yii;
use yii\web\Controller;
use app\modules\competing\services\FootballService;
use app\modules\common\services\ScheduleService;

class CompetingController extends Controller {

    /**
     * PC端获取足球可投注赛程
     * @return type
     */
    public function actionGetZuSchedule() {
        $request = Yii::$app->request;
        $lotteryCode = $request->post('lottery_code', '');
        $playType = $request->post('schedule_dg', 1); // 过关方式 1：过关 2：单关
        if ($lotteryCode == '') {
            return $this->jsonResult(109, '请选择玩法', '');
        }
        $footballService = new FootballService();
        $scheduleList = $footballService->getBetSchedule($lotteryCode, $playType, 2);
        return $this->jsonResult(600, '可投注赛程', $scheduleList);
    }
    
    /**
     * PC端获取足球热门赛事
     * @return type
     */
    public function actionGetZuHot(){
        $footballService = new FootballService();
        $scheduleList = $footballService->getHotSchedule();
        return $this->jsonResult(600, '热门赛程', $scheduleList);
    }
    
    /**
     * PC端获取足球大小球赔率
     * @return type
     */
    public function actionGetZuDaxiaoOdds() {
        $request = \Yii::$app->request;
        $mid = $request->post('schedule_mid', '');
        $scheduleService = new ScheduleService();
        $data = $scheduleService->getZuDaxiaoOdds($mid);
        return $this->jsonResult(600, "获取成功", $data, true);
    }
}
