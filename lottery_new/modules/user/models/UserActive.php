<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "user_active".
 *
 * @property integer $user_active_id
 * @property integer $user_id
 * @property integer $source_id
 * @property string $active_type
 * @property string $active_coin_value
 * @property string $start_time
 * @property string $end_time
 * @property integer $receive_status
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class UserActive extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_active';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'source_id', 'receive_status', 'status'], 'integer'],
            [['active_coin_value'], 'number'],
            [['start_time', 'end_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['active_type'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_active_id' => 'User Active ID',
            'user_id' => 'User ID',
            'source_id' => 'Source ID',
            'active_type' => 'Active Type',
            'active_coin_value' => 'Active Coin Value',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'receive_status' => 'Receive Status',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
