<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "odds_3004".
 *
 * @property integer $odds_3004_id
 * @property string $schedule_mid
 * @property integer $update_nums
 * @property string $fen_cutoff
 * @property string $da_3004
 * @property integer $da_3004_trend
 * @property string $xiao_3004
 * @property integer $xiao_3004_trend
 * @property integer $opt_id
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Odds3004 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'odds_3004';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['schedule_mid', 'fen_cutoff', 'da_3004', 'da_3004_trend', 'xiao_3004', 'xiao_3004_trend'], 'required'],
            [['update_nums', 'da_3004_trend', 'xiao_3004_trend', 'opt_id'], 'integer'],
            [['fen_cutoff', 'da_3004', 'xiao_3004'], 'number'],
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
            'odds_3004_id' => 'Odds 3004 ID',
            'schedule_mid' => 'Schedule Mid',
            'update_nums' => 'Update Nums',
            'fen_cutoff' => 'Fen Cutoff',
            'da_3004' => 'Da 3004',
            'da_3004_trend' => 'Da 3004 Trend',
            'xiao_3004' => 'Xiao 3004',
            'xiao_3004_trend' => 'Xiao 3004 Trend',
            'opt_id' => 'Opt ID',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
