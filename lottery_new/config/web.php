<?php
if(YII_ENV_DEV){
    $params = require(__DIR__ . '/params_test.php');
}else{
    $params = require(__DIR__ . '/params.php');
}

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'timeZone' => 'Asia/Shanghai',
    'modules' => [
        'user' => [
            'class' => 'app\modules\user\user',
        ],
        'welfare' => [
            'class' => 'app\modules\welfare\welfare',
        ],
        'sports' => [
            'class' => 'app\modules\sports\sports',
        ],
        'publicinterface' => [
            'class' => 'app\modules\publicinterface\publicinterface',
        ],
        'agents' =>[
            'class' => 'app\modules\agents\agents',
        ],
        'cron' => [
            'class' => 'app\modules\cron\cron',
        ],
       'test' => [
           'class' => 'app\modules\test\test',
       ],
        'competing' => [
            'class' => 'app\modules\competing\competing',
        ],
        'store' => [
            'class' => 'app\modules\store\store',
        ],
        'tools' => [
            'class' => 'app\modules\tools\tools',
        ],
        'plans' => [
            'class' => 'app\modules\plans\plans',
        ],
        'openapi' => [
            'class' => 'app\modules\openapi\openapi',
        ],
        'pay' => [
            'class' => 'app\modules\pay\pay',
        ],
        'storeback' => [
            'class' => 'app\modules\storeback\storeback',
        ],
        'experts' => [
            'class' => 'app\modules\experts\experts',

        ],
        'orders' => [
            'class' => 'app\modules\orders\order',
        ],
        'agents' =>[
            'class' => 'app\modules\agents\agents',
        ],
        'numbers' => [
            'class' => 'app\modules\numbers\numbers',
        ]
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'goodluck2017',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'text/json' => 'yii\web\JsonParser',
                'application/xml' => 'yii\web\XmlParser',
                'text/xml' => 'yii\web\XmlParser',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,//false:发送有奖，true:在日志文件中生成邮件，不发送
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.163.com',  //每种邮箱的host配置不一样
                'username' => 'kevi_chen@163.com',
                'password' => 'kevichen59',//163客户端授权码，非登录密码
                'port' => '25',
                'encryption' => 'tls',
            ],
            'messageConfig' => [
                'charset' => 'UTF-8',
                'from' => ['kevi_chen@163.com'=>'咕啦测试中心'],
            ],
        ],
//         'jpush' =>[
//             '@JPush' => '@vendor/jpush/src/JPush',
//             'class' => '@vendor/jpush/src/JPush/Client',
//         ],
        'redis' => require(__DIR__ . '/redis.php'),
        'redis2' => require(__DIR__ . '/redis2.php'),
        'wxpay' => [
            'class' => 'app\modules\components\wxpay\wxpay',
        ],
        'alipay' => [
            'class' => 'app\modules\components\alipay\alipay',
        ],
        'log' => require(__DIR__ . '/log_config.php'),
        'db' => require(__DIR__ . '/db.php'),
    	'db2' => require(__DIR__ . '/db2.php'),
    	'db3' => require(__DIR__ . '/db3.php'),
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            // Disable index.php
            'showScriptName' => false,
            // Disable r= routes
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'rules' => [
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            'allowedIPs' => ['127.0.0.1', '::1','211.149.205.*','27.154.231.158'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            'allowedIPs' => ['127.0.0.1', '::1','211.149.205.*','27.154.231.158'],
    ];
}

return $config;
