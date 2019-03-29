<?php

namespace app\modules\storeback\controllers;

use Yii;
use yii\web\Controller;

/**
 * Default controller for the `storeback` module
 */
class SysadminController extends Controller {

    /**
     * Renders the index view for the module
     * @return string
     */
    public $layout = 'main';

    public function actionIndex() {
        return $this->render('index');
    }
    
}
