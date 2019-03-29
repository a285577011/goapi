<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "tax_record".
 *
 * @property integer $tax_record_id
 * @property string $order_code
 * @property string $tax_record_code
 * @property integer $user_id
 * @property string $cust_no
 * @property string $tax_money
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class TaxRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tax_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_code', 'tax_record_code', 'user_id', 'cust_no', 'tax_money'], 'required'],
            [['user_id'], 'integer'],
            [['tax_money'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['order_code', 'tax_record_code'], 'string', 'max' => 50],
            [['cust_no'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tax_record_id' => 'Tax Record ID',
            'order_code' => 'Order Code',
            'tax_record_code' => 'Tax Record Code',
            'user_id' => 'User ID',
            'cust_no' => 'Cust No',
            'tax_money' => 'Tax Money',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
