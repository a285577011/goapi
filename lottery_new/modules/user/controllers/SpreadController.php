<?php

namespace app\modules\user\controllers;

use yii\web\Controller;
use app\modules\user\models\User;
use app\modules\user\helpers\UserTool;
use app\modules\user\models\UserStatistics;

class SpreadController extends Controller {
    
    /**
     * 获取推广员的相关信息
     * @auther GL ZYL
     * @return type
     */
    public function actionGetSpreadInfo() {
        $userId = \Yii::$userId;
        $spreadData  = User::find()->select(['spread_type', 'invite_code', 'user_name', 'user_tel', 'user_pic', 'rebate'])->where(['user_id' => $userId])->andWhere(['>', 'spread_type', 0])->asArray()->one();
        if(empty($spreadData)) {
            return $this->jsonError(415, '您还不具备推广身份！具体详情请找客服查询');
        }
        $inviteNums = User::find()->where(['from_id' => $userId, 'register_from' => 7])->count();
        $spreadData['invite_nums'] = $inviteNums;
        $data['data'] = $spreadData; 
        return $this->jsonResult(600, '获取成功', $data);
    }

    /**
     * 获取推广二维码
     * @auther GL ZYL
     * @return type
     */
    public function actionGetSpreadQr() {
        $userId = \Yii::$userId;
        $userData  = User::find()->select(['spread_type', 'invite_code', 'user_name', 'user_tel', 'user_pic', 'rebate'])->where(['user_id' => $userId])->andWhere(['>', 'spread_type', 0])->asArray()->one();
        if(empty($userData)) {
            return $this->jsonError(415, '您还不具备推广身份！具体详情请找客服查询');
        }
        $url = \Yii::$app->params["userDomain"] . "/user/promotion/bindStore?inviteCode=" . $userData['invite_code'];
        $ret = ["user_pic" => $userData['user_pic'], "user_tel" => $userData['user_tel'], "user_name" => $userData['user_name'], 'url' => $url, 'invite_code' => $userData['invite_code'], 'rebate' => $userData['rebate']];
        return $this->jsonResult(600, '获取推广二维码成功', $ret);
    }
    
    /**
     * 获取被邀请人列表
     * @auther GL ZYL
     * @return type
     */
    public function actionGetInviteList() {
        $userId = \Yii::$userId;
        $request = \Yii::$app->request;
        $page = $request->post('page', 0);
        $size = $request->post('size', 20);
        $inquire = $request->post('inquire'. '');
        $where = [];
        if(!empty($inquire)) {
            $where = ['or', ['like', 'user_tel', $inquire], ['like', 'user_name', $inquire]];
        }
        $inviteNums = User::find()->where(['from_id' => $userId, 'register_from' => 7])->andWhere($where)->count();
        $offset = ($page - 1) * $size;
        $pages = ceil($inviteNums / $size);
        $list = User::find()->select(['user_name', 'user_tel', 'user_pic','spread_type','rebate','cust_no'])->where(['from_id' => $userId, 'register_from' => 7])->andWhere($where)->limit($size)->offset($offset)->asArray()->all();
        foreach ($list as $k=>$v){
        	$statis=UserStatistics::getStatis($v['cust_no']);
        	$list[$k]['total_money']=$statis['total_money'];
        }
        $data = ['page' => $page, 'size' => count($list), 'pages' => $pages, 'total' => $inviteNums, 'data' => $list];
        return $this->jsonResult(600, '获取成功', $data);
	}

	/**
	 * 设置用户是否为推广员
	 */
	public function actionSetRole()
	{
		$request = \Yii::$app->request;
		switch (true) {
			case $request->isPost:
				$post = \Yii::$app->request->post();
				$custNo = \Yii::$custNo;
				$rebate = $post['rebate'];
				$roleType = 1; // 角色类型暂时只有推广员
				$childCustNo = $post['custNo'];
				$userData = User::findOne(['cust_no' => $custNo]);
				if (! $userData)
				{
					return $this->jsonError(110, '用户参数错误');
				}
				switch ($roleType) {
					case 1:
						if($custNo==$childCustNo){
							return $this->jsonError(109, '不能设置自己!');
						}
						// 判断返点值
						if ($userData->spread_type >= 2 || $userData->spread_type == 0)
						{
							return $this->jsonError(111, '该用户不能设置推广员');
						}
						if ($rebate > $userData->rebate)
						{
							return $this->jsonError(113, '返点超过最大值!');
						}
						$childData = User::findOne(['cust_no' => $childCustNo]);
						if (! $childData)
						{
							return $this->jsonError(114, '推广的用户参数错误');
						}
						if ($childData->agent_code != $custNo)
						{
							return $this->jsonError(114, '推广的用户不是该用户的下级!');
						}
						$childData->spread_type = $userData->spread_type + 1;
						$childData->rebate = $rebate;
						$childData->invite_code = UserTool::getSpreadMark(6);
						if (! $childData->save())
						{
							return $this->jsonError(115, '系统错误');
						}
						return $this->jsonResult(600, '设置成功', []);
						break;
				}
				break;
			case $request->isGet:
				$custNo = \Yii::$custNo;
				$userData = User::find()->select('rebate,spread_type')->where(['cust_no' => $custNo])->asArray()->one();
				return $this->jsonResult(600, '获取成功', $userData);
    		break;
    	}
    }
}

