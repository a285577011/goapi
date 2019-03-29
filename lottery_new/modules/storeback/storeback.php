<?php

namespace app\modules\storeback;

/**
 * storeback module definition class
 */
class storeback extends \yii\base\Module {

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\storeback\controllers';

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
    }

//    public function behaviors() {
//        return
//                [
//                    "LoginFilter" => [
//                        "class" => 'app\modules\core\filters\LoginFilter',
//                        "except" => [
//                            "default/index",
//                            "login/index",
//                            "piaowu/index"
//                        ]
//                    ],
//        ];
//    }

}
