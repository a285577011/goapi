<?php

namespace app\modules\user\services;
use yii\base\Exception;
use app\modules\user\models\User;
use app\modules\user\models\ThirdUser;


interface IThirduserService
{
    public function thirdLogin($data);
    
    /**
     * 说明: 第三方表数据生成
     * @author  kevi
     * @date 2017年8月17日 下午2:35:22
     * @param
     * @return 
     */
    public function saveThirdUser($data);
    
    /**
     * 说明: 第三方绑定门店或用户
     * @author  kevi
     * @date 2017年8月17日 下午2:35:32
     * @param
     * @return 
     */
    public function thirdUserBound($openId,$userId,$uidSource);
}

class ThirduserService implements IThirduserService
{
    
    /**
     * 第三方登陆
     * {@inheritDoc}
     * @see \app\modules\api\services\IUserServices::thirdLogin()
     * 
     */
    public function thirdLogin($data)
    {
    
        //查询是否已在ThirdUser
        $thirdUserModel = new ThirdUser();
        $third_user = $thirdUserModel::find()->where(['third_uid' => $data['third_uid']])->one();   
        if(!empty($third_user) && $data['type']==2 && empty($third_user->union_id)){
            $third_user->union_id = $data['union_id'];
            $third_user->save();
        }

        $userModel = new User();
        $userId = isset(\Yii::$app->session['user']['user_id'])?(\Yii::$app->session['user']['user_id']):null;
        if ($third_user) {
	         //存在:session有值?绑定操作:登录操作
        	  $third_user_uid = $third_user->uid;
        	  $user = $userModel->find()->where(['user_id'=>$third_user_uid])->one();
        	  if (!empty($userId)){
        		  header('location:'.\Yii::$app->request->hostInfo."/user/views/touserset?key=2?user_set_wxqq1=5");
        		  die;
        	  }
        } else {
            //登录:注册网站用户+新建第三方帐号
            	if (empty($userId)){
            		try {
            			//注册网站用户
            			$inviteCode = $this->createInviteCode();
            			$userModel->nickname = $data['nickname'].'_'.$inviteCode;
            			$userModel->user_pic = $data['icon'];
            			$userModel->vip_id = '1';
    //         			$auterver = (new \yii\db\Query())->select(['auth_ver'])
    //                                         			->from('vip')
    //                                         			->where(['vip_id'=>1])
    // //                                         			->asArray()
    //                                         			->one();
            			$userModel->auth_ver = "normal";//$auterver['auth_ver'];
            			$userModel->invite_code = $inviteCode;
            			$userModel->reg_type = $data['type'];
            			$userModel->modify_time = date("Y-m-d H:i:s");
            			$userModel->create_time = date("Y-m-d H:i:s");
            			if(!$userModel->save()){
            				$errorMsg = $userModel->errors;
            				return \Yii::jsonError(100,$errorMsg);
            			}
            			$user = $userModel->attributes;
            			//第三方登录默认发送系统消息
            			$notice = new UserNotices();
            			$notice->user_id= $user['user_id'];
            			$notice->notice_type =1;
            			$notice->on_action = 1;
            			$notice->in_action =1;
            			$notice->account_return =1;
            			$notice->on_params =1;
            			$notice->in_params =1;
            			$notice->create_time = date('Y-m-d H:i:s',time());
            			$notice->modify_time = date('Y-m-d H:i:s',time());
            			if(!$ret=($notice->save())){
            				$errorMsg = $notice->errors;
            				return \Yii::jsonError(100,$errorMsg);
            			}
            			//初始化活动第一次关注
            			$redis = \yii::$app->redis;
            			$redis->executeCommand('sadd',["frist_follow_action",$userModel->user_id]);
            		} catch (Exception $e) {
            			throw $e;
            		}
            	}           
            try{
            	//新建第三方帐号
            	$thirdUserModel = new ThirdUser();
            	$thirdUserModel->uid = isset($user['user_id'])?$user['user_id']:$userId;
            	
            	$thirdUserModel->third_uid = $data['third_uid'];
            	if($data['type']==2){
            	    $thirdUserModel->union_id = $data['union_id'];
            	}
            	$thirdUserModel->type = $data['type'];
            	$thirdUserModel->icon = $data['icon'];
            	$thirdUserModel->nickname = $data['nickname'];
            	$thirdUserModel->sex = $data['sex'];
            	$thirdUserModel->create_time = date("Y-m-d H:i:s");
            	if(!$thirdUserModel->save()){
            		$errorMsg = $thirdUserModel->errors;
            		return \Yii::jsonError(100,$errorMsg);
            	};
            	$message = new Message();
            	$message-> user_id = isset($user['user_id'])?$user['user_id']:$userId;
            	$message-> type = Message::MESSAGE_TYPE_OTHER;
            	$message-> from = '点金册';
            	$message-> title = '欢迎来到点金册';
            	$message-> body = "欢迎您来到点金册，我们将为您提供专业、客观的网贷平台信息，帮助您最快速，最方便的了解网贷平台信息。";
            	$message-> create_time = date('Y-m-d H:i:s',time());
            	$ret = $message->save();
            } catch (Exception $e){
            	throw $e;
            }   
            if (!empty($userId)){
            	header('location:'.\Yii::$app->request->hostInfo."/user/views/touserset?key=1?user_set_wxqq1=5");die;
            }
        	
        }   
        $userAuths = $this->getUserAuths($user['auth_ver']);
        \Yii::$app->session['user_auth'] = $userAuths;
        unset($user['password']);
        return $user;

     }
        
     public function getUserAuths($userAuthVer){
            $model = new UserAuthInterface();
            $userAuthInterfaces = $model->find()
            ->select(['user_interface.url'])
            ->from('user_auth_interface as uai')
            ->leftJoin('auth_ver','auth_ver.auth_ver_id=uai.auth_ver_id')
            ->leftJoin('user_interface','user_interface.user_interface_id=uai.user_interface_id')
            ->where(['auth_ver.auth_ver'=>$userAuthVer])
            ->asArray()
            ->all();
            $userAuthArr = [];
            foreach ($userAuthInterfaces as $userAuth){
                if(!empty($userAuth['url'])){
                    $userAuthArr[] = trim($userAuth['url']);
                }
            }
            return $userAuthArr;
    }
    public function createInviteCode(){
            $inviteCode = \Yii::getRandomString(4);
            $compareUser = User::find()->where(['invite_code'=>$inviteCode])->asArray()->one();
            if(!empty($compareUser)){
                $this->createInviteCode();
            }
            return $inviteCode;
    }
    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IThirduserService::saveThirdUser()
     */
    public function saveThirdUser($data)
    {
        $thirdUserModel = ThirdUser::find()->where(['third_uid'=>$data['third_uid']])->one();
        if(empty($thirdUserModel)){
            $thirdUserModel = new ThirdUser();
            $thirdUserModel->union_id = $data['union_id'];
            $thirdUserModel->third_uid = $data['third_uid'];
            $thirdUserModel->type = $data['type'];
            $thirdUserModel->icon = $data['icon'];
            $thirdUserModel->nickname = $data['nickname'];
            $thirdUserModel->sex = $data['sex'];
            $thirdUserModel->create_time = date('Y-m-d H:i:s');
            if(!$thirdUserModel->save()){
                print_r($thirdUserModel->errors);die;
            }
        }
        return $thirdUserModel->attributes;
    }
    
    /**
     * 说明: 第三方绑定系统用户
     * @author  kevi
     * @date 2017年8月8日 下午1:50:46
     * @param $openId 第三方id
     * @param $userId 系统用户id
     * @return
     */
    public function thirdUserBound($openId,$userId,$uidSource){
        $thirdUser = ThirdUser::find()->where(['third_uid'=>$openId,'uid_source'=>$uidSource])->one();
        if(empty($thirdUser)){//绑定失败
            return ['code'=>100,'msg'=>'参数错误，绑定失败'];
        }
        $thirdUser->uid = $userId;
        if(!$thirdUser->save()){
            print_r($thirdUser->errors);die;
        }else{
            return ['code'=>600,'data'=>$thirdUser->attributes];
        }
    }

    
}
   
