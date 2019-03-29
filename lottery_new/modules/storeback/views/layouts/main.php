<?php
/* @var $this \yii\web\View */
/* @var $content string */

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);
//var_dump($this->context->id);exit();
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
        <script src="/api/js/jquery.min.js"></script>
        <script src="/api/js/loaddata.js"></script>
    </head>
    <body  class="iframe-body">
        <?php $this->beginBody() ?>
        <div class="iframe-content">
            <div id="content-body"> 
    
                <button type="button" class="am-btn am-btn-primary freshFrame"  onclick="javascript:location.reload();"><i class="am-icon-refresh">刷新</i></button>     

                <?= $content ?>
            </div>
            <div class="paginationContainer" style="bottom: 40px;position: fixed;right: 8px;">
                <div class="M-box"></div>
            </div>
        </div>
        <?php $this->endBody() ?>
    </body>

</html>
<?php $this->endPage() ?>
