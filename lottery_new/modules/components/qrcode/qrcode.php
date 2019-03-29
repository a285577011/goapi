<?php

namespace app\modules\components\qrcode;

require_once 'phpqrcode.php';

class qrcode {

    public $url = "";
    public $imgName = "";  //生成二维码图片名称
    public $errorCorrectionLevel = 'H'; //容错级别
    public $matrixPointSize = 8; //生成图片大小
    public $logo = "";     //logo图片

    public function productQrcode() {
        \QRcode::png($this->url, $this->imgName, $this->errorCorrectionLevel, $this->matrixPointSize, 2);
        $QR = $this->imgName;
        $logo = $this->logo;
        if ($logo != "") {
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR); //二维码图片宽度
            $QR_height = imagesy($QR); //二维码图片高度
            $logo_width = imagesx($logo); //logo图片宽度
            $logo_height = imagesy($logo); //logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width / $logo_qr_width;
            $logo_qr_height = $logo_height / $scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
//重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
//输出图片
            imagepng($QR, $this->imgName);
        }
    }

}
