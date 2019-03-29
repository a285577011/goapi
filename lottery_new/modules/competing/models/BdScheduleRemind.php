<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_schedule_remind".
 *
 * @property integer $schedule_remind_id
 * @property string $schedule_mid
 * @property integer $schedule_type
 * @property string $content
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class BdScheduleRemind extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_schedule_remind';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_type'], 'integer'],
            [['content'], 'string'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 11],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'schedule_remind_id' => 'Schedule Remind ID',
            'schedule_mid' => 'Schedule Mid',
            'schedule_type' => 'Schedule Type',
            'content' => 'Content',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
