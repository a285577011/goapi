/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-5-22
 */

--自动出票表修改字段 （张悦玲）（已执行）
Alter table auto_out_order change free_type free_type varchar(25);

--新增自动出票第三方表 （张悦玲）（已执行）
CREATE TABLE `auto_out_third` (
  `auto_out_third_id` int(11) NOT NULL AUTO_INCREMENT,
  `third_name` varchar(50) NOT NULL COMMENT '第三方名',
  `out_type` tinyint(4) DEFAULT '1' COMMENT '出票类型 1：流量方 2：自营 3：全部',
  `out_lottery` varchar(255) DEFAULT NULL COMMENT '接收出票彩种',
  `status` tinyint(4) DEFAULT '1' COMMENT '启用状态 1：启用 2：停用',
  `weight` tinyint(4) DEFAULT '1' COMMENT '权重配比',
  `opt_name` varchar(25) DEFAULT NULL COMMENT '修改人员名',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`auto_out_third_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--流量单表新增字段（张悦玲）（已执行）
ALTER TABLE `api_order` ADD COLUMN `out_type` TINYINT(4) NULL DEFAULT 2 COMMENT '出票方式 2：自动出票 1：手动出票' AFTER `major_type`;

