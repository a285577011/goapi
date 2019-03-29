<?php

namespace app\modules\plans;

/**
 * plans module definition class
 */
class plans extends \yii\base\Module {

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\plans\controllers';

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();

        // custom initialization code goes here
    }

    public function behaviors() {
        return
                [
                    "LoginFilter" => [
                        "class" => 'app\modules\core\filters\LoginFilter',
                        'only' => [
                            'plans/subscribe-plan',
                            'plans/get-subscribe-list',
                            'plans/get-subscribe-detail',
                            'plans/get-my-order-detail',
                            'plans/get-my-order-list',
//                            'plans/get-plan-list',
//                            'plans/get-plan-detail',
//                            'plans/get-iposted-list',
//                            'plans/get-iposted-detail',
//                            'plans/get-custom-list',
//                            'plans/get-custom-plan-detail',
//                            'plans/get-order-list',
//                            'plans/get-order-detail',
//                            'plans/post-plan',
//                            'plans/stop-plan',
//                            'plans/play-order',
//                            'plans/settltment-plan'
                        ],
                    ],
                    "StoreOperatorFilter" => [
                        "class" => 'app\modules\core\filters\StoreOperatorFilter',
                        'only' => [
//                            'plans/subscribe-plan',
//                            'plans/get-subscribe-list',
//                            'plans/get-subscribe-detail',
//                            'plans/get-my-order-detail',
//                            'plans/get-my-order-list',
                            'plans/get-plan-list',
                            'plans/get-plan-detail',
                            'plans/get-iposted-list',
                            'plans/get-iposted-detail',
                            'plans/get-custom-list',
                            'plans/get-custom-plan-detail',
                            'plans/get-order-list',
                            'plans/get-order-detail',
                            'plans/post-plan',
                            'plans/stop-plan',
                            'plans/play-order',
                            'plans/settltment-plan',
                            'plans/accept-plan'
                        ],
                    ]
        ];
    }

}
