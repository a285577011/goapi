<?php

namespace app\models\orders\models;

use Yii;

/**
 * This is the model class for table "auto_out_third".
 *
 * @property integer $auto_out_third_id
 * @property string $third_name
 * @property integer $out_type
 * @property string $out_lottery
 * @property integer $status
 * @property integer $weight
 * @property string $opt_name
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class AutoOutThird extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auto_out_third';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['third_name'], 'required'],
            [['out_type', 'status', 'weight'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['third_name'], 'string', 'max' => 50],
            [['out_lottery'], 'string', 'max' => 255],
            [['opt_name'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'auto_out_third_id' => 'Auto Out Third ID',
            'third_name' => 'Third Name',
            'out_type' => 'Out Type',
            'out_lottery' => 'Out Lottery',
            'status' => 'Status',
            'weight' => 'Weight',
            'opt_name' => 'Opt Name',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
