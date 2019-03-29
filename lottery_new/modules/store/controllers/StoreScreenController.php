<?php

namespace app\modules\store\controllers;

use app\modules\common\services\KafkaService;
use app\modules\store\models\StoreScreen;
use Yii;
use yii\web\Controller;


class StoreScreenController extends Controller {


    public function __construct($id, $module, $config = []) {

        parent::__construct($id, $module, $config);
    }

    /**
     * 门店电视屏登录
     * @auther GL zyl
     * @return type
     */
    public function actionLogin() {
        $request = Yii::$app->request;
        $screenKey = $request->post('screen_key');
        $storeScreen = StoreScreen::findone(['screen_key'=>$screenKey]);

        if(!$storeScreen){
            return $this->jsonError(100, '验证失败，无效Key');
        }else{
            if($storeScreen->is_login == 1){
                return $this->jsonError(100, '验证失败，该账户正在使用中。');
            }
            $storeScreen->is_login =1;
            $storeScreen->modify_time = date('Y-m-d H:i:s');
            if(!$storeScreen->save()){
                print_r($storeScreen->errors);die;
                KafkaService::addLog('store_screen',$storeScreen->errors);
            }
        }
        return $this->jsonResult(600, '验证成功', $storeScreen->store_code);
    }
    /**
     * 门店电视屏退出登录
     * @auther GL zyl
     * @return type
     */
    public function actionLogout() {
        $request = Yii::$app->request;
        $screenKey = $request->post('screen_key');
        $storeScreen = StoreScreen::findone(['screen_key'=>$screenKey]);
        if(!$storeScreen){
            return $this->jsonError(100, '验证失败，无效Key');
        }else{
            $storeScreen->is_login =0;
            $storeScreen->modify_time = date('Y-m-d H:i:s');
            if(!$storeScreen->save()){
                print_r($storeScreen->errors);die;
            }
        }
        return $this->jsonResult(600, '退出成功', $storeScreen->store_code);
    }

}
