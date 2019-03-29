/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  龚伟平
 * Created: 2017-11-22
 */
--新增微信模版消息记录表(龚伟平)
CREATE TABLE `wx_msg_record` (
  `wx_msg_record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '微信模版消息记录表',
  `user_open_id` varchar(50) DEFAULT NULL COMMENT '微信openid',
  `type` tinyint(2) DEFAULT NULL COMMENT '类型：1=门店，2=订单出票，3=充值成功，4=合买未满元，5=开奖结果，6=文章审核，7=发放活动奖金',
  `msg_data` text CHARACTER SET utf8 COMMENT '微信模版消息数据（json格式）',
  `create_time` datetime DEFAULT NULL COMMENT '新建时间',
  PRIMARY KEY (`wx_msg_record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4;

--新增收藏文章表(龚伟平)
CREATE TABLE `articles_collect` (
  `articles_collect_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) DEFAULT NULL COMMENT '收藏者user_id',
  `expert_articles_id` int(1) DEFAULT NULL COMMENT '文章id',
  `create_time` datetime DEFAULT NULL COMMENT '收藏时间',
  PRIMARY KEY (`articles_collect_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收藏文章表';

--新增篮球赛程文字直播表 （张悦玲）
CREATE TABLE `lan_schedule_live` (
  `live_id` int(11) NOT NULL,
  `sort_id` int(11) NOT NULL COMMENT '文字直播ID',
  `schedule_mid` int(11) NOT NULL COMMENT '篮球MID',
  `live_person` varchar(50) NOT NULL COMMENT '直播人员',
  `text_sub` varchar(500) NOT NULL COMMENT '直播内容',
  `game_time` varchar(25) NOT NULL COMMENT '比赛时间',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`live_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

