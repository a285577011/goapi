<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "user_attention".
 *
 * @property integer $attention_id
 * @property integer $user_id
 * @property string $schedule_mid
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class UserAttention extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_attention';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'schedule_mid'], 'required'],
            [['user_id'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'attention_id' => 'Attention ID',
            'user_id' => 'User ID',
            'schedule_mid' => 'Schedule Mid',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
