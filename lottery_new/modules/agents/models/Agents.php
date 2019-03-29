<?php

namespace app\modules\agents\models;

use Yii;

/**
 * This is the model class for table "agents".
 *
 * @property integer $agents_id
 * @property string $agents_appid
 * @property string $secret_key
 * @property string $agents_account
 * @property string $agents_name
 * @property string $agents_code
 * @property string $upagents_code
 * @property string $upagents_name
 * @property integer $to_url
 * @property integer $agents_type
 * @property integer $pass_status
 * @property integer $use_status
 * @property string $create_time
 * @property string $check_time
 * @property string $update_time
 * @property string $opt_id
 * @property string $agents_remark
 * @property string $review_remark
 */
class Agents extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'agents';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['agents_appid', 'secret_key', 'agents_type'], 'required'],
            [['agents_type', 'pass_status', 'use_status'], 'integer'],
            [['create_time', 'check_time', 'update_time'], 'safe'],
            [['agents_appid'], 'string', 'max' => 16],
            [['secret_key'], 'string', 'max' => 32],
            [['agents_account', 'upagents_code'], 'string', 'max' => 20],
            [['agents_name', 'upagents_name', 'agents_remark', 'review_remark'], 'string', 'max' => 100],
            [['opt_id'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'agents_id' => 'Agents ID',
            'agents_appid' => 'Agents Appid',
            'secret_key' => 'Secret Key',
            'agents_account' => 'Agents Account',
            'agents_name' => 'Agents Name',
            'agents_code' => 'Agents Code',
            'upagents_code' => 'Upagents Code',
            'upagents_name' => 'Upagents Name',
            'to_url' => 'To Url',
            'agents_type' => 'Agents Type',
            'pass_status' => 'Pass Status',
            'use_status' => 'Use Status',
            'create_time' => 'Create Time',
            'check_time' => 'Check Time',
            'update_time' => 'Update Time',
            'opt_id' => 'Opt ID',
            'agents_remark' => 'Agents Remark',
            'review_remark' => 'Review Remark',
        ];
    }
}
