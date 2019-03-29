--新增专家表字段(hyy) (已执行)
ALTER TABLE `expert`
	ADD COLUMN `lan_article_nums` INT(11) NOT NULL DEFAULT '0' AFTER `update_time`,
	ADD COLUMN `lan_read_nums` INT(11) NOT NULL DEFAULT '0' AFTER `lan_article_nums`,
	ADD COLUMN `lan_even_red_nums` INT(11) NOT NULL DEFAULT '0' COMMENT '最近连红数(篮球)' AFTER `lan_read_nums`,
	ADD COLUMN `lan_even_back_nums` INT(11) NOT NULL DEFAULT '0' COMMENT '最近连黑数(篮球)' AFTER `lan_even_red_nums`,
	ADD COLUMN `lan_month_red_nums` INT(11) NOT NULL DEFAULT '0' COMMENT '月红单(篮球)' AFTER `lan_even_back_nums`,
	ADD COLUMN `lan_day_nums` INT(11) NOT NULL DEFAULT '0' AFTER `lan_month_red_nums`,
	ADD COLUMN `lan_day_red_nums` INT(11) NOT NULL DEFAULT '0' AFTER `lan_day_nums`,
	ADD COLUMN `lan_two_red_nums` INT(11) NOT NULL DEFAULT '0' AFTER `lan_day_red_nums`,
	ADD COLUMN `lan_three_red_nums` INT(11) NOT NULL DEFAULT '0' AFTER `lan_two_red_nums`,
	ADD COLUMN `lan_five_red_nums` INT(11) NOT NULL DEFAULT '0' AFTER `lan_three_red_nums`,
	ADD COLUMN `lan_max_even_red` INT(11) NOT NULL DEFAULT '0' AFTER `lan_five_red_nums`;
SELECT `DEFAULT_COLLATION_NAME` FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME`='gl_lottery_php';
ALTER TABLE `expert`
	CHANGE COLUMN `identity` `identity` INT(11) NULL DEFAULT '0' COMMENT '认证身份 1：足球评论员' AFTER `even_back_nums`;
	ALTER TABLE `expert`
	ADD INDEX `user_id` (`user_id`);

--文章赛程表新增字段（张悦玲）（已执行）
ALTER TABLE `articles_periods` ADD COLUMN `fen_cutoff` DECIMAL(18,2) NULL COMMENT '切分点' AFTER `rq_nums`;
--用户数据统计表
CREATE TABLE `user_statistics` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`cust_no` VARCHAR(15) NOT NULL,
	`order_money` DECIMAL(12,2) NOT NULL DEFAULT '0.00' COMMENT '普通订单交易金额',
	`pro_order_money` DECIMAL(12,2) NOT NULL DEFAULT '0.00' COMMENT '合买订单交易金额',
	`order_num` INT(11) NOT NULL DEFAULT '0' COMMENT '普通订单交易数量',
	`pro_order_num` INT(11) NOT NULL DEFAULT '0' COMMENT '合买订单交易数量',
	`u_time` INT(11) NOT NULL DEFAULT '0',
	`c_time` INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	INDEX `cust_no` (`cust_no`)
)
COMMENT='用户统计表(每日更新一次)'
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

--文章表新增索引（张悦玲）
ALTER TABLE `expert_articles` ADD INDEX `user_id` (`user_id`);
ALTER TABLE `expert_articles` ADD INDEX `user_id` (`user_id`);
--文章期数表新增索引 （张悦玲）
ALTER TABLE `articles_periods` ADD INDEX `articles_id` (`articles_id`);
ALTER TABLE `articles_periods` ADD INDEX `periods` (`periods`);


--文章期数表rq_nums,home_team_rank,visit_team_rank修改类型
Alter table articles_periods change rq_nums rq_nums varchar(25);
alter table articles_periods change home_team_rank home_team_rank varchar(25);
alter table articles_periods change visit_team_rank visit_team_rank varchar(25);