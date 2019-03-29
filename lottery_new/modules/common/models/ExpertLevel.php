<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "expert_level".
 *
 * @property integer $expert_level_id
 * @property integer $user_id
 * @property string $cust_no
 * @property integer $level
 * @property string $level_name
 * @property integer $value
 * @property integer $made_nums
 * @property integer $win_nums
 * @property integer $issue_nums
 * @property integer $succ_issue_nums
 * @property string $win_amount
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class ExpertLevel extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'expert_level';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'cust_no'], 'required'],
            [['user_id', 'level', 'value', 'made_nums', 'win_nums', 'issue_nums', 'succ_issue_nums'], 'integer'],
            [['win_amount'], 'number'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['cust_no'], 'string', 'max' => 15],
            [['level_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'expert_level_id' => 'Expert Level ID',
            'user_id' => 'User ID',
            'cust_no' => 'Cust No',
            'level' => 'Level',
            'level_name' => 'Level Name',
            'value' => 'Value',
            'made_nums' => 'Made Nums',
            'win_nums' => 'Win Nums',
            'issue_nums' => 'Issue Nums',
            'succ_issue_nums' => 'Succ Issue Nums',
            'win_amount' => 'Win Amount',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
