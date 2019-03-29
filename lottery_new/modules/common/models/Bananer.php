<?php
namespace app\modules\common\models;

use Yii;

/**
 * This is the model class for table "bananer".
 *
 * @property integer $bananer_id
 * @property string $pic_name
 * @property string $content
 * @property string $pic_url
 * @property string $pc_pic_url
 * @property string $jump_url
 * @property integer $type
 * @property integer $use_type
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property integer $opt_id
 */
class Bananer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bananer';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['content'], 'string'],
            [['type', 'use_type', 'status', 'opt_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['pic_name'], 'string', 'max' => 50],
            [['pic_url', 'pc_pic_url', 'jump_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bananer_id' => 'Bananer ID',
            'pic_name' => 'Pic Name',
            'content' => 'Content',
            'pic_url' => 'Pic Url',
            'pc_pic_url' => 'Pc Pic Url',
            'jump_url' => 'Jump Url',
            'type' => 'Type',
            'use_type' => 'Use Type',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'opt_id' => 'Opt ID',
        ];
    }
}
