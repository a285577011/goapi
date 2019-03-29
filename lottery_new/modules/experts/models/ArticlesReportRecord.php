<?php

namespace app\modules\experts\models;

use Yii;

/** 
 * This is the model class for table "articles_report_record". 
 * 
 * @property integer $articles_report_record_id
 * @property string $cust_no
 * @property integer $article_id
 * @property integer $expert_id
 * @property string $report_reasons
 * @property string $create_time
 */ 
class ArticlesReportRecord extends \yii\db\ActiveRecord
{ 
    /** 
     * @inheritdoc 
     */ 
    public static function tableName() 
    { 
        return 'articles_report_record'; 
    } 

    /** 
     * @inheritdoc 
     */ 
    public function rules() 
    { 
        return [
            [['article_id', 'expert_id', 'create_time'], 'required'],
            [['article_id', 'expert_id'], 'integer'],
            [['create_time'], 'safe'],
            [['cust_no'], 'string', 'max' => 30],
            [['report_reasons'], 'string', 'max' => 255],
        ]; 
    } 

    /** 
     * @inheritdoc 
     */ 
    public function attributeLabels() 
    { 
        return [ 
            'articles_report_record_id' => 'Articles Report Record ID',
            'cust_no' => 'Cust No',
            'article_id' => 'Article ID',
            'expert_id' => 'Expert ID',
            'report_reasons' => 'Report Reasons',
            'create_time' => 'Create Time',
        ]; 
    } 
} 
