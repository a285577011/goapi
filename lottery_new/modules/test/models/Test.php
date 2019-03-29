<?php
namespace app\modules\test\models;

use Yii;
use yii\base\Model;

class Test extends \yii\db\ActiveRecord{
    public static function tablename(){
        return 'user';
    }
}