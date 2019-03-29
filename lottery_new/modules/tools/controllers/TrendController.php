<?php

namespace app\modules\tools\controllers;

use Yii;
use yii\web\Controller;
use app\modules\common\helpers\Trend;
use app\modules\common\models\LotteryRecord;

class TrendController extends Controller {
    
    public function actionManualTrendChart(){
        $request = Yii::$app->request;
        $code = $request->post('code','');
        $periods = $request->post('periods','');
        $openNums = $request->post('open_nums','');
        if($code == '' || $periods == '' || $openNums == ''){
            return $this->jsonError(100, '参数错误');
        }
        $trend = new Trend();
        $error = $trend->getCreateTrend($code, $periods, $openNums, 1);
        if($error == false){
            return $this->jsonError(109, '添加失败');
        }
        return $this->jsonError(600, '添加成功');
    }

    

    public function actionManualRefreshTrend() {
        $request = Yii::$app->request;
        $code = $request->post('code','');
        $periods = $request->post('periods','');
        if($code == '' || $periods == ''){
            return $this->jsonError(100, '参数错误');
        }
        $trend = new Trend();
        $errorPeriods = LotteryRecord::find()->select(['lottery_code', 'periods', 'lottery_numbers', 'test_numbers'])->where(['status'=>2, 'lottery_code' => $code])->andWhere(['>=', 'periods', $periods])->orderBy('periods')->asArray()->all();
        $k = 0;
        foreach ($errorPeriods as $val){
            $error = $trend->getCreateTrend($code, $val['periods'], $val['lottery_numbers'], 1, $val['test_numbers']);
            if($error == true){
                 $k++;
            }
        }
        if($k != count($errorPeriods)){
            return $this->jsonError(109, '未全部修改成功');
        }  else {
            return $this->jsonError(600, '全部修改成功');
        }
    }
}