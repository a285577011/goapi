<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "diy_follow".
 *
 * @property integer $diy_follow_id
 * @property string $expert_no
 * @property string $cust_no
 * @property string $lottery_codes
 * @property integer $follow_type
 * @property integer $follow_num
 * @property integer $buy_num
 * @property double $follow_percent
 * @property integer $max_bet_money
 * @property integer $stop_money
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property integer $bet_nums
 */
class DiyFollow extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'diy_follow';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['follow_type', 'follow_num'], 'required'],
            [['follow_type', 'follow_num', 'buy_num', 'max_bet_money', 'stop_money', 'bet_nums'], 'integer'],
            [['follow_percent'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['expert_no', 'cust_no'], 'string', 'max' => 15],
            [['lottery_codes'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'diy_follow_id' => 'Diy Follow ID',
            'expert_no' => 'Expert No',
            'cust_no' => 'Cust No',
            'lottery_codes' => 'Lottery Codes',
            'follow_type' => 'Follow Type',
            'follow_num' => 'Follow Num',
            'buy_num' => 'Buy Num',
            'follow_percent' => 'Follow Percent',
            'max_bet_money' => 'Max Bet Money',
            'stop_money' => 'Stop Money',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'bet_nums' => 'Bet Nums',
        ];
    }
}
