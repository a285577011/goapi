<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "lottery_time".
 *
 * @property integer $lottery_time_id
 * @property string $lottery_code
 * @property string $lottery_name
 * @property string $category_name
 * @property string $rate
 * @property integer $changci
 * @property string $week
 * @property string $start_time
 * @property string $stop_time
 * @property string $limit_time
 * @property string $remark
 * @property string $opt_id
 * @property string $modify_time
 * @property string $create_time
 */
class LotteryTime extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lottery_time';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lottery_code', 'lottery_name', 'category_name', 'start_time', 'stop_time'], 'required'],
            [['changci'], 'integer'],
            [['start_time', 'stop_time', 'limit_time', 'modify_time', 'create_time'], 'safe'],
            [['lottery_code'], 'string', 'max' => 10],
            [['lottery_name', 'category_name', 'week', 'opt_id'], 'string', 'max' => 25],
            [['rate'], 'string', 'max' => 100],
            [['remark'], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'lottery_time_id' => 'Lottery Time ID',
            'lottery_code' => 'Lottery Code',
            'lottery_name' => 'Lottery Name',
            'category_name' => 'Category Name',
            'rate' => 'Rate',
            'changci' => 'Changci',
            'week' => 'Week',
            'start_time' => 'Start Time',
            'stop_time' => 'Stop Time',
            'limit_time' => 'Limit Time',
            'remark' => 'Remark',
            'opt_id' => 'Opt ID',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
        ];
    }
}
