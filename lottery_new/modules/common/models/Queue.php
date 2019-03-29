<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "queue".
 *
 * @property integer $queue_id
 * @property string $job
 * @property string $queue_name
 * @property string $args
 * @property integer $push_status
 * @property integer $status
 * @property string $exception
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class Queue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['push_status', 'status'], 'integer'],
            [['exception'], 'string'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['job', 'queue_name'], 'string', 'max' => 50],
            [['args'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'queue_id' => 'Queue ID',
            'job' => 'Job',
            'queue_name' => 'Queue Name',
            'args' => 'Args',
            'push_status' => 'Push Status',
            'status' => 'Status',
            'exception' => 'Exception',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
