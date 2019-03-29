<?php

namespace app\modules\common\services;

use app\modules\common\models\Schedule;
use app\modules\competing\models\LanSchedule;
use app\modules\common\models\League;
use yii\db\Query;
use app\modules\common\models\UserAttention;
use app\modules\competing\models\LanPlayerCount;
use app\modules\competing\models\LanScheduleLive;
use app\modules\experts\models\ArticlesPeriods;
use app\modules\experts\services\ExpertService;

class ScheduleService {

    /**
     * 获取赔率
     * @auther GL zyl
     * @param type $mid
     * @return type
     */
    public function getOdds($mid) {
        $oddStr = ['odds3006', 'odds3007', 'odds3008', 'odds3009', 'odds3010'];
        $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.visit_short_name', 'schedule.home_short_name', 'h.scale_3010_3', 'h.scale_3010_1', 'h.scale_3010_0', 'h.scale_3006_3', 'h.scale_3006_1', 'h.scale_3006_0'];
        $scheOdds = Schedule::find()->select($field)
                ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
                ->with($oddStr)
                ->where(['schedule.schedule_mid' => $mid])
                ->asArray()
                ->all();
        return $scheOdds;
    }

    /**
     * 获取联赛
     * @auther GL zyl
     * @return type
     */
    public function getLeague() {
        $field = ['l.league_id', 'l.league_category_id', 'l.league_code', 'l.league_long_name', 'l.league_short_name'];
        $leagueData = Schedule::find()->select($field)
                ->innerJoin('schedule_result sr', 'sr.schedule_mid = schedule.schedule_mid')
                ->leftJoin('league as l', 'l.league_id = schedule.league_id and l.league_status = 1 and l.league_type = 1')
                ->groupBy('l.league_id')
                ->where(['schedule.schedule_status' => 1, 'sr.status' => 0])
                ->orderBy('l.league_id')
                ->asArray()
                ->all();
        return $leagueData;
    }

    /**
     * 获取竞彩首页列表
     * @auther GL zyl
     * @param type $pn
     * @param type $size
     * @param type $where
     * @param type $sWhere
     * @param type $eWhere
     * @param type $lWhere
     * @return type
     */
    public function getScheduleList($pn, $size, $where, $lWhere, $sWhere, $orderBy, $gWhere, $eWhere, $payType) {
        $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
            'schedule.rq_nums', 'sr.schedule_result_3007', 'sr.schedule_result_sbbf', 'sr.match_time', 'sr.status', 'schedule.league_name  league_short_name', 'h.home_team_rank', 'h.visit_team_rank', 'st.home_red_num',
            'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num', 'st.home_corner_num', 'st.visit_corner_num', 'st.odds_3006 as st_odds_3006','st.odds_3007 as st_odds_3007', 
            'st.odds_3008 as st_odds_3008', 'st.odds_3009 as st_odds_3009', 'st.odds_3010 as st_odds_3010', 'h.home_team_league', 'h.visit_team_league', 'oe.odds_3 old_odds_3', 'oe.odds_3_trend old_odds_3_trend',
            'oe.odds_1 old_odds_1', 'oe.odds_1_trend old_odds_1_trend', 'oe.odds_0 old_odds_0', 'oe.odds_0_trend old_odds_0_trend', 'ne.odds_3 new_odds_3', 'ne.odds_3_trend new_odds_3_trend',
            'ne.odds_1 new_odds_1', 'ne.odds_1_trend new_odds_1_trend', 'ne.odds_0 new_odds_0', 'ne.odds_0_trend new_odds_0_trend', 'l.league_color'];
        $total = Schedule::find()->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')->where($where)->andWhere($lWhere)->andWhere($gWhere)->andWhere($eWhere)->andWhere($sWhere)->count();
        $scheDetail = Schedule::find()->select($field)
                ->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')
                ->leftJoin('league as l', 'l.league_id = schedule.league_id and l.league_type = 1')
                ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
                ->leftJoin('europe_odds oe', 'oe.schedule_mid = schedule.schedule_mid and oe.handicap_type = 1 and oe.company_name = "Bet365"')
                ->leftJoin('europe_odds ne', 'ne.schedule_mid = schedule.schedule_mid and ne.handicap_type = 2 and ne.company_name = "Bet365"')
                ->leftJoin('schedule_technic as st', 'st.schedule_mid = schedule.schedule_mid')
                ->with(['odds3010'])
                ->where($where)
                ->andWhere($lWhere)
                ->andWhere($sWhere)
                ->andWhere($gWhere)
                ->andWhere($eWhere);
        if ($pn != 0) {
            $scheDetail = $scheDetail->limit($size)->offset(($pn - 1) * $size)->orderBy($orderBy)->asArray()->all();
            $pages = ceil($total / $size);
        } else {
            $scheDetail = $scheDetail->orderBy($orderBy)->asArray()->all();
            $pn = 1;
            $pages = 1;
        }
        $expertService = new ExpertService();
        $scheData = $expertService->getScheduleArtNums($scheDetail, 1, $payType);
        $data['scheDetail'] = $scheData;
        $data = ['page' => $pn, 'size' => count($scheData), 'pages' => $pages, 'total' => $total, 'data' => $data];
        return $data;
    }

    /**
     * 获取历史交锋
     * @auther GL zyl
     * @param type $mid
     * @return type
     */
    public function getHistoryCount($mid) {
        $field = ['h.double_play_num', 'h.num3', 'h.num1', 'h.num0', 'h.home_num_3', 'h.home_num_1', 'h.home_num_0', 'h.visit_num_3', 'h.visit_num_1', 'h.visit_num_0', 'h.home_team_rank', 'h.visit_team_rank',
            'h.scale_3010_3', 'h.scale_3010_1', 'h.scale_3010_0', 'h.scale_3006_3', 'h.scale_3006_1', 'h.scale_3006_0', 'h.europe_odds_3', 'h.europe_odds_1', 'h.europe_odds_0', 'h.home_team_league', 'h.visit_team_league',
            'p.json_data', 'p.pre_result_title', 's.home_short_name'];
        $data = (new Query())->select($field)
                ->from("schedule s")
                ->leftJoin('pre_result p', 'p.schedule_mid = s.schedule_mid')
                ->leftJoin('history_count h', 'h.schedule_mid = s.schedule_mid')
                ->where(["s.schedule_mid" => $mid])
                ->one();
        if (empty($data)) {
            return ['code' => 109, 'msg' => '未找到对应赛程统计'];
        }
        $total = ArticlesPeriods::find()->innerJoin('expert_articles as e', 'e.expert_articles_id = articles_periods.articles_id and e.article_status = 3')->where(['periods' => $mid])->count();
        $jsonData = json_decode($data["json_data"], true);
        $data["avg_visit_per"] = sprintf("%.1f", $jsonData["avg_visit_per"]);
        $data["avg_home_per"] = sprintf("%.1f", $jsonData["avg_home_per"]);
        $schedule = Schedule::findOne(["schedule_mid" => $mid]);
        $league = League::findOne(["league_id" => $schedule->league_id, 'league_type' => 1]);
        $data["league_name"] = $league->league_short_name;
        $data['article_total'] = $total;
        return ['code' => 600, 'msg' => '获取成功', 'data' => $data];
    }

    /**
     * 获取赛程信息
     * @param type $mid
     * @return type
     */
    public function getScheduleInfo($mid) {
        $status = [
            "0" => "未开赛",
            "1" => "比赛中",
            "2" => "完结",
            "3" => "取消",
            "4" => "延迟",
            "5" => "完结",
            "6" => "未出赛果"
        ];
        $data = (new Query())->select(["s.home_team_name", "s.visit_team_name", "h.home_team_rank", "h.visit_team_rank", "p.average_home_percent", "p.average_visit_percent", "t1.team_code as home_team_mid", "t1.team_img as home_team_img", "t2.team_code as visit_team_mid", "t2.team_img as visit_team_img", "s.start_time", "s.schedule_code", "l.league_short_name", "h.visit_team_league", "h.home_team_league", 's.schedule_status endsale_status', 's.endsale_time'])->from("schedule s")->join("left join", "team t1", "t1.team_id=s.home_team_id")->join("left join", "team t2", "t2.team_id=s.visit_team_id")->join("left join", "league l", "l.league_id=s.league_id")->join("left join", "history_count h", "h.schedule_mid=s.schedule_mid")->join("left join", "pre_result p", "p.schedule_mid=s.schedule_mid")->where(["s.schedule_mid" => $mid])->one();
//       $data = (new Query())->select(["s.home_team_name", "s.visit_team_name", "h.home_team_rank", "h.visit_team_rank", "p.average_home_percent", "p.average_visit_percent", "t1.team_code as home_team_mid", "t1.team_img as home_team_img", "t2.team_code as visit_team_mid", "t2.team_img as visit_team_img", "s.start_time", "s.schedule_code", "l.league_short_name"])->from("schedule s")->join("left join", "team t1", "t1.team_id=s.home_team_id")->join("left join", "team t2", "t2.team_id=s.visit_team_id")->join("left join", "league l", "l.league_id=s.league_id")->join("left join", "history_count h", "h.schedule_mid=s.schedule_mid")->join("left join", "pre_result p", "p.schedule_mid=s.schedule_mid")->where(["s.schedule_mid" => $mid])->one();
//       
        if (empty($data)) {
            return ['msg' => '未找到该赛程', 'data' => null];
        }
        $result = (new Query())->select("schedule_result_3007,status")->from("schedule_result")->where(["schedule_mid" => $mid])->one();
        $result['status_name'] = $status[$result['status']];
        if (strtotime($data['endsale_time']) <= time()) {
            $data['endsale_status'] = 2;
        }
//        if (isset($status[$result["status"]])) {
//            $result["status_name"] = $status[$result["status"]];
//        } else {
//            $result["status_name"] = "";
//        }
        return ['msg' => '获取成功', 'data' => ['info' => $data, 'result' => $result]];
    }

    /**
     * 双方历史交战比赛
     * @param type $mid
     * @param type $teamType
     * @param type $size
     * @param type $sameLeague
     * @return type
     */
    public function getDoubleHistoryMatch($mid, $teamType, $size, $sameLeague) {
        $schedule = Schedule::findOne(["schedule_mid" => $mid]);
        if ($schedule == null) {
            return['msg' => '未找到该赛程', 'data' => null];
        }

        $t1 = \app\modules\common\models\Team::findOne(["team_id" => $schedule->home_team_id]);
        $t2 = \app\modules\common\models\Team::findOne(["team_id" => $schedule->visit_team_id]);
        $homeTeamMid = $t1->team_code;
        $visitTeamMid = $t2->team_code;
        $query = (new Query())->select("schedule_mid,league_code,league_name,play_time,home_team_name,home_team_mid,visit_team_mid,visit_team_name,result_3007,result_3009_b,result_3010")->from("schedule_history")->where(["<", "play_time", $schedule->start_time]);
        if ($teamType == 1) {
            $query = $query->andWhere(["home_team_mid" => $homeTeamMid, "visit_team_mid" => $visitTeamMid])->andWhere(['!=', 'result_3007', '']);
        } else if ($teamType == 2) {
            $query = $query->andWhere(["home_team_mid" => $visitTeamMid, "visit_team_mid" => $homeTeamMid])->andWhere(['!=', 'result_3007', '']);
        } else {
            $query = $query->andWhere(["or", ["home_team_mid" => $homeTeamMid, "visit_team_mid" => $visitTeamMid], ["home_team_mid" => $visitTeamMid, "visit_team_mid" => $homeTeamMid]])->andWhere(['!=', 'result_3007', '']);
        }

        if ($sameLeague == 1) {
            $league = (new Query())->select("league_code")->from("league")->where(["league_id" => $schedule->league_id])->one();
            $query = $query->andWhere(["league_code" => $league["league_code"]]);
        }
        $doubleConData = $query->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $data = $this->scheduleHistoryDeal($doubleConData, $homeTeamMid);
        return ['msg' => '获取成功', 'data' => $data];
    }

    /**
     * 单方历史交战比赛
     * @param type $mid
     * @param type $teamMid
     * @param type $size
     * @param type $sameLeague
     * @param type $teamType
     * @return type
     */
    public function getHistoryMatch($mid, $teamMid, $size, $sameLeague, $teamType) {
        $schedule = Schedule::findOne(["schedule_mid" => $mid]);
        if ($schedule == null) {
            return['msg' => '未找到该赛程', 'data' => null];
        }

        $query = (new Query())->select("schedule_mid,league_code,league_name,play_time,home_team_name,home_team_mid,visit_team_mid,visit_team_name,result_3007,result_3009_b,result_3010")->from("schedule_history")->where(["<", "play_time", $schedule->start_time])->andWhere(["!=", "result_3007", ""]);
        if ($teamType == 1) {
            $query = $query->andWhere(["home_team_mid" => $teamMid]);
        } else if ($teamType == 2) {
            $query = $query->andWhere(["visit_team_mid" => $teamMid]);
        } else {
            $query = $query->andWhere(["or", ["visit_team_mid" => $teamMid], ["home_team_mid" => $teamMid]]);
        }
        if ($sameLeague == 1) {
            $league = (new Query())->select("league_code")->from("league")->where(["league_id" => $schedule->league_id])->one();
            $query = $query->andWhere(["league_code" => $league["league_code"]]);
        }
        $conData = $query->groupBy("play_time")->orderBy("play_time desc")->limit($size)->all();
        $data = $this->scheduleHistoryDeal($conData, $teamMid);
        return ['msg' => '获取成功', 'data' => $data];
    }

    /**
     * 实力对比
     * @param type $mid
     * @return type
     */
    public function getStrengthContrast($mid) {
        $result = (new Query())->select("json_data,pre_result_title")->from("pre_result")->where(["schedule_mid" => $mid])->one();
        if (empty($result)) {
            return['msg' => '未找到该赛程', 'data' => null];
        }
        if (empty($result["json_data"])) {
            return['msg' => '该赛程未有实力分析', 'data' => null];
        }
        $data = json_decode($result["json_data"], true);
        $data["avg_visit_per"] = sprintf("%.1f", $data["avg_visit_per"]);
        $data["avg_home_per"] = sprintf("%.1f", $data["avg_home_per"]);
        $data['pre_result_title'] = $result['pre_result_title'];
        return ['msg' => '获取成功', 'data' => $data];
    }

    /**
     * 球队未来赛程
     * @param type $mid
     * @return type
     */
    public function getFutureSchedule($mid) {
        $schedule = Schedule::findOne(["schedule_mid" => $mid]);
        if (empty($schedule)) {
            return['msg' => '未找到该赛程', 'data' => null];
        }

        $t1 = \app\modules\common\models\Team::findOne(["team_id" => $schedule->home_team_id]);
        $t2 = \app\modules\common\models\Team::findOne(["team_id" => $schedule->visit_team_id]);
        $homeTeamMid = $t1->team_code;
        $visitTeamMid = $t2->team_code;

        $homeQuery = (new Query())->select("schedule_mid,league_code,league_name,play_time,home_team_name,home_team_mid,visit_team_mid,visit_team_name")->from("schedule_history")->where([">", "play_time", $schedule->start_time])->andWhere(["or", ["visit_team_mid" => $homeTeamMid], ["home_team_mid" => $homeTeamMid]]);
        $homeList = $homeQuery->orderBy("play_time asc")->all();
        foreach ($homeList as &$val) {
            $val["later_days"] = ceil((strtotime($val["play_time"]) - time()) / 24 / 3600);
        }
        $visitQuery = (new Query())->select("schedule_mid,league_code,league_name,play_time,home_team_name,home_team_mid,visit_team_mid,visit_team_name")->from("schedule_history")->where([">", "play_time", $schedule->start_time])->andWhere(["or", ["visit_team_mid" => $visitTeamMid], ["home_team_mid" => $visitTeamMid]]);
        $visitList = $visitQuery->orderBy("play_time asc")->all();
        foreach ($visitList as &$val) {
            $val["later_days"] = ceil((strtotime($val["play_time"]) - time()) / 24 / 3600);
        }
        $data["home_list"] = $homeList;
        $data["visit_list"] = $visitList;
        return ['msg' => '获取成功', 'data' => $data];
    }

    /**
     * 获取预测赛果
     * @param type $mid
     * @return type
     */
    public function getPreResult($mid) {
        $info = (new Query())->select("pre_result_title,pre_result_3010,pre_result_3007,confidence_index,expert_analysis")->from("pre_result")->where(["schedule_mid" => $mid])->one();
        $list = (new Query())->select("team_type,content")->from("schedule_remind")->where(["schedule_mid" => $mid, 'schedule_type' => 1])->all();
        $odds = $this->getOdds($mid);
        return ['info' => $info, 'list' => $list, 'odds' => $odds];
    }

    /**
     * 获取亚盘赔率
     * @param type $mid
     * @return type
     */
    public function getAsianHandicap($mid) {
        $field = ["a.company_name as company_name", "a.handicap_name as begin_handicap_name", "a.home_discount as begin_home_discount", "a.let_index as begin_let_index", "a.visit_discount as begin_visit_discount",
            "b.handicap_name as handicap_name", "b.home_discount as home_discount", "b.let_index as let_index", "b.visit_discount as visit_discount", "a.home_discount_trend as begin_home_discount_trend",
            "a.visit_discount_trend as begin_visit_discount_trend", "b.home_discount_trend as home_discount_trend", "b.visit_discount_trend as visit_discount_trend"];
        $data = (new Query())->select($field)->from("asian_handicap a")->join("left join", "asian_handicap b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")->where(["a.schedule_mid" => $mid, "a.handicap_type" => 1])->all();
        return $data;
    }

    /**
     * 获取欧盘赔率
     * @param type $mid
     * @return type
     */
    public function getEuropeOdds($mid) {
        $field = ["a.company_name as company_name", "a.handicap_name as begin_handicap_name", "a.odds_3 as begin_odds_3", "a.odds_1 as begin_odds_1", "a.odds_0 as begin_odds_0", "b.handicap_name as handicap_name",
            "b.odds_3 as odds_3", "b.odds_1 as odds_1", "b.odds_0 as odds_0", 'a.odds_3_trend as begin_odds_3_trend', 'a.odds_1_trend as begin_odds_1_trend', 'a.odds_0_trend as begin_odds_0_trend',
            'b.odds_3_trend as odds_3_trend', 'b.odds_1_trend as odds_1_trend', 'b.odds_0_trend as odds_0_trend',];
        $data = (new Query())->select($field)->from("europe_odds a")->join("left join", "europe_odds b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")->where(["a.schedule_mid" => $mid, "a.handicap_type" => 1])->all();
        return $data;
    }

    /**
     * 获取比赛实况
     * @param type $mid
     * @return type
     */
    public function getScheduleLives($mid) {
        $scheduleEvents = (new Query())->select(["team_type", "team_name", "event_type", "event_type_name", "event_content", "event_time"])->from("schedule_event")->where(["schedule_mid" => $mid])->orderBy("event_time asc")->all();
        $scheduleTechnic = (new Query())->select(["home_ball_rate", "visit_ball_rate", "home_shoot_num", "visit_shoot_num", "home_shoot_right_num", "visit_shoot_right_num", "home_corner_num", "visit_corner_num", "home_foul_num", "visit_foul_num"])->from("schedule_technic")->where(["schedule_mid" => $mid])->one();
        $scheduleResult = (new Query())->select(["schedule_result_3007"])->from("schedule_result")->where(["schedule_mid" => $mid])->one();
        return ['events' => $scheduleEvents, 'technic' => $scheduleTechnic, "schedule_result_3007" => $scheduleResult["schedule_result_3007"]];
    }

    /**
     * 赛程历史处理
     * @auther GL ctx
     * @return array
     */
    public function scheduleHistoryDeal($data, $mid, $needList = true) {
        $result = [];
        $count = count($data);
        $num_3 = 0;
        $num_1 = 0;
        $num_0 = 0;
        $num_home_3 = 0;
        $num_home_1 = 0;
        $num_home_0 = 0;
        $gainBalls = 0;
        $loseBalls = 0;
        $key = 0;
        $result["list"] = [];
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
                $val["result_3010_home"] = 3;
                $val["result_3010_home_name"] = "胜";
                $num_home_3++;
            } else if ($homeJq == $visitJq) {
                $val["result_3010_home"] = 1;
                $val["result_3010_home_name"] = "平";
                $num_home_1++;
            } else {
                $val["result_3010_home"] = 0;
                $val["result_3010_home_name"] = "负";
                $num_home_0++;
            }

            if ($arr[0] > $arr[1]) {
                $num_3++;
            } else if ($arr[0] == $arr[1]) {
                $num_1++;
            } else {
                $num_0++;
            }
            $result["list"][$key] = $val;
            $key++;
        }
        $result["num_3"] = $num_3;
        $result["num_1"] = $num_1;
        $result["num_0"] = $num_0;
        $result["num_home_3"] = $num_home_3;
        $result["num_home_1"] = $num_home_1;
        $result["num_home_0"] = $num_home_0;
        if ($needList == true) {
            $result["count"] = $count;
        } else {
            unset($result["list"]);
            $result["integral"] = $num_3 * 3 + $num_1;
            $result["average_gain_balls"] = 0 ? 0 : number_format($gainBalls / $count, 1);
            $result["average_lose_balls"] = 0 ? 0 : number_format($loseBalls / $count, 1);
        }
        return $result;
    }

    /**
     * 关注赛程
     * @param type $userId
     * @param type $mid
     * @return type
     */
    public function setAttention($userId, $mid) {
        $exist = UserAttention::find()->where(['user_id' => $userId, 'schedule_mid' => $mid])->one();
        if (!empty($exist)) {
            return ['code' => 109, 'msg' => '该赛程已关注'];
        }
        $attention = new UserAttention;
        $attention->user_id = $userId;
        $attention->schedule_mid = $mid;
        $attention->create_time = date('Y-m-d H:i:s');
        if (!$attention->validate()) {
            return ['code' => 109, 'msg' => '数据验证失败'];
        }
        if (!$attention->save()) {
            return ['code' => 109, 'msg' => '数据保存失败'];
        }
        return ['code' => 600, 'msg' => '关注成功'];
    }

    /**
     * 赛程关注取消
     * @param type $userId
     * @param type $mid
     * @return type
     */
    public function deleteAttention($userId, $mid) {
        $delete = UserAttention::deleteAll(['user_id' => $userId, 'schedule_mid' => $mid]);
        if ($delete == false) {
            return ['code' => 109, 'msg' => '取消关注失败'];
        }
        return ['code' => 600, 'msg' => '取消成功'];
    }

    /**
     * 赛程列表
     * @param type $pn
     * @param type $size
     * @param type $lWhere
     * @param type $date
     * @return boolean
     */
    public function getNoEndSchedule($pn, $size, $lWhere, $date, $payType) {
        $format = date('Y-m-d H:i:s');
        $scheDate = Schedule::find()->select(['schedule_date', 'count(schedule_mid) count_nums', 'sum(hot_status) hot_nums'])->where($lWhere)->andWhere(['>', 'start_time', $format])->groupBy('schedule_date')->indexBy('schedule_date')->orderBy('schedule_date')->asArray()->all();
        $where['schedule_status'] = 1;
        $total = Schedule::find()->where($where)->andWhere(['>', 'schedule.start_time', date('Y-m-d H:i:s')])->count();
        $pages = ceil($total / $size);
        $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
            'schedule.rq_nums', 'sr.status', 'schedule.league_name  league_short_name', 'h.home_team_rank', 'h.visit_team_rank', 'h.home_team_league', 'h.visit_team_league', 'schedule.hot_status', 'l.league_color'];
        $scheDetail = Schedule::find()->select($field)
                ->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')
                ->leftJoin('league as l', 'l.league_id = schedule.league_id and l.league_type = 1')
                ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
                ->with(['odds3010'])
                ->where($where)
                ->andWhere($lWhere)
                ->andWhere(['>', 'schedule.start_time', date('Y-m-d H:i:s')]);
        if ($pn != 0) {
            $scheDetail = $scheDetail->limit($size)->offset(($pn - 1) * $size)->orderBy('hot_status desc,schedule.start_time, schedule.schedule_mid')->asArray()->all();
            $pages = ceil($total / $size);
        } else {
            $scheDetail = $scheDetail->orderBy('hot_status desc,schedule.start_time, schedule.schedule_mid')->asArray()->all();
            $pn = 1;
            $pages = 1;
        }
        $expertService = new ExpertService();
        $scheData = $expertService->getScheduleArtNums($scheDetail, 1, $payType);
        $n = 0;
        $plainsNums = [];
        foreach ($scheData as &$val) {
            $val['date'] = date('Y-m-d', strtotime($val['schedule_date']));
            $schedultDate = (string) $val['schedule_date'];
            $gameDate = date('Y-m-d', strtotime($schedultDate));
            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            $val['week'] = '周' . $weekarray[date('w', strtotime($gameDate))];
            if ($val['hot_status'] == 1) {
                $n++;
            }
            $plainsNums[$val['date']] = $scheDate[$schedultDate]['count_nums'] - $scheDate[$schedultDate]['hot_nums'];
        }
//        if(!empty($hotSche)) {
//            $data['hot']['schedule'] = $hotSche;
//            $data['hot']['nums'] = array_sum(array_column($scheDate, 'hot_nums'));
//            $data['hot']['title'] = '火爆竞猜中';
//        }
        $data['scheDetail'] = $scheData;
        return ['page' => $pn, 'size' => count($scheData), 'pages' => $pages, 'total' => $total, 'data' => $data, 'hotNums' => $n, 'plainNums' => $plainsNums];
    }

    /**
     * 关注赛程列表
     * @param type $userId
     * @param type $pn
     * @param type $size
     * @param type $leagueId
     * @return type
     */
    public function getAttentionList($sWhere, $payType) {
//        $where['user_id'] = $userId;
//        $lwhere = [];
//        if (!empty($leagueId)) {
//            $lwhere = ['in', 's.league_id', $leagueId];
//        }
        $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time', 'schedule.rq_nums', 'sr.schedule_result_3006',
            'sr.schedule_result_3007', 'sr.schedule_result_3008', 'sr.schedule_result_3009', 'sr.schedule_result_3010', 'sr.schedule_result_sbbf', 'sr.match_time', 'sr.status', 'schedule.league_name  league_short_name', 'sr.odds_3006 as sr_odds_3006',
            'sr.odds_3007 as sr_odds_3007', 'sr.odds_3008 as sr_odds_3008', 'sr.odds_3009 as sr_odds_3009', 'sr.odds_3010 as sr_odds_3010', 'h.home_team_rank', 'h.visit_team_rank', 'h.home_team_league', 'h.visit_team_league',
            'st.home_red_num', 'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num', 'st.home_corner_num', 'st.visit_corner_num', 'st.odds_3006 as st_odds_3006', 'st.odds_3007 as st_odds_3010',
            'st.odds_3008 as st_odds_3008', 'st.odds_3009 as st_odds_3009', 'st.odds_3010 as st_odds_3010', 'l.league_color'];
        $scheDetail = Schedule::find()->select($field)
                ->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')
                ->leftJoin('league as l', 'l.league_id = schedule.league_id and l.league_type = 1')
                ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
//                ->leftJoin('team as ht', 'ht.team_id = schedule.home_team_id')
//                ->leftJoin('team as vt', 'vt.team_id = schedule.visit_team_id')
                ->leftJoin('schedule_technic as st', 'st.schedule_mid = schedule.schedule_mid')
                ->with(['odds3010'])
                ->where($sWhere)
                ->orderBy('schedule.start_time desc,schedule.schedule_mid desc')
                ->asArray()
                ->all();
        $list = [];
        $scheduleList = [];
        $cancelAttent = [];
        $midArr = array_column($scheDetail, 'schedule_mid');
        $total = $this->getScheduleTotal($midArr, 1, $payType);
        foreach ($scheDetail as &$value) {
            if ($value['start_time'] < date('Y-m-d H:i:s', strtotime('-3 day'))) {
                $cancelAttent[] = $value['schedule_mid'];
                continue;
            }
            $value['article_total'] = $total[$value['schedule_mid']];
            $value['is_attent'] = 1;
            $shcedultDate = date('Ymd', strtotime($value['start_time']));
            $gameDate = date('Y-m-d', strtotime($shcedultDate));
            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            if (array_key_exists($gameDate, $list)) {
                $list[$gameDate]['game'][] = $value;
                $list[$gameDate]['field_num'] += 1;
            } else {
                $list[$gameDate]['game'][] = $value;
                $list[$gameDate]['field_num'] = 1;
                $list[$gameDate]['game_date'] = date('Y-m-d', strtotime($gameDate));
                $list[$gameDate]['week'] = '周' . $weekarray[date('w', strtotime($gameDate))];
            }
        }
        foreach ($list as $val) {
            $scheduleList[] = $val;
        }
        $data['scheDetail'] = $scheduleList;
        $data['cancelAttent'] = $cancelAttent;
        $data = ['data' => $data];
        return $data;
    }

    /**
     * 获取竞彩首页列表
     * @auther GL zyl
     * @param type $pn
     * @param type $size
     * @param type $where
     * @param type $sWhere
     * @param type $eWhere
     * @param type $lWhere
     * @return type
     */
    public function getEndScheduleList($pn, $size, $where, $lWhere, $sWhere, $orderBy, $gWhere, $eWhere, $payType) {
        $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
            'schedule.rq_nums', 'sr.schedule_result_3006', 'sr.schedule_result_3007', 'sr.schedule_result_3008', 'sr.schedule_result_3009', 'sr.schedule_result_3010', 'sr.schedule_result_sbbf', 'schedule.league_name  league_short_name',
            'sr.odds_3006 as sr_odds_3006', 'sr.odds_3007 as sr_odds_3007', 'sr.odds_3008 as sr_odds_3008', 'sr.odds_3009 as sr_odds_3009', 'sr.odds_3010 as sr_odds_3010', 'sr.match_time', 'sr.status', 'h.home_team_rank',
            'h.visit_team_rank', 'st.home_red_num', 'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num', 'st.home_corner_num', 'st.visit_corner_num', 'h.home_team_league', 'h.visit_team_league'];
        $total = Schedule::find()->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')->where($where)->andWhere($lWhere)->andWhere($gWhere)->andWhere($eWhere)->andWhere($sWhere)->count();
        $scheDetail = Schedule::find()->select($field)
                ->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')
//                ->leftJoin('league as l', 'l.league_id = schedule.league_id')
                ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
//                ->leftJoin('team as ht', 'ht.team_id = schedule.home_team_id')
//                ->leftJoin('team as vt', 'vt.team_id = schedule.visit_team_id')
                ->leftJoin('schedule_technic as st', 'st.schedule_mid = schedule.schedule_mid')
                ->where($where)
                ->andWhere($lWhere)
                ->andWhere($sWhere)
                ->andWhere($gWhere)
                ->andWhere($eWhere);
        if ($pn != 0) {
            $scheDetail = $scheDetail->limit($size)->offset(($pn - 1) * $size)->orderBy($orderBy)->asArray()->all();
            $pages = ceil($total / $size);
        } else {
            $scheDetail = $scheDetail->orderBy($orderBy)->asArray()->all();
            $pn = 1;
            $pages = 1;
        }
        $expertService = new ExpertService();
        $scheData = $expertService->getScheduleArtNums($scheDetail, 1, $payType);
        $data['scheDetail'] = $scheData;
        $data = ['page' => $pn, 'size' => count($scheDetail), 'pages' => $pages, 'total' => $total, 'data' => $data];
        return $data;
    }

    /**
     * 获取竞彩篮球首页列表
     * @auther  xiejh
     * @param type $pn
     * @param type $size
     * @param type $where
     * @param type $sWhere
     * @param type $eWhere
     * @return type
     */
    public function getLanScheduleList($payType, $pn, $size, $where, $lWhere, $orderBy, $gWhere, $eWhere, $type = '', $date = '') {
        if ($type == 2) {
            $format = date('Y-m-d H:i:s');
            $scheDate = LanSchedule::find()->select(['schedule_date', 'count(schedule_mid) count_nums', 'sum(hot_status) hot_nums'])->where($lWhere)->andWhere(['>', 'start_time', $format])->groupBy('schedule_date')->indexBy('schedule_date')->orderBy('schedule_date')->asArray()->all();
            if (empty($scheDate)) {
                $data['scheDetail'] = [];
                $data['date_arr'] = $scheDate;
                return ['data' => $data];
            }
//
//            if ($date != '') {
//                $where['lan_schedule.schedule_date'] = (int) date('Ymd', strtotime($date));
//            } else {
//                $where['lan_schedule.schedule_date'] = $scheDate[0]['schedule_date'];
//            }
        }

        $field = ['lan_schedule.schedule_mid', 'sr.result_qcbf', 'lan_schedule.schedule_code', 'lan_schedule.league_id', 'lan_schedule.league_name', 'lan_schedule.schedule_date', 'l.league_color',
            'lan_schedule.visit_short_name', 'lan_schedule.home_short_name', 'lan_schedule.start_time', 'sr.result_status', "sr.guest_one", "sr.schedule_fc as fencha", "sr.schedule_zf as zongfen",
            "sr.guest_two", "sr.match_time", "sr.guest_three", "sr.guest_four", "sr.guest_add_one", "sr.guest_add_two", "sr.guest_add_three", "sr.guest_add_four", 'lan_schedule.hot_status'];
        $total = LanSchedule::find()->innerJoin('lan_schedule_result as sr', 'sr.schedule_mid = lan_schedule.schedule_mid')->where($where)->andWhere($lWhere)->andWhere($gWhere)->andWhere($eWhere)->count();
        $scheDetail = LanSchedule::find()->select($field)
                ->innerJoin('lan_schedule_result as sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('league l', 'l.league_code = lan_schedule.league_id and l.league_type = 2')
                ->with(["odds3002", "odds3004"])
                ->where($where)
                ->andWhere($lWhere)
                ->andWhere($gWhere)
                ->andWhere($eWhere);
        if ($pn != 0) {
            $scheDetail = $scheDetail->orderBy($orderBy)->limit($size)->offset(($pn - 1) * $size)->asArray()->all();
            $pages = ceil($total / $size);
        } else {
            $scheDetail = $scheDetail->orderBy($orderBy)->asArray()->all();
            $pn = 1;
            $pages = 1;
        }
        $scheData = [];
        foreach ($scheDetail as $k => &$v) {
//            $v['start_time'] = date('H:i', strtotime($v['start_time']));
            $v["bifen"] = [];
            if (!empty($v["guest_one"]) && ($v["guest_one"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_one"]);
            }
            if (!empty($v["guest_two"]) && ($v["guest_two"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_two"]);
            }
            if (!empty($v["guest_three"]) && ($v["guest_three"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_three"]);
            }
            if (!empty($v["guest_four"]) && ($v["guest_four"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_four"]);
            }
            if (!empty($v["guest_add_one"]) && ($v["guest_add_one"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_add_one"]);
            }
            if (!empty($v["guest_add_two"]) && ($v["guest_add_two"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_add_two"]);
            }
            if (!empty($v["guest_add_three"]) && ($v["guest_add_three"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_add_three"]);
            }
            if (!empty($v["guest_add_four"]) && ($v["guest_add_four"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_add_four"]);
            }
            if (!empty($v["result_qcbf"]) && ($v["result_qcbf"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["result_qcbf"]);
            }
            $v['match_time'] = str_replace('_', "'", $v['match_time']);
            unset($v["guest_one"], $v["guest_two"], $v["guest_three"], $v["guest_four"]);
            unset($v["guest_add_one"], $v["guest_add_two"], $v["guest_add_three"], $v["guest_add_four"]);
        }
        $expertService = new ExpertService();
        $scheInfo = $expertService->getScheduleArtNums($scheDetail, 2, $payType);
        if ($type == 2) {
            $n = 0;
            $plainsNums = [];
            $scheData['hotSchedule'] = [];
            $scheData['plainSchedule'] = [];
            foreach ($scheInfo as &$val) {
                $val['date'] = date('Y-m-d', strtotime($val['schedule_date']));
                $schedultDate = (string) $val['schedule_date'];
                $gameDate = date('Y-m-d', strtotime($schedultDate));
                $weekarray = array("日", "一", "二", "三", "四", "五", "六");
                $val['week'] = '周' . $weekarray[date('w', strtotime($gameDate))];
                if ($val['hot_status'] == 1) {
                    $scheData['hotSchedule'][] = $val;
                    $n++;
                } else {
                    $scheData['plainSchedule'][] = $val;
                }
                $plainsNums[$val['date']] = isset($scheDate[$schedultDate]) ? $scheDate[$schedultDate]['count_nums'] - $scheDate[$schedultDate]['hot_nums'] : 0;


//                if($val['hot_status'] == 1) {
//                    $data['hotSchedule'][] = $val;
//                }  else {
//                    $data['plainSchedule'][] = $val;
//                }
            }
            $data['hotNums'] = $n;
            $data['plainNums'] = $plainsNums;
        } else {
            if ($type == 3) {
                $scheData = $this->groupVisit($scheInfo, $payType);
            } else {
                $scheData['scheDetail'] = $scheInfo;
            }
        }
        $data['page'] = $pn;
        $data['size'] = count($scheInfo);
        $data['pages'] = $pages;
        $data['total'] = $total;
        $data['data'] = $scheData;
        return $data;
    }

    /* 浏览记录按日期分组 */
    public function groupVisit($visit, $payType) {
//        $curyear = date('Y');
        $visit_list = [];
        $weekarray = array("日", "一", "二", "三", "四", "五", "六");
        $cancelAttent = [];
        $midArr = array_column($visit, 'schedule_mid');
        $total = $this->getScheduleTotal($midArr, 2, $payType);
        foreach ($visit as $key => &$v) {
            $v['article_total'] = $total[$v['schedule_mid']];
            if ($v['start_time'] < date('Y-m-d H:i:s', strtotime('-3 day'))) {
                $cancelAttent[] = $v['schedule_mid'];
                continue;
            }
            $time = strtotime($v['start_time']);
            $date = date('Ymd', $time);
            $v['is_attent'] = 1;
            if (!isset($visit_list[$date])) {
                $visit_list[$date] = [];
                $visit_list[$date]["game_date"] = date('Y-m-d', strtotime($date));
                $visit_list[$date]["game"] = [];
                $visit_list[$date]['week'] = '周' . $weekarray[date('w', strtotime($date))];
            }
            $visit_list[$date]["game"][] = $v;
        }
        rsort($visit_list);
        return ['scheDetail' => $visit_list, 'cancelAttent' => $cancelAttent];
    }

    /**
     * 获取赛程信息
     * @param type $mid
     * @return type
     */
    public function getLanScheduleInfo($mid) {
        $status = [
            "0" => "未开赛",
            "1" => "比赛中",
            "2" => "完结",
            "3" => "取消",
            "4" => "延迟",
            "5" => "完结",
            "6" => "未出赛果",
            "7" => "腰斩"
        ];
        $data = (new Query())->select(["sr.result_qcbf", 'sr.result_status', 's.schedule_code', 's.league_id', 's.start_time', "s.home_short_name", "s.visit_short_name", "t1.team_code as home_team_mid", "t1.team_img as home_team_img", "t2.team_code as visit_team_mid", "t2.team_img as visit_team_img", "s.endsale_time", "s.schedule_code", "l.league_short_name", 's.schedule_status endsale_status', 's.endsale_time'])
                        ->from("lan_schedule s")
                        ->join("left join", "team t1", "t1.team_code=s.home_team_id")
                        ->join("left join", "team t2", "t2.team_code=s.visit_team_id")
                        ->join("left join", "league l", "l.league_code=s.league_id and l.league_type = 2")
                        ->join("left join", "lan_schedule_result sr", "sr.schedule_mid=s.schedule_mid")
                        ->where(["s.schedule_mid" => $mid, 't1.team_type' => 2, 't2.team_type' => 2])->one();

        if (empty($data)) {
            return ['msg' => '未找到该赛程', 'data' => null];
        }
        $data['status_name'] = $status[$data['result_status']];
        if (strtotime($data['endsale_time']) <= time()) {
            $data['endsale_status'] = 2;
        }
//        if (isset($status[$data["result_status"]])) {
//            $data["status_name"] = $status[$data["result_status"]];
//        } else {
//            $data["status_name"] = "";
//        }
        return ['msg' => '获取成功', 'data' => $data];
    }

    /**
     * 获取预测篮球赛果
     * @param type $mid
     * @return type
     */
    public function getLanPreResult($mid) {
        $info = (new Query())->select("pre_result_title,pre_result_3001,pre_result_3002,pre_result_3004,confidence_index,expert_analysis")
                ->from("lan_pre_result")
                ->where(["schedule_mid" => $mid])
                ->one();
        //$odds = $this->getOdds($mid);
        if (empty($info)) {
            $info = null;
        }
        // return ['info' => $info, 'odds' => $odds];
        return ['info' => $info];
    }

    /**
     * 获取亚盘赔率
     * @param type $mid
     * @return type
     */
    public function getLanAsianHandicap($mid) {
        $data = (new Query())->select(["a.company_name as company_name", 'a.handicap_type', 'a.rf_nums as begin_rf_nums', 'b.rf_nums as rf_nums', "a.handicap_name as begin_handicap_name", "a.profit_rate as begin_profit_rate", "b.profit_rate as profit_rate", "a.odds_0 as begin_odds_0", "a.odds_3 as begin_odds_3", "a.odds_3_trend as begin_odds_3_trend", "a.odds_0_trend as begin_odds_0_trend", "b.handicap_name as handicap_name", "b.odds_3 as odds_3", "b.odds_0 as odds_0", "b.odds_3_trend as odds_3_trend", 'b.odds_0_trend as odds_0_trend'])
                ->from("lan_rangfen_odds a")
                ->join("left join", "lan_rangfen_odds b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")
                ->where(["a.schedule_mid" => $mid, "a.handicap_type" => 1])
                ->all();
        return $data;
    }

    /**
     * 获取欧盘赔率
     * @param type $mid
     * @return type
     */
    public function getLanEuropeOdds($mid) {
        $data = (new Query())->select(["a.company_name as company_name", "a.handicap_name as begin_handicap_name", "a.odds_3 as begin_odds_3", "a.odds_0 as begin_odds_0", "a.profit_rate as begin_profit_rate", "b.profit_rate as profit_rate", "a.odds_0_trend as begin_odds_0_trend", "a.odds_3_trend as begin_odds_3_trend", "b.handicap_name as handicap_name", "b.odds_3 as odds_3", "b.odds_0 as odds_0", "b.odds_3_trend as odds_3_trend", "b.odds_0_trend as odds_0_trend"])
                ->from("lan_europe_odds a")
                ->join("left join", "lan_europe_odds b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")
                ->where(["a.schedule_mid" => $mid, "a.handicap_type" => 1])
                ->all();
        return $data;
    }

    /**
     * 获取大小赔率
     * @param type $mid
     * @return type
     */
    public function getLanDaxiaoOdds($mid) {
        $data = (new Query())->select(["a.company_name as company_name", "a.cutoff_fen as begin_cutoff_fen", "b.cutoff_fen as cutoff_fen", "a.handicap_name as begin_handicap_name", "a.profit_rate as begin_profit_rate", "b.profit_rate as profit_rate", "a.odds_da as begin_odds_da", "a.odds_xiao as begin_odds_xiao", "a.odds_da_trend as begin_odds_da_trend", "b.odds_da_trend as odds_da_trend", "b.handicap_name as handicap_name", "b.odds_da as odds_da", "b.odds_xiao as odds_xiao", "a.odds_xiao_trend as begin_odds_xiao_trend", "b.odds_xiao_trend as odds_xiao_trend"])
                ->from("lan_daxiao_odds a")
                ->join("left join", "lan_daxiao_odds b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")
                ->where(["a.schedule_mid" => $mid, "a.handicap_type" => 1])
                ->all();
        return $data;
    }

    /**
     * 篮球双方历史交战比赛
     * @param type $leagueCode
     * @param type $homeCode
     * @param type $visitCode
     * @param type $choose
     */
    public function getLanDoubleHistoryMatch($leagueCode, $homeCode, $visitCode, $choose = "") {
        $query = (new Query())->select(["play_time", "league_code", "league_name", "home_team_code", "home_team_name", "visit_team_code", "visit_team_name", "schedule_bf", "schedule_sf_nums", "rf_nums", "result_3001"])
                ->from("lan_schedule_history")
                ->where(["data_type" => 1])
                ->andWhere(["league_code" => $leagueCode])
                ->andWhere(["<", "play_time", date("Y-m-d H:i:s")])
                ->andWhere(["!=", "schedule_bf", 0]);
        if (isset($choose) && $choose == "1") {
            $query = $query->andWhere(["home_team_code" => $homeCode, "visit_team_code" => $visitCode]);
        } else {
            $query = $query->andWhere(["or", ["home_team_code" => $homeCode, "visit_team_code" => $visitCode], ["home_team_code" => $visitCode, "visit_team_code" => $homeCode]]);
        }
        $lanDoubleData = $query->groupBy("play_time")->orderBy("play_time desc")->limit(10)->all();
        if ($lanDoubleData == null) {
            $data = array("list" => [], "result" => ["num" => 0, "defeatNum" => 0, "rfVictoryAvg" => 0, "victoryAvg" => 0, "victoryNum" => 0]);
        }
        $totalRes = array();
        $shome = 0;
        $sviti = 0;
        $victoryNum = 0;
        $totalRes['num'] = count($lanDoubleData);
        foreach ($lanDoubleData as $k => &$v) {
            $score = explode(":", $v["schedule_bf"]);
            if ($v["home_team_code"] != $homeCode) {
                if ($v["result_3001"] == "3") {
                    $v["result_3001"] = "0";
                } else if ($v["result_3001"] == "0") {
                    $v["result_3001"] = "3";
                    $victoryNum++;
                }
                $shome += $score[0];
                $sviti += $score[1];
            } else {
                if ($v["result_3001"] == "3") {
                    $victoryNum++;
                }
                $shome += $score[1];
                $sviti += $score[0];
            }
        }
        $totalRes['homeVictory'] = $victoryNum;
        $totalRes['vistVictory'] = $totalRes['num'] - $victoryNum;
        $totalRes['homeAvg'] = number_format(($totalRes['num'] == 0 ? 0 : $shome / $totalRes['num']), 1);
        $totalRes['visiAvg'] = number_format(($totalRes['num'] == 0 ? 0 : $sviti / $totalRes['num']), 1);
        $data = array("list" => $lanDoubleData, "result" => $totalRes);
        return $data;
    }

    /**
     * 篮球球队历史战绩
     * @param type $leagueCode
     * @param type $teamCode
     * @param type $teamType
     * @param type $choose
     */
    public function getLanHistoryMatch($leagueCode, $teamCode, $teamType, $choose = "") {
        $query = (new Query())->select(["play_time", "league_code", "league_name", "home_team_code", "home_team_name", "visit_team_code", "visit_team_name", "schedule_bf", "schedule_sf_nums", "rf_nums", "result_3001"])
                ->from("lan_schedule_history")
                ->where(["data_type" => 1])
                ->andWhere(["<", "play_time", date("Y-m-d H:i:s")])
                ->andWhere(["!=", "schedule_bf", 0]);
        if (isset($leagueCode) && $leagueCode != "0") {
            $query = $query->andWhere(["league_code" => $leagueCode]);
        }
        if (isset($teamType) && $teamType == "1") {
            if (isset($choose) && $choose == "1") {
                $query = $query->andWhere(["home_team_code" => $teamCode]);
            } else {
                $query = $query->andWhere(["or", ["home_team_code" => $teamCode], ["visit_team_code" => $teamCode]]);
            }
        } else {
            if (isset($choose) && $choose == "1") {
                $query = $query->andWhere(["visit_team_code" => $teamCode]);
            } else {
                $query = $query->andWhere(["or", ["home_team_code" => $teamCode], ["visit_team_code" => $teamCode]]);
            }
        }
        $lanResData = $query->groupBy("play_time")->orderBy("play_time desc")->limit(10)->all();
        if ($lanResData == null) {
            $data = array("list" => [], "result" => ["num" => 0, "victoryNum" => 0, "defeatNum" => 0, "victoryAvg" => 0, "rfVictoryAvg" => 0]);
        }
        $totalRes = array();
        $victoryNum = 0;
        $rfTotal = 0;
        $rfNum = 0;
        $totalRes['num'] = count($lanResData);
        foreach ($lanResData as $k => &$v) {
            if ($v["rf_nums"] != "") {
                $rfTotal++;
            }
            if ($v["home_team_code"] != $teamCode) {
                if ($v["result_3001"] == "3") {
                    $v["result_3001"] = "0";
                } else if ($v["result_3001"] == "0") {
                    $v["result_3001"] = "3";
                    $victoryNum++;
                    if ($v["rf_nums"] != "") {
                        $rfNum++;
                    }
                }
            } else {
                if ($v["result_3001"] == "3") {
                    $victoryNum++;
                    if ($v["rf_nums"] != "") {
                        $rfNum++;
                    }
                }
            }
        }
        $totalRes['victoryNum'] = $victoryNum;
        $totalRes['defeatNum'] = $totalRes['num'] - $victoryNum;
        $totalRes['victoryAvg'] = number_format(($totalRes['num'] == 0 ? 0 : $victoryNum / $totalRes['num']), 2);
        $totalRes['rfVictoryAvg'] = number_format(($rfTotal == 0 ? 0 : $rfNum / $rfTotal), 2);
        $data = array("list" => $lanResData, "result" => $totalRes);
        return $data;
    }

    /**
     * 获取队伍未来赛事
     * @param type $teamCode
     */
    public function getLanFutureMatch($teamCode) {
        $query = (new Query())->select(["play_time", "league_code", "league_name", "home_team_code", "home_team_name", "visit_team_code", "visit_team_name"])
                ->from("lan_schedule_history")
                ->where(["data_type" => 2])
                ->andWhere(["or", ["home_team_code" => $teamCode], ["visit_team_code" => $teamCode]])
                ->andWhere([">", "play_time", date("Y-m-d H:i:s")]);
        $lanFutureData = $query->groupBy("play_time")->orderBy("play_time asc")->limit(5)->all();
        if ($lanFutureData == null) {
            $data = array("list" => []);
        }
        $today = strtotime(date('Y-m-d H:i:s'));
        foreach ($lanFutureData as $k => &$v) {
            $playtime = strtotime($v["play_time"]);
            $days = ceil(abs($playtime - $today) / 86400);
            $lanFutureData[$k]["days"] = $days;
        }
        $data = array("list" => $lanFutureData);
        return $data;
    }

    /**
     * 获取联赛盘路走势信息
     * @param type $teamCode
     */
    public function getLanMentsRoadRes($teamCode) {
        $query = (new Query())->select(["play_time", "league_code", "league_name", "home_team_code", "home_team_name", "visit_team_code", "visit_team_name", "schedule_bf", "schedule_sf_nums", "rf_nums", "cutoff_nums", "result_3002"])
                ->from("lan_schedule_history")
                ->where(["data_type" => 1])
                ->andWhere(["<", "play_time", date("Y-m-d H:i:s")])
                ->andWhere(["!=", "schedule_bf", 0]);
        $query = $query->andWhere(["or", ["home_team_code" => $teamCode], ["visit_team_code" => $teamCode]]);
        $lanMentsRoadData = $query->groupBy("play_time")->orderBy("play_time desc")->limit(6)->all();
        if ($lanMentsRoadData == null) {
            return ["num" => 0, "vdRes" => 0, "bsRes" => 0, "homeVictoryNum" => 0, "homeDefeatedNum" => 0, "visiterVictoryNum" => 0, "visiterDefeatedNum" => 0, "homeVictoryPro" => 0, "visiterVictoryPro" => 0, "homeBigNum" => 0, "homeSmallNum" => 0];
        }
        $totalRes = array();
        $homeVictoryNum = 0;
        $homeDefeatedNum = 0;
        $visiterVictoryNum = 0;
        $visiterDefeatedNum = 0;
        $homeBigNum = 0;
        $homeSmallNum = 0;
        $visiterBigNum = 0;
        $visiterSmallNum = 0;
        $totalRes['num'] = count($lanMentsRoadData);
        foreach ($lanMentsRoadData as $k => &$v) {
            $score = explode(":", $v["schedule_bf"]);
            $totalScore = $score[0] + $score[1];
            if ($totalScore > $v["cutoff_nums"]) {
                $lanMentsRoadData[$k]["compare"] = "大";
            } else {
                $lanMentsRoadData[$k]["compare"] = "小";
            }
            if ($v["home_team_code"] != $teamCode) {
                if ($v["result_3002"] == "3") {
                    $v["result_3002"] = "0";
                    $visiterDefeatedNum++;
                    $lanMentsRoadData[$k]["res"] = "输";
                } else {
                    $v["result_3002"] = "3";
                    $visiterVictoryNum++;
                    $lanMentsRoadData[$k]["res"] = "赢";
                }
                if ($totalScore > $v["cutoff_nums"]) {
                    $visiterBigNum++;
                } else {
                    $visiterSmallNum++;
                }
            } else {
                if ($v["result_3002"] == "3") {
                    $homeVictoryNum++;
                    $lanMentsRoadData[$k]["res"] = "赢";
                } else {
                    $homeDefeatedNum++;
                    $lanMentsRoadData[$k]["res"] = "输";
                }
                if ($totalScore > $v["cutoff_nums"]) {
                    $homeBigNum++;
                } else {
                    $homeSmallNum++;
                }
            }
        }
        $str = "";
        $str2 = "";
        foreach ($lanMentsRoadData as $v) {
            $str .= $v["res"];
            $str2 .= $v["compare"];
        }
        $totalRes['vdRes'] = $str;
        $totalRes['bsRes'] = $str2;
        $totalRes['homeVictoryNum'] = $homeVictoryNum;
        $totalRes['homeDefeatedNum'] = $homeDefeatedNum;
        $totalRes['visiterVictoryNum'] = $visiterVictoryNum;
        $totalRes['visiterDefeatedNum'] = $visiterDefeatedNum;
        $totalRes['homeVictoryPro'] = number_format($homeVictoryNum / ($homeVictoryNum + $homeDefeatedNum), 2);
        $totalRes['visiterVictoryPro'] = number_format($visiterVictoryNum / ($visiterVictoryNum + $visiterDefeatedNum), 2);
        $totalRes['homeBigNum'] = $homeBigNum;
        $totalRes['homeSmallNum'] = $homeSmallNum;
        $totalRes['visiterBigNum'] = $visiterBigNum;
        $totalRes['visiterSmallNum'] = $visiterSmallNum;
        $totalRes['homeBigPro'] = number_format($homeBigNum / ($homeBigNum + $homeSmallNum), 2);
        $totalRes['visiterBigPro'] = number_format($visiterBigNum / ($visiterBigNum + $visiterSmallNum), 2);
        $totalRes['allVictoryNum'] = $homeVictoryNum + $visiterVictoryNum;
        $totalRes['allDefeatedNum'] = $homeDefeatedNum + $visiterDefeatedNum;
        $totalRes['allVictoryPro'] = number_format($totalRes['allVictoryNum'] / $totalRes['num'], 2);
        $totalRes['allBigNum'] = $homeBigNum + $visiterBigNum;
        $totalRes['allSmallNum'] = $homeSmallNum + $visiterSmallNum;
        $totalRes['allBigPro'] = number_format($totalRes['allBigNum'] / $totalRes['num'], 2);
        $data = $totalRes;
        return $data;
    }

    /**
     * 获取联赛战绩
     * @param type $teamCode
     */
    public function getLanTeamResult($home_team_code, $visit_team_code) {

        $data['home_team'] = (new Query())
                ->select(['team_code', 'league_name', 'team_position',
                    'team_rank', 'team_name', 'game_nums', 'win_nums', 'lose_nums', 'win_rate',
                    'wins_diff', 'defen_nums', 'home_result', 'visit_result', 'shifen_nums'])
                ->from("lan_team_rank")
                ->where(["team_code" => $home_team_code])
                ->one();
        if (!$data['home_team']) {
            $data['home_team'] = null;
        }
        $data['visit_team'] = (new Query())->select(['team_code', 'league_name', 'team_position',
                    'team_rank', 'team_name', 'game_nums', 'win_nums', 'lose_nums', 'win_rate',
                    'wins_diff', 'defen_nums', 'home_result', 'visit_result', 'shifen_nums'])
                ->from("lan_team_rank")
                ->where(["team_code" => $visit_team_code])
                ->one();
        if (!$data['visit_team']) {
            $data['visit_team'] = null;
        }
        return $data;
    }

    public function getLanTeamRank($league_id) {

        $data['dongbu'] = (new Query())->select(['team_code', 'team_rank', 'team_name', 'league_name', 'team_position',
                    'team_rank', 'game_nums', 'win_nums', 'lose_nums', 'win_rate',
                ])
                ->from("lan_team_rank")
                ->where(["league_code" => $league_id, "team_position" => 1])
                ->orderBy('team_rank')
                ->all();
        $data['xibu'] = (new Query())->select(['team_code', 'team_rank', 'team_name', 'league_name', 'team_position',
                    'team_rank', 'game_nums', 'win_nums', 'lose_nums', 'win_rate',
                ])
                ->from("lan_team_rank")
                ->where(["league_code" => $league_id, "team_position" => 2])
                ->orderBy('team_rank')
                ->all();
        return $data;
    }

    /**
     * 获取篮球赛程基础分析信息
     * @auther GL zyl
     * @param type $mid  赛程MID
     * @return string|array
     */
    public function getLanAnaylsis($mid) {
        $field = ['lan_schedule.schedule_mid', 'lan_schedule.home_short_name', 'hc.clash_nums', 'hc.win_nums', 'hc.lose_nums', 'hr.team_position as home_position', 'hr.ten_result as home_ten_result',
            'hr.team_rank as home_rank', 'vr.team_position as visit_position', 'vr.ten_result as visit_ten_result', 'vr.team_rank as visit_rank', 'e.odds_3 as europe_odds3', 'e.odds_0 as europe_odds0',
            'r.odds_3 as rangfen_odds3', 'r.odds_0 as rangfen_odds0', 'r.rf_nums', 'dx.odds_da', 'dx.odds_xiao', 'dx.cutoff_fen'];
        $data = LanSchedule::find()->select($field)
                ->leftJoin('lan_history_count as hc', 'hc.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('lan_team_rank as hr', 'hr.team_code = lan_schedule.home_team_id and hr.league_code = lan_schedule.league_id')
                ->leftJoin('lan_team_rank as vr', 'vr.team_code = lan_schedule.visit_team_id and vr.league_code = lan_schedule.league_id')
                ->leftJoin('lan_europe_odds as e', 'e.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('lan_rangfen_odds as r', 'r.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('lan_daxiao_odds as dx', 'dx.schedule_mid = lan_schedule.schedule_mid')
                ->where(['lan_schedule.schedule_mid' => $mid])
                ->asArray()
                ->one();
        if (empty($data)) {
            $data = [];
            return $data;
        }
        if ($data['home_position'] == 1) {
            $data['home_position'] = '东';
        } elseif ($data['home_position'] == 2) {
            $data['home_position'] = '西';
        } else {
            $data['home_position'] = '未知';
        }
        if ($data['visit_position'] == 1) {
            $data['visit_position'] = '东';
        } elseif ($data['visit_position'] == 2) {
            $data['visit_position'] = '西';
        } else {
            $data['visit_position'] = '未知';
        }
        return $data;
    }

    /**
     * 获取投注赛程CODE
     * @auther GL zyl
     * @param type $lotteryCode // 彩种编号
     * @param type $bet 投注内容
     * @param type $jcCode 竞彩类型
     * @return type
     */
    public function getScheduleCode($lotteryCode, $bet, $jcCode) {
        $mids = [];
        if ($lotteryCode != '3005' && $lotteryCode != '3011') {
            $pattern = '/^([0-9]+)\((([0-9]|,)+)\)$/';
        } else {
            $pattern = '/^([0-9]+)((\*[0-9]+\(([0-9]|,)+\))+)$/';
        }
        $betNums = explode("|", trim($bet, '^'));
        $result = [];
        foreach ($betNums as $v) {
            preg_match($pattern, $v, $result);
            $mids[] = $result[1];
        }
        if ($jcCode == '3000') {
            $codeData = Schedule::find()->select(['schedule_code', 'schedule_mid'])->where(['in', 'schedule_mid', $mids])->indexBy('schedule_code')->asArray()->all();
        } else {
            $codeData = LanSchedule::find()->select(['schedule_code', 'schedule_mid'])->where(['in', 'schedule_mid', $mids])->indexBy('schedule_code')->asArray()->all();
        }
        $codeArr = array_keys($codeData);
        $codeStr = implode('X', $codeArr);
        return $codeStr;
    }

    /**
     * 获取篮球赛程实况信息
     * @auther GL zyl
     * @param type $mid  赛程MID
     * @return string|array
     */
    public function getLanCount($mid) {
        $field = ['lan_schedule.visit_short_name', 'lan_schedule.home_short_name', 'lan_schedule.home_team_id', 'lan_schedule.visit_team_id', 'sr.result_status', 'sr.guest_one', 'sr.guest_two', 'sr.guest_three', 'sr.guest_four', 'sr.guest_add_one', 'sr.guest_add_two', 'sr.guest_add_three',
            'sr.guest_add_four', 'sr.result_qcbf', 'c.home_shots', 'c.visit_shots', 'c.home_three_point', 'c.visit_three_point', 'c.home_penalty', 'c.visit_penalty', 'c.home_rebound', 'c.visit_rebound', 'c.home_assist',
            'c.visit_assist', 'c.home_steals', 'c.visit_steals', 'c.home_cap', 'c.visit_cap', 'c.home_foul', 'c.visit_foul', 'c.home_all_miss', 'c.visit_all_miss'];
        $data = LanSchedule::find()->select($field)
                ->leftJoin('lan_schedule_result as sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('lan_schedule_count as c', 'c.schedule_mid = lan_schedule.schedule_mid')
                ->where(['lan_schedule.schedule_mid' => $mid])
                ->asArray()
                ->one();
        $list = [];
        $scoreArr = [];
        $teamArr = [];
        $playArr = [];
        if ($data['result_status'] != 0) {
            if (!empty($data['guest_one'])) {
                $oneArr = explode(':', $data['guest_one']);
                $scoreArr['home']['guest'][] = $oneArr[1];
                $scoreArr['visit']['guest'][] = $oneArr[0];
            }
            if (!empty($data['guest_two'])) {
                $twoArr = explode(':', $data['guest_two']);
                if ($twoArr[0] != 0 && $twoArr[1] != 0) {
                    $scoreArr['home']['guest'][] = $twoArr[1];
                    $scoreArr['visit']['guest'][] = $twoArr[0];
                }
            }
            if (!empty($data['guest_three'])) {
                $threeArr = explode(':', $data['guest_three']);
                if ($threeArr[0] != 0 && $threeArr[1] != 0) {
                    $scoreArr['home']['guest'][] = $threeArr[1];
                    $scoreArr['visit']['guest'][] = $threeArr[0];
                }
            }
            if (!empty($data['guest_four'])) {
                $fourArr = explode(':', $data['guest_four']);
                if ($fourArr[0] != 0 && $fourArr[1] != 0) {
                    $scoreArr['home']['guest'][] = $fourArr[1];
                    $scoreArr['visit']['guest'][] = $fourArr[0];
                }
            }
            if (!empty($data['guest_add_one'])) {
                $addArr1 = explode(':', $data['guest_add_one']);
                if ($addArr1[0] != 0 && $addArr1[1] != 0) {
                    $scoreArr['home']['guest'][] = $addArr1[1];
                    $scoreArr['visit']['guest'][] = $addArr1[0];
                }
            }
            if (!empty($data['guest_add_two'])) {
                $addArr2 = explode(':', $data['guest_add_two']);
                if ($addArr2[0] != 0 && $addArr2[1] != 0) {
                    $scoreArr['home']['guest'][] = $addArr2[1];
                    $scoreArr['visit']['guest'][] = $addArr2[0];
                }
            }
            if (!empty($data['guest_add_three'])) {
                $addArr3 = explode(':', $data['guest_add_three']);
                if ($addArr3[0] != 0 && $addArr3[1] != 0) {
                    $scoreArr['home']['guest'][] = $addArr3[1];
                    $scoreArr['visit']['guest'][] = $addArr3[0];
                }
            }
            if (!empty($data['guest_add_four'])) {
                $addArr4 = explode(':', $data['guest_add_four']);
                if ($addArr4[0] != 0 && $addArr4[1] != 0) {
                    $scoreArr['home']['guest'][] = $addArr4[1];
                    $scoreArr['visit']['guest'][] = $addArr4[0];
                }
            }
            if (!empty($data['result_qcbf'])) {
                $bfArr = explode(':', $data['result_qcbf']);
                if ($bfArr[0] != 0 && $bfArr[1] != 0) {
                    $scoreArr['home']['all_guest'] = $bfArr[1];
                    $scoreArr['visit']['all_guest'] = $bfArr[0];
                }
            }
            $teamArr['shots'] = ['h_shots' => $data['home_shots'], 'v_shots' => $data['visit_shots']];
            $teamArr['point'] = ['h_point' => $data['home_three_point'], 'v_point' => $data['visit_three_point']];
            $teamArr['penalty'] = ['h_penalty' => $data['home_penalty'], 'v_penalty' => $data['visit_penalty']];
            $teamArr['rebound'] = ['h_rebound' => $data['home_rebound'], 'v_rebound' => $data['visit_rebound']];
            $teamArr['assist'] = ['h_assist' => $data['home_assist'], 'v_assist' => $data['visit_assist']];
            $teamArr['steals'] = ['h_steals' => $data['home_steals'], 'v_steals' => $data['visit_steals']];
            $teamArr['cap'] = ['h_cap' => $data['home_cap'], 'v_cap' => $data['visit_cap']];
            $teamArr['foul'] = ['h_foul' => $data['home_foul'], 'v_foul' => $data['visit_foul']];
            $teamArr['miss'] = ['h_miss' => $data['home_all_miss'], 'v_miss' => $data['visit_all_miss']];
        }
        $pfield = ['team_code', 'player_name', 'play_time', 'shots_nums', 'rebound_nums', 'assist_nums', 'foul_nums', 'score'];
        $playerArr = LanPlayerCount::find()->select($pfield)->where(['schedule_mid' => $mid])->orderBy('play_time desc')->asArray()->all();
        if (!empty($playerArr)) {
            foreach ($playerArr as $val) {
                if ($val['team_code'] == $data['home_team_id']) {
                    $playArr['home'][] = [$val['player_name'], $val['play_time'] . "'", $val['shots_nums'], $val['rebound_nums'], $val['assist_nums'], $val['foul_nums'], $val['score']];
                } elseif ($val['team_code'] == $data['visit_team_id']) {
                    $playArr['visit'][] = [$val['player_name'], $val['play_time'] . "'", $val['shots_nums'], $val['rebound_nums'], $val['assist_nums'], $val['foul_nums'], $val['score']];
                }
            }
        }
        $list['visit_short_name'] = $data['visit_short_name'];
        $list['home_short_name'] = $data['home_short_name'];
        $list['result_status'] = $data['result_status'];
        $list['result_qcbf'] = $data['result_qcbf'];
        $list['score'] = $scoreArr;
        $list['team'] = $teamArr;
        $list['player'] = $playArr;
        return $list;
    }

    /**
     * 获取文字直播列表
     * @auther GL zyl 
     * @param type $mid
     * @return array
     */
    public function getLiveList($mid, $page, $size) {
        $field = ['sort_id', 'schedule_mid', 'live_person', 'text_sub', 'game_time'];
        $total = LanScheduleLive::find()->where(['schedule_mid' => $mid])->count();
        $pages = ceil($total / $size);
        $offset = ($page - 1) * $size;
        $liveList = LanScheduleLive::find()->select($field)->where(['schedule_mid' => $mid])->limit($size)->offset($offset)->orderBy('sort_id desc')->asArray()->all();
        if (empty($liveList)) {
            $liveList = [];
        }
        $data = ['page' => $page, 'pages' => $pages, 'size' => count($liveList), 'total' => $total, 'data' => $liveList];
        return $data;
    }

    /**
     * 获取足球大小球赔率
     * @param type $mid
     * @return type
     */
    public function getZuDaxiaoOdds($mid) {
        $data = (new Query())->select(["a.company_name as company_name", "a.cutoff_nums as begin_cutoff_nums", "b.cutoff_nums as cutoff_nums", "a.handicap_name as begin_handicap_name", "a.profit_rate as begin_profit_rate", "b.profit_rate as profit_rate", "a.odds_da as begin_odds_da", "a.odds_xiao as begin_odds_xiao", "a.odds_da_trend as begin_odds_da_trend", "b.odds_da_trend as odds_da_trend", "b.handicap_name as handicap_name", "b.odds_da as odds_da", "b.odds_xiao as odds_xiao", "a.odds_xiao_trend as begin_odds_xiao_trend", "b.odds_xiao_trend as odds_xiao_trend"])
                ->from("zu_daxiao_odds a")
                ->join("left join", "zu_daxiao_odds b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")
                ->where(["a.schedule_mid" => $mid, "a.handicap_type" => 1])
                ->all();
        return $data;
    }

    /**
     * 获取联赛
     * @auther GL zyl
     * @return type
     */
    public function getLanLeague() {
        $field = ['l.league_category_id', 'l.league_code league_id', 'l.league_long_name', 'l.league_short_name'];
        $leagueData = LanSchedule::find()->select($field)
                ->innerJoin('lan_schedule_result sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('league as l', 'l.league_code = lan_schedule.league_id and l.league_status = 1 and l.league_type = 2')
                ->groupBy('l.league_id')
                ->where(['lan_schedule.schedule_status' => 1, 'sr.result_status' => 0])
                ->orderBy('l.league_id')
                ->asArray()
                ->all();
        return $leagueData;
    }

    public function getNewZuScheduleList($pn, $size, $date, $lWhere, $payType, $actionType) {
        $orderBy = 'schedule.start_time,schedule.schedule_mid';
        $gWhere = ['>=', 'start_time', $date . ' 00:00:00'];
        $eWhere = [];
        if (in_array($actionType, [1, 3])) {
            $eWhere = ['<', 'start_time', $date . ' 23:59:59'];
        }
        $field = ['schedule.schedule_id', 'schedule.schedule_mid', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
            'schedule.rq_nums', 'sr.schedule_result_3006', 'sr.schedule_result_3007', 'sr.schedule_result_3008', 'sr.schedule_result_3009', 'sr.schedule_result_3010', 'sr.schedule_result_sbbf', 'schedule.league_name  league_short_name',
            'sr.odds_3006 as sr_odds_3006', 'sr.odds_3007 as sr_odds_3007', 'sr.odds_3008 as sr_odds_3008', 'sr.odds_3009 as sr_odds_3009', 'sr.odds_3010 as sr_odds_3010', 'sr.match_time', 'sr.status', 'h.home_team_rank',
            'h.visit_team_rank', 'st.home_red_num', 'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num', 'st.home_corner_num', 'st.visit_corner_num', 'h.home_team_league', 'h.visit_team_league',
            'l.league_color'];
        if (in_array($actionType, [2, 4]) || $date == date('Y-m-d')) {
            $total = Schedule::find()->select(['start_time'])->where($gWhere)->andWhere($lWhere)->orderBy('start_time')->asArray()->one();
            $date = date('Y-m-d', strtotime($total['start_time']));
            $gWhere = ['>=', 'start_time', $date . ' 00:00:00'];
            $eWhere = ['<', 'start_time', $date . ' 23:59:59'];
        }
        $scheDetail = Schedule::find()->select($field)
                ->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')
                ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
                ->leftJoin('schedule_technic as st', 'st.schedule_mid = schedule.schedule_mid')
                ->leftJoin('league l', 'l.league_id = schedule.league_id and l.league_type = 1')
                ->with(['odds3010'])
                ->andWhere($lWhere)
                ->andWhere($gWhere)
                ->andWhere($eWhere);
        if ($pn != 0) {
            $scheDetail = $scheDetail->limit($size)->offset(($pn - 1) * $size)->orderBy($orderBy)->asArray()->all();
            $pages = ceil($total / $size);
        } else {
            $scheDetail = $scheDetail->orderBy($orderBy)->asArray()->all();
            $pages = 1;
        }

        $prevData = Schedule::find()->select(['start_time'])->where(['<', 'start_time', $date . ' 00:00:00'])->andWhere($lWhere)->orderBy('start_time desc')->asArray()->one();
        $nextData = Schedule::find()->select(['start_time'])->where(['>', 'start_time', $date . ' 23:59:59'])->andWhere($lWhere)->orderBy('start_time')->asArray()->one();
        if (empty($prevData)) {
            $prev = '';
        } else {
            $prev = date('Y-m-d', strtotime($prevData['start_time']));
            if ($prev < date('Y-m-d', strtotime('-3 day'))) {
                $prev = '';
            }
        }
        if (empty($nextData)) {
            $next = '';
        } else {
            $next = date('Y-m-d', strtotime($nextData['start_time']));
        }
        $list = [];
        $scheduleList = [];
        $midArr = array_column($scheDetail, 'schedule_mid');
        $total = $this->getScheduleTotal($midArr, 1, $payType);
        foreach ($scheDetail as &$value) {
            if ($value['start_time'] < date('Y-m-d H:i:s', strtotime('-3 day'))) {
                $value['is_attent'] = 0;
            } else {
                $value['is_attent'] = 1;
            }
            $value['article_total'] = $total[$value['schedule_mid']];
            $shcedultDate = date('Ymd', strtotime($value['start_time']));
            $gameDate = date('Y-m-d', strtotime($shcedultDate));
            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            if (array_key_exists($gameDate, $list)) {
                $list[$gameDate]['game'][] = $value;
                $list[$gameDate]['field_num'] += 1;
            } else {
                $list[$gameDate]['game'][] = $value;
                $list[$gameDate]['field_num'] = 1;
                $list[$gameDate]['game_date'] = date('Y-m-d', strtotime($gameDate));
                $list[$gameDate]['week'] = '周' . $weekarray[date('w', strtotime($gameDate))];
                $list[$gameDate]['prev'] = $prev;
                $list[$gameDate]['next'] = $next;
            }
        }
        foreach ($list as $val) {
            $scheduleList[] = $val;
        }

        $data['scheDetail'] = $scheduleList;
        $data = ['page' => $pn, 'size' => count($scheDetail), 'pages' => $pages, 'total' => count($scheDetail), 'data' => $data];
        return $data;
    }

    public function getNewLanScheduleList($pn, $size, $date, $lWhere, $payType, $actionType) {
        $orderBy = 'lan_schedule.start_time,lan_schedule.schedule_mid';
        $gWhere = ['>=', 'start_time', $date . ' 00:00:00'];
        $eWhere = [];
        if (in_array($actionType, [1, 3])) {
            $eWhere = ['<', 'start_time', $date . ' 23:59:59'];
        }
        $field = ['lan_schedule.schedule_mid', 'sr.result_qcbf', 'lan_schedule.schedule_code', 'lan_schedule.league_id', 'lan_schedule.league_name', 'lan_schedule.schedule_date', 'l.league_color',
            'lan_schedule.visit_short_name', 'lan_schedule.home_short_name', 'lan_schedule.start_time', 'sr.result_status', "sr.guest_one", "sr.schedule_fc as fencha", "sr.schedule_zf as zongfen",
            "sr.guest_two", "sr.match_time", "sr.guest_three", "sr.guest_four", "sr.guest_add_one", "sr.guest_add_two", "sr.guest_add_three", "sr.guest_add_four", 'lan_schedule.hot_status'];
        if (in_array($actionType, [2, 4]) || $date == date('Y-m-d')) {
            $total = LanSchedule::find()->select(['start_time'])->where($gWhere)->andWhere($lWhere)->orderBy('start_time')->asArray()->one();
            $date = date('Y-m-d', strtotime($total['start_time']));
            $gWhere = ['>=', 'start_time', $date . ' 00:00:00'];
            $eWhere = ['<', 'start_time', $date . ' 23:59:59'];
        }
        $scheDetail = LanSchedule::find()->select($field)
                ->innerJoin('lan_schedule_result as sr', 'sr.schedule_mid = lan_schedule.schedule_mid')
                ->leftJoin('league l', 'l.league_code = lan_schedule.league_id and l.league_type = 2')
                ->with(["odds3002", "odds3004"])
                ->andWhere($lWhere)
                ->andWhere($gWhere)
                ->andWhere($eWhere);
        if ($pn != 0) {
            $scheDetail = $scheDetail->orderBy($orderBy)->limit($size)->offset(($pn - 1) * $size)->asArray()->all();
            $pages = ceil($total / $size);
        } else {
            $scheDetail = $scheDetail->orderBy($orderBy)->asArray()->all();
            $pages = 1;
        }

        $prevData = LanSchedule::find()->select(['start_time'])->where(['<', 'start_time', $date . ' 00:00:00'])->andWhere($lWhere)->orderBy('start_time desc')->asArray()->one();
        $nextData = LanSchedule::find()->select(['start_time'])->where(['>', 'start_time', $date . ' 23:59:59'])->andWhere($lWhere)->orderBy('start_time')->asArray()->one();
        if (empty($prevData)) {
            $prev = '';
        } else {
            $prev = date('Y-m-d', strtotime($prevData['start_time']));
            if ($prev < date('Y-m-d', strtotime('-3 day'))) {
                $prev = '';
            }
        }
        if (empty($nextData)) {
            $next = '';
        } else {
            $next = date('Y-m-d', strtotime($nextData['start_time']));
        }
        $midArr = array_column($scheDetail, 'schedule_mid');
        $total = $this->getScheduleTotal($midArr, 2, $payType);
        $list = [];
        $scheduleList = [];
        foreach ($scheDetail as $k => &$v) {
            if (strtotime($v['start_time']) < strtotime(date('Y-m-d H:i:s', strtotime('-3 day')))) {
                $v['is_attent'] = 0;
            } else {
                $v['is_attent'] = 1;
            }
            $v['article_total'] = $total[$v['schedule_mid']];
            $shcedultDate = date('Ymd', strtotime($v['start_time']));
            $v["bifen"] = [];
            if (!empty($v["guest_one"]) && ($v["guest_one"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_one"]);
            }
            if (!empty($v["guest_two"]) && ($v["guest_two"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_two"]);
            }
            if (!empty($v["guest_three"]) && ($v["guest_three"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_three"]);
            }
            if (!empty($v["guest_four"]) && ($v["guest_four"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_four"]);
            }
            if (!empty($v["guest_add_one"]) && ($v["guest_add_one"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_add_one"]);
            }
            if (!empty($v["guest_add_two"]) && ($v["guest_add_two"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_add_two"]);
            }
            if (!empty($v["guest_add_three"]) && ($v["guest_add_three"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_add_three"]);
            }
            if (!empty($v["guest_add_four"]) && ($v["guest_add_four"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["guest_add_four"]);
            }
            if (!empty($v["result_qcbf"]) && ($v["result_qcbf"] != '0:0')) {
                $v["bifen"][] = explode(':', $v["result_qcbf"]);
            }
            $v['match_time'] = str_replace('_', "'", $v['match_time']);
            unset($v["guest_one"], $v["guest_two"], $v["guest_three"], $v["guest_four"]);
            unset($v["guest_add_one"], $v["guest_add_two"], $v["guest_add_three"], $v["guest_add_four"]);
            $gameDate = date('Y-m-d', strtotime($shcedultDate));
            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            if (array_key_exists($gameDate, $list)) {
                $list[$gameDate]['game'][] = $v;
                $list[$gameDate]['field_num'] += 1;
            } else {
                $list[$gameDate]['game'][] = $v;
                $list[$gameDate]['field_num'] = 1;
                $list[$gameDate]['game_date'] = date('Y-m-d', strtotime($gameDate));
                $list[$gameDate]['week'] = '周' . $weekarray[date('w', strtotime($gameDate))];
                $list[$gameDate]['prev'] = $prev;
                $list[$gameDate]['next'] = $next;
            }
        }
        foreach ($list as $val) {
            $scheduleList[] = $val;
        }

        $data['page'] = $pn;
        $data['size'] = count($scheDetail);
        $data['pages'] = $pages;
        $data['total'] = count($scheDetail);
        $data['data'] = ['scheDetail' => $scheduleList];
        return $data;
    }

    public function getScheduleTotal($midArr, $preType, $payType) {
        $expertService = new ExpertService();
        $total = $expertService->getXxArtNums($midArr, $preType, $payType);
        return $total;
    }

}
