<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_3003".
 *
 * @property integer $odds_3003_id
 * @property string $schedule_mid
 * @property integer $update_nums
 * @property string $cha_01
 * @property integer $cha_01_trend
 * @property string $cha_02
 * @property integer $cha_02_trend
 * @property string $cha_03
 * @property integer $cha_03_trend
 * @property string $cha_04
 * @property integer $cha_04_trend
 * @property string $cha_05
 * @property integer $cha_05_trend
 * @property string $cha_06
 * @property integer $cha_06_trend
 * @property string $cha_11
 * @property integer $cha_11_trend
 * @property string $cha_12
 * @property integer $cha_12_trend
 * @property string $cha_13
 * @property integer $cha_13_trend
 * @property string $cha_14
 * @property integer $cha_14_trend
 * @property string $cha_15
 * @property integer $cha_15_trend
 * @property string $cha_16
 * @property integer $cha_16_trend
 * @property integer $opt_id
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds3003 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3003';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_mid', 'cha_01', 'cha_02', 'cha_03', 'cha_04', 'cha_05', 'cha_06', 'cha_11', 'cha_12', 'cha_13', 'cha_14', 'cha_15', 'cha_16'], 'required'],
            [['update_nums', 'cha_01_trend', 'cha_02_trend', 'cha_03_trend', 'cha_04_trend', 'cha_05_trend', 'cha_06_trend', 'cha_11_trend', 'cha_12_trend', 'cha_13_trend', 'cha_14_trend', 'cha_15_trend', 'cha_16_trend', 'opt_id'], 'integer'],
            [['cha_01', 'cha_02', 'cha_03', 'cha_04', 'cha_05', 'cha_06', 'cha_11', 'cha_12', 'cha_13', 'cha_14', 'cha_15', 'cha_16'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_3003_id' => 'Odds 3003 ID',
            'schedule_mid' => 'Schedule Mid',
            'update_nums' => 'Update Nums',
            'cha_01' => 'Cha 01',
            'cha_01_trend' => 'Cha 01 Trend',
            'cha_02' => 'Cha 02',
            'cha_02_trend' => 'Cha 02 Trend',
            'cha_03' => 'Cha 03',
            'cha_03_trend' => 'Cha 03 Trend',
            'cha_04' => 'Cha 04',
            'cha_04_trend' => 'Cha 04 Trend',
            'cha_05' => 'Cha 05',
            'cha_05_trend' => 'Cha 05 Trend',
            'cha_06' => 'Cha 06',
            'cha_06_trend' => 'Cha 06 Trend',
            'cha_11' => 'Cha 11',
            'cha_11_trend' => 'Cha 11 Trend',
            'cha_12' => 'Cha 12',
            'cha_12_trend' => 'Cha 12 Trend',
            'cha_13' => 'Cha 13',
            'cha_13_trend' => 'Cha 13 Trend',
            'cha_14' => 'Cha 14',
            'cha_14_trend' => 'Cha 14 Trend',
            'cha_15' => 'Cha 15',
            'cha_15_trend' => 'Cha 15 Trend',
            'cha_16' => 'Cha 16',
            'cha_16_trend' => 'Cha 16 Trend',
            'opt_id' => 'Opt ID',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
