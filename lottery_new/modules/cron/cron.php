<?php

namespace app\modules\cron;

/**
 * user module definition class
 */
class cron extends \yii\base\Module {

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\cron\controllers';

    /**
     * @inheritdoc
     */
    public function init() {
        \Yii::$app->db->enableSlaves = false;
        parent::init();
    }

    public function behaviors() {
        return
                [
                    "LoginFilter" => [
                        "class" => 'app\modules\core\filters\LoginFilter',
                        'only' => [
                            'cron/index',
                        ],
                    ],
        ];
    }

}
