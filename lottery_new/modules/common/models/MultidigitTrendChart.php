<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "multidigit_trend_chart".
 *
 * @property integer $multidigit_trend_chart_id
 * @property string $lottery_name
 * @property string $lottery_code
 * @property string $periods
 * @property string $open_code
 * @property string $digits_omission
 * @property string $ten_omission
 * @property string $hundred_omission
 * @property string $thousand_omission
 * @property string $ten_thousand_omission
 * @property string $hundred_thousand_omission
 * @property string $million_omission
 * @property string $analysis
 * @property string $sum_omission
 * @property string $span_omission
 * @property string $sumtail_omission
 * @property string $modify_time
 * @property string $create_time
 * @property integer $opt_id
 * @property string $update_time
 */
class MultidigitTrendChart extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'multidigit_trend_chart';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_name', 'lottery_code', 'periods', 'open_code', 'digits_omission', 'ten_omission', 'hundred_omission', 'thousand_omission', 'ten_thousand_omission'], 'required'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['opt_id'], 'integer'],
            [['lottery_name', 'lottery_code', 'periods'], 'string', 'max' => 25],
            [['open_code', 'digits_omission', 'ten_omission', 'hundred_omission', 'thousand_omission', 'ten_thousand_omission', 'hundred_thousand_omission', 'million_omission'], 'string', 'max' => 50],
            [['analysis', 'sum_omission', 'span_omission', 'sumtail_omission'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'multidigit_trend_chart_id' => 'Multidigit Trend Chart ID',
            'lottery_name' => 'Lottery Name',
            'lottery_code' => 'Lottery Code',
            'periods' => 'Periods',
            'open_code' => 'Open Code',
            'digits_omission' => 'Digits Omission',
            'ten_omission' => 'Ten Omission',
            'hundred_omission' => 'Hundred Omission',
            'thousand_omission' => 'Thousand Omission',
            'ten_thousand_omission' => 'Ten Thousand Omission',
            'hundred_thousand_omission' => 'Hundred Thousand Omission',
            'million_omission' => 'Million Omission',
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
