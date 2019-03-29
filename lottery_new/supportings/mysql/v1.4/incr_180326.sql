--新增流量用户申请表 (陈启炜)(已执行)
CREATE TABLE `api_user_apply` (
  `api_user_apply_id` int(11) NOT NULL AUTO_INCREMENT,
  `apply_code` varchar(45) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `cust_no` varchar(45) NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT '类型 1：充值  2：提现',
  `money` decimal(18,2) NOT NULL COMMENT '金额',
  `voucher_pic` varchar(200) DEFAULT NULL COMMENT '凭证图片',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态 1：审核中 2：通过 3：失败',
  `api_user_bank_id` int(11) DEFAULT NULL COMMENT '提现银行卡信息',
  `remark` varchar(200) DEFAULT NULL COMMENT '申请备注',
  `refuse_reson` varchar(200) DEFAULT NULL COMMENT '拒绝理由',
  `opt_id` int(11) DEFAULT NULL COMMENT '操作人',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  PRIMARY KEY (`api_user_apply_id`),
  KEY `index2` (`user_id`),
  KEY `index3` (`cust_no`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;

--新增流量用户银行信息表 (陈启炜)(已执行)
CREATE TABLE `api_user_bank` (
  `api_user_bank_id` int(11) NOT NULL AUTO_INCREMENT,
  `bussiness_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(45) DEFAULT NULL COMMENT '姓名',
  `bank_open` varchar(45) DEFAULT NULL COMMENT '开户行',
  `branch` varchar(100) DEFAULT NULL COMMENT '支行',
  `card_number` varchar(45) DEFAULT NULL COMMENT '银行卡',
  `province` varchar(45) DEFAULT NULL COMMENT '省份',
  `city` varchar(45) DEFAULT NULL COMMENT '城市',
  `is_default` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否默认 0不是 1是',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1：可用  0：不可用',
  PRIMARY KEY (`api_user_bank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4;

