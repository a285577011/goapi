<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "football_nine".
 *
 * @property integer $football_nine_id
 * @property string $periods
 * @property string $schedule_mids
 * @property string $beginsale_time
 * @property string $endsale_time
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class FootballNine extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'football_nine';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['beginsale_time', 'endsale_time', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['periods'], 'string', 'max' => 15],
            [['schedule_mids'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'football_nine_id' => 'Football Nine ID',
            'periods' => 'Periods',
            'schedule_mids' => 'Schedule Mids',
            'beginsale_time' => 'Beginsale Time',
            'endsale_time' => 'Endsale Time',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
