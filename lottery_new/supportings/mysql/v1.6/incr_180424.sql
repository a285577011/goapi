/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-4-24
 */
--新增会员行为表 （张悦玲） (已执行)
CREATE TABLE `user_active` (
  `user_active_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '会员行为ID',
  `user_id` int(11) DEFAULT NULL COMMENT '会员Id',
  `source_id` int(11) DEFAULT NULL COMMENT '来源ID',
  `active_type` varchar(50) DEFAULT NULL COMMENT '行为类型：''register'', ''info'',  ''auth'',  ''appwell'',  ''bandwechat'',''login'', ''betday'',  ''usecoin'', ''shareapp'',''ccz001 - ccz010'',  ''czgive''',
  `active_coin_value` decimal(18,2) DEFAULT '0.00' COMMENT '行为获取咕币值',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `receive_status` tinyint(2) DEFAULT '1' COMMENT '是否领取 1：未领取 2：已领取',
  `status` tinyint(2) DEFAULT '1' COMMENT '状态 1：有效 2：无效',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_active_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--新增咕币充值类型表 （张悦玲）（已执行）
CREATE TABLE `user_coin_cz_type` (
  `coin_cz_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `cz_type` char(25) DEFAULT NULL COMMENT '充值类型',
  `cz_type_name` varchar(50) DEFAULT NULL COMMENT '充值类型名',
  `cz_money` decimal(18,2) DEFAULT '0.00' COMMENT '价格',
  `cz_coin` int(11) DEFAULT '0' COMMENT '咕币值',
  `weal_type` tinyint(4) DEFAULT '1' COMMENT '福利类型 1:无任何福利 2：充值赠咕币的百分比 3:首充福利 4：充值次日起按充值类型送相应咕币',
  `weal_value` decimal(18,2) DEFAULT '0.00' COMMENT '福利值 ',
  `weal_time` tinyint(4) DEFAULT '0' COMMENT '福利有效天数',
  `status` tinyint(2) DEFAULT '1' COMMENT '1:启用 2：禁用',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`coin_cz_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;

--新增咕币变化记录表 （张悦玲） （已执行）
CREATE TABLE `user_gl_coin_record` (
  `gl_coin_record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '咕啦币表id',
  `order_code` varchar(50) DEFAULT NULL COMMENT '订单编码',
  `user_id` int(11) NOT NULL COMMENT 'user_id',
  `cust_no` varchar(25) DEFAULT NULL,
  `type` tinyint(2) NOT NULL COMMENT '类型：1=收入，2=支出',
  `coin_source` tinyint(2) DEFAULT NULL COMMENT '来源：1 => ''注册'', 2 => ''补全资料'', 3 => ''实名'', 4 => ''APP好评'', 5 => ''微信绑定'', 6 => ''每日登录'', 7 => ''每日首购分享'', 8 => ''每日使用666个咕币'', 9 => ''每日分享APP'', 10 => ''充值'' 11 => ''充值赠送''',
  `source_id` int(11) DEFAULT NULL COMMENT '充值赠送对应的充值记录ID',
  `coin_value` decimal(10,2) DEFAULT NULL COMMENT '新增咕啦币',
  `value_money` decimal(10,2) DEFAULT '0.00' COMMENT '对应的金钱额',
  `totle_balance` int(11) DEFAULT NULL COMMENT '新增后总咕啦币余额',
  `source_type` varchar(50) DEFAULT NULL COMMENT '来源类型',
  `remark` varchar(200) DEFAULT NULL COMMENT '备注',
  `status` tinyint(2) DEFAULT '0' COMMENT '0:未支付 1：已支付',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gl_coin_record_id`),
  KEY `user_id` (`user_id`) USING BTREE COMMENT '根据用户id查找',
  KEY `coin_source` (`coin_source`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4533 DEFAULT CHARSET=utf8mb4 COMMENT='咕啦币明细表';

--修改开奖结果奖池 （张悦玲）（已执行）
Alter table lottery_record change pool pool varchar(50);

