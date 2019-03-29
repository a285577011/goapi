<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "zmf_order".
 *
 * @property integer $zmf_order_id
 * @property string $order_code
 * @property string $version
 * @property string $command
 * @property string $messageId
 * @property string $status
 * @property string $bet_val
 * @property string $ret_sync_data
 * @property string $ret_async_data
 * @property string $create_time
 * @property string $modify_time
 */
class ZmfOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zmf_order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_code', 'version', 'command', 'messageId', 'bet_val'], 'required'],
            [['create_time'], 'safe'],
            [['version'], 'string', 'max' => 15]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'zmf_order_id' => 'Zmf Order ID',
            'order_code' => 'Order Code',
            'version' => 'Version',
            'command' => 'Command',
            'messageId' => 'Message ID',
            'status' => 'status',
            'bet_val' => 'Bet Val',
            'ret_sync_data' => 'Ret Sync Data',
            'ret_async_data' => 'Ret Async Data',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
        ];
    }
}
