<?php

namespace app\modules\store\models;

use Yii;

/**
 * This is the model class for table "store_detail".
 *
 * @property integer $store_detail_id
 * @property integer $store_id
 * @property string $cust_no
 * @property string $consignee_name
 * @property string $consignee_card
 * @property string $sports_consignee_code
 * @property string $welfare_consignee_code
 * @property string $company_name
 * @property string $business_license
 * @property string $operator_name
 * @property string $operator_card
 * @property string $old_owner_name
 * @property string $old_owner_card
 * @property string $now_owner_name
 * @property string $now_owner_card
 * @property string $remark
 * @property string $consignee_img
 * @property string $consignee_img2
 * @property string $consignee_card_img1
 * @property string $consignee_card_img2
 * @property string $consignee_card_img3
 * @property string $consignee_card_img4
 * @property string $old_owner_card_img1
 * @property string $old_owner_card_img2
 * @property string $business_license_img
 * @property string $competing_img
 * @property string $football_img
 * @property string $sports_nums_img
 * @property string $sports_fre_img
 * @property string $north_single_img
 * @property string $welfare_nums_img
 * @property string $welfare_fre_img
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class StoreDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['store_id', 'cust_no'], 'required'],
            [['store_id'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['cust_no', 'sports_consignee_code', 'welfare_consignee_code', 'company_name', 'business_license', 'operator_card', 'old_owner_name', 'now_owner_name', 'consignee_img', 'consignee_img2', 'consignee_card_img1', 'consignee_card_img2', 'consignee_card_img3', 'consignee_card_img4', 'old_owner_card_img1', 'old_owner_card_img2', 'business_license_img', 'competing_img', 'football_img', 'sports_nums_img', 'sports_fre_img', 'north_single_img', 'welfare_nums_img', 'welfare_fre_img'], 'string', 'max' => 100],
            [['consignee_name'], 'string', 'max' => 20],
            [['consignee_card', 'old_owner_card', 'now_owner_card'], 'string', 'max' => 18],
            [['operator_name'], 'string', 'max' => 50],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_detail_id' => 'Store Detail ID',
            'store_id' => 'Store ID',
            'cust_no' => 'Cust No',
            'consignee_name' => 'Consignee Name',
            'consignee_card' => 'Consignee Card',
            'sports_consignee_code' => 'Sports Consignee Code',
            'welfare_consignee_code' => 'Welfare Consignee Code',
            'company_name' => 'Company Name',
            'business_license' => 'Business License',
            'operator_name' => 'Operator Name',
            'operator_card' => 'Operator Card',
            'old_owner_name' => 'Old Owner Name',
            'old_owner_card' => 'Old Owner Card',
            'now_owner_name' => 'Now Owner Name',
            'now_owner_card' => 'Now Owner Card',
            'remark' => 'Remark',
            'consignee_img' => 'Consignee Img',
            'consignee_img2' => 'Consignee Img2',
            'consignee_card_img1' => 'Consignee Card Img1',
            'consignee_card_img2' => 'Consignee Card Img2',
            'consignee_card_img3' => 'Consignee Card Img3',
            'consignee_card_img4' => 'Consignee Card Img4',
            'old_owner_card_img1' => 'Old Owner Card Img1',
            'old_owner_card_img2' => 'Old Owner Card Img2',
            'business_license_img' => 'Business License Img',
            'competing_img' => 'Competing Img',
            'football_img' => 'Football Img',
            'sports_nums_img' => 'Sports Nums Img',
            'sports_fre_img' => 'Sports Fre Img',
            'north_single_img' => 'North Single Img',
            'welfare_nums_img' => 'Welfare Nums Img',
            'welfare_fre_img' => 'Welfare Fre Img',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
