/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 10 12

-- 新建会员付费文章表 （张悦玲）
CREATE TABLE `user_article` (
  `user_article_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_article_code` varchar(100) NOT NULL COMMENT '订单编号',
  `user_id` int(11) NOT NULL COMMENT '会员ID',
  `article_id` int(11) NOT NULL COMMENT '文章ID',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '支付状态 0：未支付 1：已支付 2：退款成功 3：退款失败 4:已收款',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_article_id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4;

-- 新建会员关注专家表 （张悦玲）
CREATE TABLE `user_expert` (
  `user_expert_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '会员ID',
  `expert_id` int(11) NOT NULL COMMENT '专家ID',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '关注状态 1：关注 2：取消关注',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_expert_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4;


