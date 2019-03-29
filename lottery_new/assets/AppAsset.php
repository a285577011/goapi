<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle {

    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'api/css/site.css',
        'api/css/amazeui.min.css',
        'api/css/admin.css',
        'api/css/app.css',
        'api/css/content.css',
        'api/css/style.css',
        'api/css/pagination.css'
    ];
    public $js = [
        'api/js/echarts.min.js',
        'api/js/amazeui.min.js',
        'api/js/iscroll.js',
        'api/js/app.js',
        'api/js/content.js',
        'api/js/jedate.js',
        'api/js/Ecalendar.jquery.min.js',
        'api/js/jquery.pagination.js',
        'api/js/socket.io.js'
    ];
    public $depends = [
//        'yii\web\YiiAsset',
//        'yii\bootstrap\BootstrapAsset',
    ];

}
