<?php

namespace app\modules\user;

/**
 * user module definition class
 */
class user extends \yii\base\Module {

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\user\controllers';

    /**
     * @inheritdoc
     */
    public function init() {
        \Yii::$app->db->enableSlaves = false;
        parent::init();
        $this->SetContainer([
            'app\modules\user\services\IUserService' => 'app\modules\user\services\UserService',
            'app\modules\user\services\IThirduserService' => 'app\modules\user\services\ThirduserService',
        ]);
    }

    public function behaviors() {
        return [
            "LoginFilter" => [
                "class" => 'app\modules\core\filters\LoginFilter',
                'only' => [
                    'user/get-user-detail',
                    'user/get-user-type',
                    'user/get-user-intergal',
                    'user/get-pay-password-sms-code',
                    'user/settingpaypassword',
                    'user/upload-user-pic',
                    'user/set-default-follow',
                    'user/cancel-follow',
                    'user/get-follow-list',
                    'user/get-default-follow',
                    'user/get-auth-info',
                    'user/get-province',
                    'user/get-city',
                    'user/get-area',
                    'user/get-bank',
                    'user/get-bank-info',
                    'user/attest-img-upload',
                    'user/real-name-one',
                    'user/real-name-two',
                    'user/real-name-three',
                    'user/unbind',
                    'user/get-bank-sms-code',
                    'user/bink-bank-card',
                    'user/get-account-info',
                    'user/withdraw',
                    'user/set-nickname',
                    'user/get-real-name',
                    'user/get-withdraw-info',
                    'user/set-address',
                    'user/apply',
                    'user/change-phone',
                    'userclub/user-club-index',
                    'userclub/user-growth',
                    'userclub/user-glcoin',
                    'userclub/get-coin-list',
                    'userclub/today-sgin',
                    'userclub/add-signin',
                    'userclub/real-info',
                    'userclub/real-authen',
                    'userclub/user-coupon-num',
                    'userclub/user-coupon-lists',
                    'userclub/get-coupons-on',
                    'userclub/gift-lists',
                    'userclub/redeem-gift',
                    'userclub/sign-detail',
                    'userclub/random-award',
                    'user/change-tel',
                    'user/get-store-qr',
                    'user/apply-redeem-store',
                    'fjtc/*',
                    'spread/get-spread-qr',
                    'spread/get-spread-info',
                    'spread/get-invite-list',
                    'spread/set-role',
                    'api-order-user/*',
                    'user-coin/get-coin-task',
                    'user-coin/coin-recharge',
                    'user/get-gain-info',
                    'user/user-gain-coupons'
                ],
                "any" => [
                    'user/get-store-detail',
                    'user/user-follow',
                    'user/bink-bank-card',
                    'fjtc/get-redeem-code',
                    'fjtc/cron-deal',
                ],
            ]
        ];
    }

    private function SetContainer($relation) {
        foreach ($relation as $key => $value) {
            \Yii::$container->set($key, $value);
        }
    }

}
