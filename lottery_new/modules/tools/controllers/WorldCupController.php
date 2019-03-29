<?php

namespace app\modules\tools\controllers;

use app\modules\tools\models\WorldCupApply;
use yii\web\Controller;
;

class WorldCupController extends Controller {

    private $fileds = [
        'M1'=>'俄罗斯vs沙特阿拉伯',
        'M5'=>'法国vs澳大利亚',
        'M11'=>'德国vs墨西哥',
        'M15'=>'波兰vs塞内加尔',
        'M17'=>'俄罗斯vs埃及',
        'M19'=>'葡萄牙vs摩洛哥',
        'M25'=>'巴西vs哥斯达黎加',
        'M29'=>'比利时vs突尼斯',
        'M30'=>'英格兰vs巴拿马',
        'M35'=>'伊朗vs葡萄牙',
        'M36'=>'西班牙vs摩洛哥',
        'M37'=>'丹麦vs法国',
        'M39'=>'尼日利亚vs阿根廷',
        'M41'=>'塞尔维亚vs巴西',
        'M49'=>'1Avs2B',
        'M50'=>'1Cvs2D',
        'M51'=>'1Bvs2A',
        'M53'=>'1Evs2F',
        'M54'=>'1Gvs2H',
        'M55'=>'1Fvs2E',
        'M56'=>'1Hvs2G',
        'M57'=>'W49vsW50',
        'M58'=>'W53vsW54',
        'M59'=>'W51vsW52',
        'M60'=>'W55vsW56',
        'M61'=>'W57vsW58',
        'M62'=>'59WvsW60',
        'M64'=>'W61vsW62',
    ];


    public function actionGetFileds(){
        return $this->jsonResult(600,'succ',$this->fileds);
    }

    /**
     * 说明:世界杯门票购买申请
     * @author chenqiwei
     * @date 2018/6/6 上午9:24
     * @param
     * @return
     */
    public function actionApply(){
        $request = \Yii::$app->request;
        $userName = $request->post_nn('user_name');
        $userTel = $request->post_nn('user_tel');
        $field = $request->post_nn('field');//M1
        $level = $request->post('level');//座位等级
        $money = $request->post('money');//金额

        $worldcup = WorldCupApply::find()->where(['user_tel'=>$userTel,'field'=>$field])->one();
        if($worldcup){
            return $this->jsonError(400,'该手机号已申请该场次，请检查或联系客服。');
        }
        $worldcup = new WorldCupApply();
        $worldcup->user_name = $userName;
        $worldcup->user_tel = $userTel;
        $worldcup->field = $field;
        $worldcup->field_name = $this->fileds[$field];
        $worldcup->level = $level;
        $worldcup->money = $money;
        $worldcup->create_time = date('Y-m-d H:i:s');
        $worldcup->save();
        return $this->jsonResult(600,'申请成功，请等待客服与您联系。',$worldcup->attributes);
    }
    
}