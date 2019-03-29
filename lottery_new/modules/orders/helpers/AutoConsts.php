<?php

namespace app\modules\orders\helpers;

class AutoConsts {
    
    // 自动出票对应的串关编号
    const AUTO_CHUAN = [
        "1" => "500",
        "2" => "502",
        "3" => "503",
        "4" => "526",
        "5" => "527",
        "6" => "504",
        "7" => "539",
        "8" => "540",
        "9" => "528",
        "10" => "529",
        "11" => "505",
        "12" => "544",
        "13" => "545",
        "14" => "530",
        "15" => "541",
        "16" => "531",
        "17" => "532",
        "18" => "506",
        "19" => "549",
        "20" => "550",
        "21" => "533",
        "22" => "531",
        "23" => "546",
        "24" => "534",
        "25" => "543",
        "26" => "535",
        "27" => "536",
        "28" => "507",
        "29" => "553",
        "30" => "554",
        "31" => "551",
        "32" => "547",
        "33" => "537",
        "35" => "508",
        "36" => "556",
        "37" => "557",
        "38" => "555",
        "39" => "552",
        "40" => "548",
        "41" => "538"
    ];
    
    
    // 自动出票对应的彩种编号
    const AUTO_PLAY = [
        '3001' => 'BSK001',  // 竞篮胜负
        '3002' => 'BSK002',  // 竞篮让球胜负
        '3003' => 'BSK003',  // 竞篮胜分差
        '3004' => 'BSK004',  // 竞篮大小分
        '3005' => 'BSK005',  // 竞篮混合投注
        '3006' => 'FT006',   // 竞足让球胜平负
        '3007' => 'FT002',   // 竞足比分
        '3008' => 'FT003',   // 竞足总进球数
        '3009' => 'FT004',   // 竞足半全场胜平负
        '3010' => 'FT001',   // 竞足胜平负
        '3011' => 'FT005',   // 竞足混合投注
        '4001' => 'D14',     // 任选14
        '4002' => 'D9',      // 任选9
        '2001' => 'T001',    // 大乐透
        '2002' => 'D3',      // 排列三
        '2003' => 'D5',      // 排列五
        '2004' => 'D7',       // 七星彩
        '301201' => 'GYJ001', //竞彩冠军
        '301301' => 'GYJ002'  // 竞彩冠亚军
    ];
    
    //自动出票玩法对应的投注赔率字段
    const AUTO_ODDS_FIELD = [
        'BSK001' => 'vs',// 竞篮胜负
        'BSK002' => 'letVs',// 竞篮让球胜负
        'BSK003' => 'diff',// 竞篮胜分差
        'BSK004' => 'bs',// 竞篮大小分
        'FT001' => 'vs',// 竞足胜平负
        'FT002' => 'score',// 竞足比分
        'FT003' => 'goal',// 竞足总进球数
        'FT004' => 'half',// 竞足半全场胜平负
        'FT006' => 'letVs',// 竞足让球胜平负
        'GYJ001' => 'gyjodd',//竞彩冠军
        'GYJ002' => 'gyjodd'//竞彩冠亚军
    ];
    
    //自动出票投注结果对应的赔率字段
    const AUTO_ODDS_PLAY = [
        'BSK001' => ['3' => 'v3', '0' => 'v0', 'rf_nums' => 'letPoint'],
        'BSK002' => ['3' => 'v3', '0' => 'v0', 'rf_nums' => 'letPoint'],
        'BSK003' => ['01' => 'v01', '02' => 'v02', '03' => 'v03', '04' => 'v04', '05' => 'v05', '06' => 'v06', '11' => 'v11', '12' => 'v12', '13' => 'v13', '14' => 'v14', '15' => 'v15', '16' => 'v16'],
        'BSK004' => ['1' => 'g', '2' => 'l', 'fen_cutoff' => 'basePoint'],
        'FT001' => ['3' => 'v3', '1' => 'v1', '0' => 'v0', 'rf_nums' => 'letPoint'],
        'FT002' => ['10' => 'v10', '20' => 'v20', '21' => 'v21', '30' => 'v30', '31' => 'v31', '32' => 'v32', '40' => 'v40', '41' => 'v41', '42' => 'v42', '50' => 'v50', '51' => 'v51', '52' => 'v52','90' => 'v90',
                   '00' => 'v00', '11' => 'v11', '22' => 'v22', '33' => 'v33', '99' => 'v99',
                   '01' => 'v01', '02' => 'v02', '12' => 'v12', '03' => 'v03', '13' => 'v13', '23' => 'v23', '04' => 'v04', '14' => 'v14', '24' => 'v24', '05' => 'v05', '15' => 'v15', '25' => 'v25', '09' => 'v09'],
        'FT003' => ['0' => 'v0', '1' => 'v1', '2' => 'v2', '3' => 'v3', '4' => 'v4', '5' => 'v5', '6' => 'v6', '7' => 'v7'],
        'FT004' => ['33' => 'v33', '31' => 'v31', '30' => 'v30', '13' => 'v13', '11' => 'v11', '10' => 'v10', '03' => 'v03', '01' => 'v01', '00' => "v00"],
        'FT006' => ['3' => 'v3', '1' => 'v1',  '0' => 'v0', 'rf_nums' => 'letPoint'],
    ];
    
    //joylott 11X5玩法编码
    const JOYLOTT_ELEVEN_PLAYCODE = [
        '2007' => ['200702' => '22001', '200703' => '22003', '200704' => '22005', '200705' => '22007', '200706' => '22009', '200707' => '22011', '200708' => '22013',
            '200712' => '22001', '200713' => '22003', '200714' => '22005', '200715' => '22007', '200716' => '22009', '200717' => '22011', '200718' => '22013',
            '200722' => '22002', '200723' => '22004', '200724' => '22006', '200725' => '22008', '200726' => '22010', '200727' => '22012', '200728' => '22013',
            '200731' => '22014', '200732' => '22015', '200733' => '22018', '200734' => '22016', '200735' => '22019', '200741' => '22014', '200742' => '22015',
            '200743' => '22018', '200744' => '22016', '200745' => '22019', '200754' => '22017', '200755' => '22020'],
        '2010' => ['201002' => '23001', '201003' => '23003', '201004' => '23005', '201005' => '23007', '201006' => '23009', '201007' => '23011', '201008' => '23013',
            '201012' => '23001', '201013' => '23003', '201014' => '23005', '201015' => '23007', '201016' => '23009', '201017' => '23011', '201018' => '23013',
            '201022' => '23002', '201023' => '23004', '201024' => '23006', '201025' => '23008', '201026' => '23010', '201027' => '23012', '201028' => '23013',
            '201031' => '23014', '201032' => '23015', '201033' => '23018', '201034' => '23016', '201035' => '23019', '201041' => '23014', '201042' => '23015',
            '201043' => '23018', '201044' => '23016', '201045' => '23019', '201054' => '23017', '201055' => '23020'],
    ];
    
    //joylott 11X5 不支持玩法
    const JOYLOTT_NOTOUT = [
        '2010' => ['201018', '201028', '201041'],
    ];
    
    //JOYLOTT 彩种ID
    const JOYLOTT_GAMEID = [
        '1001' => '4', //双色球
        '1002' => '6', //福彩3D
        '1003' => '7', // 七乐彩
        '2001' => '2001', //大乐透
        '2002' => '2003', // 排列三
        '2003' => '2004', //排列五
        '2004' => '2002', // 七星彩
        '2007' => '2005', // 山东11X5
        '2010' => '2008', // 湖北11X5
        '3000' => '2010', //竞足
        '3100' => '2011', //竞篮
        '4000' => '2006', //胜负彩
    ];
    
    //JOYLOTT 竞彩玩法编号
    const JOYLOTT_ZL_PLAY = [
        '3001' => '10001',  // 竞篮胜负
        '3002' => '10002',  // 竞篮让球胜负
        '3003' => '10003',  // 竞篮胜分差
        '3004' => '10004',  // 竞篮大小分
        '3005' => '10005',  // 竞篮混合投注
        '3006' => '9001',   // 竞足让球胜平负
        '3007' => '9003',   // 竞足比分
        '3008' => '9002',   // 竞足总进球数
        '3009' => '9004',   // 竞足半全场胜平负
        '3010' => '9005',   // 竞足胜平负
        '3011' => '9006',   // 竞足混合投注
        '4001' => '6001',     // 任选14
        '4002' => '6002',      // 任选9
        '301201' => 'GYJ001', //竞彩冠军
        '301301' => 'GYJ002'  // 竞彩冠亚军
    ];
    

    //JOYLOTT 11X5 玩法编号        
    const JOYLOTT_PCODE = [
        '2007' => '220',
        '2010' => '230'
    ];
    
    /**
     * 糯米非自由过关
     */
    const NM_SCHE_COUNT = [
        1 => ['1001'],
        2 => ['2001'],
        3 => ['3001', '3003', '3004'],
        4 => ['4001', '4004', '4005', '4006', '4011'],
        5 => ['5001', '5005', '5006', '5010', '5016', '5020', '5026'],
        6 => ['6001', '6006', '6007', '6015', '6020', '6022', '6035', '6042', '6050', '6057'],
        7 => ['7001', '7007', '7008', '7021', '7035', '7120'],
        8 => ['8001', '8008', '8009', '8028', '8056', '8070', '8247']
    ];
}
