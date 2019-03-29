<?php
namespace app\modules\test\services;

use yii\base\Exception;
use yii\helpers\ArrayHelper;
use app\modules\test\models\Test;

interface ITestService
{
    public function ajaxTel($phone);
    public function aa($words,$num);
    public function cc($user);
}
class TestService implements ITestService
{
   public function ajaxTel($phone)
    {
        $test = new Test();
        if($phone){
            
            $exist = $test->find()->asArray()->where("user_tel = '{$phone}'")->one();
            if(!empty($exist)){
                return true;
            }else{
                return false;
            }
        }
    }
/**
     * {@inheritDoc}
     * @see \app\modules\test\services\ITestService::aa($words)
     */
    public function aa($words,$num)
    {
        $ret = $this->getRank($words,$num);
        return $ret;
    }
    
    function getRank($arr, $len, $str="") {
        global $arr_getrank;
        $arr_len = count($arr);
        if($len == 0){
            $arr_getrank[] = $str;
        }else{
            for($i=0; $i<$arr_len; $i++){
                $tmp = array_shift($arr);
                if (empty($str))
                {
                    $this->getRank($arr, $len-1, $tmp);
                }
                else
                {
                    $this->getRank($arr, $len-1, $str.",".$tmp);
                }
            }
        }
        return $arr_getrank;
    }

    public function cc($user){


        return $user->user_id;
    }
}