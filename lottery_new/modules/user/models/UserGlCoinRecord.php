<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "user_gl_coin_record".
 *
 * @property integer $gl_coin_record_id
 * @property string $order_code
 * @property integer $user_id
 * @property string $cust_no
 * @property integer $type
 * @property integer $coin_source
 * @property integer $source_id
 * @property string $coin_value
 * @property string $value_money
 * @property integer $totle_balance
 * @property string $source_type
 * @property string $remark
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class UserGlCoinRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_gl_coin_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'type'], 'required'],
            [['user_id', 'type', 'coin_source', 'source_id', 'totle_balance', 'status'], 'integer'],
            [['coin_value', 'value_money'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['order_code', 'source_type'], 'string', 'max' => 50],
            [['cust_no'], 'string', 'max' => 25],
            [['remark'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'gl_coin_record_id' => 'Gl Coin Record ID',
            'order_code' => 'Order Code',
            'user_id' => 'User ID',
            'cust_no' => 'Cust No',
            'type' => 'Type',
            'coin_source' => 'Coin Source',
            'source_id' => 'Source ID',
            'coin_value' => 'Coin Value',
            'value_money' => 'Value Money',
            'totle_balance' => 'Totle Balance',
            'source_type' => 'Source Type',
            'remark' => 'Remark',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
