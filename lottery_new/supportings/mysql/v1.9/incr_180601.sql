/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-6-1
 */
--联赛表新增字段（张悦玲） （已执行）
ALTER TABLE `league` ADD COLUMN `league_color` VARCHAR(25) NULL DEFAULT '#FFFAFA' COMMENT '联赛颜色' AFTER `league_status`;

--新增世界杯基础赛程表 （张悦玲） （已执行）
CREATE TABLE `worldcup_schedule` (
  `worldcup_schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_date` varchar(50) DEFAULT NULL COMMENT '比赛日',
  `start_time` datetime DEFAULT NULL COMMENT '比赛时间',
  `sort` varchar(25) DEFAULT NULL,
  `group_id` varchar(25) DEFAULT '0' COMMENT '所属小组序号',
  `group_name` varchar(25) DEFAULT NULL COMMENT '所属小组',
  `home_team_name` varchar(50) DEFAULT NULL COMMENT '主队名',
  `home_img` varchar(100) DEFAULT NULL COMMENT '主队国旗',
  `visit_team_name` varchar(50) DEFAULT NULL COMMENT '客队名',
  `visit_img` varchar(100) DEFAULT NULL COMMENT '客队国旗',
  `bifen` varchar(50) DEFAULT NULL COMMENT '比分',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`worldcup_schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--世界杯基础赛程表 （张悦玲） （张悦玲）(已执行)
ALTER TABLE `worldcup_schedule` ADD COLUMN `game_city` VARCHAR(50) NULL  COMMENT '比赛城市' AFTER `worldcup_schedule_id`;
ALTER TABLE `worldcup_schedule` ADD COLUMN `game_field` VARCHAR(50) NULL  COMMENT '比赛场地' AFTER `game_city`;
ALTER TABLE `worldcup_schedule` ADD COLUMN `game_level_id` TINYINT(4) NULL  COMMENT '比赛级别 1:小组赛 2:1/8决赛 3:1/4决赛 4：1/2决赛  5:3,4名决赛 6：决赛' AFTER `game_field`;
ALTER TABLE `worldcup_schedule` ADD COLUMN `game_level_name` TINYINT(50) NULL  COMMENT '比赛级别名 1:小组赛 2:1/8决赛 3:1/4决赛 4：1/2决赛  5:3,4名决赛 6：决赛' AFTER `game_field`;

--新增出票权重表 （张悦玲）（已执行）
CREATE TABLE `weight_lottery_out` (
  `weight_lottery_out_id` int(11) NOT NULL AUTO_INCREMENT,
  `lottery_code` varchar(25) DEFAULT NULL COMMENT '彩种',
  `out_code` varchar(50) DEFAULT NULL COMMENT '门店编号/自动出票编号',
  `weight` int(11) DEFAULT '0' COMMENT '出票权重',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`weight_lottery_out_id`)
) ENGINE=InnoDB AUTO_INCREMENT=225 DEFAULT CHARSET=utf8mb4;

--第三方出票方新增字段 （张悦玲）（已执行）
ALTER TABLE `auto_out_third` ADD COLUMN `third_code` VARCHAR(50) NULL  COMMENT '第三方出票编号' AFTER `auto_out_third_id`;
