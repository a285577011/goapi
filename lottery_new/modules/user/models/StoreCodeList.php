<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "redeem_code".
 *
 * @property integer $id
 * @property string $store_code
 * @property string $create_time
 */
class StoreCodeList extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store_code_list';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_code'], 'string', 'max' => 32],
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
            'create_time' => 'Create Time',
        ];
    }
}
