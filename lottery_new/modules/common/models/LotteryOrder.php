<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "lottery_order".
 *
 * @property string $lottery_order_id
 * @property integer $lottery_additional_id
 * @property string $lottery_name
 * @property string $lottery_order_code
 * @property string $play_name
 * @property string $play_code
 * @property integer $lottery_id
 * @property integer $lottery_type
 * @property string $periods
 * @property string $cust_no
 * @property integer $cust_type
 * @property integer $user_id
 * @property integer $store_id
 * @property string $store_no
 * @property string $agent_id
 * @property string $end_time
 * @property string $programme_code
 * @property string $bet_val
 * @property integer $additional_periods
 * @property integer $chased_num
 * @property integer $bet_double
 * @property integer $is_bet_add
 * @property string $bet_money
 * @property string $odds
 * @property integer $count
 * @property string $win_amount
 * @property string $award_amount
 * @property integer $deal_status
 * @property integer $status
 * @property string $refuse_reason
 * @property integer $record_type
 * @property integer $source
 * @property integer $suborder_status
 * @property string $opt_id
 * @property string $remark
 * @property string $modify_time
 * @property string $create_time
 * @property string $out_time
 * @property string $update_time
 * @property string $award_time
 * @property integer $source_id
 * @property integer $send_status
 * @property string $build_code
 * @property string $build_name
 * @property integer $major_type
 * @property integer $auto_type
 */
class LotteryOrder extends \yii\db\ActiveRecord
{
	use SyncCommon;
    const FIELD = [
        'user.user_remark as manager_no',//所属业务经理标示
        'lottery_order_code',//彩票编号
        'lottery_name',//彩种名称
        'play_name',//玩法名称
        'lottery_type',//彩种类型 1：数字彩 2：足竞 3：胜负彩 4：篮竞
        'periods',//期数
        'lottery_order.cust_no',//用户咕啦编号
        'bet_val',//投注内容
        'bet_double',//倍数
        'is_bet_add',//是否追加（大乐透）
        'bet_money',//投注总金额
        'count',//总注数
        'end_time',//投注截止时间
        'win_amount',//中奖金额
        'award_amount',//派奖金额
        'deal_status',//兑奖处理(0:未处理、1:已兑奖、2:派奖失败、3:派奖成功、4:退款失败、5:退款成功)
        'lottery_order.status',//订单状态(3:待开奖、4:中奖、5:未中奖、6:出票失败、9:过点撤销、10:拒绝出票)
        'lottery_order.create_time',//投注时间
        'out_time',//出票时间
        'award_time'//派奖时间
    ];
    public static $successOrderStatus=[3,4,5];//交易成功订单状态
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lottery_order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_additional_id', 'lottery_id', 'lottery_type', 'cust_type', 'user_id', 'store_id', 'additional_periods', 'chased_num', 'bet_double', 'is_bet_add', 'count', 'deal_status', 'status', 'record_type', 'source', 'suborder_status', 'source_id', 'send_status', 'major_type', 'auto_type'], 'integer'],
            [['lottery_id'], 'required'],
            [['end_time', 'modify_time', 'create_time', 'out_time', 'update_time', 'award_time'], 'safe'],
            [['bet_money', 'win_amount', 'award_amount'], 'number'],
            [['lottery_name', 'lottery_order_code', 'build_name'], 'string', 'max' => 50],
            [['play_name'], 'string', 'max' => 1500],
            [['play_code'], 'string', 'max' => 700],
            [['periods', 'opt_id', 'build_code'], 'string', 'max' => 25],
            [['cust_no', 'store_no'], 'string', 'max' => 15],
            [['programme_code', 'remark'], 'string', 'max' => 100],
            [['bet_val', 'odds'], 'string', 'max' => 1000],
            [['refuse_reason'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'lottery_order_id' => 'Lottery Order ID',
            'lottery_additional_id' => 'Lottery Additional ID',
            'lottery_name' => 'Lottery Name',
            'lottery_order_code' => 'Lottery Order Code',
            'play_name' => 'Play Name',
            'play_code' => 'Play Code',
            'lottery_id' => 'Lottery ID',
            'lottery_type' => 'Lottery Type',
            'periods' => 'Periods',
            'cust_no' => 'Cust No',
            'cust_type' => 'Cust Type',
            'user_id' => 'User ID',
            'store_id' => 'Store ID',
            'store_no' => 'Store No',
            'agent_id' => 'Agent ID',
            'end_time' => 'End Time',
            'programme_code' => 'Programme Code',
            'bet_val' => 'Bet Val',
            'additional_periods' => 'Additional Periods',
            'chased_num' => 'Chased Num',
            'bet_double' => 'Bet Double',
            'is_bet_add' => 'Is Bet Add',
            'bet_money' => 'Bet Money',
            'odds' => 'Odds',
            'count' => 'Count',
            'win_amount' => 'Win Amount',
            'award_amount' => 'Award Amount',
            'deal_status' => 'Deal Status',
            'status' => 'Status',
            'refuse_reason' => 'Refuse Reason',
            'record_type' => 'Record Type',
            'source' => 'Source',
            'suborder_status' => 'Suborder Status',
            'opt_id' => 'Opt ID',
            'remark' => 'Remark',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'out_time' => 'Out Time',
            'update_time' => 'Update Time',
            'award_time' => 'Award Time',
            'source_id' => 'Source ID',
            'send_status' => 'Send Status',
            'build_code' => 'Build Code',
            'build_name' => 'Build Name',
            'major_type' => 'Major Type',
            'auto_type' => 'Auto Type',
        ];
    }

}
