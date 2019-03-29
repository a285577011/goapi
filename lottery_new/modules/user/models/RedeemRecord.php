<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "redeem_record".
 *
 * @property integer $id
 * @property string $store_code
 * @property string $open_id
 * @property integer $redeem_code_id
 * @property integer $status
 * @property string $modify_time
 * @property string $create_time
 */
class RedeemRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'redeem_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['redeem_code_id', 'status'], 'integer'],
            [['create_time'], 'safe'],
            [['store_code'], 'string', 'max' => 45],
            [['open_id'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_code' => 'Store Code',
            'open_id' => 'Open ID',
            'redeem_code_id' => 'Redeem Code ID',
            'status' => 'Status',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
        ];
    }
}
