<?php

namespace app\modules\store\models;

use Yii;

/**
 * This is the model class for table "store_account".
 *
 * @property integer $store_account_id
 * @property integer $store_id
 * @property string $cust_no
 * @property string $account_name
 * @property string $account_nums
 * @property string $open_bank
 * @property string $bank_address
 * @property string $bank_branches
 * @property string $reserved_tel
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class StoreAccount extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store_account';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'cust_no', 'account_name', 'account_nums', 'open_bank', 'bank_address', 'bank_branches', 'reserved_tel'], 'required'],
            [['store_id'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['cust_no', 'bank_address'], 'string', 'max' => 100],
            [['account_name', 'open_bank'], 'string', 'max' => 50],
            [['account_nums'], 'string', 'max' => 19],
            [['bank_branches'], 'string', 'max' => 255],
            [['reserved_tel'], 'string', 'max' => 11],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_account_id' => 'Store Account ID',
            'store_id' => 'Store ID',
            'cust_no' => 'Cust No',
            'account_name' => 'Account Name',
            'account_nums' => 'Account Nums',
            'open_bank' => 'Open Bank',
            'bank_address' => 'Bank Address',
            'bank_branches' => 'Bank Branches',
            'reserved_tel' => 'Reserved Tel',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
