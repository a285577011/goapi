<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "odds_3009".
 *
 * @property integer $odds_3009_id
 * @property integer $schedule_id
 * @property string $schedule_mid
 * @property integer $updates_nums
 * @property string $bqc_33
 * @property integer $bqc_33_trend
 * @property string $bqc_31
 * @property integer $bqc_31_trend
 * @property string $bqc_30
 * @property integer $bqc_30_trend
 * @property string $bqc_13
 * @property integer $bqc_13_trend
 * @property string $bqc_11
 * @property integer $bqc_11_trend
 * @property string $bqc_10
 * @property integer $bqc_10_trend
 * @property string $bqc_03
 * @property integer $bqc_03_trend
 * @property string $bqc_01
 * @property integer $bqc_01_trend
 * @property string $bqc_00
 * @property integer $bqc_00_trend
 * @property integer $opt_id
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class Odds3009 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3009';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_id', 'schedule_mid', 'updates_nums', 'bqc_33', 'bqc_33_trend', 'bqc_31', 'bqc_31_trend', 'bqc_30', 'bqc_30_trend', 'bqc_13', 'bqc_13_trend', 'bqc_11', 'bqc_11_trend', 'bqc_10', 'bqc_10_trend', 'bqc_03', 'bqc_03_trend', 'bqc_01', 'bqc_01_trend', 'bqc_00', 'bqc_00_trend'], 'required'],
            [['schedule_id', 'updates_nums', 'bqc_33_trend', 'bqc_31_trend', 'bqc_30_trend', 'bqc_13_trend', 'bqc_11_trend', 'bqc_10_trend', 'bqc_03_trend', 'bqc_01_trend', 'bqc_00_trend', 'opt_id'], 'integer'],
            [['bqc_33', 'bqc_31', 'bqc_30', 'bqc_13', 'bqc_11', 'bqc_10', 'bqc_03', 'bqc_01', 'bqc_00'], 'number'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['schedule_mid'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'odds_3009_id' => 'Odds 3009 ID',
            'schedule_id' => 'Schedule ID',
            'schedule_mid' => 'Schedule Mid',
            'updates_nums' => 'Updates Nums',
            'bqc_33' => 'Bqc 33',
            'bqc_33_trend' => 'Bqc 33 Trend',
            'bqc_31' => 'Bqc 31',
            'bqc_31_trend' => 'Bqc 31 Trend',
            'bqc_30' => 'Bqc 30',
            'bqc_30_trend' => 'Bqc 30 Trend',
            'bqc_13' => 'Bqc 13',
            'bqc_13_trend' => 'Bqc 13 Trend',
            'bqc_11' => 'Bqc 11',
            'bqc_11_trend' => 'Bqc 11 Trend',
            'bqc_10' => 'Bqc 10',
            'bqc_10_trend' => 'Bqc 10 Trend',
            'bqc_03' => 'Bqc 03',
            'bqc_03_trend' => 'Bqc 03 Trend',
            'bqc_01' => 'Bqc 01',
            'bqc_01_trend' => 'Bqc 01 Trend',
            'bqc_00' => 'Bqc 00',
            'bqc_00_trend' => 'Bqc 00 Trend',
            'opt_id' => 'Opt ID',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
