<?php

namespace app\modules\experts;

/**
 * experts module definition class
 */
class experts extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\experts\controllers';

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

    public function behaviors() {
        return
                [
                    "ExpertLoginFilter" => [
                        "class" => 'app\modules\core\filters\ExpertLoginFilter',
                        'except' => [
                            'expert/expert-login',
                            'expert/get-schedule-list',
                            'expert/get-schedule-detail',
                            'expert/get-league-list',
                            'expert/update-pwd',
                            'expert/get-sms-code'
                        ],
                    ],
        ];
    }
    
    private function SetContainer($relation) {
        foreach ($relation as $key => $value) {
            \Yii::$container->set($key, $value);
        }
    }
}
