<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "deal_detail".
 *
 * @property integer $deal_detail_id
 * @property integer $deal_order_id
 * @property string $lottery_code
 * @property string $bet_val
 * @property string $odds
 * @property string $fen_json
 * @property integer $schedule_nums
 * @property integer $deal_nums
 * @property string $deal_schedule
 * @property string $deal_odds_sche
 * @property integer $status
 * @property integer $deal_status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class DealDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'deal_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['deal_order_id', 'lottery_code', 'bet_val'], 'required'],
            [['deal_order_id', 'schedule_nums', 'deal_nums', 'status', 'deal_status'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['lottery_code'], 'string', 'max' => 50],
            [['bet_val', 'deal_schedule', 'deal_odds_sche'], 'string', 'max' => 255],
            [['odds'], 'string', 'max' => 100],
            [['fen_json'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'deal_detail_id' => 'Deal Detail ID',
            'deal_order_id' => 'Deal Order ID',
            'lottery_code' => 'Lottery Code',
            'bet_val' => 'Bet Val',
            'odds' => 'Odds',
            'fen_json' => 'Fen Json',
            'schedule_nums' => 'Schedule Nums',
            'deal_nums' => 'Deal Nums',
            'deal_schedule' => 'Deal Schedule',
            'deal_odds_sche' => 'Deal Odds Sche',
            'status' => 'Status',
            'deal_status' => 'Deal Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
