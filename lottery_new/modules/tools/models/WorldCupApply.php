<?php

namespace app\modules\tools\models;

use Yii;

/**
 * This is the model class for table "world_cup_apply".
 *
 * @property integer $id
 * @property string $user_name
 * @property string $user_tel
 * @property string $field
 * @property string $field_name
 * @property string $remark
 * @property integer $status
 * @property string $level
 * @property string $money
 * @property string $create_time
 */
class WorldCupApply extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'world_cup_apply';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['user_name', 'user_tel', 'field', 'field_name'], 'string', 'max' => 45],
            [['remark'], 'string', 'max' => 500],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_name' => 'User Name',
            'user_tel' => 'User Tel',
            'field' => 'Field',
            'field_name' => 'Field Name',
            'remark' => 'Remark',
            'status' => 'Status',
            'level' => 'Level',
            'money' => 'Money',
            'create_time' => 'Create Time',
        ];
    }
}
