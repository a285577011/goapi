<?php

namespace app\modules\publicinterface;

use yii\filters\AccessControl;

/**
 * publicinterface module definition class
 */
class publicinterface extends \yii\base\Module {

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\publicinterface\controllers';

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
                    "LoginFilter" => [
                        "class" => 'app\modules\core\filters\LoginFilter',
                        'except' => [
                            'interface/pro-period',
                            'interface/now-period',
                            'interface/bet-game',
                            'interface/his-result',
                            'interface/get-league',
                            'interface/pay',
                            'interface/notify_ali',
                            'interface/notify_wxpay',
                            'interface/notify_wxpayapp',
                            'interface/trend',
                            'interface/return_ali',
                            'interface/return_wxpay',
                            'interface/get-result',
                            'interface/getquery',
                            'interface/get-programme',
                            'interface/get-with-people',
                            'interface/store-info',
                            'interface/get-pay-record-type',
                            'interface/get-lottery',
                            'interface/get-ball-result',
                            'interface/strength-contrast',
                            'interface/double-history-match',
                            'interface/get-history-count',
                            'interface/schedule-info',
                            'interface/history-match',
                            'interface/get-pre-result',
                            'interface/get-future-schedule',
                            'interface/get-asian-handicap',
                            'interface/get-europe-odds',
                            'interface/get-schedule-lives',
                            'interface/get-europe-odds',
                            'interface/get-programme-count',
                            'interface/optional-schedule',
                            'interface/get-order-img',
                            'expert/get-article-list',
                            'expert/get-schedule-article',
                            'interface/wechat-order-detail',
                            'interface/wechat-competing-order',
                            'interface/wechat-optional-order',
                            'interface/get-lan-schedule',
                            'interface/get-compting-basket-ball',
                            'interface/get-lan-league',
                            'interface/lan-schedule-info',
                            'interface/get-lan-double-his-result',
                            'interface/get-lan-his-result',
                            'interface/get-lan-future-result',
                            'interface/get-lan-pre-result',
                            'interface/get-lan-europe-odds',
                            'interface/get-lan-asian-handicap',
                            'interface/get-lan-daxiao-odds',
                            'interface/get-lan-team-result',
                            'interface/get-lan-ments-road',
                            'interface/get-lan-team-rank',
                            'interface/get-lan-analyze',
                            'interface/get-lan-count',
                            'expert/get-good-league',
                            'expert/get-article-recommend',
                            'interface/add-order-list',
                            'interface/get-lan-live',
                            'interface/get-bananer-pic',
                            'interface/get-bd-schedule',
                            'interface/get-bananer-content',
                            'interface/get-web-conf',
                            'interface/get-compting',
                        ],
                        'any' => [
                            'interface/get-programme-detail',
                            'expert/get-expert-list',
                            'expert/get-expert-detail',
                            'expert/get-article-detail',
                        	'expert/get-article-detailxx',
                        	'expert/get-article-detailsimxx',
                            'expert/get-schedule-article-total'
                        ],
                    ],
                	'access' => [
                		'class' => AccessControl::className(),
                		'only' => ['expert/get-article-detailxx'],
                		'rules' => [
                			[
                				'ips' => [],
                				//'ips' => ['127.0.0.1','211.149.172.57','27.154.231.158'],//这里填写允许访问的IP
                				'allow' => true,
                			],
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
