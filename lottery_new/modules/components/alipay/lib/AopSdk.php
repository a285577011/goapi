<?php

if (!defined("AOP_SDK_WORK_DIR")) {
    define("AOP_SDK_WORK_DIR", "/tmp/");
}

if (!defined("AOP_SDK_DEV_MODE")) {
    define("AOP_SDK_DEV_MODE", true);
}

$lotusHome = \Yii::$app->basePath . "/modules/components/alipay/lotusphp_runtime/";
include($lotusHome . "Lotus.php");
$lotus = new Lotus();
$lotus->option["autoload_dir"] = \Yii::$app->basePath . "/modules/components/alipay/aop";
$lotus->devMode = AOP_SDK_DEV_MODE;
$lotus->defaultStoreDir = AOP_SDK_WORK_DIR;
$lotus->init();
