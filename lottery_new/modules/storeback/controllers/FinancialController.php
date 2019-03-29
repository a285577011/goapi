<?php

namespace app\modules\storeback\controllers;

use Yii;
use yii\web\Controller;

class FinancialController extends Controller{
     public $layout = 'main';
     public function actionIndex() {
        return $this->render('index');
    }
}

