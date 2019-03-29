<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "weight_lottery_out".
 *
 * @property integer $weight_lottery_out_id
 * @property string $lottery_code
 * @property string $out_code
 * @property integer $weight
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class WeightLotteryOut extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'weight_lottery_out';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['weight'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['lottery_code'], 'string', 'max' => 25],
            [['out_code'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'weight_lottery_out_id' => 'Weight Lottery Out ID',
            'lottery_code' => 'Lottery Code',
            'out_code' => 'Out Code',
            'weight' => 'Weight',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
