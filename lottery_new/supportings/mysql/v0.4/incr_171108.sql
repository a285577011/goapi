/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-11-8
 */
-- 订单表新增奖金优化数据 (张悦玲) 已执行
ALTER TABLE lottery_order ADD major_type TINYINT(4) NULL DEFAULT 0  COMMENT '奖金优化类型 0：无奖金优化 1：平均优化 2：博热优化 3：博冷优化';

-- 奖金优化暂存表 （张悦玲）已执行
CREATE TABLE `major_data` (
  `major_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `major` longtext NOT NULL COMMENT '优化明细',
  `major_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '优化类型0：无奖金优化 1：平均优化 2：博热优化 3：博冷优化',
  `source` tinyint(4) NOT NULL DEFAULT '1' COMMENT '来源 1：普通下单 2：合买 3：计划？',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`major_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4;


--新建代理商表 （李家能）已执行
CREATE TABLE `agents`(
  `agents_id` int(11) NOT NULL AUTO_INCREMENT '代理商ID',
  `agents_appid` varchar(16) NOT NULL COMMENT '代理商APPID',
  `secret_key` varchar(32) NOT NULL COMMENT '代理商秘钥',
  `agents_account` varchar(20) NOT NULL DEFAULT Empty String COMMENT '代理商账户',
  `agents_name` varchar(100)   NOT NULL DEFAULT Empty String COMMENT '代理商名称',
  `upagents_code` varchar(20)  NOT NULL DEFAULT Empty String COMMENT '上级代理商编号',
  `upagents_name` varchar(100) NOT NULL DEFAULT Empty String COMMENT '上级代理商名称',
  `agents_type` tinyint(2) NOT NULL COMMENT '代理商类型：1总部 2地推 3体彩店 4福彩店 5便利店 6个人',
  `pass_status` tinyint(1) NOT NULL  DEFAULT "2" COMMENT '认证状态：1未认证 2审核中 3已通过 4未通过',
  `use_status` tinyint(1) NOT NULL  DEFAULT "1" COMMENT '使用状态：1使用 2锁定',
  `create_time` datetime DEFAULT NULL,COMMENT '创建时间',
  `check_time` datetime DEFAULT NULL,COMMENT '审核时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,COMMENT '更新时间',
  `opt_id` varchar(25)  DEFAULT NULL COMMENT '操作人',
  `agents_remark` varchar(100)  DEFAULT NULL COMMENT '代理商备注',
  `review_remark` varchar(100)  DEFAULT NULL COMMENT '审核说明',
  PRIMARY KEY (`agents_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


--新建代理商IP表 （李家能）已执行
CREATE TABLE `agents_ip`(
  `agents_ip_id` int(10) NOT NULL AUTO_INCREMENT '代理商IP ID',
  `agents_id` int(10) NOT NULL COMMENT '代理商ID',
  `ip_address` varchar(15) NOT NULL COMMENT 'IP地址',
  `status` tinyint(1) NOT NULL  DEFAULT "1" COMMENT '使用状态：1使用 2禁用',
  PRIMARY KEY (`agents_ip_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

