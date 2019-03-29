<?php

namespace app\modules\user\helpers;


use app\modules\agents\models\Agents;
use app\modules\user\models\User;
use app\modules\user\models\Coupons;
use app\modules\user\models\CouponsDetail;
use yii\db\Exception;
use yii\db\Expression;
use yii;

class UserTool {
	const ADMIN_NO='gl00015788';//古拉体育编号
	const SP='-';
	/**
	 * 获取用户层级分类树
	 * @param unknown $pNo上级父咕啦编号
	 * @param unknown $uNo上级父咕啦编号
	 */
	public static function getUserTree($pNo,$uNo){
		if($pNo==self::ADMIN_NO){
			return $pNo.self::SP.$uNo;
		}
		$isAgent=Agents::findOne(['agents_account'=>$pNo]);
		if($isAgent){//上级是代理商
			return self::ADMIN_NO.self::SP.$pNo.self::SP.$uNo;
		}
		$parentsNO=User::findOne(['cust_no'=>$pNo]);
		return $parentsNO->p_tree.self::SP.$uNo;
	}

	/**
	 * 生成推广员邀请码
	 */
	public static function getSpreadMark($nums)
	{
		$str = md5(uniqid(microtime(true), true));
		$mark = strtoupper(substr($str, 0, $nums));
		while (User::findOne(['invite_code' => $mark]))
		{
			$str = md5(uniqid(microtime(true), true));
			$mark = strtoupper(substr($str, 0, $nums));
		}
		return $mark;
	}
    /**
     * 推广平台注册用户注册是赠优惠券
     */
    public static function regSendCoupons($batch,$userAry){
        if ($batch == "" || $userAry == "") {
            return ["code"=>109,"msg"=>'参数缺失'];
        }
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            //保存优惠券明细表记录
            $times = date('Y-m-d H:i:s');
            $detail = "insert into coupons_detail(coupons_no,conversion_code,coupons_batch,send_user,send_status,use_status,send_time,create_time) values";
            foreach ($userAry as $k => $v) {
                $num = str_pad($k + 1, 6, "0", STR_PAD_LEFT);
                $coupons_no = $batch . date("Ymdhis") . $num;
                $conversionCode = self::getCouponsMark();
                if ($k == count($userAry) - 1) {
                    $detail.="('" . $coupons_no . "','" . $conversionCode . "','" . $batch . "','" . $v . "',2,1,'" . $times . "','" . $times . "')";
                } else {
                    $detail.="('" . $coupons_no . "','" . $conversionCode . "','" . $batch . "','" . $v . "',2,1,'" . $times . "','" . $times . "'),";
                }
            }
            $addDetail = $db->createCommand($detail)->execute();
            if (!$addDetail) {
                throw new Exception('失败，优惠券详情表新增失败');
            }
            //更新优惠券主表数据
            $updateCoupons = \Yii::$app->db->createCommand()->update('coupons', ['numbers' => new Expression('numbers+' . count($userAry)), 'send_num' => new Expression('send_num+' . count($userAry))], ["batch" => $batch])->execute();
            if (!$updateCoupons) {
                throw new Exception('优惠券表单更新失败');
            }
            $trans->commit();
            return ["code"=>600,"msg"=>'优惠券发行成功'];
        } catch (Exception $e) {
            $trans->rollBack();
            return ["code"=>109,"msg"=>$e->getMessage()];
        }
    }

    /**
     * 生成15位优惠券兑换码
     */
    public static function getCouponsMark() {
        $str = md5(uniqid(microtime(true), true));
        $mark = strtoupper(substr($str, 0, 15));
        while(CouponsDetail::findOne(["conversion_code" => $mark])){
            $str = md5(uniqid(microtime(true), true));
            $mark = strtoupper(substr($str, 0, 15));
        }
        return $mark;
    }
    /**
     * 推广注册用户数组
     */
    public static function getUserAry($cust_no,$num){
        $userAry = [];
        for($i=0;$i<$num;$i++){
            array_push($userAry,$cust_no);
        }
        return $userAry;
    }
    /**
     * 查找符合条件的优惠券批次
     */
    public static function getCouponsBatch($where=[],$now,$num){
        $coupons = Coupons::find()->select("batch")
            ->where($where)
            ->andWhere(['and',['use_range' => 101], ["<=","start_date",$now],[">","end_date",$now]])
            ->andWhere("send_num +{$num}<1000")
            ->orderBy("coupons_id desc")
            ->asArray()
            ->one();
        if(!empty($coupons)){
            return $coupons;
        }else{
            return false;
        }

    }

}
