<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "odds_3008".
 *
 * @property integer $odds_3008_id
 * @property integer $schedule_id
 * @property string $schedule_mid
 * @property integer $updates_nums
 * @property string $total_gold_0
 * @property integer $total_gold_0_trend
 * @property string $total_gold_1
 * @property integer $total_gold_1_trend
 * @property string $total_gold_2
 * @property integer $total_gold_2_trend
 * @property string $total_gold_3
 * @property integer $total_gold_3_trend
 * @property string $total_gold_4
 * @property integer $total_gold_4_trend
 * @property string $total_gold_5
 * @property integer $total_gold_5_trend
 * @property string $total_gold_6
 * @property integer $total_gold_6_trend
 * @property string $total_gold_7
 * @property integer $total_gold_7_trend
 * @property integer $opt_id
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class Odds3008 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3008';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_id', 'schedule_mid', 'updates_nums', 'total_gold_0', 'total_gold_0_trend', 'total_gold_1', 'total_gold_1_trend', 'total_gold_2', 'total_gold_2_trend', 'total_gold_3', 'total_gold_3_trend', 'total_gold_4', 'total_gold_4_trend', 'total_gold_5', 'total_gold_5_trend', 'total_gold_6', 'total_gold_6_trend', 'total_gold_7', 'total_gold_7_trend'], 'required'],
            [['schedule_id', 'updates_nums', 'total_gold_0_trend', 'total_gold_1_trend', 'total_gold_2_trend', 'total_gold_3_trend', 'total_gold_4_trend', 'total_gold_5_trend', 'total_gold_6_trend', 'total_gold_7_trend', 'opt_id'], 'integer'],
            [['total_gold_0', 'total_gold_1', 'total_gold_2', 'total_gold_3', 'total_gold_4', 'total_gold_5', 'total_gold_6', 'total_gold_7'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_3008_id' => 'Odds 3008 ID',
            'schedule_id' => 'Schedule ID',
            'schedule_mid' => 'Schedule Mid',
            'updates_nums' => 'Updates Nums',
            'total_gold_0' => 'Total Gold 0',
            'total_gold_0_trend' => 'Total Gold 0 Trend',
            'total_gold_1' => 'Total Gold 1',
            'total_gold_1_trend' => 'Total Gold 1 Trend',
            'total_gold_2' => 'Total Gold 2',
            'total_gold_2_trend' => 'Total Gold 2 Trend',
            'total_gold_3' => 'Total Gold 3',
            'total_gold_3_trend' => 'Total Gold 3 Trend',
            'total_gold_4' => 'Total Gold 4',
            'total_gold_4_trend' => 'Total Gold 4 Trend',
            'total_gold_5' => 'Total Gold 5',
            'total_gold_5_trend' => 'Total Gold 5 Trend',
            'total_gold_6' => 'Total Gold 6',
            'total_gold_6_trend' => 'Total Gold 6 Trend',
            'total_gold_7' => 'Total Gold 7',
            'total_gold_7_trend' => 'Total Gold 7 Trend',
            'opt_id' => 'Opt ID',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
