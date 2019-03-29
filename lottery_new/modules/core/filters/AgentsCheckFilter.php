<?php

namespace app\modules\core\filters;

use app\modules\agents\models\Agents;
use yii\base\ActionFilter;
use app\modules\tools\helpers\Toolfun;
use app\modules\agents\models\AgentsIp;

class AgentsCheckFilter extends ActionFilter {

    public function __construct($config = array()) {
        parent::__construct($config);
        
        
    }

    public function beforeAction($action) {
        $request = \Yii::$app->request;
        $appId = $request->post('appId');
        $accessToken = $request->post('access_token');
        if(empty($appId)||empty($accessToken)){
            return \Yii::jsonError(100, '参数缺失');
        }
        //查找该appID 所对应的 secret_key 和 ip白名单
        $agents = Agents::find()->where(['agents_appid'=>$appId,'use_status'=>1])->asArray()->one();
        if(empty($agents)){
            return \Yii::jsonError(101, 'appId错误或该代理商已禁用');
        }
        //对比md5(secret_key)==$accessToken
        if(md5($appId.$agents['secret_key'])!=$accessToken){
            return \Yii::jsonError(103, 'access_token验签失败。请核对access_token');
        }
        if(YII_ENV_PROD){
            $toolfun = new Toolfun();
            $userIp = $toolfun->getUserIp();
            //验证appID白名单
            $isAllowedIp = AgentsIp::find()->where(['agents_id'=>$agents['agents_id'],'ip_address'=>$userIp,'status'=>1])->one();
            if(empty($isAllowedIp)){
                return \Yii::jsonError(102, 'IP鉴权失败！');
            }
        }
        \Yii::$agentId = $agents['agents_id'];

        return parent::beforeAction($action);
    }

//     public function afterAction($action, $result)
//     {
//         return parent::afterAction($action, $result);
//     }
//except only
}

?>