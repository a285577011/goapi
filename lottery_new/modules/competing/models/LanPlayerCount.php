<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "lan_player_count".
 *
 * @property integer $player_count_id
 * @property integer $schedule_mid
 * @property integer $team_code
 * @property integer $player_code
 * @property string $player_name
 * @property string $play_time
 * @property string $shots_nums
 * @property integer $rebound_nums
 * @property integer $assist_nums
 * @property integer $foul_nums
 * @property integer $score
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class LanPlayerCount extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lan_player_count';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_mid', 'team_code', 'player_code', 'rebound_nums', 'assist_nums', 'foul_nums', 'score'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['player_name'], 'string', 'max' => 100],
            [['play_time'], 'string', 'max' => 20],
            [['shots_nums'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'player_count_id' => 'Player Count ID',
            'schedule_mid' => 'Schedule Mid',
            'team_code' => 'Team Code',
            'player_code' => 'Player Code',
            'player_name' => 'Player Name',
            'play_time' => 'Play Time',
            'shots_nums' => 'Shots Nums',
            'rebound_nums' => 'Rebound Nums',
            'assist_nums' => 'Assist Nums',
            'foul_nums' => 'Foul Nums',
            'score' => 'Score',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
