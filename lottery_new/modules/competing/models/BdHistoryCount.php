<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_history_count".
 *
 * @property integer $history_count_id
 * @property string $schedule_mid
 * @property integer $double_play_num
 * @property integer $num3
 * @property integer $num1
 * @property integer $num0
 * @property integer $home_num_3
 * @property integer $home_num_1
 * @property integer $home_num_0
 * @property integer $visit_num_3
 * @property integer $visit_num_1
 * @property integer $visit_num_0
 * @property string $home_team_rank
 * @property string $visit_team_rank
 * @property string $home_team_league
 * @property string $visit_team_league
 * @property string $scale_3010_3
 * @property string $scale_3010_1
 * @property string $scale_3010_0
 * @property string $scale_3006_3
 * @property string $scale_3006_1
 * @property string $scale_3006_0
 * @property string $europe_odds_3
 * @property string $europe_odds_1
 * @property string $europe_odds_0
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class BdHistoryCount extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_history_count';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['double_play_num', 'num3', 'num1', 'num0', 'home_num_3', 'home_num_1', 'home_num_0', 'visit_num_3', 'visit_num_1', 'visit_num_0'], 'integer'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid', 'home_team_rank', 'visit_team_rank'], 'string', 'max' => 11],
            [['home_team_league', 'visit_team_league'], 'string', 'max' => 50],
            [['scale_3010_3', 'scale_3010_1', 'scale_3010_0', 'scale_3006_3', 'scale_3006_1', 'scale_3006_0', 'europe_odds_3', 'europe_odds_1', 'europe_odds_0'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'history_count_id' => 'History Count ID',
            'schedule_mid' => 'Schedule Mid',
            'double_play_num' => 'Double Play Num',
            'num3' => 'Num3',
            'num1' => 'Num1',
            'num0' => 'Num0',
            'home_num_3' => 'Home Num 3',
            'home_num_1' => 'Home Num 1',
            'home_num_0' => 'Home Num 0',
            'visit_num_3' => 'Visit Num 3',
            'visit_num_1' => 'Visit Num 1',
            'visit_num_0' => 'Visit Num 0',
            'home_team_rank' => 'Home Team Rank',
            'visit_team_rank' => 'Visit Team Rank',
            'home_team_league' => 'Home Team League',
            'visit_team_league' => 'Visit Team League',
            'scale_3010_3' => 'Scale 3010 3',
            'scale_3010_1' => 'Scale 3010 1',
            'scale_3010_0' => 'Scale 3010 0',
            'scale_3006_3' => 'Scale 3006 3',
            'scale_3006_1' => 'Scale 3006 1',
            'scale_3006_0' => 'Scale 3006 0',
            'europe_odds_3' => 'Europe Odds 3',
            'europe_odds_1' => 'Europe Odds 1',
            'europe_odds_0' => 'Europe Odds 0',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
