<?php

namespace app\modules\openapi\controllers;

use Yii;
use yii\web\Controller;
use app\modules\openapi\services\ArticlesService;

class ExpertArticlesController extends Controller {
     
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
}

