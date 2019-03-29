<?php

namespace app\modules\experts\models;

use Yii;

/**
 * This is the model class for table "user_expert".
 *
 * @property integer $user_expert_id
 * @property integer $user_id
 * @property integer $expert_id
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class UserExpert extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_expert';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'expert_id'], 'required'],
            [['user_id', 'expert_id', 'status'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_expert_id' => 'User Expert ID',
            'user_id' => 'User ID',
            'expert_id' => 'Expert ID',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
