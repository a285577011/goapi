<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_5005".
 *
 * @property integer $odds_5005_id
 * @property string $open_mid
 * @property integer $update_nums
 * @property string $odds_10
 * @property integer $trend_10
 * @property string $odds_20
 * @property integer $trend_20
 * @property string $odds_21
 * @property integer $trend_21
 * @property string $odds_30
 * @property integer $trend_30
 * @property string $odds_31
 * @property integer $trend_31
 * @property string $odds_32
 * @property integer $trend_32
 * @property string $odds_40
 * @property integer $trend_40
 * @property string $odds_41
 * @property integer $trend_41
 * @property string $odds_42
 * @property integer $trend_42
 * @property string $odds_00
 * @property integer $trend_00
 * @property string $odds_11
 * @property integer $trend_11
 * @property string $odds_22
 * @property integer $trend_22
 * @property string $odds_33
 * @property integer $trend_33
 * @property string $odds_01
 * @property integer $trend_01
 * @property string $odds_02
 * @property integer $trend_02
 * @property string $odds_12
 * @property integer $trend_12
 * @property string $odds_03
 * @property integer $trend_03
 * @property string $odds_13
 * @property integer $trend_13
 * @property string $odds_23
 * @property integer $trend_23
 * @property string $odds_04
 * @property integer $trend_04
 * @property string $odds_14
 * @property integer $trend_14
 * @property string $odds_24
 * @property integer $trend_24
 * @property string $odds_90
 * @property integer $trend_90
 * @property string $odds_99
 * @property integer $trend_99
 * @property string $odds_09
 * @property integer $trend_09
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds5005 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_5005';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['update_nums', 'trend_10', 'trend_20', 'trend_21', 'trend_30', 'trend_31', 'trend_32', 'trend_40', 'trend_41', 'trend_42', 'trend_00', 'trend_11', 'trend_22', 'trend_33', 'trend_01', 'trend_02', 'trend_12', 'trend_03', 'trend_13', 'trend_23', 'trend_04', 'trend_14', 'trend_24', 'trend_90', 'trend_99', 'trend_09'], 'integer'],
            [['odds_10', 'odds_20', 'odds_21', 'odds_30', 'odds_31', 'odds_32', 'odds_40', 'odds_41', 'odds_42', 'odds_00', 'odds_11', 'odds_22', 'odds_33', 'odds_01', 'odds_02', 'odds_12', 'odds_03', 'odds_13', 'odds_23', 'odds_04', 'odds_14', 'odds_24', 'odds_90', 'odds_99', 'odds_09'], 'number'],
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
            'odds_5005_id' => 'Odds 5005 ID',
            'open_mid' => 'Open Mid',
            'update_nums' => 'Update Nums',
            'odds_10' => 'Odds 10',
            'trend_10' => 'Trend 10',
            'odds_20' => 'Odds 20',
            'trend_20' => 'Trend 20',
            'odds_21' => 'Odds 21',
            'trend_21' => 'Trend 21',
            'odds_30' => 'Odds 30',
            'trend_30' => 'Trend 30',
            'odds_31' => 'Odds 31',
            'trend_31' => 'Trend 31',
            'odds_32' => 'Odds 32',
            'trend_32' => 'Trend 32',
            'odds_40' => 'Odds 40',
            'trend_40' => 'Trend 40',
            'odds_41' => 'Odds 41',
            'trend_41' => 'Trend 41',
            'odds_42' => 'Odds 42',
            'trend_42' => 'Trend 42',
            'odds_00' => 'Odds 00',
            'trend_00' => 'Trend 00',
            'odds_11' => 'Odds 11',
            'trend_11' => 'Trend 11',
            'odds_22' => 'Odds 22',
            'trend_22' => 'Trend 22',
            'odds_33' => 'Odds 33',
            'trend_33' => 'Trend 33',
            'odds_01' => 'Odds 01',
            'trend_01' => 'Trend 01',
            'odds_02' => 'Odds 02',
            'trend_02' => 'Trend 02',
            'odds_12' => 'Odds 12',
            'trend_12' => 'Trend 12',
            'odds_03' => 'Odds 03',
            'trend_03' => 'Trend 03',
            'odds_13' => 'Odds 13',
            'trend_13' => 'Trend 13',
            'odds_23' => 'Odds 23',
            'trend_23' => 'Trend 23',
            'odds_04' => 'Odds 04',
            'trend_04' => 'Trend 04',
            'odds_14' => 'Odds 14',
            'trend_14' => 'Trend 14',
            'odds_24' => 'Odds 24',
            'trend_24' => 'Trend 24',
            'odds_90' => 'Odds 90',
            'trend_90' => 'Trend 90',
            'odds_99' => 'Odds 99',
            'trend_99' => 'Trend 99',
            'odds_09' => 'Odds 09',
            'trend_09' => 'Trend 09',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
