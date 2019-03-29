/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-10-20
 */

--新建任选14赛程表 （张悦玲）
CREATE TABLE `optional_schedule` (
  `optional_schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `sorting_code` int(11) NOT NULL COMMENT '任选14 位置标号',
  `periods` varchar(50) NOT NULL COMMENT '期数',
  `league_name` varchar(100) DEFAULT NULL COMMENT '联赛名',
  `schedule_mid` varchar(25) DEFAULT NULL COMMENT '赛程唯一编码',
  `start_time` varchar(50) DEFAULT NULL COMMENT '比赛时间',
  `home_short_name` varchar(100) DEFAULT NULL COMMENT '主队名',
  `visit_short_name` varchar(100) DEFAULT NULL COMMENT '客队名',
  `schedule_result` tinyint(4) DEFAULT NULL COMMENT '比赛结果',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`optional_schedule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4;

