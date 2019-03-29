<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "lottery_record".
 *
 * @property integer $lottery_record_id
 * @property string $lottery_code
 * @property string $lottery_name
 * @property string $periods
 * @property string $lottery_time
 * @property string $limit_time
 * @property string $week
 * @property string $lottery_numbers
 * @property string $test_numbers
 * @property integer $status
 * @property integer $win_status
 * @property string $total_sales
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class LotteryRecord extends \yii\db\ActiveRecord {

    const STATUS_NEXT = 0; //下一期
    const STATUS_CURRENT = 1; //当期
    const STATUS_LAST = 2; //往期
    const STATUS_LAST_115 = 3; //往期
    const WIN_STATUS_UNWIN = 0; //未兑奖
    const WIN_STATUS_WINNING = 1; //兑奖中
    const WIN_STATUS_WON = 2; //详情单已兑奖
    const WIN_STATUS_ORDER_WON = 3; //订单已兑奖

    /**
     * @inheritdoc
     */

    public static function tableName() {
        return 'lottery_record';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['lottery_code', 'lottery_name', 'periods'], 'required'],
            [['lottery_time', 'limit_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['status', 'win_status'], 'integer'],
            [['total_sales'], 'number'],
            [['lottery_code'], 'string', 'max' => 50],
            [['lottery_name'], 'string', 'max' => 20],
//             [['periods'], 'string', 'max' => 15],
            [['week'], 'string', 'max' => 5],
            [['lottery_numbers', 'test_numbers'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'lottery_record_id' => 'Lottery Record ID',
            'lottery_code' => 'Lottery Code',
            'lottery_name' => 'Lottery Name',
            'periods' => 'Periods',
            'lottery_time' => 'Lottery Time',
            'limit_time' => 'Limit Time',
            'week' => 'Week',
            'lottery_numbers' => 'Lottery Numbers',
            'test_numbers' => 'Test Numbers',
            'status' => 'Status',
            'win_status' => 'Win Status',
            'total_sales' => 'Total Sales',
            'pool' => 'Pool',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

}
