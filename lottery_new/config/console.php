<?php

//Yii::setAlias('@tests', dirname(__DIR__) . '/tests/codeception');

if(YII_ENV_DEV){
    $params = require(__DIR__ . '/params_test.php');
}else{
    $params = require(__DIR__ . '/params.php');
}
$db = require(__DIR__ . '/db.php');

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
		'cache' => [
			'class' => 'yii\caching\FileCache',
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
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
