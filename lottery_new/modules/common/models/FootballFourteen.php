<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "football_fourteen".
 *
 * @property integer $football_fourteen_id
 * @property string $periods
 * @property string $schedule_mids
 * @property string $beginsale_time
 * @property string $endsale_time
 * @property string $schedule_results
 * @property string $first_prize
 * @property string $second_prize
 * @property integer $status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property string $nine_prize
 * @property integer $win_status
 */
class FootballFourteen extends \yii\db\ActiveRecord
{
    
    const STATUS_NEXT = 0; //下一期
    const STATUS_CURRENT = 1; //当期
    const STATUS_LAST = 2; //往期
    const STATUS_HAS_PZIRE = 3; //往期并获取
    const WIN_STATUS_UNWIN = 0; //未兑奖
    const WIN_STATUS_WINNING = 1; //兑奖中
    const WIN_STATUS_WON = 2; //详情单已兑奖
    const WIN_STATUS_ORDER_WON = 3; //订单已兑奖
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'football_fourteen';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['beginsale_time', 'endsale_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['first_prize', 'second_prize', 'nine_prize'], 'number'],
            [['status', 'win_status'], 'integer'],
            [['periods'], 'string', 'max' => 15],
            [['schedule_mids', 'schedule_results'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'football_fourteen_id' => 'Football Fourteen ID',
            'periods' => 'Periods',
            'schedule_mids' => 'Schedule Mids',
            'beginsale_time' => 'Beginsale Time',
            'endsale_time' => 'Endsale Time',
            'schedule_results' => 'Schedule Results',
            'first_prize' => 'First Prize',
            'second_prize' => 'Second Prize',
            'status' => 'Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'nine_prize' => 'Nine Prize',
            'win_status' => 'Win Status',
        ];
    }
}
