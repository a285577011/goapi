<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "order_taking".
 *
 * @property integer $order_taking_id
 * @property string $order_code
 * @property integer $store_code
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class OrderTaking extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_taking';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_code', 'status'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['order_code'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_taking_id' => 'Order Taking ID',
            'order_code' => 'Order Code',
            'store_code' => 'Store Code',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
