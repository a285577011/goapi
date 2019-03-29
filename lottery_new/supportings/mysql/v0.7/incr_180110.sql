/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  龚伟平
 * Created: 2018-1-8
 */

-- ----------------------------
-- 优惠券批次表  （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `coupons_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '优惠券id',
  `batch` varchar(20) NOT NULL DEFAULT '' COMMENT '批次',
  `coupons_name` varchar(50) NOT NULL DEFAULT '' COMMENT '优惠券名称',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '优惠券类型：1代金券2折扣券',
  `application_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '适用类型：1 系统发放类 2用户兑换类',
  `is_gift` tinyint(2) NOT NULL DEFAULT '2' COMMENT '是否为礼品：1是 2否',
  `discount` tinyint(2) DEFAULT NULL COMMENT '折扣值（80代表8折）',
  `numbers` int(10) NOT NULL DEFAULT '0' COMMENT '发行数量：系统发放类：0 用户兑换类：具体张数',
  `use_range` tinyint(4) NOT NULL DEFAULT '0' COMMENT '使用范围：0无限制使用彩种  其他参照彩种表',
  `less_consumption` int(10) NOT NULL COMMENT '最低消费',
  `reduce_money` int(10) DEFAULT NULL COMMENT '优惠金额',
  `days_num` tinyint(4) NOT NULL DEFAULT '0' COMMENT '会员单日限用张数：0不受限制 ',
  `stack_use` tinyint(2) NOT NULL DEFAULT '2' COMMENT '是否可叠加使用:1是2否',
  `start_date` datetime NOT NULL COMMENT '有效期开始时间',
  `end_date` datetime NOT NULL COMMENT '有效期结束时间',
  `send_content` varchar(200) DEFAULT '' COMMENT '发送内容，通知用户',
  `send_num` int(10) NOT NULL DEFAULT '0' COMMENT '本批次优惠券已发送数量',
  `use_num` int(10) NOT NULL DEFAULT '0' COMMENT '本批次优惠券已使用数量',
  `opt_id` tinyint(4) DEFAULT NULL COMMENT '操作人',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`coupons_id`),
  KEY `批次` (`batch`),
  KEY `date_rang` (`start_date`,`end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 用户优惠券表（已执行）
-- ----------------------------
DROP TABLE IF EXISTS `coupons_detail`;
CREATE TABLE `coupons_detail` (
  `coupons_detail_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '优惠券明细ID',
  `coupons_no` varchar(30) NOT NULL COMMENT '优惠券号',
  `conversion_code` varchar(15) NOT NULL DEFAULT '' COMMENT '优惠券兑换码',
  `coupons_batch` varchar(10) NOT NULL COMMENT '所属批次',
  `send_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '发送状态：1未发送2已发送',
  `send_user` varchar(30) DEFAULT NULL COMMENT '发送对象：用户cust_no',
  `send_time` datetime DEFAULT NULL COMMENT '发送时间',
  `use_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '使用状态:0:未领取:1领取未使用 2领取已使用',
  `use_order_code` varchar(50) DEFAULT '' COMMENT '使用订单号',
  `use_order_source` tinyint(4) DEFAULT NULL COMMENT '来源（1自购、2追号、3赠送、4合买 5、分享 6、计划购买）',
  `use_time` datetime DEFAULT NULL COMMENT '使用时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '优惠券状态:1激活2锁定',
  `create_time` datetime DEFAULT NULL COMMENT '优惠券明细创建时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`coupons_detail_id`),
  KEY `code` (`conversion_code`),
  KEY `num` (`coupons_no`),
  KEY `coupons_batch` (`coupons_batch`),
  KEY `send_user` (`send_user`)
) ENGINE=InnoDB AUTO_INCREMENT=686 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 礼品兑换记录表（已执行）
-- ----------------------------
DROP TABLE IF EXISTS `exchange_record`;
CREATE TABLE `exchange_record` (
  `exchange_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '会员ID',
  `user_name` varchar(50) NOT NULL COMMENT '会员名称',
  `cust_no` varchar(50) NOT NULL COMMENT '会员编码',
  `user_tel` char(12) NOT NULL COMMENT '会员手机',
  `exch_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:系统兑换;2:自助平台兑换；3微信平台兑换',
  `exch_code` varchar(50) NOT NULL COMMENT '兑换流水单号',
  `exch_glcoin` int(11) NOT NULL COMMENT '兑换咕币',
  `exch_nums` int(11) NOT NULL COMMENT '兑换数量',
  `less_glcoin` int(11) DEFAULT NULL COMMENT '扣除积分',
  `exch_time` datetime DEFAULT NULL COMMENT '兑换时间',
  `agent_code` varchar(100) DEFAULT NULL,
  `agent_name` varchar(100) DEFAULT NULL,
  `opt_name` varchar(50) DEFAULT NULL COMMENT '操作人昵称',
  `review_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否已审核；1：未审核；2：审核通过；3：不通过',
  `review_name` varchar(50) DEFAULT NULL COMMENT '审核员',
  `review_remark` varchar(255) DEFAULT NULL COMMENT '审核说明',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人ID',
  `create_time` datetime DEFAULT NULL COMMENT '申请时间',
  `modify_time` datetime DEFAULT NULL COMMENT '审核时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`exchange_record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=141 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 礼品兑换记录详情表（已执行）
-- ----------------------------
DROP TABLE IF EXISTS `exgift_record`;
CREATE TABLE `exgift_record` (
  `exgift_record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '礼品兑换记录id',
  `exchange_id` int(11) NOT NULL,
  `exch_code` varchar(50) NOT NULL,
  `gift_code` varchar(50) NOT NULL,
  `gift_name` varchar(50) NOT NULL COMMENT '礼品名称',
  `gift_nums` int(11) NOT NULL,
  `exch_int` int(11) NOT NULL COMMENT '所需积分',
  `all_int` int(11) NOT NULL COMMENT '总需积分',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`exgift_record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 礼品表  （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `gift`;
CREATE TABLE `gift` (
  `gift_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '礼品ID',
  `gift_code` varchar(25) NOT NULL COMMENT '礼品简码',
  `gift_name` varchar(50) NOT NULL COMMENT '礼品名称',
  `subtitle` varchar(100) DEFAULT '' COMMENT '礼品副标题',
  `gift_category` int(11) NOT NULL COMMENT '礼品分类',
  `type` tinyint(2) DEFAULT '1' COMMENT '礼品类型：1=优惠券，2=礼品卡，3=实物',
  `batch` varchar(20) DEFAULT NULL COMMENT '批次：如果是优惠券或礼品卡会有批次',
  `gift_level` tinyint(2) NOT NULL DEFAULT '1' COMMENT '兑换所需等级 最低1级',
  `gift_glcoin` decimal(18,0) NOT NULL COMMENT '所需咕币',
  `gift_picture` varchar(100) DEFAULT NULL COMMENT '礼品图片缩略图URL',
  `gift_picture2` varchar(100) DEFAULT NULL COMMENT '礼品详情图URL',
  `in_stock` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  `exchange_nums` int(11) NOT NULL DEFAULT '0' COMMENT '已兑换数量',
  `start_date` datetime NOT NULL COMMENT '活动开始时间',
  `end_date` datetime NOT NULL COMMENT '结束时间',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '礼品状态：1=上线，2=下线',
  `agent_code` varchar(50) DEFAULT NULL,
  `agent_name` varchar(50) DEFAULT NULL,
  `gift_remark` varchar(255) NOT NULL COMMENT '礼品备注',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作员',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gift_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 礼品分类表    （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `gift_category`;
CREATE TABLE `gift_category` (
  `gift_category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '类别ID',
  `category_name` varchar(50) NOT NULL COMMENT '类别名称',
  `category_remark` varchar(255) DEFAULT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '上一级id',
  `opt_id` int(11) DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gift_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- 默认数据
-- ----------------------------
INSERT INTO `gift_category` VALUES ('1', '彩票', '这是彩票', '0', '1', '2017-10-11 09:39:18', '2017-06-28 09:42:10', '2017-12-08 11:32:40');
INSERT INTO `gift_category` VALUES ('2', '数字彩', '测试', '1', '1', '2017-07-06 08:34:30', '2017-06-28 08:10:50', '2017-12-08 11:39:13');
INSERT INTO `gift_category` VALUES ('3', '优惠券', '咕啦优惠券', '0', '33', '2017-12-11 14:15:35', '2017-06-28 08:11:11', '2017-12-11 14:15:09');
INSERT INTO `gift_category` VALUES ('5', '咕啦电子优惠券', '咕啦发行的电子优惠券', '3', '33', '2017-12-11 14:15:49', null, '2017-12-11 14:15:23');

-- ----------------------------
-- 彩种分类表  （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `lottery_category`;
CREATE TABLE `lottery_category` (
  `lottery_category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `cp_category_name` char(20) NOT NULL COMMENT '分类名称',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父级id',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lottery_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COMMENT='彩种分类表';
-- ----------------------------
-- 默认数据
-- ----------------------------
INSERT INTO `lottery_category` VALUES ('1', '全国彩种', '0', null, '2017-12-12 09:14:11', '2017-12-12 09:13:42');
INSERT INTO `lottery_category` VALUES ('2', '地方彩种', '0', null, '2017-12-12 09:14:30', '2017-12-12 09:14:07');
INSERT INTO `lottery_category` VALUES ('3', '高频彩种', '0', null, '2017-12-12 09:14:44', '2017-12-12 09:14:21');
INSERT INTO `lottery_category` VALUES ('4', '全国福利彩种', '1', null, '2017-12-12 09:15:08', '2017-12-12 09:14:45');
INSERT INTO `lottery_category` VALUES ('5', '全国体育彩种', '1', null, '2017-12-12 09:15:24', '2017-12-12 09:15:00');
INSERT INTO `lottery_category` VALUES ('6', '11选5', '3', null, '2017-12-12 09:15:41', '2017-12-12 09:15:18');
INSERT INTO `lottery_category` VALUES ('7', '快3', '3', null, '2017-12-12 09:15:58', '2017-12-12 09:15:35');
INSERT INTO `lottery_category` VALUES ('8', '时时彩', '3', null, '2017-12-12 09:16:15', '2017-12-12 09:15:52');
INSERT INTO `lottery_category` VALUES ('9', '快乐十分', '3', null, '2017-12-12 09:16:28', '2017-12-12 09:16:05');
INSERT INTO `lottery_category` VALUES ('10', '竞技彩彩种', '0', null, '2017-12-21 09:47:10', '2017-12-21 09:46:41');
INSERT INTO `lottery_category` VALUES ('11', '竞彩足球', '10', null, '2017-12-21 09:26:45', '2017-12-21 09:26:09');
INSERT INTO `lottery_category` VALUES ('12', '竞彩篮球', '10', null, '2017-12-21 09:27:05', '2017-12-21 09:26:28');


-- ----------------------------
-- 咕啦币类型表   (已执行)
-- ----------------------------
DROP TABLE IF EXISTS `user_gl_coin`;
CREATE TABLE `user_gl_coin` (
  `user_gl_coin_id` tinyint(4) NOT NULL AUTO_INCREMENT COMMENT '咕啦币类型ID',
  `gl_coin_source` varchar(50) NOT NULL DEFAULT '' COMMENT '咕啦币来源',
  `gl_coin_type` varchar(50) NOT NULL DEFAULT '' COMMENT '咕啦币类型',
  `gl_coin_value` smallint(6) NOT NULL COMMENT '咕啦币数量',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '咕啦币备注',
  `opt_id` tinyint(4) DEFAULT NULL COMMENT '操作人id',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`user_gl_coin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='咕啦币来源分类表';

-- ----------------------------
-- 默认数据
-- ----------------------------
INSERT INTO `user_gl_coin` VALUES ('1', '购彩', '浮动值', '0', '购买彩票送咕啦币', null, '2017-12-01 16:00:46', null);
INSERT INTO `user_gl_coin` VALUES ('2', '出票失败', '浮动值', '0', '出票失败扣除咕啦币', null, '2017-12-01 16:04:38', null);
INSERT INTO `user_gl_coin` VALUES ('3', '抵扣', '浮动值', '0', '抵扣现金', null, '2017-12-01 16:05:38', null);
INSERT INTO `user_gl_coin` VALUES ('4', '兑换礼品', '浮动值', '0', '兑换礼品', null, '2017-12-05 09:35:11', '2017-12-05 09:35:11');
INSERT INTO `user_gl_coin` VALUES ('5', '退款', '浮动值', '0', '订单退款', null, '2017-12-13 16:05:33', null);

-- ----------------------------
-- 咕啦币记录表  （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `user_gl_coin_record`;
CREATE TABLE `user_gl_coin_record` (
  `gl_coin_record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '咕啦币表id',
  `user_id` varchar(15) NOT NULL COMMENT 'user_id',
  `type` tinyint(2) NOT NULL COMMENT '类型：1=收入，2=支出',
  `coin_source` tinyint(2) DEFAULT NULL COMMENT '咕啦币来源：user_gl_coin表id',
  `coin_value` decimal(10,2) NOT NULL COMMENT '新增咕啦币',
  `totle_balance` int(11) DEFAULT NULL COMMENT '新增后总咕啦币余额',
  `multiple` float(3,1) DEFAULT NULL COMMENT '获得咕啦币的倍数',
  `remark` varchar(200) DEFAULT NULL COMMENT '备注',
  `order_code` varchar(50) DEFAULT NULL COMMENT '订单编码',
  `order_source` tinyint(3) DEFAULT NULL COMMENT '来源（1自购、2追号、3赠送、4合买 5、分享 6、计划购买，7=兑换礼品）',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`gl_coin_record_id`),
  KEY `user_id` (`user_id`) USING BTREE COMMENT '根据用户id查找',
  KEY `coin_source` (`coin_source`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3584 DEFAULT CHARSET=utf8mb4 COMMENT='咕啦币明细表';

-- ----------------------------
-- 成长值类型表   （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `user_growth`;
CREATE TABLE `user_growth` (
  `user_growth_id` tinyint(4) NOT NULL AUTO_INCREMENT COMMENT '成长类型ID',
  `growth_source` varchar(50) NOT NULL DEFAULT '' COMMENT '成长值来源',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '成长值类型：1=增加，2=减少',
  `growth_type` varchar(50) DEFAULT '' COMMENT '成长值类型',
  `growth_value` smallint(6) NOT NULL COMMENT '成长值',
  `growth_remark` varchar(200) NOT NULL DEFAULT '' COMMENT '成长值备注',
  `opt_id` tinyint(4) DEFAULT NULL COMMENT '操作人id',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`user_growth_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='成长值来源分类表';

-- ----------------------------
-- 默认数据
-- ----------------------------
INSERT INTO `user_growth` VALUES ('1', '每日签到', '1', '固定值', '10', '每日第一次手动登录后获得', null, '2017-11-30 14:16:31', '2017-11-30 14:16:31');
INSERT INTO `user_growth` VALUES ('2', '购彩', '1', '浮动值', '0', '结算金额', null, '2017-12-01 11:08:49', '2017-12-01 11:08:49');
INSERT INTO `user_growth` VALUES ('3', '购彩天数奖励', '1', '固定值', '100', '自然月内，有3天都进行了购彩且订单状态已完成次月10日发放', null, '2017-11-30 10:23:08', null);
INSERT INTO `user_growth` VALUES ('4', '注册', '1', '固定值', '100', '注册会员一次性奖励', null, '2017-12-01 11:05:18', '2017-12-01 11:05:18');
INSERT INTO `user_growth` VALUES ('5', '完善资料', '1', '固定值', '100', '补全基本资料一次性奖励', null, '2017-12-01 11:05:25', '2017-12-01 11:05:25');
INSERT INTO `user_growth` VALUES ('6', '实名认证', '1', '固定值', '100', '实名认证一次性奖励', null, '2017-12-19 15:26:51', '2017-12-19 15:26:51');
INSERT INTO `user_growth` VALUES ('7', '订单分享', '1', '固定值', '20', '实单分享，一单分享一次', null, '2017-12-01 11:07:41', '2017-12-01 11:07:41');
INSERT INTO `user_growth` VALUES ('8', '微信绑定', '1', '固定值', '100', '微信绑定一次性奖励', null, '2017-12-01 11:08:29', null);
INSERT INTO `user_growth` VALUES ('9', '转盘抽奖', '1', '浮动值', '0', '转盘抽奖活动', null, '2017-12-01 18:06:28', null);
INSERT INTO `user_growth` VALUES ('10', '年末清算', '2', '浮动值', '0', '年末清算', null, '2017-12-05 11:00:09', '2017-12-05 11:00:09');

-- ----------------------------
-- 成长值记录表   （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `user_growth_record`;
CREATE TABLE `user_growth_record` (
  `user_growth_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '成长值记录ID',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '操作类型：1=增加，2=减少',
  `growth_source` varchar(25) NOT NULL COMMENT '成长值来源：user_growth表id:购彩，注册，完善基本资料，实名认证，每天登录，订单分享，微信绑定，抽奖',
  `growth_value` int(11) NOT NULL DEFAULT '0' COMMENT '新增成长值',
  `totle_balance` int(11) DEFAULT NULL COMMENT '新增后总成长值数',
  `growth_remark` varchar(255) DEFAULT NULL COMMENT '操作备注',
  `user_id` int(10) DEFAULT NULL COMMENT 'user_id',
  `levels` tinyint(2) DEFAULT '0' COMMENT '用户当前等级',
  `order_code` varchar(50) DEFAULT NULL COMMENT '订单编号',
  `order_source` tinyint(3) DEFAULT NULL COMMENT '来源（1自购、2追号、3赠送、4合买 5、分享 6、计划购买）',
  `create_time` datetime DEFAULT NULL,
  PRIMARY KEY (`user_growth_id`),
  KEY `user_id` (`user_id`) USING BTREE COMMENT '根据用户id查找',
  KEY `growth_source` (`growth_source`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=368 DEFAULT CHARSET=utf8mb4 COMMENT='成长值明细记录';

-- ----------------------------
-- 等级基础表   （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `user_levels`;
CREATE TABLE `user_levels` (
  `user_level_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '等级ID',
  `level_name` varchar(25) NOT NULL COMMENT '等级名称',
  `level_growth` int(11) NOT NULL DEFAULT '0' COMMENT '所需成长值',
  `cz_integral` int(11) DEFAULT NULL COMMENT '充值送积分值',
  `glcz_discount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '充值成功赠送咕啦币',
  `multiple` float(3,1) DEFAULT '1.0' COMMENT '赠送的咕啦币倍数',
  `raffle_num` tinyint(2) DEFAULT '1' COMMENT '抽奖次数',
  `agent_code` varchar(100) DEFAULT NULL,
  `up_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '升级机制:1正常升级2等级锁定',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人',
  `modify_time` datetime DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_level_id`,`up_status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of user_levels
-- ----------------------------
INSERT INTO `user_levels` VALUES ('1', '初出茅庐', '0', '10', '0.00', '1.0', '1', 'GL888888', '1', '1', '2017-11-29 15:55:34', '2017-07-04 09:11:39', '2017-12-11 16:10:59');
INSERT INTO `user_levels` VALUES ('2', '蒙猜大虾', '3500', '50', '0.00', '1.0', '1', 'GL888888', '1', '33', '2017-12-15 17:54:31', '2017-07-04 09:13:01', '2017-12-15 17:54:04');
INSERT INTO `user_levels` VALUES ('3', '江湖半仙', '10000', '100', '0.02', '1.0', '2', 'GL888888', '1', '33', '2017-12-15 17:54:50', '2017-07-04 09:13:32', '2017-12-20 09:19:37');
INSERT INTO `user_levels` VALUES ('4', '神机妙算', '20000', '1000', '0.03', '1.0', '2', 'GL888888', '1', '33', '2017-12-15 17:55:05', '2017-07-04 09:14:00', '2017-12-20 09:19:38');
INSERT INTO `user_levels` VALUES ('5', '未卜先知', '50000', '2000', '0.05', '1.0', '3', 'GL888888', '1', '33', '2017-12-18 09:26:05', '2017-07-04 09:15:13', '2017-12-20 09:19:41');

-- ----------------------------
-- 签到记录表  （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `user_sgin_record`;
CREATE TABLE `user_sgin_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '签到记录表id',
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `create_time` datetime DEFAULT NULL COMMENT '签到时间',
  `continuous_num` tinyint(3) DEFAULT NULL COMMENT '连续签到数',
  `prize` tinyint(2) DEFAULT NULL COMMENT '签到奖励',
  PRIMARY KEY (`id`),
  KEY `user_id_find` (`user_id`) USING BTREE COMMENT '根据用户id查找'
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;

-- ----------------------------
-- 首页banner图标  （已执行）
-- ----------------------------
DROP TABLE IF EXISTS `bananer`;
CREATE TABLE `bananer` (
  `bananer_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '幻灯片id',
  `pic_name` varchar(50) NOT NULL DEFAULT '' COMMENT '标题名称',
  `pic_url` varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址URL',
  `jump_url` varchar(255) DEFAULT '' COMMENT '跳转URL',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '适用于：1->竞彩首页 2->发现界面',
  `status` tinyint(4) DEFAULT '2' COMMENT '使用状态:1->在线 2->下线',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人',
  PRIMARY KEY (`bananer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;

--用户表等级字段
ALTER TABLE `user`
MODIFY COLUMN `level_name`  varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '初出茅庐' COMMENT '等级名称' AFTER `from_id`,
MODIFY COLUMN `level_id`  tinyint(3) NOT NULL DEFAULT 1 COMMENT '等级ID' AFTER `level_name`;
