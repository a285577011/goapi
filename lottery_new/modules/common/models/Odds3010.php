<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "odds_3010".
 *
 * @property integer $odds_outcome_id
 * @property integer $schedule_id
 * @property string $schedule_mid
 * @property integer $updates_nums
 * @property string $outcome_wins
 * @property integer $outcome_wins_trend
 * @property string $outcome_level
 * @property integer $outcome_level_trend
 * @property string $outcome_negative
 * @property integer $outcome_negative_trend
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class Odds3010 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3010';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_id', 'schedule_mid'], 'required'],
            [['schedule_id', 'updates_nums', 'outcome_wins_trend', 'outcome_level_trend', 'outcome_negative_trend'], 'integer'],
            [['outcome_wins', 'outcome_level', 'outcome_negative'], 'number'],
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
            'odds_outcome_id' => 'Odds Outcome ID',
            'schedule_id' => 'Schedule ID',
            'schedule_mid' => 'Schedule Mid',
            'updates_nums' => 'Updates Nums',
            'outcome_wins' => 'Outcome Wins',
            'outcome_wins_trend' => 'Outcome Wins Trend',
            'outcome_level' => 'Outcome Level',
            'outcome_level_trend' => 'Outcome Level Trend',
            'outcome_negative' => 'Outcome Negative',
            'outcome_negative_trend' => 'Outcome Negative Trend',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
