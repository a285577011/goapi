<?php

namespace app\modules\openapi\services;

use app\modules\common\models\Schedule;
use app\modules\common\models\League;
use app\modules\common\models\Team;
use app\modules\experts\models\ArticlesPeriods;
use yii\db\Query;

class SoccerScheduleService {
    
    /**
     * 获取联赛列表
     * @return type
     */
    public function getLeague($leagueId) {
        $field = ['league_id', 'league_category_id', 'league_long_name', 'league_short_name', 'league_remarks'];
        if(!empty($leagueId)){
           $data = League::find()->select($field)->where(['league_type' => 1, 'league_status' => 1,"league_id"=>$leagueId])->asArray()->all(); 
        }else{
           $data = League::find()->select($field)->where(['league_type' => 1, 'league_status' => 1])->asArray()->all();  
        }
        return $data;
    }
    
    /**
     * 获取球队列表
     * @return type
     */
    public function getTeam() {
        $field = ['team_id', 'team_long_name', 'team_short_name'];
        $data = Team::find()->select($field)->where(['team_type' => 1])->asArray()->all();
        return $data;
    }

    /**
     * 根据日期获取赛程列表
     * @param type $date
     * @return type
     */
    public function getScheduleByDate($date) {
        $field = ['schedule.open_mid', 'schedule.schedule_code', 'schedule.schedule_date', 'schedule.league_id',
            'schedule.visit_team_name', 'schedule.home_team_name', 'schedule.home_team_id', 'schedule.visit_team_id',
            'schedule.start_time', 'schedule.rq_nums', 'sr.schedule_result_3007', 'sr.schedule_result_sbbf', 'sr.status', 'sr.match_time',
            'st.home_red_num', 'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num', 'h.home_team_rank', 'h.visit_team_rank',
        ];
        $data = Schedule::find()->select($field)
            ->leftJoin('schedule_result sr', 'sr.schedule_mid = schedule.schedule_mid')
            ->leftJoin('schedule_technic st', 'st.schedule_mid = schedule.schedule_mid')
            ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
            ->where(['schedule.schedule_date' => $date])
            ->andWhere(['!=', 'sr.status', 5])
            ->asArray()
            ->all();
        return $data;
    }
    
    public function getScheduleAnalyze($scheduleId) {
        $field = ['schedule.schedule_id', 'pr.pre_result_title', 'pr.pre_result_3010', 'pr.pre_result_3007', 'pr.confidence_index', 'pr.average_home_percent', 'pr.average_visit_percent', 'pr.json_data', 'pr.expert_analysis'];
        $odds = ['odds3006', 'odds3007', 'odds3008', 'odds3009', 'odds3010'];
        $data = Schedule::find()->select($field)
                ->leftJoin('pre_result pr', 'pr.schedule_mid = schedule.schedule_mid')
                ->with($odds)
                ->where(['schedule_id' => $scheduleId])
                ->asArray()
                ->one();
        if(empty($data)) {
            return $data;
        }
         $anaylzeData = json_decode($data['json_data'], true);
        unset($data['json_data']);
        $home = ['win' => $anaylzeData['home']['num_3'], 'tie' => $anaylzeData['home']['num_1'], 'lose' => $anaylzeData['home']['num_0'], 'integral' => $anaylzeData['home']['integral'], 'percent' => $anaylzeData['per1_h']];
        $visit = ['win' => $anaylzeData['visit']['num_3'], 'tie' => $anaylzeData['visit']['num_1'], 'lose' => $anaylzeData['visit']['num_0'], 'integral' => $anaylzeData['visit']['integral'], 'percent' => $anaylzeData['per1_v']];
        $data['gameChart']['option1']['title'] = '近10场对阵数据分析对比';
        $data['gameChart']['option1']['home'] = $home;
        $data['gameChart']['option1']['visit'] = $visit;
        $InHome = ['win' => $anaylzeData['homeInHome']['num_3'], 'tie' => $anaylzeData['homeInHome']['num_1'], 'lose' => $anaylzeData['homeInHome']['num_0'], 'integral' => $anaylzeData['homeInHome']['integral'], 'percent' => $anaylzeData['per2_h']];
        $InVisit = ['win' => $anaylzeData['visitInVisit']['num_3'], 'tie' => $anaylzeData['visitInVisit']['num_1'], 'lose' => $anaylzeData['visitInVisit']['num_0'], 'integral' => $anaylzeData['visitInVisit']['integral'], 'percent' => $anaylzeData['per2_v']];
        $data['gameChart']['option2']['title'] = '主队主场/客队客场近10场比赛数据分析对比';
        $data['gameChart']['option2']['homeInHome'] = $InHome;
        $data['gameChart']['option2']['visitInVisit'] = $InVisit;
        $data['gameChart']['option3']['title'] = '近10场对阵进攻数据分析对比';
        $data['gameChart']['option3']['home'] = ['avg_gain_balls' => $anaylzeData['home']['avg_gain_balls'], 'percent' => $anaylzeData['per3_h']];
        $data['gameChart']['option3']['visit'] = ['avg_gain_balls' => $anaylzeData['visit']['avg_gain_balls'], 'percent' => $anaylzeData['per3_v']];
        $data['gameChart']['option4']['title'] = '近10场对阵防守数据分析对比';
        $data['gameChart']['option4']['home'] = ['avg_lose_balls' => $anaylzeData['home']['avg_lose_balls'], 'percent' => $anaylzeData['per4_h']];
        $data['gameChart']['option4']['visit'] = ['avg_lose_balls' => $anaylzeData['visit']['avg_lose_balls'], 'percent' => $anaylzeData['per4_v']];
        $data['odds3006'] = ['win' => $data['odds3006']['let_wins'], 'tie' => $data['odds3006']['let_level'], 'lose' => $data['odds3006']['let_negative']];
        $data['odds3007'] = ['bf_10' => $data['odds3007']['score_wins_10'], 'bf_20' => $data['odds3007']['score_wins_20'], 'bf_21' => $data['odds3007']['score_wins_21'], 'bf_30' => $data['odds3007']['score_wins_30'], 
            'bf_31' => $data['odds3007']['score_wins_31'], 'bf_32' => $data['odds3007']['score_wins_32'], 'bf_40' => $data['odds3007']['score_wins_40'], 'bf_41' => $data['odds3007']['score_wins_41'], 
            'bf_42' => $data['odds3007']['score_wins_42'], 'bf_50' => $data['odds3007']['score_wins_50'], 'bf_51' => $data['odds3007']['score_wins_51'], 'bf_52' => $data['odds3007']['score_wins_52'],
            'bf_90' => $data['odds3007']['score_wins_90'], 'bf_00' => $data['odds3007']['score_level_00'], 'bf_11' => $data['odds3007']['score_level_11'], 'bf_22' => $data['odds3007']['score_level_22'],
            'bf_33' => $data['odds3007']['score_level_33'], 'bf_99' => $data['odds3007']['score_level_99'], 'bf_01' => $data['odds3007']['score_negative_01'], 'bf_02' => $data['odds3007']['score_negative_02'],
            'bf_12' => $data['odds3007']['score_negative_12'], 'bf_03' => $data['odds3007']['score_negative_03'], 'bf_13' => $data['odds3007']['score_negative_13'], 'bf_23' => $data['odds3007']['score_negative_23'],
            'bf_04' => $data['odds3007']['score_negative_04'], 'bf_14' => $data['odds3007']['score_negative_14'], 'bf_24' => $data['odds3007']['score_negative_24'], 'bf_05' => $data['odds3007']['score_negative_05'],
            'bf_15' => $data['odds3007']['score_negative_15'], 'bf_25' => $data['odds3007']['score_negative_25'], 'bf_09' => $data['odds3007']['score_negative_09']];
        $data['odds3008'] = ['gold_0' => $data['odds3008']['total_gold_0'], 'gold_1' => $data['odds3008']['total_gold_1'], 'gold_2' => $data['odds3008']['total_gold_2'], 'gold_3' => $data['odds3008']['total_gold_3'],
            'gold_4' => $data['odds3008']['total_gold_4'], 'gold_5' => $data['odds3008']['total_gold_5'], 'gold_6' => $data['odds3008']['total_gold_6'], 'gold_7' => $data['odds3008']['total_gold_7']];
        $data['odds3009'] = ['bqc_33' => $data['odds3009']['bqc_33'], 'bqc_31' => $data['odds3009']['bqc_31'], 'bqc_30' => $data['odds3009']['bqc_30'], 'bqc_13' => $data['odds3009']['bqc_13'], 'bqc_11' => $data['odds3009']['bqc_11'], 
            'bqc_10' => $data['odds3009']['bqc_10'], 'bqc_03' => $data['odds3009']['bqc_03'], 'bqc_01' => $data['odds3009']['bqc_01'], 'bqc_00' => $data['odds3009']['bqc_00']];
        $data['odds3010'] = ['win' => $data['odds3010']['outcome_wins'], 'tie' => $data['odds3010']['outcome_level'], 'lose' => $data['odds3010']['outcome_negative']];
        return $data;
    }

    /**
     * 即时赛程
     * $where
     * $type 1
     * $sWhere  ['in', 'sr.status', [3, 4 ]]; 近期取消、延期、腰斩的赛事
     * league_id string	查询联赛
     */
    public function getScheduleList($type=1, $where = '', $sWhere = ''){
        if(empty($sWhere)) {
            $sWhere =  ['sr.status'=>1];
        }
        $orderBy = 'schedule.schedule_date desc';
        if($type==1){
            $where['schedule_status'] = 1;
            $field = [
                'schedule.schedule_id', 'schedule.schedule_code', 'schedule.league_id', 'schedule.schedule_date',
                'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time', 'schedule.rq_nums',
                'sr.schedule_result_3007', 'sr.schedule_result_sbbf', 'sr.match_time', 'sr.status',
                'h.home_team_rank', 'h.visit_team_rank', 'l.league_short_name', 'l.league_remarks',
                'st.home_red_num', 'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num', 'st.home_corner_num',
                'st.visit_corner_num', 'st.odds_3006 as st_odds_3006', 'st.odds_3007 as st_odds_3007', 'st.odds_3008 as st_odds_3008',
                'st.odds_3009 as st_odds_3009', 'st.odds_3010 as st_odds_3010', 'h.home_team_league', 'h.visit_team_league'
            ];
        } else {
            $field = [
                'schedule.schedule_id', 'schedule.schedule_code', 'schedule.league_id',
                'schedule.home_team_mid', 'schedule.visit_team_mid', 'schedule.start_time',
                'schedule.home_short_name', 'schedule.visit_short_name', 'schedule.rq_nums',
                'h.home_team_rank', 'h.visit_team_rank', 'schedule.league_name' ,'l.league_short_name',
                'sr.status',
            ];
        }

        $scheDetail = Schedule::find()->select($field)
            ->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')
            ->leftJoin('league as l', 'l.league_id = schedule.league_id')
            ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
            ->leftJoin('schedule_technic as st', 'st.schedule_mid = schedule.schedule_mid');
        if($type ==1){
            $scheDetail -> with(['odds3010']);
        }
        $scheDetail = $scheDetail -> where($where)
            ->andWhere($sWhere)
            ->orderBy($orderBy)->asArray()->all();
        if($scheDetail){
            foreach($scheDetail as &$v){
                if(isset($v['odds3010']['schedule_mid'])){
                    unset($v['odds3010']['schedule_mid']);
                    unset($v['odds3010']['modify_time']);
                    unset($v['odds3010']['create_time']);
                    unset($v['odds3010']['update_time']);
                    unset($v['odds3010']['opt_id']);
                }
            }
        }
        return $scheDetail;
    }

    /**
     * 单个赛程比分详情
     */
    public function getScheduledsp($open_mid) {
        $field = [
            'schedule.schedule_id' , 'schedule.league_id',
            'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
            'sr.schedule_result_3007', 'sr.schedule_result_3010', 'sr.schedule_result_3006', 'sr.schedule_result_3008',
            'sr.schedule_result_3009', 'sr.schedule_result_sbbf', 'sr.status sr_status',
            'st.home_red_num', 'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num',
        ];
        $orderBy = 'schedule.schedule_date,schedule.start_time, schedule.schedule_mid';
        $odds = ['odds3006', 'odds3007', 'odds3008', 'odds3009', 'odds3010'];
        $spData = Schedule::find()->select($field)
            ->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')
            ->leftJoin('schedule_technic as st', 'st.schedule_mid = schedule.schedule_mid')
            //->with($odds)
            ->where(['schedule.open_mid'=>$open_mid])
            ->orderBy($orderBy)
            ->asArray()->one();
        return ['code'=>600, 'msg'=>'实时比分', 'data'=>$spData];
    }

    /**
     * 单个比赛基本信息
     */
    public function getScheduleBasicInfo($open_mid) {
        $field = [
            'schedule.schedule_id' , 'schedule.open_mid', 'schedule.league_id', 'schedule.schedule_date',
            'schedule.visit_team_name', 'schedule.home_team_name', 'schedule.visit_short_name',
            'schedule.home_short_name',
            'l.league_short_name', 'l.league_code', 'l.league_long_name',
            'pr.pre_result_title', 'pr.pre_result_3010', 'pr.pre_result_3007',
            'pr.confidence_index', 'pr.average_home_percent', 'pr.average_visit_percent', 'pr.json_data', 'pr.expert_analysis',
            'st.home_red_num', 'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num',
            'sr.schedule_result_sbbf', 'sr.schedule_result_3007',
        ];
//        $odds = ['odds3006', 'odds3007', 'odds3008', 'odds3009', 'odds3010'];
        $data = Schedule::find()->select($field)
            ->leftJoin('schedule_result sr', 'sr.schedule_mid = schedule.schedule_mid')
            ->leftJoin('league as l', 'l.league_id = schedule.league_id')
            ->leftJoin('pre_result pr', 'pr.schedule_mid = schedule.schedule_mid')
            ->leftJoin('schedule_technic as st', 'st.schedule_mid = schedule.schedule_mid')
            ->where(['schedule.open_mid' => $open_mid])
            ->asArray()
            ->one();
        return $data;
    }

    /**
     * 获取预测赛果
     * @param type $scheduleId
     * @return type
     */
    public function getPreResult($open_mid) {
        $schedule = (new Query())->select("schedule_mid")->from("schedule")->where(["open_mid" => $open_mid])->one();
        $info = (new Query())->select("pre_result_title,pre_result_3010,pre_result_3007,confidence_index,expert_analysis")->from("pre_result")->where(["schedule_mid" => $schedule['schedule_mid']])->one();
        $list = (new Query())->select("content")->from("schedule_remind")->where(["schedule_mid" => $schedule['schedule_mid']])->all();
        $oddStr = ['odds3006','odds3010'];
        $field = ['schedule.open_mid', 'schedule.visit_short_name', 'schedule.home_short_name', 'h.scale_3010_3', 'h.scale_3010_1', 'h.scale_3010_0', 'h.scale_3006_3', 'h.scale_3006_1', 'h.scale_3006_0'];
        $odds = Schedule::find()->select($field)
            ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid')
            ->with($oddStr)
            ->where(['schedule.open_mid' => $open_mid])
            ->asArray()
            ->all();
        foreach($odds as &$v){
            if(isset($v['odds3006']['schedule_mid'])){
                unset($v['odds3006']['schedule_mid']);
            }
            if(isset($v['odds3010']['schedule_mid'])){
                unset($v['odds3010']['schedule_mid']);
            }
        }
        //实力对比
        $result = (new Query())->select("json_data,pre_result_title")->from("pre_result")->where(["schedule_mid" => $schedule['schedule_mid']])->one();
        if (!empty($result)) {
            $data = json_decode($result["json_data"], true);
            $data["avg_visit_per"] = sprintf("%.1f", $data["avg_visit_per"]);
            $data["avg_home_per"] = sprintf("%.1f", $data["avg_home_per"]);
            $data['pre_result_title'] = $result['pre_result_title'];
        } else {
            $data = '';
        }
        return ['info' => $info, 'list' => $list, 'odds' => $odds, 'contrast'=>$data];
    }


    /**
     * 根据id获取过往赛事
     */
    public function getPastSchedule($scheduleId){

        $field = [
            'schedule.schedule_id' , 'schedule.schedule_date',
            'schedule.visit_team_name', 'schedule.home_team_name', 'schedule.visit_short_name',
            'schedule.home_short_name', 'schedule.start_time',
            'st.home_red_num', 'st.home_yellow_num', 'st.visit_red_num', 'st.visit_yellow_num',
            'h.result_3007', 'h.result_3009_b', 'h.result_3010', 'h.play_time', 'h.league_name', 'h.league_code'
        ];
        $data = Schedule::find()->select($field)
            ->leftJoin('schedule_history as h', 'h.schedule_mid = schedule.schedule_mid')
            ->leftJoin('schedule_technic st', 'st.schedule_mid = schedule.schedule_mid')
            ->where(['schedule.schedule_id' => $scheduleId])
            ->asArray()
            ->all();
        return $data;
    }

    /**
     * 获取历史赛事
     * @param int $team_id  团队id
     * @return type
     */
    public function getScheduleHistory($team_id) {
        $field = 'league_code,league_name,play_time,home_team_mid,home_team_name,
        visit_team_mid,visit_team_name,result_3007,result_3009_b,result_3010';
        $data = (new Query())->select($field)->from("schedule_history")
            ->where(["home_team_mid" => $team_id])
            ->orWhere(["visit_team_mid" => $team_id])
            ->andWhere(['<', 'play_time', date('Y-m-d H:i:s')])
            ->orderBy("play_time desc")->all();
        return $data;
    }


    /**
     * 竞彩赛程关联比赛列表
     * @param string $date 日期
     * @param int $date  类型 1=不带赔率 2=带赔率,3=赔率变化
     * @return boolean
     */
    public function getScheduleAgenda($date='', $type=1) {
        $where = [];
        if ($date) {
            $where['schedule.schedule_date'] = date('Ymd', strtotime($date));
        } else {
            $where['schedule.schedule_date'] = date('Ymd');
        }
        $field = ['schedule.schedule_id', 'schedule.schedule_code', 'schedule.league_id',
            'schedule.schedule_date', 'schedule.visit_short_name', 'schedule.home_short_name', 'schedule.start_time',
            'schedule.rq_nums', 'sr.status', 'l.league_short_name', 'h.home_team_rank', 'h.visit_team_rank', 'h.home_team_league',
            'h.visit_team_league',
            'let_ball_nums', 'let_wins', 'let_wins_trend', 'let_level', 'let_level_trend', 'let_negative', 'let_negative_trend'
        ];
        $scheDetail = Schedule::find()->select($field)
            ->innerJoin('schedule_result as sr', 'sr.schedule_id = schedule.schedule_id')
            ->leftJoin('league as l', 'l.league_id = schedule.league_id')
            ->leftJoin('history_count as h', 'h.schedule_mid = schedule.schedule_mid');
        if($type==2){
            $scheDetail->innerJoin('odds_3006 o', 'o.schedule_mid = schedule.schedule_mid');
        }
        $scheDetail = $scheDetail->where($where)->orderBy('schedule.schedule_mid')->asArray()->all();
        foreach($scheDetail as &$v){
            if(isset($v['odds3010']['schedule_mid'])){
                unset($v['odds3010']['schedule_mid']);
            }
        }
        return $scheDetail;
    }

    /**
     * 获取竞彩比赛sp值变化
     */
    public function getScheduleAgendaOdds($scheduleId){
        $field = [
            'schedule_id', 'schedule_mid', 'schedule_code', 'league_id',
            'schedule_date', 'visit_short_name', 'home_short_name', 'start_time',
            'rq_nums', 'league_name',
        ];
        $data = Schedule::find()->select($field)
            ->where(['schedule_id' => $scheduleId])
            ->orderBy('schedule.schedule_mid')->asArray()->one();

        $query = new Query();
        $field = 'let_ball_nums,let_wins,let_wins_trend,let_level,let_level_trend,let_negative,let_negative_trend';
        $data['odds3006'] = $query -> select($field) ->from('odds_3006') ->where(['schedule_mid'=>$data['schedule_mid']]) -> orderBy('schedule_mid') -> all();

        $field = 'updates_nums,score_wins_10,score_wins_20,score_wins_21,score_wins_30,score_wins_31,score_wins_32,
        score_wins_40,score_wins_41,score_wins_42,score_wins_50,score_wins_51,score_wins_52,score_wins_90,
        score_level_00,score_level_11,score_level_22,score_level_33,score_level_99,score_negative_01,score_negative_02,
        score_negative_12,score_negative_03,score_negative_13,score_negative_23,score_negative_04,score_negative_14,
        score_negative_24,score_negative_05,score_negative_15,score_negative_25,score_negative_09 ';
        $data['odds3007'] = $query -> select($field) ->from('odds_3007') ->where(['schedule_mid'=>$data['schedule_mid']]) -> orderBy('schedule_mid') -> all();

        $field = 'total_gold_0,total_gold_0_trend, total_gold_1,total_gold_1_trend,total_gold_2,total_gold_2_trend,total_gold_3,
        total_gold_3_trend,total_gold_4,total_gold_4_trend,total_gold_5,total_gold_5_trend,total_gold_6,total_gold_6_trend,total_gold_7,total_gold_7_trend';
        $data['odds3008'] = $query -> select($field) ->from('odds_3008') ->where(['schedule_mid'=>$data['schedule_mid']]) -> orderBy('schedule_mid') -> all();

        $field = 'bqc_33,bqc_33_trend,bqc_31,bqc_31_trend,bqc_30,bqc_30_trend,bqc_13,bqc_13_trend,bqc_11,bqc_11_trend,bqc_10,
        bqc_10_trend,bqc_03,bqc_03_trend,bqc_01,bqc_01_trend,bqc_00,bqc_00_trend';
        $data['odds3009'] = $query -> select($field) ->from('odds_3009') ->where(['schedule_mid'=>$data['schedule_mid']]) -> orderBy('schedule_mid') -> all();

        $field = 'outcome_wins,outcome_wins_trend,outcome_level,outcome_level_trend,outcome_negative,outcome_negative_trend';
        $data['odds3010'] = $query -> select($field) ->from('odds_3010') ->where(['schedule_mid'=>$data['schedule_mid']]) -> orderBy('schedule_mid') -> all();

        return $data;
    }

    /**
     * 获取胜负彩赛程
     * @param $periods 期数
     * @return array
     */
    public function getFourteenByPeriods($periods){
        $query = new Query();
        $field = ['f.periods', 'f.beginsale_time', 'f.endsale_time', 'o.sorting_code', 'o.league_name as league_short_name',
            'o.start_time', 'o.home_short_name', 'o.visit_short_name', 'o.odds_win', 'o.odds_flat', 'o.odds_lose'];
        $data = $query -> select($field) -> from('football_fourteen f')
            -> innerJoin('optional_schedule o', 'f.periods = o.periods')
            -> where(['f.periods'=>$periods])
            -> andWhere(['in', 'f.status', [0, 1]])
            -> orderBy('o.optional_schedule_id')
            ->all();
        return $data;
    }

    /**
     * 获取足球情报列表
     */
    public function getFbIntelligence(){
        $field = 'schedule.open_mid, schedule.start_time,schedule.schedule_code, schedule.league_name, schedule.home_team_id, 
        schedule.home_team_name, schedule.visit_team_id, schedule.visit_team_name,';
        $field .= 'pr.pre_result_title,pr.pre_result_3010,pr.pre_result_3007,pr.expert_analysis';
        //一周内的比赛时间限制
        $date = date('Y-m-d', strtotime('-1 day'));
        $data = Schedule::find()->select($field)
            ->leftJoin('pre_result pr', 'pr.schedule_mid = schedule.schedule_mid')
            ->where(['>','start_time',$date])
            ->orderBy('schedule_id  desc')
            ->asArray()
            ->all();
        return $data;
    }

    /**
     * 获取足球情报单个
     * $schedule_id 赛事id
     */
    public function getFbIntelligenceOne($open_mid){

        $query = new Query();
        $schedule = $query->select("schedule_mid")->from("schedule")->where(["open_mid" => $open_mid])->one();
        //基本信息
        $field = 'schedule.open_mid, schedule.start_time,schedule.schedule_code, schedule.league_name, schedule.home_team_id, 
        schedule.home_team_name, schedule.visit_team_id, schedule.visit_team_name,';
        $field .= 'pr.pre_result_3010,pr.pre_result_3007,pr.expert_analysis';
        $oneDate = Schedule::find()->select($field)
            ->leftJoin('pre_result pr', 'pr.schedule_mid = schedule.schedule_mid')
            ->where(['schedule.open_mid' => $open_mid])
            ->orderBy('schedule_id  desc')
            ->asArray()
            ->one();
        $scheduleService = new \app\modules\common\services\ScheduleService;
        //欧盘
        $europeOdds = $scheduleService->getEuropeOdds($schedule['schedule_mid']);
        //亚盘
        $asianOdds = $scheduleService->getAsianHandicap($schedule['schedule_mid']);
        //事件影响
        $moreInfo = $scheduleService->getScheduleLives($schedule['schedule_mid']);
        //实力对比
        $contrast = $scheduleService->getStrengthContrast($schedule['schedule_mid']);
        $data = [
            'info' => $oneDate,    //基本信息
            'europeOdds' => $europeOdds,    //欧赔
            'asianOdds' => $asianOdds,    //亚赔
            'events' => $moreInfo['events'],    //事件
            'technic' => $moreInfo['technic'],  //技术统计
            'contrast' => $contrast['data'],  //图形对比（实力对比）
        ];
        return $data;
    }

    /**
     * 足球资讯列表
     */
    public function getFbmsgList(){
        $field = 'schedule.open_mid, schedule.start_time, schedule.create_time, pr.pre_result_title';
        $data = Schedule::find()->select($field)
            ->leftJoin('pre_result pr', 'pr.schedule_mid = schedule.schedule_mid')
            ->where(['>=', 'schedule.start_time', date('Y-m-d')])
            ->andWhere(['<>','pre_result_title', ''])
            ->orderBy('schedule.start_time')
            ->asArray()
            ->all();
        return $data;
    }

    /**
     *  足球资讯单个
     */
    public function getTeamBoalNumTotal($leagueId) {
        $team = (new Query())->select("t.team_code,t.team_id,t.team_short_name")
                ->from("league_team as l")
                ->leftJoin("team as t", "t.team_id= l.team_id")
                ->where(["l.league_id" => $leagueId])
                ->all();
        foreach ($team as $key => $v) {
            $teamInfo[$v["team_id"]] = $v["team_short_name"];
            $data = (new Query())->select("sh.home_team_name,sh.visit_team_name,sh.result_3007,sh.play_time,sh.home_team_mid,sh.visit_team_mid")
                    ->from("schedule_history as sh")
                    ->where(["or", ["sh.home_team_mid" => $v["team_code"]], ["sh.visit_team_mid" => $v["team_code"]]])
                    ->andWhere("result_3007 !=''")
                    ->orderBy("sh.play_time desc")
                    ->limit(10)
                    ->all();
            $Total[$v["team_id"]] = [];
            $Total[$v["team_id"]]["01"] = 0;
            $Total[$v["team_id"]]["23"] = 0;
            $Total[$v["team_id"]]["46"] = 0;
            $Total[$v["team_id"]]["7+"] = 0;
            $Total[$v["team_id"]]["single"] = 0;
            $Total[$v["team_id"]]["double"] = 0;
            if (!empty($data)) {
                foreach ($data as $k => $val) {
                    $bfNum = explode(":", $val["result_3007"]);
                    if ($val["home_team_mid"] == $v["team_code"]) {
                        if ($bfNum[0] >= 0 && $bfNum[0] <= 1) {
                            $Total[$v["team_id"]]["01"] ++;
                        } elseif ($bfNum[0] >= 2 && $bfNum[0] <= 3) {
                            $Total[$v["team_id"]]["23"] ++;
                        } elseif ($bfNum[0] >= 4 && $bfNum[0] <= 6) {
                            $Total[$v["team_id"]]["46"] ++;
                        } elseif ($bfNum[0] >= 7) {
                            $Total[$v["team_id"]]["7+"] ++;
                        }
                        if ($bfNum[0] % 2 == 0) {
                            $Total[$v["team_id"]]["double"] ++;
                        } elseif ($bfNum[0] % 2 == 1) {
                            $Total[$v["team_id"]]["single"] ++;
                        }
                    } elseif ($val["visit_team_mid"] == $v["team_code"]) {
                        if ($bfNum[1] >= 0 && $bfNum[1] <= 1) {
                            $Total[$v["team_id"]]["01"] ++;
                        } elseif ($bfNum[1] >= 2 && $bfNum[1] <= 3) {
                            $Total[$v["team_id"]]["23"] ++;
                        } elseif ($bfNum[1] >= 4 && $bfNum[1] <= 6) {
                            $Total[$v["team_id"]]["46"] ++;
                        } elseif ($bfNum[1] >= 7) {
                            $Total[$v["team_id"]]["7+"] ++;
                        }
                        if ($bfNum[1] % 2 == 0) {
                            $Total[$v["team_id"]]["double"] ++;
                        } elseif ($bfNum[1] % 2 == 1) {
                            $Total[$v["team_id"]]["single"] ++;
                        }
                    }
                }
            }
        }
        $result["Total"] = $Total;
        $result["team"] = $teamInfo;
        return $result;
    }

    /**
     * 根据赛事ID获取单场赛事欧赔指数
     */
    public function getEuropeHandicap($openMid) {
        $data = (new Query())->select(["e.company_name","e.handicap_type" ,"e.handicap_name", "e.odds_3", "e.odds_1", "e.odds_0","s.schedule_id", "s.open_mid"])
                ->from("europe_odds as e")
                ->leftJoin("schedule as s", "s.schedule_mid=e.schedule_mid")
                ->where(["s.open_mid" => $openMid])
                ->all();
        return $data;
    }
    /**
     * 获取单场比赛公司赔率变化记录
     */
    public function getEuropeHandicapChange($openMid,$companyName){
        $data = (new Query())->select(["e.company_name","e.handicap_type" ,"e.handicap_name", "e.odds_3", "e.odds_1", "e.odds_0", "s.schedule_id", "s.open_mid"])
                ->from("europe_odds as e")
                ->leftJoin("schedule as s", "s.schedule_mid=e.schedule_mid")
                ->where(["s.open_mid" => $openMid,"e.company_name"=>$companyName])
                ->all();
        return $data;
    }
     /**
     * 根据公司名称获取欧赔指数列表
     */
    public function getCompanyEuropeList($companyName, $type, $date) {
        $data = (new Query())->select(["e.company_name","e.handicap_type" ,"e.handicap_name", "e.odds_3", "e.odds_1", "e.odds_0","s.schedule_id", "s.open_mid"])
                ->from("europe_odds as e")
                ->leftJoin("schedule as s", "s.schedule_mid=e.schedule_mid")
                ->where(["e.handicap_type" => $type, "e.company_name" => $companyName]);
        if (!empty($date)) {
            $data = $data->andWhere(["between", "e.create_time", $date . " 00:00:00", $date . " 23:59:59"]);
        }
        $data = $data->orderBy("e.create_time desc")->all();
        return $data;
    }

    public function getFbmsgOne($open_mid){
        $field = 'schedule.open_mid, schedule.start_time, schedule.create_time, schedule.home_team_id, schedule.visit_team_id, pr.pre_result_title, pr.expert_analysis';
        $data = Schedule::find()->select($field)
            ->leftJoin('pre_result pr', 'pr.schedule_mid = schedule.schedule_mid')
            ->where(['schedule.open_mid' => $open_mid])
            ->asArray()
            ->one();
        return $data;
    }

    /**
     * 获取足球近两年比赛数据
     * @param type $teamId
     * @param type $timer
     * @return type
     */
    public function getTwoYearsHistoryMatch($teamId) {
        $team = Team::findOne(["team_id" => $teamId]);
        if (empty($team)) {
            return['msg' => '未找到该队伍', 'data' => null];
        }
        $time = date("Y-m-d H:i:s");
        //近两年比赛数据
        $timer = date("Y-m-d H:i:s", strtotime(" -2 years"));
        $query = (new Query())->select("league_name,play_time,home_team_name,visit_team_name,result_3007,result_3009_b,result_3010")
            ->from("schedule_history ")
            ->where(["between", "play_time", $timer, $time])
            ->andWhere(["!=", "result_3007", ""])
            ->andWhere(["or", ["home_team_mid" => $team->team_code], ["visit_team_mid" => $team->team_code]]);
        $data["history"] = $query->groupBy("play_time")->orderBy("play_time desc")->all();
        foreach ($data["history"] as &$v) {
            if ($v["result_3010"] == 3) {
                $v["result_3010"] = "胜";
            } elseif ($v["result_3010"] == 1) {
                $v["result_3010"] = "平";
            } elseif ($v["result_3010"] == 0) {
                $v["result_3010"] = "负";
            }
        }
        //数据统计
        $data["total"] = [];
        $data["total"]["join"] = [];
        $data["total"]["join"]["01"] = 0;
        $data["total"]["join"]["23"] = 0;
        $data["total"]["join"]["46"] = 0;
        $data["total"]["join"]["7+"] = 0;
        $data["total"]["join"]["single"] = 0;
        $data["total"]["join"]["double"] = 0;
        foreach ($data["history"] as $k => $v) {
            $goal = explode(":", $v["result_3007"]);
            $totalGoal = $goal["0"] + $goal["1"];
            if ($totalGoal >= 0 && $totalGoal <= 1) {
                $data["total"]["join"]["01"] ++;
            } elseif ($totalGoal >= 2 && $totalGoal <= 3) {
                $data["total"]["join"]["23"] ++;
            } elseif ($totalGoal >= 4 && $totalGoal <= 6) {
                $data["total"]["join"]["46"] ++;
            } elseif ($totalGoal >= 7) {
                $data["total"]["join"]["7+"] ++;
            }
            if ($totalGoal % 2 == 0) {
                $data["total"]["join"]["double"] ++;
            } elseif ($totalGoal % 2 == 1) {
                $data["total"]["join"]["single"] ++;
            }
        }
        //未来赛事
        $query = (new Query())->select("league_name,play_time,home_team_name,visit_team_name")
            ->from("schedule_history")
            ->where([">", "play_time", $time])
            ->andWhere(["or", ["visit_team_mid" => $team->team_code], ["home_team_mid" => $team->team_code]]);
        $data["future"] = $query->orderBy("play_time asc")->all();
        return $data;
    }

    /**
     * 获取足球球队列表及对应体彩名称
     * @param type $teamId
     */
    public function getSoccerTeamInfo($teamId) {
        if ($teamId == 0) {
            $teamInfo = Team::find()->select("team_id,team_short_name,team_long_name")
                ->where(["team_type" => 1])
                ->asArray()
                ->all();
        } else {
            $teamInfo = Team::find()->select("team_id,team_short_name,team_long_name")
                ->where(["team_id" => $teamId])
                ->andWhere(["team_type" => 1])
                ->asArray()
                ->all();
        }
        return $teamInfo;
    }

    /**
     * 根据赛事ID获取赛事信息
     */
    public function getScheduleInfo($openMid) {
        $field = ['schedule.schedule_id', 'schedule.schedule_date', 'schedule.league_id', 'schedule.visit_team_name', 'schedule.home_team_name', 'schedule.home_team_id',
            'schedule.visit_team_id', 'schedule.start_time', 'schedule.rq_nums', 'sr.schedule_result_3006', 'sr.schedule_result_3007', 'sr.schedule_result_3008', 'sr.schedule_result_3009',
            'sr.schedule_result_3010', 'sr.schedule_result_sbbf', 'sr.status', 'sr.match_time', 'sh.result_3007'];
        $data = Schedule::find()->select($field)
            ->leftJoin('schedule_result sr', 'sr.schedule_mid = schedule.schedule_mid')
            ->leftJoin('schedule_history sh', 'sh.schedule_mid = schedule.schedule_mid')
            ->where(['schedule.open_mid' => $openMid])
            ->andWhere(['!=', 'sr.status', 5])
            ->asArray()
            ->all();
        return $data;
    }

    /**
     * 根据赛事ID获取赛事亚盘指数
     */
    public function getAsianHandicap($openMid) {
        $data = (new Query())->select(["a.company_name", "a.handicap_name", "a.home_discount", "a.let_index", "a.visit_discount", "a.create_time", "s.schedule_id", "s.open_mid"])
            ->from("asian_handicap as a")
//                ->join("left join", "asian_handicap b", "a.schedule_mid=b.schedule_mid and a.company_name=b.company_name and b.handicap_type=2")
            ->leftJoin("schedule as s", "s.schedule_mid=a.schedule_mid")
            ->where(["s.open_mid" => $openMid])
            ->all();
        return $data;
    }

    /**
     * 根据公司名称获取亚盘指数列表
     */
    public function getCompanyAsianList($name, $type, $date) {
        $data = (new Query())->select("a.company_name,a.handicap_name,a.home_discount,a.let_index,a.visit_discount,a.create_time,s.schedule_id,s.open_mid")
            ->from("asian_handicap as a")
            ->leftJoin("schedule as s", "s.schedule_mid=a.schedule_mid")
            ->where(["a.handicap_type" => $type, "a.company_name" => $name]);
        if (!empty($date)) {
            $data = $data->andWhere(["between", "a.create_time", $date . " 00:00:00", $date . " 23:59:59"]);
        }
        $data = $data->orderBy("a.create_time desc")->all();
        return $data;
    }

    /**
     * 根据日期获取实况列表
     * 日期必须大于或等于当前日期的前一天
     */
    public function getScheduleLiveList($date) {
        $data["list"] = (new Query())->select("s.schedule_id,s.league_name,s.home_team_id,s.visit_team_id,s.home_short_name,s.visit_short_name")
            ->from("schedule as s")
            ->where(["between", "start_time", $date . " 00:00:00", $date . " 23:59:59"])
            ->orderBy("start_time desc")
            ->all();
        $data["date"] = $date;
        return $data;
    }

    /**
     * 根据赛事ID获取实况数据
     */
    public function getScheduleLive($openMid){
        $data["live"] = (new Query())->select("e.team_name,e.event_type_name,e.event_content,e.event_time,e.event_type,e.team_type")
            ->from("schedule_event as e")
            ->leftJoin("schedule as s", "s.schedule_mid=e.schedule_mid")
            ->where(["s.open_mid" => $openMid])
            ->all();
        $data["info"] = (new Query())->select("s.home_team_id,s.visit_team_id,s.home_short_name,s.visit_short_name,s.start_time,h.schedule_result_3007,h.schedule_result_3010")
            ->from("schedule as s")
            ->leftJoin("schedule_result as h", "h.schedule_mid =s.schedule_mid")
            ->where(["s.open_mid" => $openMid])
            ->one();

        return $data;
    }

    /**
     * 获取队伍及所属联赛列表
     */
    public function getTeamLeagueList() {
        $data = (new Query())->select("t.team_id,t.team_short_name,t.team_long_name,t.team_img,l.league_id,l.league_short_name,l.league_long_name,l.league_img")
            ->from("league_team as lt")
            ->leftJoin("team as t", "t.team_id=lt.team_id ")
            ->leftJoin("league as l", "l.league_id=lt.league_id")
            ->where("t.team_type=1 and l.league_type=1")
            ->all();
        return $data;
    }

    /**
     * 获取队伍近10场进球数据
     */
    public function getTeamHisTotal($leagueId) {
        $team = (new Query())->select("t.team_code,t.team_id,t.team_short_name")
            ->from("league_team as l")
            ->leftJoin("team as t", "t.team_id= l.team_id")
            ->where(["l.league_id" => $leagueId])
            ->all();
         if(empty($team)){
            $data = [];
            return $data;
        }
        //主队记录
        foreach ($team as $key => $v) {
            $teamInfo[$v["team_id"]] = $v["team_short_name"];
            $data = (new Query())->select("sh.home_team_name,sh.visit_team_name,sh.result_3007,sh.play_time")
                ->from("schedule_history as sh")
                ->where(["sh.home_team_mid" => $v["team_code"]])
                ->andWhere("result_3007 !=''")
                ->orderBy("sh.play_time desc")
                ->limit(10)
                ->all();
            $homeTotal[$v["team_id"]] = [];
            $homeTotal[$v["team_id"]]["0"] = 0;
            $homeTotal[$v["team_id"]]["1"] = 0;
            $homeTotal[$v["team_id"]]["2"] = 0;
            $homeTotal[$v["team_id"]]["3"] = 0;
            $homeTotal[$v["team_id"]]["4"] = 0;
            $homeTotal[$v["team_id"]]["5+"] = 0;
            if (!empty($data)) {
                foreach ($data as $k => $val) {
                    $bfNum = explode(":", $val["result_3007"]);
                    switch ($bfNum[0]) {
                        case 0:
                            $homeTotal[$v["team_id"]]["0"] ++;
                            break;
                        case 1:
                            $homeTotal[$v["team_id"]]["1"] ++;
                            break;
                        case 2:
                            $homeTotal[$v["team_id"]]["2"] ++;
                            break;
                        case 3:
                            $homeTotal[$v["team_id"]]["3"] ++;
                            break;
                        case 4:
                            $homeTotal[$v["team_id"]]["4"] ++;
                            break;
                        default:
                            $homeTotal[$v["team_id"]]["5+"] ++;
                            break;
                    }
                }
            }
        }
        //客队记录
        foreach ($team as $key => $v) {
            $data = (new Query())->select("sh.home_team_name,sh.visit_team_name,sh.result_3007,sh.play_time")
                ->from("schedule_history as sh")
                ->where(["sh.visit_team_mid" => $v["team_code"]])
                ->andWhere("result_3007 !=''")
                ->orderBy("sh.play_time desc")
                ->limit(10)
                ->all();
            $visitTotal[$v["team_id"]] = [];
            $visitTotal[$v["team_id"]]["0"] = 0;
            $visitTotal[$v["team_id"]]["1"] = 0;
            $visitTotal[$v["team_id"]]["2"] = 0;
            $visitTotal[$v["team_id"]]["3"] = 0;
            $visitTotal[$v["team_id"]]["4"] = 0;
            $visitTotal[$v["team_id"]]["5+"] = 0;
            if (!empty($data)) {
                foreach ($data as $k => $val) {
                    $bfNum = explode(":", $val["result_3007"]);
                    switch ($bfNum[0]) {
                        case 0:
                            $visitTotal[$v["team_id"]]["0"] ++;
                            break;
                        case 1:
                            $visitTotal[$v["team_id"]]["1"] ++;
                            break;
                        case 2:
                            $visitTotal[$v["team_id"]]["2"] ++;
                            break;
                        case 3:
                            $visitTotal[$v["team_id"]]["3"] ++;
                            break;
                        case 4:
                            $visitTotal[$v["team_id"]]["4"] ++;
                            break;
                        default:
                            $visitTotal[$v["team_id"]]["5+"] ++;
                            break;
                    }
                }
            }
        }
        //总共进球记录
        foreach ($homeTotal as $k => $v) {
            $Total[$k] = [];
            $Total[$k]["0"] = 0;
            $Total[$k]["1"] = 0;
            $Total[$k]["2"] = 0;
            $Total[$k]["3"] = 0;
            $Total[$k]["4"] = 0;
            $Total[$k]["5+"] = 0;
            $Total[$k]["0"] = $v["0"] + $visitTotal[$k]["0"];
            $Total[$k]["1"] = $v["1"] + $visitTotal[$k]["1"];
            $Total[$k]["2"] = $v["2"] + $visitTotal[$k]["2"];
            $Total[$k]["3"] = $v["3"] + $visitTotal[$k]["3"];
            $Total[$k]["4"] = $v["4"] + $visitTotal[$k]["4"];
            $Total[$k]["5+"] = $v["5+"] + $visitTotal[$k]["5+"];
        }
        $result["Total"] = $Total;
        $result["homeTotal"] = $homeTotal;
        $result["visitTotal"] = $visitTotal;
        $result["team"] = $teamInfo;
        return $result;
    }

    /**
     * 获取联赛队伍近10场的全场入球统计
     */
    public function getTeamBoalCount($leagueId) {
        $team = (new Query())->select("t.team_code,t.team_id,t.team_short_name")
            ->from("league_team as l")
            ->leftJoin("team as t", "t.team_id= l.team_id")
            ->where(["l.league_id" => $leagueId])
            ->all();
         if(empty($team)){
            $data = [];
            return $data;
        }
        foreach ($team as $key => $v) {
            $teamInfo[$v["team_id"]] = $v["team_short_name"];
            $data = (new Query())->select("sh.home_team_name,sh.visit_team_name,sh.result_3007,sh.play_time,sh.home_team_mid,sh.visit_team_mid")
                ->from("schedule_history as sh")
                ->where(["or", ["sh.home_team_mid" => $v["team_code"]], ["sh.visit_team_mid" => $v["team_code"]]])
                ->andWhere("result_3007 !=''")
                ->orderBy("sh.play_time desc")
                ->limit(10)
                ->all();
            $Total[$v["team_id"]] = [];
            $Total[$v["team_id"]]["count"] = 0;
            $Total[$v["team_id"]]["2-"] = 0;
            $Total[$v["team_id"]]["3+"] = 0;
            if (!empty($data)) {
                $Total[$v["team_id"]]["count"] = count($data);
                foreach ($data as $k => $val) {
                    $bfNum = explode(":", $val["result_3007"]);
                    if ($val["home_team_mid"] == $v["team_code"]) {
                        if ($bfNum[0] >= 0 && $bfNum[0] <= 2) {
                            $Total[$v["team_id"]]["2-"] ++;
                        }
                        if ($bfNum[0] >= 3) {
                            $Total[$v["team_id"]]["3+"] ++;
                        }
                    } elseif ($val["visit_team_mid"] == $v["team_code"]) {
                        if ($bfNum[1] >= 0 && $bfNum[1] <= 2) {
                            $Total[$v["team_id"]]["2-"] ++;
                        }
                        if ($bfNum[1] >= 3) {
                            $Total[$v["team_id"]]["3+"] ++;
                        }
                    }
                }
            }
        }
        $result["Total"] = $Total;
        $result["team"] = $teamInfo;
        return $result;
    }

    /**
     * 获取联赛队伍近10场赛果半全场数据统计
     */
    public function getTeamBqcTotal($leagueId) {
        $team = (new Query())->select("t.team_code,t.team_id,t.team_short_name")
            ->from("league_team as l")
            ->leftJoin("team as t", "t.team_id= l.team_id")
            ->where(["l.league_id" => $leagueId])
            ->all();
        if(empty($team)){
            $data = [];
            return $data;
        }
        //主队记录
        foreach ($team as $key => $v) {
            $teamInfo[$v["team_id"]] = $v["team_short_name"];
            $data = (new Query())->select("sh.home_team_name,sh.visit_team_name,sh.result_3007,sr.schedule_result_3009")
                ->from("schedule_history as sh")
                ->leftJoin("schedule_result as sr", "sr.schedule_mid=sh.schedule_mid")
                ->where(["sh.home_team_mid" => $v["team_code"]])
                ->andWhere("result_3007 !=''")
                ->orderBy("sh.play_time desc")
                ->limit(10)
                ->all();
            $homeTotal[$v["team_id"]] = [];
            $homeTotal[$v["team_id"]]["33"] = 0;
            $homeTotal[$v["team_id"]]["31"] = 0;
            $homeTotal[$v["team_id"]]["30"] = 0;
            $homeTotal[$v["team_id"]]["13"] = 0;
            $homeTotal[$v["team_id"]]["11"] = 0;
            $homeTotal[$v["team_id"]]["10"] = 0;
            $homeTotal[$v["team_id"]]["03"] = 0;
            $homeTotal[$v["team_id"]]["01"] = 0;
            $homeTotal[$v["team_id"]]["00"] = 0;
            if (!empty($data)) {
                foreach ($data as $k => $val) {
                    switch ($val["schedule_result_3009"]) {
                        case 33:
                            $homeTotal[$v["team_id"]]["33"] ++;
                            break;
                        case 31:
                            $homeTotal[$v["team_id"]]["31"] ++;
                            break;
                        case 30:
                            $homeTotal[$v["team_id"]]["30"] ++;
                            break;
                        case 13:
                            $homeTotal[$v["team_id"]]["13"] ++;
                            break;
                        case 11:
                            $homeTotal[$v["team_id"]]["11"] ++;
                            break;
                        case 10:
                            $homeTotal[$v["team_id"]]["10"] ++;
                            break;
                        case 03:
                            $homeTotal[$v["team_id"]]["03"] ++;
                            break;
                        case 01:
                            $homeTotal[$v["team_id"]]["01"] ++;
                            break;
                        case 00:
                            $homeTotal[$v["team_id"]]["00"] ++;
                            break;
                    }
                }
            }
        }
        //客队记录
        foreach ($team as $key => $v) {
            $data = (new Query())->select("sr.schedule_result_3009")
                ->from("schedule_history as sh")
                ->leftJoin("schedule_result as sr", "sr.schedule_mid=sh.schedule_mid")
                ->where(["sh.visit_team_mid" => $v["team_code"]])
                ->andWhere("result_3007 !=''")
                ->orderBy("sh.play_time desc")
                ->limit(10)
                ->all();
            $visitTotal[$v["team_id"]] = [];
            $visitTotal[$v["team_id"]]["33"] = 0;
            $visitTotal[$v["team_id"]]["31"] = 0;
            $visitTotal[$v["team_id"]]["30"] = 0;
            $visitTotal[$v["team_id"]]["13"] = 0;
            $visitTotal[$v["team_id"]]["11"] = 0;
            $visitTotal[$v["team_id"]]["10"] = 0;
            $visitTotal[$v["team_id"]]["03"] = 0;
            $visitTotal[$v["team_id"]]["01"] = 0;
            $visitTotal[$v["team_id"]]["00"] = 0;
            if (!empty($data)) {
                foreach ($data as $k => $val) {
                    switch ($val["schedule_result_3009"]) {
                        case 33:
                            $visitTotal[$v["team_id"]]["33"] ++;
                            break;
                        case 31:
                            $visitTotal[$v["team_id"]]["31"] ++;
                            break;
                        case 30:
                            $visitTotal[$v["team_id"]]["30"] ++;
                            break;
                        case 13:
                            $visitTotal[$v["team_id"]]["13"] ++;
                            break;
                        case 11:
                            $visitTotal[$v["team_id"]]["11"] ++;
                            break;
                        case 10:
                            $visitTotal[$v["team_id"]]["10"] ++;
                            break;
                        case 03:
                            $visitTotal[$v["team_id"]]["03"] ++;
                            break;
                        case 01:
                            $visitTotal[$v["team_id"]]["01"] ++;
                            break;
                        case 00:
                            $visitTotal[$v["team_id"]]["00"] ++;
                            break;
                    }
                }
            }
        }
        //总共进球记录
             foreach ($homeTotal as $k => $v) {
                $Total[$k] = [];
                $Total[$k]["33"] = 0;
                $Total[$k]["31"] = 0;
                $Total[$k]["30"] = 0;
                $Total[$k]["13"] = 0;
                $Total[$k]["11"] = 0;
                $Total[$k]["10"] = 0;
                $Total[$k]["03"] = 0;
                $Total[$k]["01"] = 0;
                $Total[$k]["00"] = 0;
                $Total[$k]["33"] = $v["33"] + $visitTotal[$k]["33"];
                $Total[$k]["31"] = $v["31"] + $visitTotal[$k]["31"];
                $Total[$k]["30"] = $v["30"] + $visitTotal[$k]["30"];
                $Total[$k]["13"] = $v["13"] + $visitTotal[$k]["13"];
                $Total[$k]["11"] = $v["11"] + $visitTotal[$k]["11"];
                $Total[$k]["10"] = $v["10"] + $visitTotal[$k]["10"];
                $Total[$k]["03"] = $v["03"] + $visitTotal[$k]["03"];
                $Total[$k]["01"] = $v["01"] + $visitTotal[$k]["01"];
                $Total[$k]["00"] = $v["00"] + $visitTotal[$k]["00"];
            }
        $result["Total"] = $Total;
        $result["homeTotal"] = $homeTotal;
        $result["visitTotal"] = $visitTotal;
        $result["team"] = $teamInfo;
        return $result;
    }

    /**
     * 获取联赛队伍排名情况
     */
//    public function getTeamRank($leagueId) {
//        $team = (new Query())->select("t.team_code")
//            ->from("league_team as l")
//            ->leftJoin("team as t", "t.team_id= l.team_id")
//            ->where(["l.league_id" => $leagueId])
//            ->all();
//        if(empty($team)){
//            $data = [];
//            return $data;
//        }
//        foreach ($team as $k => $v) {
//            $data = (new Query())->select("s.home_short_name,h.schedule_mid,h.home_team_rank")
//                ->from("history_count as h")
////                    ->leftJoin("schedule_result as sr","sr.schedule_mid=h.schedule_mid")
//                ->leftJoin("schedule as s", "s.schedule_mid=h.schedule_mid")
//                ->where(["s.home_team_mid" => $v["team_code"]])
//                ->orderBy("h.history_count_id desc")
//                ->all();
//        }
//    }

}
