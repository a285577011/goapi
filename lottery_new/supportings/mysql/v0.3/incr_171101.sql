/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-11-1
 */
--门店表新增字段  （谢建洪）
ALTER TABLE store ADD business_status TINYINT(1) NULL  COMMENT '营业状态1:为营业，2为暂停营业';

--新增门店操作日志表 （谢建洪）
CREATE TABLE `store_opt_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '操作日志id',
  `operator_id` int(11) DEFAULT NULL COMMENT '操作人id',
  `operator_name` varchar(50) DEFAULT '' COMMENT '操作员名称',
  `store_code` int(11) DEFAULT NULL COMMENT '门店编号',
  `content` varchar(255) DEFAULT NULL COMMENT '操作内容',
  `create_time` datetime DEFAULT NULL COMMENT '操作时间',
  `cust_no` varchar(100) NOT NULL COMMENT '门店唯一编码',
  `store_name` varchar(100) DEFAULT NULL COMMENT '门店名称',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--新增赛程统计表  （张悦玲）
CREATE TABLE `lan_schedule_count` (
  `count_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(20) NOT NULL COMMENT '赛程MID',
  `home_shots` varchar(200) DEFAULT NULL COMMENT '主队投篮',
  `visit_shots` varchar(200) DEFAULT NULL COMMENT '客队投篮',
  `home_three_point` varchar(200) DEFAULT NULL COMMENT '主队三分球',
  `visit_three_point` varchar(200) DEFAULT NULL COMMENT '客队三分球',
  `home_penalty` varchar(200) DEFAULT NULL COMMENT '主队罚球',
  `visit_penalty` varchar(200) DEFAULT NULL COMMENT '客队罚球',
  `home_rebound` int(11) DEFAULT NULL COMMENT '主队篮板',
  `visit_rebound` int(11) DEFAULT NULL COMMENT '客队篮板',
  `home_assist` int(11) DEFAULT NULL COMMENT '主队助攻',
  `visit_assist` int(11) DEFAULT NULL COMMENT '客队助攻',
  `home_steals` int(11) DEFAULT NULL COMMENT '主队抢断',
  `visit_steals` int(11) DEFAULT NULL COMMENT '客队抢断',
  `home_cap` int(11) DEFAULT NULL COMMENT '主队盖帽',
  `visit_cap` int(11) DEFAULT NULL COMMENT '客队盖帽',
  `home_foul` int(11) DEFAULT NULL COMMENT '主队犯规',
  `visit_foul` int(11) DEFAULT NULL COMMENT '客队犯规',
  `home_all_miss` int(11) DEFAULT NULL COMMENT '主队总失误',
  `visit_all_miss` int(11) DEFAULT NULL COMMENT '客队总失误',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`count_id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4;

--新增球员统计表 (张悦玲)
CREATE TABLE `lan_player_count` (
  `player_count_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` int(11) DEFAULT NULL COMMENT '赛程MID',
  `team_code` int(11) DEFAULT NULL COMMENT '球队CODE',
  `player_code` int(11) DEFAULT NULL COMMENT '球员编号',
  `player_name` varchar(100) DEFAULT NULL COMMENT '球员名字',
  `play_time` int(11) DEFAULT NULL COMMENT '上场分钟数',
  `shots_nums` varchar(25) DEFAULT NULL COMMENT '投篮次数',
  `rebound_nums` int(11) DEFAULT NULL COMMENT '篮板次数',
  `assist_nums` int(11) DEFAULT NULL COMMENT '助攻次数',
  `foul_nums` int(11) DEFAULT NULL COMMENT '犯规次数',
  `score` int(11) DEFAULT NULL COMMENT '得分',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`player_count_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1227 DEFAULT CHARSET=utf8mb4;
