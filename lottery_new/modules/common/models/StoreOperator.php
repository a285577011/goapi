<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "store_operator".
 *
 * @property integer $store_operator_id
 * @property integer $user_id
 * @property integer $store_id
 * @property integer $status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class StoreOperator extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store_operator';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'store_id'], 'required'],
            [['user_id', 'store_id', 'status'], 'integer'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_operator_id' => 'Store Operator ID',
            'user_id' => 'User ID',
            'store_id' => 'Store ID',
            'status' => 'Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
