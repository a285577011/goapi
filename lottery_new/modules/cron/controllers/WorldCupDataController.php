<?php

namespace app\modules\cron\controllers;

use yii\web\Controller;
use app\modules\competing\models\WorldcupChp;
use app\modules\competing\models\WorldcupFnl;
use app\modules\common\helpers\Commonfun;

class WorldCupDataController extends Controller {
    
    /**
     * 世界杯冠军预猜数据对接
     * @return type
     */
    public function actionChpData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $teamCode = Commonfun::arrParameter($data, 'team_code');
        $teamName = Commonfun::arrParameter($data, 'team_name');
        $teamImg = Commonfun::arrParameter($data, 'team_img');
        $teamOdds = Commonfun::arrParameter($data, 'team_odds');
        $status = Commonfun::arrParameter($data, 'status');
        $teamChance = Commonfun::arrParameter($data, 'team_chance');

        $chp = WorldcupChp::findOne(['open_mid' => $openMid]);
        if (empty($chp)) {
            $chp = new WorldcupChp();
           $chp->create_time = date('Y-m-d H:i:s');
        } else {
            $chp->modify_time = date('Y-m-d H:i:s');
        }
        $chp->open_mid = $openMid;
        $chp->team_code = $teamCode;
        $chp->team_name = $teamName;
        $chp->team_img = $teamImg;
        $chp->team_odds = $teamOdds;
        $chp->status = $status;
        $chp->team_chance = $teamChance;
        if(!$chp->validate()) {
            return \Yii::jsonResult(109, '赛程数据处理验证失败', $chp->getFirstErrors());
        }
        if(!$chp->save()){
            return \Yii::jsonResult(109, '赛程数据处理失败', $chp->getFirstErrors());
        }
        return \Yii::jsonResult(600, '赛程数据处理成功', true);
    }
    
    /**
     * 冠亚军数据对接
     * @return type
     */
    public function actionFnlData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $homeCode = Commonfun::arrParameter($data, 'home_code');
        $homeName = Commonfun::arrParameter($data, 'home_name');
        $homeImg = Commonfun::arrParameter($data, 'home_img');
        $visitCode = Commonfun::arrParameter($data, 'visit_code');
        $visitName = Commonfun::arrParameter($data, 'visit_name');
        $visitImg = Commonfun::arrParameter($data, 'visit_img');
        $teamOdds = Commonfun::arrParameter($data, 'team_odds');
        $status = Commonfun::arrParameter($data, 'status');
        $teamChance = Commonfun::arrParameter($data, 'team_chance');

        $fnl = WorldcupFnl::findOne(['open_mid' => $openMid]);
        if (empty($fnl)) {
            $fnl = new WorldcupFnl();
            $fnl->create_time = date('Y-m-d H:i:s');
        } else {
            $fnl->modify_time = date('Y-m-d H:i:s');
        }
        $fnl->open_mid = $openMid;
        $fnl->home_code = $homeCode;
        $fnl->home_name = $homeName;
        $fnl->home_img = $homeImg;
        $fnl->visit_code = $visitCode;
        $fnl->visit_name = $visitName;
        $fnl->visit_img = $visitImg;
        $fnl->team_odds = $teamOdds;
        $fnl->status = $status;
        $fnl->team_chance = $teamChance;
        
        if(!$fnl->validate()) {
            return \Yii::jsonResult(109, '赛程数据处理验证失败', $fnl->getFirstErrors());
        }
        if(!$fnl->save()){
            return \Yii::jsonResult(109, '赛程数据处理失败', $fnl->getFirstErrors());
        }
        return \Yii::jsonResult(600, '赛程数据处理成功', true);
    }
}

