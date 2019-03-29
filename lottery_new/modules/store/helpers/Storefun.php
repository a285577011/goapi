<?php

namespace app\modules\store\helpers;

use app\modules\tools\helpers\Uploadfile;
use app\modules\common\models\Store;
use app\modules\common\helpers\Constants;
use app\modules\competing\helpers\CompetConst;
use app\modules\orders\models\WeightLotteryOut;

class Storefun {

    /**
     * 手机验证码校验
     * @auther GL zyl
     * @param type $tel
     * @param type $code
     * @return boolean
     */
    public static function getCheckCode($key, $tel, $code) {
        $redis = \yii::$app->redis;
        $data = $redis->executeCommand('get', ["{$key}:{$tel}"]);
        if (empty($data)) {
            return ['code' => '409', 'msg' => '验证码已过期，请重新获取'];
        }
        if ($code === $data) {
            return ['code' => '600', 'msg' => '验证成功'];
        } else {
            return ['code' => '410', 'msg' => '验证码错误，请重新填写'];
        }
    }

    /**
     * base64 图片上传图片服务器
     * @auther GL zyl
     * @param type $baseImg
     * @param type $saveDir
     * @param type $name
     * @return array
     */
    public static function getPath($baseImg, $saveDir, $name) {
//        $img = base64_decode($baseImg);
        $pathJson = Uploadfile::pic_host_upload_base64($baseImg, $saveDir, $name);
        $pathArr = json_decode($pathJson, true);
        if ($pathArr['code'] != 600) {
            $result = ['code' => $pathArr['code'], 'msg' => $pathArr['msg']];
            return $result;
        }
        $path = $pathArr['result']['ret_path'];
        $result = ['code' => '600', 'msg' => '获取成功', 'data' => $path];
        return $result;
    }

    /**
     * 普通图片上传图片服务器
     * @auther GL zyl
     * @param type $file
     * @param type $saveDir
     * @param type $name
     * @return array
     */
    public static function getImgPath($file, $saveDir, $name) {
        $pathJson = Uploadfile::pic_host_upload($file, $saveDir, $name);
        $pathArr = json_decode($pathJson, true);
        if ($pathArr['code'] != 600) {
            $result = ['code' => $pathArr['code'], 'msg' => $pathArr['msg']];
            return $result;
        }
        $path = $pathArr['result']['ret_path'];
        $result = ['code' => '600', 'msg' => '获取成功', 'data' => $path];
        return $result;
    }

    /**
     * 随机获取门店
     * @param type $lotteryCode
     * @return type
     */
    public static function getStore($lotteryCode, $outTicket, $type = 1, $storeNo = '', $ipProvince = '') {
        $basketball = CompetConst::MADE_BASKETBALL_LOTTERY;
        $football = Constants::MADE_FOOTBALL_LOTTERY;
        $bd = CompetConst::MADE_BD_LOTTERY;
        $wcup = CompetConst::MADE_WCUP_LOTTERY;
        if (in_array($lotteryCode, $basketball)) {
            $lotteryCode = 3100;
        }
        if (in_array($lotteryCode, $football)) {
            $lotteryCode = 3000;
        }
        if (in_array($lotteryCode, $bd)) {
            $lotteryCode = 5000;
        }
        if (in_array($lotteryCode, $wcup)) {
            $lotteryCode = 3300;
        }

        $query = Store::find();
        $autoStore = [];
        $where = ['and', ['company_id' => 1, 'business_status' => 1], ['like', 'sale_lottery', $lotteryCode]];
        if (!empty($storeNo)) {
            $where[] = ['store_code' => $storeNo];
        } else {
            $autoStore = WeightLotteryOut::find()->select(['out_code', 'weight_lottery_out.weight'])
                    ->innerJoin('store s', 's.store_code = weight_lottery_out.out_code')
                    ->where(['weight_lottery_out.lottery_code' => $lotteryCode, 's.company_id' => 1, 's.business_status' => 1, 's.status' => 1])
                    ->andWhere(['like', 's.sale_lottery', $lotteryCode])
                    ->andWhere(['>', 'weight_lottery_out.weight', 0])
                    ->indexBy('out_code')
                    ->asArray()
                    ->all();
            if (!empty($autoStore)) {
                $storeArr = array_column($autoStore, 'out_code');
                $where[] = ['in', 'store_code', $storeArr];
            }
        }
        if (!empty($ipProvince)) {
            $province = Store::find()->select(['store_code', 'store_id'])->where($where)->andWhere(['like', 'province', $ipProvince])->indexBy('store_code')->asArray()->all();
            if (!empty($province)) {
                $storeCodeArr = array_keys($province);
                $where[] = ['in', 'store_code', $storeCodeArr];
            } else {
                $ipProvince = '';
            }
        }
        $sysAuto = \Yii::$app->params['sysAutoOut'];
        if ($type == 1 || $sysAuto != 1) {
            $where[] = ['status' => 1];
            $store = $query->select(['store_code store_no', 'store_name', 'user_id store_id', 'business_status', 'sale_lottery', 'weight', 'cust_no'])
                    ->where($where)
                    ->indexBy('store_no')
                    ->asArray()
                    ->all();
        } elseif ($type == 2) {
            $where[] = ['store.status' => 1];
            $where[] = ['>=', "d.mod_nums", $outTicket];
            $where[] = ['d.type' => 2, 'd.status' => 1];
            $where[] = ['like', 'd.out_lottery', $lotteryCode];
            $store = $query->select(['store_code store_no', 'store_name', 'user_id store_id', 'business_status', 'sale_lottery', 'weight', 'cust_no', 'd.out_lottery'])
                    ->innerJoin('ticket_dispenser d', 'd.store_no = store.store_code')
                    ->where($where)->indexBy('store_no')
                    ->asArray()
                    ->all();
        }
        if (empty($store)) {
            if ($type == 2) {
                $type = 1;
                $retData = self::getStore($lotteryCode, $outTicket, $type, $storeNo, $ipProvince);
                return $retData;
            } else {
                return ['code' => 109, 'msg' => '暂无门店接单！请稍后再试！'];
            }
        }
        $weight = 0;
        $subStore = array();
        foreach ($store as $one) {
            if(!empty($autoStore)) {
                $oneWeight = (int) $autoStore[$one['store_no']]['weight'];
            } else {
                $oneWeight = (int) $one['weight'] ? $one['weight'] : 1;
            }
//            $oneWeight = (int) $one['weight'] ? $one['weight'] : 1;
            $weight += $oneWeight;
            for ($i = 0; $i < $oneWeight; $i ++) {
                $subStore[] = $one;
            }
        }
        $data = $subStore[rand(0, $weight - 1)];
        return ['code' => 600, 'msg' => '获取成功', 'data' => $data];
    }

}
