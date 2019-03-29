<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "direct_trend_chart".
 *
 * @property integer $direct_trend_chart_id
 * @property string $lottery_name
 * @property string $lottery_code
 * @property string $periods
 * @property string $open_code
 * @property string $red_omission
 * @property string $blue_omission
 * @property string $red_analysis
 * @property string $redtail_omission
 * @property string $blue_analysis
 * @property string $modify_time
 * @property string $create_time
 * @property integer $opt_id
 * @property string $update_time
 */
class DirectTrendChart extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'direct_trend_chart';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_name', 'lottery_code', 'periods', 'open_code', 'red_omission'], 'required'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['opt_id'], 'integer'],
            [['lottery_name', 'lottery_code'], 'string', 'max' => 25],
            [['periods', 'open_code'], 'string', 'max' => 50],
            [['red_omission', 'blue_omission'], 'string', 'max' => 100],
            [['red_analysis', 'redtail_omission', 'blue_analysis'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'direct_trend_chart_id' => 'Direct Trend Chart ID',
            'lottery_name' => 'Lottery Name',
            'lottery_code' => 'Lottery Code',
            'periods' => 'Periods',
            'open_code' => 'Open Code',
            'red_omission' => 'Red Omission',
            'blue_omission' => 'Blue Omission',
            'red_analysis' => 'Red Analysis',
            'redtail_omission' => 'Redtail Omission',
            'blue_analysis' => 'Blue Analysis',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'opt_id' => 'Opt ID',
            'update_time' => 'Update Time',
        ];
    }
}
