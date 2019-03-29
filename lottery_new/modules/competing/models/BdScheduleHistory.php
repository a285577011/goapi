<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_schedule_history".
 *
 * @property integer $schedule_history_id
 * @property string $schedule_mid
 * @property string $league_code
 * @property string $league_name
 * @property string $play_time
 * @property string $home_team_mid
 * @property string $home_team_name
 * @property string $visit_team_mid
 * @property string $visit_team_name
 * @property string $result_3007
 * @property string $result_3009_b
 * @property string $result_3010
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class BdScheduleHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_schedule_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['play_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid', 'league_code', 'home_team_mid', 'visit_team_mid'], 'string', 'max' => 11],
            [['league_name', 'home_team_name', 'visit_team_name', 'result_3007', 'result_3009_b', 'result_3010'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'schedule_history_id' => 'Schedule History ID',
            'schedule_mid' => 'Schedule Mid',
            'league_code' => 'League Code',
            'league_name' => 'League Name',
            'play_time' => 'Play Time',
            'home_team_mid' => 'Home Team Mid',
            'home_team_name' => 'Home Team Name',
            'visit_team_mid' => 'Visit Team Mid',
            'visit_team_name' => 'Visit Team Name',
            'result_3007' => 'Result 3007',
            'result_3009_b' => 'Result 3009 B',
            'result_3010' => 'Result 3010',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
