<?php

namespace app\modules\storeback\controllers;

use Yii;
use yii\web\Controller;

class ReportController extends Controller{
     public $layout = 'main';
     public function actionIndex() {
        return $this->render('index');
    }
   public function actionSaledetail(){
        return $this->render('saledetail');
    }
    public function actionSaleOrderList(){
        return $this->render('saleorderlist');
    }
}

