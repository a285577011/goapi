/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-10-24
 */

--联赛表新增字段 （张悦玲）
ALTER TABLE league ADD league_type TINYINT(4) NULL DEFAULT 1  COMMENT '1：足球 2：篮球' ;

--球队表新增字段 （张悦玲）
ALTER TABLE team ADD team_type TINYINT(4) NULL  DEFAULT 1 COMMENT '1：足球 2：篮球' ;

--联赛表删除字段 （张悦玲）
ALTER TABLE league DROP COLUMN league_mid;

--新增篮球联赛球队关联表 （张悦玲）
CREATE TABLE `lan_league_team` (
  `lan_league_team` int(11) NOT NULL AUTO_INCREMENT,
  `lan_league_id` int(11) DEFAULT NULL COMMENT '联赛ID',
  `lan_team_id` int(11) DEFAULT NULL COMMENT '球队Id',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_league_team`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4;

--新增篮球赛程表 （张悦玲）
CREATE TABLE `lan_schedule` (
  `lan_schedule_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '篮球赛程表ID',
  `schedule_code` varchar(25) DEFAULT NULL COMMENT '赛程编号',
  `schedule_date` int(11) DEFAULT NULL COMMENT '赛程日期',
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛程唯一ID',
  `league_id` int(11) NOT NULL COMMENT '联赛ID',
  `league_name` varchar(50) DEFAULT NULL COMMENT '联赛名',
  `visit_short_name` varchar(50) NOT NULL COMMENT '客队简称',
  `home_short_name` varchar(50) NOT NULL COMMENT '主队简称',
  `visit_team_id` int(11) NOT NULL COMMENT '客队ID',
  `home_team_id` int(11) NOT NULL COMMENT '主队ID',
  `start_time` datetime NOT NULL COMMENT '比赛开始时间',
  `beginsale_time` datetime NOT NULL COMMENT '开售时间',
  `endsale_time` datetime NOT NULL COMMENT '停售时间',
  `schedule_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '停售状态',
  `schedule_sf` tinyint(4) NOT NULL DEFAULT '2' COMMENT '胜负  0、待开售此玩法  1、仅开售过关方式   2、开售单关方式和过关方式   3、未开售此玩法',
  `schedule_rfsf` tinyint(4) NOT NULL DEFAULT '2' COMMENT '让分胜负  0、待开售此玩法  1、仅开售过关方式   2、开售单关方式和过关方式   3、未开售此玩法',
  `schedule_dxf` tinyint(4) NOT NULL DEFAULT '2' COMMENT '大小分  0、待开售此玩法  1、仅开售过关方式   2、开售单关方式和过关方式   3、未开售此玩法',
  `schedule_sfc` tinyint(4) NOT NULL DEFAULT '1' COMMENT '胜负差  0、待开售此玩法  1、仅开售过关方式   2、开售单关方式和过关方式   3、未开售此玩法',
  `high_win_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '高中奖赛事  0：不是 1：是',
  `hot_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '热门赛事 0：不热门 1：热门',
  `opt_id` int(11) DEFAULT NULL COMMENT '修改操作人',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `modify_time` datetime DEFAULT NULL COMMENT '更新时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'CURRENT_TIMESTAMP',
  PRIMARY KEY (`lan_schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4;

--新增篮球赛程结果表  （张悦玲）
CREATE TABLE `lan_schedule_result` (
  `lan_schedule_result_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '篮球赛程结果ID',
  `schedule_mid` varchar(25) NOT NULL COMMENT '比赛MID',
  `schedule_date` int(11) NOT NULL COMMENT '赛程date',
  `guest_one` varchar(25) DEFAULT NULL COMMENT '第一节',
  `guest_two` varchar(25) DEFAULT NULL COMMENT '第二节',
  `guest_three` varchar(25) DEFAULT NULL COMMENT '第三节',
  `guest_four` varchar(25) DEFAULT NULL COMMENT '第四节',
  `guest_add_one` varchar(25) DEFAULT NULL COMMENT '加时1',
  `guest_add_two` varchar(25) DEFAULT NULL COMMENT '加时2',
  `guest_add_three` varchar(25) DEFAULT NULL COMMENT '加时3',
  `guest_add_four` varchar(25) DEFAULT NULL COMMENT '加时4',
  `result_3001` tinyint(4) DEFAULT NULL COMMENT '胜负结果',
  `result_3002` tinyint(4) DEFAULT NULL COMMENT '让分胜负',
  `result_3003` varchar(25) DEFAULT NULL COMMENT '胜分差',
  `result_3004` tinyint(4) DEFAULT NULL COMMENT '大小分 1:大 2：小',
  `odds_3001` decimal(10,2) DEFAULT NULL COMMENT '胜负赔率',
  `odds_3002` decimal(10,2) DEFAULT NULL COMMENT '让分胜负赔率',
  `odds_3003` decimal(10,2) DEFAULT NULL COMMENT '大小分赔率',
  `odds_3004` decimal(10,2) DEFAULT NULL COMMENT '胜分差赔率',
  `opt_id` int(11) DEFAULT NULL COMMENT '后台修改人',
  `match_time` varchar(20) DEFAULT NULL COMMENT '比赛进行时间',
  `schedule_fc` int(11) DEFAULT '0' COMMENT '分差',
  `schedule_zf` int(11) DEFAULT NULL COMMENT '总分',
  `result_zcbf` varchar(25) DEFAULT NULL COMMENT '中场比分',
  `result_qcbf` varchar(25) DEFAULT NULL COMMENT '全场比分',
  `result_rf` float(11,1) DEFAULT NULL COMMENT '最后让分',
  `result_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否开奖 0：未开赛 1：比赛中 2：完结',
  `deal_status` tinyint(4) DEFAULT '0' COMMENT '是否已兑奖 0：未兑奖 1：详情单已兑奖 ',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_schedule_result_id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4;


--新增胜负赔率表（3001） （张悦玲）
CREATE TABLE `odds_3001` (
  `odds_3001_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(25) NOT NULL COMMENT '场次MId',
  `update_nums` int(11) DEFAULT NULL COMMENT '更新次',
  `wins_3001` decimal(18,2) NOT NULL COMMENT '胜赔率',
  `wins_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `lose_3001` decimal(18,2) NOT NULL COMMENT '负赔率',
  `lose_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：下降',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作员ID',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_3001_id`)
) ENGINE=InnoDB AUTO_INCREMENT=253 DEFAULT CHARSET=utf8mb4;

--新增让分胜负赔率表（3002） （张悦玲）
CREATE TABLE `odds_3002` (
  `odds_3002_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(25) NOT NULL COMMENT '场次MId',
  `update_nums` int(11) DEFAULT NULL COMMENT '更新次',
  `rf_nums` decimal(18,1) NOT NULL DEFAULT '0.0' COMMENT '让分数',
  `wins_3002` decimal(18,2) NOT NULL COMMENT '胜赔率',
  `wins_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `lose_3002` decimal(18,2) NOT NULL COMMENT '负赔率',
  `lose_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作员ID',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_3002_id`)
) ENGINE=InnoDB AUTO_INCREMENT=363 DEFAULT CHARSET=utf8mb4;


--新增胜分差赔率表（3003） （张悦玲）
CREATE TABLE `odds_3003` (
  `odds_3003_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛程MID',
  `update_nums` int(11) DEFAULT NULL COMMENT '更新次',
  `cha_01` decimal(18,2) NOT NULL COMMENT '主胜1-5',
  `cha_01_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_02` decimal(18,2) NOT NULL COMMENT '主胜6-10',
  `cha_02_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_03` decimal(18,2) NOT NULL COMMENT '主胜11-15',
  `cha_03_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_04` decimal(18,2) NOT NULL COMMENT '主胜16-20',
  `cha_04_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_05` decimal(18,2) NOT NULL COMMENT '主胜21-25',
  `cha_05_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_06` decimal(18,2) NOT NULL COMMENT '主胜26+',
  `cha_06_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_11` decimal(18,2) NOT NULL COMMENT '主负1-5',
  `cha_11_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_12` decimal(18,2) NOT NULL COMMENT '主负6-10',
  `cha_12_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_13` decimal(18,2) NOT NULL COMMENT '主负11-15',
  `cha_13_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_14` decimal(18,2) NOT NULL COMMENT '主负16-20',
  `cha_14_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_15` decimal(18,2) NOT NULL COMMENT '主负21-25',
  `cha_15_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `cha_16` decimal(18,2) NOT NULL COMMENT '主负26+',
  `cha_16_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作员ID',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_3003_id`)
) ENGINE=InnoDB AUTO_INCREMENT=168 DEFAULT CHARSET=utf8mb4;

--新增大小分赔率表 （3004） （张悦玲）
CREATE TABLE `odds_3004` (
  `odds_3004_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛程MID',
  `update_nums` int(11) DEFAULT NULL COMMENT '更新次',
  `fen_cutoff` decimal(18,2) NOT NULL COMMENT '大小分切割点',
  `da_3004` decimal(18,2) NOT NULL COMMENT '大分赔率',
  `da_3004_trend` tinyint(4) NOT NULL COMMENT '升降状态 0：不变 1：上升 2：下降',
  `xiao_3004` decimal(18,2) NOT NULL COMMENT '小分赔率',
  `xiao_3004_trend` tinyint(4) NOT NULL COMMENT '升降状态 0：不变 1：上升 2：下降',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作员ID',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_3004_id`)
) ENGINE=InnoDB AUTO_INCREMENT=307 DEFAULT CHARSET=utf8mb4;

--新增篮球大小分盘口赔率  （张悦玲）
CREATE TABLE `lan_daxiao_odds` (
  `lan_daxiao_odds_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(25) DEFAULT NULL COMMENT '赛程MID',
  `company_name` varchar(25) DEFAULT NULL COMMENT '公司名',
  `country` varchar(25) DEFAULT NULL COMMENT '国家',
  `handicap_type` tinyint(4) DEFAULT NULL COMMENT '数据类型 1：初 2：即',
  `handicap_name` varchar(20) DEFAULT NULL COMMENT '初、即',
  `cutoff_fen` float(11,1) DEFAULT NULL COMMENT '预测总分',
  `odds_da` decimal(18,2) DEFAULT NULL COMMENT '大分赔率',
  `odds_da_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `odds_xiao` decimal(18,2) DEFAULT NULL COMMENT '小分赔率',
  `odds_xiao_trend` tinyint(4) DEFAULT NULL COMMENT '升降状态 0：不变 1：上升 2：下降',
  `profit_rate` float(11,2) DEFAULT NULL COMMENT '返还率',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_daxiao_odds_id`)
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4;

--新增篮球欧赔盘口赔率  （张悦玲）
CREATE TABLE `lan_europe_odds` (
  `lan_europe_odds_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(25) DEFAULT NULL COMMENT '赛程MID',
  `company_name` varchar(25) DEFAULT NULL COMMENT '公司名',
  `country` varchar(25) DEFAULT NULL COMMENT '国家',
  `handicap_type` tinyint(4) DEFAULT NULL COMMENT '数据类型 1：初 2：即',
  `handicap_name` varchar(20) DEFAULT NULL COMMENT '初、即',
  `odds_3` decimal(18,2) DEFAULT NULL COMMENT '胜赔率',
  `odds_3_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `odds_0` decimal(18,2) DEFAULT NULL COMMENT '负赔率',
  `odds_0_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `profit_rate` float(11,2) DEFAULT NULL COMMENT '返还率',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_europe_odds_id`)
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4;

--新增篮球亚盘（让分）盘口赔率 （张悦玲）
CREATE TABLE `lan_rangfen_odds` (
  `lan_rangfen_odds_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(25) DEFAULT NULL COMMENT '赛程MID',
  `company_name` varchar(25) DEFAULT NULL COMMENT '公司名',
  `country` varchar(25) DEFAULT NULL COMMENT '国家',
  `handicap_type` tinyint(4) DEFAULT NULL COMMENT '数据类型 1：初 2：即',
  `handicap_name` varchar(20) DEFAULT NULL COMMENT '初、即',
  `rf_nums` float(11,1) DEFAULT NULL COMMENT '让分数',
  `odds_3` decimal(18,2) DEFAULT NULL COMMENT '让分胜赔率',
  `odds_3_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `odds_0` decimal(18,2) DEFAULT NULL COMMENT '让分负赔率',
  `odds_0_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 2：下降',
  `profit_rate` float(11,2) DEFAULT NULL COMMENT '返还率',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_rangfen_odds_id`)
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4;

--新增篮球历史统计表  （张悦玲）
CREATE TABLE `lan_history_count` (
  `lan_history_count_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '历史交锋统计ID',
  `schedule_mid` varchar(25) DEFAULT NULL COMMENT '赛程MID',
  `clash_nums` int(11) DEFAULT NULL COMMENT '历史交锋次数',
  `win_nums` int(11) DEFAULT NULL COMMENT '近几场交锋赢次数',
  `lose_nums` int(11) DEFAULT NULL COMMENT '历史交锋负次数',
  `home_team_rank` int(11) DEFAULT NULL COMMENT '主队排名',
  `visit_team_rank` int(11) DEFAULT NULL COMMENT '客队排名',
  `scale_3001_3` varchar(10) DEFAULT NULL COMMENT '胜购买率',
  `scale_3001_0` varchar(10) DEFAULT NULL COMMENT '负购买率',
  `scale_3002_3` varchar(10) DEFAULT NULL COMMENT '让分胜购买率',
  `scale_3002_0` varchar(10) DEFAULT NULL COMMENT '让分负购买率',
  `europe_odds_3` varchar(25) DEFAULT NULL COMMENT '胜平均欧赔',
  `europe_odds_0` varchar(25) DEFAULT NULL COMMENT '负平均欧赔',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_history_count_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4;

--篮球专家预测点评 （张悦玲）
CREATE TABLE `lan_pre_result` (
  `lan_pre_result_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '篮球预测赛事',
  `schedule_mid` varchar(25) NOT NULL COMMENT '篮球赛程MID',
  `pre_result_title` varchar(100) DEFAULT NULL COMMENT '预测标题',
  `pre_result_3001` varchar(50) DEFAULT NULL COMMENT '胜负预测结果',
  `pre_result_3002` varchar(50) DEFAULT NULL COMMENT '让分预测结果',
  `pre_result_3004` varchar(50) DEFAULT NULL COMMENT '大小分预测结果',
  `confidence_index` float(11,1) DEFAULT NULL COMMENT '信息指数',
  `expert_analysis` text COMMENT '专家分析',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_pre_result_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4;

--篮球球队历史交锋数据 （张悦玲）
CREATE TABLE `lan_schedule_history` (
  `lan_schedule_history_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '篮球历史交锋数据',
  `data_type` tinyint(4) DEFAULT NULL COMMENT '数据类型 1：历史 2：未来',
  `schedule_mid` varchar(25) DEFAULT NULL COMMENT '赛程MID',
  `league_code` varchar(25) DEFAULT NULL COMMENT '联赛CODE',
  `league_name` varchar(50) DEFAULT NULL COMMENT '联赛名',
  `play_time` datetime DEFAULT NULL COMMENT '比赛时间',
  `home_team_code` varchar(25) DEFAULT NULL COMMENT '主队code',
  `home_team_name` varchar(50) DEFAULT NULL COMMENT '主队名',
  `visit_team_code` varchar(25) DEFAULT NULL COMMENT '客队code',
  `visit_team_name` varchar(50) DEFAULT NULL COMMENT '客队名',
  `schedule_bf` varchar(50) DEFAULT NULL COMMENT '赛程比分',
  `schedule_sf_nums` int(11) DEFAULT NULL COMMENT '赛程胜/负 分',
  `result_3002` tinyint(4) DEFAULT NULL COMMENT '让球胜负结果',
  `rf_nums` float(11,1) DEFAULT NULL COMMENT '赛程让分数',
  `cutoff_nums` float(11,1) DEFAULT NULL COMMENT '预测总分',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_schedule_history_id`)
) ENGINE=InnoDB AUTO_INCREMENT=799 DEFAULT CHARSET=utf8mb4;

--篮球球队排名 （张悦玲）
CREATE TABLE `lan_team_rank` (
  `team_rank_id` int(11) NOT NULL AUTO_INCREMENT,
  `team_code` varchar(25) DEFAULT NULL COMMENT '球队CODE',
  `team_name` varchar(100) DEFAULT NULL COMMENT '球队简称',
  `league_code` varchar(25) DEFAULT NULL COMMENT '联赛code',
  `league_name` varchar(100) DEFAULT NULL COMMENT '联赛简称',
  `team_position` tinyint(4) DEFAULT NULL COMMENT '球队所属方位 1：东部 2：西部',
  `team_rank` int(11) DEFAULT NULL COMMENT '球队排名',
  `game_nums` int(11) DEFAULT NULL COMMENT '比赛场数',
  `win_nums` int(11) DEFAULT NULL COMMENT '胜几场',
  `lose_nums` int(11) DEFAULT NULL COMMENT '负几场',
  `win_rate` float(11,2) DEFAULT NULL COMMENT '胜率',
  `wins_diff` float(11,2) DEFAULT NULL COMMENT '胜差',
  `defen_nums` float(11,2) DEFAULT NULL COMMENT '得分数',
  `shifen_nums` float(11,2) DEFAULT NULL COMMENT '失分数',
  `home_result` varchar(50) DEFAULT NULL COMMENT '主场比赛数据',
  `visit_result` varchar(50) DEFAULT NULL COMMENT '客场比赛数据',
  `east_result` varchar(50) DEFAULT NULL COMMENT '对阵东部',
  `west_result` varchar(50) DEFAULT NULL COMMENT '对阵西部',
  `same_result` varchar(50) DEFAULT NULL COMMENT '同区战绩',
  `ten_result` varchar(50) DEFAULT NULL COMMENT '近10场',
  `near_result` varchar(50) DEFAULT NULL COMMENT '近期状态',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`team_rank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4;

--