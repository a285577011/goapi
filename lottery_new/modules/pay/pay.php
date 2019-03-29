<?php

namespace app\modules\pay;

/**
 * tools module definition class
 */
class pay extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\pay\controllers';

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
            "LoginFilter" => [
                "class" => 'app\modules\core\filters\LoginFilter',
                'only' => [
                    'apple-pay/*',
                ],
            ]
        ];
    
    }
}
