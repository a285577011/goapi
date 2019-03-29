/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-1-26
 */
--新增门店出票机器表 （张悦玲）
CREATE TABLE `ticket_dispenser` (
  `ticket_dispenser_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '出票机主键',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '出票机类型 1：手工出票 2：自动出票',
  `dispenser_code` varchar(100) NOT NULL COMMENT '机器编号',
  `store_no` int(11) NOT NULL COMMENT '所属门店编号',
  `pre_out_nums` int(11) NOT NULL DEFAULT '0' COMMENT '预出票数',
  `mod_nums` int(11) NOT NULL DEFAULT '7200' COMMENT '剩余票数',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否使用 1：正常使用 2：禁用',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ticket_dispenser_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--新增智魔方出票表 （张悦玲）
CREATE TABLE `zmf_order` (
  `zmf_order_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `order_code` varchar(45) NOT NULL COMMENT '咕啦订单编号',
  `version` varchar(15) NOT NULL COMMENT '版本号',
  `command` varchar(15) NOT NULL COMMENT '命令码',
  `messageId` varchar(45) NOT NULL COMMENT '消息流水号',
  `status` varchar(10) DEFAULT NULL COMMENT '返回状态：0提交成功、1回调成功、2自动查询成功',
  `bet_val` text NOT NULL COMMENT '投注内容',
  `ret_sync_data` text COMMENT '同步返回消息',
  `ret_async_data` text COMMENT '异步返回消息',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `modify_time` datetime DEFAULT NULL COMMENT '回调修改时间',
  PRIMARY KEY (`zmf_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1785 DEFAULT CHARSET=utf8mb4;