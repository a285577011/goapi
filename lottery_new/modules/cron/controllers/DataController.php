<?php

namespace app\modules\cron\controllers;

use yii\web\Controller;
use app\modules\common\helpers\Commonfun;
use app\modules\common\models\League;
use app\modules\common\models\Team;
use app\modules\common\models\Schedule;
use app\modules\common\models\ScheduleEvent;
use app\modules\common\models\ScheduleTechnic;
use app\modules\common\models\FootballNine;
use app\modules\common\models\FootballFourteen;
use app\modules\common\models\ScheduleResult;
use yii\db\Query;
use app\modules\common\helpers\Winning;
use app\modules\common\models\OptionalSchedule;
use app\modules\common\services\AdditionalService;
use app\modules\common\helpers\ArticleRed;
use app\modules\common\helpers\Constants;
use app\modules\experts\models\ArticlesPeriods;
use app\modules\common\models\Programme;
use app\modules\common\services\ProgrammeService;
use app\modules\common\helpers\TrendFall;
use app\modules\common\models\LotteryRecord;
use app\modules\common\models\GroupTrendChart;
use app\modules\competing\models\MatchNotice;
use app\modules\competing\models\LanSchedule;
use app\modules\common\helpers\Jpush;
use app\modules\common\helpers\Trend;
use app\modules\common\models\Lottery;
use app\modules\competing\models\WorldcupSchedule;

class DataController extends Controller {

    /**
     * 支付限制刷新
     * @return json
     */
    public function actionUpdatePayLimit() {
        Commonfun::updatePayLimit();
        return $this->jsonResult(600, "更新成功", "");
    }

    /**
     * 联赛数据接入
     * @return json
     */
    public function actionLeaguesData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTempStr = "insert into league (league_code,league_type,league_short_name,league_long_name,league_img,league_category_id,league_remarks,league_status,league_color,create_time)values(':league_code',':league_type',':league_short_name',':league_long_name',':league_img',:league_category_id,':league_remarks',1,':league_color','" . date("Y-m-d H:i:s") . "');";
        $updateTempStr = "update league set league_short_name=':league_short_name',league_long_name=':league_long_name',league_img=':league_img',league_category_id=:league_category_id,league_remarks=':league_remarks',league_status=1,league_color=':league_color',modify_time='" . date("Y-m-d H:i:s") . "' where league_code=':league_code' and league_type=':league_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            if ((int) $val['league_code'] < 1) {
                return $this->jsonError(109, '联赛编号小于0');
            }
            if (in_array($val["league_code"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["league_code"];
            }
        }
        $hasLeagues = League::find()->select("league_code,league_type")->where(["in", "league_code", $mids])->asArray()->all();
        $zuHasMids = [];
        $lanHasMids = [];
        foreach ($hasLeagues as $val) {
            if ($val['league_type'] == 1) {
                $zuHasMids[] = $val["league_code"];
            } elseif ($val['league_type'] == 2) {
                $lanHasMids[] = $val['league_code'];
            }
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if ((in_array($val["league_code"], $zuHasMids) && $val['league_type'] == 1) || (in_array($val["league_code"], $lanHasMids) && $val['league_type'] == 2)) {
                $sqlStr .= $this->strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTempStr, $val);
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
    public function actionTeamData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTempStr = "insert into team (team_code,team_type,team_position,team_short_name,team_long_name,country_name,team_img,team_remarks,team_status,create_time)values(':team_code',':team_type',':team_position',':team_short_name',':team_long_name',':country_name',':team_img',':team_remarks',1,'" . date("Y-m-d H:i:s") . "');";
        $updateTempStr = "update team set team_position=':team_position',team_short_name=':team_short_name',team_long_name=':team_long_name',country_name=':country_name',team_img=':team_img',team_remarks=':team_remarks',team_status=1,modify_time='" . date("Y-m-d H:i:s") . "' where team_code=':team_code' and team_type=':team_type';";
        $tids[1] = [];
        $tids[2] = [];
        $mids[1] = [];
        $mids[2] = [];
        $leagueTeamArr[1] = [];
        $leagueTeamArr[2] = [];
        $leagueNames[1] = [];
        $leagueNames[2] = [];
        foreach ($data as $key => $val) {
            if ((int) $val['team_code'] < 1) {
                return $this->jsonError(109, '球队编号小于0');
            }
            if ($val['team_type'] == 1) {
                if (!isset($leagueTeamArr[1][$val["league_code"]])) {
                    $leagueTeamArr[1][$val["league_code"]] = [];
                    $mids[1][] = $val["league_code"];
                }
                $leagueTeamArr[1][$val["league_code"]][$val["team_code"]] = $val["team_code"];
                $leagueNames[1][$val["league_code"]] = isset($val["league_name"]) ? $val["league_name"] : "缺少联赛名";
                if (in_array($val["team_code"], $tids[1])) {
                    unset($data[$key]);
                } else {
                    $tids[1][] = $val["team_code"];
                }
                $type = 1;
            } elseif ($val['team_type'] == 2) {
                if (!isset($leagueTeamArr[2][$val['league_code']])) {
                    $leagueTeamArr[2][$val["league_code"]] = [];
                    $mids[2][] = $val["league_code"];
                }
                $leagueTeamArr[2][$val["league_code"]][$val["team_code"]] = $val["team_code"];
                $leagueNames[2][$val["league_code"]] = isset($val["league_name"]) ? $val["league_name"] : "缺少联赛名";
                if (in_array($val["team_code"], $tids[2])) {
                    unset($data[$key]);
                } else {
                    $tids[2][] = $val["team_code"];
                }
                $type = 2;
            }
        }
        $hasTeams = Team::find()->select("team_code,team_type")->where(["in", "team_code", $tids[1]])->orWhere(['in', 'team_code', $tids[2]])->asArray()->all();
        $zuHasTids = [];
        $lanHasTids = [];
        foreach ($hasTeams as $val) {
            if ($val['team_type'] == 1) {
                $zuHasTids[] = $val["team_code"];
            } elseif ($val['team_type'] == 2) {
                $lanHasTids[] = $val['team_code'];
            }
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $val["team_short_name"] = trim($val["team_short_name"], "'");
            $val["team_long_name"] = trim($val["team_long_name"], "'");
            if ((in_array($val["team_code"], $zuHasTids) && $val['team_type'] == 1) || (in_array($val['team_code'], $lanHasTids) && $val['team_type'] == 2)) {
                $sqlStr .= $this->strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTempStr, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "球队数据处理失败");
        }

        $arr = [];
        $leagueTeamIds = [];
        $msg = "";
        if ($type == 1) {
            $zuTeams = Team::find()->select("team_id,team_code,team_type")->where(['team_type' => 1])->andWhere(["in", "team_code", $tids[1]])->indexBy("team_code")->asArray()->all();
            $zuLeagues = League::find()->select("league_id,league_code,league_type")->where(['league_type' => 1])->andWhere(["in", "league_code", $mids[1]])->indexBy("league_code")->asArray()->all();
            foreach ($leagueTeamArr[1] as $key => $list) {
                if (!isset($zuLeagues[$key])) {
                    $league = new League();
                    $league->league_type = 1;
                    $league->league_long_name = trim($leagueNames[1][$key], "'");
                    $league->league_short_name = trim($leagueNames[1][$key], "'");
                    $league->league_code = (string) $key;
                    $league->create_time = date("Y-m-d H:i:s");
                    if ($league->validate()) {
                        $ret = $league->save();
                        if ($ret == false) {
                            $msg .= $key . $leagueNames[1][$key] . ":联赛缺失插入失败；";
                            continue;
                        } else {
                            $leagueId = $league->league_id;
                        }
                    } else {
                        $msg .= $key . $leagueNames[1][$key] . ":" . json_encode($league->getFirstErrors(), true);
                        continue;
                    }
                } else {
                    $leagueId = $zuLeagues[$key]["league_id"];
                }
                foreach ($list as $val) {
                    if (!isset($zuTeams[$val])) {
                        continue;
                    }
                    $arr[] = $leagueId . "_" . $zuTeams[$val]["team_id"];
                    $leagueTeamIds[$leagueId . "_" . $zuTeams[$val]["team_id"]][":league_id"] = $leagueId;
                    $leagueTeamIds[$leagueId . "_" . $zuTeams[$val]["team_id"]][":team_id"] = $zuTeams[$val]["team_id"];
                }
            }
            $leagueTeams = (new \yii\db\Query())->select("league_id,team_id")->from("league_team")->where(["in", "CONCAT(`league_id`,'_' ,`team_id`)", $arr])->all();
            $hasLeagueTeams = [];
            foreach ($leagueTeams as $val) {
                $hasLeagueTeams[] = $val["league_id"] . "_" . $val["team_id"];
            }
            $temp = "insert into league_team (league_id,team_id)value(:league_id,:team_id);";
        } elseif ($type == 2) {
            $lanTeams = Team::find()->select("team_id,team_code,team_type")->where(['team_type' => 2])->andWhere(["in", "team_code", $tids[2]])->indexBy("team_code")->asArray()->all();
            $lanLeagues = League::find()->select("league_id,league_code,league_type")->where(['league_type' => 2])->andWhere(["in", "league_code", $mids[2]])->indexBy("league_code")->asArray()->all();
            foreach ($leagueTeamArr[2] as $key => $list) {
                if (!isset($lanLeagues[$key])) {
                    $league = new League();
                    $league->league_type = 2;
                    $league->league_long_name = trim($leagueNames[2][$key], "'");
                    $league->league_short_name = trim($leagueNames[2][$key], "'");
                    $league->league_code = (string) $key;
                    $league->create_time = date("Y-m-d H:i:s");
                    if ($league->validate()) {
                        $ret = $league->save();
                        if ($ret == false) {
                            $msg .= $key . $leagueNames[2][$key] . ":联赛缺失插入失败；";
                            continue;
                        } else {
                            $leagueId = $league->league_id;
                        }
                    } else {
                        $msg .= $key . $leagueNames[2][$key] . ":" . json_encode($league->getFirstErrors(), true);
                        continue;
                    }
                } else {
                    $leagueId = $lanLeagues[$key]["league_id"];
                }
                foreach ($list as $val) {
                    if (!isset($lanTeams[$val])) {
                        continue;
                    }
                    $arr[] = $leagueId . "_" . $lanTeams[$val]["team_id"];
                    $leagueTeamIds[$leagueId . "_" . $lanTeams[$val]["team_id"]][":league_id"] = $leagueId;
                    $leagueTeamIds[$leagueId . "_" . $lanTeams[$val]["team_id"]][":team_id"] = $lanTeams[$val]["team_id"];
                }
            }
            $leagueTeams = (new \yii\db\Query())->select("lan_league_id,lan_team_id")->from("lan_league_team")->where(["in", "CONCAT(`lan_league_id`,'_' ,`lan_team_id`)", $arr])->all();
            $hasLeagueTeams = [];
            foreach ($leagueTeams as $val) {
                $hasLeagueTeams[] = $val["lan_league_id"] . "_" . $val["lan_team_id"];
            }
            $temp = "insert into lan_league_team (lan_league_id,lan_team_id)value(:league_id,:team_id);";
        } else {
            return $this->jsonError(109, '球队所属类型不存在');
        }

        $sql = "";
        foreach ($arr as $val) {
            if (!in_array($val, $hasLeagueTeams)) {
                $sql .= strtr($temp, $leagueTeamIds[$val]);
            }
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, $msg . "球队数据处理失败");
        }
        return $this->jsonResult(600, $msg . "球队数据处理成功", "");
    }

    /**
     * 赛事数据接入
     * @return json
     */
    public function actionScheduleData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTempStr = "insert into schedule (schedule_code,schedule_mid,schedule_date,league_id,league_name,visit_team_name,home_team_name,visit_short_name,home_short_name,home_team_id,home_team_mid,visit_team_id,visit_team_mid,start_time,beginsale_time,endsale_time,periods,rq_nums,schedule_status,schedule_spf,schedule_rqspf,schedule_bf,schedule_zjqs,schedule_bqcspf,high_win_status,hot_status,is_optional,create_time,open_mid)values(':schedule_code',':schedule_mid',:schedule_date,:league_id,':league_name',':visit_team_name',':home_team_name',':visit_short_name',':home_short_name',:home_team_id,':home_team_code',:visit_team_id,':visit_team_code',':start_time',':beginsale_time',':endsale_time',':periods',':rq_nums',:schedule_status,:schedule_spf,:schedule_rqspf,:schedule_bf,:schedule_zjqs,:schedule_bqcspf,:high_win_status,:hot_status,:is_optional,'" . date("Y-m-d H:i:s") . "',':open_mid');";
        $updateTempStr = "update schedule set schedule_code=':schedule_code',schedule_date=:schedule_date,league_id=:league_id,league_name=':league_name',visit_team_name=':visit_team_name',home_team_name=':home_team_name',visit_short_name=':visit_short_name',home_short_name=':home_short_name',visit_team_mid=':visit_team_code',home_team_mid=':home_team_code',home_team_id=:home_team_id,visit_team_id=:visit_team_id,start_time=':start_time',beginsale_time=':beginsale_time',endsale_time=':endsale_time',periods=':periods',rq_nums=':rq_nums',schedule_status=':schedule_status',schedule_spf=':schedule_spf',schedule_rqspf=':schedule_rqspf',schedule_bf=':schedule_bf',schedule_zjqs=':schedule_zjqs',schedule_bqcspf=':schedule_bqcspf',high_win_status=:high_win_status,hot_status=:hot_status,is_optional=:is_optional,modify_time='" . date("Y-m-d H:i:s") . "',open_mid=':open_mid' where schedule_mid=':schedule_mid';";
        $mids = [];
        $teamCodes = [];
//        $leagueCodes = [];
        $leagueShortNames = [];
        $msg = "";
        foreach ($data as $key => $val) {
            if ($val['rq_nums'] == 0) {
                $msg = $val["schedule_mid"] . '让球数不能为0';
                return $this->jsonError(109, $msg);
            }
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
                $msg .= $val["schedule_mid"] . "赛程重复；";
            } else {
                $mids[] = $val["schedule_mid"];
            }
            $leagueShortNames[] = $val["league_name"];
            $teamCodes[] = $val["home_team_code"];
            $teamCodes[] = $val["visit_team_code"];
//            $leagueCodes[] = $val["league_code"];
        }
        $leagues = League::find()->select("league_code,league_id,league_short_name")->where(["in", "league_short_name", $leagueShortNames])->andWhere(['league_type' => 1])->andWhere(['>', 'league_code', 0])->indexBy("league_short_name")->asArray()->all();
//        $leagues = League::find()->select("league_code,league_id")->where(["in", "league_code", $leagueCodes])->indexBy("league_code")->asArray()->all();
        $teams = Team::find()->select("team_code,team_id,team_long_name,team_short_name")->where(["in", "team_code", $teamCodes])->andWhere(['team_type' => 1])->andWhere(['>', 'team_code', 0])->indexBy("team_code")->asArray()->all();
        $hasSchedule = Schedule::find()->select("schedule_mid")->where(["in", "schedule_mid", $mids])->asArray()->all();
        $hasMids = [];
        foreach ($hasSchedule as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $key => $val) {
            if ($val['home_team_code'] == '0' || $val['visit_team_code'] == '0') {
                $exist = Schedule::find()->select(['schedule_mid', 'schedule_code'])->where(['schedule_mid' => $val['schedule_mid']])->asArray()->one();
                if (empty($exist)) {
                    Commonfun::sysAlert("赛程数据接入错误 - 足球", "通知", 'scheduleMid_ZQ:' . $val['schedule_code'] . '(' . $val['schedule_mid'] . ')', "待处理", "请即时处理！");
                    continue;
                }
            }
            if (!isset($teams[$val["home_team_code"]])) {
                $msg .= $val["home_team_code"] . "球队不存在；";
                continue;
            }
            if (!isset($teams[$val["visit_team_code"]])) {
                $msg .= $val["visit_team_code"] . "球队不存在；";
                continue;
            }
            if (!isset($leagues[$val["league_name"]])) {
                $msg .= $val["schedule_mid"] . "联赛名不存在；";
                continue;
            }
            if (!isset($val["schedule_date"]) || empty($val["schedule_date"])) {
                $val["schedule_date"] = date("Ymd", strtotime($val["start_time"]));
            }
            $val["home_team_id"] = $teams[$val["home_team_code"]]["team_id"];
            $val["visit_team_id"] = $teams[$val["visit_team_code"]]["team_id"];
            $val["visit_team_name"] = $teams[$val["visit_team_code"]]["team_long_name"];
            $val["home_team_name"] = $teams[$val["home_team_code"]]["team_long_name"];
            $val["visit_short_name"] = $teams[$val["visit_team_code"]]["team_short_name"];
            $val["home_short_name"] = $teams[$val["home_team_code"]]["team_short_name"];
//            $val["league_id"] = $leagues[$val["league_code"]]["league_id"];
            $val["league_id"] = $leagues[$val["league_name"]]["league_id"];
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .= $this->strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTempStr, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret == false) {
            return $this->jsonError(109, $msg . "赛程数据处理失败。");
        }
        sleep(5);
        $schedules = Schedule::find()->select("schedule_mid,schedule_id,schedule_date")->where(["in", "schedule_mid", $mids])->asArray()->all();
        $scheduleResults = (new \yii\db\Query())->select("schedule_mid")->from("schedule_result")->where(["in", "schedule_mid", $mids])->all();
        $existScheduleMid = [];
        foreach ($scheduleResults as $val) {
            $existScheduleMid[] = $val["schedule_mid"];
        }
        $temp = "insert into schedule_result (schedule_id,schedule_mid,schedule_date,create_time)values(:schedule_id,':schedule_mid',:schedule_date,'" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update schedule_result set schedule_id=:schedule_id,schedule_date=:schedule_date where schedule_mid=':schedule_mid'";
        $sql = "";
        foreach ($schedules as $val) {
            if (!in_array($val["schedule_mid"], $existScheduleMid)) {
                $sql .= $this->strTemplateReplace($temp, $val);
            } else {
                $sql .= $this->strTemplateReplace($updateTemp, $val);
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
     * 让球赔率数据接入
     * @return json
     */
    public function actionOdds3006Data() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $mids = [];
        $temp = "insert into odds_3006 (schedule_id,schedule_mid,updates_nums,let_ball_nums,let_wins,let_wins_trend,let_level,let_level_trend,let_negative,let_negative_trend,create_time)values(:schedule_id,':schedule_mid',:updates_nums,':let_ball_nums',:let_wins,:let_wins_trend,:let_level,:let_level_trend,:let_negative,:let_negative_trend,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $schedulesRqnums = (new \yii\db\Query())->select("schedule_id,schedule_mid,rq_nums")->from("schedule")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        if (empty($schedulesRqnums)) {
            return $this->jsonError(109, '该赛程暂时不存在，请稍后再试');
        }
        $oddsNums = (new \yii\db\Query())->select(["schedule_mid", "MAX(updates_nums) as updates_nums"])->from("odds_3006")->where(["in", "schedule_mid", $mids])->groupBy("")->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] ++;
            }
            $val["updates_nums"] = $oddsNums[$val["schedule_mid"]]["updates_nums"];
            $val["let_ball_nums"] = $schedulesRqnums[$val["schedule_mid"]]["rq_nums"];
            $val["schedule_id"] = $schedulesRqnums[$val["schedule_mid"]]["schedule_id"];
            $sql .= $this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3006赔率数据处理失败");
        }

        return $this->jsonResult(600, "3006赔率数据处理成功", $ret);
    }

    /**
     * 比分赔率数据接入
     * @return json
     */
    public function actionOdds3007Data() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $mids = [];
        $temp = "insert into odds_3007 (schedule_id,schedule_mid,updates_nums,score_wins_10,score_wins_20,score_wins_21,score_wins_30,score_wins_31,score_wins_32,score_wins_40,score_wins_41,score_wins_42,score_wins_50,score_wins_51,score_wins_52,score_wins_90,score_level_00,score_level_11,score_level_22,score_level_33,score_level_99,score_negative_01,score_negative_02,score_negative_12,score_negative_03,score_negative_13,score_negative_23,score_negative_04,score_negative_14,score_negative_24,score_negative_05,score_negative_15,score_negative_25,score_negative_09,create_time)values(:schedule_id,':schedule_mid',:updates_nums,:score_wins_10,:score_wins_20,:score_wins_21,:score_wins_30,:score_wins_31,:score_wins_32,:score_wins_40,:score_wins_41,:score_wins_42,:score_wins_50,:score_wins_51,:score_wins_52,:score_wins_90,:score_level_00,:score_level_11,:score_level_22,:score_level_33,:score_level_99,:score_negative_01,:score_negative_02,:score_negative_12,:score_negative_03,:score_negative_13,:score_negative_23,:score_negative_04,:score_negative_14,:score_negative_24,:score_negative_05,:score_negative_15,:score_negative_25,:score_negative_09,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $schedulesRqnums = (new \yii\db\Query())->select("schedule_id,schedule_mid")->from("schedule")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        if (empty($schedulesRqnums)) {
            return $this->jsonError(109, '该赛程暂时不存在，请稍后再试');
        }
        $oddsNums = (new \yii\db\Query())->select("schedule_mid,updates_nums")->from("odds_3007")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] ++;
            }
            $val["updates_nums"] = $oddsNums[$val["schedule_mid"]]["updates_nums"];
            $val["schedule_id"] = $schedulesRqnums[$val["schedule_mid"]]["schedule_id"];
            $sql .= $this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3007赔率数据处理失败");
        }

        return $this->jsonResult(600, "3007赔率数据处理成功", $ret);
    }

    /**
     * 总进球赔率数据接入
     * @return json
     */
    public function actionOdds3008Data() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $mids = [];
        $temp = "insert into odds_3008 (schedule_id,schedule_mid,updates_nums,total_gold_0,total_gold_0_trend,total_gold_1,total_gold_1_trend,total_gold_2,total_gold_2_trend,total_gold_3,total_gold_3_trend,total_gold_4,total_gold_4_trend,total_gold_5,total_gold_5_trend,total_gold_6,total_gold_6_trend,total_gold_7,total_gold_7_trend,create_time)values(:schedule_id,':schedule_mid',:updates_nums,:total_gold_0,:total_gold_0_trend,:total_gold_1,:total_gold_1_trend,:total_gold_2,:total_gold_2_trend,:total_gold_3,:total_gold_3_trend,:total_gold_4,:total_gold_4_trend,:total_gold_5,:total_gold_5_trend,:total_gold_6,:total_gold_6_trend,:total_gold_7,:total_gold_7_trend,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $schedulesRqnums = (new \yii\db\Query())->select("schedule_id,schedule_mid")->from("schedule")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        if (empty($schedulesRqnums)) {
            return $this->jsonError(109, '该赛程暂时不存在，请稍后再试');
        }
        $oddsNums = (new \yii\db\Query())->select(["schedule_mid", "MAX(updates_nums) as updates_nums"])->from("odds_3008")->where(["in", "schedule_mid", $mids])->groupBy("")->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] ++;
            }
            $val["updates_nums"] = $oddsNums[$val["schedule_mid"]]["updates_nums"];
            $val["schedule_id"] = $schedulesRqnums[$val["schedule_mid"]]["schedule_id"];
            $sql .= $this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3008赔率数据处理失败");
        }

        return $this->jsonResult(600, "3008赔率数据处理成功", $ret);
    }

    /**
     * 半全场赔率数据接入
     * @return json
     */
    public function actionOdds3009Data() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $mids = [];
        $temp = "insert into odds_3009 (schedule_id,schedule_mid,updates_nums,bqc_33,bqc_33_trend,bqc_31,bqc_31_trend,bqc_30,bqc_30_trend,bqc_13,bqc_13_trend,bqc_11,bqc_11_trend,bqc_10,bqc_10_trend,bqc_03,bqc_03_trend,bqc_01,bqc_01_trend,bqc_00,bqc_00_trend,create_time)values(:schedule_id,':schedule_mid',:updates_nums,:bqc_33,bqc_33_trend,:bqc_31,:bqc_31_trend,:bqc_30,:bqc_30_trend,:bqc_13,:bqc_13_trend,:bqc_11,:bqc_11_trend,:bqc_10,:bqc_10_trend,:bqc_03,:bqc_03_trend,:bqc_01,:bqc_01_trend,:bqc_00,:bqc_00_trend,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $schedulesRqnums = (new \yii\db\Query())->select("schedule_id,schedule_mid")->from("schedule")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        if (empty($schedulesRqnums)) {
            return $this->jsonError(109, '该赛程暂时不存在，请稍后再试');
        }
        $oddsNums = (new \yii\db\Query())->select(["schedule_mid", "MAX(updates_nums) as updates_nums"])->from("odds_3009")->where(["in", "schedule_mid", $mids])->groupBy("schedule_mid")->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] ++;
            }
            $val["updates_nums"] = $oddsNums[$val["schedule_mid"]]["updates_nums"];
            $val["schedule_id"] = $schedulesRqnums[$val["schedule_mid"]]["schedule_id"];
            $sql .= $this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3009赔率数据处理失败");
        }

        return $this->jsonResult(600, "3009赔率数据处理成功", $ret);
    }

    /**
     * 胜平负赔率数据接入
     * @return json
     */
    public function actionOdds3010Data() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $mids = [];
        $temp = "insert into odds_3010 (schedule_id,schedule_mid,updates_nums,outcome_wins,outcome_wins_trend,outcome_level,outcome_level_trend,outcome_negative,outcome_negative_trend,create_time)values(:schedule_id,':schedule_mid',:updates_nums,:outcome_wins,:outcome_wins_trend,:outcome_level,:outcome_level_trend,:outcome_negative,:outcome_negative_trend,':create_time');";
        foreach ($data as $val) {
            $mids[] = $val["schedule_mid"];
        }
        $schedulesRqnums = (new \yii\db\Query())->select("schedule_id,schedule_mid")->from("schedule")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        if (empty($schedulesRqnums)) {
            return $this->jsonError(109, '该赛程暂时不存在，请稍后再试');
        }
        $oddsNums = (new \yii\db\Query())->select(["schedule_mid", "MAX(updates_nums) as updates_nums"])->from("odds_3010")->where(["in", "schedule_mid", $mids])->groupBy("")->indexBy("schedule_mid")->all();
        $sql = "";
        foreach ($data as $val) {
            if (!isset($oddsNums[$val["schedule_mid"]])) {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] = 0;
            } else {
                $oddsNums[$val["schedule_mid"]]["updates_nums"] ++;
            }
            $val["updates_nums"] = $oddsNums[$val["schedule_mid"]]["updates_nums"];
            $val["schedule_id"] = $schedulesRqnums[$val["schedule_mid"]]["schedule_id"];
            $sql .= $this->strTemplateReplace($temp, $val);
        }
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "3010赔率数据处理失败");
        }

        return $this->jsonResult(600, "3010赔率数据处理成功", $ret);
    }

    /**
     * 赛程结果数据接入
     * @return json
     */
    public function actionScheduleResultData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $updateTempStr1 = "update schedule_result set schedule_result_3007=':schedule_result_3007',schedule_result_sbbf=':schedule_result_sbbf',match_time=':match_time',status=':status',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid' and status not in (2,4,6);";
        $updateTempStr2 = "update schedule_result set schedule_result_3010=':schedule_result_3010',schedule_result_3006=':schedule_result_3006',schedule_result_3007=':schedule_result_3007',schedule_result_3008=':schedule_result_3008',schedule_result_3009=':schedule_result_3009',schedule_result_sbbf=':schedule_result_sbbf',odds_3006=':odds_3006',odds_3007=':odds_3007',odds_3008=':odds_3008',odds_3009=':odds_3009',odds_3010=':odds_3010',match_time=':match_time',status=':status',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $updateTempStr3 = "update schedule_result set schedule_result_3007=':schedule_result_3007',schedule_result_sbbf=':schedule_result_sbbf',status=':status',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid' and status not in (2,4,6);";

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
        $hasResults = (new Query())->select("schedule_mid")->from("schedule_result")->where(["in", "schedule_mid", $mids])->andWhere(["status" => 2])->all();
        $hasMids = [];
        foreach ($hasResults as $val) {
            $hasMids[] = $val["schedule_mid"];
        }

        foreach ($data as $val) {
            if ($val["status"] == 1 && isset($val['match_time'])) {
                $sqlStr .= $this->strTemplateReplace($updateTempStr1, $val);
            } else if ($val["status"] == 2) {
                $sqlStr .= $this->strTemplateReplace($updateTempStr2, $val);
            } elseif (!isset($val['match_time'])) {
                $sqlStr .= $this->strTemplateReplace($updateTempStr3, $val);
            } else {
                $msg .= $val["schedule_mid"] . "赛程赛果数据状态出错；";
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, $msg . "赛程结果数据处理失败");
        }
        if (YII_ENV_PROD) {//正式环境才推送
            $url = \Yii::$app->params['websocket_jsts_url'] . $data[0]['schedule_mid'];
            \Yii::sendCurlGet($url);
        }
        $bifen = Constants::BIFEN_ARR;
        foreach ($data as $val) {
            if ($val["status"] == 2 && !in_array($val["schedule_mid"], $hasMids)) {
                $result3007 = str_replace(':', '', $val['schedule_result_3007']);
                if ($val['schedule_result_3010'] == 0) {
                    if (!in_array($result3007, $bifen[0])) {
                        $result3007 = '09';
                    }
                } elseif ($val['schedule_result_3010'] == 1) {
                    if (!in_array($result3007, $bifen[1])) {
                        $result3007 = '99';
                    }
                } elseif ($val['schedule_result_3010'] == 3) {
                    if (!in_array($result3007, $bifen[3])) {
                        $result3007 = '90';
                    }
                }
                if ($val['schedule_result_3008'] > 7) {
                    $result3008 = 7;
                } else {
                    $result3008 = $val['schedule_result_3008'];
                }
                $wining = new Winning();
//                \Yii::redisSet('winning', json_encode($val));
                $wining->footballLevel($val['schedule_mid'], $val['schedule_result_3006'], $result3007, $result3008, $val['schedule_result_3009'], $val['schedule_result_3010']);
                $redArticle = new ArticleRed();
                $redArticle->acticlePreResult2($val['schedule_mid'], $val['schedule_result_3006'], $val['schedule_result_3010']);
            }
        }
        return $this->jsonResult(600, $msg . "赛程结果数据处理成功", $ret);
    }

    /**
     * 数字彩结果数据接入
     * @return json
     */
    public function actionLotteryResultData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $weekArr = \app\modules\common\helpers\Constants::WEEKARR;
        $lotteryCode = $this->arrParameter($data[0], "lottery_code");
        $periods = $this->arrParameter($data[0], "periods");
        $status = $this->arrParameter($data[0], "status");
        $lotRecord = \app\modules\common\models\LotteryRecord::findOne(["lottery_code" => $lotteryCode, "periods" => $periods]);
        if ($status == 3 || $status == 2) {
            if (empty($lotRecord)) {
                return $this->jsonError(109, '彩种此期数不存在');
            }
        }
        if ($status == 1 || $status == 0 || $lotRecord == null) {
            $lotteryTime = $this->arrParameter($data[0], "lottery_time");
            $week = date("w", strtotime($lotteryTime));
            $week = $weekArr[$week];
            $lotteryNames = \app\modules\common\helpers\Constants::LOTTERY;
            if ($lotRecord == null) {
                $lotRecord = new \app\modules\common\models\LotteryRecord();
                $lotRecord->periods = $periods;
                $lotRecord->lottery_code = $lotteryCode;
                $lotRecord->create_time = date("Y-m-d H:i:s");
            }
            $lotRecord->lottery_name = $lotteryNames[$lotteryCode];
            $lotRecord->lottery_time = $lotteryTime;
            $lotRecord->week = $week;
            $lotRecord->status = $status;
        }
        if ($status == 2) {
            $lotteryNumbers = $this->arrParameter($data[0], "lottery_numbers");
            $totalSales = $this->arrParameter($data[0], "total_sales");
            $pool = $this->arrParameter($data[0], "pool");
            if ($lotteryCode == '2009') {
                $newLotteryNums = explode(',', $lotteryNumbers);
                $puke = Constants::PUKE_NUMS;
                foreach ($newLotteryNums as $v) {
                    $new = explode('_', $v);
                    $news[] = $new[0] . '_' . $puke[$new[1]];
                }
                $lotteryNumbers = implode(',', $news);
            }
            $lotRecord->lottery_numbers = $lotteryNumbers;
            $lotRecord->total_sales = $totalSales;
            $lotRecord->pool = $pool;
            $lotRecord->status = 2;
            $lotRecord->modify_time = date("Y-m-d H:i:s");
        }
        if ($status == 3) {
            $lotRecord->status = 3;
            $lotRecord->modify_time = date("Y-m-d H:i:s");
        }
        if ($lotRecord->validate()) {
            $ret = $lotRecord->save();
            if ($ret === false) {
                return $this->jsonResult(109, "开奖记录更新失败", "");
            } else {
                if ($status == 1) {
                    if (in_array($lotteryCode, [2005, 2006, 2007, 2010, 2011])) {
                        //追号订单追期
                        $traceService = new AdditionalService();
                        $traceService->traceJob($lotteryCode, $periods, $lotteryTime);
                    }
                }
                if ($status == 2) {
                    if (!empty($lotteryNumbers)) {
                        $winHelper = new Winning();
                        if (in_array($lotteryCode, [2005, 2006, 2007, 2010, 2011])) {
                            $winHelper->lottery11X5Level($lotteryCode, $periods, $lotteryNumbers);
                        } elseif ($lotteryCode == 1001) {
                            $winHelper->lottery1001Level($lotteryCode, $periods, $lotteryNumbers);
                        } elseif ($lotteryCode == 1002) {
                            $winHelper->lottery1002Level($lotteryCode, $periods, $lotteryNumbers);
                        } elseif ($lotteryCode == 1003) {
                            $winHelper->lottery1003Level($lotteryCode, $periods, $lotteryNumbers);
                        }
                        $trend = new \app\modules\common\helpers\Trend();
                        $ret = $trend->getCreateTrend($lotteryCode, $periods, $lotteryNumbers);
                        if ($ret !== true) {
                            return $this->jsonResult(601, "走势图错误", "");
                        }
                        //开奖推送
                        $trendFall = new TrendFall();
                        $trendFall->trendWebsocket($lotteryCode, $periods, $lotteryNumbers);
                        //极光推送
//                        $Jpush = new Jpush();
//                        $msg = "第".$periods."期".$lotteryCode."11选5开奖号码 ".$lotteryNumbers;
//                        $Jpush->JpushDrawNotice($msg);
                    }
                }
                return $this->jsonResult(600, "开奖记录更新成功", "");
            }
        } else {
            return $this->jsonResult(109, "开奖记录更新错误！", $lotRecord->getFirstErrors());
        }
    }

    /**
     * 说明: 根据mid获取 改场次的变动信息
     * @author  kevi
     * @date 2017年10月26日 下午5:26:02
     * @param
     * @return 
     */
    public function actionGetChangeScheduleBymid() {
        $request = \Yii::$app->request;
        $mid = $request->get('mid');
        $changeRet = ScheduleResult::find()->select(['schedule_result.schedule_mid', 'schedule_result.status', 'schedule_result.match_time', 's.rq_nums', 'schedule_result.schedule_result_3006',
                            'schedule_result.schedule_result_3007', 'schedule_result.schedule_result_3008', 'schedule_result.schedule_result_3009', 'schedule_result.schedule_result_3010',
                            'schedule_result.schedule_result_sbbf', 'schedule_result.odds_3006 as sr_odds_3006', 'schedule_result.odds_3007 as sr_odds_3007', 'schedule_result.odds_3008 as sr_odds_3008',
                            'schedule_result.odds_3009 as sr_odds_3009', 'home_corner_num', 'home_red_num', 'home_yellow_num', 'visit_corner_num', 'visit_red_num', 'visit_yellow_num'])
                        ->leftJoin('schedule_technic', 'schedule_technic.schedule_mid = schedule_result.schedule_mid')
                        ->leftJoin('schedule s', 's.schedule_mid = schedule_result.schedule_mid')
                        ->where(['schedule_result.schedule_mid' => $mid])->asArray()->one();
        $this->jsonResult(600, '获取成功', $changeRet);
    }

    /**
     * 赛程历史数据接入 
     * @return json
     */
    public function actionScheduleHistoryData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTemp = "insert into schedule_history (schedule_mid,league_code,league_name,play_time,home_team_mid,home_team_name,visit_team_mid,visit_team_name,result_3007,result_3009_b,result_3010,create_time)values(':schedule_mid',':league_code',':league_name',':play_time',':home_team_mid',':home_team_name',':visit_team_mid',':visit_team_name',':result_3007',':result_3009_b',':result_3010','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update schedule_history set league_code=':league_code',league_name=':league_name',play_time=':play_time',home_team_mid=':home_team_mid',home_team_name=':home_team_name',visit_team_mid=':visit_team_mid',visit_team_name=':visit_team_name',result_3007=':result_3007',result_3009_b=':result_3009_b',result_3010=':result_3010',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasScheduleHistory = (new \yii\db\Query())->select("schedule_mid")->from("schedule_history")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasScheduleHistory as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .= $this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTemp, $val);
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
    public function actionHistoryCountData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTemp = "insert into history_count (schedule_mid,double_play_num,num3,num1,num0,home_team_rank,visit_team_rank,home_team_league,visit_team_league,scale_3010_3,scale_3010_1,scale_3010_0,scale_3006_3,scale_3006_1,scale_3006_0,europe_odds_3,europe_odds_1,europe_odds_0,create_time)values(':schedule_mid',':double_play_num',':num3',':num1',':num0',':home_team_rank',':visit_team_rank',':home_team_league',':visit_team_league',':scale_3010_3',':scale_3010_1',':scale_3010_0',':scale_3006_3',':scale_3006_1',':scale_3006_0',':europe_odds_3',':europe_odds_1',':europe_odds_0','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update history_count set double_play_num=':double_play_num',num3=':num3',num1=':num1',num0=':num0',home_team_rank=':home_team_rank',visit_team_rank=':visit_team_rank',home_team_league=':home_team_league',visit_team_league=':visit_team_league',scale_3010_3=':scale_3010_3',scale_3010_1=':scale_3010_1',scale_3010_0=':scale_3010_0',scale_3006_3=':scale_3006_3',scale_3006_1=':scale_3006_1',scale_3006_0=':scale_3006_0',europe_odds_3=':europe_odds_3',europe_odds_1=':europe_odds_1',europe_odds_0=':europe_odds_0',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select("schedule_mid")->from("history_count")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .= $this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTemp, $val);
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
    public function actionAsianHandicapData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTemp = "insert into asian_handicap (schedule_mid,company_name,country,handicap_type,handicap_name,home_discount,let_index,visit_discount,create_time,home_discount_trend,visit_discount_trend)values(':schedule_mid',':company_name',':country',':handicap_type',':handicap_name',':home_discount',':let_index',':visit_discount','" . date("Y-m-d H:i:s") . "',':home_discount_trend',':visit_discount_trend');";
        $updateTemp = "update asian_handicap set handicap_name=':handicap_name',country=':country',home_discount=':home_discount',let_index=':let_index',visit_discount=':visit_discount',modify_time='" . date("Y-m-d H:i:s") . "',home_discount_trend=':home_discount_trend',visit_discount_trend=':visit_discount_trend' where company_name=':company_name' and schedule_mid=':schedule_mid' and handicap_type=':handicap_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $str;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select(["schedule_mid", "company_name", "handicap_type"])->from("asian_handicap")->where(["in", "CONCAT(`schedule_mid`,'_' ,`company_name`,'_',`handicap_type`)", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            $hasMids[] = $str;
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $hasMids)) {
                $sqlStr .= $this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTemp, $val);
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
    public function actionEuropeOddsData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTemp = "insert into europe_odds (schedule_mid,company_name,country,handicap_type,handicap_name,odds_3,odds_1,odds_0,create_time,odds_3_trend,odds_1_trend,odds_0_trend)values(':schedule_mid',':company_name',':country',':handicap_type',':handicap_name',':odds_3',':odds_1',':odds_0','" . date("Y-m-d H:i:s") . "',':odds_3_trend',':odds_1_trend',':odds_0_trend');";
        $updateTemp = "update europe_odds set handicap_name=':handicap_name',country=':country',odds_3=':odds_3',odds_1=':odds_1',odds_0=':odds_0',modify_time='" . date("Y-m-d H:i:s") . "',odds_3_trend=':odds_3_trend',odds_1_trend=':odds_1_trend',odds_0_trend=':odds_0_trend' where company_name=':company_name' and schedule_mid=':schedule_mid' and handicap_type=':handicap_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $str;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select(["schedule_mid", "company_name", "handicap_type"])->from("europe_odds")->where(["in", "CONCAT(`schedule_mid`,'_' ,`company_name`,'_',`handicap_type`)", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            $hasMids[] = $str;
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $hasMids)) {
                $sqlStr .= $this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTemp, $val);
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
    public function actionPreResultData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTempStr = "insert into pre_result (schedule_mid,pre_result_title,pre_result_3010,pre_result_3007,confidence_index,expert_analysis,create_time)values(':schedule_mid',':pre_result_title',':pre_result_3010',':pre_result_3007',':confidence_index',':expert_analysis','" . date("Y-m-d H:i:s") . "');";
        $updateTempStr = "update pre_result set pre_result_title=':pre_result_title',pre_result_3010=':pre_result_3010',pre_result_3007=':pre_result_3007',confidence_index=':confidence_index',expert_analysis=':expert_analysis',modify_time='" . date("Y-m-d H:i:s") . "' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $hasPreResult = (new \yii\db\Query())->select("schedule_mid")->from("pre_result")->where(["in", "schedule_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasPreResult as $val) {
            $hasMids[] = $val["schedule_mid"];
        }
        $sqlStr = "";
        foreach ($data as $val) {
            if (in_array($val["schedule_mid"], $hasMids)) {
                $sqlStr .= $this->strTemplateReplace($updateTempStr, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTempStr, $val);
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
    public function actionScheduleEventData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
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
        $hasEvents = (new Query())->select("schedule_event_mid")->from("schedule_event")->where(["in", "schedule_event_mid", $mids])->all();
        $hasMids = [];
        foreach ($hasEvents as $val) {
            $hasMids[] = $val["schedule_event_mid"];
        }

        foreach ($data as $val) {
            if (in_array($val["schedule_event_mid"], $hasMids)) {
                $scheduleEvent = ScheduleEvent::findOne(["schedule_event_mid" => $val["schedule_event_mid"]]);
            } else {
                $scheduleEvent = new ScheduleEvent();
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
                    $msg .= "schedule_mid:" . $scheduleEvent->schedule_mid . "赛程事件数据插入失败;";
                }
                $msg .= "schedule_mid:" . $scheduleEvent->schedule_mid . "赛程事件数据插入成功;";
            } else {
                $msg .= "schedule_mid:" . $scheduleEvent->schedule_mid . "赛程事件数据插入失败;";
                $es[$scheduleEvent->schedule_mid] = $scheduleEvent->getFirstErrors();
            }
        }
        return $this->jsonResult(600, $msg, $es);
    }

    /**
     * 赛程技术统计数据接入
     * @return json
     */
    public function actionScheduleTechnic() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $data = $data[0];
        $mid = $this->arrParameter($data, "schedule_mid");
        $scheduleTechnic = ScheduleTechnic::findOne(["schedule_mid" => $mid]);
        if ($scheduleTechnic == null) {
            $scheduleTechnic = new ScheduleTechnic();
            $scheduleTechnic->schedule_mid = $mid;
            $scheduleTechnic->create_time = date("Y-m-d H:i:s");
        } else {
            $scheduleTechnic->modify_time = date("Y-m-d H:i:s");
        }
        $field = ['event_type', 'count(event_type_name) as total'];
        $homeEvent = ScheduleEvent::find()->select($field)->where(['schedule_mid' => $mid, 'team_type' => 1])->andWhere(['in', 'event_type', [4, 6, 7]])->groupBy('event_type')->asArray()->all();
        $visitEvent = ScheduleEvent::find()->select($field)->where(['schedule_mid' => $mid, 'team_type' => 2])->andWhere(['in', 'event_type', [4, 6, 7]])->groupBy('event_type')->asArray()->all();
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
    public function actionScheduleRemindData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTempStr = "insert into schedule_remind (schedule_mid,schedule_type,team_type,content,create_time)values(':schedule_mid',1,':team_type',':content','" . date("Y-m-d H:i:s") . "');";

        $sqlStr = "";
        foreach ($data as $val) {
            $sqlStr .= $this->strTemplateReplace($insertTempStr, $val);
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
    public function actionScheduleInit() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTempStr = "insert into pre_result (schedule_mid,average_home_percent,average_visit_percent,json_data)values(':schedule_mid',':average_home_percent',':average_visit_percent',':json_data');";
        $updateTempStr = "update pre_result set average_home_percent=':average_home_percent',average_visit_percent=':average_visit_percent',json_data=':json_data' where schedule_mid=':schedule_mid';";
        $insertTempStr1 = "insert into history_count (schedule_mid,home_num_3,home_num_1,home_num_0,visit_num_3,visit_num_1,visit_num_0)values(':schedule_mid',':home_num_3',':home_num_1',':home_num_0',':visit_num_3',':visit_num_1',':visit_num_0');";
        $updateTempStr1 = "update history_count set home_num_3=':home_num_3',home_num_1=':home_num_1',home_num_0=':home_num_0',visit_num_3=':visit_num_3',visit_num_1=':visit_num_1',visit_num_0=':visit_num_0' where schedule_mid=':schedule_mid';";
        $mids = [];
        foreach ($data as $key => $val) {
            if (in_array($val["schedule_mid"], $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $val["schedule_mid"];
            }
        }
        $sql = "";
        $schedule = (new \yii\db\Query())->select(["t1.team_code as home_team_mid", "t2.team_code as visit_team_mid", "s.schedule_mid", "s.start_time"])->from("schedule s")->join("left join", "team t1", "t1.team_id=s.home_team_id")->join("left join", "team t2", "t2.team_id=s.visit_team_id")->where(["in", "s.schedule_mid", $mids])->all();
        $hasPreResult = (new \yii\db\Query())->select("schedule_mid")->from("pre_result")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();
        $hasHistoryCount = (new \yii\db\Query())->select("schedule_mid")->from("history_count")->where(["in", "schedule_mid", $mids])->indexBy("schedule_mid")->all();

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
                $sql .= $this->strTemplateReplace($updateTempStr, $arr);
            } else {
                $sql .= $this->strTemplateReplace($insertTempStr, $arr);
            }
            if (isset($hasHistoryCount[$val["schedule_mid"]])) {
                $sql .= $this->strTemplateReplace($updateTempStr1, $arr);
            } else {
                $sql .= $this->strTemplateReplace($insertTempStr1, $arr);
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
        $homeData = (new Query())->select(["result_3007", "home_team_mid"])->from("schedule_history")->where(["<", "play_time", $time])->andWhere(["or", ["home_team_mid" => $homeTeamMid], ["visit_team_mid" => $homeTeamMid]])->andWhere(["!=", "result_3007", ""])->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $visitData = (new Query())->select(["result_3007", "home_team_mid"])->from("schedule_history")->where(["<", "play_time", $time])->andWhere(["or", ["home_team_mid" => $visitTeamMid], ["visit_team_mid" => $visitTeamMid]])->andWhere(["!=", "result_3007", ""])->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $data["home"] = $this->scheduleHistoryDeal($homeData, $homeTeamMid);
        $data["visit"] = $this->scheduleHistoryDeal($visitData, $visitTeamMid);
        $homeInHomeData = (new Query())->select(["result_3007", "home_team_mid"])->from("schedule_history")->where(["home_team_mid" => $homeTeamMid])->andWhere(["<", "play_time", $time])->andWhere(["!=", "result_3007", ""])->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $visitInVisitData = (new Query())->select(["result_3007", "home_team_mid"])->from("schedule_history")->where(["visit_team_mid" => $visitTeamMid])->andWhere(["<", "play_time", $time])->andWhere(["!=", "result_3007", ""])->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
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
            $gainBalls += $homeJq;
            $loseBalls += $visitJq;
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
     * 新浪热门赛事
     * @return type
     */
    public function actionScheduleHotData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $mid = $this->arrParameter($data[0], "schedule_mid");
        $scheduleType = $this->arrParameter($data[0], 'schedule_type');
        if ($scheduleType == 1) {
            $schedule = Schedule::findOne(["schedule_mid" => $mid]);
        } else {
            $schedule = LanSchedule::findOne(['schedule_mid' => $mid]);
        }

        if ($schedule == null) {
            return $this->jsonResult(109, "未找到{$mid}赛程", "");
        }
        $hotStatus = $this->arrParameter($data[0], "hot_status");
        $scheduleHot = (new Query())->select("schedule_mid")->from("schedule_hot_sina")->where(["schedule_mid" => $mid, 'schedule_type' => $scheduleType])->one();
        if ($scheduleHot == null) {
            $ret = \Yii::$app->db->createCommand()->insert("schedule_hot_sina", [
                        "schedule_mid" => $mid,
                        "schedule_type" => $scheduleType,
                        "hot_status" => $hotStatus,
                        "create_time" => date("Y-m-d H:i:s")
                    ])->execute();
        } else {
            $ret = \Yii::$app->db->createCommand()->update("schedule_hot_sina", [
                        "hot_status" => $hotStatus,
                        "schedule_type" => $scheduleType,
                        "modify_time" => date("Y-m-d H:i:s")
                            ], [
                        "schedule_mid" => $mid
                    ])->execute();
        }
        if ($ret !== false) {
            $schedule->hot_status = 1;
            $schedule->modify_time = date('Y-m-d H:i:s');
            $schedule->save();
            return $this->jsonResult(600, "{$mid}热门数据处理成功", "");
        } else {
            return $this->jsonResult(109, "{$mid}热门数据错误", "");
        }
    }

    /**
     * 任九数据处理
     * @return json
     */
    public function actionFootballNineData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $periods = $this->arrParameter($data[0], "periods");
        $footballNine = FootballNine::findOne(["periods" => $periods]);
        if ($footballNine == null) {
            $footballNine = new FootballNine();
            $footballNine->periods = $periods;
            $footballNine->create_time = date("Y-m-d H:i:s");
        }
        $footballNine->schedule_mids = $this->arrParameter($data[0], "schedule_mids");
        $footballNine->beginsale_time = $this->arrParameter($data[0], "beginsale_time");
        $footballNine->endsale_time = $this->arrParameter($data[0], "endsale_time");
        $footballNine->modify_time = date("Y-m-d H:i:s");
        if ($footballNine->validate()) {
            $ret = $footballNine->save();
            if ($ret === false) {
                return \Yii::jsonResult(109, "保存失败", "");
            }
            return $this->jsonResult(600, "保存成功", "");
        } else {
            return \Yii::jsonResult(109, "保存失败", $footballNine->getFirstErrors());
        }
    }

    /**
     * 任14数据处理
     * @return json
     */
    public function actionFootballFourteenData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $periods = $this->arrParameter($data[0], "periods");
        $footballFourteen = FootballFourteen::findOne(["periods" => $periods]);
        if ($footballFourteen == null) {
            $footballFourteen = new FootballFourteen();
            $footballFourteen->periods = $periods;
            $footballFourteen->beginsale_time = $this->arrParameter($data[0], "beginsale_time");
            $footballFourteen->endsale_time = $this->arrParameter($data[0], "endsale_time");
        }

        if ($footballFourteen->status != 3) {
            $status = $this->arrParameter($data[0], 'status');
            $footballFourteen->status = $status;
            if ($status == 2) {
                $footballFourteen->schedule_mids = $this->arrParameter($data[0], "schedule_mids");
                $footballFourteen->schedule_results = $this->arrParameter($data[0], "schedule_results");
            }
        }

        if ($footballFourteen->validate()) {
            $ret = $footballFourteen->save();
            if ($ret === false) {
                return \Yii::jsonResult(109, "保存失败", "");
            }
            return $this->jsonResult(600, "保存成功", "");
        } else {
            return \Yii::jsonResult(109, "保存失败", $footballFourteen->getFirstErrors());
        }
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
     * 判断数组是否存在该键
     * @param type $data
     * @param type $name
     * @return type
     */
    public function arrParameter($data, $name) {
        if (!isset($data[$name])) {
            return $this->jsonError(109, $name . "参数缺失;");
        }
        return $data[$name];
    }

    /**
     * 赛程结果表单个字段更新
     * @return type
     */
    public function actionUpdateSingle() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $mid = $this->arrParameter($data[0], "schedule_mid");
        $scheduleResult = ScheduleResult::findOne(["schedule_mid" => $mid]);
        if (empty($scheduleResult)) {
            return \Yii::jsonError(109, '该赛程不存在');
        }
        if ($scheduleResult->status == 2 || $scheduleResult->status == 3) {
            return $this->jsonResult(600, '该赛程已完场', true);
        }

        $status = $this->arrParameter($data[0], 'status');

        if ($scheduleResult->status == 4 && $status == 4) {
            return $this->jsonError(109, '该场次已推迟！！！');
        }
        if ($scheduleResult->status == 4 && $status == 0) {
            $schedule = Schedule::findOne(['schedule_mid' => $mid]);
            if (empty($schedule)) {
                return \Yii::jsonError(109, '该赛程不存在');
            }
            $startTime = $this->arrParameter($data[0], 'start_time');
            $endTime = $this->arrParameter($data[0], 'endsale_time');
            $schedule->start_time = $startTime;
            $schedule->endsale_time = $endTime;
            $schedule->modify_time = date('Y-m-d H:i:s');
            if (!$schedule->save()) {
                return \Yii::jsonError(109, '保存失败');
            }
            ArticlesPeriods::updateAll(['start_time' => $startTime, 'endsale_time' => $endTime], ['and', ['periods' => $mid], ['in', 'lottery_code', [3006, 3010]]]);
            $title = '足球延迟赛事重新开售';
        } elseif ($status == 3) {
            $schedule = Schedule::findOne(['schedule_mid' => $mid]);
            if (empty($schedule)) {
                return \Yii::jsonError(109, '该赛程不存在');
            }
            $schedule->schedule_status = 2;
            $schedule->modify_time = date('Y-m-d H:i:s');
            if (!$schedule->save()) {
                return \Yii::jsonError(109, '保存失败');
            }
            $redArticle = new ArticleRed();
            $redArticle->articleCancel($mid);
//            ArticlesPeriods::updateAll(['status' => 4], ['periods' => $mid]);
            $programmes = Programme::find()->select("programme_id")->where(["status" => 2])->andWhere(['between', 'lottery_code', 3006, 3011])->andWhere(["like", "bet_val", $mid])->asArray()->all();
            if (!empty($programmes)) {
                $programmeService = new ProgrammeService();
                foreach ($programmes as $val) {
                    $programmeService->outProgrammeFalse($val['programme_id'], 8, 'cancel');
                }
            }
            $str = '3000_' . $mid;
            $key = 'cancel_schedule';
            $redis = \Yii::$app->redis;
            $redis->sadd($key, $str);
            $title = '足球赛事取消';
        } elseif ($status == 4) {
            $schedule = Schedule::findOne(['schedule_mid' => $mid]);
            if (empty($schedule)) {
                return \Yii::jsonError(109, '该赛程不存在');
            }
            $oldEndSale = $schedule->endsale_time;
            $newEndSale = date('Y-m-d H:i:s', strtotime('+36 hours', strtotime($oldEndSale)));
            $schedule->endsale_time = $newEndSale;
            if (!$schedule->save()) {
                return \Yii::jsonError(109, '保存失败');
            }
            ArticlesPeriods::updateAll(['endsale_time' => $newEndSale], ['and', ['periods' => $mid], ['in', 'lottery_code', [3006, 3010]]]);
            $title = '足球赛事延迟';
        } elseif ($status == 7) {
            $schedule = Schedule::findOne(['schedule_mid' => $mid]);
            $title = '足球赛事腰斩';
        }
        $scheduleResult->status = $status;
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
                    Commonfun::sysAlert($title, "通知", 'scheduleMid_ZQ:' . $schedule['schedule_code'] . '(' . $schedule['schedule_mid'] . ')', "已处理", "请确认处理结果！");
                }
            }
            return $this->jsonError(600, '保存成功');
        } else {
            return \Yii::jsonResult(109, '保存失败', $scheduleResult->getFirstErrors());
        }
    }

    /**
     * 任选奖金更新写入
     * @return type
     */
    public function actionUpdateOptional() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $periods = $this->arrParameter($data[0], "periods");
        $optional = FootballFourteen::findOne(["periods" => $periods, 'status' => 2]);
        if (empty($optional)) {
            return \Yii::jsonError(109, '该期数据不存在或还未开奖');
        }
        $optional->schedule_results = $this->arrParameter($data[0], 'schedule_results');
        $optional->first_prize = $this->arrParameter($data[0], 'first_prize');
        $optional->second_prize = $this->arrParameter($data[0], 'second_prize');
        $optional->nine_prize = $this->arrParameter($data[0], 'nine_prize');
        $optional->status = $this->arrParameter($data[0], 'status');
        $optional->modify_time = date('Y-m-d H:i:s');
        if ($optional->validate()) {
            $ret = $optional->save();
            if ($ret === false) {
                return \Yii::jsonError(109, '保存失败');
            }
            $winning = new Winning();
            $winning->optionalLevel($optional->schedule_results, $periods, $optional->first_prize, $optional->second_prize, $optional->nine_prize);
            return $this->jsonError(600, '保存成功');
        } else {
            return \Yii::jsonResult(109, '保存失败', $optional->getFirstErrors());
        }
    }

    /**
     * 任选14场 场次的写入
     * @return type
     */
    public function actionOptionalSchedule() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(100, '参数缺失');
        }
        $data = json_decode($json, true);
        $actionType = $this->arrParameter($data[0], 'action_type');
        $periods = $this->arrParameter($data[0], 'periods');
        $sortCode = $this->arrParameter($data[0], 'sort_code');
        $optional = FootballFourteen::find()->select(['periods'])->where(['periods' => $periods])->asArray()->one();
        if (empty($optional)) {
            return \Yii::jsonError(109, '该期不存在');
        }
        $optSche = OptionalSchedule::findOne(['sorting_code' => $sortCode, 'periods' => $periods]);
        if ($actionType == 1) {
            if (empty($optSche)) {
                $optSche = new OptionalSchedule;
            }
            $homeTeam = $this->arrParameter($data[0], 'home_team_name');
            $visitTeam = $this->arrParameter($data[0], 'visit_team_name');
            $leagueName = $this->arrParameter($data[0], 'league_name');
            $startTime = $this->arrParameter($data[0], 'start_time');
            $oddsWin = $this->arrParameter($data[0], 'odds_win');
            $oddsFlat = $this->arrParameter($data[0], 'odds_flat');
            $oddsLose = $this->arrParameter($data[0], 'odds_lose');
            $optSche->sorting_code = $sortCode;
            $optSche->periods = $periods;
            $optSche->league_name = $leagueName;
            $optSche->home_short_name = $homeTeam;
            $optSche->visit_short_name = $visitTeam;
            $optSche->start_time = $startTime;
            $optSche->odds_win = $oddsWin;
            $optSche->odds_flat = $oddsFlat;
            $optSche->odds_lose = $oddsLose;
            $optSche->create_time = date('Y-m-d H:i:s');
        } elseif ($actionType == 2) {
            if (empty($optSche)) {
                return \Yii::jsonError(109, '该场次不存在');
            }
            $mid = $this->arrParameter($data[0], 'schedule_mid');
            $scheResult = $this->arrParameter($data[0], 'schedule_result');
            $optSche->schedule_mid = $mid;
            $optSche->schedule_result = $scheResult;
            $optSche->modify_time = date('Y-m-d H:i:s');
        } else {
            return \Yii::jsonError(109, '未知操作');
        }
        if (!$optSche->validate()) {
            return \Yii::jsonResult(109, '数据保存失败', $optSche->getFirstErrors());
        }
        if (!$optSche->save()) {
            return \Yii::jsonResult(109, '数据保存失败', $optSche->getFirstErrors());
        }
        return \Yii::jsonResult(600, '数据保存成功', true);
    }

    /**
     * 赛程开停售
     * @return type
     */
    public function actionStopSale() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $mid = $this->arrParameter($data[0], "schedule_mid");
        $schedule = Schedule::findOne(['schedule_mid' => $mid]);
        if (empty($schedule)) {
            return \Yii::jsonError(109, '该赛程不存在');
        }
        $status = $this->arrParameter($data[0], 'status');
        $schedule->schedule_status = $status;
        $schedule->modify_time = date('Y-m-d H:i:s');

        if ($schedule->validate()) {
            $ret = $schedule->save();
            if ($ret === false) {
                return \Yii::jsonError(109, '保存失败');
            }
            return $this->jsonError(600, '保存成功');
        } else {
            return \Yii::jsonResult(109, '保存失败', $schedule->getFirstErrors());
        }
    }

    /**
     * 数字彩开奖结果更新
     * @return type
     */
    public function actionSzcUpdate() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(100, '参数缺失');
        }
        $data = json_decode($json, true);
        $lotteryCode = $this->arrParameter($data[0], 'lottery_code');
        $periods = $this->arrParameter($data[0], 'periods');
        $record = LotteryRecord::findOne(['lottery_code' => $lotteryCode, 'periods' => $periods, 'status' => 2]);
        if (empty($record)) {
            return \Yii::jsonError(109, '该期不存在或还未开奖');
        }
        $testNums = $data[0]['test_number'];
        if (!empty($testNums)) {
            $record->test_numbers = $testNums;
            $trend = GroupTrendChart::findOne(['lottery_code' => $lotteryCode, 'periods' => $periods]);
            $trend->test_nums = $testNums;
            $trend->modify_time = date('Y-m-d H:i:s');
            $trend->save();
        }
        $record->modify_time = date('Y-m-d H:i:s');
        if (!$record->save()) {
            return \Yii::jsonResult(109, '数据保存失败', $record->getFirstErrors());
        }
        return \Yii::jsonResult(600, '数据保存成功', true);
    }

    /**
     * 赛事公告存储
     * @return type
     */
    public function actionMatchNotice() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        if (empty($json)) {
            return \Yii::jsonError(100, '参数缺失');
        }
        $data = json_decode($json, true);
        $matchType = $this->arrParameter($data[0], 'match_type');
        $title = $this->arrParameter($data[0], 'title');
        $notice = $this->arrParameter($data[0], 'notice');
        $noticeTime = $this->arrParameter($data[0], 'notice_time');
        $matchNotice = new MatchNotice;
        $matchNotice->match_type = $matchType;
        $matchNotice->notice_title = $title;
        $matchNotice->notice = $notice;
        $matchNotice->notice_time = $noticeTime;
        $matchNotice->create_time = date('Y-m-d H:i:s');
        if (!$matchNotice->validate()) {
            return \Yii::jsonResult(109, '数据验证失败', $matchNotice->getFirstErrors());
        }
        if (!$matchNotice->save()) {
            return \Yii::jsonResult(109, '数据保存失败', $matchNotice->getFirstErrors());
        }
        if ($matchType == 1) {
            $news = '足球公告';
        } elseif ($matchType == 2) {
            $news = '篮球公告';
        }
        Commonfun::sysAlert("官网公告 - " . $title, "通知 - " . $news, $title . '_' . $noticeTime, "待处理", "请即时处理！");
        return \Yii::jsonResult(600, '赛事公告保存成功', true);
    }

    /**
     * 大小球赔率数据接入 
     * @return json
     */
    public function actionZuDaxiaoOddsData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $insertTemp = "insert into zu_daxiao_odds (schedule_mid,company_name,country,handicap_type,handicap_name,cutoff_nums,odds_da,odds_da_trend,odds_xiao,odds_xiao_trend,profit_rate,create_time)values(':schedule_mid',':company_name',':country',':handicap_type',':handicap_name',':cutoff_nums',':odds_da',':odds_da_trend',':odds_xiao',':odds_xiao_trend',':profit_rate','" . date("Y-m-d H:i:s") . "');";
        $updateTemp = "update zu_daxiao_odds set handicap_name=':handicap_name',country=':country',cutoff_nums=':cutoff_nums',odds_da=':odds_da',odds_da_trend=':odds_da_trend',odds_xiao=':odds_xiao',odds_xiao_trend=':odds_xiao_trend',profit_rate=':profit_rate',modify_time='" . date("Y-m-d H:i:s") . "' where company_name=':company_name' and schedule_mid=':schedule_mid' and handicap_type=':handicap_type';";
        $mids = [];
        foreach ($data as $key => $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $mids)) {
                unset($data[$key]);
            } else {
                $mids[] = $str;
            }
        }
        $hasHistoryCount = (new \yii\db\Query())->select(["schedule_mid", "company_name", "handicap_type"])->from("zu_daxiao_odds")->where(["in", "CONCAT(`schedule_mid`,'_' ,`company_name`,'_',`handicap_type`)", $mids])->all();
        $hasMids = [];
        foreach ($hasHistoryCount as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            $hasMids[] = $str;
        }
        $sqlStr = "";
        foreach ($data as $val) {
            $str = $val["schedule_mid"] . "_" . $val["company_name"] . "_" . $val["handicap_type"];
            if (in_array($str, $hasMids)) {
                $sqlStr .= $this->strTemplateReplace($updateTemp, $val);
            } else {
                $sqlStr .= $this->strTemplateReplace($insertTemp, $val);
            }
        }
        $ret = \Yii::$app->db->createCommand($sqlStr)->execute();
        if ($ret === false) {
            return $this->jsonError(109, "大小球赔率数据处理失败");
        }
        return $this->jsonResult(600, "大小球赔率数据处理成功", $ret);
    }

    /**
     * 福建体彩开奖结果
     * @return type
     */
    public function actionTrendResultData() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $weekArr = Constants::WEEKARR;
        $lotteryCode = $this->arrParameter($data[0], "lottery_code");
        $periods = $this->arrParameter($data[0], "periods");
        $status = $this->arrParameter($data[0], "status");

        if ($status != 2) {
            return $this->jsonError(109, "数据状态错误！");
        }

        $lotRecord = LotteryRecord::findOne(["lottery_code" => $lotteryCode, "periods" => $periods]);
        $lotteryTime = $this->arrParameter($data[0], "lottery_time");
        $week = date("w", strtotime($lotteryTime));
        $week = $weekArr[$week];
        $lotteryNames = Constants::LOTTERY;
        if (empty($lotRecord)) {
            $lotRecord = new LotteryRecord();
            $lotRecord->periods = $periods;
            $lotRecord->lottery_code = $lotteryCode;
            $lotRecord->create_time = date("Y-m-d H:i:s");
        } else {
            $lotRecord->modify_time = date("Y-m-d H:i:s");
        }
        $lotRecord->lottery_name = $lotteryNames[$lotteryCode];
        $lotRecord->lottery_time = $lotteryTime;
        $lotRecord->week = $week;
        $lotRecord->status = $status;
        $lotteryNumbers = $this->arrParameter($data[0], "lottery_numbers");
        if ($lotteryCode == '2009') {
            $newLotteryNums = explode(',', $lotteryNumbers);
            $puke = Constants::PUKE_NUMS;
            foreach ($newLotteryNums as $v) {
                $new = explode('_', $v);
                $news[] = $new[0] . '_' . $puke[$new[1]];
            }
            $lotteryNumbers = implode(',', $news);
        }
        $totalSales = $this->arrParameter($data[0], "total_sales");
        $pool = $this->arrParameter($data[0], "pool");
        $lotRecord->lottery_numbers = $lotteryNumbers;
        $lotRecord->total_sales = $totalSales;
        $lotRecord->pool = $pool;


        if ($lotRecord->validate()) {
            $ret = $lotRecord->save();
            if ($ret === false) {
                return $this->jsonResult(109, "开奖记录更新失败", "");
            } else {
                $trend = new Trend();
                $ret = $trend->getCreateTrend($lotteryCode, $periods, $lotteryNumbers);
                if ($ret !== true) {
                    return $this->jsonResult(601, "走势图错误", "");
                }
            }
            return $this->jsonResult(600, "开奖记录更新成功", "");
        } else {
            return $this->jsonResult(109, "开奖记录更新错误！", $lotRecord->getFirstErrors());
        }
    }

    /**
     * 数字彩结果错误重新更新
     * @return type
     */
    public function actionUpdateSzcRecord() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $lotteryCode = $this->arrParameter($data[0], "lottery_code");
        $periods = $this->arrParameter($data[0], "periods");
        $status = $this->arrParameter($data[0], "status");
        $testNumbers = $this->arrParameter($data[0], 'test_numbers');

        if ($status != 2) {
            return $this->jsonError(109, "数据状态错误！");
        }

        $lotRecord = LotteryRecord::findOne(["lottery_code" => $lotteryCode, "periods" => $periods, 'status' => 2]);
        if (empty($lotRecord)) {
            return $this->jsonError(109, '该彩种此期数还未曾开奖');
        }
        $lotteryNumbers = $this->arrParameter($data[0], "lottery_numbers");
        if ($lotteryCode == '2009') {
            $newLotteryNums = explode(',', $lotteryNumbers);
            $puke = Constants::PUKE_NUMS;
            foreach ($newLotteryNums as $v) {
                $new = explode('_', $v);
                $news[] = $new[0] . '_' . $puke[$new[1]];
            }
            $lotteryNumbers = implode(',', $news);
        }
        $lotRecord->lottery_numbers = $lotteryNumbers;
        $lotRecord->test_numbers = $testNumbers;
        $lotRecord->modify_time = date('Y-m-d H:i:s');
        if ($lotRecord->validate()) {
            if (!$lotRecord->save()) {
                return $this->jsonError(109, "开奖记录更新失败");
            } else {
                $trend = new Trend();
                $ret = $trend->getCreateTrend($lotteryCode, $periods, $lotteryNumbers, 1, $testNumbers);
                if ($ret !== true) {
                    return $this->jsonError(601, "走势图错误");
                }
            }
            return $this->jsonResult(600, "开奖记录更新成功", true);
        } else {
            return $this->jsonResult(109, "开奖记录更新错误！", $lotRecord->getFirstErrors());
        }
    }

    /**
     * 更新任选开始时间
     * @return type
     */
    public function actionOptionalSaleTime() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $periods = $this->arrParameter($data[0], "periods");
        $optional = FootballFourteen::findOne(["periods" => $periods, 'status' => 1]);
        if (empty($optional)) {
            return \Yii::jsonError(109, '该期数据不存在或还未开奖');
        }
        $optional->beginsale_time = $this->arrParameter($data[0], 'beginsale_time');
        $optional->endsale_time = $this->arrParameter($data[0], 'endsale_time');
        $optional->modify_time = date('Y-m-d H:i:s');
        if ($optional->validate()) {
            $ret = $optional->save();
            if ($ret === false) {
                return \Yii::jsonError(109, '保存失败');
            }
            return $this->jsonError(600, '保存成功');
        } else {
            return \Yii::jsonResult(109, '保存失败', $optional->getFirstErrors());
        }
    }

    /**
     * 11X5开停售
     * @return type
     */
    public function actionSaleEleven() {
        $post = \Yii::$app->request->post();
        $json = $post["data"];
        $data = json_decode($json, true);
        $lotteryCode = $this->arrParameter($data[0], "lottery_code");
        $lotteryData = Lottery::findOne(["lottery_code" => $lotteryCode]);
        if (empty($lotteryData)) {
            return \Yii::jsonError(109, '该彩种不存在');
        }
        $lotteryData->sale_status = $this->arrParameter($data[0], 'sale_status');
        $lotteryData->modify_time = date('Y-m-d H:i:s');
        if ($lotteryData->validate()) {
            $ret = $lotteryData->save();
            if ($ret === false) {
                return \Yii::jsonError(109, '保存失败');
            }
            return $this->jsonError(600, '保存成功');
        } else {
            return \Yii::jsonResult(109, '保存失败', $lotteryData->getFirstErrors());
        }
    }

    /**
     * 世界杯基础赛程
     * @return type
     */
    public function actionWorldCupSchedule() {
        $request = \Yii::$app->request;
        $json = $request->post('data', '');
        $data = json_decode($json, true);
        $sort = $this->arrParameter($data[0], 'sort');
        $wcupSchedule = WorldcupSchedule::findOne(['sort' => $sort]);
        if (empty($wcupSchedule)) {
            $wcupSchedule = new WorldcupSchedule();
            $wcupSchedule->create_time = date('Y-m-d H:i:s');
        } else {
            $wcupSchedule->modify_time = date('Y-m-d H:i:s');
        }
        $wcupSchedule->game_city = $this->arrParameter($data[0], 'game_city');
        $wcupSchedule->game_field = $this->arrParameter($data[0], 'game_field');
        $wcupSchedule->game_level_id = $this->arrParameter($data[0], 'game_level_id');
        $wcupSchedule->game_level_name = $this->arrParameter($data[0], 'game_level_name');
        $wcupSchedule->schedule_date = $this->arrParameter($data[0], 'schedule_date');
        $wcupSchedule->start_time = $this->arrParameter($data[0], 'start_time');
        $wcupSchedule->sort = $this->arrParameter($data[0], 'sort');
        $wcupSchedule->group_id = $this->arrParameter($data[0], 'group_id');
        $wcupSchedule->group_name = $this->arrParameter($data[0], 'group_name');
        $wcupSchedule->home_team_name = $this->arrParameter($data[0], 'home_team_name');
        $wcupSchedule->home_img = $this->arrParameter($data[0], 'home_img');
        $wcupSchedule->visit_team_name = $this->arrParameter($data[0], 'visit_team_name');
        $wcupSchedule->visit_img = $this->arrParameter($data[0], 'visit_img');
        $wcupSchedule->bifen = $this->arrParameter($data[0], 'bifen');
        if($wcupSchedule->validate()) {
            if($wcupSchedule->save()) {
                return $this->jsonResult(600, '数据写入成功', true);
            }
            return $this->jsonResult(109, '数据写入失败', $wcupSchedule->errors);
        } else {
            return $this->jsonResult(109, '数据验证失败', $wcupSchedule->errors);
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
        $schedule = Schedule::findOne(['schedule_mid' => $scheduleMid]);
        if (empty($schedule)) {
            return $this->jsonError(109, '该赛程不存在');
        } 
        
        $schedule->schedule_bf = $this->arrParameter($data[0], 'schedule_bf');
        $schedule->schedule_rqspf = $this->arrParameter($data[0], 'schedule_rqspf');
        $schedule->schedule_bqcspf = $this->arrParameter($data[0], 'schedule_bqcspf');
        $schedule->schedule_zjqs = $this->arrParameter($data[0], 'schedule_zjqs');
        $schedule->schedule_spf = $this->arrParameter($data[0], 'schedule_spf');
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
