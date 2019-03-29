/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-11-27
 */

--文章期数表新增字段  （张悦玲）
ALTER TABLE `gl_lottery_php`.`articles_periods` ADD COLUMN `endsale_time` datetime DEFAULT NULL COMMENT '停售时间' AFTER `start_time`;

--专家表新增字段 （张悦玲）
ALTER TABLE `gl_lottery_php`.`expert` ADD COLUMN `two_red_nums` INT(11) DEFAULT 0 COMMENT '两场连红数' AFTER `day_red_time`;
ALTER TABLE `gl_lottery_php`.`expert` ADD COLUMN `three_red_nums` INT(11) DEFAULT 0 COMMENT '三场连红数' AFTER `two_red_nums`;
ALTER TABLE `gl_lottery_php`.`expert` ADD COLUMN `five_red_nums` INT(11) DEFAULT 0 COMMENT '五场连红数' AFTER `day_red_time`;

--胜负彩（任14.任9）新增字段 （张悦玲）
ALTER TABLE `gl_lottery_php`.`optional_schedule` ADD COLUMN `odds_win` FLOAT(8,2) DEFAULT 0 COMMENT '胜赔率' AFTER `visit_short_name`;
ALTER TABLE `gl_lottery_php`.`optional_schedule` ADD COLUMN `odds_flat` FLOAT(8,2) DEFAULT 0 COMMENT '平赔率' AFTER `odds_win`;
ALTER TABLE `gl_lottery_php`.`optional_schedule` ADD COLUMN `odds_lose` FLOAT(8,2) DEFAULT 0 COMMENT '负赔率' AFTER `odds_flat`;