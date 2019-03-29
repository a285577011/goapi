/*
 Navicat Premium Data Transfer

 Source Server         : online主服务器164
 Source Server Type    : MySQL
 Source Server Version : 50718
 Source Host           : 27.155.105.164
 Source Database       : gl_lottery_php

 Target Server Type    : MySQL
 Target Server Version : 50718
 File Encoding         : utf-8

 Date: 09/18/2017 09:53:52 AM
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `asian_handicap`
-- ----------------------------
DROP TABLE IF EXISTS `asian_handicap`;
CREATE TABLE `asian_handicap` (
  `asian_handicap_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `company_name` varchar(20) DEFAULT NULL COMMENT '公司名字',
  `country` varchar(20) DEFAULT NULL COMMENT '国家',
  `handicap_type` tinyint(4) DEFAULT NULL COMMENT '1、初盘  2、现盘',
  `handicap_name` varchar(20) DEFAULT NULL COMMENT '初盘、现盘',
  `home_discount` decimal(18,2) DEFAULT NULL COMMENT '主队贴水',
  `let_index` varchar(20) DEFAULT NULL COMMENT '让球指数',
  `visit_discount` decimal(18,2) DEFAULT NULL COMMENT '客队贴水',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`asian_handicap_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19614 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `betting_detail`
-- ----------------------------
DROP TABLE IF EXISTS `betting_detail`;
CREATE TABLE `betting_detail` (
  `betting_detail_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '投注单id',
  `lottery_order_id` int(11) NOT NULL COMMENT '所属订单id',
  `lottery_order_code` varchar(50) NOT NULL COMMENT '所属订单编号',
  `betting_detail_code` varchar(50) NOT NULL COMMENT '投注单号',
  `lottery_id` int(11) NOT NULL COMMENT '彩种id',
  `lottery_name` varchar(25) NOT NULL COMMENT '彩种名称',
  `cust_no` varchar(15) NOT NULL COMMENT '会员id',
  `agent_id` varchar(25) NOT NULL COMMENT '代理商id',
  `periods` varchar(25) DEFAULT NULL COMMENT '当前期数',
  `bet_val` varchar(255) NOT NULL COMMENT '投注内容',
  `odds` varchar(255) DEFAULT NULL COMMENT '赔率',
  `play_name` varchar(50) DEFAULT NULL,
  `play_code` varchar(20) DEFAULT NULL COMMENT '玩法类型',
  `schedule_nums` int(11) DEFAULT '1' COMMENT '比赛场数',
  `deal_nums` int(11) DEFAULT '0' COMMENT '已处理几次',
  `deal_schedule` varchar(200) DEFAULT 'gl' COMMENT '已处理赛程',
  `bet_double` int(11) NOT NULL DEFAULT '1' COMMENT '加倍',
  `is_bet_add` tinyint(2) NOT NULL DEFAULT '0' COMMENT '追加投注（大乐透 1元）',
  `win_amount` decimal(18,2) DEFAULT '0.00' COMMENT '中奖金额',
  `status` tinyint(4) NOT NULL DEFAULT '2' COMMENT '状态（1未支付 2处理中 3待开奖、4中奖、5未中奖、6出票失败',
  `deal_status` tinyint(4) NOT NULL DEFAULT '0',
  `win_level` int(4) DEFAULT NULL COMMENT '奖级',
  `one_money` decimal(18,2) NOT NULL COMMENT '单注金额',
  `back_order` varchar(100) DEFAULT NULL,
  `bet_money` decimal(18,2) NOT NULL COMMENT '投注金额',
  `opt_id` varchar(25) DEFAULT NULL COMMENT '操作人',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`betting_detail_id`),
  KEY `lottery_order_id` (`lottery_order_id`),
  KEY `lottery_order_code` (`lottery_order_code`),
  KEY `betting_detail_code` (`betting_detail_code`),
  KEY `lottery_id` (`lottery_id`),
  KEY `user_id` (`cust_no`),
  KEY `agent_id` (`agent_id`),
  KEY `betting_detail_id` (`betting_detail_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COMMENT='投注单表';

-- ----------------------------
--  Table structure for `country`
-- ----------------------------
DROP TABLE IF EXISTS `country`;
CREATE TABLE `country` (
  `country_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_name` varchar(150) NOT NULL,
  `country_code` varchar(50) NOT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`country_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `direct_trend_chart`
-- ----------------------------
DROP TABLE IF EXISTS `direct_trend_chart`;
CREATE TABLE `direct_trend_chart` (
  `direct_trend_chart_id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_name` varchar(25) NOT NULL,
  `lottery_code` varchar(25) NOT NULL,
  `periods` varchar(50) NOT NULL,
  `open_code` varchar(50) NOT NULL,
  `red_omission` varchar(100) NOT NULL,
  `blue_omission` varchar(100) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `opt_id` int(11) DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`direct_trend_chart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=709 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `diy_follow`
-- ----------------------------
DROP TABLE IF EXISTS `diy_follow`;
CREATE TABLE `diy_follow` (
  `diy_follow_id` int(11) NOT NULL AUTO_INCREMENT,
  `expert_no` varchar(15) DEFAULT NULL COMMENT '专家no',
  `cust_no` varchar(15) DEFAULT NULL COMMENT '会员no',
  `lottery_codes` varchar(50) DEFAULT NULL COMMENT '彩种类型多个以逗号隔开',
  `follow_type` tinyint(4) NOT NULL COMMENT '跟单方式  1、按固定金额   2、按百分比',
  `follow_num` int(11) NOT NULL COMMENT '认购方案个数',
  `buy_num` int(11) DEFAULT '0' COMMENT '已认购方案个数',
  `bet_money` decimal(18,2) DEFAULT NULL COMMENT '每个方案认购金额',
  `follow_percent` float(4,1) DEFAULT NULL COMMENT '跟单百分比',
  `max_bet_money` int(11) DEFAULT NULL COMMENT '最大跟单金额',
  `stop_money` int(11) DEFAULT '0' COMMENT '帐户余额停止认购',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL COMMENT '开户时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`diy_follow_id`),
  KEY `diy_follow_expert_no` (`expert_no`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `europe_odds`
-- ----------------------------
DROP TABLE IF EXISTS `europe_odds`;
CREATE TABLE `europe_odds` (
  `europe_odds_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `company_name` varchar(20) DEFAULT NULL COMMENT '公司名字',
  `country` varchar(20) DEFAULT NULL COMMENT '国家',
  `handicap_type` tinyint(4) DEFAULT NULL COMMENT '1、初盘  2、现盘',
  `handicap_name` varchar(20) DEFAULT NULL COMMENT '初盘、现盘',
  `odds_3` decimal(18,4) DEFAULT NULL COMMENT '主胜赔率',
  `odds_1` decimal(18,4) DEFAULT NULL COMMENT '平赔率',
  `odds_0` decimal(18,4) DEFAULT NULL COMMENT '客胜赔率',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`europe_odds_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `exchange_record`
-- ----------------------------
DROP TABLE IF EXISTS `exchange_record`;
CREATE TABLE `exchange_record` (
  `exchange_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '会员ID',
  `user_name` varchar(50) NOT NULL COMMENT '会员名称',
  `cust_no` varchar(50) DEFAULT NULL COMMENT '会员编码',
  `exch_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:系统兑换;2:自助平台兑换；3微信平台兑换',
  `exch_code` varchar(50) NOT NULL COMMENT '兑换流水单号',
  `exch_intergal` int(11) NOT NULL COMMENT '兑换积分',
  `exch_nums` int(11) NOT NULL COMMENT '兑换数量',
  `less_intergal` int(11) DEFAULT NULL COMMENT '扣除积分',
  `exch_time` datetime DEFAULT NULL COMMENT '兑换时间',
  `agent_code` varchar(100) NOT NULL,
  `agent_name` varchar(100) DEFAULT NULL,
  `opt_name` varchar(50) NOT NULL COMMENT '操作人',
  `review_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否已审核；1：未审核；2：审核通过；3：不通过',
  `review_name` varchar(50) DEFAULT NULL COMMENT '审核员',
  `review_remark` varchar(255) DEFAULT NULL COMMENT '审核说明',
  `opt_id` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL COMMENT '申请时间',
  `modify_time` datetime DEFAULT NULL COMMENT '审核时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`exchange_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `exgift_record`
-- ----------------------------
DROP TABLE IF EXISTS `exgift_record`;
CREATE TABLE `exgift_record` (
  `exgift_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `exchange_id` int(11) NOT NULL,
  `exch_code` varchar(50) NOT NULL,
  `gift_nums` int(11) NOT NULL,
  `exch_int` int(11) NOT NULL COMMENT '所需积分',
  `all_int` int(11) NOT NULL COMMENT '总需积分',
  `gift_code` varchar(50) NOT NULL,
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`exgift_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `football_fourteen`
-- ----------------------------
DROP TABLE IF EXISTS `football_fourteen`;
CREATE TABLE `football_fourteen` (
  `football_fourteen_id` int(11) NOT NULL AUTO_INCREMENT,
  `periods` varchar(15) DEFAULT NULL COMMENT '期数',
  `schedule_mids` varchar(200) DEFAULT NULL COMMENT '赛程mids用逗号隔开',
  `beginsale_time` datetime DEFAULT NULL COMMENT '开售时间',
  `endsale_time` datetime DEFAULT NULL COMMENT '停售时间',
  `schedule_results` varchar(200) DEFAULT NULL,
  `first_prize` decimal(18,2) DEFAULT NULL,
  `second_prize` decimal(18,2) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`football_fourteen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `football_nine`
-- ----------------------------
DROP TABLE IF EXISTS `football_nine`;
CREATE TABLE `football_nine` (
  `football_nine_id` int(11) NOT NULL AUTO_INCREMENT,
  `periods` varchar(15) DEFAULT NULL COMMENT '期数',
  `schedule_mids` varchar(100) DEFAULT NULL COMMENT '赛程mids用逗号隔开',
  `beginsale_time` datetime DEFAULT NULL COMMENT '开售时间',
  `endsale_time` datetime DEFAULT NULL COMMENT '停售时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`football_nine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `gift`
-- ----------------------------
DROP TABLE IF EXISTS `gift`;
CREATE TABLE `gift` (
  `gift_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '礼品ID',
  `gift_code` varchar(25) NOT NULL COMMENT '礼品编码',
  `gift_name` varchar(50) NOT NULL COMMENT '礼品名称',
  `gift_category` int(11) NOT NULL COMMENT '礼品分类',
  `gift_integral` decimal(18,0) NOT NULL COMMENT '所需积分',
  `gift_picture` varchar(100) DEFAULT NULL COMMENT '礼品图片URL',
  `in_stock` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  `exchange_nums` int(11) NOT NULL DEFAULT '0' COMMENT '兑换数量',
  `agent_code` varchar(50) NOT NULL,
  `agent_name` varchar(50) NOT NULL,
  `gift_remark` varchar(255) DEFAULT NULL,
  `opt_id` int(11) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `gift_category`
-- ----------------------------
DROP TABLE IF EXISTS `gift_category`;
CREATE TABLE `gift_category` (
  `gift_category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '类别ID',
  `category_name` varchar(50) NOT NULL COMMENT '类别名称',
  `category_remark` varchar(255) DEFAULT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `opt_id` int(11) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gift_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `group_trend_chart`
-- ----------------------------
DROP TABLE IF EXISTS `group_trend_chart`;
CREATE TABLE `group_trend_chart` (
  `group_trend_chart_id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_name` varchar(25) NOT NULL,
  `lottery_code` varchar(25) NOT NULL,
  `periods` varchar(25) NOT NULL,
  `open_code` varchar(50) NOT NULL,
  `hundred_omission` varchar(50) NOT NULL,
  `ten_omission` varchar(50) NOT NULL,
  `digits_omission` varchar(50) NOT NULL,
  `group_omission` varchar(100) NOT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `opt_id` int(11) DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_trend_chart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=565 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `history_count`
-- ----------------------------
DROP TABLE IF EXISTS `history_count`;
CREATE TABLE `history_count` (
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
) ENGINE=InnoDB AUTO_INCREMENT=1044 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `ice_record`
-- ----------------------------
DROP TABLE IF EXISTS `ice_record`;
CREATE TABLE `ice_record` (
  `ice_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `cust_no` varchar(20) NOT NULL COMMENT '用户no',
  `cust_type` tinyint(4) NOT NULL COMMENT '1、会员   2、门店',
  `order_code` varchar(50) NOT NULL COMMENT '订单号',
  `money` decimal(18,2) NOT NULL COMMENT '金额',
  `ice_balance` decimal(18,2) NOT NULL COMMENT '冻结总金额',
  `body` varchar(100) DEFAULT NULL COMMENT '描述',
  `type` tinyint(4) NOT NULL COMMENT '1、收入    2、支出',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ice_record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `integral_count`
-- ----------------------------
DROP TABLE IF EXISTS `integral_count`;
CREATE TABLE `integral_count` (
  `integral_count_id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) DEFAULT NULL COMMENT '球队',
  `team_name` varchar(50) DEFAULT NULL COMMENT '球队',
  `integral_type` int(11) DEFAULT NULL COMMENT '1、总成绩   2、主场   3、客场',
  `field_num` int(11) DEFAULT NULL COMMENT '场次',
  `num3` int(11) DEFAULT NULL COMMENT '胜场次数',
  `num1` int(11) DEFAULT NULL COMMENT '平场次数',
  `num0` int(11) DEFAULT NULL COMMENT '负场次数',
  `ball_num` int(11) DEFAULT NULL COMMENT '进球数',
  `s_num` int(11) DEFAULT NULL COMMENT '失',
  `j_num` int(11) DEFAULT NULL COMMENT '净',
  `scores` int(11) DEFAULT NULL COMMENT '积分',
  `rank` int(11) unsigned zerofill DEFAULT NULL COMMENT '名次',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`integral_count_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `intergal_record`
-- ----------------------------
DROP TABLE IF EXISTS `intergal_record`;
CREATE TABLE `intergal_record` (
  `intergal_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `cust_no` varchar(100) NOT NULL,
  `intergal_source` varchar(100) NOT NULL,
  `intergal_value` int(11) NOT NULL,
  `opt_id` int(11) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`intergal_record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `league`
-- ----------------------------
DROP TABLE IF EXISTS `league`;
CREATE TABLE `league` (
  `league_id` int(11) NOT NULL AUTO_INCREMENT,
  `league_code` varchar(25) NOT NULL COMMENT '编码',
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
  `league_mid` int(11) DEFAULT NULL COMMENT '抓取的联赛唯一id',
  PRIMARY KEY (`league_id`),
  KEY `league_code` (`league_code`)
) ENGINE=InnoDB AUTO_INCREMENT=224 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `league_team`
-- ----------------------------
DROP TABLE IF EXISTS `league_team`;
CREATE TABLE `league_team` (
  `league_team_id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` varchar(25) NOT NULL COMMENT '球队编码',
  `league_id` varchar(25) NOT NULL COMMENT '联赛编码',
  PRIMARY KEY (`league_team_id`),
  KEY `team_id` (`team_id`),
  KEY `league_id` (`league_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1672 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `logtest`
-- ----------------------------
DROP TABLE IF EXISTS `logtest`;
CREATE TABLE `logtest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text,
  `type` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1826 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `lottery`
-- ----------------------------
DROP TABLE IF EXISTS `lottery`;
CREATE TABLE `lottery` (
  `lottery_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `lottery_code` char(10) NOT NULL COMMENT '编码',
  `lottery_name` varchar(20) NOT NULL COMMENT '彩种名称',
  `description` varchar(200) DEFAULT NULL COMMENT '描述',
  `lottery_category_id` int(11) NOT NULL DEFAULT '9' COMMENT '所属类别',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 0：停用 1：启用',
  `sale_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否开售 0：停售 1：开售',
  `lottery_pic` varchar(100) DEFAULT NULL COMMENT '图片',
  `lottery_sort` int(11) DEFAULT '99' COMMENT '彩种排序',
  `result_status` tinyint(4) DEFAULT '1' COMMENT '开奖结果，是否显示 1：显示 0：不显示',
  `opt_id` int(11) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lottery_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COMMENT='彩种表';

-- ----------------------------
--  Table structure for `lottery_additional`
-- ----------------------------
DROP TABLE IF EXISTS `lottery_additional`;
CREATE TABLE `lottery_additional` (
  `lottery_additional_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '追期表id',
  `lottery_name` varchar(50) NOT NULL,
  `lottery_id` int(11) NOT NULL COMMENT '彩种id',
  `play_name` varchar(255) NOT NULL,
  `play_code` varchar(255) NOT NULL,
  `lottery_additional_code` varchar(25) NOT NULL COMMENT '追号编号',
  `chased_num` int(11) NOT NULL DEFAULT '1' COMMENT '已追',
  `periods_total` int(11) NOT NULL DEFAULT '1' COMMENT '追号总期数',
  `periods` varchar(25) DEFAULT NULL COMMENT '当前期数',
  `cust_no` varchar(15) NOT NULL COMMENT '用户id',
  `user_id` int(11) DEFAULT NULL,
  `cust_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '用户类型：1、会员   2、门店',
  `store_id` int(11) DEFAULT NULL,
  `store_no` varchar(15) DEFAULT NULL COMMENT '门店no',
  `agent_id` varchar(50) NOT NULL COMMENT '代理商id',
  `user_plan_id` int(11) DEFAULT NULL COMMENT '用户计划id',
  `programme_code` varchar(100) DEFAULT NULL COMMENT '专家方案编号',
  `bet_val` varchar(256) NOT NULL COMMENT '投注内容',
  `bet_double` int(4) NOT NULL DEFAULT '1' COMMENT '加倍',
  `is_bet_add` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否追加',
  `bet_money` decimal(18,2) NOT NULL COMMENT '投注金额',
  `total_money` decimal(18,2) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '1' COMMENT '总注数',
  `opt_id` varchar(25) DEFAULT NULL COMMENT '操作人',
  `is_random` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否随机',
  `is_limit` int(2) NOT NULL DEFAULT '0',
  `win_limit` decimal(18,2) DEFAULT NULL,
  `pay_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '支付状态(0：未支付 ，1：支付)',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0，停止，1未追，2正在追，3已结束',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lottery_additional_id`),
  KEY `lottery_id` (`lottery_id`),
  KEY `play_code` (`play_code`),
  KEY `lottery_additional_code` (`lottery_additional_code`)
) ENGINE=InnoDB AUTO_INCREMENT=1406 DEFAULT CHARSET=utf8mb4 COMMENT='追期表';

-- ----------------------------
--  Table structure for `lottery_category`
-- ----------------------------
DROP TABLE IF EXISTS `lottery_category`;
CREATE TABLE `lottery_category` (
  `lottery_category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `cp_category_name` char(20) NOT NULL COMMENT '分类名称',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lottery_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='彩种分类表';

-- ----------------------------
--  Table structure for `lottery_levels`
-- ----------------------------
DROP TABLE IF EXISTS `lottery_levels`;
CREATE TABLE `lottery_levels` (
  `levels_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `lottery_code` varchar(50) NOT NULL COMMENT '彩种编号',
  `lottery_name` varchar(255) NOT NULL DEFAULT '' COMMENT '彩种名称',
  `lottery_category` varchar(255) NOT NULL,
  `levels_name` varchar(50) NOT NULL,
  `levels_code` int(11) NOT NULL,
  `levels_sort` int(4) NOT NULL DEFAULT '0' COMMENT '0:否 1:是',
  `levels_red` int(4) NOT NULL DEFAULT '0',
  `levels_blue` int(4) NOT NULL DEFAULT '0',
  `levels_remark` varchar(255) DEFAULT NULL,
  `levels_bonus_category` varchar(50) NOT NULL,
  `levels_bonus` decimal(18,2) DEFAULT NULL,
  `levels_bonus_remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`levels_id`),
  KEY `lottery_code` (`lottery_code`),
  KEY `levels_code` (`levels_code`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `lottery_order`
-- ----------------------------
DROP TABLE IF EXISTS `lottery_order`;
CREATE TABLE `lottery_order` (
  `lottery_order_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单id',
  `lottery_additional_id` int(11) NOT NULL DEFAULT '0' COMMENT '追期表id 合买ID 计划ID',
  `lottery_name` varchar(50) DEFAULT NULL,
  `lottery_order_code` varchar(50) NOT NULL DEFAULT '' COMMENT '订单编号',
  `out_order_code` varchar(50) DEFAULT NULL COMMENT '出单交易号',
  `play_name` varchar(255) NOT NULL DEFAULT '',
  `play_code` varchar(256) NOT NULL DEFAULT '',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `lottery_id` int(11) NOT NULL COMMENT '彩种',
  `lottery_type` tinyint(4) DEFAULT NULL COMMENT '彩种类型 1：数字彩 2：竞彩 3：其他',
  `periods` varchar(25) DEFAULT NULL COMMENT '期数',
  `cust_no` varchar(15) NOT NULL DEFAULT '' COMMENT '用户',
  `cust_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '用户类型：1、会员   2、门店',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID 门店ID 专家ID',
  `store_id` int(11) DEFAULT NULL COMMENT '门店Id',
  `store_no` varchar(15) DEFAULT NULL COMMENT '门店的custNo',
  `agent_id` varchar(50) DEFAULT NULL COMMENT '代理商',
  `user_plan_id` int(11) DEFAULT NULL COMMENT '用户计划id',
  `end_time` datetime DEFAULT NULL COMMENT '截止时间',
  `programme_code` varchar(100) DEFAULT NULL COMMENT '专家方案编号',
  `bet_val` varchar(256) NOT NULL DEFAULT '' COMMENT '投注内容',
  `additional_periods` int(4) NOT NULL DEFAULT '1' COMMENT '追期',
  `chased_num` int(11) NOT NULL DEFAULT '1' COMMENT '追号第几期',
  `bet_double` int(11) NOT NULL DEFAULT '1' COMMENT '倍数',
  `is_bet_add` tinyint(2) NOT NULL DEFAULT '0' COMMENT '追加投注（大乐透 1元）',
  `bet_money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '投注金额',
  `odds` varchar(500) DEFAULT NULL COMMENT '赔率',
  `count` int(11) NOT NULL DEFAULT '1' COMMENT '总注数',
  `is_win` tinyint(2) DEFAULT NULL COMMENT '是否中奖',
  `win_amount` decimal(18,2) DEFAULT '0.00' COMMENT '中奖金额',
  `deal_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '兑奖处理 0:未处理 ；1：已兑奖 ；2：派奖失败； 3：派奖成功   4:退款失败   5：退款成功',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态（1未支付 2处理中 3待开奖、4中奖、5未中奖、6出票失败、9过点撤销、10拒绝出票',
  `record_type` tinyint(4) DEFAULT '1' COMMENT '1、自购 2、合买',
  `source` tinyint(4) NOT NULL DEFAULT '1' COMMENT '来源（1自购、2追号、3赠送、4合买 6、计划购买）',
  `is_generate_child` tinyint(2) NOT NULL DEFAULT '0' COMMENT '是否生成子单',
  `suborder_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0:未生成子单，1：已生成子单，2：生成出错',
  `opt_id` varchar(25) DEFAULT NULL COMMENT '操作人',
  `remark` varchar(100) DEFAULT NULL COMMENT '备注',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `award_time` datetime DEFAULT NULL COMMENT '派奖时间',
  PRIMARY KEY (`lottery_order_id`),
  KEY `lottery_additional_id` (`lottery_additional_id`),
  KEY `lottery_order_code` (`lottery_order_code`),
  KEY `out_order_code` (`out_order_code`),
  KEY `lottery_id` (`lottery_id`),
  KEY `periods` (`periods`),
  KEY `cust_no` (`cust_no`),
  KEY `user_id` (`user_id`),
  KEY `store_id` (`store_id`),
  KEY `store_no` (`store_no`),
  KEY `user_plan_id` (`user_plan_id`),
  KEY `programme_code` (`programme_code`),
  KEY `source` (`source`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=175 DEFAULT CHARSET=utf8mb4 COMMENT='订单表';

-- ----------------------------
--  Table structure for `lottery_play`
-- ----------------------------
DROP TABLE IF EXISTS `lottery_play`;
CREATE TABLE `lottery_play` (
  `lottery_play_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `lottery_play_code` varchar(10) NOT NULL COMMENT '玩法编码',
  `lottery_play_name` varchar(20) NOT NULL COMMENT '玩法名称',
  `lottery_code` varchar(10) NOT NULL COMMENT '所属彩种编号',
  `lottery_name` varchar(20) NOT NULL COMMENT '彩种名称',
  `category_name` varchar(20) NOT NULL COMMENT '所属类别',
  `example` varchar(255) DEFAULT NULL COMMENT '示例',
  `number_count` varchar(50) DEFAULT NULL COMMENT '号码个数',
  `format_remark` varchar(200) DEFAULT NULL COMMENT '格式说明',
  `opt_id` varchar(25) DEFAULT NULL COMMENT '操作人',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lottery_play_id`),
  KEY `lottery_code` (`lottery_code`)
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COMMENT='彩票玩法表';

-- ----------------------------
--  Table structure for `lottery_record`
-- ----------------------------
DROP TABLE IF EXISTS `lottery_record`;
CREATE TABLE `lottery_record` (
  `lottery_record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `lottery_code` varchar(50) NOT NULL COMMENT '彩种编号',
  `lottery_name` varchar(20) NOT NULL COMMENT '彩种名称',
  `periods` varchar(15) NOT NULL COMMENT '期数',
  `lottery_time` datetime DEFAULT NULL COMMENT '开奖时间',
  `limit_time` datetime DEFAULT NULL COMMENT '截止时间',
  `week` varchar(5) DEFAULT NULL COMMENT '礼拜几',
  `lottery_numbers` varchar(25) DEFAULT NULL COMMENT '开奖号码',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态（0:下一期1:当期2:往期）',
  `win_status` tinyint(4) DEFAULT '0' COMMENT '0:未兑奖；1:兑奖中；2:详情已兑奖; 3:订单已兑奖；',
  `total_sales` decimal(18,2) DEFAULT NULL COMMENT '总销售额',
  `pool` decimal(18,2) DEFAULT NULL COMMENT '奖池奖金',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `parity_ratio` varchar(50) DEFAULT NULL COMMENT '奇偶比',
  `size_ratio` varchar(50) DEFAULT NULL COMMENT '大小比',
  PRIMARY KEY (`lottery_record_id`),
  KEY `lottery_id` (`lottery_code`),
  KEY `periods` (`periods`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2610 DEFAULT CHARSET=utf8mb4 COMMENT='开奖记录表';

-- ----------------------------
--  Table structure for `lottery_time`
-- ----------------------------
DROP TABLE IF EXISTS `lottery_time`;
CREATE TABLE `lottery_time` (
  `lottery_time_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '开奖时间id',
  `lottery_code` varchar(10) NOT NULL COMMENT '彩种编码',
  `lottery_name` varchar(25) NOT NULL COMMENT '彩种名称',
  `category_name` varchar(25) NOT NULL COMMENT '彩种所属分类',
  `rate` varchar(100) DEFAULT NULL COMMENT '频率',
  `changci` tinyint(4) DEFAULT NULL COMMENT '场次',
  `week` varchar(25) DEFAULT NULL COMMENT '周几开奖',
  `start_time` time NOT NULL COMMENT '开始时间',
  `stop_time` time NOT NULL COMMENT '结束时间',
  `limit_time` time DEFAULT NULL COMMENT '投注截止时间',
  `remark` varchar(256) DEFAULT NULL COMMENT '备注说明',
  `opt_id` int(11) DEFAULT NULL COMMENT '最后操作人',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lottery_time_id`),
  KEY `lottery_code` (`lottery_code`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COMMENT='开奖时间表';

-- ----------------------------
--  Table structure for `multidigit_trend_chart`
-- ----------------------------
DROP TABLE IF EXISTS `multidigit_trend_chart`;
CREATE TABLE `multidigit_trend_chart` (
  `multidigit_trend_chart_id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_name` varchar(25) NOT NULL,
  `lottery_code` varchar(25) NOT NULL,
  `periods` varchar(25) NOT NULL,
  `open_code` varchar(50) NOT NULL,
  `digits_omission` varchar(50) NOT NULL,
  `ten_omission` varchar(50) NOT NULL,
  `hundred_omission` varchar(50) NOT NULL,
  `thousand_omission` varchar(50) NOT NULL,
  `ten_thousand_omission` varchar(50) NOT NULL,
  `hundred_thousand_omission` varchar(50) DEFAULT NULL,
  `million_omission` varchar(50) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `opt_id` int(11) DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`multidigit_trend_chart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=560 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `odds_3006`
-- ----------------------------
DROP TABLE IF EXISTS `odds_3006`;
CREATE TABLE `odds_3006` (
  `odds_let_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '让球胜平负ID',
  `schedule_mid` varchar(25) NOT NULL DEFAULT '' COMMENT '赛程ID',
  `updates_nums` int(11) DEFAULT NULL COMMENT '更新次数',
  `let_ball_nums` varchar(10) NOT NULL DEFAULT '0' COMMENT '让球数',
  `let_wins` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜赔率',
  `let_wins_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '比上次升降（-1：降；0：不动；1：升）',
  `let_level` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '平赔率',
  `let_level_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '比上次升降（-1：降；0：不动；1：升）',
  `let_negative` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '负赔率',
  `let_negative_trend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '比上次升降（-1：降；0：不动；1：升）',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '添加时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `schedule_id` int(11) NOT NULL COMMENT '联赛表ID',
  `opt_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`odds_let_id`),
  KEY `schedule_mid` (`schedule_mid`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4877 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `odds_3007`
-- ----------------------------
DROP TABLE IF EXISTS `odds_3007`;
CREATE TABLE `odds_3007` (
  `odds_score_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '比分ID',
  `schedule_id` int(11) NOT NULL,
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛程ID',
  `updates_nums` int(11) DEFAULT NULL COMMENT '更新次数',
  `score_wins_10` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜1:0',
  `score_wins_20` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜2:0',
  `score_wins_21` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜2:1',
  `score_wins_30` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜3:0',
  `score_wins_31` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜3:1',
  `score_wins_32` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜3:2',
  `score_wins_40` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜4:0',
  `score_wins_41` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜4:1',
  `score_wins_42` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜4:2',
  `score_wins_50` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜5:0',
  `score_wins_51` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜5:1',
  `score_wins_52` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜5:2',
  `score_wins_90` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '胜其他',
  `score_level_00` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '平0：0',
  `score_level_11` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '平1：1',
  `score_level_22` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '平2:2',
  `score_level_33` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '平3:3',
  `score_level_99` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '平其他',
  `score_negative_01` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '负0:1',
  `score_negative_02` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_12` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_03` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_13` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_23` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_04` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_14` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_24` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_05` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_15` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_25` decimal(18,2) NOT NULL DEFAULT '0.00',
  `score_negative_09` decimal(18,2) NOT NULL DEFAULT '0.00',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `opt_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`odds_score_id`),
  KEY `schedule_mid` (`schedule_mid`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6645 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `odds_3008`
-- ----------------------------
DROP TABLE IF EXISTS `odds_3008`;
CREATE TABLE `odds_3008` (
  `odds_3008_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛程',
  `updates_nums` tinyint(4) NOT NULL COMMENT '更新次数',
  `total_gold_0` decimal(4,2) NOT NULL COMMENT '进0球',
  `total_gold_0_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `total_gold_1` decimal(4,2) NOT NULL COMMENT '进1球',
  `total_gold_1_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `total_gold_2` decimal(4,2) NOT NULL COMMENT '进2球',
  `total_gold_2_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `total_gold_3` decimal(4,2) NOT NULL COMMENT '进3球',
  `total_gold_3_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `total_gold_4` decimal(4,2) NOT NULL COMMENT '进4球',
  `total_gold_4_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `total_gold_5` decimal(4,2) NOT NULL COMMENT '进5球',
  `total_gold_5_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `total_gold_6` decimal(4,2) NOT NULL COMMENT '进6球',
  `total_gold_6_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `total_gold_7` decimal(4,2) NOT NULL COMMENT '进7球',
  `total_gold_7_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `opt_id` int(11) DEFAULT NULL COMMENT '最后操作人',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_3008_id`),
  KEY `schedule_mid` (`schedule_mid`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2247 DEFAULT CHARSET=utf8mb4 COMMENT='赔率--总进球过关';

-- ----------------------------
--  Table structure for `odds_3009`
-- ----------------------------
DROP TABLE IF EXISTS `odds_3009`;
CREATE TABLE `odds_3009` (
  `odds_3009_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛程id',
  `updates_nums` tinyint(4) NOT NULL COMMENT '更新期数',
  `bqc_33` decimal(18,2) NOT NULL COMMENT '胜胜',
  `bqc_33_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `bqc_31` decimal(18,2) NOT NULL COMMENT '胜平',
  `bqc_31_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `bqc_30` decimal(18,2) NOT NULL COMMENT '胜负',
  `bqc_30_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `bqc_13` decimal(18,2) NOT NULL COMMENT '平胜',
  `bqc_13_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `bqc_11` decimal(18,2) NOT NULL COMMENT '平平',
  `bqc_11_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `bqc_10` decimal(18,2) NOT NULL COMMENT '平负',
  `bqc_10_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `bqc_03` decimal(18,2) NOT NULL COMMENT '负胜',
  `bqc_03_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `bqc_01` decimal(18,2) NOT NULL COMMENT '负平',
  `bqc_01_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `bqc_00` decimal(18,2) NOT NULL COMMENT '负负',
  `bqc_00_trend` tinyint(4) NOT NULL COMMENT '趋势',
  `opt_id` int(11) DEFAULT NULL COMMENT '最后操作人',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`odds_3009_id`),
  KEY `schedule_mid` (`schedule_mid`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6164 DEFAULT CHARSET=utf8mb4 COMMENT='赔率--半全场胜平负过关';

-- ----------------------------
--  Table structure for `odds_3010`
-- ----------------------------
DROP TABLE IF EXISTS `odds_3010`;
CREATE TABLE `odds_3010` (
  `odds_outcome_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `schedule_mid` varchar(25) NOT NULL,
  `updates_nums` int(11) DEFAULT NULL,
  `outcome_wins` decimal(18,2) NOT NULL DEFAULT '0.00',
  `outcome_wins_trend` tinyint(4) NOT NULL DEFAULT '0',
  `outcome_level` decimal(18,2) NOT NULL DEFAULT '0.00',
  `outcome_level_trend` tinyint(4) NOT NULL DEFAULT '0',
  `outcome_negative` decimal(18,2) NOT NULL DEFAULT '0.00',
  `outcome_negative_trend` tinyint(4) NOT NULL DEFAULT '0',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `opt_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`odds_outcome_id`),
  KEY `schedule_mid` (`schedule_mid`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4773 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `pay_record`
-- ----------------------------
DROP TABLE IF EXISTS `pay_record`;
CREATE TABLE `pay_record` (
  `pay_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(50) DEFAULT NULL COMMENT '内部订单编号',
  `cust_no` varchar(50) DEFAULT NULL,
  `cust_type` int(11) DEFAULT NULL COMMENT '1、会员   2、门店',
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(50) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `agent_code` varchar(50) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL COMMENT '1、会员  2、门店',
  `agent_name` varchar(50) DEFAULT NULL,
  `pay_no` varchar(32) DEFAULT NULL COMMENT '我们生成的支付流水号',
  `outer_no` varchar(50) DEFAULT NULL COMMENT '第三方生成交易单号',
  `refund_no` varchar(50) DEFAULT NULL COMMENT '退款请求号',
  `pay_name` varchar(200) DEFAULT NULL,
  `way_name` varchar(200) DEFAULT NULL COMMENT '交易方式名称',
  `way_type` varchar(20) DEFAULT NULL COMMENT '交易方式',
  `pay_way` int(11) DEFAULT NULL COMMENT '1 支付宝  2微信支付 3余额',
  `pay_money` decimal(18,2) DEFAULT '0.00' COMMENT '实际支付金额',
  `pay_pre_money` decimal(18,2) DEFAULT NULL COMMENT '预支付金额',
  `balance` decimal(18,2) DEFAULT NULL COMMENT '余额',
  `pay_type_name` varchar(50) DEFAULT NULL COMMENT '交易类型名称（购彩、转账 、充值 、提现、合买、退款，提现费用）',
  `pay_type` int(11) DEFAULT '1' COMMENT '交易类型（1、购彩 2、转账 3、充值 4、提现  5、购彩-合买  6、退款 7、定投计划-认购  8、定投计划-收款 9、门店出票 10、提现手续费 11、奖金发放 12、定投计划-结算收款  13、定投计划-结算付款 14、合买-提成 15、奖金）',
  `body` varchar(200) DEFAULT NULL,
  `status` int(11) DEFAULT NULL COMMENT '0 未支付  1已支付  2支付失败 3退款成功',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pay_record_id`),
  KEY `order_code` (`order_code`),
  KEY `cust_no` (`cust_no`),
  KEY `user_id` (`user_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=314 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `pay_setting`
-- ----------------------------
DROP TABLE IF EXISTS `pay_setting`;
CREATE TABLE `pay_setting` (
  `pay_setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `ali_app_id` varchar(50) DEFAULT NULL COMMENT '支付宝appid',
  `ali_merchant_private_key` text COMMENT '支付宝私钥',
  `ali_charset` varchar(20) DEFAULT NULL COMMENT '支付宝编码',
  `ali_sign_type` varchar(20) DEFAULT NULL COMMENT '支付宝签名方式',
  `ali_gatewayUrl` varchar(120) DEFAULT NULL COMMENT '支付宝网关',
  `ali_alipay_public_key` text COMMENT '支付宝公钥',
  `ali_switch` tinyint(4) NOT NULL DEFAULT '0' COMMENT '支付宝开关',
  `wx_switch` tinyint(4) NOT NULL DEFAULT '0' COMMENT '微信开关',
  `wx_APPID` varchar(100) DEFAULT NULL COMMENT '微信appid',
  `wx_MCHID` varchar(100) DEFAULT NULL COMMENT '微信商户号',
  `wx_KEY` varchar(100) DEFAULT NULL COMMENT '微信key',
  `wx_APPSECRET` varchar(100) DEFAULT NULL COMMENT '微信公众帐号secert',
  `wx_SSLCERT` text COMMENT '微信证书cert',
  `wx_SSLKEY` text COMMENT '微信证书key',
  `wxapp_switch` tinyint(4) DEFAULT '0' COMMENT '微信app 支付开关',
  `wxapp_APPID` varchar(100) DEFAULT NULL COMMENT '微信app appid',
  `wxapp_MCHID` varchar(100) DEFAULT NULL COMMENT '微信app商户号',
  `wxapp_KEY` varchar(100) DEFAULT NULL COMMENT '微信app KEY',
  `wxapp_SSLCERT` text COMMENT '微信app证书cert',
  `wxapp_SSLKEY` text COMMENT '微信app证书key',
  `pay_pro_flag` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否生成对应文件',
  PRIMARY KEY (`pay_setting_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `pay_type`
-- ----------------------------
DROP TABLE IF EXISTS `pay_type`;
CREATE TABLE `pay_type` (
  `pay_type_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '支付类型Id',
  `pay_type` int(11) NOT NULL COMMENT '支付方式',
  `pay_type_name` varchar(100) NOT NULL COMMENT '支付类型名',
  `pay_type_code` varchar(100) NOT NULL COMMENT '支付类型code',
  `parent_id` int(11) NOT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否开放 1：未开放 2：开放',
  `remark` varchar(50) DEFAULT NULL,
  `pay_type_sort` int(11) DEFAULT '1' COMMENT '排序',
  `default` tinyint(4) DEFAULT '0' COMMENT '默认支付 0：常态 1：默认',
  `opt_name` varchar(100) DEFAULT NULL COMMENT '操作人',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pay_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `picture`
-- ----------------------------
DROP TABLE IF EXISTS `picture`;
CREATE TABLE `picture` (
  `picture_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '图片Id',
  `picture_type_code` varchar(100) NOT NULL COMMENT '图片类型',
  `picture_type_name` varchar(50) NOT NULL COMMENT '图片类型名',
  `picture_url` varchar(200) NOT NULL COMMENT '图片路径',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`picture_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `plan`
-- ----------------------------
DROP TABLE IF EXISTS `plan`;
CREATE TABLE `plan` (
  `plan_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `plan_code` varchar(45) NOT NULL COMMENT '计划编号',
  `store_id` int(11) NOT NULL COMMENT '彩店id',
  `store_name` varchar(45) DEFAULT NULL COMMENT '彩店名称',
  `store_tel` varchar(20) DEFAULT NULL COMMENT '彩店电话',
  `title` varchar(45) NOT NULL COMMENT '计划标题',
  `settlement_type` tinyint(4) DEFAULT NULL COMMENT '结算类型1、定期 2、不定期',
  `settlement_periods` int(11) DEFAULT NULL COMMENT '周期(天数)',
  `plan_buy_min` decimal(16,2) NOT NULL COMMENT '最小购买金额',
  `incr_money` decimal(16,2) NOT NULL DEFAULT '0.00' COMMENT '递增金额',
  `plan_remark` text COMMENT '购买详情-备注',
  `buy_nums` int(11) DEFAULT '0' COMMENT '已购人数',
  `buy_amount` decimal(16,2) DEFAULT '0.00' COMMENT '已购总额',
  `plan_time` datetime DEFAULT NULL COMMENT '计划开始时间',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '发布状态',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '数据库最后更新时间',
  PRIMARY KEY (`plan_id`),
  KEY `store_id` (`store_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `pre_result`
-- ----------------------------
DROP TABLE IF EXISTS `pre_result`;
CREATE TABLE `pre_result` (
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
  PRIMARY KEY (`pre_result_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1012 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `programme`
-- ----------------------------
DROP TABLE IF EXISTS `programme`;
CREATE TABLE `programme` (
  `programme_id` int(11) NOT NULL AUTO_INCREMENT,
  `programme_code` varchar(50) NOT NULL COMMENT '方案编号',
  `programme_money` int(11) DEFAULT NULL COMMENT '方案手动输入金额',
  `programme_title` varchar(50) DEFAULT NULL COMMENT '方案标题',
  `lottery_order_id` int(11) DEFAULT NULL COMMENT '订单id',
  `lottery_order_code` varchar(50) DEFAULT NULL COMMENT '订单编号',
  `store_id` int(11) DEFAULT NULL COMMENT '出票门店',
  `expert_no` varchar(15) NOT NULL COMMENT '发布方案人的no(专家no)',
  `cust_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '用户类型 ：1、会员   2、门店',
  `bet_val` varchar(255) DEFAULT NULL COMMENT '购彩内容',
  `bet_money` decimal(18,0) DEFAULT NULL COMMENT '购彩金额',
  `lottery_code` varchar(10) DEFAULT NULL COMMENT '彩种code',
  `lottery_name` varchar(50) DEFAULT NULL COMMENT '彩种名',
  `play_code` varchar(255) DEFAULT NULL COMMENT '玩法code',
  `play_name` varchar(255) DEFAULT NULL COMMENT '玩法名',
  `periods` varchar(20) DEFAULT NULL COMMENT '期数',
  `bet_double` int(11) DEFAULT NULL COMMENT '加班',
  `is_bet_add` tinyint(4) DEFAULT NULL COMMENT '是否追加  1、是   0 、否',
  `count` int(11) DEFAULT NULL COMMENT '注数',
  `security` tinyint(4) DEFAULT NULL COMMENT '保密设置  1、完全公开 2、跟单公开 3、截止后公开',
  `royalty_ratio` float(4,1) DEFAULT NULL COMMENT '提成比例',
  `owner_buy_number` int(11) DEFAULT NULL COMMENT '自己认购份额',
  `minimum_guarantee` int(11) DEFAULT NULL COMMENT '保底金额',
  `programme_start_time` datetime DEFAULT NULL COMMENT '下单开始时间',
  `programme_end_time` datetime DEFAULT NULL COMMENT '截止时间',
  `programme_reason` varchar(255) DEFAULT NULL COMMENT '推荐理由',
  `programme_all_number` int(11) NOT NULL COMMENT '方案总份数',
  `programme_buy_number` int(11) NOT NULL DEFAULT '0' COMMENT '方案被购份数',
  `programme_peoples` int(11) NOT NULL DEFAULT '0' COMMENT '跟单人数',
  `programme_speed` int(11) NOT NULL DEFAULT '0' COMMENT '方案进度  （百分比）',
  `programme_last_amount` int(11) NOT NULL COMMENT '剩余金额',
  `win_amount` decimal(18,2) DEFAULT NULL COMMENT '中奖金额',
  `made_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '预购总金额',
  `guarantee_status` tinyint(4) DEFAULT '1' COMMENT '保底操作   1、未操作   2、参加保底   3、未参与保底  ',
  `bet_status` tinyint(4) DEFAULT '1' COMMENT '投注内容添加 1、未添加   2、已添加  3、下单  4、未完成退款    5、退款成功  6、未完成兑奖   7、兑奖成功',
  `status` tinyint(4) DEFAULT '1' COMMENT '方案状态  1、未发布  2、招募中  3、处理中  4、待开奖  5、未中奖  6、中奖    7、未满员撤单  8、方案失败、9过点撤销、10拒绝出票、11未上传方案撤单',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`programme_id`),
  KEY `programme_code` (`programme_code`),
  KEY `store_id` (`store_id`),
  KEY `expert_no` (`expert_no`),
  KEY `lottery_code` (`lottery_code`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `programme_user`
-- ----------------------------
DROP TABLE IF EXISTS `programme_user`;
CREATE TABLE `programme_user` (
  `programme_user_id` int(11) NOT NULL AUTO_INCREMENT,
  `programme_id` int(11) DEFAULT NULL COMMENT '方案id',
  `programme_user_code` varchar(50) DEFAULT NULL COMMENT '方案跟单编号',
  `programme_code` varchar(50) DEFAULT NULL COMMENT '方案code',
  `record_type` tinyint(4) DEFAULT '2' COMMENT '1、自购 2、合买',
  `store_id` int(11) DEFAULT NULL,
  `expert_no` varchar(15) DEFAULT NULL COMMENT '专家no',
  `cust_no` varchar(15) DEFAULT NULL COMMENT '会员no',
  `cust_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '用户类型：1、会员   2、门店',
  `user_name` varchar(50) DEFAULT NULL COMMENT '会员用户名',
  `lottery_code` varchar(10) DEFAULT NULL,
  `lottery_name` varchar(50) DEFAULT NULL COMMENT '彩种名',
  `periods` varchar(100) DEFAULT NULL,
  `bet_money` decimal(18,2) DEFAULT NULL COMMENT '购买金额',
  `buy_number` int(11) DEFAULT NULL COMMENT '购买份数',
  `win_amount` decimal(18,2) DEFAULT NULL COMMENT '中奖金额',
  `buy_type` tinyint(4) DEFAULT NULL COMMENT '合买类型：1：自主认购 2：定制合买',
  `deal_status` tinyint(4) DEFAULT '1' COMMENT '处理状态：1:未兑奖； 2:兑奖完成；3:兑奖失败；4：退款成功  5、退款失败  ',
  `status` tinyint(4) DEFAULT '1' COMMENT '方案状态  1、未发布  2、招募中  3、处理中  4、待开奖  5、未中奖  6、中奖    7、未满员撤单  8、方案失败、9过点撤销、10拒绝出票、11未上传方案撤单',
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`programme_user_id`),
  KEY `programme_id` (`programme_id`),
  KEY `programme_code` (`programme_code`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `queue`
-- ----------------------------
DROP TABLE IF EXISTS `queue`;
CREATE TABLE `queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `job` varchar(50) DEFAULT NULL COMMENT '任务',
  `queue_name` varchar(50) DEFAULT NULL COMMENT '队列名称',
  `args` varchar(100) DEFAULT NULL COMMENT '任务参数',
  `push_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1、未消息推送  2、消息推送完',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1、添加    2、执行    3、执行成功    4、执行失败',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `redeem_code`
-- ----------------------------
DROP TABLE IF EXISTS `redeem_code`;
CREATE TABLE `redeem_code` (
  `redeem_code_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'pk_id',
  `redeem_code` varchar(32) NOT NULL COMMENT '兑换码',
  `value_amount` decimal(18,0) NOT NULL COMMENT '价值金额',
  `out_trade_no` varchar(100) NOT NULL COMMENT '平台唯一编码',
  `platform_source` tinyint(4) NOT NULL DEFAULT '1' COMMENT '来源平台',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '兑换码状态 1、未使用（默认） 2、使用 3、已过期 4、已废除',
  `store__id` int(11) DEFAULT NULL COMMENT '兑换店铺ID',
  `store_cust_no` varchar(100) DEFAULT NULL COMMENT '钱包、门店的cust_no',
  `settle_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '结算状态 1：未结算 2 ： 已结算',
  `redeem_time` datetime DEFAULT NULL COMMENT '兑换时间',
  `settle_date` date DEFAULT NULL COMMENT '结算日期',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`redeem_code_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule`
-- ----------------------------
DROP TABLE IF EXISTS `schedule`;
CREATE TABLE `schedule` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_code` varchar(25) DEFAULT NULL COMMENT '赛程编号',
  `schedule_date` int(11) DEFAULT NULL COMMENT '赛程日期',
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛程唯一id',
  `league_id` int(11) NOT NULL COMMENT '所属联赛',
  `league_code` varchar(11) DEFAULT NULL COMMENT '联赛code',
  `league_name` varchar(50) DEFAULT NULL COMMENT '联赛名称',
  `visit_team_name` varchar(50) NOT NULL,
  `home_team_name` varchar(50) NOT NULL,
  `visit_short_name` varchar(50) NOT NULL,
  `home_short_name` varchar(50) NOT NULL,
  `home_team_id` int(11) NOT NULL COMMENT '主队id',
  `visit_team_id` int(11) NOT NULL COMMENT '客队id',
  `home_team_mid` varchar(11) DEFAULT NULL COMMENT '主队mid',
  `visit_team_mid` varchar(11) DEFAULT NULL COMMENT '客队mid',
  `start_time` datetime NOT NULL COMMENT '比赛开始时间',
  `beginsale_time` datetime DEFAULT NULL COMMENT '开售时间',
  `endsale_time` datetime DEFAULT NULL COMMENT '停售时间',
  `periods` varchar(25) DEFAULT NULL,
  `rq_nums` varchar(25) DEFAULT NULL,
  `schedule_result` varchar(25) DEFAULT NULL COMMENT '开奖结果',
  `url` varchar(50) DEFAULT NULL COMMENT '固定奖金链接',
  `schedule_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '开售状态',
  `schedule_spf` tinyint(4) NOT NULL DEFAULT '1' COMMENT '胜平负',
  `schedule_rqspf` tinyint(4) NOT NULL DEFAULT '1' COMMENT '让球胜平负',
  `schedule_bf` tinyint(4) NOT NULL DEFAULT '2' COMMENT '比分',
  `schedule_zjqs` tinyint(4) NOT NULL DEFAULT '2' COMMENT '总进球数',
  `schedule_bqcspf` tinyint(4) NOT NULL DEFAULT '2' COMMENT '半全场胜平负',
  `high_win_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '高中奖赛事  0：不是 1：是',
  `hot_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '热门赛事 0：不热门 1：热门',
  `is_optional` tinyint(4) DEFAULT '0' COMMENT '任选14/9  0：不是 1：是',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `opt_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改人',
  PRIMARY KEY (`schedule_id`),
  KEY `schedule_mid` (`schedule_mid`)
) ENGINE=InnoDB AUTO_INCREMENT=1234 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule_event`
-- ----------------------------
DROP TABLE IF EXISTS `schedule_event`;
CREATE TABLE `schedule_event` (
  `schedule_event_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `schedule_event_mid` varchar(11) DEFAULT NULL COMMENT '事件mid',
  `team_type` int(11) DEFAULT NULL COMMENT '1、主队   2、客队',
  `team_name` varchar(10) DEFAULT NULL COMMENT '主队 、客队',
  `event_type` int(11) DEFAULT NULL COMMENT '1、进球  2、点球  3、乌龙球   4、两黄一红   5、换人  ',
  `event_type_name` varchar(20) DEFAULT NULL COMMENT '进球  、点球  、乌龙球   、两黄一红   、换人',
  `event_content` varchar(100) DEFAULT NULL COMMENT '事件内容',
  `event_time` varchar(10) DEFAULT NULL COMMENT '事件时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8058 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule_event_copy`
-- ----------------------------
DROP TABLE IF EXISTS `schedule_event_copy`;
CREATE TABLE `schedule_event_copy` (
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
) ENGINE=InnoDB AUTO_INCREMENT=1293 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule_event_copy1`
-- ----------------------------
DROP TABLE IF EXISTS `schedule_event_copy1`;
CREATE TABLE `schedule_event_copy1` (
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
) ENGINE=InnoDB AUTO_INCREMENT=1294 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule_history`
-- ----------------------------
DROP TABLE IF EXISTS `schedule_history`;
CREATE TABLE `schedule_history` (
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
) ENGINE=InnoDB AUTO_INCREMENT=24523 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule_hot_sina`
-- ----------------------------
DROP TABLE IF EXISTS `schedule_hot_sina`;
CREATE TABLE `schedule_hot_sina` (
  `schedule_hot_sina_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL,
  `hot_status` tinyint(4) DEFAULT '0' COMMENT '热门赛事 0：不热门 1：热门',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`schedule_hot_sina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule_remind`
-- ----------------------------
DROP TABLE IF EXISTS `schedule_remind`;
CREATE TABLE `schedule_remind` (
  `schedule_remind_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(11) DEFAULT NULL COMMENT '赛程mid',
  `content` text COMMENT '赛事提点',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_remind_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2557 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule_result`
-- ----------------------------
DROP TABLE IF EXISTS `schedule_result`;
CREATE TABLE `schedule_result` (
  `schedule_result_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL COMMENT '赛程id',
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛事唯一mid',
  `schedule_date` int(11) DEFAULT NULL COMMENT '赛程日期',
  `schedule_result_3010` int(11) DEFAULT NULL COMMENT '胜平负',
  `schedule_result_3006` varchar(25) DEFAULT NULL COMMENT '让球胜平负',
  `schedule_result_3007` varchar(25) DEFAULT NULL COMMENT '猜比分',
  `schedule_result_3008` varchar(11) DEFAULT NULL COMMENT '总进球数',
  `schedule_result_3009` varchar(25) DEFAULT NULL COMMENT '半全场胜平负',
  `schedule_result_sbbf` varchar(25) DEFAULT NULL COMMENT '上半场比分',
  `odds_3006` decimal(10,2) DEFAULT NULL,
  `odds_3007` decimal(10,2) DEFAULT NULL,
  `odds_3008` decimal(10,2) DEFAULT NULL,
  `odds_3009` decimal(10,2) DEFAULT NULL,
  `odds_3010` decimal(10,2) DEFAULT NULL,
  `opt_id` int(11) DEFAULT NULL,
  `match_time` varchar(20) DEFAULT NULL COMMENT '比赛进行时间',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否开奖 0：未开赛    1：比赛中  2：完结',
  `deal_status` tinyint(4) DEFAULT '0' COMMENT '是否已兑奖 0：未兑奖 1：详情单已兑奖 ',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '添加时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`schedule_result_id`),
  KEY `schedule_mid` (`schedule_mid`)
) ENGINE=InnoDB AUTO_INCREMENT=1226 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `schedule_technic`
-- ----------------------------
DROP TABLE IF EXISTS `schedule_technic`;
CREATE TABLE `schedule_technic` (
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
) ENGINE=InnoDB AUTO_INCREMENT=626 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `ssq_trend_chart`
-- ----------------------------
DROP TABLE IF EXISTS `ssq_trend_chart`;
CREATE TABLE `ssq_trend_chart` (
  `ssq_trend_chart_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `lottery_name` varchar(25) NOT NULL COMMENT '彩种名称',
  `periods` varchar(25) NOT NULL COMMENT '期数',
  `open_code` varchar(50) NOT NULL COMMENT '开奖号码',
  `red_omission` varchar(100) NOT NULL COMMENT '红球遗漏',
  `blue_omission` varchar(50) NOT NULL COMMENT '蓝球遗漏',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ssq_trend_chart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='双色球走势图';

-- ----------------------------
--  Table structure for `store`
-- ----------------------------
DROP TABLE IF EXISTS `store`;
CREATE TABLE `store` (
  `store_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_code` int(11) DEFAULT NULL COMMENT '门店编号',
  `user_id` int(11) DEFAULT NULL COMMENT '会员表ID',
  `cust_no` varchar(100) NOT NULL COMMENT '门店唯一编码',
  `password` varchar(255) DEFAULT NULL COMMENT '登录密码',
  `store_name` varchar(100) DEFAULT NULL COMMENT '门店名称',
  `phone_num` varchar(11) DEFAULT NULL COMMENT '手机号',
  `telephone` varchar(12) DEFAULT NULL COMMENT '联系电话',
  `email` varchar(50) DEFAULT NULL COMMENT '邮箱',
  `province` varchar(50) DEFAULT NULL COMMENT '所在省份',
  `city` varchar(50) DEFAULT NULL COMMENT '所在城市',
  `area` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL COMMENT '所在地区',
  `coordinate` varchar(100) DEFAULT NULL COMMENT '门店坐标',
  `company_id` int(11) DEFAULT NULL COMMENT '所属公司ID',
  `store_type` tinyint(4) DEFAULT '0' COMMENT '0： 未知 1:个体自营店；2：个体转让店；3：企业自营店',
  `cert_status` tinyint(4) DEFAULT '1' COMMENT '认证状态 1、未认证  2、审核中   3、已通过  4、未通过',
  `real_name_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '实名认证状态 0：未认证 1:认证成功 2：待审核 3：审核失败',
  `review_remark` varchar(100) DEFAULT NULL COMMENT '审核说明',
  `status` tinyint(4) DEFAULT '1' COMMENT '使用状态   2、禁用   1、启用',
  `store_remark` varchar(255) DEFAULT NULL COMMENT '店铺说明',
  `opt_id` int(11) DEFAULT NULL,
  `amap_id` int(11) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL COMMENT '开户时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `support_bonus` decimal(18,2) DEFAULT NULL COMMENT '支持代兑奖金',
  `open_time` varchar(10) DEFAULT NULL COMMENT '开店时间',
  `close_time` varchar(10) DEFAULT NULL COMMENT '关门时间',
  `contract_start_date` date DEFAULT NULL COMMENT '合同开始时间',
  `contract_end_date` date DEFAULT NULL COMMENT '合同结束时间',
  `store_img` varchar(255) DEFAULT NULL COMMENT '店面',
  `store_qrcode` varchar(100) DEFAULT NULL COMMENT '二维码图片',
  `store_grade` varchar(50) DEFAULT '0' COMMENT '门店等级',
  `his_win_nums` int(11) DEFAULT '0' COMMENT '中奖次数',
  `his_win_amount` decimal(18,2) DEFAULT '0.00' COMMENT '中奖金额',
  `made_nums` int(11) NOT NULL DEFAULT '0' COMMENT '定制人数',
  `consignment_type` tinyint(4) DEFAULT NULL COMMENT '代销类型；1:体彩；2:福彩；3:全部',
  PRIMARY KEY (`store_id`),
  KEY `store_code` (`store_code`),
  KEY `cust_no` (`cust_no`),
  KEY `cert_status` (`cert_status`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `store_account`
-- ----------------------------
DROP TABLE IF EXISTS `store_account`;
CREATE TABLE `store_account` (
  `store_account_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `cust_no` varchar(100) NOT NULL COMMENT '门店编码',
  `account_name` varchar(50) NOT NULL COMMENT '开户人姓名',
  `account_nums` varchar(19) NOT NULL COMMENT '银行卡号',
  `open_bank` varchar(50) NOT NULL COMMENT '开户行',
  `bank_address` varchar(100) NOT NULL COMMENT '银行所在地址',
  `bank_branches` varchar(255) NOT NULL COMMENT '银行网点',
  `reserved_tel` varchar(11) NOT NULL COMMENT '预留手机号',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`store_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `store_detail`
-- ----------------------------
DROP TABLE IF EXISTS `store_detail`;
CREATE TABLE `store_detail` (
  `store_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '门店Id',
  `cust_no` varchar(100) NOT NULL COMMENT '门店编码',
  `consignee_name` varchar(20) DEFAULT NULL COMMENT '代销者姓名',
  `consignee_card` varchar(18) DEFAULT NULL COMMENT '代销者身份证号码',
  `sports_consignee_code` varchar(100) DEFAULT NULL COMMENT '体彩代销编号',
  `welfare_consignee_code` varchar(100) DEFAULT NULL COMMENT '福彩代销编号',
  `company_name` varchar(100) DEFAULT NULL COMMENT '公司名称',
  `business_license` varchar(100) DEFAULT NULL COMMENT '营业执照号',
  `operator_name` varchar(50) DEFAULT NULL COMMENT '运营者姓名',
  `operator_card` varchar(100) DEFAULT NULL COMMENT '运营者证件',
  `old_owner_name` varchar(100) DEFAULT NULL COMMENT '原业主姓名',
  `old_owner_card` varchar(18) DEFAULT NULL COMMENT '原业主身份证',
  `now_owner_name` varchar(100) DEFAULT NULL COMMENT '现业主姓名',
  `now_owner_card` varchar(18) DEFAULT NULL COMMENT '现业主身份证',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `consignee_img` varchar(100) DEFAULT NULL COMMENT '代销资质图片 正面',
  `consignee_card_img1` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '代销者身份证 正面',
  `consignee_card_img2` varchar(100) DEFAULT NULL COMMENT '代销者身份证 反面',
  `consignee_card_img3` varchar(100) DEFAULT NULL COMMENT '代销者手持身份证 正面',
  `consignee_card_img4` varchar(100) DEFAULT NULL COMMENT '代销者手持身份证 反面',
  `old_owner_card_img1` varchar(100) DEFAULT NULL COMMENT '原业主身份证 正面',
  `old_owner_card_img2` varchar(100) DEFAULT NULL COMMENT '原业主身份证 反面',
  `business_license_img` varchar(100) DEFAULT NULL COMMENT '营业执照 正面',
  `competing_img` varchar(100) DEFAULT NULL COMMENT '竞彩票样',
  `football_img` varchar(100) DEFAULT NULL COMMENT '传统足球',
  `sports_nums_img` varchar(100) DEFAULT NULL COMMENT '体彩数字',
  `sports_fre_img` varchar(100) DEFAULT NULL COMMENT '体彩高频',
  `north_single_img` varchar(100) DEFAULT NULL COMMENT '北单',
  `welfare_nums_img` varchar(100) DEFAULT NULL COMMENT '福彩数字',
  `welfare_fre_img` varchar(100) DEFAULT NULL COMMENT '福彩高频',
  `opt_id` int(11) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`store_detail_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `store_token`
-- ----------------------------
DROP TABLE IF EXISTS `store_token`;
CREATE TABLE `store_token` (
  `store_token_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `cust_no` varchar(50) NOT NULL COMMENT '会员编号',
  `token` varchar(70) NOT NULL COMMENT 'token值',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`store_token_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户token表';

-- ----------------------------
--  Table structure for `sys_admin`
-- ----------------------------
DROP TABLE IF EXISTS `sys_admin`;
CREATE TABLE `sys_admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `admin_code` varchar(255) NOT NULL DEFAULT 'GLC513116000000',
  `status` smallint(6) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `admin_tel` varchar(20) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `admin_pid` int(11) NOT NULL DEFAULT '1',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_admin_role`
-- ----------------------------
DROP TABLE IF EXISTS `sys_admin_role`;
CREATE TABLE `sys_admin_role` (
  `admin_role_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_auth`
-- ----------------------------
DROP TABLE IF EXISTS `sys_auth`;
CREATE TABLE `sys_auth` (
  `auth_id` int(11) NOT NULL AUTO_INCREMENT,
  `auth_name` varchar(50) NOT NULL,
  `auth_url` varchar(127) DEFAULT NULL,
  `auth_create_at` datetime NOT NULL,
  `auth_update_at` datetime NOT NULL,
  `auth_status` tinyint(1) NOT NULL DEFAULT '1',
  `auth_pid` int(11) NOT NULL DEFAULT '0',
  `auth_level` int(11) NOT NULL DEFAULT '1',
  `auth_sort` int(11) NOT NULL DEFAULT '0',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`auth_id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_menu`
-- ----------------------------
DROP TABLE IF EXISTS `sys_menu`;
CREATE TABLE `sys_menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(50) NOT NULL,
  `menu_url` varchar(255) NOT NULL,
  `menu_pid` int(11) NOT NULL DEFAULT '0',
  `menu_level` int(11) NOT NULL DEFAULT '1',
  `menu_sort` int(11) NOT NULL DEFAULT '1',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `sys_role`
-- ----------------------------
DROP TABLE IF EXISTS `sys_role`;
CREATE TABLE `sys_role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `role_create_at` datetime NOT NULL,
  `role_update_at` datetime NOT NULL,
  `role_status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_role_auth`
-- ----------------------------
DROP TABLE IF EXISTS `sys_role_auth`;
CREATE TABLE `sys_role_auth` (
  `role_auth_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `auth_id` int(11) NOT NULL,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_auth_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1216 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `team`
-- ----------------------------
DROP TABLE IF EXISTS `team`;
CREATE TABLE `team` (
  `team_id` int(11) NOT NULL AUTO_INCREMENT,
  `team_code` varchar(25) NOT NULL COMMENT '编码',
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
) ENGINE=InnoDB AUTO_INCREMENT=2890 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `third_user`
-- ----------------------------
DROP TABLE IF EXISTS `third_user`;
CREATE TABLE `third_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '我们的uid',
  `uid_source` tinyint(4) NOT NULL DEFAULT '1' COMMENT '客户端来源 1:彩票 2:彩店',
  `third_uid` varchar(200) NOT NULL COMMENT '第三方用户id（如open_id）',
  `union_id` varchar(200) DEFAULT NULL COMMENT '微信唯一id',
  `type` tinyint(1) DEFAULT '1' COMMENT '1-微信公众号 2-QQ 3-微信 4-微博',
  `icon` varchar(200) DEFAULT NULL COMMENT '头像url',
  `nickname` varchar(100) NOT NULL COMMENT '第三方用户昵称',
  `sex` tinyint(2) DEFAULT NULL COMMENT '1-男 2-女',
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8 COMMENT='第三方用户信息表';

-- ----------------------------
--  Table structure for `user`
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_tel` char(12) NOT NULL COMMENT '手机号',
  `user_land` char(12) DEFAULT NULL COMMENT '联系电话',
  `user_sex` tinyint(4) DEFAULT NULL COMMENT '性别',
  `password` varchar(100) DEFAULT NULL COMMENT '用户密码',
  `cust_no` varchar(15) DEFAULT NULL COMMENT '咕啦会员系统编号',
  `user_email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `balance` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '余额放弃不用',
  `user_pic` varchar(256) DEFAULT NULL COMMENT '用户头像',
  `province` varchar(50) DEFAULT NULL COMMENT '省份',
  `city` varchar(50) DEFAULT NULL COMMENT '城市',
  `area` varchar(50) DEFAULT NULL COMMENT '区域',
  `address` varchar(100) DEFAULT NULL COMMENT '详细地址',
  `invite_code` varchar(100) DEFAULT NULL COMMENT '邀请码',
  `user_type` tinyint(4) DEFAULT '1' COMMENT '用户类型:1:普通用户；2：可购彩用户 3：门店用户',
  `account_time` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '用户状态1:正常',
  `authen_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '认证状态 0：未认证 1:认证成功 2：待审核 3：审核失败',
  `authen_remark` varchar(100) DEFAULT NULL COMMENT '审核说明',
  `register_from` tinyint(4) DEFAULT NULL COMMENT '用户注册来源1:彩票app,2:彩票h5,3:咕啦钱包,4:咕啦社区,5:微信公众号',
  `level_name` varchar(100) DEFAULT NULL COMMENT '等级名称',
  `level_id` int(11) DEFAULT NULL COMMENT '等级ID',
  `agent_code` varchar(255) NOT NULL DEFAULT 'GL888888' COMMENT '上级代理商编码',
  `agent_name` varchar(255) NOT NULL DEFAULT 'glagent' COMMENT '上级代理商名称',
  `agent_id` int(11) NOT NULL DEFAULT '21' COMMENT '上级代理商ID',
  `user_remark` varchar(255) DEFAULT NULL COMMENT '用户备注',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人',
  `last_login` datetime DEFAULT NULL COMMENT '上次登录时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '生成修改时间',
  PRIMARY KEY (`user_id`),
  KEY `register_from` (`register_from`),
  KEY `cust_no` (`cust_no`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COMMENT='用户';

-- ----------------------------
--  Table structure for `user_attention`
-- ----------------------------
DROP TABLE IF EXISTS `user_attention`;
CREATE TABLE `user_attention` (
  `attention_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关注ID',
  `user_id` int(11) NOT NULL COMMENT '会员',
  `schedule_mid` varchar(25) NOT NULL COMMENT '赛程MID',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`attention_id`)
) ENGINE=InnoDB AUTO_INCREMENT=356 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `user_deploy`
-- ----------------------------
DROP TABLE IF EXISTS `user_deploy`;
CREATE TABLE `user_deploy` (
  `user_deploy_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员个人配置ID',
  `user_id` int(11) NOT NULL COMMENT '会员ID',
  `deploy_lottery_id` varchar(100) NOT NULL COMMENT '会员个人喜好彩种ID串',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_deploy_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `user_follow`
-- ----------------------------
DROP TABLE IF EXISTS `user_follow`;
CREATE TABLE `user_follow` (
  `user_follow_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户关注ID',
  `cust_no` varchar(15) NOT NULL COMMENT '用户cust_no',
  `store_no` varchar(15) DEFAULT NULL COMMENT '门店cust_no',
  `store_id` int(11) NOT NULL COMMENT '门店ID',
  `ticket_amount` decimal(18,0) NOT NULL DEFAULT '0' COMMENT '出票金额',
  `ticket_count` int(11) NOT NULL DEFAULT '0' COMMENT '总出票数',
  `default_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:系统默认 2：会员自主默认',
  `follow_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0：取消关注 1：用户关注 2：用户拉黑门店 3：被门店拉黑 4：被门店关注',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_follow_id`),
  KEY `cust_no` (`cust_no`),
  KEY `store_no` (`store_no`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `user_funds`
-- ----------------------------
DROP TABLE IF EXISTS `user_funds`;
CREATE TABLE `user_funds` (
  `user_funds_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `cust_no` varchar(50) NOT NULL,
  `all_funds` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '总金额',
  `able_funds` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '可用金额',
  `ice_funds` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '冻结金额',
  `no_withdraw` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '不可提现余额',
  `user_integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `user_glcoin` decimal(18,2) DEFAULT '0.00',
  `user_growth` int(11) NOT NULL DEFAULT '0',
  `pay_password` varchar(100) DEFAULT NULL COMMENT '支付密码',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_funds_id`),
  KEY `user_id` (`user_id`),
  KEY `cust_no` (`cust_no`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `user_growth`
-- ----------------------------
DROP TABLE IF EXISTS `user_growth`;
CREATE TABLE `user_growth` (
  `user_growth_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '成长机制值ID',
  `growth_source` varchar(25) NOT NULL COMMENT '成长值来源',
  `growth_category` varchar(50) NOT NULL DEFAULT '' COMMENT '成长类型',
  `growth_value` int(11) NOT NULL DEFAULT '0' COMMENT '成长值',
  `growth_remark` varchar(255) NOT NULL COMMENT '成长机制',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_growth_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `user_levels`
-- ----------------------------
DROP TABLE IF EXISTS `user_levels`;
CREATE TABLE `user_levels` (
  `user_level_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '等级ID',
  `level_name` varchar(25) NOT NULL COMMENT '等级名称',
  `level_growth` int(11) NOT NULL DEFAULT '0' COMMENT '所需成长值',
  `cz_integral` int(11) NOT NULL COMMENT '充值送积分值',
  `glcz_discount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '咕啦币折扣',
  `glcz_integral` int(11) NOT NULL COMMENT '充值咕啦币送积分',
  `agent_code` varchar(100) NOT NULL,
  `up_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '升级机制',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_level_id`,`up_status`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `user_plan`
-- ----------------------------
DROP TABLE IF EXISTS `user_plan`;
CREATE TABLE `user_plan` (
  `user_plan_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_plan_code` varchar(50) NOT NULL COMMENT '认购编码',
  `user_id` int(11) NOT NULL,
  `user_name` varchar(45) DEFAULT NULL,
  `user_tel` varchar(45) DEFAULT NULL,
  `plan_id` int(11) NOT NULL COMMENT '计划id',
  `store_id` int(11) NOT NULL,
  `win_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '奖金去向 1：自动转入下一期  2：直接转入自己余额',
  `bet_scale` int(11) NOT NULL DEFAULT '100' COMMENT '投注比例 ',
  `buy_money` decimal(16,2) NOT NULL COMMENT '认购金额',
  `able_funds` decimal(16,2) DEFAULT NULL COMMENT '可用金额',
  `betting_funds` decimal(16,0) DEFAULT '0' COMMENT '正在投金额',
  `win_amount` decimal(16,2) DEFAULT NULL COMMENT '总奖金',
  `total_profit` decimal(16,2) DEFAULT '0.00' COMMENT '总盈利',
  `end_time` datetime DEFAULT NULL COMMENT '结算时间',
  `status` tinyint(4) DEFAULT NULL COMMENT '状态 1：未支付 2：进行中 3：已结算',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '数据库最后更新时间',
  PRIMARY KEY (`user_plan_id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `store_id` (`store_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Table structure for `user_token`
-- ----------------------------
DROP TABLE IF EXISTS `user_token`;
CREATE TABLE `user_token` (
  `user_token_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `cust_no` varchar(50) NOT NULL COMMENT '会员编号',
  `token` varchar(70) NOT NULL COMMENT 'token值',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_token_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户token表';

-- ----------------------------
--  Table structure for `withdraw`
-- ----------------------------
DROP TABLE IF EXISTS `withdraw`;
CREATE TABLE `withdraw` (
  `withdraw_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '提现ID',
  `cust_no` varchar(50) NOT NULL COMMENT 'cust_no 会员咕啦唯一编号',
  `cust_type` tinyint(4) DEFAULT NULL,
  `withdraw_code` varchar(100) NOT NULL COMMENT '订单编号',
  `outer_no` varchar(100) DEFAULT NULL COMMENT '银行生成的交易编号',
  `bank_info` varchar(100) DEFAULT NULL COMMENT '提现账户信息',
  `withdraw_money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '申请提现金额',
  `actual_money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '实际到账金额',
  `fee_money` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '提现费用',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0：未处理 1:处理中 2：提现成功 3：提现失败 4：异常订单',
  `toaccount_time` datetime DEFAULT NULL COMMENT '到账时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `modify_time` datetime DEFAULT NULL COMMENT '更新时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`withdraw_id`),
  KEY `cust_no` (`cust_no`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
--  Procedure structure for `sp_update_deal_lottery_order`
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_update_deal_lottery_order`;
delimiter ;;
CREATE DEFINER=`root`@`%` PROCEDURE `sp_update_deal_lottery_order`()
BEGIN
 Update lottery_order d 
	inner join (select a.lottery_order_id,sum(b.win_amount) as win_total from lottery_order a
	inner join betting_detail b on a.lottery_order_id = b.lottery_order_id
where b.deal_status = 1 and b.lottery_order_id NOT IN (SELECT lottery_order_id FROM betting_detail WHERE deal_status = 0 GROUP BY lottery_order_id) group by a.lottery_order_id) c on d.lottery_order_id = c.lottery_order_id
SET d.win_amount=c.win_total, d.deal_status = 1
WHERE lottery_type= 2 and d.deal_status = 0;
UPDATE lottery_order SET status = 4 WHERE status=3 and deal_status=1 and lottery_type=2 and win_amount !=0 ;
UPDATE lottery_order SET status = 5 WHERE status=3 and deal_status=1 and lottery_type=2 and win_amount=0 ;
end
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `sp_update_deal_programme`
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_update_deal_programme`;
delimiter ;;
CREATE DEFINER=`root`@`%` PROCEDURE `sp_update_deal_programme`()
BEGIN
  Update programme p 
inner join (select a.programme_code,a.win_amount as win_total from lottery_order a
where a.deal_status = 1) c on p.programme_code = c.programme_code
SET p.win_amount=c.win_total, p.bet_status = 7;
UPDATE programme SET status = 6 WHERE status=4 and bet_status=7 and win_amount !=0 ;
UPDATE programme SET status = 5 WHERE status=4 and bet_status=7 and win_amount=0 ;
UPDATE programme_user pu INNER JOIN programme p ON p.programme_id = pu.programme_id AND p.bet_status = 6  SET pu.status = p.status WHERE pu.status = 4 AND p.status != 4;
end
 ;;
delimiter ;

-- ----------------------------
--  Procedure structure for `sp_update_expired_lottery_order`
-- ----------------------------
DROP PROCEDURE IF EXISTS `sp_update_expired_lottery_order`;
delimiter ;;
CREATE DEFINER=`root`@`%` PROCEDURE `sp_update_expired_lottery_order`()
BEGIN
  DECLARE exit handler for sqlexception rollback; 
  update betting_detail a
     INNER JOIN lottery_order b
     on (a.lottery_id=b.lottery_id)
     set a.`status`=6
  where a.`status`=2 
  and b.`status`=2
  and b.end_time<now();

	update lottery_order set status = 6 where status = 2 and now()>end_time;
  COMMIT; 
end
 ;;
delimiter ;

-- ----------------------------
--  Event structure for `e_update_deal_lottery_order`
-- ----------------------------
DROP EVENT IF EXISTS `e_update_deal_lottery_order`;
delimiter ;;
CREATE DEFINER=`root`@`%` EVENT `e_update_deal_lottery_order` ON SCHEDULE EVERY 5 MINUTE STARTS '2017-08-29 13:49:01' ON COMPLETION PRESERVE ENABLE DO call sp_update_deal_lottery_order()
 ;;
delimiter ;

-- ----------------------------
--  Event structure for `e_update_expired_lottery_order`
-- ----------------------------
DROP EVENT IF EXISTS `e_update_expired_lottery_order`;
delimiter ;;
CREATE DEFINER=`root`@`%` EVENT `e_update_expired_lottery_order` ON SCHEDULE EVERY 5 MINUTE STARTS '2017-08-25 16:08:35' ON COMPLETION PRESERVE ENABLE DO call sp_update_expired_lottery_order()
 ;;
delimiter ;

-- ----------------------------
--  Event structure for `e_update_iswin_betting_detail`
-- ----------------------------
DROP EVENT IF EXISTS `e_update_iswin_betting_detail`;
delimiter ;;
CREATE DEFINER=`root`@`%` EVENT `e_update_iswin_betting_detail` ON SCHEDULE EVERY 3 MINUTE STARTS '2017-08-29 13:57:13' ON COMPLETION PRESERVE ENABLE DO UPDATE betting_detail SET status = 4,deal_status = 1 WHERE deal_status=0 and deal_nums = schedule_nums and win_amount!=0
 ;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
