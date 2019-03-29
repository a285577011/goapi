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
class TaskType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'task_type';
    }
    /**
     * 触发提醒
     * @param String $from params | actions | news
     * @param Int $obid 平台id或者活动id或者资讯id
     */
	public static function notice($from,$obid){
		if(empty($from)||empty($obid))return;
		switch ($from){
			case "params":
				parent::updateAll(["status"=>1],["id"=>1]);
				Platform::updateAll(["notice"=>1],["platform_id"=>$obid]);
				break;
			case "actions":
				parent::updateAll(["status"=>1],["id"=>2]);
				PlatformAction::updateAll(["notice"=>1],["platform_action_id"=>$obid]);
				break;
			case "news":
				break;
			
		}
	}
    
    
}
