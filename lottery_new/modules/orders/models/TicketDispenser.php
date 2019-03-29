<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "ticket_dispenser".
 *
 * @property integer $ticket_dispenser_id
 * @property integer $type
 * @property string $dispenser_code
 * @property integer $store_no
 * @property integer $pre_out_nums
 * @property integer $mod_nums
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class TicketDispenser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ticket_dispenser';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'store_no', 'pre_out_nums', 'mod_nums', 'status'], 'integer'],
            [['dispenser_code', 'store_no'], 'required'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['dispenser_code'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ticket_dispenser_id' => 'Ticket Dispenser ID',
            'type' => 'Type',
            'dispenser_code' => 'Dispenser Code',
            'store_no' => 'Store No',
            'pre_out_nums' => 'Pre Out Nums',
            'mod_nums' => 'Mod Nums',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
