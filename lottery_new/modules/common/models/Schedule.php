<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "schedule".
 *
 * @property integer $schedule_id
 * @property string $schedule_code
 * @property string $schedule_mid
 * @property integer $league_id
 * @property string $visit_team_name
 * @property string $home_team_name
 * @property string $visit_short_name
 * @property string $home_short_name
 * @property integer $home_team_id
 * @property integer $visit_team_id
 * @property string $start_time
 * @property string $beginsale_time
 * @property string $endsale_time
 * @property string $periods
 * @property string $rq_nums
 * @property string $schedule_result
 * @property string $url
 * @property integer $schedule_status
 * @property integer $schedule_spf
 * @property integer $schedule_rqspf
 * @property integer $schedule_bf
 * @property integer $schedule_zjqs
 * @property integer $schedule_bqcspf
 * @property integer $high_win_status
 * @property integer $hot_status
 * @property integer $is_optional
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property integer $opt_id
 */
class Schedule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'schedule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_code', 'schedule_mid', 'league_id', 'visit_team_name', 'home_team_name', 'visit_short_name', 'home_short_name', 'home_team_id', 'visit_team_id', 'start_time'], 'required'],
            [['league_id', 'home_team_id', 'visit_team_id', 'schedule_status', 'schedule_spf', 'schedule_rqspf', 'schedule_bf', 'schedule_zjqs', 'schedule_bqcspf', 'high_win_status', 'hot_status', 'is_optional', 'opt_id'], 'integer'],
            [['start_time', 'beginsale_time', 'endsale_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_code', 'schedule_mid', 'periods', 'rq_nums', 'schedule_result'], 'string', 'max' => 25],
            [['visit_team_name', 'home_team_name', 'visit_short_name', 'home_short_name', 'url'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'schedule_id' => 'Schedule ID',
            'schedule_code' => 'Schedule Code',
            'schedule_mid' => 'Schedule Mid',
            'league_id' => 'League ID',
            'visit_team_name' => 'Visit Team Name',
            'home_team_name' => 'Home Team Name',
            'visit_short_name' => 'Visit Short Name',
            'home_short_name' => 'Home Short Name',
            'home_team_id' => 'Home Team ID',
            'visit_team_id' => 'Visit Team ID',
            'start_time' => 'Start Time',
            'beginsale_time' => 'Beginsale Time',
            'endsale_time' => 'Endsale Time',
            'periods' => 'Periods',
            'rq_nums' => 'Rq Nums',
            'schedule_result' => 'Schedule Result',
            'url' => 'Url',
            'schedule_status' => 'Schedule Status',
            'schedule_spf' => 'Schedule Spf',
            'schedule_rqspf' => 'Schedule Rqspf',
            'schedule_bf' => 'Schedule Bf',
            'schedule_zjqs' => 'Schedule Zjqs',
            'schedule_bqcspf' => 'Schedule Bqcspf',
            'high_win_status' => 'High Win Status',
            'hot_status' => 'Hot Status',
            'is_optional' => 'Is Optional',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'opt_id' => 'Opt ID',
        ];
    }
    
    public function getOdds3010()
    {
        return $this->hasOne(Odds3010::className(), ['schedule_id' => 'schedule_id'])
            ->orderBy('odds_outcome_id desc');
    }
    public function getOdds3006()
    {
        return $this->hasOne(Odds3006::className(), ['schedule_id' => 'schedule_id'])
//         ->select('let_ball_nums', 'let_wins', 'let_level', 'let_negative')
        ->orderBy('odds_let_id desc');
    }
    public function getOdds3007()
    {
        return $this->hasOne(Odds3007::className(), ['schedule_id' => 'schedule_id'])
        ->orderBy('odds_score_id desc');
    }
    public function getOdds3008()
    {
        return $this->hasOne(Odds3008::className(), ['schedule_id' => 'schedule_id'])
        ->orderBy('odds_3008_id desc');
    }
    public function getOdds3009()
    {
        return $this->hasOne(Odds3009::className(), ['schedule_id' => 'schedule_id'])
        ->orderBy('odds_3009_id desc');
    }
    
}
