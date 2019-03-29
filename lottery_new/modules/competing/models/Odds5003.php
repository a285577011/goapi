<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_5003".
 *
 * @property integer $odds_5003_id
 * @property string $open_mid
 * @property integer $update_nums
 * @property double $odds_00
 * @property integer $trend_00
 * @property string $odds_01
 * @property integer $trend_01
 * @property string $odds_03
 * @property integer $trend_03
 * @property string $odds_10
 * @property integer $trend_10
 * @property string $odds_11
 * @property integer $trend_11
 * @property string $odds_13
 * @property integer $trend_13
 * @property string $odds_30
 * @property integer $trend_30
 * @property string $odds_31
 * @property integer $trend_31
 * @property string $odds_33
 * @property integer $trend_33
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds5003 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_5003';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['update_nums', 'trend_00', 'trend_01', 'trend_03', 'trend_10', 'trend_11', 'trend_13', 'trend_30', 'trend_31', 'trend_33'], 'integer'],
            [['odds_00', 'odds_01', 'odds_03', 'odds_10', 'odds_11', 'odds_13', 'odds_30', 'odds_31', 'odds_33'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['open_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_5003_id' => 'Odds 5003 ID',
            'open_mid' => 'Open Mid',
            'update_nums' => 'Update Nums',
            'odds_00' => 'Odds 00',
            'trend_00' => 'Trend 00',
            'odds_01' => 'Odds 01',
            'trend_01' => 'Trend 01',
            'odds_03' => 'Odds 03',
            'trend_03' => 'Trend 03',
            'odds_10' => 'Odds 10',
            'trend_10' => 'Trend 10',
            'odds_11' => 'Odds 11',
            'trend_11' => 'Trend 11',
            'odds_13' => 'Odds 13',
            'trend_13' => 'Trend 13',
            'odds_30' => 'Odds 30',
            'trend_30' => 'Trend 30',
            'odds_31' => 'Odds 31',
            'trend_31' => 'Trend 31',
            'odds_33' => 'Odds 33',
            'trend_33' => 'Trend 33',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
