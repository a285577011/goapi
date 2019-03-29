<?php

namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "user_deploy".
 *
 * @property integer $user_deploy_id
 * @property integer $user_id
 * @property string $deploy_lottery_id
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class UserDeploy extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_deploy';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'deploy_lottery_id'], 'required'],
            [['user_id'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['deploy_lottery_id'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_deploy_id' => 'User Deploy ID',
            'user_id' => 'User ID',
            'deploy_lottery_id' => 'Deploy Lottery ID',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
