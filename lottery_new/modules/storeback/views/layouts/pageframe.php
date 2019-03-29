<?php
/* @var $this \yii\web\View */
/* @var $content string */

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <?= Html::csrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
        <link rel="shortcut icon" href="/api/i/ico.jpg" >
        <script src="/api/js/jquery.min.js"></script>
        <script src="/api/js/loaddata.js"></script>
    </head>
    <body>
        <?php $this->beginBody() ?>
        <!--头部导航栏-->
        <?= $this->render('header', ["html_topnav" => $this->params["html_topnav"]]) ?>
        <!--<div class="loadIconMask" style="display: none;"><i class="am-icon-spinner am-icon-spin loadIcon"></i></div>-->

        <div class="tpl-page-container tpl-page-header-fixed">
            <!--侧边栏-->
            <?= $this->render('leftnav', ["html_leftnav" => $this->params["html_leftnav"]]) ?>

            <!--主要内容  start-->
            <div class="tpl-content-wrapper" style="padding-top: 0px;">
                <ul id="iframenavs" style="position: fixed;z-index: 1;">
                </ul>
                <div class="content">
                </div>
            </div>
            <!--主要内容  end-->

        </div>
        <script type='text/javascript'>
            $(function () {
                window._location;
                window.body = $("body");
                window.soundSwitch = true;
                window.webSocketIp='<?= Yii::$app->params["webSocket"] ?>';
//                window._winHeight;
//                window._winHeight = $(window).height() - 135;//190;//205;
//                var leftHeight = $(window).height() - 93;//190;//205;
//                var maxwinHeight = $(window).height() - 93;
////                $(".content").height(winHeight);
//                $(".pageiframe").height(window._winHeight);
                urlActive('<?= $this->context->id ?>');
                mytopnavClick($("#topnavContainer .mytopnav").first());
                $('#iframenavs li .closepagenav').click(function () {
                    closepagenavclick(this);
                });
                $('#iframenavs .iframenav span').click(function () {
                    iframenavclick(this);
                });
                $(".tpl-left-nav-list .tpl-left-nav-menu .tpl-left-nav-item a").click(function () {
                    leftNavItemclick(this);
                });
                $("#topnavContainer .mytopnav").click(function () {
                    mytopnavClick(this);
                });
//                $(".tpl-left-nav-hover").height(leftHeight);
//                window.onresize = function () {
//                    window._winHeight = $(window).height() - 135;//190;//205;
//                    leftHeight = $(window).height() - 93;//190;//205;
//                    maxwinHeight = $(window).height() - 93;
//                    $(".pageiframe").height(window._winHeight);
//                    $(".tpl-left-nav-hover").height(leftHeight);
//                }
                connectSocket();
            });
        </script>
        <?php $this->endBody() ?>
    </body>

</html>
<?php $this->endPage() ?>
