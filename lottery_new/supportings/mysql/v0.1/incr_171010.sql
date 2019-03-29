/*!40101 SET NAMES utf8 */;
-- gl_lottery_php 服务器 v1.0 修改
-- 2017 10 10

-- 新建专家表 （张悦玲）
CREATE TABLE `expert` (
  `expert_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '专家表ID',
  `user_id` int(11) NOT NULL COMMENT 'user表ID ',
  `cust_no` varchar(15) NOT NULL COMMENT '会员编号',
  `introduction` varchar(1000) DEFAULT NULL COMMENT '专家简介',
  `article_nums` int(11) DEFAULT '0' COMMENT '文章数量',
  `fans_nums` int(11) DEFAULT '0' COMMENT '粉丝数量',
  `read_nums` int(11) DEFAULT '0' COMMENT '阅读数量',
  `lottery` int(11) DEFAULT '1' COMMENT '擅长彩种 1：足彩',
  `even_red_nums` int(11) DEFAULT '0' COMMENT '最近连红数',
  `identity` int(11) DEFAULT '1' COMMENT '认证身份 1：足球评论员',
  `month_red_nums` int(11) DEFAULT '0' COMMENT '月红单数',
  `day_nums` int(11) DEFAULT '0' COMMENT '近七天发表文章数',
  `day_red_nums` int(11) DEFAULT '0' COMMENT '近七天红单数',
  `expert_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1：待审核 2：认证成功 3：认证失败',
  `pact_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '协议状态 1：未签 2：正常 :3：失效',
  `expert_type` int(11) DEFAULT NULL COMMENT '专家类型身份',
  `expert_type_name` varchar(50) DEFAULT NULL COMMENT '专家类型身份名称',
  `remark` varchar(100) DEFAULT NULL COMMENT '审核原因',
  `opt_id` int(11) DEFAULT NULL COMMENT '审核人ID',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `create_time` datetime DEFAULT NULL COMMENT '申请时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`expert_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;

-- 新建专家发布文章表 （张悦玲）
CREATE TABLE `expert_articles` (
  `expert_articles_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '文章ID',
  `user_id` int(11) NOT NULL COMMENT '专家ID',
  `article_type` tinyint(4) DEFAULT '1' COMMENT '文章类型 1：足球赛事 ',
  `article_title` varchar(200) DEFAULT NULL COMMENT '文章标题',
  `pay_type` tinyint(4) DEFAULT '1' COMMENT '付费类型 1:免费 2：付费',
  `pay_money` decimal(18,0) DEFAULT '0' COMMENT '付费金额',
  `article_content` longtext COMMENT '文章内容',
  `article_status` tinyint(4) DEFAULT '1' COMMENT '1:草稿 2：待审核 3：上线 4：下线 5:审核失败 ',
  `result_status` tinyint(4) DEFAULT '1' COMMENT '猜测结果 1:待开 2：黑单 3：红单 ',
  `buy_nums` int(11) DEFAULT '0' COMMENT '购买数量',
  `read_nums` int(11) DEFAULT '0' COMMENT '阅读数',
  `remark` varchar(100) DEFAULT NULL COMMENT '审核原因',
  `article_remark` varchar(200) DEFAULT NULL COMMENT '审核备注',
  `buy_back` tinyint(4) DEFAULT '1' COMMENT '金额是否返还 1：未中返还 0：未中不返还',
  `deal_status` tinyint(4) DEFAULT '1' COMMENT '是否清算 1：未清算 2：已清算',
  `opt_id` int(11) DEFAULT NULL COMMENT '审核人员',
  `cutoff_time` varchar(100) DEFAULT NULL COMMENT '截止时间戳',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `create_time` datetime DEFAULT NULL COMMENT '发布时间',
  `modify_time` datetime DEFAULT NULL COMMENT '修改时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`expert_articles_id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4;

-- 新建文章期数表 （张悦玲）
CREATE TABLE `articles_periods` (
  `articles_periods_id` int(11) NOT NULL AUTO_INCREMENT,
  `articles_id` int(11) NOT NULL COMMENT '文章ID',
  `periods` varchar(100) NOT NULL COMMENT '期数、场次',
  `lottery_code` varchar(100) NOT NULL COMMENT '彩种。玩法',
  `schedule_code` varchar(100) DEFAULT NULL,
  `league_id` int(11) DEFAULT NULL COMMENT '联赛ID',
  `league_short_name` varchar(100) DEFAULT NULL,
  `home_short_name` varchar(100) DEFAULT NULL COMMENT '主队名',
  `visit_short_name` varchar(100) DEFAULT NULL COMMENT '客队名',
  `home_team_rank` int(11) DEFAULT NULL COMMENT '主队联赛排名',
  `visit_team_rank` int(11) DEFAULT NULL COMMENT '客队联赛排名',
  `home_team_img` varchar(100) DEFAULT NULL COMMENT '主队图片',
  `visit_team_img` varchar(100) DEFAULT NULL COMMENT '客队图片',
  `rq_nums` int(11) DEFAULT NULL COMMENT '让球数',
  `start_time` datetime DEFAULT NULL COMMENT '比赛开始时间',
  `pre_result` varchar(100) NOT NULL COMMENT '预测结果',
  `pre_odds` varchar(11) DEFAULT NULL COMMENT '预测结果赔率',
  `featured` tinyint(4) DEFAULT '2' COMMENT '主推 0：负主推 1：平主推 2：非主推 3：胜主推',
  `status` tinyint(4) DEFAULT '1' COMMENT '处理状态 1：待开奖 2：中 3：未中',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`articles_periods_id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4;

