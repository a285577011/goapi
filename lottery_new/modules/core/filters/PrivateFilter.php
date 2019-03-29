<?php 
namespace app\modules\core\filters;

use yii\base\ActionFilter;

class PrivateFilter extends ActionFilter
{

    public function beforeAction($action)
    {
        $request = \Yii::$app->request;
        $secert = empty($request->get("secert"))? $request->get("secert") : $request->post("secert");
        if(empty($secert)||$secert != 'gula_secret'){
            return \Yii::jsonError(480, '内部访问权限');
        }
        return parent::beforeAction($action);
    }

//     public function afterAction($action, $result)
//     {
//         return parent::afterAction($action, $result);
//     }
//except only
    
}
?>