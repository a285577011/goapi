<?php

namespace app\modules\cron\models;

use Yii;
use app\modules\platform\models\Platform;
use app\modules\platform\models\PlatformAction;

/**
 * This is the model class for table "third_user".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $third_uid
 * @property integer $type
 * @property string $icon
 * @property string $nickname
 * @property integer $sex
 * @property string $create_time
 */
class Cron extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cron';
    }
    /**
     * 触发提醒
     * @param String $from params | actions | news
     * @param Int $obid 平台id或者活动id或者资讯id
     */
	public static function check($name,$code,$code2){
		$cron = parent::find()->where("name='{$name}' or code = '{$code}' or code2='{$code2}'")->one();
		if(empty($code)){
			$cron = new Cron();
			$cron->name =  $name;
			$cron->code = $code;
			$cron->code2 = $code2;
			$cron->status = 0;
			$cron->save();
		}
		
	}
    
    
}
