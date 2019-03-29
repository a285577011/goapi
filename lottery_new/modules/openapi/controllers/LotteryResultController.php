<?php

namespace app\modules\openapi\controllers;

use yii\web\Controller;
use app\modules\openapi\services\LotteryResultService;

class LotteryResultController extends Controller {
    
    /**
     * 数字彩开奖结果查询
     * @return type
     */
    public function actionGetSzcResult() {
        $request = \Yii::$app->request;
        $lotteryCode = $request->post('lotteryCode', '');
        $periods = $request->post('period', '');
        $startDate = $request->post('startDate', '');
        $endDate = $request->post('endDate', '');
        if(empty($lotteryCode)) {
            return $this->jsonError(100, '查询彩种缺失！！');
        }
        if(empty($periods) && (empty($startDate) && empty($endDate))) {
            return $this->jsonError(100, '查询期数和查询日期区间不可同时为空！！');
        }
        $szcService = new LotteryResultService();
        $data = $szcService->getLotteryResult($lotteryCode, $periods, $startDate, $endDate);
        return $this->jsonResult(600, '获取成功', $data);
    }
    
    
    /**
     * 胜负彩开奖结果查询
     * @return type
     */
    public function actionGetOptionalResult() {
        $request = \Yii::$app->request;
        $periods = $request->post('period', '');
        $startDate = $request->post('startDate', '');
        $endDate = $request->post('endDate', '');
        if(empty($periods) && (empty($startDate) && empty($endDate))) {
            return $this->jsonError(100, '查询期数和查询日期区间不可同时为空！！');
        }
        $optionalService = new LotteryResultService();
        $data = $optionalService->getOptionalResult($periods, $startDate, $endDate);
        return $this->jsonResult(600, '获取成功', $data);
    }
}

