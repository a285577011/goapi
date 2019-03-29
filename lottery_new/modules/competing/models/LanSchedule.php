<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "lan_schedule".
 *
 * @property integer $lan_schedule_id
 * @property string $schedule_code
 * @property integer $schedule_date
 * @property string $schedule_mid
 * @property integer $league_id
 * @property string $league_name
 * @property string $visit_short_name
 * @property string $home_short_name
 * @property integer $visit_team_id
 * @property integer $home_team_id
 * @property string $start_time
 * @property string $beginsale_time
 * @property string $endsale_time
 * @property integer $schedule_status
 * @property integer $schedule_sf
 * @property integer $schedule_rfsf
 * @property integer $schedule_dxf
 * @property integer $schedule_sfc
 * @property integer $high_win_status
 * @property integer $hot_status
 * @property integer $opt_id
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class LanSchedule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lan_schedule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_date', 'league_id', 'visit_team_id', 'home_team_id', 'schedule_status', 'schedule_sf', 'schedule_rfsf', 'schedule_dxf', 'schedule_sfc', 'high_win_status', 'hot_status', 'opt_id'], 'integer'],
            [['schedule_mid', 'league_id', 'visit_short_name', 'home_short_name', 'visit_team_id', 'home_team_id', 'start_time', 'beginsale_time', 'endsale_time'], 'required'],
            [['start_time', 'beginsale_time', 'endsale_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['schedule_code', 'schedule_mid'], 'string', 'max' => 25],
            [['league_name', 'visit_short_name', 'home_short_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'lan_schedule_id' => 'Lan Schedule ID',
            'schedule_code' => 'Schedule Code',
            'schedule_date' => 'Schedule Date',
            'schedule_mid' => 'Schedule Mid',
            'league_id' => 'League ID',
            'league_name' => 'League Name',
            'visit_short_name' => 'Visit Short Name',
            'home_short_name' => 'Home Short Name',
            'visit_team_id' => 'Visit Team ID',
            'home_team_id' => 'Home Team ID',
            'start_time' => 'Start Time',
            'beginsale_time' => 'Beginsale Time',
            'endsale_time' => 'Endsale Time',
            'schedule_status' => 'Schedule Status',
            'schedule_sf' => 'Schedule Sf',
            'schedule_rfsf' => 'Schedule Rfsf',
            'schedule_dxf' => 'Schedule Dxf',
            'schedule_sfc' => 'Schedule Sfc',
            'high_win_status' => 'High Win Status',
            'hot_status' => 'Hot Status',
            'opt_id' => 'Opt ID',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
    
    public function getOdds3001()
    {
        return $this->hasOne(Odds3001::className(), ['schedule_mid' => 'schedule_mid'])
        ->orderBy('odds_3001_id desc');
    }
    public function getOdds3002()
    {
        return $this->hasOne(Odds3002::className(), ['schedule_mid' => 'schedule_mid'])
        ->orderBy('odds_3002_id desc');
    }
    public function getOdds3003()
    {
        return $this->hasOne(Odds3003::className(), ['schedule_mid' => 'schedule_mid'])
        ->orderBy('odds_3003_id desc');
    }
    public function getOdds3004()
    {
        return $this->hasOne(Odds3004::className(), ['schedule_mid' => 'schedule_mid'])
        ->orderBy('odds_3004_id desc');
    }
}
