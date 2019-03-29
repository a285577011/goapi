<?php

namespace app\modules\openapi;

use yii\filters\AccessControl;

/**
 * api module definition class
 */
class openapi extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\openapi\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
    public function behaviors()
    {
    	return [
    		 'OpenApiFilter'=>  [
    	          "class" => 'app\modules\core\filters\OpenApiFilter',
                'except' => [
                    'soccer/test',
                ],
            ],
    	];
//     	except only
    }
}
