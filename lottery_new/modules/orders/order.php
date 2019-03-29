<?php

namespace app\modules\orders;

/**
 * orders module definition class
 */
class order extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\orders\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
    	\Yii::$app->db->enableSlaves = false;
        parent::init();

        // custom initialization code goes here
    }
     public function behaviors() {
        return
                [
                    "LoginFilter" => [
                        "class" => 'app\modules\core\filters\LoginFilter',
                        'except' => [
                           'share/get-order-info',
                           'order/delay-schedule',
                            'zmf/*',
                            'nm/*',
                        	'jw/*',
                        ],
                    ],
        ];
    }
}
