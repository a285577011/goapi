<?php

namespace app\modules\store\models;

use Yii;

/**
 * This is the model class for table "store_opt_log".
 *
 * @property integer $log_id
 * @property integer $operator_id
 * @property string $operator_name
 * @property integer $store_code
 * @property string $content
 * @property string $create_time
 * @property string $cust_no
 * @property string $store_name
 */
class StoreOptLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store_opt_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['operator_id', 'store_code'], 'integer'],
            [['create_time'], 'safe'],
            [['cust_no'], 'required'],
            [['operator_name'], 'string', 'max' => 50],
            [['content'], 'string', 'max' => 255],
            [['cust_no', 'store_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'log_id' => 'Log ID',
            'operator_id' => 'Operator ID',
            'operator_name' => 'Operator Name',
            'store_code' => 'Store Code',
            'content' => 'Content',
            'create_time' => 'Create Time',
            'cust_no' => 'Cust No',
            'store_name' => 'Store Name',
        ];
    }
}
