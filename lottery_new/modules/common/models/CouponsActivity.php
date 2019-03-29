<?php

namespace app\modules\common\models;

/**
 * This is the model class for table "coupons_activity".
 *
 * @property integer $coupons_activity_id
 * @property integer $activity_id
 * @property string $batch
 * @property integer $send_num
 * @property integer $status
 * @property string $create_time
 */
class CouponsActivity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'coupons_activity';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['activity_id', 'send_num', 'status'], 'integer'],
            [['create_time'], 'safe'],
            [['batch'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'coupons_activity_id' => 'Coupons Activity ID',
            'activity_id' => 'Activity ID',
            'batch' => 'Batch',
            'send_num' => 'Send Num',
            'status' => 'Status',
            'create_time' => 'Create Time',
        ];
    }
}
