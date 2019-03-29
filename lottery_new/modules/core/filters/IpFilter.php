<?php 
namespace app\modules\core\filters;

use yii\base\ActionFilter;
use app\modules\tools\helpers\Toolfun;

class IpFilter extends ActionFilter
{

    public function beforeAction($action)
    {
        $userIp = Toolfun::getUserIp();
        if(in_array( $userIp,['127.0.0.2'])){
            echo 'succ';
        }else{
            \Yii::jsonError(120, 'IP not authorized');
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