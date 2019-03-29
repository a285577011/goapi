/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-12-15
 */

--专家表expert新增字段 （张悦玲）（已执行）
ALTER TABLE `gl_lottery_php`.`expert` ADD COLUMN `max_even_red` INT(11) DEFAULT 0 COMMENT '最高连红' AFTER `five_red_nums`;

--用户表user新增字段 （kevi）（已执行）
ALTER TABLE `gl_lottery_php`.`user` ADD COLUMN `from_id` INT(11) DEFAULT 0 COMMENT '注册来源id' AFTER `register_from`;

ALTER TABLE `gl_lottery_php`.`user` ADD COLUMN `level_name` varchar(100) DEFAULT '初出茅庐' COMMENT '等级名称';

ALTER TABLE `user` MODIFY COLUMN `level_id`  tinyint(3) NOT NULL DEFAULT 1 COMMENT '等级ID' AFTER `level_name`;