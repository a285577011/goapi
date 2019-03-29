<?php

namespace app\modules\tools;

/**
 * tools module definition class
 */
class tools extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\tools\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        \Yii::$app->db->enableSlaves = false;
        parent::init();

        // custom initialization code goes here
    }
    
    public function behaviors()
    {
        return
        [
//            "PrivateFilter" =>[
//                "class" => 'app\modules\core\filters\PrivateFilter',
//                'except' => [
////                     'user/get-user-detail',
//                ],
//            ],
        ];
    
    }
}
