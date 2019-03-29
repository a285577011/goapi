<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_schedule_event".
 *
 * @property integer $schedule_event_id
 * @property string $schedule_mid
 * @property string $schedule_event_mid
 * @property integer $team_type
 * @property string $team_name
 * @property integer $event_type
 * @property string $event_type_name
 * @property string $event_content
 * @property string $event_time
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property integer $cf
 */
class BdScheduleEvent extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_schedule_event';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['team_type', 'event_type', 'cf'], 'integer'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid', 'schedule_event_mid'], 'string', 'max' => 11],
            [['team_name', 'event_time'], 'string', 'max' => 10],
            [['event_type_name'], 'string', 'max' => 20],
            [['event_content'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'schedule_event_id' => 'Schedule Event ID',
            'schedule_mid' => 'Schedule Mid',
            'schedule_event_mid' => 'Schedule Event Mid',
            'team_type' => 'Team Type',
            'team_name' => 'Team Name',
            'event_type' => 'Event Type',
            'event_type_name' => 'Event Type Name',
            'event_content' => 'Event Content',
            'event_time' => 'Event Time',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'cf' => 'Cf',
        ];
    }
}
