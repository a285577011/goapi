<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "activity_lucky".
 *
 * @property integer $activity_lucky_id
 * @property string $active_code
 * @property string $active_name
 * @property string $content_code
 * @property string $content_name
 * @property integer $weight
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class ActivityLucky extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'activity_lucky';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['weight', 'status'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['active_code', 'content_code'], 'string', 'max' => 25],
            [['active_name', 'content_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'activity_lucky_id' => 'Activity Lucky ID',
            'active_code' => 'Active Code',
            'active_name' => 'Active Name',
            'content_code' => 'Content Code',
            'content_name' => 'Content Name',
            'weight' => 'Weight',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
