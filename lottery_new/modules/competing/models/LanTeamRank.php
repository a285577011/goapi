<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "lan_team_rank".
 *
 * @property integer $team_rank_id
 * @property string $team_code
 * @property string $team_name
 * @property string $league_code
 * @property string $league_name
 * @property integer $team_position
 * @property integer $team_rank
 * @property integer $game_nums
 * @property integer $win_nums
 * @property integer $lose_nums
 * @property double $win_rate
 * @property double $wins_diff
 * @property double $defen_nums
 * @property double $shifen_nums
 * @property string $home_result
 * @property string $visit_result
 * @property string $east_result
 * @property string $west_result
 * @property string $same_result
 * @property string $ten_result
 * @property string $near_result
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class LanTeamRank extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lan_team_rank';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['team_position', 'team_rank', 'game_nums', 'win_nums', 'lose_nums'], 'integer'],
            [['win_rate', 'wins_diff', 'defen_nums', 'shifen_nums'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['team_code', 'league_code'], 'string', 'max' => 25],
            [['team_name', 'league_name'], 'string', 'max' => 100],
            [['home_result', 'visit_result', 'east_result', 'west_result', 'same_result', 'ten_result', 'near_result'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'team_rank_id' => 'Team Rank ID',
            'team_code' => 'Team Code',
            'team_name' => 'Team Name',
            'league_code' => 'League Code',
            'league_name' => 'League Name',
            'team_position' => 'Team Position',
            'team_rank' => 'Team Rank',
            'game_nums' => 'Game Nums',
            'win_nums' => 'Win Nums',
            'lose_nums' => 'Lose Nums',
            'win_rate' => 'Win Rate',
            'wins_diff' => 'Wins Diff',
            'defen_nums' => 'Defen Nums',
            'shifen_nums' => 'Shifen Nums',
            'home_result' => 'Home Result',
            'visit_result' => 'Visit Result',
            'east_result' => 'East Result',
            'west_result' => 'West Result',
            'same_result' => 'Same Result',
            'ten_result' => 'Ten Result',
            'near_result' => 'Near Result',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
