<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "lan_schedule_result".
 *
 * @property integer $lan_schedule_result_id
 * @property string $schedule_mid
 * @property integer $schedule_date
 * @property string $guest_one
 * @property string $guest_two
 * @property string $guest_three
 * @property string $guest_four
 * @property string $guest_add_one
 * @property string $guest_add_two
 * @property string $guest_add_three
 * @property string $guest_add_four
 * @property integer $result_3001
 * @property integer $result_3002
 * @property string $result_3003
 * @property integer $result_3004
 * @property string $odds_3001
 * @property string $odds_3002
 * @property string $odds_3003
 * @property string $odds_3004
 * @property integer $opt_id
 * @property string $match_time
 * @property integer $schedule_fc
 * @property integer $schedule_zf
 * @property string $result_zcbf
 * @property string $result_qcbf
 * @property double $result_rf
 * @property integer $result_status
 * @property integer $deal_status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class LanScheduleResult extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lan_schedule_result';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_mid', 'schedule_date'], 'required'],
            [['schedule_date', 'result_3001', 'result_3002', 'result_3004', 'opt_id', 'schedule_fc', 'schedule_zf', 'result_status', 'deal_status'], 'integer'],
            [['odds_3001', 'odds_3002', 'odds_3003', 'odds_3004', 'result_rf'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['schedule_mid', 'guest_one', 'guest_two', 'guest_three', 'guest_four', 'guest_add_one', 'guest_add_two', 'guest_add_three', 'guest_add_four', 'result_3003', 'result_zcbf', 'result_qcbf'], 'string', 'max' => 25],
            [['match_time'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'lan_schedule_result_id' => 'Lan Schedule Result ID',
            'schedule_mid' => 'Schedule Mid',
            'schedule_date' => 'Schedule Date',
            'guest_one' => 'Guest One',
            'guest_two' => 'Guest Two',
            'guest_three' => 'Guest Three',
            'guest_four' => 'Guest Four',
            'guest_add_one' => 'Guest Add One',
            'guest_add_two' => 'Guest Add Two',
            'guest_add_three' => 'Guest Add Three',
            'guest_add_four' => 'Guest Add Four',
            'result_3001' => 'Result 3001',
            'result_3002' => 'Result 3002',
            'result_3003' => 'Result 3003',
            'result_3004' => 'Result 3004',
            'odds_3001' => 'Odds 3001',
            'odds_3002' => 'Odds 3002',
            'odds_3003' => 'Odds 3003',
            'odds_3004' => 'Odds 3004',
            'opt_id' => 'Opt ID',
            'match_time' => 'Match Time',
            'schedule_fc' => 'Schedule Fc',
            'schedule_zf' => 'Schedule Zf',
            'result_zcbf' => 'Result Zcbf',
            'result_qcbf' => 'Result Qcbf',
            'result_rf' => 'Result Rf',
            'result_status' => 'Result Status',
            'deal_status' => 'Deal Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
