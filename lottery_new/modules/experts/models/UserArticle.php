<?php

namespace app\modules\experts\models;

use Yii;

/**
 * This is the model class for table "user_article".
 *
 * @property integer $user_article_id
 * @property string $user_article_code
 * @property integer $user_id
 * @property integer $article_id
 * @property integer $status
 * @property string $create_time
 * @property string $modify_time
 * @property string $update_time
 */
class UserArticle extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_article';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_article_code', 'user_id', 'article_id'], 'required'],
            [['user_id', 'article_id', 'status'], 'integer'],
            [['create_time', 'modify_time', 'update_time'], 'safe'],
            [['user_article_code'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_article_id' => 'User Article ID',
            'user_article_code' => 'User Article Code',
            'user_id' => 'User ID',
            'article_id' => 'Article ID',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'modify_time' => 'Modify Time',
            'update_time' => 'Update Time',
        ];
    }
    /**
     * 更新数据
     * @param unknown $update
     * @param unknown $where
     */
    public static function upData($update,$where){
    	 
    	return \Yii::$app->db->createCommand()->update(self::tableName(),$update,$where)->execute();
    }
}
