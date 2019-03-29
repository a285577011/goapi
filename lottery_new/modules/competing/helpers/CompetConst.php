<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\modules\competing\helpers;

class CompetConst {
    
    /**
     * 篮球赛程投注玩法
     */
    
    const LAN_SCHEDULE_PLAY = [
        '3001' => 'schedule_sf',
        '3002' => 'schedule_rfsf',
        '3003' => 'schedule_sfc',
        '3004' => 'schedule_dxf'
    ];
    
    //定制竞彩篮球
    const MADE_BASKETBALL_LOTTERY = [
        '0' => '3001',
        '1' => '3002',
        '2' => '3003',
        '3' => '3004',
        '4' => '3005'
    ];
    
    //胜分差 实际名
    const SFC_BETWEEN_ARR = [
        '01' => '主胜 1-5',
        '02' => '主胜 6-10',
        '03' => '主胜 11-15',
        '04' => '主胜 16-20',
        '05' => '主胜 21-25',
        '06' => '主胜 26+',
        '11' => '客胜 1-5',
        '12' => '客胜 6-10',
        '13' => '客胜 11-15',
        '14' => '客胜 16-20',
        '15' => '客胜 21-25',
        '16' => '客胜 26+'
    ];
    
    //竞彩组合过关
    const M_CHUAN_N =[
        '4' => '2',
        '5' => '2,3',
        '7' => '3',
        '8' => '3,6',
        '9' => '2',
        '10' => '2,3,6',
        '12' => '6',
        '13' => '6,11',
        '14' => '2',
        '15' => '3,6,11',
        '16' => '2,3',
        '17' => '2,3,6,11',
        '19' => '11',
        '20' => '11,18',
        '21' => '2',
        '22' => '3',
        '23' => '6,11,18',
        '24' => '2,3',
        '25' => '3,6,11,18',
        '26' => '2,3,6',
        '27' => '2,3,6,11,18',
        '29' => '18',
        '30' => '18,28',
        '31' => '11',
        '32' => '6',
        '33' => '2,3,6,11,18,28',
        '36' => '28',
        '37' => '28,35',
        '38' => '18',
        '39' => '11',
        '40' => '6',
        '41' => '2,3,6,11,18,28,35',
        '42' => '1,2',
        '43' => '1,2,3',
        '44' => '1,2,3,6',
        '45' => '1,2,3,6,11',
        '46' => '1,2,3,6,11,18'
    ];
    
    //奖金优化的方式
    const MAJOR_ARR = [0 => '无优化', 1 => '平均优化', 2 => '博热优化', 3 => '博冷优化'];
    
    //玩法对应投注内容名
    const SCHEDULE_PLAY = [
        '3001' => ['0' => '负', '3' => '胜'],
        '3002' => ['0' => '【让分】负', '3' => '【让分】胜'],
        '3003' => ['01' => '主胜 1-5', '02' => '主胜 6-10', '03' => '主胜 11-15', '04' => '主胜 16-20', '05' => '主胜 21-25', '06' => '主胜 26+', '11' => '客胜 1-5', '12' => '客胜 6-10', '13' => '客胜 11-15',
                   '14' => '客胜 16-20', '15' => '客胜 21-25', '16' => '客胜 26+'],
        '3004' => ['1' => '大分', '2' => '小分'],
        '3006' => ['0' => '【让球】负', '1' => '【让球】平', '3' => '【让球】胜'],
        '3007' => ['10' => '1:0', '20' => '2:0', '21' => '2:1', '30' => '3:0', '31' => '3:1', '32' => '3:2', '40' => '4:0', '41' => '4:1', '42' => '4:2', '50' => '5:0', '51' => '5:1', '52' => '5:2','90' => '胜其他',
                   '00' => '0:0', '11' => '1:1', '22' => '2:2', '33' => '3:3', '99' => '平其他',
                   '01' => '0:1', '02' => '0:2', '12' => '1:2', '03' => '0:3', '13' => '1:3', '23' => '2:3', '04' => '0:4', '14' => '1:4', '24' => '2:4', '05' => '0:5', '15' => '1:5', '25' => '2:5', '09' => '负其他'],
        '3008' => ['0' => '0球', '1' => '1球', '2' => '2球', '3' => '3球', '4' => '4球', '5' => '5球', '6' => '6球', '7' => '7+球',],
        '3009' => ['33' => '胜胜', '31' => '胜平', '30' => '胜负', '13' => '平胜', '11' => '平平', '10' => '平负', '03' => '负胜', '01' => '负平', '00' => "负负"],
        '3010' => ['0' => '负', '1' => '平', '3' => '胜'],
        '5001' => ['0' => '负', '1' => '平', '3' => '胜'],
        '5002' => ['0' => '0球', '1' => '1球', '2' => '2球', '3' => '3球', '4' => '4球', '5' => '5球', '6' => '6球', '7' => '7+球'],
        '5003' => ['33' => '胜胜', '31' => '胜平', '30' => '胜负', '13' => '平胜', '11' => '平平', '10' => '平负', '03' => '负胜', '01' => '负平', '00' => "负负"],
        '5004' => ['1' => '上单', '2' => '上双', '3' => '下单', '4' => '下双'],
        '5005' => ['10' => '1:0', '20' => '2:0', '21' => '2:1', '30' => '3:0', '31' => '3:1', '32' => '3:2', '40' => '4:0', '41' => '4:1', '42' => '4:2', '90' => '胜其他',
                   '00' => '0:0', '11' => '1:1', '22' => '2:2', '33' => '3:3', '99' => '平其他',
                   '01' => '0:1', '02' => '0:2', '12' => '1:2', '03' => '0:3', '13' => '1:3', '23' => '2:3', '04' => '0:4', '14' => '1:4', '24' => '2:4', '09' => '负其他'],
        '5006' => ['0' => '负', '3' => '胜'],
    ];
    
    //场次数对应的M串N
    const COUNT_MCN = [
        3 => ['4' => '2', '5' => '2,3'],
        4 => ['7' => '3', '8' => '3,6', '9' => '2', '10' => '2,3,6'],
        5 => ['12' => '6', '13' => '6,11', '14' => '2', '15' => '3,6,11', '16' => '2,3', '17' => '2,3,6,11'],
        6 => ['19' => '11', '20' => '11,18', '21' => '2', '22' => '3', '23' => '6,11,18', '24' => '2,3', '25' => '3,6,11,18', '26' => '2,3,6', '27' => '2,3,6,11,18'],
        7 => [ '29' => '18', '30' => '18,28', '31' => '11', '32' => '6', '33' => '2,3,6,11,18,28'],
        8 => ['36' => '28', '37' => '28,35', '38' => '18', '39' => '11', '40' => '6', '41' => '2,3,6,11,18,28,35']
    ];
    
    //定制北单
    const MADE_BD_LOTTERY = [
        '0' => '5001',
        '1' => '5002',
        '2' => '5003',
        '3' => '5004',
        '4' => '5005',
        '6' => '5006'
    ];
    
    //北单M串N
    const BD_MCN = [
        '0101' => '单关',
        '0201' => '2串1',
        '0203' => '2串3',
        '0301' => '3串1',
        '0304' => '3串4',
        '0307' => '3串7',
        '0401' => '4串1',
        '0405' => '4串5',
        '0411' => '4串11',
        '0415' => '4串15',
        '0501' => '5串1',
        '0506' => '5串6',
        '0516' => '5串16',
        '0526' => '5串25',
        '0531' => '5串31',
        '0601' => '6串1',
        '0607' => '6串7',
        '0622' => '6串22',
        '0642' => '6串42',
        '0657' => '6串57',
        '0663' => '6串63',
        '0701' => '7串1',
        '0801' => '8串1',
        '0901' => '9串1',
        '1001' => '10串1',
        '1101' => '11串1',
        '1201' => '12串1',
        '1301' => '13串1',
        '1401' => '14串1',
        '1501' => '15串1'
    ];


    //北单相关玩法串关
    const BD_CHUAN = [
        '5001' => ['0101', '0201', '0203', '0301', '0304', '0307', '0401', '0405', '0411', '0415', '0501', '0506', '0516', '0526', '0531', '0601', '0607', '0622', '0642', '0657', '0663', '0701', '0801', '0901', '1001', '1101', '1201', '1301', '1401', '1501'],
        '5002' => ['0101', '0201', '0203', '0301', '0304', '0307', '0401', '0405', '0411', '0415', '0501', '0506', '0516', '0526', '0531', '0601', '0607', '0622', '0642', '0657', '0663'],
        '5003' => ['0101', '0201', '0203', '0301', '0304', '0307', '0401', '0405', '0411', '0415', '0501', '0506', '0516', '0526', '0531', '0601', '0607', '0622', '0642', '0657', '0663'],
        '5004' => ['0101', '0201', '0203', '0301', '0304', '0307', '0401', '0405', '0411', '0415', '0501', '0506', '0516', '0526', '0531', '0601', '0607', '0622', '0642', '0657', '0663'],
        '5005' => ['0101', '0201', '0203', '0301', '0304', '0307'],
        '5006' => ['0301', '0401', '0405', '0501', '0506', '0516', '0601', '0607', '0622', '0657', '0701', '0801', '0901', '1001', '1101', '1201', '1301', '1401', '1501']
    ];
    
    const BD_SCHEDULE_PLAY = [
        '5001' => 'spf_status',
        '5002' => 'zjqs_status',
        '5003' => 'bqc_status',
        '5004' => 'sxpds_status',
        '5005' => 'dcbf_status',
        '5006' => 'sfgg_status'
    ];
    
    //北单组合过关
    const BD_M_CHUAN_N =[
        '0203' => '0101,0201',
        '0304' => '0201,0301',
        '0307' => '0101,0201,0301',
        '0405' => '0301,0401',
        '0411' => '0201,0301,0401',
        '0415' => '0101,0201,0301,0401',
        '0506' => '0401,0501',
        '0516' => '0301,0401,0501',
        '0526' => '0201,0301,0401,0501',
        '0531' => '0101,0201,0301,0401,0501',
        '0607' => '0501,0601',
        '0622' => '0401,0501,0601',
        '0642' => '0301,0401,0501,0601',
        '0657' => '0201,0301,0401,0501,0601',
        '0663' => '0101,0201,0301,0401,0501,0601'
    ];
    
    const BD_BF =  [
        '3' => ["1:0", "2:0", "2:1", "3:0", "3:1", "3:2", "4:0", "4:1", "4:2"],
        '1' => ["0:0", "1:1", "2:2", "3:3"],
        '0' => ["0:1", "0:2", "1:2", "0:3", "1:3", "2:3", "0:4", "1:4", "2:4"]
    ];
    //竞彩投注彩种
    const COMPET = ['3001', '3002', '3003', '3004', '3005', '3006', '3007', '3008', '3009', '3010', '3011'];
    
    //M串1对应的场次
    const AUTO_CHANG = [
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '6' => 4,
        '11' => 5,
        '18' => 6,
        '28' => 7,
        '35' => 8,
        '0101' => 1,
        '0201' => 2,
        '0301' => 3,
        '0401' => 4,
        '0501' => 5,
        '0601' => 6,
        '0701' => 7,
        '0801' => 8,
        '0901' => 9,
        '1001' => 10,
        '1101' => 11,
        '1201' => 12,
        '1301' => 13,
        '1401' => 14,
        '1501' => 15
    ];
    
    //所属地区
    const POSITION = [1 => '东部', 2 => '西部', 3 => '未知'];
    
    //北单总进球数
    const BD_5005 = [
        '0' => ['10', '20', '21' , '30', '31', '32' , '40', '41', '42'],
        '1' => ['00' , '11' , '22' , '33' ],
        '3' => ['01', '02', '12', '03', '13', '23', '04', '14', '24', '09']
    ];
    
    const MADE_WCUP_LOTTERY = ['301201', '301301'];
    
    //场次数对应的串关格式
    const SCHE_COUNT_MCN = [
        1 => ['1' => '1'],
        2 => ['2' => '2'],
        3 => ['3' => '3', '2' => '4', '4' => '4', '2,3' => '5', '5' => '5'],
        4 => ['6' => '6', '3' => '7', '7' => '7', '3,6' => '8', '8' => '8', '2' => '9', '9' => '9', '2,3,6' => '10', '10' => '10'],
        5 => ['11' => '11', '6' => '12', '12' => '12', '6,11' => '13', '13' => '13', '2' => '14', '14' => '14', '3,6,11' => '15', '15' => '15', '2,3' => '16', '16' => '16', '2,3,6,11' => '17', '17' => '17'],
        6 => ['18' => '18', '11' => '19', '19' => '19', '11,18' => '20', '20' => '20', '2' => '21', '21' => '21', '3' => '22', '22' => '22', '6,11,18' => '23', '23' => '23', '2,3' => '24', '24' => '24', '3,6,11,18' => '25', '25' => '25', '2,3,6' => '26', '26' => '26', '2,3,6,11,18' => '27', '27' => '27'],
        7 => ['28' => '28', '18' => '29', '29' => '29', '18,28' => '30', '30' => '30', '11' => '31', '31' => '31', '6' => '32', '32' => '32', '2,3,6,11,18,28' => '33', '33' => '33'],
        8 => ['35' => '35', '28' => '36', '36' => '36', '28,35' => '37', '37' => '37', '18' => '38', '38' => '38', '39' => '11', '39' => '39', '6' => '40', '40' => '40', '2,3,6,11,18,28,35' => '41','41' => '41']
    ];
    
    /**
     * 非自由过关
     */
    const NO_FREE_SCHE = [
        1 => ['1'],
        2 => ['2'],
        3 => ['3', '4', '5'],
        4 => ['6', '7', '8', '9', '11'],
        5 => ['12', '13', '14', '15', '16', '17'],
        6 => ['18', '19', '20', '21', '22', '23', '24', '25', '26', '27'],
        7 => ['28', '29', '30', '31', '32', '33'],
        8 => ['35', '36', '37', '38', '39', '40', '41']
    ];
    
}
