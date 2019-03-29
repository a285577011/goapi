/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  GLC-ZYL
 * Created: 2018-1-3
 */

--足球欧赔表新增字段  （张悦玲）（已执行）
Alter table `europe_odds` Add COLUMN odds_3_trend TINYINT(4) DEFAULT 0 COMMENT '升降状态 0：不变 1：上升 -1：下降' AFTER `odds_3`;
Alter table `europe_odds` Add COLUMN odds_1_trend TINYINT(4) DEFAULT 0 COMMENT '升降状态 0：不变 1：上升 -1：下降' AFTER `odds_1`;
Alter table `europe_odds` Add COLUMN odds_0_trend TINYINT(4) DEFAULT 0 COMMENT '升降状态 0：不变 1：上升 -1：下降' AFTER `odds_0`;

--足球亚赔表新增字段  （张悦玲）（已执行）
Alter table `asian_handicap` Add COLUMN home_discount_trend TINYINT(4) DEFAULT 0 COMMENT '升降状态 0：不变 1：上升 -1：下降' AFTER `home_discount`;
Alter table `asian_handicap` Add COLUMN visit_discount_trend TINYINT(4) DEFAULT 0 COMMENT '升降状态 0：不变 1：上升 -1：下降' AFTER `visit_discount`;

