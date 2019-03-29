<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "optional_schedule".
 *
 * @property integer $optional_schedule_id
 * @property integer $sorting_code
 * @property string $periods
 * @property string $league_name
 * @property string $schedule_mid
 * @property string $start_time
 * @property string $home_short_name
 * @property string $visit_short_name
 * @property double $odds_win
 * @property double $odds_flat
 * @property double $odds_lose
 * @property integer $schedule_result
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class OptionalSchedule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'optional_schedule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sorting_code', 'periods'], 'required'],
            [['sorting_code', 'schedule_result'], 'integer'],
            [['odds_win', 'odds_flat', 'odds_lose'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['periods', 'start_time'], 'string', 'max' => 50],
            [['league_name', 'home_short_name', 'visit_short_name'], 'string', 'max' => 100],
            [['schedule_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'optional_schedule_id' => 'Optional Schedule ID',
            'sorting_code' => 'Sorting Code',
            'periods' => 'Periods',
            'league_name' => 'League Name',
            'schedule_mid' => 'Schedule Mid',
            'start_time' => 'Start Time',
            'home_short_name' => 'Home Short Name',
            'visit_short_name' => 'Visit Short Name',
            'odds_win' => 'Odds Win',
            'odds_flat' => 'Odds Flat',
            'odds_lose' => 'Odds Lose',
            'schedule_result' => 'Schedule Result',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
