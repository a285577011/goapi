<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_schedule_result".
 *
 * @property integer $bd_schedule_result_id
 * @property integer $periods
 * @property integer $open_mid
 * @property integer $schedule_mid
 * @property integer $play_type
 * @property integer $bd_sort
 * @property integer $result_5001
 * @property string $odds_5001
 * @property integer $result_5002
 * @property string $odds_5002
 * @property string $result_5003
 * @property string $odds_5003
 * @property integer $result_5004
 * @property string $odds_5004
 * @property string $result_5005
 * @property string $odds_5005
 * @property integer $result_5006
 * @property string $odds_5006
 * @property string $result_bcbf
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class BdScheduleResult extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_schedule_result';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['periods', 'open_mid', 'bd_sort'], 'required'],
            [['periods', 'open_mid', 'schedule_mid', 'play_type', 'bd_sort', 'result_5001', 'result_5002', 'result_5004', 'result_5006', 'status'], 'integer'],
            [['odds_5001', 'odds_5002', 'odds_5003', 'odds_5004', 'odds_5005', 'odds_5006'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['result_5003', 'result_5005', 'result_bcbf'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bd_schedule_result_id' => 'Bd Schedule Result ID',
            'periods' => 'Periods',
            'open_mid' => 'Open Mid',
            'schedule_mid' => 'Schedule Mid',
            'play_type' => 'Play Type',
            'bd_sort' => 'Bd Sort',
            'result_5001' => 'Result 5001',
            'odds_5001' => 'Odds 5001',
            'result_5002' => 'Result 5002',
            'odds_5002' => 'Odds 5002',
            'result_5003' => 'Result 5003',
            'odds_5003' => 'Odds 5003',
            'result_5004' => 'Result 5004',
            'odds_5004' => 'Odds 5004',
            'result_5005' => 'Result 5005',
            'odds_5005' => 'Odds 5005',
            'result_5006' => 'Result 5006',
            'odds_5006' => 'Odds 5006',
            'result_bcbf' => 'Result Bcbf',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
