/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-10-18
 */

--文章表新增字段  （张悦玲）
ALTER TABLE `expert_articles`
MODIFY COLUMN `articles_code`  varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '文章编号' AFTER `expert_articles_id`;