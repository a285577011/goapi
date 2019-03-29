<?php

namespace app\modules\experts\models;

use Yii;

/**
 * This is the model class for table "expert_articles".
 *
 * @property integer $expert_articles_id
 * @property string $articles_code
 * @property integer $user_id
 * @property integer $article_type
 * @property string $article_title
 * @property integer $pay_type
 * @property string $pay_money
 * @property string $article_content
 * @property integer $article_status
 * @property integer $result_status
 * @property integer $buy_nums
 * @property integer $read_nums
 * @property string $remark
 * @property string $article_remark
 * @property integer $buy_back
 * @property integer $deal_status
 * @property integer $opt_id
 * @property string $cutoff_time
 * @property string $review_time
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 * @property string $stick
 * @property string $oppid
 */
class ExpertArticles extends \yii\db\ActiveRecord
{ 
    /** 
     * @inheritdoc 
     */ 
    public static function tableName() 
    { 
        return 'expert_articles'; 
    } 

    /** 
     * @inheritdoc 
     */ 
    public function rules() 
    { 
        return [
            [['user_id'], 'required'],
            [['user_id', 'article_type', 'pay_type', 'article_status', 'result_status', 'buy_nums', 'read_nums', 'buy_back', 'deal_status', 'opt_id', 'stick', 'report_num'], 'integer'],
            [['pay_money'], 'number'],
            [['article_content'], 'string'],
            [['review_time', 'create_time', 'modify_time', 'update_time'], 'safe'],
            [['articles_code'], 'string', 'max' => 150],
            [['article_title', 'article_remark'], 'string', 'max' => 200],
            [['remark', 'cutoff_time'], 'string', 'max' => 100],
            [['oppid'], 'string', 'max' => 50],
        ]; 
    } 

    /** 
     * @inheritdoc 
     */ 
    public function attributeLabels() 
    { 
        return [ 
            'expert_articles_id' => 'Expert Articles ID',
            'articles_code' => 'Articles Code',
            'user_id' => 'User ID',
            'article_type' => 'Article Type',
            'article_title' => 'Article Title',
            'pay_type' => 'Pay Type',
            'pay_money' => 'Pay Money',
            'article_content' => 'Article Content',
            'article_status' => 'Article Status',
            'result_status' => 'Result Status',
            'buy_nums' => 'Buy Nums',
            'read_nums' => 'Read Nums',
            'remark' => 'Remark',
            'article_remark' => 'Article Remark',
            'buy_back' => 'Buy Back',
            'deal_status' => 'Deal Status',
            'opt_id' => 'Opt ID',
            'cutoff_time' => 'Cutoff Time',
            'review_time' => 'Review Time',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
            'stick' => 'Stick',
            'oppid' => 'Oppid',
            'report_num' => 'Report Num',
        ]; 
    } 
} 
