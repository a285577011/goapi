<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "group_trend_chart".
 *
 * @property integer $group_trend_chart_id
 * @property string $lottery_name
 * @property string $lottery_code
 * @property string $periods
 * @property string $open_code
 * @property string $test_nums
 * @property string $hundred_omission
 * @property string $ten_omission
 * @property string $digits_omission
 * @property string $group_omission
 * @property string $analysis
 * @property string $sum_omission
 * @property string $span_omission
 * @property string $sumtail_omission
 * @property string $modify_time
 * @property string $create_time
 * @property integer $opt_id
 * @property string $update_time
 */
class GroupTrendChart extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'group_trend_chart';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_name', 'lottery_code', 'periods', 'open_code', 'hundred_omission', 'ten_omission', 'digits_omission', 'group_omission'], 'required'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['opt_id'], 'integer'],
            [['lottery_name', 'lottery_code', 'periods', 'test_nums'], 'string', 'max' => 25],
            [['open_code', 'hundred_omission', 'ten_omission', 'digits_omission'], 'string', 'max' => 50],
            [['group_omission'], 'string', 'max' => 100],
            [['analysis', 'sum_omission', 'span_omission', 'sumtail_omission'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'group_trend_chart_id' => 'Group Trend Chart ID',
            'lottery_name' => 'Lottery Name',
            'lottery_code' => 'Lottery Code',
            'periods' => 'Periods',
            'open_code' => 'Open Code',
            'test_nums' => 'Test Nums',
            'hundred_omission' => 'Hundred Omission',
            'ten_omission' => 'Ten Omission',
            'digits_omission' => 'Digits Omission',
            'group_omission' => 'Group Omission',
            'analysis' => 'Analysis',
            'sum_omission' => 'Sum Omission',
            'span_omission' => 'Span Omission',
            'sumtail_omission' => 'Sumtail Omission',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'opt_id' => 'Opt ID',
            'update_time' => 'Update Time',
        ];
    }
}
