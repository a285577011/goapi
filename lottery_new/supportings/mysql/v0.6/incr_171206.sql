/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-gwp
 * Created: 2017-12-6
 */

--微信通知表添加成功失败字段 (龚伟平)已执行
ALTER TABLE `wx_msg_record`
ADD COLUMN `status`  tinyint(2) DEFAULT NULL COMMENT '1=成功，2=失败' AFTER `status`;

--修改exchange_record表（龚伟平）
ALTER TABLE `exchange_record`
MODIFY COLUMN `opt_name`  varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '操作人昵称' AFTER `agent_name`;


--球队对阵历史表新增字段 （张悦玲）已执行
ALTER TABLE `gl_lottery_php`.`history_count` ADD COLUMN `home_team_league` VARCHAR(50) DEFAULT NULL, COMMENT '主队所属联赛' AFTER `visit_team_rank`;
ALTER TABLE `gl_lottery_php`.`history_count` ADD COLUMN `visit_team_league` VARCHAR(50) DEFAULT NULL, COMMENT '客队所属联赛' AFTER `home_team_league`;