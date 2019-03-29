<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "user_coin_cz_type".
 *
 * @property integer $coin_cz_type_id
 * @property string $cz_type
 * @property string $cz_type_name
 * @property string $cz_money
 * @property integer $cz_coin
 * @property integer $weal_type
 * @property string $weal_value
 * @property integer $weal_time
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class UserCoinCzType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_coin_cz_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cz_money', 'weal_value'], 'number'],
            [['cz_coin', 'weal_type', 'weal_time', 'status'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['cz_type'], 'string', 'max' => 25],
            [['cz_type_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'coin_cz_type_id' => 'Coin Cz Type ID',
            'cz_type' => 'Cz Type',
            'cz_type_name' => 'Cz Type Name',
            'cz_money' => 'Cz Money',
            'cz_coin' => 'Cz Coin',
            'weal_type' => 'Weal Type',
            'weal_value' => 'Weal Value',
            'weal_time' => 'Weal Time',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
