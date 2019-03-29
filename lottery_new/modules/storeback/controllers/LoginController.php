<?php

namespace app\modules\storeback\controllers;

use Yii;
use yii\web\Controller;

/**
 * Default controller for the `storeback` module
 */
class LoginController extends Controller {

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex() {
        return $this->render('index');
    }

}
