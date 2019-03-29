<?php

namespace app\modules\store\models;

use Yii;

/**
 * This is the model class for table "store".
 *
 * @property integer $store_id
 * @property integer $store_code
 * @property string $cust_no
 * @property string $password
 * @property string $store_name
 * @property string $phone_num
 * @property string $telephone
 * @property string $email
 * @property string $province
 * @property string $city
 * @property string $area
 * @property string $address
 * @property string $coordinate
 * @property integer $store_type
 * @property integer $cert_status
 * @property integer $real_name_status
 * @property string $review_remark
 * @property string $pay_password
 * @property integer $status
 * @property string $store_remark
 * @property integer $opt_id
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 * @property string $support_bonus
 * @property string $open_time
 * @property string $close_time
 * @property string $contract_start_date
 * @property string $contract_end_date
 * @property string $store_img
 * @property string $store_qrcode
 * @property string $store_grade
 * @property integer $his_win_nums
 * @property string $his_win_amount
 * @property integer $made_nums
 * @property integer $consignment_type
 */
class Store extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_code', 'store_type', 'cert_status', 'real_name_status', 'status', 'opt_id', 'his_win_nums', 'made_nums', 'consignment_type'], 'integer'],
            [['cust_no'], 'required'],
            [['modify_time', 'create_time', 'update_time', 'contract_start_date', 'contract_end_date'], 'safe'],
            [['support_bonus', 'his_win_amount'], 'number'],
            [['cust_no', 'store_name', 'coordinate', 'review_remark', 'pay_password', 'store_qrcode'], 'string', 'max' => 100],
            [['password', 'address', 'store_remark', 'store_img'], 'string', 'max' => 255],
            [['phone_num'], 'string', 'max' => 11],
            [['telephone'], 'string', 'max' => 12],
            [['email', 'province', 'city', 'area', 'store_grade'], 'string', 'max' => 50],
            [['open_time', 'close_time'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_id' => 'Store ID',
            'store_code' => 'Store Code',
            'cust_no' => 'Cust No',
            'password' => 'Password',
            'store_name' => 'Store Name',
            'phone_num' => 'Phone Num',
            'telephone' => 'Telephone',
            'email' => 'Email',
            'province' => 'Province',
            'city' => 'City',
            'area' => 'Area',
            'address' => 'Address',
            'coordinate' => 'Coordinate',
            'store_type' => 'Store Type',
            'cert_status' => 'Cert Status',
            'real_name_status' => 'Real Name Status',
            'review_remark' => 'Review Remark',
            'pay_password' => 'Pay Password',
            'status' => 'Status',
            'store_remark' => 'Store Remark',
            'opt_id' => 'Opt ID',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'support_bonus' => 'Support Bonus',
            'open_time' => 'Open Time',
            'close_time' => 'Close Time',
            'contract_start_date' => 'Contract Start Date',
            'contract_end_date' => 'Contract End Date',
            'store_img' => 'Store Img',
            'store_qrcode' => 'Store Qrcode',
            'store_grade' => 'Store Grade',
            'his_win_nums' => 'His Win Nums',
            'his_win_amount' => 'His Win Amount',
            'made_nums' => 'Made Nums',
            'consignment_type' => 'Consignment Type',
        ];
    }
}
