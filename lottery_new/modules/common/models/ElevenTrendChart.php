<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "eleven_trend_chart".
 *
 * @property integer $eleven_trend_chart_id
 * @property string $lottery_name
 * @property string $lottery_code
 * @property string $periods
 * @property string $open_code
 * @property string $optional_omission
 * @property string $qone_omission
 * @property string $qtwo_omission
 * @property string $qthree_omission
 * @property string $qtwo_group_omission
 * @property string $qthree_group_omission
 * @property string $analysis
 * @property string $span_omission
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class ElevenTrendChart extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'eleven_trend_chart';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_name', 'lottery_code', 'periods', 'open_code'], 'required'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['lottery_name', 'lottery_code'], 'string', 'max' => 25],
            [['periods', 'open_code'], 'string', 'max' => 50],
            [['optional_omission', 'qone_omission', 'qtwo_omission', 'qthree_omission', 'qtwo_group_omission', 'qthree_group_omission'], 'string', 'max' => 100],
            [['analysis', 'span_omission'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'eleven_trend_chart_id' => 'Eleven Trend Chart ID',
            'lottery_name' => 'Lottery Name',
            'lottery_code' => 'Lottery Code',
            'periods' => 'Periods',
            'open_code' => 'Open Code',
            'optional_omission' => 'Optional Omission',
            'qone_omission' => 'Qone Omission',
            'qtwo_omission' => 'Qtwo Omission',
            'qthree_omission' => 'Qthree Omission',
            'qtwo_group_omission' => 'Qtwo Group Omission',
            'qthree_group_omission' => 'Qthree Group Omission',
            'analysis' => 'Analysis',
            'span_omission' => 'Span Omission',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
