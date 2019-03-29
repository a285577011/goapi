/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2017-10-26
 */
--篮球赛果表新增字段 (张悦玲)
ALTER TABLE schedule_remind ADD schedule_type TINYINT(4) NULL DEFAULT 1 COMMENT '1：足球 2：篮球' ;

--文章新增排序置顶字段 （李家能）
ALTER TABLE expert_articles ADD stick VARCHAR(20) NOT NULL DEFAULT 0  COMMENT '是否置顶：0否 时间戳是' ;

--详情表新增字段 （张悦玲）
ALTER TABLE betting_detail ADD fen_json VARCHAR(200) NULL  COMMENT '（篮球）让分，预测总分的json串' ;

--新增球队所属位置
ALTER TABLE team ADD team_position TINYINT(4) NULL  COMMENT '球队所属方位 1：东部 2：西部';