<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "programme".
 *
 * @property integer $programme_id
 * @property string $programme_code
 * @property integer $programme_money
 * @property string $programme_title
 * @property integer $lottery_order_id
 * @property string $lottery_order_code
 * @property integer $store_id
 * @property string $expert_no
 * @property integer $cust_type
 * @property string $bet_val
 * @property string $bet_money
 * @property string $lottery_code
 * @property string $lottery_name
 * @property string $play_code
 * @property string $play_name
 * @property string $periods
 * @property integer $bet_double
 * @property integer $is_bet_add
 * @property integer $count
 * @property integer $security
 * @property double $royalty_ratio
 * @property integer $owner_buy_number
 * @property integer $minimum_guarantee
 * @property string $programme_start_time
 * @property string $programme_end_time
 * @property string $programme_reason
 * @property integer $programme_all_number
 * @property integer $programme_buy_number
 * @property integer $programme_peoples
 * @property integer $programme_speed
 * @property integer $programme_last_amount
 * @property string $win_amount
 * @property string $made_amount
 * @property integer $guarantee_status
 * @property integer $bet_status
 * @property integer $status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property integer $level_deal
 * @property integer $user_id
 * @property string $programme_univalent
 * @property integer $programme_last_number
 * @property integer $made_nums
 * @property integer $store_no
 */
class Programme extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'programme';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['programme_code', 'expert_no', 'programme_all_number', 'programme_last_amount'], 'required'],
            [['programme_money', 'lottery_order_id', 'store_id', 'cust_type', 'bet_double', 'is_bet_add', 'count', 'security', 'owner_buy_number', 'minimum_guarantee', 'programme_all_number', 'programme_buy_number', 'programme_peoples', 'programme_speed', 'programme_last_amount', 'guarantee_status', 'bet_status', 'status', 'level_deal', 'user_id', 'programme_last_number', 'made_nums', 'store_no'], 'integer'],
            [['bet_money', 'royalty_ratio', 'win_amount', 'made_amount', 'programme_univalent'], 'number'],
            [['programme_start_time', 'programme_end_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['programme_code', 'programme_title', 'lottery_order_code', 'lottery_name'], 'string', 'max' => 50],
            [['expert_no'], 'string', 'max' => 15],
            [['bet_val'], 'string', 'max' => 1000],
            [['lottery_code'], 'string', 'max' => 10],
            [['play_code'], 'string', 'max' => 700],
            [['play_name'], 'string', 'max' => 1500],
            [['periods'], 'string', 'max' => 20],
            [['programme_reason'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'programme_id' => 'Programme ID',
            'programme_code' => 'Programme Code',
            'programme_money' => 'Programme Money',
            'programme_title' => 'Programme Title',
            'lottery_order_id' => 'Lottery Order ID',
            'lottery_order_code' => 'Lottery Order Code',
            'store_id' => 'Store ID',
            'expert_no' => 'Expert No',
            'cust_type' => 'Cust Type',
            'bet_val' => 'Bet Val',
            'bet_money' => 'Bet Money',
            'lottery_code' => 'Lottery Code',
            'lottery_name' => 'Lottery Name',
            'play_code' => 'Play Code',
            'play_name' => 'Play Name',
            'periods' => 'Periods',
            'bet_double' => 'Bet Double',
            'is_bet_add' => 'Is Bet Add',
            'count' => 'Count',
            'security' => 'Security',
            'royalty_ratio' => 'Royalty Ratio',
            'owner_buy_number' => 'Owner Buy Number',
            'minimum_guarantee' => 'Minimum Guarantee',
            'programme_start_time' => 'Programme Start Time',
            'programme_end_time' => 'Programme End Time',
            'programme_reason' => 'Programme Reason',
            'programme_all_number' => 'Programme All Number',
            'programme_buy_number' => 'Programme Buy Number',
            'programme_peoples' => 'Programme Peoples',
            'programme_speed' => 'Programme Speed',
            'programme_last_amount' => 'Programme Last Amount',
            'win_amount' => 'Win Amount',
            'made_amount' => 'Made Amount',
            'guarantee_status' => 'Guarantee Status',
            'bet_status' => 'Bet Status',
            'status' => 'Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'level_deal' => 'Level Deal',
            'user_id' => 'User ID',
            'programme_univalent' => 'Programme Univalent',
            'programme_last_number' => 'Programme Last Number',
            'made_nums' => 'Made Nums',
            'store_no' => 'Store No',
        ];
    }
}
