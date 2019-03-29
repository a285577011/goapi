<?php

namespace app\modules\tools\controllers;
use yii\web\Controller;
use yii\web\UploadedFile;

use app\modules\tools\helpers\Uploadfile;

/**
 * Default controller for the `tools` module
 */
class ImgController extends Controller
{
    /**
     * 说明:用户头像上传至七牛(form表单提交)
     * @author  kevi
     * @date 2017年1月12日 上午10:15:16
     * @param
     * @return
     */
    public function actionUser_pic_upload(){
        $request = \Yii::$app->request;
        $pic = $_FILES['file']['tmp_name'];
        $day = date('ymdHis', time());
        $custNo = $this->custNo;
        if(empty($custNo)){
            $this->jsonError(100, '用户不存在');
        }
        $key = 'img/user/user_pic/'.$custNo.'/'.$day .'-'. $_FILES['file']['name'];
        $picture = Uploadfile::qiniu_upload($pic, $key);//上传至七牛服务器
        if($picture==441){
            $this->jsonError(441, '上传失败');
        }
        $this->jsonResult(600, 'cuss',['user_pic'=>$picture]);
    }
    
    /**
     * 说明:彩种图片上传至七牛(form表单提交)
     * @author  kevi
     * @date 2017年1月12日 上午10:15:16
     * @param
     * @return
     */
    public function actionLottery_pic_upload(){
        $request = \Yii::$app->request;
        $pic = $_FILES['file']['tmp_name'];
        $day = date('ymdHis', time());
        $key = 'img/lottery/'.$day .'-'. $_FILES['file']['name'];
        $picture = Uploadfile::qiniu_upload($pic, $key);//上传至七牛服务器
        if($picture==441){
            $this->jsonError(441, '上传失败');
        }
        $this->jsonResult(600, 'cuss',['user_pic'=>$picture]);
    }
    
    public function actionLotterPic(){
        $file = $_FILES['file'];
        $pic = $file['tmp_name'];
        $typeArr = array('gif', 'jpg', 'jpeg', 'png');
        if($file['name']){
            $name = $file['name'];
            $type = strtolower(substr($name,strrpos($name,'.')+1));
            if(!in_array($type, $typeArr)) {
                $this->jsonError(440, '文件格式不正确');
            }
            $path = Uploadfile::pic_host_upload($file,'/lottery/');//上传至图片服务器
            return $path;
        }else{
            $this->jsonError(441,'上传文件未找到');
        }
    }
}
