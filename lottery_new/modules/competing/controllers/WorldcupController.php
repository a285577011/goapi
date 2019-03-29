<?php

namespace app\modules\competing\controllers;

use Yii;
use yii\web\Controller;
use app\modules\competing\services\WorldcupService;

class WorldcupController extends Controller {
    
     /**
     * 获取世界杯可投注赛程
     * @return type
     */
    public function actionGetWcTeam() {
        $request = Yii::$app->request;
        $lotteryCode = $request->post('lottery_code', '');
        if (empty($lotteryCode)) {
            return $this->jsonError(100, '请先选择玩法');
        }
        if($lotteryCode == '301201') {
            $data = WorldcupService::getWcChp();
        }elseif ($lotteryCode == '301301') {
            $data = WorldcupService::getWcFnl();
        }  else {
            return $this->jsonError(109, '此彩种暂未开放');
        }
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取筛选球队
     * @return type
     */
    public function actionGetTeamList() {
        $request = Yii::$app->request;
        $lotteryCode = $request->post('lottery_code', '');
        if (empty($lotteryCode)) {
            return $this->jsonError(100, '请先选择玩法');
        }
        $data = WorldcupService::getTeam($lotteryCode);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    /**
     * 获取世界杯基础赛程
     */
    public function actionGetWcupInfo() {
        $infoList = WorldcupService::getScheduleInfo();
        return $this->jsonResult(600, '获取成功', $infoList);
    }
    
}

