/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-1-17
 */

--订单表新增字段 (张悦玲)已执行
ALTER TABLE `lottery_order` ADD COLUMN `auto_type` TINYINT(4) DEFAULT 1 COMMENT '自动出票 1：手工出票 2：自动出票';

--新增自动出票表 （张悦玲）已执行
CREATE TABLE `auto_out_order` (
  `out_order_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自动出票订单表主键',
  `out_order_code` varchar(100) DEFAULT NULL COMMENT '出票编号',
  `order_code` varchar(100) DEFAULT NULL COMMENT '订单编号',
  `ticket_code` varchar(50) DEFAULT NULL COMMENT '出票单票号',
  `free_type` tinyint(4) DEFAULT '1' COMMENT '串关方式 0：非自由串关 1：自由串关',
  `lottery_code` varchar(25) DEFAULT NULL COMMENT '彩种编号',
  `play_code` varchar(25) DEFAULT NULL COMMENT '玩法编号',
  `periods` varchar(50) DEFAULT NULL COMMENT '期数',
  `bet_val` text COMMENT '投注内容',
  `bet_add` tinyint(4) DEFAULT '0' COMMENT '追加 0：不追加 1:追加',
  `multiple` int(11) DEFAULT '1' COMMENT '倍数',
  `amount` decimal(16,0) DEFAULT '0' COMMENT '投注金额',
  `count` int(11) DEFAULT '1' COMMENT '注数',
  `status` tinyint(4) DEFAULT '1' COMMENT '订单状态 1:等待出票 2:出票成功 3:出票失败',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '数据库对比时间',
  PRIMARY KEY (`out_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1783 DEFAULT CHARSET=utf8mb4;

--删表重建  借口投注接单表 （张悦玲）已执行
DROP TABLE IF EXISTS `api_order`;
CREATE TABLE `api_order` (
  `api_order_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '接口订单ID',
  `api_order_code` varchar(50) NOT NULL COMMENT '接口订单编号',
  `third_order_code` varchar(50) NOT NULL COMMENT '第三方订单编号',
  `user_id` int(11) NOT NULL COMMENT '会员ID',
  `lottery_code` varchar(25) NOT NULL COMMENT '彩种编号',
  `periods` varchar(50) DEFAULT NULL COMMENT '彩种期数',
  `play_code` varchar(50) NOT NULL COMMENT '投注玩法',
  `bet_val` varchar(500) NOT NULL COMMENT '投注内容',
  `bet_money` int(11) NOT NULL DEFAULT '0' COMMENT '投注金额',
  `multiple` int(11) NOT NULL DEFAULT '1' COMMENT '投注倍数',
  `is_add` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否追加',
  `end_time` datetime DEFAULT NULL COMMENT '截止时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:接单成功 2：下单成功 3：重复订单 4：余额不足 5：无门店接单 6：投注内容有误 7：下单失败  8：出票超时',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`api_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4;