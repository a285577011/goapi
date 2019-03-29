<?php

namespace app\modules\cron\controllers;

use yii\web\Controller;
use app\modules\competing\models\BdSchedule;
use app\modules\competing\models\BdScheduleResult;
use app\modules\competing\models\Odds5001;
use app\modules\competing\models\Odds5002;
use app\modules\competing\models\Odds5003;
use app\modules\competing\models\Odds5004;
use app\modules\competing\models\Odds5005;
use app\modules\competing\models\Odds5006;
use app\modules\common\helpers\Commonfun;
use Yii;
use app\modules\competing\models\BdLeague;
use app\modules\competing\models\BdTeam;
use app\modules\competing\models\BdScheduleTechnic;
use app\modules\competing\models\BdScheduleEvent;
use yii\db\Query;
use app\modules\common\helpers\Winning;
use app\modules\competing\helpers\CompetConst;

class BdDataController extends Controller {

    /**
     * 北单赛程对接
     * @return type
     */
    public function actionBdScheduleData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $periods = Commonfun::arrParameter($data, 'periods');
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $playType = Commonfun::arrParameter($data, 'play_type');
        $bdSort = Commonfun::arrParameter($data, 'bd_sort');

        $key = ['periods', 'open_mid', 'schedule_mid', 'play_type', 'schedule_type', 'bd_sort', 'start_time', 'beginsale_time', 'endsale_time', 'league_name', 'home_name', 'visit_name', 'spf_rq_nums', 'sfgg_rf_nums',
            'sale_status', 'sfgg_status', 'zjqs_status', 'bqc_status', 'spf_status', 'sxpds_status', 'dcbf_status', 'xbcbf_status', 'league_code', 'home_code', 'visit_code', 'schedule_date'];

        $bdSchedule = BdSchedule::findOne(['periods' => $periods, 'open_mid' => $openMid, 'play_type' => $playType, 'bd_sort' => $bdSort]);
        $where = [];
        if (empty($bdSchedule)) {
            array_push($key, 'create_time');
        } else {
            array_push($key, 'modify_time');
            $where = ['periods' => $periods, 'open_mid' => $openMid, 'play_type' => $playType, 'bd_sort' => $bdSort];
        }
        $scheduleDate = Commonfun::arrParameter($data, 'schedule_date');
        $scheduleMid = Commonfun::arrParameter($data, 'schedule_mid');
        $homeCode = Commonfun::arrParameter($data, 'home_code');
        $visitCode = Commonfun::arrParameter($data, 'visit_code');
        $leagueCode = Commonfun::arrParameter($data, 'league_code');
        $scheduleType = $data['schedule_type'];
        $leagueName = $data['league_name'];
        $homeTeam = $data['home_name'];
        $visitTeam = $data['visit_name'];
        $db = \Yii::$app->db;
        $info[] = [$periods, $openMid, $scheduleMid, $playType, $scheduleType, $bdSort, $data['start_time'], $data['beginsale_time'], $data['endsale_time'], $leagueName, $homeTeam, $visitTeam, $data['spf_rq_nums'],
            $data['sfgg_rf_nums'], $data['sale_status'], $data['sfgg_status'], $data['zjqs_status'], $data['bqc_status'], $data['spf_status'], $data['sxpds_status'], $data['dcbf_status'], $data['xbcbf_status'],
            $leagueCode, $homeCode, $visitCode, $scheduleDate, date('Y-m-d H:i:s')];
        if (empty($bdSchedule)) {
            $data = $db->createCommand()->batchInsert('bd_schedule', $key, $info)->execute();
        } else {
            $param = array_combine($key, $info[0]);
            $data = $db->createCommand()->update('bd_schedule', $param, $where)->execute();
        }
        if ($data === false) {
            return \Yii::jsonError(109, '赛程数据处理失败');
        }
        // $sch = BdSchedule::find()->select(['bd_schedule_id'])->where(['periods' => $periods, 'open_mid' => $openMid, 'play_type' => $playType, 'bd_sort' => $bdSort])->asArray()->one();
        $scheduleResult = BdScheduleResult::findOne(['periods' => $periods, 'open_mid' => $openMid, 'play_type' => $playType, 'bd_sort' => $bdSort]);
        if (empty($scheduleResult)) {
            $scheduleResult = new BdScheduleResult();
            $scheduleResult->create_time = date('Y-m-d H:i:s');
        } else {
            $scheduleResult->modify_time = date('Y-m-d H:i:s');
        }
//        $scheduleResult->bd_schedule_id = $sch['bd_schedule_id'];
        $scheduleResult->periods = $periods;
        $scheduleResult->open_mid = $openMid;
        $scheduleResult->play_type = $playType;
        $scheduleResult->schedule_mid = $scheduleMid;
        $scheduleResult->bd_sort = $bdSort;
        if (!$scheduleResult->save()) {
            return $this->jsonResult(109, '赛程结果表数据写入失败', $scheduleResult->getFirstErrors());
        }
        if (empty($homeCode) || empty($visitCode) || empty($leagueCode)) {
            return \Yii::jsonResult(601, '联赛/球队编号未知', true);
        }
        return \Yii::jsonResult(600, '赛程数据处理成功', true);
    }

    /**
     * 北单赛程结果写入
     * @return type
     */
    public function actionBdScheduleResultData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $periods = Commonfun::arrParameter($data, 'periods');
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $playType = Commonfun::arrParameter($data, 'play_type');
        $bdSort = Commonfun::arrParameter($data, 'bd_sort');

        $bdScheduleResult = BdScheduleResult::findOne(['periods' => $periods, 'open_mid' => $openMid, 'play_type' => $playType, 'bd_sort' => $bdSort]);
        if (empty($bdScheduleResult)) {
            return \Yii::jsonError(109, '该赛程不存在');
        }
//        if ($bdScheduleResult->status == 2) {
//            return \Yii::jsonError(109, '该赛程已有开奖结果！');
//        }
        $status = Commonfun::arrParameter($data, 'status');
        if ($status != 2) {
            return \Yii::jsonError(109, '赛程赛果数据状态有误');
        }
        $bdScheduleResult->result_5001 = $data['result_5001'];
        $bdScheduleResult->result_5002 = $data['result_5002'];
        $bdScheduleResult->result_5003 = $data['result_5003'];
        $bdScheduleResult->result_5004 = $data['result_5004'];
        $bdScheduleResult->result_5005 = $data['result_5005'];
        $bdScheduleResult->result_5006 = $data['result_5006'];
        $bdScheduleResult->odds_5001 = $data['odds_5001'];
        $bdScheduleResult->odds_5002 = $data['odds_5002'];
        $bdScheduleResult->odds_5003 = $data['odds_5003'];
        $bdScheduleResult->odds_5004 = $data['odds_5004'];
        $bdScheduleResult->odds_5005 = $data['odds_5005'];
        $bdScheduleResult->odds_5006 = $data['odds_5006'];
        $bdScheduleResult->result_bcbf = $data['result_bcbf'];
        $bdScheduleResult->status = $status;
        $bdScheduleResult->modify_time = date('Y-m-d H:i:s');
        if (!$bdScheduleResult->save()) {
            return $this->jsonResult(109, '赛程结果数据处理失败', $bdScheduleResult->getFirstErrors());
        }
        $bifen = CompetConst::BD_5005;
        $bf = explode(':', $bdScheduleResult->result_bcbf);
        $bdScheduleResult->result_5005 = str_replace(':', '', $bdScheduleResult->result_5005);
        $result5005 = $bdScheduleResult->result_5005;
        if ($bf[0] < $bf[1]) {
            if (!in_array($result5005, $bifen[0])) {
                $bdScheduleResult->result_5005 = '09';
            }
        } elseif ($bf[0] == $bf[1]) {
            if (!in_array($result5005, $bifen[1])) {
                $bdScheduleResult->result_5005 = '99';
            }
        } elseif ($bf[0] > $bf[1]) {
            if (!in_array($result5005, $bifen[3])) {
                $bdScheduleResult->result_5005 = '90';
            }
        }
        if ($bdScheduleResult->result_5002 > 7) {
            $bdScheduleResult->result_5002 = 7;
        } 
        $winning = new Winning();
        $winning->bdLevel($openMid, $bdScheduleResult->result_5001, $bdScheduleResult->result_5002, $bdScheduleResult->result_5003, $bdScheduleResult->result_5004, $bdScheduleResult->result_5005, $bdScheduleResult->result_5006, $bdScheduleResult->odds_5001, $bdScheduleResult->odds_5002, $bdScheduleResult->odds_5003, $bdScheduleResult->odds_5004, $bdScheduleResult->odds_5005, $bdScheduleResult->odds_5006);
        return $this->jsonResult(600, '赛程结果数据处理成功', true);
    }

    /**
     * 北单胜平负赔率
     * @return type
     */
    public function actionOdds5001Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $newOdds = Odds5001::findOne(["open_mid" => $openMid]);
        if (empty($newOdds)) {
            $updatesNums = 1;
            $newOdds = new Odds5001();
        } else {
            $updatesNums = $newOdds->update_nums + 1;
        }

        $newOdds->open_mid = $openMid;
        $newOdds->update_nums = $updatesNums;
        $newOdds->odds_0 = Commonfun::arrParameter($data, 'odds_0');
        $newOdds->trend_0 = Commonfun::arrParameter($data, 'trend_0');
        $newOdds->odds_1 = Commonfun::arrParameter($data, 'odds_1');
        $newOdds->trend_1 = Commonfun::arrParameter($data, 'trend_1');
        $newOdds->odds_3 = Commonfun::arrParameter($data, 'odds_3');
        $newOdds->trend_3 = Commonfun::arrParameter($data, 'trend_3');
        $newOdds->create_time = date('Y-m-d H:i:s');
        if (!$newOdds->save()) {
            return \Yii::jsonResult(109, '数据处理失败', $newOdds->getFirstErrors());
        }
        return \Yii::jsonResult(600, '数据处理成功', true);
    }

    /**
     * 总进球赔率
     * @return type
     */
    public function actionOdds5002Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $newOdds = Odds5002::findOne(["open_mid" => $openMid]);
        if (empty($newOdds)) {
            $updatesNums = 1;
            $newOdds = new Odds5002();
        } else {
            $updatesNums = $newOdds->update_nums + 1;
        }
        $newOdds->open_mid = $openMid;
        $newOdds->update_nums = $updatesNums;
        $newOdds->odds_0 = Commonfun::arrParameter($data, 'odds_0');
        $newOdds->trend_0 = Commonfun::arrParameter($data, 'trend_0');
        $newOdds->odds_1 = Commonfun::arrParameter($data, 'odds_1');
        $newOdds->trend_1 = Commonfun::arrParameter($data, 'trend_1');
        $newOdds->odds_2 = Commonfun::arrParameter($data, 'odds_2');
        $newOdds->trend_2 = Commonfun::arrParameter($data, 'trend_2');
        $newOdds->odds_3 = Commonfun::arrParameter($data, 'odds_3');
        $newOdds->trend_3 = Commonfun::arrParameter($data, 'trend_3');
        $newOdds->odds_4 = Commonfun::arrParameter($data, 'odds_4');
        $newOdds->trend_4 = Commonfun::arrParameter($data, 'trend_4');
        $newOdds->odds_5 = Commonfun::arrParameter($data, 'odds_5');
        $newOdds->trend_5 = Commonfun::arrParameter($data, 'trend_5');
        $newOdds->odds_6 = Commonfun::arrParameter($data, 'odds_6');
        $newOdds->trend_6 = Commonfun::arrParameter($data, 'trend_6');
        $newOdds->odds_7 = Commonfun::arrParameter($data, 'odds_7');
        $newOdds->trend_7 = Commonfun::arrParameter($data, 'trend_7');
        $newOdds->create_time = date('Y-m-d H:i:s');
        if (!$newOdds->save()) {
            return \Yii::jsonResult(109, '数据处理失败', $newOdds->getFirstErrors());
        }
        return \Yii::jsonResult(600, '数据处理成功', true);
    }

    /**
     * 北单半全场胜平负赔率
     * @return type
     */
    public function actionOdds5003Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $newOdds = Odds5003::findOne(["open_mid" => $openMid]);
        if (empty($newOdds)) {
            $updatesNums = 1;
            $newOdds = new Odds5003();
        } else {
            $updatesNums = $newOdds->update_nums + 1;
        }
        $newOdds->open_mid = $openMid;
        $newOdds->update_nums = $updatesNums;
        $newOdds->odds_00 = Commonfun::arrParameter($data, 'odds_00');
        $newOdds->trend_00 = Commonfun::arrParameter($data, 'trend_00');
        $newOdds->odds_01 = Commonfun::arrParameter($data, 'odds_01');
        $newOdds->trend_01 = Commonfun::arrParameter($data, 'trend_01');
        $newOdds->odds_03 = Commonfun::arrParameter($data, 'odds_03');
        $newOdds->trend_03 = Commonfun::arrParameter($data, 'trend_03');
        $newOdds->odds_10 = Commonfun::arrParameter($data, 'odds_10');
        $newOdds->trend_10 = Commonfun::arrParameter($data, 'trend_10');
        $newOdds->odds_11 = Commonfun::arrParameter($data, 'odds_11');
        $newOdds->trend_11 = Commonfun::arrParameter($data, 'trend_11');
        $newOdds->odds_13 = Commonfun::arrParameter($data, 'odds_13');
        $newOdds->trend_13 = Commonfun::arrParameter($data, 'trend_13');
        $newOdds->odds_30 = Commonfun::arrParameter($data, 'odds_30');
        $newOdds->trend_30 = Commonfun::arrParameter($data, 'trend_30');
        $newOdds->odds_31 = Commonfun::arrParameter($data, 'odds_31');
        $newOdds->trend_31 = Commonfun::arrParameter($data, 'trend_31');
        $newOdds->odds_33 = Commonfun::arrParameter($data, 'odds_33');
        $newOdds->trend_33 = Commonfun::arrParameter($data, 'trend_33');
        $newOdds->create_time = date('Y-m-d H:i:s');
        if (!$newOdds->save()) {
            return \Yii::jsonResult(109, '数据处理失败', $newOdds->getFirstErrors());
        }
        return \Yii::jsonResult(600, '数据处理成功', true);
    }

    /**
     * 北单上下场单双
     * @return type
     */
    public function actionOdds5004Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $newOdds = Odds5004::findOne(["open_mid" => $openMid]);
        if (empty($newOdds)) {
            $updatesNums = 1;
            $newOdds = new Odds5004();
        } else {
            $updatesNums = $newOdds->update_nums + 1;
        }
        $newOdds->open_mid = $openMid;
        $newOdds->update_nums = $updatesNums;
        $newOdds->odds_1 = Commonfun::arrParameter($data, 'odds_1');
        $newOdds->trend_1 = Commonfun::arrParameter($data, 'trend_1');
        $newOdds->odds_2 = Commonfun::arrParameter($data, 'odds_2');
        $newOdds->trend_2 = Commonfun::arrParameter($data, 'trend_2');
        $newOdds->odds_3 = Commonfun::arrParameter($data, 'odds_3');
        $newOdds->trend_3 = Commonfun::arrParameter($data, 'trend_3');
        $newOdds->odds_4 = Commonfun::arrParameter($data, 'odds_4');
        $newOdds->trend_4 = Commonfun::arrParameter($data, 'trend_4');
        $newOdds->create_time = date('Y-m-d H:i:s');
        if (!$newOdds->save()) {
            return \Yii::jsonResult(109, '数据处理失败', $newOdds->getFirstErrors());
        }
        return \Yii::jsonResult(600, '数据处理成功', true);
    }

    public function actionOdds5005Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $newOdds = Odds5005::findOne(["open_mid" => $openMid]);
        if (empty($newOdds)) {
            $updatesNums = 1;
            $newOdds = new Odds5005();
        } else {
            $updatesNums = $newOdds->update_nums + 1;
        }
        $newOdds->open_mid = $openMid;
        $newOdds->update_nums = $updatesNums;
        $newOdds->odds_10 = Commonfun::arrParameter($data, 'odds_10');
        $newOdds->trend_10 = Commonfun::arrParameter($data, 'trend_10');
        $newOdds->odds_20 = Commonfun::arrParameter($data, 'odds_20');
        $newOdds->trend_20 = Commonfun::arrParameter($data, 'trend_20');
        $newOdds->odds_21 = Commonfun::arrParameter($data, 'odds_21');
        $newOdds->trend_21 = Commonfun::arrParameter($data, 'trend_21');
        $newOdds->odds_30 = Commonfun::arrParameter($data, 'odds_30');
        $newOdds->trend_30 = Commonfun::arrParameter($data, 'trend_30');
        $newOdds->odds_31 = Commonfun::arrParameter($data, 'odds_31');
        $newOdds->trend_31 = Commonfun::arrParameter($data, 'trend_31');
        $newOdds->odds_32 = Commonfun::arrParameter($data, 'odds_32');
        $newOdds->trend_32 = Commonfun::arrParameter($data, 'trend_32');
        $newOdds->odds_40 = Commonfun::arrParameter($data, 'odds_40');
        $newOdds->trend_40 = Commonfun::arrParameter($data, 'trend_40');
        $newOdds->odds_41 = Commonfun::arrParameter($data, 'odds_41');
        $newOdds->trend_41 = Commonfun::arrParameter($data, 'trend_41');
        $newOdds->odds_42 = Commonfun::arrParameter($data, 'odds_42');
        $newOdds->trend_42 = Commonfun::arrParameter($data, 'trend_42');
        $newOdds->odds_00 = Commonfun::arrParameter($data, 'odds_00');
        $newOdds->trend_00 = Commonfun::arrParameter($data, 'trend_00');
        $newOdds->odds_11 = Commonfun::arrParameter($data, 'odds_11');
        $newOdds->trend_11 = Commonfun::arrParameter($data, 'trend_11');
        $newOdds->odds_22 = Commonfun::arrParameter($data, 'odds_22');
        $newOdds->trend_22 = Commonfun::arrParameter($data, 'trend_22');
        $newOdds->odds_33 = Commonfun::arrParameter($data, 'odds_33');
        $newOdds->trend_33 = Commonfun::arrParameter($data, 'trend_33');
        $newOdds->odds_01 = Commonfun::arrParameter($data, 'odds_01');
        $newOdds->trend_01 = Commonfun::arrParameter($data, 'trend_01');
        $newOdds->odds_02 = Commonfun::arrParameter($data, 'odds_02');
        $newOdds->trend_02 = Commonfun::arrParameter($data, 'trend_02');
        $newOdds->odds_12 = Commonfun::arrParameter($data, 'odds_12');
        $newOdds->trend_12 = Commonfun::arrParameter($data, 'trend_12');
        $newOdds->odds_03 = Commonfun::arrParameter($data, 'odds_03');
        $newOdds->trend_03 = Commonfun::arrParameter($data, 'trend_03');
        $newOdds->odds_13 = Commonfun::arrParameter($data, 'odds_13');
        $newOdds->trend_13 = Commonfun::arrParameter($data, 'trend_13');
        $newOdds->odds_23 = Commonfun::arrParameter($data, 'odds_23');
        $newOdds->trend_23 = Commonfun::arrParameter($data, 'trend_23');
        $newOdds->odds_04 = Commonfun::arrParameter($data, 'odds_04');
        $newOdds->trend_04 = Commonfun::arrParameter($data, 'trend_04');
        $newOdds->odds_14 = Commonfun::arrParameter($data, 'odds_14');
        $newOdds->trend_14 = Commonfun::arrParameter($data, 'trend_14');
        $newOdds->odds_24 = Commonfun::arrParameter($data, 'odds_24');
        $newOdds->trend_24 = Commonfun::arrParameter($data, 'trend_24');
        $newOdds->odds_90 = Commonfun::arrParameter($data, 'odds_90');
        $newOdds->trend_90 = Commonfun::arrParameter($data, 'trend_90');
        $newOdds->odds_99 = Commonfun::arrParameter($data, 'odds_99');
        $newOdds->trend_99 = Commonfun::arrParameter($data, 'trend_99');
        $newOdds->odds_09 = Commonfun::arrParameter($data, 'odds_09');
        $newOdds->trend_09 = Commonfun::arrParameter($data, 'trend_09');
        $newOdds->create_time = date('Y-m-d H:i:s');
        if (!$newOdds->save()) {
            return \Yii::jsonResult(109, '数据处理失败', $newOdds->getFirstErrors());
        }
        return \Yii::jsonResult(600, '数据处理成功', true);
    }

    /**
     * 北单胜负过关赔率
     * @return type
     */
    public function actionOdds5006Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $newOdds = Odds5006::findOne(["open_mid" => $openMid]);
        if (empty($newOdds)) {
            $updatesNums = 1;
            $newOdds = new Odds5006();
        } else {
            $updatesNums = $newOdds->update_nums + 1;
        }
        $newOdds->open_mid = $openMid;
        $newOdds->update_nums = $updatesNums;
        $newOdds->odds_0 = Commonfun::arrParameter($data, 'odds_0');
        $newOdds->trend_0 = Commonfun::arrParameter($data, 'trend_0');
        $newOdds->odds_3 = Commonfun::arrParameter($data, 'odds_3');
        $newOdds->trend_3 = Commonfun::arrParameter($data, 'trend_3');
        $newOdds->create_time = date('Y-m-d H:i:s');
        if (!$newOdds->save()) {
            return \Yii::jsonResult(109, '数据处理失败', $newOdds->getFirstErrors());
        }
        return \Yii::jsonResult(600, '数据处理成功', true);
    }

    /**
     * 北单联赛数据接入
     * @return json
     */
    public function actionBdLeaguesData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTempStr = "insert into bd_league (league_code,league_type,league_short_name,league_long_name,league_img,league_category_id,league_remarks,league_status,create_time)values(':league_code',':league_type',':league_short_name',':league_long_name',':league_img',:league_category_id,':league_remarks',1,'" . date("Y-m-d H:i:s") . "');";
        $updateTempStr = "update bd_league set league_short_name=':league_short_name',league_long_name=':league_long_name',league_img=':league_img',league_category_id=:league_category_id,league_remarks=':league_remarks',league_status=1,modify_time='" . date("Y-m-d H:i:s") . "' where league_code=':league_code' and league_type=':league_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["league_code"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["league_code"];
            }
        }
        $hasLeagues = BdLeague::find()->select("league_code,league_type")->where(["in", "league_code", $mids])->asArray()->all();
        $zuHasMids = [];
        $lanHasMids = [];
        $bingHasMids = [];
        $wangHasMids = [];
        $yuHasMids = [];
        $pingHasMids = [];
        $ganHasMids = [];
        $otherHasMids = [];
        foreach ($hasLeagues as $val) {
            if ($val['league_type'] == 1) {
                $zuHasMids[] = $val["league_code"];
            } elseif ($val['league_type'] == 2) {
                $lanHasMids[] = $val['league_code'];
            } elseif ($val['league_type'] == 3) {
                $bingHasMids[] = $val['league_code'];
            } elseif ($val['league_type'] == 4) {
                $wangHasMids[] = $val['league_code'];
            } elseif ($val['league_type'] == 5) {
                $yuHasMids[] = $val['league_code'];
            } elseif ($val['league_type'] == 6) {
                $pingHasMids[] = $val['league_code'];
            } elseif ($val['league_type'] == 7) {
                $ganHasMids[] = $val['league_code'];
            } elseif ($val['league_type'] == 8) {
                $otherHasMids[] = $val['league_code'];
            } else {
                return $this->jsonError(109, '联赛所属类型不存在');
            }
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if ((in_array($val["league_code"], $zuHasMids) && $val['league_type'] == 1) || (in_array($val["league_code"], $lanHasMids) && $val['league_type'] == 2)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTempStr, $val);
            } elseif ((in_array($val["league_code"], $lanHasMids) && $val['league_type'] == 3) || (in_array($val["league_code"], $lanHasMids) && $val['league_type'] == 4)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTempStr, $val);
            } elseif ((in_array($val["league_code"], $lanHasMids) && $val['league_type'] == 5) || (in_array($val["league_code"], $lanHasMids) && $val['league_type'] == 6)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTempStr, $val);
            } elseif ((in_array($val["league_code"], $lanHasMids) && $val['league_type'] == 7) || (in_array($val["league_code"], $lanHasMids) && $val['league_type'] == 8)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .= Commonfun::strTemplateReplace($insertTempStr, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "联赛数据处理失败");
        }
        return $this->jsonResult(600, "联赛数据处理成功", $ret);
    }

    /**
     * 球队数据接入
     * @return json
     */
    public function actionBdTeamData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTempStr = "insert into bd_team (team_code,team_type,team_position,team_short_name,team_long_name,country_name,team_img,team_remarks,team_status,create_time)values(':team_code',':team_type',':team_position',':team_short_name',':team_long_name',':country_name',':team_img',':team_remarks',1,'" . date("Y-m-d H:i:s") . "');";
        $updateTempStr = "update bd_team set team_position=':team_position',team_short_name=':team_short_name',team_long_name=':team_long_name',country_name=':country_name',team_img=':team_img',team_remarks=':team_remarks',team_status=1,modify_time='" . date("Y-m-d H:i:s") . "' where team_code=':team_code' and team_type=':team_type';";
        $tids[1] = $tids[2] = $tids[3] = $tids[4] = $tids[5] = $tids[6] = $tids[7] = $tids[8] = [];
        $mids[1] = $mids[2] = $mids[3] = $mids[4] = $mids[5] = $mids[6] = $mids[7] = $mids[8] = [];
        $leagueTeamArr[1] = $leagueTeamArr[2] = $leagueTeamArr[3] = $leagueTeamArr[4] = $leagueTeamArr[5] = $leagueTeamArr[6] = $leagueTeamArr[7] = $leagueTeamArr[8] = [];
        $leagueNames[1] = $leagueNames[2] = $leagueNames[3] = $leagueNames[4] = $leagueNames[5] = $leagueNames[6] = $leagueNames[7] = $leagueNames[8] = [];
        foreach ($data as $key => $val) {
            if (in_array($val['team_type'], [1, 2, 3, 4, 5, 6, 7, 8])) {
                $teamType = $val['team_type'];
                if (!isset($leagueTeamArr[$teamType][$val["league_code"]])) {
                    $leagueTeamArr[$teamType][$val["league_code"]] = [];
                    $mids[$teamType][] = $val["league_code"];
                }
                $leagueTeamArr[$teamType][$val["league_code"]][$val["team_code"]] = $val["team_code"];
                $leagueNames[$teamType][$val["league_code"]] = isset($val["league_name"]) ? $val["league_name"] : "缺少联赛名";
                if (in_array($val["team_code"], $tids[$teamType])) {
                    unset($data[$key]);
                } else {
                    $tids[$teamType][] = $val["team_code"];
                }
            } else {
                return $this->jsonError(109, '球队所属类型不存在');
            }
        }

        $hasTeams = BdTeam::find()->select("team_code,team_type")->where(['team_type' => $teamType])->andWhere(['in', 'team_code', $tids[$teamType]])->asArray()->all();
        $hasTids = [];
        foreach ($hasTeams as $val) {
            $hasTids[$teamType][] = $val['team_code'];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $val["team_short_name"] = trim($val["team_short_name"], "'");
            $val["team_long_name"] = trim($val["team_long_name"], "'");
            $type = $val['team_type'];
            if (isset($hasTids[$type]) && (in_array($val["team_code"], $hasTids[$type]) && $val['team_type'] == $teamType)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .= Commonfun::strTemplateReplace($insertTempStr, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "球队数据处理失败");
        }

        $msg = "";
        $Leagues = BdLeague::find()->select("league_id,league_code,league_type")->where(['league_type' => $teamType])->andWhere(["in", "league_code", $mids[$teamType]])->indexBy("league_code")->asArray()->all();
        foreach ($leagueTeamArr[$teamType] as $key => $list) {
            if (!isset($Leagues[$key])) {
                $league = new BdLeague();
                $league->league_type = $teamType;
                $league->league_long_name = trim($leagueNames[$teamType][$key], "'");
                $league->league_short_name = trim($leagueNames[$teamType][$key], "'");
                $league->league_code = (string) $key;
                $league->create_time = date("Y-m-d H:i:s");
                if ($league->validate()) {
                    $ret = $league->save();
                    if ($ret === false) {
                        $msg .=$key . $leagueNames[1][$key] . ":联赛缺失插入失败；";
                        continue;
                    }
                } else {
                    $msg .=$key . $leagueNames[$teamType][$key] . ":" . json_encode($league->getFirstErrors(), true);
                    continue;
                }
            }
        }
        return $this->jsonResult(600, $msg . "球队数据处理成功", "");
    }

    /**
     * 赛程历史数据接入 
     * @return json
     */
    public function actionBdScheduleHistoryData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into bd_schedule_history (schedule_mid,league_code,league_name,play_time,home_team_mid,home_team_name,visit_team_mid,visit_team_name,result_3007,result_3009_b,result_3010,create_time)values(':schedule_mid',':league_code',':league_name',':play_time',':home_team_mid',':home_team_name',':visit_team_mid',':visit_team_name',':result_3007',':result_3009_b',':result_3010','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update bd_schedule_history set league_code=':league_code',league_name=':league_name',play_time=':play_time',home_team_mid=':home_team_mid',home_team_name=':home_team_name',visit_team_mid=':visit_team_mid',visit_team_name=':visit_team_name',result_3007=':result_3007',result_3009_b=':result_3009_b',result_3010=':result_3010',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasScheduleHistory = (new \yii\db\Query())->select("schedule_mid")->from("bd_schedule_history")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasScheduleHistory as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= Commonfun::strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程历史数据处理失败");
        }
        return $this->jsonResult(600, "赛程历史数据处理成功", $ret);
    }

    /**
     * 赛程历史总计数据接入 
     * @return json
     */
    public function actionBdHistoryCountData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into bd_history_count (schedule_mid,double_play_num,num3,num1,num0,home_team_rank,visit_team_rank,home_team_league,visit_team_league,scale_3010_3,scale_3010_1,scale_3010_0,scale_3006_3,scale_3006_1,scale_3006_0,europe_odds_3,europe_odds_1,europe_odds_0,create_time)values(':schedule_mid',':double_play_num',':num3',':num1',':num0',':home_team_rank',':visit_team_rank',':home_team_league',':visit_team_league',':scale_3010_3',':scale_3010_1',':scale_3010_0',':scale_3006_3',':scale_3006_1',':scale_3006_0',':europe_odds_3',':europe_odds_1',':europe_odds_0','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update bd_history_count set double_play_num=':double_play_num',num3=':num3',num1=':num1',num0=':num0',home_team_rank=':home_team_rank',visit_team_rank=':visit_team_rank',home_team_league=':home_team_league',visit_team_league=':visit_team_league',scale_3010_3=':scale_3010_3',scale_3010_1=':scale_3010_1',scale_3010_0=':scale_3010_0',scale_3006_3=':scale_3006_3',scale_3006_1=':scale_3006_1',scale_3006_0=':scale_3006_0',europe_odds_3=':europe_odds_3',europe_odds_1=':europe_odds_1',europe_odds_0=':europe_odds_0',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        $parent = '/[\x80-\xff]+/';
        $homeLeague = [];
        $visitLeague = [];
        foreach ($data as $key => &$val) {
            preg_match($parent, $val['home_team_rank'], $homeLeague);
            preg_match($parent, $val['visit_team_rank'], $visitLeague);
            if (!empty($homeLeague)) {
                $val['home_team_league'] = $homeLeague[0];
                $val['home_team_rank'] = str_replace($homeLeague[0], '', $val['home_team_rank']);
            }
            if (!empty($visitLeague)) {
                $val['visit_team_league'] = $visitLeague[0];
                $val['visit_team_rank'] = str_replace($visitLeague[0], '', $val['visit_team_rank']);
            }
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select("schedule_mid")->from("bd_history_count")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $v) {
            $hasMids[] = $v["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= Commonfun::strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程历史总计数据处理失败");
        }
        return $this->jsonResult(600, "赛程历史总计数据处理成功", $ret);
    }

    /**
     * 亚盘数据接入 
     * @return json
     */
    public function actionBdAsianHandicapData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into bd_asian_handicap (schedule_mid,company_name,country,handicap_type,handicap_name,home_discount,let_index,visit_discount,create_time,home_discount_trend,visit_discount_trend)values(':schedule_mid',':company_name',':country',':handicap_type',':handicap_name',':home_discount',':let_index',':visit_discount','" . date("Y-m-d H:i:s") . "',':home_discount_trend',':visit_discount_trend');";
        $updateTemp = "update bd_asian_handicap set handicap_name=':handicap_name',country=':country',home_discount=':home_discount',let_index=':let_index',visit_discount=':visit_discount',modify_time='" . date("Y-m-d H:i:s") . "',home_discount_trend=':home_discount_trend',visit_discount_trend=':visit_discount_trend' where company_name=':company_name' and schedule_mid=':schedule_mid' and handicap_type=':handicap_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $str;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select(["schedule_mid", "company_name", "handicap_type"])->from("bd_asian_handicap")->where(["in", "CONCAT(`schedule_mid`,'_' ,`company_name`,'_',`handicap_type`)", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            $hasMids[] = $str;
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $hasMids)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= Commonfun::strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "亚盘数据处理失败");
        }
        return $this->jsonResult(600, "亚盘数据处理成功", $ret);
    }

    /**
     * 欧赔数据接入 
     * @return json
     */
    public function actionBdEuropeOddsData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into bd_europe_odds (schedule_mid,company_name,country,handicap_type,handicap_name,odds_3,odds_1,odds_0,create_time,odds_3_trend,odds_1_trend,odds_0_trend)values(':schedule_mid',':company_name',':country',':handicap_type',':handicap_name',':odds_3',':odds_1',':odds_0','" . date("Y-m-d H:i:s") . "',':odds_3_trend',':odds_1_trend',':odds_0_trend');";
        $updateTemp = "update bd_europe_odds set handicap_name=':handicap_name',country=':country',odds_3=':odds_3',odds_1=':odds_1',odds_0=':odds_0',modify_time='" . date("Y-m-d H:i:s") . "',odds_3_trend=':odds_3_trend',odds_1_trend=':odds_1_trend',odds_0_trend=':odds_0_trend' where company_name=':company_name' and schedule_mid=':schedule_mid' and handicap_type=':handicap_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $str;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select(["schedule_mid", "company_name", "handicap_type"])->from("bd_europe_odds")->where(["in", "CONCAT(`schedule_mid`,'_' ,`company_name`,'_',`handicap_type`)", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            $hasMids[] = $str;
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $hasMids)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= Commonfun::strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "欧赔数据处理失败");
        }
        return $this->jsonResult(600, "欧赔数据处理成功", $ret);
    }

    /**
     * 赛事预测
     * @return json
     */
    public function actionBdPreResultData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTempStr = "insert into bd_pre_result (schedule_mid,pre_result_title,pre_result_3010,pre_result_3007,confidence_index,expert_analysis,create_time)values(':schedule_mid',':pre_result_title',':pre_result_3010',':pre_result_3007',':confidence_index',':expert_analysis','" . date("Y-m-d H:i:s") . "');";
        $updateTempStr = "update bd_pre_result set pre_result_title=':pre_result_title',pre_result_3010=':pre_result_3010',pre_result_3007=':pre_result_3007',confidence_index=':confidence_index',expert_analysis=':expert_analysis',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasPreResult = (new \yii\db\Query())->select("schedule_mid")->from("bd_pre_result")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasPreResult as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .= Commonfun::strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .= Commonfun::strTemplateReplace($insertTempStr, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程赛果预测数据处理失败");
        }
        return $this->jsonResult(600, "赛程赛果预测数据处理成功", $ret);
    }

    /**
     * 赛程事件数据接入
     * @return json
     */
    public function actionBdScheduleEventData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $teamTypes = [
            "1" => "主队",
            "2" => "客队"
        ];
        $eventTypes = [
            "1" => "进球",
            "2" => "点球",
            "3" => "乌龙球",
            "4" => "两黄一红",
            "5" => "换人",
            "6" => "黄牌",
            "7" => "红牌"
        ];
        $msg = "";
        $es = [];

        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_event_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_event_mid"];
            }
        }
        $hasEvents = (new Query())->select("schedule_event_mid")->from("bd_schedule_event")->where(["in", "schedule_event_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasEvents as $val) {
            $hasMids[] = $val["schedule_event_mid"];
        }

        foreach ($data as $val) {
            if (in_array($val["schedule_event_mid"], $hasMids)) {
                $scheduleEvent = BdScheduleEvent::findOne(["schedule_event_mid" => $val["schedule_event_mid"]]);
            } else {
                $scheduleEvent = new BdScheduleEvent();
                $scheduleEvent->schedule_event_mid = $val["schedule_event_mid"];
            }
            $scheduleEvent->schedule_mid = $this->arrParameter($val, 'schedule_mid');
            $scheduleEvent->team_type = $this->arrParameter($val, 'team_type');
            $scheduleEvent->event_type = $this->arrParameter($val, 'event_type');
            $scheduleEvent->event_type_name = isset($eventTypes[$scheduleEvent->event_type]) ? $eventTypes[$scheduleEvent->event_type] : ($this->jsonError(109, "event_type参数错误"));
            $scheduleEvent->team_name = isset($teamTypes[$scheduleEvent->team_type]) ? $teamTypes[$scheduleEvent->team_type] : ($this->jsonError(109, "team_type参数错误"));
            $scheduleEvent->event_content = $this->arrParameter($val, 'event_content');
            $scheduleEvent->event_time = $this->arrParameter($val, 'event_time');
            $scheduleEvent->create_time = date("Y-m-d H:i:s");
            if ($scheduleEvent->validate()) {
                $ret = $scheduleEvent->save();
                if ($ret === false) {
                    $msg.="schedule_mid:" . $scheduleEvent->schedule_mid . "赛程事件数据插入失败;";
                }
                $msg.="schedule_mid:" . $scheduleEvent->schedule_mid . "赛程事件数据插入成功;";
            } else {
                $msg.="schedule_mid:" . $scheduleEvent->schedule_mid . "赛程事件数据插入失败;";
                $es[$scheduleEvent->schedule_mid] = $scheduleEvent->getFirstErrors();
            }
        }
        return $this->jsonResult(600, $msg, $es);
    }

    /**
     * 赛程技术统计数据接入
     * @return json
     */
    public function actionBdScheduleTechnic() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $data = $data[0];
        $mid = Commonfun::arrParameter($data, "schedule_mid");
        $scheduleTechnic = BdScheduleTechnic::findOne(["schedule_mid" => $mid]);
        if ($scheduleTechnic == null) {
            $scheduleTechnic = new BdScheduleTechnic();
            $scheduleTechnic->schedule_mid = $mid;
            $scheduleTechnic->create_time = date("Y-m-d H:i:s");
        } else {
            $scheduleTechnic->modify_time = date("Y-m-d H:i:s");
        }
        $field = ['event_type', 'count(event_type_name) as total'];
        $homeEvent = BdScheduleEvent::find()->select($field)->where(['schedule_mid' => $mid, 'team_type' => 1])->andWhere(['in', 'event_type', [4, 6, 7]])->groupBy('event_type')->asArray()->all();
        $visitEvent = BdScheduleEvent::find()->select($field)->where(['schedule_mid' => $mid, 'team_type' => 2])->andWhere(['in', 'event_type', [4, 6, 7]])->groupBy('event_type')->asArray()->all();
        $homeR = 0;
        $homeY = 0;
        $visitR = 0;
        $visitY = 0;
        if (!empty($homeEvent)) {
            foreach ($homeEvent as $val) {
                if ($val['event_type'] == 6) {
                    $homeY = $val['total'];
                } else {
                    $homeR += $val['total'];
                }
            }
        }
        if (!empty($visitEvent)) {
            foreach ($visitEvent as $val) {
                if ($val['event_type'] == 6) {
                    $visitY = $val['total'];
                } else {
                    $visitR += $val['total'];
                }
            }
        }
        $scheduleTechnic->home_ball_rate = $this->arrParameter($data, "home_ball_rate");
        $scheduleTechnic->visit_ball_rate = $this->arrParameter($data, "visit_ball_rate");
        $scheduleTechnic->home_shoot_num = $this->arrParameter($data, "home_shoot_num");
        $scheduleTechnic->visit_shoot_num = $this->arrParameter($data, "visit_shoot_num");
        $scheduleTechnic->home_shoot_right_num = $this->arrParameter($data, "home_shoot_right_num");
        $scheduleTechnic->visit_shoot_right_num = $this->arrParameter($data, "visit_shoot_right_num");
        $scheduleTechnic->home_corner_num = $this->arrParameter($data, "home_corner_num");
        $scheduleTechnic->visit_corner_num = $this->arrParameter($data, "visit_corner_num");
        $scheduleTechnic->home_foul_num = $this->arrParameter($data, "home_foul_num");
        $scheduleTechnic->visit_foul_num = $this->arrParameter($data, "visit_foul_num");
        $scheduleTechnic->home_red_num = $homeR;
        $scheduleTechnic->home_yellow_num = $homeY;
        $scheduleTechnic->visit_red_num = $visitR;
        $scheduleTechnic->visit_yellow_num = $visitY;
        $scheduleTechnic->odds_3006 = $this->arrParameter($data, "odds_3006");
        $scheduleTechnic->odds_3007 = $this->arrParameter($data, "odds_3007");
        $scheduleTechnic->odds_3008 = $this->arrParameter($data, "odds_3008");
        $scheduleTechnic->odds_3009 = $this->arrParameter($data, "odds_3009");
        $scheduleTechnic->odds_3010 = $this->arrParameter($data, "odds_3010");
        if ($scheduleTechnic->validate()) {
            $ret = $scheduleTechnic->save();
            if ($ret === false) {
                return $this->jsonResult(109, "赛程技术统计数据插入失败", "");
            }
            return $this->jsonResult(600, "赛程技术统计数据插入成功", "");
        } else {
            return $this->jsonResult(109, "赛程技术统计数据插入失败", $scheduleTechnic->getFirstErrors());
        }
    }

    /**
     * 赛事提点
     * @return json
     */
    public function actionBdScheduleRemindData() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTempStr = "insert into bd_schedule_remind (schedule_mid,schedule_type,team_type,content,create_time)values(':schedule_mid',3,':team_type',':content','" . date("Y-m-d H:i:s") . "');";

        $sqlStr = "";
        foreach ($data as $val) {
            $sqlStr .= Commonfun::strTemplateReplace($insertTempStr, $val);
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程提点数据处理失败");
        }
        return $this->jsonResult(600, "赛程提点数据处理成功", $ret);
    }

    /**
     * 赛程初始化
     * @return json
     */
    public function actionBdScheduleInit() {
        $request = \Yii::$app->request;
        $json = $request->post("data", '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTempStr = "insert into bd_pre_result (schedule_mid,average_home_percent,average_visit_percent,json_data)values(':schedule_mid',':average_home_percent',':average_visit_percent',':json_data');";
        $updateTempStr = "update bd_pre_result set average_home_percent=':average_home_percent',average_visit_percent=':average_visit_percent',json_data=':json_data' where schedule_mid=':schedule_mid';";
        $insertTempStr1 = "insert into bd_history_count (schedule_mid,home_num_3,home_num_1,home_num_0,visit_num_3,visit_num_1,visit_num_0)values(':schedule_mid',':home_num_3',':home_num_1',':home_num_0',':visit_num_3',':visit_num_1',':visit_num_0');";
        $updateTempStr1 = "update bd_history_count set home_num_3=':home_num_3',home_num_1=':home_num_1',home_num_0=':home_num_0',visit_num_3=':visit_num_3',visit_num_1=':visit_num_1',visit_num_0=':visit_num_0' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val['schedule_mid'];
            }
        }
        $sql = "";
        $schedule = (new \yii\db\Query())->select(["home_code as home_team_mid", "visit_code as visit_team_mid", "open_mid schedule_mid", "start_time"])->from("bd_schedule")->where(["in", "open_mid", $mids])->all();
        $hasPreResult = (new \yii\db\Query())->select("schedule_mid")->from("bd_pre_result")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        $hasHistoryCount = (new \yii\db\Query())->select("schedule_mid")->from("bd_history_count")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        foreach ($schedule as $val) {
            $arr = [];
            $ret = $this->strengthContrast($val["home_team_mid"], $val["visit_team_mid"], $val["start_time"]);
            $arr["average_home_percent"] = $ret["avg_home_per"];
            $arr["average_visit_percent"] = $ret["avg_visit_per"];
            $arr["schedule_mid"] = $val["schedule_mid"];
            $arr["json_data"] = json_encode($ret, true);

            $arr["home_num_3"] = $ret["home"]["num_3"];
            $arr["home_num_1"] = $ret["home"]["num_1"];
            $arr["home_num_0"] = $ret["home"]["num_0"];
            $arr["visit_num_3"] = $ret["visit"]["num_3"];
            $arr["visit_num_1"] = $ret["visit"]["num_1"];
            $arr["visit_num_0"] = $ret["visit"]["num_0"];
            if (isset($hasPreResult[$val["schedule_mid"]])) {
                $sql.= Commonfun::strTemplateReplace($updateTempStr, $arr);
            } else {
                $sql.= Commonfun::strTemplateReplace($insertTempStr, $arr);
            }
            if (isset($hasHistoryCount[$val["schedule_mid"]])) {
                $sql.= Commonfun::strTemplateReplace($updateTempStr1, $arr);
            } else {
                $sql.= Commonfun::strTemplateReplace($insertTempStr1, $arr);
            }
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程初始化数据处理失败");
        }
        return $this->jsonResult(600, "赛程初始化数据处理成功", $ret);
    }

    /**
     * 交锋分析
     * @param type $homeTeamMid
     * @param type $visitTeamMid
     * @param type $time
     * @return type
     */
    public function strengthContrast($homeTeamMid, $visitTeamMid, $time) {
        $size = 10;
        $data = [];
        $homeData = (new Query())->select(["result_3007", "home_team_mid"])->from("bd_schedule_history")->where(["<", "play_time", $time])->andWhere(["or", ["home_team_mid" => $homeTeamMid], ["visit_team_mid" => $homeTeamMid]])->andWhere(["!=", "result_3007", ""])->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all(); //->createCommand()->getRawSql();
        $visitData = (new Query())->select(["result_3007", "home_team_mid"])->from("bd_schedule_history")->where(["<", "play_time", $time])->andWhere(["or", ["home_team_mid" => $visitTeamMid], ["visit_team_mid" => $visitTeamMid]])->andWhere(["!=", "result_3007", ""])->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $data["home"] = $this->scheduleHistoryDeal($homeData, $homeTeamMid);
        $data["visit"] = $this->scheduleHistoryDeal($visitData, $visitTeamMid);
        $homeInHomeData = (new Query())->select(["result_3007", "home_team_mid"])->from("bd_schedule_history")->where(["home_team_mid" => $homeTeamMid])->andWhere(["<", "play_time", $time])->andWhere(["!=", "result_3007", ""])->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $visitInVisitData = (new Query())->select(["result_3007", "home_team_mid"])->from("bd_schedule_history")->where(["visit_team_mid" => $visitTeamMid])->andWhere(["<", "play_time", $time])->andWhere(["!=", "result_3007", ""])->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $data["homeInHome"] = $this->scheduleHistoryDeal($homeInHomeData, $homeTeamMid, false);
        $data["visitInVisit"] = $this->scheduleHistoryDeal($visitInVisitData, $visitTeamMid, false);
        $totalIntegral1 = $data["visit"]["integral"] + $data["home"]["integral"];
        $data["per1_h"] = $totalIntegral1 == 0 ? 50 : round($data["home"]["integral"] / $totalIntegral1 * 100, 1);
        $data["per1_v"] = $totalIntegral1 == 0 ? 50 : (100 - $data["per1_h"]);
        $totalIntegral2 = $data["homeInHome"]["integral"] + $data["visitInVisit"]["integral"];
        $data["per2_h"] = $totalIntegral2 == 0 ? 50 : round($data["homeInHome"]["integral"] / $totalIntegral2 * 100, 1);
        $data["per2_v"] = $totalIntegral2 == 0 ? 50 : (100 - $data["per2_h"]);
        $totalBalls1 = $data["home"]["avg_gain_balls"] + $data["visit"]["avg_gain_balls"];
        $data["per3_h"] = $totalBalls1 == 0 ? 50 : round($data["home"]["avg_gain_balls"] / $totalBalls1 * 100, 1);
        $data["per3_v"] = $totalBalls1 == 0 ? 50 : (100 - $data["per3_h"]);
        $totalBalls2 = $data["home"]["avg_lose_balls"] + $data["visit"]["avg_lose_balls"];
        $data["per4_v"] = $totalBalls2 == 0 ? 50 : round($data["home"]["avg_lose_balls"] / $totalBalls2 * 100, 1);
        $data["per4_h"] = $totalBalls2 == 0 ? 50 : (100 - $data["per4_v"]);
        $total = $data["per1_h"] + $data["per2_h"] + $data["per3_h"] + $data["per4_h"] + $data["per1_v"] + $data["per2_v"] + $data["per3_v"] + $data["per4_v"];
        $data["avg_home_per"] = round(($data["per1_h"] + $data["per2_h"] + $data["per3_h"] + $data["per4_h"]) / ($total / 100), 1);
        $data["avg_visit_per"] = sprintf("%.1f", 100 - $data["avg_home_per"]);
        return $data;
    }

    /**
     * 赛程历史处理
     * @auther GL ctx
     * @return array
     */
    public function scheduleHistoryDeal($data, $mid, $hasAvg = true) {
        $result = [];
        $count = count($data);
        $num_3 = 0;
        $num_1 = 0;
        $num_0 = 0;
        $gainBalls = 0;
        $loseBalls = 0;
        foreach ($data as $val) {
            $arr = explode(":", $val["result_3007"]);
            if (count($arr) != 2) {
                continue;
            }
            if ($mid == $val["home_team_mid"]) {
                $homeJq = $arr[0];
                $visitJq = $arr[1];
            } else {
                $homeJq = $arr[1];
                $visitJq = $arr[0];
            }
            $gainBalls+=$homeJq;
            $loseBalls+=$visitJq;
            if ($homeJq > $visitJq) {
                $num_3++;
            } else if ($homeJq == $visitJq) {
                $num_1++;
            } else {
                $num_0++;
            }
        }
        $result["num_3"] = $num_3;
        $result["num_1"] = $num_1;
        $result["num_0"] = $num_0;
        $result["integral"] = $num_3 * 3 + $num_1;
        if ($hasAvg == true) {
            $result["avg_gain_balls"] = $count == 0 ? 0 : number_format($gainBalls / $count, 1);
            $result["avg_lose_balls"] = $count == 0 ? 0 : number_format($loseBalls / $count, 1);
        }
        return $result;
    }

    /**
     * 北单赛程结果写入
     * @return type
     */
    public function actionBdUpdateData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(101, '参数缺失');
        }
        $jsonData = json_decode($json, true);
        $data = $jsonData[0];
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $periods = Commonfun::arrParameter($data, 'periods');
        $openMid = Commonfun::arrParameter($data, 'open_mid');
        $playType = Commonfun::arrParameter($data, 'play_type');
        $bdSort = Commonfun::arrParameter($data, 'bd_sort');

        $bdScheduleResult = BdScheduleResult::findOne(['periods' => $periods, 'open_mid' => $openMid, 'play_type' => $playType, 'bd_sort' => $bdSort]);
        if (empty($bdScheduleResult)) {
            return \Yii::jsonError(109, '该赛程不存在');
        }
        if ($bdScheduleResult->status == 2) {
            return \Yii::jsonError(109, '该赛程已有开奖结果！');
        }
        $status = Commonfun::arrParameter($data, 'status');
        $bdScheduleResult->status = $status;
        $bdScheduleResult->modify_time = date('Y-m-d H:i:s');
        if (!$bdScheduleResult->save()) {
            return $this->jsonResult(109, '赛程结果数据处理失败', $bdScheduleResult->getFirstErrors());
        }
        if ($status == 3) {
            $winning = new Winning();
            $winning->bdCancelLevel($openMid);
        }
        return $this->jsonResult(600, '赛程结果数据处理成功', true);
    }

}
