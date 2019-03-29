/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Administrator
 * Created: 2018-02-08
 */
-- user 表新增字段 
ALTER TABLE `user`
	ADD COLUMN `p_tree` VARCHAR(255) NOT NULL COMMENT '上级推广树' AFTER `level_id`;

--schedule_remind 赛程提点表新增字段 （张悦玲）
ALTER TABLE `schedule_remind` ADD COLUMN `team_type` TINYINT(4) NULL COMMENT '球队主客场 1：主场 2：客场' AFTER `schedule_type`;

--新增足球大小球 （张悦玲）
CREATE TABLE `zu_daxiao_odds` (
  `lan_daxiao_odds_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_mid` varchar(25) DEFAULT NULL COMMENT '赛程MID',
  `company_name` varchar(25) DEFAULT NULL COMMENT '公司名',
  `country` varchar(25) DEFAULT NULL COMMENT '国家',
  `handicap_type` tinyint(4) DEFAULT NULL COMMENT '数据类型 1：初 2：即',
  `handicap_name` varchar(20) DEFAULT NULL COMMENT '初、即',
  `cutoff_nums` varchar(50) DEFAULT NULL COMMENT '盘口（让球数）',
  `odds_da` decimal(18,2) DEFAULT NULL COMMENT '大分赔率',
  `odds_da_trend` tinyint(4) DEFAULT '0' COMMENT '升降状态 0：不变 1：上升-1：下降',
  `odds_xiao` decimal(18,2) DEFAULT NULL COMMENT '小分赔率',
  `odds_xiao_trend` tinyint(4) DEFAULT NULL COMMENT '升降状态 0：不变 1：上升 -1：下降',
  `profit_rate` decimal(11,2) DEFAULT NULL COMMENT '返还率',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lan_daxiao_odds_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8521 DEFAULT CHARSET=utf8mb4;
