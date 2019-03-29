<?php

namespace app\modules\store;

/**
 * store module definition class
 */
class store extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\store\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        \Yii::$app->db->enableSlaves = false;
        parent::init();
        $this->SetContainer([
            'app\modules\user\services\IUserService'=>'app\modules\user\services\UserService',
        ]);
    }
    public function behaviors()
    {
        return
        [
//            "StoreOperatorFilter" =>[
//                "class" => 'app\modules\core\filters\StoreOperatorFilter',
//                "except"=>[
//                    "store/lottery-category"
//                ]
//            ]
            "StoreOperatorFilter" =>[
                "class" => 'app\modules\core\filters\StoreOperatorFilter',
                "except"=>[
                    "store/lottery-category",
                    "store/get-map-store-list",
                    "store-screen/*"
                ]
            ]
        ];
    
    }
    private function SetContainer($relation)
    {
        foreach ($relation as $key => $value)
        {
            \Yii::$container->set($key,$value);
        }
    }
}
