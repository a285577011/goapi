/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-3-13
 */

--新增世界杯冠亚军预猜表 （张悦玲）
CREATE TABLE `worldcup_fnl` (
  `worldcup_fnl_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '世界杯冠亚军预猜',
  `open_mid` char(25) DEFAULT NULL COMMENT '出售open_mid',
  `home_code` int(11) DEFAULT NULL COMMENT '第一个球队编号',
  `home_name` varchar(100) DEFAULT NULL COMMENT '第一个球队名',
  `home_img` varchar(150) DEFAULT NULL COMMENT '第一个球队名',
  `visit_code` int(11) DEFAULT NULL COMMENT '第二个球队编号',
  `visit_name` varchar(100) DEFAULT NULL COMMENT '第二个球队名',
  `visit_img` varchar(150) DEFAULT NULL COMMENT '第二个球队图标',
  `team_odds` decimal(10,2) DEFAULT '0.00' COMMENT '赔率',
  `team_chance` decimal(10,2) DEFAULT '0.00' COMMENT '概率',
  `status` tinyint(4) DEFAULT NULL COMMENT '在售状态 1：在售 2：停售',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`worldcup_fnl_id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4;

--新增世界杯冠军预猜表  （张悦玲）
CREATE TABLE `worldcup_chp` (
  `worldcup_chp_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '世界杯冠军预猜',
  `open_mid` char(25) DEFAULT NULL COMMENT '出售open_mid',
  `team_code` int(11) DEFAULT NULL COMMENT '球队编号',
  `team_name` varchar(150) DEFAULT NULL COMMENT '球队全称',
  `team_img` varchar(255) DEFAULT NULL COMMENT '球队图片',
  `team_odds` decimal(10,2) DEFAULT '0.00' COMMENT '球队赔率',
  `status` tinyint(4) DEFAULT '1' COMMENT '出售状态 1：在售 2：停售',
  `team_chance` decimal(10,2) DEFAULT '0.00' COMMENT '球队冠军概率',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`worldcup_chp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4;

