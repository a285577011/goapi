<?php

namespace app\modules\user\models;

use Yii;
use app\modules\common\services\KafkaService;
require_once \Yii::$app->basePath . '/vendor/resque/lottery/lottery_queue.php';
/**
 * This is the model class for table "user".
 *
 * @property integer $user_id
 * @property string $user_name
 * @property string $user_tel
 * @property string $user_land
 * @property integer $user_sex
 * @property string $cust_no
 * @property string $user_pic
 * @property string $province
 * @property string $city
 * @property string $area
 * @property string $address
 * @property string $invite_code
 * @property integer $user_type
 * @property integer $is_operator
 * @property string $account_time
 * @property integer $status
 * @property integer $authen_status
 * @property string $authen_remark
 * @property integer $register_from
 * @property string $level_name
 * @property integer $level_id
 * @property string $p_tree
 * @property string $agent_code
 * @property string $agent_name
 * @property integer $agent_id
 * @property string $user_remark
 * @property integer $opt_id
 * @property string $last_login
 * @property string $modify_time
 * @property string $create_time
 * @property string $update_time
 */
class User extends \yii\db\ActiveRecord
{
    const REG_FROM_CP = 1;
    const REG_FROM_H5 = 2;
    const REG_FROM_QB = 3;
    const REG_FTOM_TG = 7;
    const REG_FTOM_PT = 8;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_name', 'user_tel'], 'required'],
            [['user_sex', 'user_type', 'is_operator', 'status', 'authen_status', 'register_from', 'level_id', 'agent_id', 'opt_id'], 'integer'],
            [['account_time', 'last_login', 'modify_time', 'create_time', 'update_time'], 'safe'],
            [['user_name', 'province', 'city', 'area'], 'string', 'max' => 50],
            [['user_tel', 'user_land'], 'string', 'max' => 12],
            [['address', 'invite_code', 'authen_remark', 'level_name'], 'string', 'max' => 100],
            [['cust_no'], 'string', 'max' => 15],
            [['user_pic'], 'string', 'max' => 256],
            [['agent_code', 'agent_name', 'user_remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'user_name' => 'User Name',
            'user_tel' => 'User Tel',
            'user_land' => 'User Land',
            'user_sex' => 'User Sex',
            'cust_no' => 'Cust No',
            'user_pic' => 'User Pic',
            'province' => 'Province',
            'city' => 'City',
            'area' => 'Area',
            'address' => 'Address',
            'invite_code' => 'Invite Code',
            'user_type' => 'User Type',
            'is_operator' => 'Is Operator',
            'account_time' => 'Account Time',
            'status' => 'Status',
            'authen_status' => 'Authen Status',
            'authen_remark' => 'Authen Remark',
            'register_from' => 'Register From',
            'level_name' => 'Level Name',
            'level_id' => 'Level ID',
        	'p_tree' => 'p_tree',
            'agent_code' => 'Agent Code',
            'agent_name' => 'Agent Name',
            'agent_id' => 'Agent ID',
            'user_remark' => 'User Remark',
            'opt_id' => 'Opt ID',
            'last_login' => 'Last Login',
            'modify_time' => 'Modify Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
    /**
     * 保存数据
     */
    public function saveData(){
    	//print_r(array_filter($this->attributes));
    	$res=$this->save();
    	if ($res)
    	{
    		//$lotteryqueue = new \LotteryQueue();
    		$data = array_filter($this->attributes, function ($v) {
    			return $v !== null;
    		});
    			if (isset($data['user_id']))
    			{ // 有主键更新
    				$update=$data;
    				$where['user_id']=$data['user_id'];
    				unset($update['user_id']);
    				KafkaService::addQue('SyncImUser', ['tablename' => self::tableName(),'type' => 'update','data'=>['update'=>$update,'where'=>$where],'pk'=>['user_id'=>$data['user_id']]],false);
    				//$lotteryqueue->pushQueue('syncImUser_job', 'sync#'.self::tableName(), ['tablename' => self::tableName(),'type' => 'update','data'=>['update'=>$update,'where'=>$where],'pk'=>['user_id'=>$data['user_id']]]);//主键更新
    			}else
    			{
    				$data['user_id']=\Yii::$app->db->getLastInsertID();
    				KafkaService::addQue('SyncImUser',['tablename' => self::tableName(),'type' => 'insert','data' => $data,'pkField'=>'user_id'],false);
    				//$lotteryqueue->pushQueue('syncImUser_job', 'sync#'.self::tableName(), ['tablename' => self::tableName(),'type' => 'insert','data' => $data,'pkField'=>'user_id']);
    			}
    	}
    	return $res;
    }
}
