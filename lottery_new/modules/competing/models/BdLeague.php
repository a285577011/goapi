<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "bd_league".
 *
 * @property integer $league_id
 * @property string $league_code
 * @property integer $league_type
 * @property string $league_short_name
 * @property string $league_long_name
 * @property string $league_img
 * @property integer $league_category_id
 * @property string $league_remarks
 * @property integer $league_status
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property integer $opt_id
 */
class BdLeague extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bd_league';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['league_code', 'league_type', 'league_long_name'], 'required'],
            [['league_type', 'league_category_id', 'league_status', 'opt_id'], 'integer'],
            [['modify_time', 'create_time', 'update_time'], 'safe'],
            [['league_code', 'league_short_name'], 'string', 'max' => 25],
            [['league_long_name', 'league_img'], 'string', 'max' => 100],
            [['league_remarks'], 'string', 'max' => 500],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'league_id' => 'League ID',
            'league_code' => 'League Code',
            'league_type' => 'League Type',
            'league_short_name' => 'League Short Name',
            'league_long_name' => 'League Long Name',
            'league_img' => 'League Img',
            'league_category_id' => 'League Category ID',
            'league_remarks' => 'League Remarks',
            'league_status' => 'League Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'opt_id' => 'Opt ID',
        ];
    }
}
