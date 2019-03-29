<?php

namespace app\modules\user\controllers;

use Yii;
use yii\web\Controller;
use app\modules\user\models\User;
use app\modules\user\services\IUserService;
use app\modules\user\models\BussinessPlatformUser;

/**
 * 用户控制器
 */
class PlatformUserController extends Controller {

    private $userService;

    public function __construct($id, $module, $config = [], IUserService $userService) {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    public function actionLoginByTel(){
        
        $request = \Yii::$app->request;
        $platformCode = $request->post('platform_code');
        $platformId = $request->post('platform_id');
        $userTel = $request->post('user_tel');
        
        $pltUser = BussinessPlatformUser::find()->select(['uid as user_id','cust_no'])
            ->leftJoin('user','user.user_id = bussiness_platform_user.uid')
            ->where(['bussiness_platform_id'=>$platformId,'bussiness_platform_user.user_tel'=>$userTel])->asArray()->one();
        if(empty($pltUser)){//未生成第三方平台用户
            $newpltUser = new BussinessPlatformUser();
            //自动注册用户
            $phpUser = User::find()->where(['user_tel'=>$userTel])->one();
            if(!empty($phpUser)){//php中已经注册
                 $newpltUser->uid = $phpUser->user_id;
                 $cust_no = $phpUser->cust_no;
            }else{
                $javaUser = $this->userService->getJavaUserDetailByTel($userTel);
                if($javaUser['httpCode'] !=200){//java未注册
                    $javaRegUser = $this->userService->javaRegister($userTel, '123321');
                    if ($javaRegUser['httpCode'] == 200) {
                        $javaUser = $this->userService->getJavaUserDetailByTel($userTel);//java 根据手机号获取用户信息接口
                        if($javaUser['httpCode'] !=200){
                            return $this->jsonError(490, '登录失败,获取用户信息失败,请稍候重试');
                        }
                    }else{
                        return $this->jsonError(490, '登录失败,注册未成功,请稍候重试');
                    }
                }
                $result = $this->userService->setRegisterData($javaUser);
                $result['register_from'] = 9;
                $userjava = $this->userService->createOrUpdateUser($userTel, $javaUser['data']['account'], $result);
                $newpltUser->uid = $userjava['data']['user_id'];
                $cust_no = $userjava['data']['cust_no'];
            }
            
            $newpltUser->bussiness_platform_id = $platformId;
            $newpltUser->user_tel = $userTel;
            $newpltUser->status = 1;
            $newpltUser->create_time = date('Y-m-d H:i:s');
            $newpltUser->save();
            $user_id = $newpltUser->uid;
        }else{//已有平台用户
            $cust_no = $pltUser['cust_no'];
            $user_id = $pltUser['user_id'];
        }
        $token = $this->userService->autoLogin($cust_no, $user_id);//自动登录
        return $this->jsonResult(600, 'login succ', ['platform_token'=>$token]);
    }
    
    /**
     * {@inheritDoc}
     * @see \app\modules\user\services\IUserService::autoLogin()
     */
//     public function autoPlatformLogin($custNo, $userId, $objStr = 'user') {
//         $token = $this->createToken($custNo); //生成token
//         $oldToken = \Yii::tokenGet("{$objStr}_token:{$custNo}");
//         \Yii::tokenDel("token_{$objStr}:{$oldToken}");
//         \Yii::tokenSet("token_{$objStr}:{$token}", "{$custNo}|{$userId}"); //保存token
//         \Yii::tokenSet("{$objStr}_token:{$custNo}", "{$token}"); //保存token
//         return $token;
//     }
}
