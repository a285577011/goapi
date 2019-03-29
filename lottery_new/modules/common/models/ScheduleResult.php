<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "schedule_result".
 *
 * @property integer $schedule_result_id
 * @property integer $schedule_id
 * @property string $schedule_mid
 * @property integer $schedule_date
 * @property integer $schedule_result_3010
 * @property string $schedule_result_3006
 * @property string $schedule_result_3007
 * @property string $schedule_result_3008
 * @property string $schedule_result_3009
 * @property string $schedule_result_sbbf
 * @property string $odds_3006
 * @property string $odds_3007
 * @property string $odds_3008
 * @property string $odds_3009
 * @property string $odds_3010
 * @property integer $opt_id
 * @property string $match_time
 * @property integer $status
 * @property integer $deal_status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class ScheduleResult extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'schedule_result';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_id', 'schedule_mid'], 'required'],
            [['schedule_id', 'schedule_date', 'schedule_result_3010', 'opt_id', 'status', 'deal_status'], 'integer'],
            [['odds_3006', 'odds_3007', 'odds_3008', 'odds_3009', 'odds_3010'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid', 'schedule_result_3006', 'schedule_result_3007', 'schedule_result_3009', 'schedule_result_sbbf'], 'string', 'max' => 25],
            [['schedule_result_3008'], 'string', 'max' => 11],
            [['match_time'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'schedule_result_id' => 'Schedule Result ID',
            'schedule_id' => 'Schedule ID',
            'schedule_mid' => 'Schedule Mid',
            'schedule_date' => 'Schedule Date',
            'schedule_result_3010' => 'Schedule Result 3010',
            'schedule_result_3006' => 'Schedule Result 3006',
            'schedule_result_3007' => 'Schedule Result 3007',
            'schedule_result_3008' => 'Schedule Result 3008',
            'schedule_result_3009' => 'Schedule Result 3009',
            'schedule_result_sbbf' => 'Schedule Result Sbbf',
            'odds_3006' => 'Odds 3006',
            'odds_3007' => 'Odds 3007',
            'odds_3008' => 'Odds 3008',
            'odds_3009' => 'Odds 3009',
            'odds_3010' => 'Odds 3010',
            'opt_id' => 'Opt ID',
            'match_time' => 'Match Time',
            'status' => 'Status',
            'deal_status' => 'Deal Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
