<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "coupons".
 *
 * @property integer $coupons_id
 * @property string $batch
 * @property string $coupons_name
 * @property integer $type
 * @property integer $is_gift
 * @property integer $discount
 * @property integer $numbers
 * @property integer $use_range
 * @property integer $less_consumption
 * @property integer $reduce_money
 * @property integer $days_num
 * @property integer $stack_use
 * @property integer $use_date
 * @property string $start_date
 * @property string $end_date
 * @property string $send_content
 * @property integer $send_num
 * @property integer $use_num
 * @property integer $opt_id
 * @property string $create_time
 * @property string $update_time
 */
class Coupons extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'coupons';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'is_gift', 'discount', 'numbers', 'use_range', 'less_consumption', 'reduce_money', 'days_num', 'stack_use', 'use_date', 'send_num', 'use_num', 'opt_id'], 'integer'],
            [['numbers', 'less_consumption'], 'required'],
            [['create_time', 'update_time'], 'safe'],
            [['batch'], 'string', 'max' => 20],
            [['coupons_name'], 'string', 'max' => 50],
            [['start_date', 'end_date'], 'string', 'max' => 30],
            [['send_content'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'coupons_id' => 'Coupons ID',
            'batch' => 'Batch',
            'coupons_name' => 'Coupons Name',
            'type' => 'Type',
            'is_gift' => 'Is Gift',
            'discount' => 'Discount',
            'numbers' => 'Numbers',
            'use_range' => 'Use Range',
            'less_consumption' => 'Less Consumption',
            'reduce_money' => 'Reduce Money',
            'days_num' => 'Days Num',
            'stack_use' => 'Stack Use',
            'use_date' => 'Use Date',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'send_content' => 'Send Content',
            'send_num' => 'Send Num',
            'use_num' => 'Use Num',
            'opt_id' => 'Opt ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
