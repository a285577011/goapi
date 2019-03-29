<?php

namespace app\modules\competing\models;

use Yii;

/**
 * This is the model class for table "match_notice".
 *
 * @property integer $match_notice_id
 * @property integer $match_type
 * @property string $notice_title
 * @property string $notice
 * @property string $notice_time
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class MatchNotice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'match_notice';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['match_type'], 'integer'],
            [['notice_title', 'notice'], 'required'],
            [['notice_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['notice_title'], 'string', 'max' => 150],
            [['notice'], 'string', 'max' => 1500],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'match_notice_id' => 'Match Notice ID',
            'match_type' => 'Match Type',
            'notice_title' => 'Notice Title',
            'notice' => 'Notice',
            'notice_time' => 'Notice Time',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
