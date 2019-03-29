<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_schedule".
 *
 * @property integer $bd_schedule_id
 * @property integer $periods
 * @property string $schedule_date
 * @property string $open_mid
 * @property string $schedule_mid
 * @property integer $play_type
 * @property integer $schedule_type
 * @property integer $bd_sort
 * @property string $start_time
 * @property string $beginsale_time
 * @property string $endsale_time
 * @property integer $league_code
 * @property string $league_name
 * @property integer $home_code
 * @property string $home_name
 * @property integer $visit_code
 * @property string $visit_name
 * @property integer $spf_rq_nums
 * @property double $sfgg_rf_nums
 * @property integer $sale_status
 * @property integer $sfgg_status
 * @property integer $zjqs_status
 * @property integer $bqc_status
 * @property integer $spf_status
 * @property integer $sxpds_status
 * @property integer $dcbf_status
 * @property integer $xbcbf_status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class BdSchedule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_schedule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['periods', 'open_mid', 'bd_sort', 'start_time', 'beginsale_time', 'endsale_time', 'league_name', 'home_name', 'visit_name'], 'required'],
            [['periods', 'play_type', 'schedule_type', 'bd_sort', 'league_code', 'home_code', 'visit_code', 'spf_rq_nums', 'sale_status', 'sfgg_status', 'zjqs_status', 'bqc_status', 'spf_status', 'sxpds_status', 'dcbf_status', 'xbcbf_status'], 'integer'],
            [['schedule_date', 'start_time', 'beginsale_time', 'endsale_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['sfgg_rf_nums'], 'number'],
            [['open_mid', 'schedule_mid'], 'string', 'max' => 25],
            [['league_name', 'home_name', 'visit_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bd_schedule_id' => 'Bd Schedule ID',
            'periods' => 'Periods',
            'schedule_date' => 'Schedule Date',
            'open_mid' => 'Open Mid',
            'schedule_mid' => 'Schedule Mid',
            'play_type' => 'Play Type',
            'schedule_type' => 'Schedule Type',
            'bd_sort' => 'Bd Sort',
            'start_time' => 'Start Time',
            'beginsale_time' => 'Beginsale Time',
            'endsale_time' => 'Endsale Time',
            'league_code' => 'League Code',
            'league_name' => 'League Name',
            'home_code' => 'Home Code',
            'home_name' => 'Home Name',
            'visit_code' => 'Visit Code',
            'visit_name' => 'Visit Name',
            'spf_rq_nums' => 'Spf Rq Nums',
            'sfgg_rf_nums' => 'Sfgg Rf Nums',
            'sale_status' => 'Sale Status',
            'sfgg_status' => 'Sfgg Status',
            'zjqs_status' => 'Zjqs Status',
            'bqc_status' => 'Bqc Status',
            'spf_status' => 'Spf Status',
            'sxpds_status' => 'Sxpds Status',
            'dcbf_status' => 'Dcbf Status',
            'xbcbf_status' => 'Xbcbf Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
    
    public function getOdds5001() {
        return $this->hasOne(Odds5001::className(), ['open_mid' => 'open_mid']);
    }

    public function getOdds5002() {
        return $this->hasOne(Odds5002::className(), ['open_mid' => 'open_mid']);
    }

    public function getOdds5003() {
        return $this->hasOne(Odds5003::className(), ['open_mid' => 'open_mid']);
    }

    public function getOdds5004() {
        return $this->hasOne(Odds5004::className(), ['open_mid' => 'open_mid']);
    }
    
    public function getOdds5005() {
        return $this->hasOne(Odds5005::className(), ['open_mid' => 'open_mid']);
    }

    public function getOdds5006() {
        return $this->hasOne(Odds5006::className(), ['open_mid' => 'open_mid']);
    }
}
