<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "activity".
 *
 * @property integer $activity_id
 * @property string $activity_name
 * @property string $use_agents
 * @property integer $type_id
 * @property integer $status
 * @property string $start_date
 * @property string $end_date
 * @property string $create_time
 */
class Activity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type_id', 'status'], 'integer'],
            [['start_date', 'end_date'], 'required'],
            [['start_date', 'end_date', 'create_time'], 'safe'],
            [['activity_name'], 'string', 'max' => 255],
            [['use_agents'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'activity_id' => 'Activity ID',
            'activity_name' => 'Activity Name',
            'use_agents' => 'Use Agents',
            'type_id' => 'Type ID',
            'status' => 'Status',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'create_time' => 'Create Time',
        ];
    }
}
