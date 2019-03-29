/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-3-22
 */
--彩票机表新增字段  （张悦玲）(已执行)
ALTER TABLE `ticket_dispenser` ADD COLUMN `out_lottery` VARCHAR(255) NULL COMMENT '可出彩种' AFTER `status`;

--处理明细订单  （张悦玲）(已执行)
CREATE TABLE `deal_order` (
  `deal_order_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '处理明细订单 ID',
  `order_id` int(11) NOT NULL COMMENT '相关订单ID',
  `lottery_code` varchar(50) NOT NULL COMMENT '投注彩种',
  `play_code` varchar(20) NOT NULL COMMENT '投注玩法',
  `bet_val` varchar(500) NOT NULL COMMENT '投注内容',
  `odds` varchar(1000) DEFAULT NULL COMMENT '赔率',
  `bet_money` decimal(18,0) NOT NULL COMMENT '投注金额',
  `bet_double` int(11) DEFAULT '1' COMMENT '投注倍数',
  `win_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '中奖金额',
  `status` tinyint(4) DEFAULT '3' COMMENT '3:待开奖 4：中奖 5：未中奖',
  `deal_status` tinyint(4) DEFAULT '0' COMMENT '0：未处理 1：已处理',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`deal_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4;

--处理明细详情单  （张悦玲）(已执行)
CREATE TABLE `deal_detail` (
  `deal_detail_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '处理明细订单详情ID',
  `deal_order_id` int(11) NOT NULL COMMENT '处理明细订单 ID',
  `lottery_code` varchar(50) NOT NULL COMMENT '彩种',
  `bet_val` varchar(255) NOT NULL COMMENT '投注内容',
  `odds` varchar(100) DEFAULT NULL COMMENT '赔率乘积',
  `fen_json` varchar(200) DEFAULT NULL COMMENT '（篮球）让分，预测总分的json串',
  `schedule_nums` int(11) DEFAULT '0' COMMENT '赛程个数',
  `deal_nums` int(11) DEFAULT '0' COMMENT '处理个数',
  `deal_schedule` varchar(255) DEFAULT 'gl' COMMENT '已处理赛程',
  `deal_odds_sche` varchar(255) DEFAULT 'ODDS_' COMMENT '更新赔率',
  `status` tinyint(4) DEFAULT '3' COMMENT '3:待开奖 4：中奖 5：未中奖',
  `deal_status` tinyint(4) DEFAULT '0' COMMENT '0：未处理 1：已处理',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`deal_detail_id`)
) ENGINE=InnoDB AUTO_INCREMENT=292 DEFAULT CHARSET=utf8mb4;
