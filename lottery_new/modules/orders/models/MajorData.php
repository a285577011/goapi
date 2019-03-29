<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "major_data".
 *
 * @property integer $major_id
 * @property integer $order_id
 * @property string $major
 * @property integer $major_type
 * @property integer $source
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class MajorData extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'major_data';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'major'], 'required'],
            [['order_id', 'major_type', 'source'], 'integer'],
            [['major'], 'string'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'major_id' => 'Major ID',
            'order_id' => 'Order ID',
            'major' => 'Major',
            'major_type' => 'Major Type',
            'source' => 'Source',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
