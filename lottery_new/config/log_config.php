<?php
//使用说明：\Yii::info('msg','winning_log');
//使用说明：\Yii::error('msg','winning_log');
return [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [//兑奖成功日志
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'], //这里说明配置的是那个，如error，warning，info，trace这些
                    'categories' => ['winning_log'], //所属类别
                    'logFile' => '@app/logs/winning/succ.log',//日志文件位置
                    'logVars' => [],//需要记录的消息类型
                    'maxFileSize' => 1024 * 2, //这些一看就知道的就不说明了
                    'maxLogFiles' => 20,
                ],
                [//兑奖失败日志
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'], //这里说明配置的是那个，如error，warning，info，trace这些
                    'categories' => ['winning_log'], 
                    'logFile' => '@app/logs/winning/error.log',
                    'logVars' => [],
                    'maxFileSize' => 1024 * 2, //这些一看就知道的就不说明了
                    'maxLogFiles' => 20,
                ],
                [//提现日志
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'], //这里说明配置的是那个，如error，warning，info，trace这些
                    'categories' => ['withdraw_log'],
                    'logFile' => '@app/logs/withdraw/withdraw.log',
                    'logVars' => [],
                    'maxFileSize' => 1024 * 2, //这些一看就知道的就不说明了
                    'maxLogFiles' => 20,
                ],
                [//生成子单错误日志
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'],
                    'categories' => ['withdraw_log'],
                    'logFile' => '@app/logs/order_detail_error/error.log',
                    'logVars' => [],
                    'maxFileSize' => 1024 * 2, //这些一看就知道的就不说明了
                    'maxLogFiles' => 20,
                ],
            	[//备份订单数据到SQLSERVER日志
            		'class' => 'yii\log\FileTarget',
            		'levels' => ['info'],
            		'categories' => ['backuporder_log'],
            		'logFile' => '@app/logs/backuporder/backuporder.log',
            		'logVars' => [],
            		'maxFileSize' => 1024 * 2, //这些一看就知道的就不说明了
            		'maxLogFiles' => 20,
            	],
            	[//备份订单数据到SQLSERVER日志
            		'class' => 'yii\log\FileTarget',
            		'levels' => ['info'],
            		'categories' => ['cron_log'],
            		'logFile' => '@app/logs/cron/cron_log'.date('Y-m-d').'.log',
            		'logVars' => [],
            		'maxFileSize' => 1024 * 2, //这些一看就知道的就不说明了
            		'maxLogFiles' => 20,
            	],
            ],
];
