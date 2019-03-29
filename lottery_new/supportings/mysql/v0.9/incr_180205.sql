/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-2-5
 */
--北单赛程 （张悦玲）
CREATE TABLE `bd_schedule` (
  `bd_schedule_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '北单赛程主键',
  `periods` int(11) NOT NULL COMMENT '期数',
  `schedule_date` date DEFAULT NULL COMMENT '赛程日',
  `open_mid` varchar(25) NOT NULL COMMENT '期内赛程MID',
  `schedule_mid` varchar(25) DEFAULT NULL COMMENT '竞彩赛程MID',
  `play_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '玩法类型 1：非胜负过关 2：胜负过关',
  `schedule_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '赛程类型 1：足球 2：篮球 3：冰球 4：网球 5：羽毛球 6：乒乓球 7：橄榄球 8：',
  `bd_sort` int(11) NOT NULL COMMENT '期内赛程序号',
  `start_time` datetime NOT NULL COMMENT '开始比赛时间',
  `beginsale_time` datetime NOT NULL COMMENT '开售时间',
  `endsale_time` datetime NOT NULL COMMENT '停售时间',
  `league_code` int(11) NOT NULL DEFAULT '1' COMMENT '联赛编号',
  `league_name` varchar(50) NOT NULL COMMENT '联赛名',
  `home_code` int(11) NOT NULL DEFAULT '1' COMMENT '主场编号',
  `home_name` varchar(50) NOT NULL COMMENT '主场名',
  `visit_code` int(11) NOT NULL DEFAULT '1' COMMENT '客队编号',
  `visit_name` varchar(50) NOT NULL COMMENT '客场名',
  `spf_rq_nums` int(11) NOT NULL DEFAULT '0' COMMENT '胜平负让球数',
  `sfgg_rf_nums` float(8,1) NOT NULL DEFAULT '0.0' COMMENT '胜负过关让分数',
  `sale_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '赛程出售状态 1：在售 2：停售 ',
  `sfgg_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '胜负过关出售状态 1：在售 2：停售 ',
  `zjqs_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '总进球数出售状态 1：在售 2：停售 ',
  `bqc_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '半全场出售状态 1：在售 2：停售 ',
  `spf_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '胜平负出售状态 1：在售 2：停售 ',
  `sxpds_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '上下盘单双出售状态 1：在售 2：停售 ',
  `dcbf_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '单场比分出售状态 1：在售 2：停售 ',
  `xbcbf_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '下半场比分出售状态 1：在售 2：停售 ',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bd_schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=547 DEFAULT CHARSET=utf8mb4;

--北单赛程结果表 （张悦玲）
CREATE TABLE `bd_schedule_result` (
  `bd_schedule_result_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '北单赛程结果主键',
  `periods` int(11) NOT NULL COMMENT '北单期数',
  `open_mid` int(11) NOT NULL COMMENT '期内赛程MID',
  `schedule_mid` int(11) DEFAULT NULL COMMENT '竞彩赛程MID',
  `play_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '玩法类型 1：非胜负过关 2：胜负过关',
  `bd_sort` int(11) NOT NULL COMMENT '期内赛程序号',
  `result_5001` tinyint(4) DEFAULT NULL COMMENT '胜平负结果',
  `odds_5001` decimal(8,2) DEFAULT NULL COMMENT '胜平负结果赔率',
  `result_5002` tinyint(4) DEFAULT NULL COMMENT '总进球结果',
  `odds_5002` decimal(8,2) DEFAULT NULL COMMENT '总进球赔率',
  `result_5003` char(25) DEFAULT NULL COMMENT '半全场结果',
  `odds_5003` decimal(8,2) DEFAULT NULL COMMENT '半全场赔率',
  `result_5004` tinyint(4) DEFAULT NULL COMMENT '上下盘单双结果',
  `odds_5004` decimal(8,2) DEFAULT NULL COMMENT '上下盘单双赔率',
  `result_5005` char(25) DEFAULT NULL COMMENT '比分结果',
  `odds_5005` decimal(8,2) DEFAULT NULL COMMENT '比分赔率',
  `result_5006` tinyint(4) DEFAULT NULL COMMENT '胜负过关结果',
  `odds_5006` decimal(8,2) DEFAULT '0.00' COMMENT '胜负过关赔率',
  `result_bcbf` char(25) DEFAULT NULL COMMENT '半场比分',
  `status` tinyint(4) DEFAULT '0' COMMENT '赛程状态 是否开奖 0：未开赛    1：比赛中  2：完结 3:取消 4：延迟 5：比赛结果不对 6：未出赛果 7：腰斩',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` time DEFAULT '00:00:00',
  PRIMARY KEY (`bd_schedule_result_id`)
) ENGINE=InnoDB AUTO_INCREMENT=545 DEFAULT CHARSET=utf8mb4;

--北单联赛表 （张悦玲）
CREATE TABLE `bd_league` (
  `league_id` int(11) NOT NULL AUTO_INCREMENT,
  `league_code` varchar(25) NOT NULL COMMENT '编码',
  `league_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '赛程类型 1：足球 2：篮球 3：冰球 4：网球 5：羽毛球 6：乒乓球 7：橄榄球 8：',
  `league_short_name` varchar(25) DEFAULT NULL COMMENT '联赛简称',
  `league_long_name` varchar(100) NOT NULL COMMENT '联赛全称',
  `league_img` varchar(100) DEFAULT NULL COMMENT '图标',
  `league_category_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属分类',
  `league_remarks` varchar(500) DEFAULT NULL COMMENT '备注',
  `league_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  `opt_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改人',
  PRIMARY KEY (`league_id`),
  KEY `league_code` (`league_code`)
) ENGINE=InnoDB AUTO_INCREMENT=272 DEFAULT CHARSET=utf8mb4;

--北单球队表 （张悦玲）
CREATE TABLE `bd_team` (
  `team_id` int(11) NOT NULL AUTO_INCREMENT,
  `team_code` varchar(25) NOT NULL COMMENT '编码',
  `team_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '赛程类型 1：足球 2：篮球 3：冰球 4：网球 5：羽毛球 6：乒乓球 7：橄榄球 8：',
  `team_position` tinyint(4) DEFAULT NULL COMMENT '球队所属方位 1：东部 2：西部',
  `team_short_name` varchar(25) DEFAULT NULL COMMENT '球队简称',
  `team_long_name` varchar(100) NOT NULL COMMENT '球队全称',
  `country_name` varchar(150) DEFAULT NULL,
  `country_code` varchar(50) DEFAULT NULL,
  `team_img` varchar(100) DEFAULT NULL COMMENT '图标',
  `team_remarks` varchar(255) DEFAULT NULL COMMENT '备注',
  `team_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  `opt_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改人',
  PRIMARY KEY (`team_id`),
  KEY `team_code` (`team_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3525 DEFAULT CHARSET=utf8mb4;

--北单亚赔表 （张悦玲）
CREATE TABLE `bd_asian_handicap` (
  `asian_handicap_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `company_name` varchar(20) DEFAULT NULL COMMENT '公司名字',
  `country` varchar(20) DEFAULT NULL COMMENT '国家',
  `handicap_type` tinyint(4) DEFAULT NULL COMMENT '1、初盘  2、现盘',
  `handicap_name` varchar(20) DEFAULT NULL COMMENT '初盘、现盘',
  `home_discount` decimal(18,2) DEFAULT NULL COMMENT '主队贴水',
  `home_discount_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：下降',
  `let_index` varchar(20) DEFAULT NULL COMMENT '让球指数',
  `visit_discount` decimal(18,2) DEFAULT NULL COMMENT '客队贴水',
  `visit_discount_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：下降',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`asian_handicap_id`),
  KEY `mid` (`schedule_mid`,`handicap_type`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=169238 DEFAULT CHARSET=utf8mb4;

--北单欧赔表 （张悦玲）
CREATE TABLE `bd_europe_odds` (
  `europe_odds_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `company_name` varchar(20) DEFAULT NULL COMMENT '公司名字',
  `country` varchar(20) DEFAULT NULL COMMENT '国家',
  `handicap_type` tinyint(4) DEFAULT NULL COMMENT '1、初盘  2、现盘',
  `handicap_name` varchar(20) DEFAULT NULL COMMENT '初盘、现盘',
  `odds_3` decimal(18,2) DEFAULT NULL COMMENT '主胜赔率',
  `odds_3_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：下降',
  `odds_1` decimal(18,2) DEFAULT NULL COMMENT '平赔率',
  `odds_1_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：下降',
  `odds_0` decimal(18,2) DEFAULT NULL COMMENT '客胜赔率',
  `odds_0_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：下降',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`europe_odds_id`),
  KEY `mid` (`schedule_mid`,`handicap_type`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=74038 DEFAULT CHARSET=utf8mb4;

--北单历史统计  （张悦玲）
CREATE TABLE `bd_history_count` (
  `history_count_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `double_play_num` int(11) DEFAULT NULL COMMENT '历史交锋次数',
  `num3` int(11) DEFAULT NULL COMMENT '胜次数',
  `num1` int(11) DEFAULT NULL COMMENT '平次数',
  `num0` int(11) DEFAULT NULL COMMENT '负次数',
  `home_num_3` int(11) DEFAULT NULL COMMENT '近10场 主队胜场次',
  `home_num_1` int(11) DEFAULT NULL COMMENT '近10场 主队平场次',
  `home_num_0` int(11) DEFAULT NULL COMMENT '近10场 主队负场次',
  `visit_num_3` int(11) DEFAULT NULL COMMENT '近10场 客队胜场次',
  `visit_num_1` int(11) DEFAULT NULL COMMENT '近10场 客队平场次',
  `visit_num_0` int(11) DEFAULT NULL COMMENT '近10场 客队负场次',
  `home_team_rank` varchar(11) DEFAULT NULL COMMENT '联赛排名',
  `visit_team_rank` varchar(11) DEFAULT NULL COMMENT '联赛排名',
  `home_team_league` varchar(50) DEFAULT NULL COMMENT '主队所属联赛',
  `visit_team_league` varchar(50) DEFAULT NULL COMMENT '客队所属联赛',
  `scale_3010_3` varchar(10) DEFAULT NULL COMMENT '投注比例：6个',
  `scale_3010_1` varchar(10) DEFAULT NULL COMMENT '投注比例：6个',
  `scale_3010_0` varchar(10) DEFAULT NULL COMMENT '投注比例：6个',
  `scale_3006_3` varchar(10) DEFAULT NULL COMMENT '投注比例：6个',
  `scale_3006_1` varchar(10) DEFAULT NULL COMMENT '投注比例：6个',
  `scale_3006_0` varchar(10) DEFAULT NULL COMMENT '投注比例：6个',
  `europe_odds_3` varchar(10) DEFAULT NULL COMMENT '平均胜欧指',
  `europe_odds_1` varchar(10) DEFAULT NULL COMMENT '平均平欧指',
  `europe_odds_0` varchar(10) DEFAULT NULL COMMENT '平均负欧指',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_count_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5708 DEFAULT CHARSET=utf8mb4;

--北单预测表 （张悦玲）
CREATE TABLE `bd_pre_result` (
  `pre_result_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `pre_result_title` varchar(100) DEFAULT NULL COMMENT '口号标题',
  `pre_result_3010` varchar(50) DEFAULT NULL COMMENT '预测胜平负赛果',
  `pre_result_3007` varchar(50) DEFAULT NULL COMMENT '预测比分',
  `confidence_index` float(11,1) DEFAULT NULL COMMENT '信心指数',
  `average_home_percent` float(11,1) DEFAULT NULL COMMENT '主队平均战力百分比',
  `average_visit_percent` float(11,1) DEFAULT NULL COMMENT '客队平均战力百分比',
  `json_data` text COMMENT '将数据使用json编码',
  `expert_analysis` text COMMENT '专家分析',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pre_result_id`),
  KEY `name` (`schedule_mid`)
) ENGINE=InnoDB AUTO_INCREMENT=5605 DEFAULT CHARSET=utf8mb4;

--北单赛程事件表 （张悦玲）
CREATE TABLE `bd_schedule_event` (
  `schedule_event_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `schedule_event_mid` varchar(11) DEFAULT NULL COMMENT '事件mid',
  `team_type` int(11) DEFAULT NULL COMMENT '1、主队   2、客队',
  `team_name` varchar(10) DEFAULT NULL COMMENT '主队 、客队',
  `event_type` int(11) DEFAULT NULL COMMENT '1、进球  2、点球  3、乌龙球   4、两黄一红   5、换人',
  `event_type_name` varchar(20) DEFAULT NULL COMMENT '进球  、点球  、乌龙球   、两黄一红   、换人',
  `event_content` varchar(100) DEFAULT NULL COMMENT '事件内容',
  `event_time` varchar(10) DEFAULT NULL COMMENT '事件时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cf` int(11) DEFAULT '0' COMMENT '0、不重复  1、重复',
  PRIMARY KEY (`schedule_event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--北单历史战绩表 （张悦玲）
CREATE TABLE `bd_schedule_history` (
  `schedule_history_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '历史赛事',
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛事唯一mid',
  `league_code` varchar(11) DEFAULT NULL COMMENT '联赛mid',
  `league_name` varchar(50) DEFAULT NULL COMMENT '赛事名称',
  `play_time` datetime DEFAULT NULL COMMENT '比赛时间',
  `home_team_mid` varchar(11) DEFAULT NULL COMMENT '主队MID',
  `home_team_name` varchar(50) DEFAULT NULL COMMENT '主队名称',
  `visit_team_mid` varchar(11) DEFAULT NULL COMMENT '客队mid',
  `visit_team_name` varchar(50) DEFAULT NULL COMMENT '客队ID',
  `result_3007` varchar(50) DEFAULT NULL COMMENT '比分结果',
  `result_3009_b` varchar(50) DEFAULT NULL COMMENT '半场',
  `result_3010` varchar(50) DEFAULT NULL COMMENT '胜平负',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_history_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60937 DEFAULT CHARSET=utf8mb4;

--北单赛程提点表 （张悦玲）
CREATE TABLE `bd_schedule_remind` (
  `schedule_remind_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `schedule_type` tinyint(4) DEFAULT NULL COMMENT '赛程所属类型 1：足球 2：篮球',
  `content` text COMMENT '赛事提点',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_remind_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--北单赛程战况表 （张悦玲）
CREATE TABLE `bd_schedule_technic` (
  `schedule_technic_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL,
  `home_ball_rate` int(11) NOT NULL DEFAULT '0' COMMENT '主队控球率',
  `visit_ball_rate` int(11) NOT NULL DEFAULT '0' COMMENT '客队控球率',
  `home_shoot_num` int(11) NOT NULL DEFAULT '0' COMMENT '主队 射门次数',
  `visit_shoot_num` int(11) NOT NULL DEFAULT '0' COMMENT '客队射门次数',
  `home_shoot_right_num` int(11) NOT NULL DEFAULT '0' COMMENT '主队射正次数',
  `visit_shoot_right_num` int(11) NOT NULL DEFAULT '0' COMMENT '客队射正次数',
  `home_corner_num` int(11) NOT NULL DEFAULT '0' COMMENT '主队角球次数',
  `visit_corner_num` int(11) NOT NULL DEFAULT '0' COMMENT '客队角球次数',
  `home_foul_num` int(11) NOT NULL DEFAULT '0' COMMENT '主队犯规次数',
  `visit_foul_num` int(11) NOT NULL DEFAULT '0' COMMENT '客队犯规次数',
  `home_red_num` int(11) NOT NULL DEFAULT '0' COMMENT '红队红牌',
  `home_yellow_num` int(11) NOT NULL DEFAULT '0' COMMENT '主队黄牌',
  `visit_red_num` int(11) NOT NULL DEFAULT '0' COMMENT '客队红牌',
  `visit_yellow_num` int(11) NOT NULL DEFAULT '0' COMMENT '客队黄牌',
  `odds_3006` decimal(18,0) DEFAULT NULL COMMENT '让球胜平负赔率',
  `odds_3007` decimal(18,0) DEFAULT NULL COMMENT '比分赔率',
  `odds_3008` decimal(18,0) DEFAULT NULL COMMENT '总进球数赔率',
  `odds_3009` decimal(18,0) DEFAULT NULL COMMENT '半全场赔率',
  `odds_3010` decimal(18,0) DEFAULT NULL COMMENT '胜平负赔率',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_technic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--北单胜平负赔率表 （张悦玲）
CREATE TABLE `odds_5001` (
  `odds_5001_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '北单胜平负赔率表',
  `open_mid` varchar(25) DEFAULT NULL COMMENT '期内MID',
  `update_nums` int(11) DEFAULT '1' COMMENT '更新次数',
  `odds_3` decimal(8,2) DEFAULT NULL COMMENT '胜赔率',
  `trend_3` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_1` decimal(8,2) DEFAULT NULL COMMENT '负赔率',
  `trend_1` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_0` decimal(8,2) DEFAULT NULL COMMENT '负赔率',
  `trend_0` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_5001_id`)
) ENGINE=InnoDB AUTO_INCREMENT=372 DEFAULT CHARSET=utf8mb4;

--北单总进球数赔率表 （张悦玲）
CREATE TABLE `odds_5002` (
  `odds_5002_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '总进球赔率ID',
  `open_mid` varchar(25) NOT NULL COMMENT '期内MID',
  `update_nums` int(11) DEFAULT '1' COMMENT '更新次',
  `odds_0` decimal(8,2) DEFAULT NULL COMMENT '0球 赔率',
  `trend_0` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_1` decimal(8,2) DEFAULT NULL COMMENT '1球 赔率',
  `trend_1` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_2` decimal(8,2) DEFAULT NULL COMMENT '2球赔率',
  `trend_2` tinyint(4) DEFAULT NULL COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_3` decimal(8,2) DEFAULT NULL COMMENT '3球 赔率',
  `trend_3` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_4` decimal(8,2) DEFAULT NULL COMMENT '4球陪聊',
  `trend_4` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_5` decimal(8,2) DEFAULT NULL COMMENT '5球赔率',
  `trend_5` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_6` decimal(8,2) DEFAULT NULL COMMENT '6球赔率',
  `trend_6` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_7` decimal(8,2) DEFAULT NULL COMMENT '7球 + 赔率',
  `trend_7` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_5002_id`)
) ENGINE=InnoDB AUTO_INCREMENT=372 DEFAULT CHARSET=utf8mb4;

--北单半全场胜负赔率表 （张悦玲）
CREATE TABLE `odds_5003` (
  `odds_5003_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '半全场胜负赔率',
  `open_mid` varchar(25) DEFAULT NULL,
  `update_nums` int(11) DEFAULT '1' COMMENT '更新数',
  `odds_00` float(8,2) DEFAULT NULL COMMENT '半全场负负赔率',
  `trend_00` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_01` decimal(8,2) DEFAULT NULL COMMENT '半全场负平赔率',
  `trend_01` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_03` decimal(8,2) DEFAULT NULL COMMENT '半全场负胜赔率',
  `trend_03` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_10` decimal(8,2) DEFAULT NULL COMMENT '半全场平负赔率',
  `trend_10` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_11` decimal(8,2) DEFAULT NULL COMMENT '半全场平平赔率',
  `trend_11` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_13` decimal(8,2) DEFAULT NULL COMMENT '半全场平胜赔率',
  `trend_13` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_30` decimal(8,2) DEFAULT NULL COMMENT '半全场胜负赔率',
  `trend_30` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_31` decimal(8,2) DEFAULT NULL COMMENT '半全场胜平赔率',
  `trend_31` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_33` decimal(8,2) DEFAULT NULL COMMENT '半全场胜胜赔率',
  `trend_33` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_5003_id`)
) ENGINE=InnoDB AUTO_INCREMENT=372 DEFAULT CHARSET=utf8mb4;

--北单上下单双赔率表 （张悦玲）
CREATE TABLE `odds_5004` (
  `odds_5004_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '上下场单双赔率',
  `open_mid` varchar(24) DEFAULT NULL,
  `update_nums` int(11) DEFAULT '1' COMMENT '更新数',
  `odds_1` decimal(8,2) DEFAULT NULL COMMENT '上半场单',
  `trend_1` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_2` decimal(8,2) DEFAULT NULL COMMENT '上半场双',
  `trend_2` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_3` decimal(8,2) DEFAULT NULL COMMENT '下半场单',
  `trend_3` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_4` decimal(8,2) DEFAULT NULL COMMENT '下半场双',
  `trend_4` tinyint(4) DEFAULT NULL COMMENT '升降状态 0：不变 1：上升 -1：降',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_5004_id`)
) ENGINE=InnoDB AUTO_INCREMENT=372 DEFAULT CHARSET=utf8mb4;

--北单比分赔率表 （张悦玲）
CREATE TABLE `odds_5005` (
  `odds_5005_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '比分赔率',
  `open_mid` varchar(25) DEFAULT NULL,
  `update_nums` int(11) DEFAULT '1' COMMENT '更新数',
  `odds_10` decimal(8,2) DEFAULT NULL COMMENT '比分1:0',
  `trend_10` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_20` decimal(8,2) DEFAULT NULL COMMENT '比分2:0',
  `trend_20` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_21` decimal(8,2) DEFAULT NULL COMMENT '比分2：1',
  `trend_21` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_30` decimal(8,2) DEFAULT NULL COMMENT '比分1:0',
  `trend_30` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_31` decimal(8,2) DEFAULT NULL COMMENT '比分3:1',
  `trend_31` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_32` decimal(8,2) DEFAULT NULL COMMENT '比分3:2',
  `trend_32` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_40` decimal(8,2) DEFAULT NULL COMMENT '比分4:0',
  `trend_40` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_41` decimal(8,2) DEFAULT NULL COMMENT '比分4:1',
  `trend_41` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_42` decimal(8,2) DEFAULT NULL COMMENT '比分4:2',
  `trend_42` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_00` decimal(8,2) DEFAULT NULL COMMENT '比分0:0',
  `trend_00` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_11` decimal(8,2) DEFAULT NULL COMMENT '比分1:1',
  `trend_11` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_22` decimal(8,2) DEFAULT NULL COMMENT '比分2:2',
  `trend_22` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_33` decimal(8,2) DEFAULT NULL COMMENT '比分3:3',
  `trend_33` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_01` decimal(8,2) DEFAULT NULL COMMENT '比分0:1',
  `trend_01` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_02` decimal(8,2) DEFAULT NULL COMMENT '比分0:2',
  `trend_02` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_12` decimal(8,2) DEFAULT NULL COMMENT '比分1:2',
  `trend_12` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_03` decimal(8,2) DEFAULT NULL COMMENT '比分0:3',
  `trend_03` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_13` decimal(8,2) DEFAULT NULL COMMENT '比分1:3',
  `trend_13` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_23` decimal(8,2) DEFAULT NULL COMMENT '比分2:3',
  `trend_23` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_04` decimal(8,2) DEFAULT NULL COMMENT '比分0:4',
  `trend_04` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_14` decimal(8,2) DEFAULT NULL COMMENT '比分1:4',
  `trend_14` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_24` decimal(8,2) DEFAULT NULL COMMENT '比分2:4',
  `trend_24` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_90` decimal(8,2) DEFAULT NULL COMMENT '比分胜其他',
  `trend_90` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_99` decimal(8,2) DEFAULT NULL COMMENT '比分平其他',
  `trend_99` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_09` decimal(8,2) DEFAULT NULL COMMENT '比分负其他',
  `trend_09` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_5005_id`)
) ENGINE=InnoDB AUTO_INCREMENT=372 DEFAULT CHARSET=utf8mb4;

--北单胜负过关赔率表 （张悦玲）
CREATE TABLE `odds_5006` (
  `odds_5006_id` int(11) NOT NULL COMMENT '胜负过关赔率表',
  `open_mid` varchar(25) DEFAULT NULL COMMENT '期内赛程MID',
  `update_nums` int(11) DEFAULT '1' COMMENT '更新数',
  `odds_3` decimal(8,2) DEFAULT NULL COMMENT '胜负过关胜赔率',
  `trend_3` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `odds_0` decimal(8,2) DEFAULT NULL COMMENT '胜负过关负赔率',
  `trend_0` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升 -1：降',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_5006_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;




