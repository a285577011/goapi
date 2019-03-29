<?php

namespace app\modules\cron\controllers;

use yii\web\Controller;
use yii\db\Query;
use app\modules\competing\models\LanSchedule;
use app\modules\common\models\League;
use app\modules\common\models\Team;
use Yii;
use app\modules\common\helpers\Winning;
use app\modules\competing\models\LanLeagueTeam;
use app\modules\common\helpers\Commonfun;
use app\modules\common\helpers\ArticleRed;
use app\modules\competing\models\LanScheduleResult;
use app\modules\experts\models\ArticlesPeriods;
use app\modules\common\services\ProgrammeService;


class LanDataController extends Controller {

    /**
     * 赛事数据接入
     * @return json
     */
    public function actionLanScheduleData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTempStr = "insert into lan_schedule (schedule_code,schedule_mid,schedule_date,league_id,league_name,visit_short_name,home_short_name,home_team_id,visit_team_id,start_time,beginsale_time,endsale_time,schedule_status,schedule_sf,schedule_rfsf,schedule_dxf,schedule_sfc,high_win_status,hot_status,create_time,open_mid)values(':schedule_code',':schedule_mid',:schedule_date,:league_id,':league_name',':visit_short_name',':home_short_name',:home_team_id,:visit_team_id,':start_time',':beginsale_time',':endsale_time',':schedule_status',':schedule_sf',':schedule_rfsf',':schedule_dxf',':schedule_sfc',':high_win_status',':hot_status','" . date("Y-m-d H:i:s") . "',':open_mid');";
        $updateTempStr = "update lan_schedule set schedule_code=':schedule_code',schedule_date=:schedule_date,league_id=:league_id,league_name=':league_name',visit_short_name=':visit_short_name',home_short_name=':home_short_name',home_team_id=:home_team_id,visit_team_id=:visit_team_id,start_time=':start_time',beginsale_time=':beginsale_time',endsale_time=':endsale_time',schedule_status=':schedule_status',schedule_sf=':schedule_sf',schedule_rfsf=':schedule_rfsf',schedule_dxf=':schedule_dxf',schedule_sfc=':schedule_sfc',high_win_status=:high_win_status,hot_status=:hot_status,modify_time='" . date("Y-m-d H:i:s") . "',open_mid=':open_mid' where schedule_mid=':schedule_mid';";
        $mids = [];
        $teamCodes = [];
//        $leagueCodes = [];
        $leagueShortNames = [];
        $msg = "";
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
                $msg.=$val["schedule_mid"] . "赛程重复；";
            } else {
                $mids[] = $val["schedule_mid"];
            }
            $leagueShortNames[] = $val["league_name"];
            $teamCodes[] = $val['visit_name'];
            $teamCodes[] = $val['home_name'];
        }
        $leagues = League::find()->select("league_code,league_id,league_short_name")->where(['in', "league_short_name", $leagueShortNames])->andWhere(['league_type' => 2])->asArray()->one();
        $leagueTeam = LanLeagueTeam::find()->select(['lan_team_id'])->where(['lan_league_id' => $leagues['league_id']])->indexBy('lan_team_id')->asArray()->all();
        $teamIds = array_keys($leagueTeam);
        $teams = Team::find()->select("team_code,team_id,team_short_name")->where(["in", "team_short_name", $teamCodes])->andWhere(['in', 'team_id', $teamIds])->andWhere(['team_type' => 2])->indexBy("team_short_name")->asArray()->all();
        $hasSchedule = LanSchedule::find()->select("schedule_mid")->where(["in", "schedule_mid", $mids])->asArray()->all();
        $hasMids = [];
        foreach ($hasSchedule as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $key => $val) {
            if (!isset($teams[$val["home_name"]])) {
                $msg.=$val["home_name"] . "球队不存在；";
                continue;
            }
            if (!isset($teams[$val["visit_name"]])) {
                $msg.=$val["visit_name"] . "球队不存在；";
                continue;
            }

            if ($leagues['league_short_name'] != $val['league_name']) {
                $msg.=$val["schedule_mid"] . "联赛名不存在；";
                continue;
            }
            if (!isset($val["schedule_date"]) || empty($val["schedule_date"])) {
                $val["schedule_date"] = date("Ymd", strtotime($val["start_time"]));
            }
            $val["home_team_id"] = $teams[$val["home_name"]]["team_code"];
            $val["visit_team_id"] = $teams[$val["visit_name"]]["team_code"];
            $val["visit_short_name"] = $teams[$val["visit_name"]]["team_short_name"];
            $val["home_short_name"] = $teams[$val["home_name"]]["team_short_name"];
            $val["league_id"] = $leagues["league_code"];
            $val['league_name'] = $leagues['league_short_name'];
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTempStr, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret == false) {
            return $this->jsonError(109, $msg . "赛程数据处理失败。");
        }
        $schedules = LanSchedule::find()->select("schedule_mid,schedule_date")->where(["in", "schedule_mid", $mids])->asArray()->all();
        $scheduleResults = (new \yii\db\Query())->select("schedule_mid")->from("lan_schedule_result")->where(["in", "schedule_mid", $mids])->all();
        $existScheduleMid = [];
        foreach ($scheduleResults as $val) {
            $existScheduleMid[] = $val["schedule_mid"];
        }
        $temp = "insert into lan_schedule_result (schedule_mid,schedule_date,create_time)values(':schedule_mid',:schedule_date,'" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_schedule_result set schedule_date=:schedule_date where schedule_mid=':schedule_mid'";
        $sql = "";
        foreach ($schedules as $val) {
            if (!in_array($val['schedule_mid'], $existScheduleMid)) {
                $sql .=$this->strTemplateReplace($temp, $val);
            } else {
                $sql .=$this->strTemplateReplace($updateTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, $msg . "赛程数据处理失败。");
        }
        if ($msg == "") {
            return $this->jsonResult(600, "赛程数据处理成功", $ret);
        } else {
            return $this->jsonResult(109, $msg, $ret);
        }
    }

    /**
     * 赛程结果数据接入
     * @return json
     */
    public function actionLanScheduleResultData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $updateTempStr1 = "update lan_schedule_result set guest_one=':guest_one',guest_two=':guest_two',guest_three=':guest_three',guest_four=':guest_four',guest_add_one=':guest_add_one',guest_add_two=':guest_add_two',guest_add_three=':guest_add_three',guest_add_four=':guest_add_four',result_zcbf=':result_zcbf',result_qcbf=':result_qcbf',schedule_fc=':schedule_fc',schedule_zf=':schedule_zf',match_time=':match_time',result_status=:status,modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid' and result_status not in (2,4,6);";
        $updateTempStr2 = "update lan_schedule_result set result_3001=':result_3001',result_3002=':result_3002',result_3003=':result_3003',result_3004=':result_3004',result_qcbf=':result_qcbf',odds_3001=':odds_3001',odds_3002=':odds_3002',odds_3003=':odds_3003',odds_3004=':odds_3004',schedule_fc=':schedule_fc',schedule_zf=':schedule_zf',match_time=':match_time',result_status=':status',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";

        $sqlStr = "";
        $msg = "";


        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasResults = (new Query())->select("schedule_mid")->from("lan_schedule_result")->where(["in", "schedule_mid", $mids])->andWhere(["result_status" => 2])->all();
        $hasMids = [];
        foreach ($hasResults as $val) {
            $hasMids[] = $val["schedule_mid"];
        }

        foreach ($data as $val) {
            if ($val["status"] == 1) {
                $sqlStr .=$this->strTemplateReplace($updateTempStr1, $val);
            } else if ($val["status"] == 2) {
                $sqlStr .=$this->strTemplateReplace($updateTempStr2, $val);
            } else {
                $msg.=$val["schedule_mid"] . "赛程赛果数据状态出错；";
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, $msg . "赛程结果数据处理失败");
        }
        foreach ($data as $val) {
            if ($val["status"] == 2 && !in_array($val["schedule_mid"], $hasMids)) {
                $winning = new Winning();
                $bf = explode(':', $val['result_qcbf']);
                $winning->basketballLevel($val["schedule_mid"], $bf[0], $bf[1]);
                $articleRed = new ArticleRed();
                $articleRed->articleLanPreResult($val["schedule_mid"], $bf[0], $bf[1]);
            }
        }
        return $this->jsonResult(600, $msg . "赛程结果数据处理成功", $ret);
    }

    /**
     * 胜负赔率数据接入
     * @return json
     */
    public function actionOdds3001Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $mids = [];
        $temp = "insert into odds_3001 (schedule_mid,update_nums,wins_3001,wins_trend,lose_3001,lose_trend,create_time)values(':schedule_mid',:update_nums,':wins_3001',:wins_trend,:lose_3001,:lose_trend,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $oddsNums = (new \yii\db\Query())->select(["schedule_mid", "MAX(update_nums) as update_nums"])->from("odds_3001")->where(["in", "schedule_mid", $mids])->groupBy("")->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["update_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["update_nums"] ++;
            }
            $val["update_nums"] = $oddsNums[$val["schedule_mid"]]["update_nums"];
            $sql.=$this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3001赔率数据处理失败");
        }

        return $this->jsonResult(600, "3001赔率数据处理成功", $ret);
    }

    /**
     * 让分胜负赔率数据接入
     * @return json
     */
    public function actionOdds3002Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $mids = [];
        $temp = "insert into odds_3002 (schedule_mid,update_nums,rf_nums,wins_3002,wins_trend,lose_3002,lose_trend,create_time)values(':schedule_mid',:update_nums,:rf_nums,':wins_3002',:wins_trend,:lose_3002,:lose_trend,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $oddsNums = (new \yii\db\Query())->select(["schedule_mid", "MAX(update_nums) as update_nums"])->from("odds_3002")->where(["in", "schedule_mid", $mids])->groupBy("")->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["update_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["update_nums"] ++;
            }
            $val["update_nums"] = $oddsNums[$val["schedule_mid"]]["update_nums"];
            $sql.=$this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3002赔率数据处理失败");
        }

        return $this->jsonResult(600, "3002赔率数据处理成功", $ret);
    }

    /**
     * 胜分差赔率数据接入
     * @return json
     */
    public function actionOdds3003Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $mids = [];
        $temp = "insert into odds_3003 (schedule_mid,update_nums,cha_01,cha_01_trend,cha_02,cha_02_trend,cha_03,cha_03_trend,cha_04,cha_04_trend,cha_05,cha_05_trend,cha_06,cha_06_trend,cha_11,cha_11_trend,cha_12,cha_12_trend,cha_13,cha_13_trend,cha_14,cha_14_trend,cha_15,cha_15_trend,cha_16,cha_16_trend,create_time)values(':schedule_mid',:update_nums,:cha_01,:cha_01_trend,:cha_02,:cha_02_trend,:cha_03,:cha_03_trend,:cha_04,:cha_04_trend,:cha_05,:cha_05_trend,:cha_06,:cha_06_trend,:cha_11,:cha_11_trend,:cha_12,:cha_12_trend,:cha_13,:cha_13_trend,:cha_14,:cha_14_trend,:cha_15,:cha_15_trend,:cha_16,:cha_16_trend,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $oddsNums = (new \yii\db\Query())->select(["schedule_mid", "MAX(update_nums) as update_nums"])->from("odds_3003")->where(["in", "schedule_mid", $mids])->groupBy("")->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["update_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["update_nums"] ++;
            }
            $val["update_nums"] = $oddsNums[$val["schedule_mid"]]["update_nums"];
            $sql.=$this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3003赔率数据处理失败");
        }

        return $this->jsonResult(600, "3003赔率数据处理成功", $ret);
    }

    /**
     * 胜分差赔率数据接入
     * @return json
     */
    public function actionOdds3004Data() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $mids = [];
        $temp = "insert into odds_3004 (schedule_mid,update_nums,fen_cutoff,da_3004,da_3004_trend,xiao_3004,xiao_3004_trend,create_time)values(':schedule_mid',:update_nums,:fen_cutoff,:da_3004,:da_3004_trend,:xiao_3004,:xiao_3004_trend,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $oddsNums = (new \yii\db\Query())->select(["schedule_mid", "MAX(update_nums) as update_nums"])->from("odds_3004")->where(["in", "schedule_mid", $mids])->groupBy("")->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["update_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["update_nums"] ++;
            }
            $val["update_nums"] = $oddsNums[$val["schedule_mid"]]["update_nums"];
            $sql.=$this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3004赔率数据处理失败");
        }

        return $this->jsonResult(600, "3004赔率数据处理成功", $ret);
    }

    /**
     * sql语句模板数据嵌入
     * @param string $tempStr
     * @param array $data
     * @return string
     */
    public function strTemplateReplace($tempStr, $data) {
        $arr = [];
        foreach ($data as $key => $val) {
            $arr[":" . $key] = $val;
        }
        $result = strtr($tempStr, $arr);
        return $result;
    }

    /**
     * 赛事提点
     * @return json
     */
    public function actionLanScheduleRemindData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTempStr = "insert into schedule_remind (schedule_mid,schedule_type,team_type,content,create_time)values(':schedule_mid',2,':team_type',':content','" . date("Y-m-d H:i:s") . "');";

        $sqlStr = "";
        foreach ($data as $val) {
            $sqlStr .=$this->strTemplateReplace($insertTempStr, $val);
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程提点数据处理失败");
        }
        return $this->jsonResult(600, "赛程提点数据处理成功", $ret);
    }

    /**
     * 赛程历史数据接入 
     * @return json
     */
    public function actionLanScheduleHistoryData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_schedule_history (data_type,schedule_mid,league_name,league_code,play_time,home_team_code,home_team_name,visit_team_code,visit_team_name,schedule_bf,schedule_sf_nums,result_3001,result_3002,rf_nums,cutoff_nums,create_time)values(':data_type',':schedule_mid',':league_name',':league_code',':play_time',':home_team_mid',':home_team_name',':visit_team_mid',':visit_team_name',':schedule_bf',':schedule_sf_nums',':result_3001',':result_3002',':schedule_rf_nums',':cutoff_nums','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_schedule_history set league_code=':league_code',league_name=':league_name',play_time=':play_time',home_team_code=':home_team_mid',home_team_name=':home_team_name',visit_team_code=':visit_team_mid',visit_team_name=':visit_team_name',schedule_bf=':schedule_bf',schedule_sf_nums=':schedule_sf_nums',result_3001=':result_3001',result_3002=':result_3002',rf_nums=':schedule_rf_nums',cutoff_nums=':cutoff_nums',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasScheduleHistory = (new \yii\db\Query())->select("schedule_mid")->from("lan_schedule_history")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasScheduleHistory as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
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
    public function actionLanHistoryCountData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_history_count (schedule_mid,clash_nums,win_nums,lose_nums,home_team_rank,visit_team_rank,scale_3001_3,scale_3001_0,scale_3002_3,scale_3002_0,europe_odds_3,europe_odds_0,create_time)values(':schedule_mid',':clash_nums',':win_nums',':lose_nums',':home_team_rank',':visit_team_rank',':scale_3001_3',':scale_3001_0',':scale_3002_3',':scale_3002_0',':europe_odds_3',':europe_odds_0','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_history_count set clash_nums=':clash_nums',win_nums=':win_nums',lose_nums=':lose_nums',home_team_rank=':home_team_rank',visit_team_rank=':visit_team_rank',scale_3001_3=':scale_3001_3',scale_3001_0=':scale_3001_0',scale_3002_3=':scale_3002_3',scale_3002_0=':scale_3002_0',europe_odds_3=':europe_odds_3',europe_odds_0=':europe_odds_0',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select("schedule_mid")->from("lan_history_count")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程历史总计数据处理失败");
        }
        return $this->jsonResult(600, "赛程历史总计数据处理成功", $ret);
    }

    /**
     * 赛事预测
     * @return json
     */
    public function actionLanPreResultData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTempStr = "insert into lan_pre_result (schedule_mid,pre_result_title,pre_result_3001,pre_result_3002,pre_result_3004,confidence_index,expert_analysis,create_time)values(':schedule_mid',':pre_result_title',':pre_result_3001',':pre_result_3002',':pre_result_3004',':confidence_index',':expert_analysis','" . date("Y-m-d H:i:s") . "');";
        $updateTempStr = "update lan_pre_result set pre_result_title=':pre_result_title',pre_result_3001=':pre_result_3001',pre_result_3002=':pre_result_3002',pre_result_3004=':pre_result_3004',confidence_index=':confidence_index',expert_analysis=':expert_analysis',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasPreResult = (new \yii\db\Query())->select("schedule_mid")->from("lan_pre_result")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasPreResult as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTempStr, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程赛果预测数据处理失败");
        }
        return $this->jsonResult(600, "赛程赛果预测数据处理成功", $ret);
    }

    /**
     * 欧赔数据接入 
     * @return json
     */
    public function actionLanEuropeOddsData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_europe_odds (schedule_mid,company_name,country,handicap_type,handicap_name,odds_3,odds_3_trend,odds_0,odds_0_trend,profit_rate,create_time)values(':schedule_mid',':company_name',':country',':handicap_type',':handicap_name',':odds_3',':odds_3_trend',':odds_0',':odds_0_trend',':profit_rate','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_europe_odds set handicap_name=':handicap_name',country=':country',odds_3=':odds_3',odds_3_trend=':odds_3_trend',odds_0=':odds_0',odds_0_trend=':odds_0_trend',profit_rate=':profit_rate',modify_time='" . date("Y-m-d H:i:s") . "' where company_name=':company_name' and schedule_mid=':schedule_mid' and handicap_type=':handicap_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $str;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select(["schedule_mid", "company_name", "handicap_type"])->from("lan_europe_odds")->where(["in", "CONCAT(`schedule_mid`,'_' ,`company_name`,'_',`handicap_type`)", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            $hasMids[] = $str;
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "欧赔数据处理失败");
        }
        return $this->jsonResult(600, "欧赔数据处理成功", $ret);
    }

    /**
     * 让分赔率数据接入 
     * @return json
     */
    public function actionLanRangfenOddsData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_rangfen_odds (schedule_mid,company_name,country,handicap_type,handicap_name,rf_nums,odds_3,odds_3_trend,odds_0,odds_0_trend,profit_rate,create_time)values(':schedule_mid',':company_name',':country',':handicap_type',':handicap_name',':rf_nums',':odds_3',':odds_3_trend',':odds_0',':odds_0_trend',':profit_rate','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_rangfen_odds set handicap_name=':handicap_name',country=':country',rf_nums=':rf_nums',odds_3=':odds_3',odds_3_trend=':odds_3_trend',odds_0=':odds_0',odds_0_trend=':odds_0_trend',profit_rate=':profit_rate',modify_time='" . date("Y-m-d H:i:s") . "' where company_name=':company_name' and schedule_mid=':schedule_mid' and handicap_type=':handicap_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $str;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select(["schedule_mid", "company_name", "handicap_type"])->from("lan_rangfen_odds")->where(["in", "CONCAT(`schedule_mid`,'_' ,`company_name`,'_',`handicap_type`)", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            $hasMids[] = $str;
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "让分赔率数据处理失败");
        }
        return $this->jsonResult(600, "让分赔率数据处理成功", $ret);
    }

    /**
     * 大小分赔率数据接入 
     * @return json
     */
    public function actionLanDaxiaoOddsData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_daxiao_odds (schedule_mid,company_name,country,handicap_type,handicap_name,cutoff_fen,odds_da,odds_da_trend,odds_xiao,odds_xiao_trend,profit_rate,create_time)values(':schedule_mid',':company_name',':country',':handicap_type',':handicap_name',':cutoff_fen',':odds_da',':odds_da_trend',':odds_xiao',':odds_xiao_trend',':profit_rate','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_daxiao_odds set handicap_name=':handicap_name',country=':country',cutoff_fen=':cutoff_fen',odds_da=':odds_da',odds_da_trend=':odds_da_trend',odds_xiao=':odds_xiao',odds_xiao_trend=':odds_xiao_trend',profit_rate=':profit_rate',modify_time='" . date("Y-m-d H:i:s") . "' where company_name=':company_name' and schedule_mid=':schedule_mid' and handicap_type=':handicap_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $str;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select(["schedule_mid", "company_name", "handicap_type"])->from("lan_daxiao_odds")->where(["in", "CONCAT(`schedule_mid`,'_' ,`company_name`,'_',`handicap_type`)", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            $hasMids[] = $str;
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "大小赔率数据处理失败");
        }
        return $this->jsonResult(600, "大小赔率数据处理成功", $ret);
    }

    /**
     * 球队联赛排名数据接入
     * @return json
     */
    public function actionLanRankData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_team_rank (team_code,team_name,league_code,league_name,team_position,team_rank,game_nums,win_nums,lose_nums,win_rate,wins_diff,defen_nums,shifen_nums,home_result,"
                . "visit_result,east_result,west_result,same_result,ten_result,near_result,create_time)values(':team_code',':team_name',':league_code',':league_name',':team_position',':team_rank',"
                . "':game_nums',':win_nums',':lose_nums',':win_rate',':wins_diff',':defen_nums',':shifen_nums',':home_result',':visit_result',':east_result',':west_result',':same_result',':ten_result',':near_result','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_team_rank set team_position=':team_position', team_rank=':team_rank',game_nums=':game_nums',win_nums=':win_nums',lose_nums=':lose_nums',win_rate=':win_rate',wins_diff=':wins_diff',defen_nums=':defen_nums',"
                . "shifen_nums=':shifen_nums',home_result=':home_result',visit_result=':visit_result',east_result=':east_result',west_result=':west_result',same_result=':same_result',ten_result=':ten_result',"
                . "near_result=':near_result',modify_time='" . date("Y-m-d H:i:s") . "' where team_code=':team_code' and league_code=':league_code';";
        $codeArr = [];
        foreach ($data as $key => $val) {
            $teamCode = $val['team_code'];
            if (in_array($teamCode, $codeArr)) {
                unset($data[$key]);
            } else {
                $codeArr[] = $teamCode;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select("team_code")->from("lan_team_rank")->where(["in", "team_code", $codeArr])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $hasMids[] = $val["team_code"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["team_code"], $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "球队联赛排名数据处理失败");
        }
        return $this->jsonResult(600, "球队联赛排名数据处理成功", $ret);
    }

    /**
     * 篮球比赛球队统计
     * @return json
     */
    public function actionLanCountData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_schedule_count (schedule_mid,home_shots,visit_shots,home_three_point,visit_three_point,home_penalty,visit_penalty,home_rebound,visit_rebound,home_assist,visit_assist,home_steals,visit_steals,home_cap,"
                . "visit_cap,home_foul,visit_foul,home_all_miss,visit_all_miss,create_time)values(':schedule_mid',':home_shots',':visit_shots',':home_three_point',':visit_three_point',':home_penalty',"
                . "':visit_penalty',':home_rebound',':visit_rebound',':home_assist',':visit_assist',':home_steals',':visit_steals',':home_cap',':visit_cap',':home_foul',':visit_foul',':home_all_miss',':visit_all_miss','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_schedule_count set home_shots=':home_shots', visit_shots=':visit_shots',home_three_point=':home_three_point',visit_three_point=':visit_three_point',home_penalty=':home_penalty',visit_penalty=':visit_penalty',home_rebound=':home_rebound',visit_rebound=':visit_rebound',"
                . "home_assist=':home_assist',visit_assist=':visit_assist',home_steals=':home_steals',visit_steals=':visit_steals',home_cap=':home_cap',visit_cap=':visit_cap',home_foul=':home_foul',"
                . "visit_foul=':visit_foul',home_all_miss=':home_all_miss',visit_all_miss=':visit_all_miss',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid'";
        $codeArr = [];
        foreach ($data as $key => $val) {
            $schedule = $val['schedule_mid'];
            if (in_array($schedule, $codeArr)) {
                unset($data[$key]);
            } else {
                $codeArr[] = $schedule;
            }
        }
        $teamCode = LanSchedule::find()->select(['schedule_mid'])->where(['in', 'schedule_mid', $codeArr])->asArray()->one();
        if (empty($teamCode)) {
            return $this->jsonError(109, '该赛程不存在！！');
        }
        $hasHistoryCount = (new \yii\db\Query())->select("schedule_mid")->from("lan_schedule_count")->where(["in", "schedule_mid", $codeArr])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .=$this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "赛程实况数据处理失败");
        }
        return $this->jsonResult(600, "赛程实况数据处理成功", $ret);
    }

    /**
     * 篮球比赛球员统计
     * @return json
     */
    public function actionLanPlayerCountData() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_player_count (schedule_mid,team_code,player_name,play_time,shots_nums,rebound_nums,assist_nums,foul_nums,score,create_time)"
                . "values(':schedule_mid',':team_code',':player_name',':play_time',':shots_nums',':rebound_nums',':assist_nums',':foul_nums',':score','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update lan_player_count set player_name=':player_name', play_time=':play_time',shots_nums=':shots_nums',rebound_nums=':rebound_nums',assist_nums=':assist_nums',"
                . "foul_nums=':foul_nums',score=':score',modify_time='" . date("Y-m-d H:i:s") . "' where  schedule_mid=':schedule_mid' and team_code=':team_code' and  player_name=':player_name'";
        $codeArr = [];
        $playName = [];
        foreach ($data as $key => $val) {
            $teamCode = $val['schedule_mid'];
            $playerName = $val['player_name'];
            if (in_array($teamCode, $codeArr)) {
                unset($data[$key]);
            } else {
                $codeArr[] = $teamCode;
            }
            if (in_array($playerName, $playName)) {
                unset($data[$key]);
            } else {
                $playName[] = $playerName;
            }
            $mid = $val['schedule_mid'];
        }
        $teamCode = LanSchedule::find()->select(['home_team_id', 'visit_team_id'])->where(['schedule_mid' => $mid])->asArray()->one();
        if (empty($teamCode)) {
            return $this->jsonError(109, '该赛程不存在！！');
        }
        $hasHistoryCount = (new \yii\db\Query())->select(['player_name', 'team_code'])->from("lan_player_count")->where(['schedule_mid' => $mid])->andWhere(['in', 'team_code', [$teamCode['home_team_id'], $teamCode['visit_team_id']]])->indexBy('player_name')->all();
        $sqlStr = "";
        foreach ($data as &$val) {
            if (array_key_exists($val['player_name'], $hasHistoryCount)) {
                $val['team_code'] = $hasHistoryCount[$val['player_name']]['team_code'];
                $sqlStr .=$this->strTemplateReplace($updateTemp, $val);
            } else {
                if ($val['data_type'] == 1) {
                    $val['team_code'] = $teamCode['home_team_id'];
                } elseif ($val['data_type'] == 2) {
                    $val['team_code'] = $teamCode['visit_team_id'];
                } else {
                    return $this->jsonError(109, '球员信息有误');
                }
                $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "球员实况数据处理失败");
        }
        return $this->jsonResult(600, "球员实况数据处理成功", $ret);
    }

    /**
     * 篮球赛程文字直播
     * @return type
     */
    public function actionLanScheduleLive() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $insertTemp = "insert into lan_schedule_live (sort_id,schedule_mid,live_person,text_sub,game_time,create_time)values(':sort_id',':schedule_mid',':live_person',':text_sub',':game_time','" . date("Y-m-d H:i:s") . "');";
        foreach ($data as $val) {
            $mid = $val['schedule_mid'];
        }
        $schedule = LanSchedule::find()->select(['schedule_mid'])->where(['schedule_mid' => $mid])->asArray()->one();
        if (empty($schedule)) {
            return $this->jsonError(109, '该赛程不存在！！');
        }
        $sqlStr = "";
        foreach ($data as &$val) {
            $sqlStr .=$this->strTemplateReplace($insertTemp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "篮球赛程文字直播数据处理失败");
        }
        return $this->jsonResult(600, "篮球赛程文字直播数据处理成功", $ret);
    }

    /**
     * 赛程开停售
     * @return type
     */
    public function actionLanStopSale() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $mid = $data[0]["schedule_mid"];
        $schedule = LanSchedule::findOne(['schedule_mid' => $mid]);
        if (empty($schedule)) {
            return \Yii::jsonError(109, '该赛程不存在');
        }
        $status = $data[0]['status'];
        $schedule->schedule_status = $status;
        $schedule->modify_time = date('Y-m-d H:i:s');

        if ($schedule->validate()) {
            $ret = $schedule->save();
            if ($ret === false) {
                return \Yii::jsonError(109, '保存失败');
            }
            if($status == 1) {
                $title = '赛程重新开售';
            }  else {
                $title = '赛程停售';
            }
            Commonfun::sysAlert('篮球 - ' .$title, "通知", 'scheduleMid_LQ:' . $schedule->schedule_code . '(' . $schedule->schedule_mid . ')', "已处理", "请确认处理结果！");
            return $this->jsonError(600, '保存成功');
            
        } else {
            return \Yii::jsonResult(109, '保存失败', $schedule->getFirstErrors());
        }
    }
    
    /**
     * 赛程结果表单个字段更新
     * @return type
     */
    public function actionUpdateSingle() {
       $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        if(empty($data) || !is_array($data)) {
            return $this->jsonError(109, '数据格式有误');
        }
        $mid = Commonfun::arrParameter($data[0], "schedule_mid");
        $scheduleResult = LanScheduleResult::findOne(["schedule_mid" => $mid]);
        if (empty($scheduleResult)) {
            return \Yii::jsonError(109, '该赛程不存在');
        }
        if ($scheduleResult->result_status == 2 || $scheduleResult->result_status == 3) {
            return $this->jsonResult(600, '该赛程已完场', true);
        }

        $status = Commonfun::arrParameter($data[0], 'status');

        if ($scheduleResult->result_status == 4 && $status == 4) {
            return $this->jsonError(109, '该场次已推迟！！！');
        }
        if ($scheduleResult->result_status == 4 && $status == 0) {
            $schedule = LanSchedule::findOne(['schedule_mid' => $mid]);
            if (empty($schedule)) {
                return \Yii::jsonError(109, '该赛程不存在');
            }
            $startTime = Commonfun::arrParameter($data[0], 'start_time');
            $endTime = Commonfun::arrParameter($data[0], 'endsale_time');
            $schedule->start_time = $startTime;
            $schedule->endsale_time = $endTime;
            $schedule->modify_time = date('Y-m-d H:i:s');
            if (!$schedule->save()) {
                return \Yii::jsonError(109, '保存失败');
            }
//            ArticlesPeriods::updateAll(['start_time' => $startTime, 'endsale_time' => $endTime], ['and', ['periods' => $mid], ['in', 'lottery_code', [3001,3002]]]);
            $title = '篮球延迟赛事重新开售';
        } elseif ($status == 3) {
            $schedule = LanSchedule::findOne(['schedule_mid' => $mid]);
            if (empty($schedule)) {
                return \Yii::jsonError(109, '该赛程不存在');
            }
            $schedule->schedule_status = 2;
            $schedule->modify_time = date('Y-m-d H:i:s');
            if (!$schedule->save()) {
                return \Yii::jsonError(109, '保存失败');
            }
            ArticlesPeriods::updateAll(['status' => 4], ['and', ['periods' => $mid], ['in', 'lottery_code', [3001,3002]]]);
            $programmes = Programme::find()->select("programme_id")->where(["status" => 2])->andWhere(['between', 'lottery_code', 3001, 3005])->andWhere(["like", "bet_val", $mid])->asArray()->all();
            if (!empty($programmes)) {
                $programmeService = new ProgrammeService();
                foreach ($programmes as $val) {
                    $programmeService->outProgrammeFalse($val['programme_id'], 8, 'cancel');
                }
            }
            $articleRed = new ArticleRed();
            $articleRed->articleLanCancel($mid);
            $str = '3100_' . $mid;
            $key = 'cancel_schedule';
            $redis = \Yii::$app->redis;
            $redis->sadd($key, $str);
            $title = '篮球赛事取消';
        } elseif ($status == 4) {
            $schedule = LanSchedule::findOne(['schedule_mid' => $mid]);
            if (empty($schedule)) {
                return \Yii::jsonError(109, '该赛程不存在');
            }
            $oldEndSale = $schedule->endsale_time;
            $newEndSale = date('Y-m-d H:i:s', strtotime('+36 hours', strtotime($oldEndSale)));
            $schedule->endsale_time = $newEndSale;
            if (!$schedule->save()) {
                return \Yii::jsonError(109, '保存失败');
            }
            ArticlesPeriods::updateAll(['endsale_time' => $newEndSale], ['and', ['periods' => $mid], ['in', 'lottery_code', [3001,3002]]]);
            $title = '篮球赛事延迟';
        } elseif ($status == 7) {
            $schedule = LanSchedule::findOne(['schedule_mid' => $mid]);
            $title = '篮球赛事腰斩';
        }
        $scheduleResult->result_status = $status;
        $scheduleResult->modify_time = date('Y-m-d H:i:s');
        if ($scheduleResult->validate()) {
            $ret = $scheduleResult->save();
            if ($ret === false) {
                return \Yii::jsonError(109, '保存失败');
            }
            if (in_array($status, [0, 3, 4, 7])) {
                $sT = strtotime('00:30');
                $eT = strtotime('06:30');
                $nT = strtotime(date('H:i'));
                if ($nT > $sT && $nT < $eT) {
//                    return $this->jsonResult(600, '00:30-06:30时间段内不推送', true);
                } else {
                    Commonfun::sysAlert($title, "通知", 'scheduleMid_LQ:' . $schedule['schedule_code'] . '(' . $schedule['schedule_mid'] . ')', "已处理", "请确认处理结果！");
                }
            }
            return $this->jsonError(600, '保存成功');
        } else {
            return \Yii::jsonResult(109, '保存失败', $scheduleResult->getFirstErrors());
        }
    }
    
    /**
     * 更新赛程相关投注状态
     * @return type
     */
    public function actionUpdateSaleInfo() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        $scheduleMid = $this->arrParameter($data[0], 'schedule_mid');
        $schedule = LanSchedule::findOne(['schedule_mid' => $scheduleMid]);
        if (empty($schedule)) {
            return $this->jsonError(109, '该赛程不存在');
        } 
        
        $schedule->schedule_sf = $this->arrParameter($data[0], 'schedule_dxf');
        $schedule->schedule_rfsf = $this->arrParameter($data[0], 'schedule_rfsf');
        $schedule->schedule_sfc = $this->arrParameter($data[0], 'schedule_sfc');
        $schedule->schedule_dxf = $this->arrParameter($data[0], 'schedule_dxf');
        $schedule->modify_time = date('Y-m-d H:i:s');
        
        if($schedule->validate()) {
            if($schedule->save()) {
                return $this->jsonResult(600, '数据写入成功', true);
            }
            return $this->jsonResult(109, '数据写入失败', $schedule->errors);
        } else {
            return $this->jsonResult(109, '数据验证失败', $schedule->errors);
        }
    }

}
