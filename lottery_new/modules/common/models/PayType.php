<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "pay_type".
 *
 * @property integer $pay_type_id
 * @property integer $pay_type
 * @property string $pay_type_name
 * @property string $pay_type_code
 * @property integer $parent_id
 * @property string $parent_name
 * @property integer $status
 * @property string $remark
 * @property integer $pay_type_sort
 * @property integer $default
 * @property string $opt_name
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class PayType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pay_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pay_type', 'pay_type_name', 'pay_type_code', 'parent_id'], 'required'],
            [['pay_type', 'parent_id', 'status', 'pay_type_sort', 'default'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['pay_type_name', 'pay_type_code', 'parent_name', 'opt_name'], 'string', 'max' => 100],
            [['remark'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pay_type_id' => 'Pay Type ID',
            'pay_type' => 'Pay Type',
            'pay_type_name' => 'Pay Type Name',
            'pay_type_code' => 'Pay Type Code',
            'parent_id' => 'Parent ID',
            'parent_name' => 'Parent Name',
            'status' => 'Status',
            'remark' => 'Remark',
            'pay_type_sort' => 'Pay Type Sort',
            'default' => 'Default',
            'opt_name' => 'Opt Name',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
