<?php
date_default_timezone_set('Asia/Shanghai');

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require_once __DIR__ . '/../../yiisoft/yii2/Yii.php';
$yiiConfig = require(__DIR__ . '/../../../config/web.php');
new yii\web\Application($yiiConfig);

require dirname(__FILE__).'/lottery_job.php';

require dirname(__FILE__).'/backupOrder_job.php';

require dirname(__FILE__).'/programme_job.php';

require dirname(__FILE__).'/custom_made_job.php';

require dirname(__FILE__).'/win_order_job.php';

require dirname(__FILE__).'/cash_article_job.php';

require dirname(__FILE__).'/order_trace_job.php';

require dirname(__FILE__).'/wx_msg_record_job.php';

require dirname(__FILE__).'/syncImUser_job.php';

require dirname(__FILE__).'/cancle_order_job.php';

require dirname(__FILE__).'/third_order_pay_job.php';

require dirname(__FILE__).'/third_order_create_job.php';

require dirname(dirname(__FILE__)).'/bin/resque';

