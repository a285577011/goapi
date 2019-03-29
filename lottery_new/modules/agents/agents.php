<?php

namespace app\modules\agents;

/**
 * user module definition class
 */
class agents extends \yii\base\Module {

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\agents\controllers';

    /**
     * @inheritdoc
     */
    public function init() {
    	\Yii::$app->db->enableSlaves = false;
        parent::init();
        $this->SetContainer([
            'app\modules\user\services\IUserService' => 'app\modules\user\services\UserService',
        ]);
    }

    public function behaviors() {
        return [
            "AgentsCheckFilter" => [
                "class" => 'app\modules\core\filters\AgentsCheckFilter',
                'except' => [
                    'agents/platform-user-login',
                    'agents/gl-test-login',
                    'agents/agent-user-login',
                    'agents/agent-user-auth',
                    'agents/register-openid'
                ]
            ]
        ];
    }

    private function SetContainer($relation) {
        foreach ($relation as $key => $value) {
            \Yii::$container->set($key, $value);
        }
    }

}
