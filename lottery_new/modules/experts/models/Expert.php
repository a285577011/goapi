<?php

namespace app\modules\experts\models;

use Yii;

/**
 * This is the model class for table "expert".
 *
 * @property integer $expert_id
 * @property integer $user_id
 * @property string $cust_no
 * @property string $introduction
 * @property integer $article_nums
 * @property integer $fans_nums
 * @property integer $read_nums
 * @property integer $lottery
 * @property integer $even_red_nums
 * @property integer $even_back_nums
 * @property integer $identity
 * @property integer $month_red_nums
 * @property integer $day_nums
 * @property integer $day_red_nums
 * @property integer $two_red_nums
 * @property integer $three_red_nums
 * @property integer $five_red_nums
 * @property integer $max_even_red
 * @property integer $expert_status
 * @property integer $pact_status
 * @property integer $expert_source
 * @property integer $expert_type
 * @property string $expert_type_name
 * @property string $remark
 * @property integer $opt_id
 * @property string $review_time
 * @property string $oppid
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class Expert extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'expert';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'cust_no'], 'required'],
            [['user_id', 'article_nums', 'fans_nums', 'read_nums', 'lottery', 'even_red_nums', 'even_back_nums', 'identity', 'month_red_nums', 'day_nums', 'day_red_nums', 'two_red_nums', 'three_red_nums', 'five_red_nums', 'max_even_red', 'expert_status', 'pact_status', 'expert_source', 'expert_type', 'opt_id'], 'integer'],
            [['review_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['cust_no'], 'string', 'max' => 15],
            [['introduction'], 'string', 'max' => 1000],
            [['expert_type_name', 'oppid'], 'string', 'max' => 50],
            [['remark'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'expert_id' => 'Expert ID',
            'user_id' => 'User ID',
            'cust_no' => 'Cust No',
            'introduction' => 'Introduction',
            'article_nums' => 'Article Nums',
            'fans_nums' => 'Fans Nums',
            'read_nums' => 'Read Nums',
            'lottery' => 'Lottery',
            'even_red_nums' => 'Even Red Nums',
            'even_back_nums' => 'Even Back Nums',
            'identity' => 'Identity',
            'month_red_nums' => 'Month Red Nums',
            'day_nums' => 'Day Nums',
            'day_red_nums' => 'Day Red Nums',
            'two_red_nums' => 'Two Red Nums',
            'three_red_nums' => 'Three Red Nums',
            'five_red_nums' => 'Five Red Nums',
            'max_even_red' => 'Max Even Red',
            'expert_status' => 'Expert Status',
            'pact_status' => 'Pact Status',
            'expert_source' => 'Expert Source',
            'expert_type' => 'Expert Type',
            'expert_type_name' => 'Expert Type Name',
            'remark' => 'Remark',
            'opt_id' => 'Opt ID',
            'review_time' => 'Review Time',
            'oppid' => 'Oppid',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
}
