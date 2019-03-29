<div style="width: 100%;">
    <form class="myForm" id="filterForm">
        <ul class="third_team_ul">
            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">方案编号  </label>
                <input type="text" name="lottery_order_code" id="lottery_order_code" class="form-control" style="width: 200px;display: inline;margin-left:5px;"  value="" placeholder="方案订单号"/>
            </li>
            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">投注时间  </label>
                <input type="text" name="start_date" class='ECalendar form-control' id="start_date" style="width: 100px;display: inline;margin-left:5px;"  value="" placeholder="开始时间"/>
                -
                <input type="text" name="end_date" class='ECalendar form-control' id="end_date" style="width: 100px;display: inline;"  value="" placeholder="结束时间"/>
            </li>


            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">彩种  </label>
                <select name="lottery_code" class="form-control" id="lottery_code" style="width: 100px;display: inline;margin-left:5px;">
                    <option value="">全部</option>
                </select>
            </li>
            <!--        <li class="third_team_ul">
                        <label style="margin-left:15px;" for="">排序方式  </label>
                        <select name="time_type" class="form-control" style="width: 100px;display: inline;margin-left:5px;">
                            <option value="1">提交时间</option>
                            <option value="2">截止时间</option>
                        </select>
                    </li>-->
            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">会员信息  </label>
                <input type="text" name="user_info" id="user_info" class="form-control" style="width: 200px;display: inline;margin-left:5px;"  value="" placeholder="会员编号、手机号、昵称"/>
            </li>

            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">截止时间  </label>
                <input type="text" name="start_end_time" class='ECalendar form-control' id="start_end_time" style="width: 100px;display: inline;margin-left:5px;"  value="" placeholder="开始时间"/>
                -
                <input type="text" name="end_end_time" class='ECalendar form-control' id="end_end_time" style="width: 100px;display: inline;"  value="" placeholder="结束时间"/>
            </li>
        </ul>
    </form>
    <button type="button" class="am-btn am-btn-primary" id="filterButton" style="float:right;position: relative;">搜索</button>
    <button type="button" class="am-btn am-btn-primary" id="resetFilterButton" style="float:right;position: relative;margin-right: 5px;">重置</button>
</div>
<div style="width: 100%;float: left;">
    <table><tr><td >提示：</td><td>1、仅展示出票成功且未取票的方案</td></tr><tr><td></td><td>2、中奖单注超过1万元的订单请店主自主扣税后再兑奖，手工出票兑奖请店主认真审核。</td></tr></table>
</div>
<table class="table" id="pwTable">
    <thead>
        <tr>
            <th style="text-align: center;">方案编号</th>
            <th style="text-align: center;">投注时间<i class="am-icon-long-arrow-up sortIcon" data-val="up" data-name="create_time"></i><i class="am-icon-long-arrow-down sortIcon" data-val="down" data-name="create_time"></i></th>
            <th style="text-align: center;">截止时间<i class="am-icon-long-arrow-up sortIcon" data-val="up" data-name="end_time"></i><i class="am-icon-long-arrow-down sortIcon" data-val="down" data-name="end_time"></i></th>
            <th style="text-align: center;">投注方案</th>
            <th style="text-align: center;">彩种玩法</th>
            <th style="text-align: center;">注数</th>
            <th style="text-align: center;">倍数</th>
            <th style="text-align: center;">投注金额(元)</th>
            <th style="text-align: center;">中奖金额(元)</th>
            <th style="text-align: center;">会员编号</th>
            <th style="text-align: center;">会员名称</th>
            <th style="text-align: center;">会员手机号</th>
            <th style="text-align: center;">操作</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<!--<div class="paginationContainer" style="bottom: 40px;position: fixed;right: 8px;">
    <div class="M-box"></div>
</div>-->

<div class="am-modal am-modal-no-btn" tabindex="-1" id="your-modal">
    <div class="am-modal-dialog">
        <div class="am-modal-hd">
            <span class="modalTitle"></span>
            <a href="javascript: void(0)" class="am-close am-close-spin" data-am-modal-close>&times;</a>
        </div>
        <div class="am-modal-bd">
            <p class="modalContent">
                Modal 内容。
            </p>
        </div>
    </div>
</div>
<script type="text/javascript">
    var total = 0;
    var params = {deal_status: 1, status: 4};
    var orderHasImgs = {};
    function getResultName(lotteryCode, res) {
        if (res == null || res == '') {
            return '';
        }
        var results = [];
        results["3010"] = [];
        results["3010"]["3"] = '胜';
        results["3010"]["1"] = '平';
        results["3010"][0] = '负';
        results["3006"] = [];
        results["3006"]["3"] = '胜';
        results["3006"]["1"] = '平';
        results["3006"]["0"] = '负';
        results["3008"] = [];
        results["3008"]["0"] = '总进0球';
        results["3008"]["1"] = '总进1球';
        results["3008"]["2"] = '总进2球';
        results["3008"]["3"] = '总进3球';
        results["3008"]["4"] = '总进4球';
        results["3008"]["5"] = '总进5球';
        results["3008"]["6"] = '总进6球';
        results["3008"]["7"] = '总进7/7+球';
        results["3008"]["7+"] = '总进7/7+球';
        if (lotteryCode == '3007') {
            if (res == '90') {
                return '胜其他';
            }
            if (res == '99') {
                return '平其他';
            }
            if (res == '09') {
                return '负其他';
            }
            return res.substr(0, 1) + ':' + res.substr(1, 2);
        }

        if (lotteryCode == '3009') {
            return results["3010"][res.substr(0, 1)] + results["3010"][res.substr(1, 1)];
        }
        return results[lotteryCode][res];
    }
    function modalOpen(orderCode, hasOutOrder) {
        var $modal = $('#your-modal');
        myAjax({
            url: "/api/store/store/get-order-detail",
            type: "POST",
            data: {order_code: orderCode, token: loaddata.get("token")},
            async: false,
            dataType: "json",
            success: function (json) {
                if (json["code"] != 600) {
                    alert(json["msg"]);
                    return false;
                }
                var data = json["result"];
                var resultHtml = '';
                var betHtml = '';
                var resultRedBalls = [];
                var resultBlueBalls = [];
                var resultBalls = [];
                var Array5001 = {
                    0:"负", 
                    1:"平", 
                    3:"胜", 
                };
                var Array5002 = {
                    0:"0球", 
                    1:"0球", 
                    2:"2球", 
                    3:"3球", 
                    4:"4球", 
                    5:"5球", 
                    6:"6球", 
                    7:"7+球",
                };
                var Array5003 = {
                    33:"胜胜", 
                    31:"胜平", 
                    30:"胜负", 
                    13:"平胜", 
                    11:"平平", 
                    10:"平负", 
                    "03":"负胜", 
                    "01":"负平",
                    "00":"负负"
                };
                var Array5004 = {
                   1:"上单",
                   2:"上双",
                   3:"下单",
                   4:"下双",
                };
                var Array5005 = {
                   10:"1:0",
                   20:"2:0",
                   21:"2:1",
                   30:"3:0",
                   31:"3:1",
                   32:"3:2",
                   40:"4:0",
                   41:"4:1",
                   42:"4:2",
                   90:"胜其他",
                   "00":"0:0",
                   11:"1:1",
                   22:"2:2",
                   33:"3:3",
                   99:"平其他",
                   "01":"0:1",
                   "02":"0:2",
                   12:"1:2",
                   "03":"0:3",
                   13:"1:3",
                   23:"2:3",
                   "04":"0:4",
                   14:"1:4",
                   24:"2:4",
                   "09":"负其他",
                };
                if (data["lottery_numbers"] != null && data["lottery_numbers"] != '') {
                    if (['2001', '1001'].indexOf(data["lottery_id"]) != '-1') {
                        var str = data["lottery_numbers"];
                        var areas = str.split("|");
                        resultRedBalls = areas[0].split(",");
                        resultBlueBalls = areas[1].split(",");
                        resultHtml = '<tr><td>开奖号码</td><td><div class="marginBottom2">';
                        $.each(resultRedBalls, function (k, v) {
                            resultHtml += '<span class="yuan_0">' + v + '</span>';
                        });
                        $.each(resultBlueBalls, function (k, v) {
                            resultHtml += '<span class="yuan_1">' + v + '</span>';
                        });
                        resultHtml += '</div></td></tr>';
                    }
                    if (['1002', '1003', '2002', '2003', '2004'].indexOf(data["lottery_id"]) != '-1') {
                        var str = data["lottery_numbers"];
                        var resultBalls = str.split(",");
                        resultHtml = '<tr><td>开奖号码</td><td><div class="marginBottom2">';
                        $.each(resultBalls, function (k, v) {
                            resultHtml += '<span class="yuan_0">' + v + '</span>';
                        });
                        resultHtml += '</div></td></tr>';
                    }
                    if (['2005', '2006', '2007', '2008','2011','2010'].indexOf(data["lottery_id"]) != '-1') {
                        var str = data["lottery_numbers"];
                        var resultBalls = str.split(",");
                        resultHtml = '<tr><td>开奖号码</td><td><div class="marginBottom2">';
                        $.each(resultBalls, function (k, v) {
                            resultHtml += '<span class="yuan_0">' + v + '</span>';
                        });
                        resultHtml += '</div></td></tr>';
                    }
                }

                var strs = data["bet_val"].split("^");
                var playCodes = data["play_code"].split(",");
                var playNames = data["play_name"].split(",");
                betHtml = '<tr><td style="vertical-align: middle;">投注号码</td><td style="padding: 2px;">';

                if (['2001', '1001', '1002', '1003', '2002', '2003', '2004'].indexOf(data["lottery_id"]) != '-1') {
                    $.each(strs, function (strKey, str) {
                        if (str == "") {
                            return true;
                        }
                        betHtml += '<div class="marginBottom2">';
                        if (data["lottery_id"] == '2001' || data["lottery_id"] == '1001') {
                            var areas = str.split("|");
                            var redBalls = areas[0].split(",");
                            var blueBalls = areas[1].split(",");
                            $.each(redBalls, function (k, v) {
                                if (resultRedBalls.indexOf(v) != '-1') {
                                    betHtml += '<span class="yuan_0">' + v + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_3">' + v + '</span>';
                                }
                            });
                            $.each(blueBalls, function (k, v) {
                                if (resultBlueBalls.indexOf(v) != '-1') {
                                    betHtml += '<span class="yuan_1">' + v + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_4">' + v + '</span>';
                                }
                            });
                        }
                        if (['100201', '100211', '100301', '100302', '100303', '200201', '200211', '200301', '200302', '200401', '200402'].indexOf(playCodes[strKey]) != '-1') {
                            betHtml += '<span class="circlePrompt">' + playNames[strKey].substr(0, 2) + '</span>';
                            var areas = str.split("|");
                            $.each(areas, function (key, val) {
                                var balls = val.split(",");
                                $.each(balls, function (k, v) {
                                    if (key != 0 && k == 0) {
                                        betHtml += "<span class='areaBalls'>";
                                    } else {
                                        betHtml += "<span class='balls'>";
                                    }
                                    if (resultBalls[key] != undefined && resultBalls[key] == v) {
                                        betHtml += '<span class="yuan_0">' + v + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_3">' + v + '</span>';
                                    }
                                    betHtml += "</span>";
                                });
                            });
                        } else if (['100202', '100212', '100203', '100213', '200202', '200212', '200203', '200213'].indexOf(playCodes[strKey]) != '-1') {
                            betHtml += '<span class="circlePrompt">' + playNames[strKey].substr(0, 2) + '</span>';
                            var balls = str.split(",");
                            $.each(balls, function (k, v) {
                                betHtml += "<span class='balls'>";
                                if (resultBalls != undefined && resultBalls.length > 0 && resultBalls.indexOf(v) != '-1') {
                                    betHtml += '<span class="yuan_0">' + v + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_3">' + v + '</span>';
                                }
                                betHtml += "</span>";
                            });
                        }
                        betHtml += '</div>';
                    });
                } else if (['3006', '3007', '3008', '3009', '3010', '3011'].indexOf(data["lottery_id"]) != '-1') {
                    var competHtml = '<table class="table am-table am-table-bordered am-table-striped" style="margin: 0;">\n\
                                        <tr><th style="text-align: center;">主队 VS 客队</th><th style="text-align: center;">赛果</th><th style="text-align: center;">投注内容</th></tr>';

                    $.each(data["compet_detail"], function (key, val) {
                        var playAry=[];
                        var resHtml='';
                        var playHtml = '';
                        var has3006 = false;
                        $.each(val['lottery'], function (k, v) {
                            playAry.push(v["play"]);
                            if (val["schedule_result_" + v["play"]] == v["bet"]) {
                                playHtml += "<span class='balls' style='color:#dc3b40;display:block;'>";
                            } else {
                                playHtml += "<span class='balls' style='display:block;'>";
                            }
                            if (v["play"] == '3006') {
                                playHtml += '<span class="prompt3006">让</span>';
                                has3006 = true;
                            }
                            playHtml += getResultName(v["play"], v["bet"]) + '(' + v["odds"] + ')';
                            playHtml += "</span>";
                        });
                        $.unique(playAry.sort());  //赛程结果数组去重
                        $.each(playAry,function(k,v){
                            if(v=="3006"){
                                if(val["schedule_result_3006"]!=""){
                                    resHtml+= '<span class="prompt3006">让</span>'+getResultName(v, val["schedule_result_3006"])+"<br/>";
                                } 
                            }
                            if(v=="3007"){
                                if(val["schedule_result_3007"]!=""){
                                   resHtml+= getResultName(v, val["schedule_result_3007"])+"<br/>";
                                } 
                            }
                            if(v=="3008"){
                                if(val["schedule_result_3008"]!=""){
                                    resHtml+=getResultName(v, val["schedule_result_3008"])+"<br/>";
                                } 
                            }
                            if(v=="3009"){
                                if(val["schedule_result_3009"]!=""){
                                    resHtml+=getResultName(v, val["schedule_result_3009"])+"<br/>";
                                } 
                            }
                            if(v=="3010"){
                                if(val["schedule_result_3010"]!=""){
                                    resHtml+=getResultName(v, val["schedule_result_3010"])+"<br/>";
                                } 
                            }
                        })
                        competHtml += '<tr><td style="text-align: center;"><span style="display:block;color:#999;">' + val['schedule_code'] + '</span>' + val['home_short_name'] + (has3006 ? '<span style="color:#0c89e1;font-size:8px;padding-left: 5px;">(' + val['rq_nums'] + ')</span>' : '') + (val['schedule_result_bf'] ? ('<span style="color:#dc3b40;"> ' + val['schedule_result_bf']) + ' </span>' : " VS ") + val['visit_short_name'] + '</td><td style="text-align: center;">' +resHtml+ '</td>\n\<td style="text-align: center;">' + playHtml + '</td></tr>';
                    });
                    competHtml += '</table>';
                    betHtml += competHtml;
                } else if (['3001', '3002', '3003', '3004', '3005'].indexOf(data["lottery_id"]) != '-1') {
                    var competHtml = '<table class="table am-table am-table-bordered am-table-striped" style="margin: 0;">\n\
                                        <tr><th style="text-align: center;">客队 VS 主队</th><th style="text-align: center;">赛果</th><th style="text-align: center;">投注内容</th></tr>';
                    $.each(data["compet_detail"], function (key, val) {
                        var playHtml = "<tr>";
                        if (val.hasOwnProperty("result_qcbf")) {
                            if (val.hasOwnProperty("rf_nums")) {
                                if (val.hasOwnProperty("fen_cutoff")) {
                                    playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.schedule_code + "</span><span>" + val.visit_short_name + "</span><span style='color:red;'>" + val.result_qcbf + "</span><span>" + val.home_short_name + "&nbsp;</span><span style='color:blue'>(" + val.rf_nums + ")</span><span style='display:block;color:#999;'>预测总分" + val.fen_cutoff + "分</span></td>";
                                } else {
                                    playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.schedule_code + "</span><span>" + val.visit_short_name + "</span><span style='color:red;'>" + val.result_qcbf + "</span><span>" + val.home_short_name + "&nbsp;</span><span style='color:blue'>(" + val.rf_nums + ")</span></td>";
                                }
                            } else if (val.hasOwnProperty("fen_cutoff")) {
                                playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.schedule_code + "</span><span>" + val.visit_short_name + "</span><span style='color:red;'>" + val.result_qcbf + "</span><span>" + val.home_short_name + "&nbsp;</span><span style='display:block;color:#999;'>预测总分" + val.fen_cutoff + "分</span></td>";
                            } else {
                                playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.schedule_code + "</span><span>" + val.visit_short_name + "</span><span style='color:red;'>" + val.result_qcbf + "</span><span>" + val.home_short_name + "&nbsp;</span></td>";
                            }
                        } else {
                            if (val.hasOwnProperty("rf_nums")) {
                                if (val.hasOwnProperty("fen_cutoff")) {
                                    playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.schedule_code + "</span><span>" + val.visit_short_name + "</span><span>&nbsp;VS&nbsp;</span><span>" + val.home_short_name + "&nbsp;</span><span style='color:blue'>(" + val.rf_nums + ")</span><span style='display:block;color:#999;'>预测总分" + val.fen_cutoff + "分</span></td>";
                                } else {
                                    playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.schedule_code + "</span><span>" + val.visit_short_name + "</span><span>&nbsp;VS&nbsp;</span><span>" + val.home_short_name + "&nbsp;</span><span style='color:blue'>(" + val.rf_nums + ")</span></td>";
                                }
                            } else if (val.hasOwnProperty("fen_cutoff")) {
                                playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.schedule_code + "</span><span>" + val.visit_short_name + "</span><span>&nbsp;VS&nbsp;</span><span>" + val.home_short_name + "&nbsp;</span><span style='display:block;color:#999;'>预测总分" + val.fen_cutoff + "分</span></td>";
                            } else {
                                playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.schedule_code + "</span><span>" + val.visit_short_name + "</span><span>&nbsp;VS&nbsp;</span><span>" + val.home_short_name + "</span></td>";
                            }
                        }
                        //赛果
                        playHtml += "<td>";
                        if (val.hasOwnProperty("result_3001") && val.result_3001 == "0") {
                            playHtml += "<span style='text-align: center;display:block;'>主负</span>"
                        } else if (val.hasOwnProperty("result_3001") && val.result_3001 == "3") {
                            playHtml += "<span style='text-align: center;display:block;'>主胜</span>"
                        }
                        if (val.hasOwnProperty("result_3002") && val.result_3002 == "0") {
                            playHtml += "<span style='text-align: center;display:block;'>让分主负</span>"
                        } else if (val.hasOwnProperty("result_3002") && val.result_3002 == "3") {
                            playHtml += "<span style='text-align: center;display:block;'>让分主胜</span>"
                        }
                        if (val.hasOwnProperty("result_3003")) {
                            switch (val.result_3003) {
                                case "01":
                                    playHtml += "<span style='text-align: center;display:block;'>主胜1-5</span>";
                                    break;
                                case "02":
                                    playHtml += "<span style='text-align: center;display:block;'>主胜6-10</span>";
                                    break;
                                case "03":
                                    playHtml += "<span style='text-align: center;display:block;'>主胜11-15</span>";
                                    break;
                                case "04":
                                    playHtml += "<span style='text-align: center;display:block;'>主胜16-20</span>";
                                    break;
                                case "05":
                                    playHtml += "<span style='text-align: center;display:block;'>主胜21-25</span>";
                                    break;
                                case "06":
                                    playHtml += "<span style='text-align: center;display:block;'>主胜26+</span>";
                                    break;
                                case "11":
                                    playHtml += "<span style='text-align: center;display:block;'>客胜1-5</span>";
                                    break;
                                case "12":
                                    playHtml += "<span style='text-align: center;display:block;'>客胜6-10</span>";
                                    break;
                                case "13":
                                    playHtml += "<span style='text-align: center;display:block;'>客胜11-15</span>";
                                    break;
                                case "14":
                                    playHtml += "<span style='text-align: center;display:block;'>客胜16-20</span>";
                                    break;
                                case "15":
                                    playHtml += "<span style='text-align: center;display:block;'>客胜21-25</span>";
                                    break;
                                case "16":
                                    playHtml += "<span style='text-align: center;display:block;'>客胜26+</span>";
                                    break;
                            }

                        }
                        if (val.hasOwnProperty("result_3004") && val.result_3004 == "1") {
                            playHtml += "<span style='text-align: center;display:block;'>大分</span>"
                        } else if (val.hasOwnProperty("result_3004") && val.result_3004 == "2") {
                            playHtml += "<span style='text-align: center;display:block;'>小分</span>"
                        }
                        playHtml += "</td>";
                        //投注
                        playHtml += "<td style='text-align: center;'>"
                        $.each(val['lottery'], function (k, v) {
                            if (v.play == "3001") {
                                if (v.bet == val.result_3001) {
                                    if (v.bet == 3) {
                                        playHtml += "<span style='display:block;color:red;'>胜(" + v.odds + ")</span>";
                                    } else {
                                        playHtml += "<span style='display:block;color:red;'>负(" + v.odds + ")</span>";
                                    }
                                } else {
                                    if (v.bet == 3) {
                                        playHtml += "<span style='display:block;'>胜(" + v.odds + ")</span>";
                                    } else {
                                        playHtml += "<span style='display:block;'>负(" + v.odds + ")</span>";
                                    }
                                }
                            }
                            if (v.play == "3002") {
                                if (v.bet == val.result_3002) {
                                    if (v.bet == 3) {
                                        playHtml += "<span style='display:block;color:red;'>让分主胜(" + v.odds + ")</span>";
                                    } else {
                                        playHtml += "<span style='display:block;color:red;'>让分主负(" + v.odds + ")</span>";
                                    }
                                } else {
                                    if (v.bet == 3) {
                                        playHtml += "<span style='display:block;'>让分主胜(" + v.odds + ")</span>";
                                    } else {
                                        playHtml += "<span style='display:block;'>让分主负(" + v.odds + ")</span>";
                                    }
                                }
                            }
                            if (v.play == "3003") {
                                if (v.bet == val.result_3003) {
                                    playHtml += "<span style='display:block;color:red;'>" + v.bet_name + "(" + v.odds + ")</span>";
                                } else {
                                    playHtml += "<span style='display:block;'>" + v.bet_name + "(" + v.odds + ")</span>";
                                }
                            }
                            if (v.play == "3004") {
                                if (v.bet == val.result_3004) {
                                    if (v.bet == 2) {
                                        playHtml += "<span style='display:block;color:red;'>小分(" + v.odds + ")</span>";
                                    } else {
                                        playHtml += "<span style='display:block;color:red;'>大分(" + v.odds + ")</span>";
                                    }
                                } else {
                                    if (v.bet == 2) {
                                        playHtml += "<span style='display:block;'>小分(" + v.odds + ")</span>";
                                    } else {
                                        playHtml += "<span style='display:block;'>大分(" + v.odds + ")</span>";
                                    }
                                }
                            }

                        });
                        playHtml += "</td></tr>";
                        competHtml += playHtml;
                    });
                    competHtml += '</table>';
                    betHtml += competHtml;
                } else if (['5001', '5002', '5003', '5004', '5005', '5006'].indexOf(data["lottery_id"]) != '-1') {
                     var competHtml = '<table class="table am-table am-table-bordered am-table-striped" style="margin: 0;">\n\
                                        <tr><th style="text-align: center;">主队 VS 客队</th><th style="text-align: center;">赛果</th><th style="text-align: center;">投注内容</th></tr>';
                    $.each(data["compet_detail"], function (key, val) {
                        var playHtml = "<tr>";
                        if (val.hasOwnProperty("rq_nums")){
                            if(val.hasOwnProperty("schedule_result_bf")){
                               playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.mid + "</span><span>" + val.home_short_name + "</span><span style='color:blue;'>( " + val.rq_nums + " )</span><span style='color:red;'>" + val.schedule_result_bf + "</span><span> VS " + val.visit_short_name + "&nbsp;</span></td>"; 
                            }else{
                              playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.mid + "</span><span>" + val.home_short_name + "</span><span style='color:blue;'>( " + val.rq_nums + " )</span><span> VS " + val.visit_short_name + "&nbsp;</span></td>";   
                            }                        
                        } else {
                            if(val.hasOwnProperty("schedule_result_bf")){
                               playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.mid + "</span><span>" + val.home_short_name + "</span><span style='color:red;'>" + val.schedule_result_bf + "</span><span> VS " + val.visit_short_name + "&nbsp;</span></td>"; 
                            }else{
                               playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.mid + "</span><span>" + val.home_short_name + "</span><span> VS " + val.visit_short_name + "&nbsp;</span></td>";  
                            }
                            
                        }
                         //投注
                        var  playAry = [];
                        var playConHtml = "<td style='text-align: center;'>"
                        $.each(val['lottery'], function (k, v) {
                            playAry.push(v.play);
                            if (v.play == "5001") {
                                if (v.bet == val.schedule_result_5001) {
                                    playConHtml += "<span style='display:block;color:red;'>" + Array5001[v.bet]+"(" + v.odds + ")</span>";
                                } else {
                                    playConHtml += "<span style='display:block;'>" + Array5001[v.bet]+"(" + v.odds + ")</span>"; 
                                }
                            }
                            if (v.play == "5002") {
                                if (v.bet == val.schedule_result_5002) {
                                    playConHtml += "<span style='display:block;color:red;'>"+Array5002[v.bet]+"(" + v.odds + ")</span>";
                                } else {
                                    playConHtml += "<span style='display:block;'>"+Array5002[v.bet]+"(" + v.odds + ")</span>";
                                }
                            }
                            if (v.play == "5003") {
                                if (v.bet == val.schedule_result_5003) {
                                    playConHtml += "<span style='display:block;color:red;'>" + Array5003[v.bet]+ "(" + v.odds + ")</span>";
                                } else {
                                    playConHtml += "<span style='display:block;'>" +Array5003[v.bet]+ "(" + v.odds + ")</span>";
                                }
                            }
                            if (v.play == "5004") {
                                if (v.bet == val.schedule_result_5004) {
                                    playConHtml += "<span style='display:block;color:red;'>"+Array5004[v.bet]+"(" + v.odds + ")</span>";
                                 } else {
                                    playConHtml += "<span style='display:block;'>"+Array5004[v.bet]+"(" + v.odds + ")</span>";
                                 }
                             }
                            if (v.play == "5005") {
                                if (v.bet == val.schedule_result_5005){
                                    playConHtml += "<span style='display:block;color:red;'>"+Array5005[v.bet]+"(" + v.odds + ")</span>";
                                 } else {
                                    playConHtml += "<span style='display:block;'>"+Array5005[v.bet]+"(" + v.odds + ")</span>";
                                 }
                             }

                        });
                        playConHtml += "</td>";
                        $.unique(playAry.sort());  //赛程结果数组去重
                         //赛果
                        var  resHtml = "<td>";
                        if (val.result_status == 3) {
                            resHtml += "比赛取消";
                        } else {
                            $.each(playAry,function(k,v){
                                if(v==5001){
                                    if(val.schedule_result_5001!=""){
                                      resHtml += "<span style='text-align: center;display:block;'>"+Array5001[val.schedule_result_5001]+"</span>"  
                                    }
                                 }
                                 if(v==5002){
                                    if(val.schedule_result_5002!=""){
                                      resHtml += "<span style='text-align: center;display:block;'>"+Array5002[val.schedule_result_5002]+"</span>"  
                                    }
                                 }
                                 if(v==5003){
                                    if(val.schedule_result_5003!=""){
                                      resHtml += "<span style='text-align: center;display:block;'>"+Array5003[val.schedule_result_5003]+"</span>"  
                                    }
                                 }
                                 if(v==5004){
                                    if(val.schedule_result_5004!=""){
                                      resHtml += "<span style='text-align: center;display:block;'>"+Array5004[val.schedule_result_5004]+"</span>"  
                                    }
                                 }
                                 if(v==5005){
                                    if(val.schedule_result_5005!=""){
                                      resHtml += "<span style='text-align: center;display:block;'>"+Array5005[val.schedule_result_5005]+"</span>"  
                                    }
                                 }
                             })
                        }
                        resHtml += "</td>";
                        //投注内容拼接
                        competHtml += playHtml+resHtml+playConHtml+"</tr>";
                        });
                    competHtml += '</table>';
                    betHtml += competHtml;
                }else if (['301201', '301301'].indexOf(data["lottery_id"]) != '-1') {
                     var competHtml = '<table class="table am-table am-table-bordered am-table-striped" style="margin: 0;">\n\
                                        <tr><th style="text-align: center;">球队</th><th style="text-align: center;">赛果</th><th style="text-align: center;">赔率</th></tr>';
                    $.each(data["compet_detail"], function (key, val) {
                        //投注球队
                        var playHtml = "<tr>";
                        playHtml += "<td style='text-align: center;'><span style='display:block;color:#999;'>" + val.open_mid + "</span><span style='display:block;color:#999;'>" + val.team_name + "</span></td>";
                         //投注赛果
                        var playConHtml = "<td style='text-align: center;'>"
                        if(val.result==1){
                            playConHtml += "";
                        }
                        playConHtml += "</td>";
                        //投注赔率
                        var resHtml = "<td style='text-align: center;'><span>"+ val.odds + "</span></td>";
                        //投注内容拼接
                        competHtml += playHtml+playConHtml+resHtml+"</tr>";
                        });
                    competHtml += '</table>';
                    betHtml += competHtml;
                } else if (['4001', '4002'].indexOf(data["lottery_id"]) != '-1') {
//                     console.log(json);
                    var competHtml = '<table class="table am-table am-table-bordered am-table-striped" style="margin: 0;">\n\
                                        <tr><th style="text-align: center;">主队 VS 客队</th><th style="text-align: center;">赛果</th><th style="text-align: center;">投注内容</th></tr>';
                    $.each(data["optional_detail"], function (key, val) {
                        var resSta="";
                        if(val["result"]=="0"){
                                resSta="负"
                            }else if(val["result"]=="1"){
                                resSta="平"
                            }else if(val["result"]=="3"){
                                resSta="胜"
                            }else{
                                resSta=""
                            }
                        var playHtml = '';
                        var bets = val["bet_val"].split("");
                        $.each(bets, function (k, v) {
                            var sta="";
                          
                            if(v=="0"){
                                sta="负"
                            }else if(v=="1"){
                                sta="平"
                            }else if(v=="3"){
                                sta="胜"
                            }else{
                                sta=""
                            }
                            
                            if (val["result"] == v) {
                                playHtml += "<span class='balls' style='color:#dc3b40;margin:3px;'>";
                            } else {
                                playHtml += "<span class='balls' style='margin:3px;'>";
                            }
                            playHtml += sta + "</span>";
                        });
                        competHtml += '<tr><td style="text-align: center;"><span style="display:block;color:#999;">' + val['sid'] + '</span>' + val['home_team'] + " VS " + val['visit_team'] + '</td><td style="text-align: center;">' + resSta + '</td><td style="text-align: center;">' + playHtml + '</td></tr>';
                    });
                    competHtml += '</table>';
                    betHtml += competHtml;
                } else if (['2005', '2006', '2007', '2008','2011','2010'].indexOf(data["lottery_id"]) != '-1'){
                    //开奖结果
                    var str = data["lottery_numbers"];
                    if(str!=null){
                        var resultBalls = str.split(",");
                    }else{
                        var resultBalls =[];
                    }
                    $.each(strs, function (strKey, str) {
                        if (str == "") {
                            return true;
                        }
                        //前一单式复式
                        if (['200531', '200541', '200631', '200641', '200731', '200741', '200831', '200841','201131','201141','201031','201041'].indexOf(playCodes[strKey]) != '-1') {
                            betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                            var areas = str.split(",");
                            $.each(areas, function (key, v) {
                                if (resultBalls[0] == v) {
                                    betHtml += '<span class="yuan_0">' + v + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_3">' + v + '</span>';
                                }
                            })
                            betHtml += '<br/>';
                            //前二前三直选单式复式
                        } else if (['200532', '200533', '200542', '200543', '200632', '200633', '200642', '200643', '200732', '200733', '200742', '200743', '200832', '200833', '200842', '200843','201132','201133','201142','201143','201032','201033','201042','201043'].indexOf(playCodes[strKey]) != '-1') {
                            betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                            if (str.indexOf(";") > 0){
                                var areas = str.split(";");
                                var num = areas[0].split(",");
                                var num2 = areas[1].split(",");
                                $.each(num, function (key, n) {
                                    if (resultBalls[0] == n) {
                                        betHtml += '<span class="yuan_0">' + n + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_3">' + n + '</span>';
                                    }
                                })
                                betHtml += ' |  ';
                                $.each(num2, function (key, n) {
                                    if (resultBalls[1] == n) {
                                        betHtml += '<span class="yuan_0">' + n + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_3">' + n + '</span>';
                                    }
                                })
                                if (areas.length == 3) {
                                    betHtml += ' |  ';
                                    var num3 = areas[2].split(",");
                                    $.each(num3, function (key, n) {
                                        if (resultBalls[2] == n) {
                                            betHtml += '<span class="yuan_0">' + n + '</span>';
                                        } else {
                                            betHtml += '<span class="yuan_3">' + n + '</span>';
                                        }
                                    })
                                }
                            } else {
                                var areas = str.split(",");
                                if (resultBalls[0] == areas[0]) {
                                    betHtml += '<span class="yuan_0">' + areas[0] + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_3">' + areas[0] + '</span>';
                                }
                                if (resultBalls[1] == areas[1]) {
                                    betHtml += '<span class="yuan_0">' + areas[1] + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_3">' + areas[1] + '</span>';
                                }
                                if (areas.length == 3) {
                                    if (resultBalls[2] == areas[2]) {
                                        betHtml += '<span class="yuan_0">' + areas[2] + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_3">' + areas[2] + '</span>';
                                    }
                                }
                            }
                            betHtml += '<br/>';
                            //前二组选单式复式
                        } else if (['200534', '200544', '200634', '200644', '200734', '200744', '200834', '200844','201134','201144','201034','201044'].indexOf(playCodes[strKey]) != '-1') {
                            betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                            var areas = str.split(",");
                            var newAry = [resultBalls[0], resultBalls[1]];
                            $.each(areas, function (key, n) {
                                if (newAry.indexOf(n) == '-1') {
                                    betHtml += '<span class="yuan_3">' + n + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_0">' + n + '</span>';
                                }
                            })
                            betHtml += '<br/>';
                            //前三组选单式复式
                        } else if (['200535', '200545', '200635', '200645', '200735', '200745', '200835', '200845','201135','201145','201035','201045'].indexOf(playCodes[strKey]) != '-1') {
                            betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                            var areas = str.split(",");
                            var newAry = [resultBalls[0], resultBalls[1], resultBalls[2]];
                            $.each(areas, function (key, n) {
                                if (newAry.indexOf(n) == '-1') {
                                    betHtml += '<span class="yuan_3">' + n + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_0">' + n + '</span>';
                                }
                            })
                            betHtml += '<br/>';
                            //前二组选胆拖
                        } else if (['200554', '200654', '200754', '200854','201154','201054'].indexOf(playCodes[strKey]) != '-1') {
                            betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                            var areas = str.split("#");
                            var danBall = areas[0];
                            var tuoBall = areas[1].split(",");
                            var newAry = [resultBalls[0], resultBalls[1]];
                            betHtml += '<span>胆：</span>';
                            if (newAry.indexOf(danBall) == "-1") {
                                betHtml += '<span class="yuan_3">' + danBall + '</span>';
                            } else {
                                betHtml += '<span class="yuan_0">' + danBall + '</span>';
                            }
                            betHtml += '<span>拖：</span>';
                            $.each(tuoBall, function (key, t) {
                                if (newAry.indexOf(t) == '-1') {
                                    betHtml += '<span class="yuan_3">' + t + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_0">' + t + '</span>';
                                }
                            })
                            betHtml += '<br/>';
                            //前三组选胆拖
                        } else if (['200555', '200655', '200755', '200855','201155','201055'].indexOf(playCodes[strKey]) != '-1') {
                            betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                            var areas = str.split("#");
                            var danBall = areas[0].split(",");
                            var tuoBall = areas[1].split(",");
                            var newAry = [resultBalls[0], resultBalls[1], resultBalls[2]];
                            betHtml += '<span>胆：</span>';
                            $.each(danBall, function (key, t) {
                                if (newAry.indexOf(t) == "-1") {
                                    betHtml += '<span class="yuan_3">' + t + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_0">' + t + '</span>';
                                }
                            })
                            betHtml += '<span>拖：</span>';
                            $.each(tuoBall, function (key, t) {
                                if (newAry.indexOf(t) == '-1') {
                                    betHtml += '<span class="yuan_3">' + t + '</span>';
                                } else {
                                    betHtml += '<span class="yuan_0">' + t + '</span>';
                                }
                            })
                            betHtml += '<br/>';
                            //乐选单式
                        }else if(['201163', '201164', '201165','200763', '200764', '200765'].indexOf(playCodes[strKey]) != '-1'){
                                betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                                var areas = str.split(";");
                                $.each(areas, function (key, n) {
                                    if (resultBalls.indexOf(n) == '-1') {
                                        betHtml += '<span class="yuan_3">' + n + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_0">' + n + '</span>';
                                    }
                                })
                                betHtml += '<br/>';
                                //乐选复式
                       }else if(['201166','201167','201168','200766','200767','200768'].indexOf(playCodes[strKey]) != '-1'){
                            betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                            var areas = str.split(";");
                            $.each(areas, function (key, n) {
                                var lnum = n.split(",");
                                var str="";
                                $.each(lnum,function(k,m){
                                    if (resultBalls.indexOf(m) == '-1') {
                                        betHtml += '<span class="yuan_3">' + m + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_0">' + m + '</span>';
                                    }
                                })
                                str +=" | ";
                                betHtml+= str;
                            })
                            betHtml += '<br/>';
                        }else{
                            //任选胆拖
                            if (str.indexOf("#") > 0){
                                betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                                var areas = str.split("#");
                                var danBall = areas[0].split(",");
                                var tuoBall = areas[1].split(",");
                                betHtml += '<span>胆：</span>';
                                $.each(danBall, function (key, d) {
                                    if (resultBalls.indexOf(d) == '-1') {
                                        betHtml += '<span class="yuan_3">' + d + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_0">' + d + '</span>';
                                    }
                                })
                                betHtml += '<span>拖：</span>';
                                $.each(tuoBall, function (key, t) {
                                    if (resultBalls.indexOf(t) == '-1') {
                                        betHtml += '<span class="yuan_3">' + t + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_0">' + t + '</span>';
                                    }
                                })
                                betHtml += '<br/>';
                            } else {
                                //任选
                                betHtml += '<span class="circlePrompt">' + playNames[strKey] + '</span>';
                                var areas = str.split(",");
                                $.each(areas, function (key, n) {
                                    if (resultBalls.indexOf(n) == '-1') {
                                        betHtml += '<span class="yuan_3">' + n + '</span>';
                                    } else {
                                        betHtml += '<span class="yuan_0">' + n + '</span>';
                                    }
                                })
                                betHtml += '<br/>';
                            }
                        }
                    })
                }
                betHtml += '</td></tr>';
                orderHasImgs["order_img1"] = false;
                orderHasImgs["order_img2"] = false;
                orderHasImgs["order_img3"] = false;
                orderHasImgs["order_img4"] = false;
                var imgHtml = "";
                myAjax({
                    url: "/api/publicinterface/interface/get-order-img",
                    type: "POST",
                    data: {order_id: data["lottery_order_id"]},
                    async: false,
                    dataType: "json",
                    success: function (json) {
                        if (json["code"] == 600) {
                            if (json["result"]["data"] != null) {
                                if (json["result"]["data"]["order_img1"] != null && json["result"]["data"]["order_img1"] != "") {
                                    imgHtml += '<span class="img-group" data-name="order_img1"><img src="' + json["result"]["data"]["order_img1"] + '" class="preImg " />' + ((data["status"] == '2' && hasOutOrder == 'outOrder') ? '<i class="am-icon-times-circle imgPreClose" onclick="delOrderImg($(this), ' + data["lottery_order_id"] + ')"></i>' : "") + '</span>';
                                    orderHasImgs["order_img1"] = true;
                                }
                                if (json["result"]["data"]["order_img2"] != null && json["result"]["data"]["order_img2"] != "") {
                                    imgHtml += '<span class="img-group" data-name="order_img2"><img src="' + json["result"]["data"]["order_img2"] + '" class="preImg" />' + ((data["status"] == '2' && hasOutOrder == 'outOrder') ? '<i class="am-icon-times-circle imgPreClose" onclick="delOrderImg($(this), ' + data["lottery_order_id"] + ')"></i>' : "") + '</span>';
                                    orderHasImgs["order_img2"] = true;
                                }
                                if (json["result"]["data"]["order_img3"] != null && json["result"]["data"]["order_img3"] != "") {
                                    imgHtml += '<span class="img-group" data-name="order_img3"><img src="' + json["result"]["data"]["order_img3"] + '" class="preImg" />' + ((data["status"] == '2' && hasOutOrder == 'outOrder') ? '<i class="am-icon-times-circle imgPreClose" onclick="delOrderImg($(this), ' + data["lottery_order_id"] + ')"></i>' : "") + '</span>';
                                    orderHasImgs["order_img3"] = true;
                                }
                                if (json["result"]["data"]["order_img4"] != null && json["result"]["data"]["order_img4"] != "") {
                                    imgHtml += '<span class="img-group" data-name="order_img4"><img src="' + json["result"]["data"]["order_img4"] + '" class="preImg" />' + ((data["status"] == '2' && hasOutOrder == 'outOrder') ? '<i class="am-icon-times-circle imgPreClose" onclick="delOrderImg($(this), ' + data["lottery_order_id"] + ')"></i>' : "") + '</span>';
                                    orderHasImgs["order_img4"] = true;
                                }
                            }
                        }
                    }
                });
                if (data["status"] == '2' && hasOutOrder == 'outOrder') {
                    imgHtml += '<span class="img-group"><i class="am-icon-plus addImgIcon" onclick="$(\'#order_img\').click();" ></i><input id="order_img" name="order_img" class="picFileInput" type="file" name="pic" style="display:none;"/></span>';
                }
                //奖金优化M串N
                var newAry={};
                newAry["0"]="无奖金优化";
                newAry["1"]="平均优化";
                newAry["2"]="博热优化";
                newAry["3"]="博冷优化";
                var buyinfo="";
                if(data.build_name!=""&&data.build_name!=null){
                    buyinfo=data.build_name+"&nbsp("+ data.play_name + ")";
                }else{
                    buyinfo=data.play_name;
                }
                var html = '<div style="text-align:left;" id="outOrderTable">\n\
                                <table class="table am-table am-table-bordered am-table-striped">\n\
                                    <tr><td colspan="2" style="text-align:center;font-size: 16px;font-weight: 600;">' + data["lottery_name"] + ' ( ' + data["status_name"] + ' )</td></tr>\n\
                                    ' + (['3006', '3007', '3008', '3009', '3010', '3011'].indexOf(data["lottery_id"]) != '-1' ? "" : ('<tr><td>期数</td><td>' + data["periods"] + '</td></tr>')) + '\n\
                                    <tr><td style="width: 70px;">下单客户</td><td> ' + data["user_name"] + ' ( 联系号码： ' + data["user_tel"] + ' )</td></tr>\n\
                                    <tr><td colspan="2"> 已被 ' + data["forbided"] + ' 店拉黑,本店出票 ' + data["allTicket"] + ' 元</td></tr>\n\
                                    ' + resultHtml + betHtml + (['3000', '3001', '3002', '3003', '3004', '3005','3006', '3007', '3008', '3009', '3010', '3011'].indexOf(data["lottery_id"]) != '-1' ? ('<tr><td>奖金优化</td><td>' + newAry[data["major_type"]] + '</td></tr>') :"" )+ '\n\
                                    ' + (data["lottery_time"] == null || data["lottery_time"] == '' ? '' : ('<tr><td>开奖时间</td><td>' + data["lottery_time"] + '</td></tr>')) + '\n\
                                    <tr><td>投注信息</td><td> ' +buyinfo + ' ' + data["count"] + '注 ' + data["bet_double"] + '倍' + ' ( 金额: ' + data["bet_money"] + '元 )</td></tr>\n\
                                    ' + (data["status"] == 4 ? ('<tr><td>中奖金额</td><td> ' + data["win_amount"] + ' 元</td></tr>') : "") + '\n\
                                    <tr><td>投注时间</td><td>' + data["create_time"] + '</td></tr>\n\
                                    ' + (data["limit_time"] == null || data["limit_time"] == '' ? '' : ('<tr><td>停售时间</td><td>' + data["limit_time"] + '</td></tr>')) + '\n\
                                    <tr><td>方案编号</td><td>' + data["lottery_order_code"] + '</td></tr>\n\
                                    <tr><td>彩票照片</td><td>' + imgHtml + '<div style="color:#bbb;margin-left:10px;width: 100%;float: left;">最多上传4张图片</div></td></tr>\n\
                                    <tr><td>出票时间</td><td>' + data["out_time"] + '</td></tr>\n\
                                </table>\n\
                                <div style="text-align:center;"><button type="button" class="am-btn am-btn-default" onclick="$(\'#your-modal\').myModal(\'close\');" style="width:100px;margin-right:20px;">返回</button>' + ((data["status"] == '2' && hasOutOrder) ? '<button type="button" class="am-btn am-btn-primary" onclick="outOrder(\'' + data["lottery_order_code"] + '\');" style="width:100px;">手工出票</button>' : '') + ((data["status"] == '4' && data["deal_status"] == '1' && hasOutOrder) ? '<button type="button" class="am-btn am-btn-primary" onclick="playAwards(\'' + data["lottery_order_code"] + '\','+data["win_amount"]+');" style="width:100px;">派奖</button>' : '') + '</div>\n\
                            </div>';
                $modal.find(".modalContent").html(html);
                $modal.myModal();
            }
        });
    }
    function outOrder(orderCode) {
        myAjax({
            url: "/api/store/store/out-ticket",
            type: "POST",
            data: {lottery_order_code: orderCode},
            async: false,
            dataType: "json",
            success: function (json) {
                alert(json["msg"]);
                $('#your-modal').myModal("close");
                getContent(params);
            }
        });
    }
    function outOrderFalse(orderCode) {
        myAjax({
            url: "/api/store/store/out-ticket-false",
            type: "POST",
            data: {lottery_order_code: orderCode},
            async: false,
            dataType: "json",
            success: function (json) {
                if (json["code"] == 600) {
                    getContent(params);
                }
                $('#your-modal').myModal("close");
                alert(json["msg"]);
            }
        });
    }
    function playAwards(orderCode,money) {
            msgPrompt("提示","请输入派奖金额",money,function(){
                var  award_amount=$("#value").val();
                myAjax({
                    url: "/api/store/store/play-awards",
                    type: "POST",
                    data: {lottery_order_code:orderCode,award_amount:award_amount},
                    async: false,
                    dataType: "json",
                    success: function (json) {
                        alert(json["msg"]);
                        if (json["code"] == 600) {
                            getContent(params);
                        }
                        closeMask();
                    }
                });
            })

       
    }
    function getContent(options) {
        var data = $.extend({end_date: "", lottery_code: "", lottery_order_code: "", month: "", page: 1, start_date: "", status: "", time_type: "", user_info: "", token: loaddata.get("token")}, params, options);
        params = data;
        myAjax({
            url: "/api/store/store/order-list",
            type: "POST",
            data: data,
            async: false,
            dataType: "json",
            success: function (json) {
                if (json["code"] != 600) {
                    alert(json["msg"]);
                    return false;
                }
                var html = "";
                $.each(json["result"]["list"], function (key, val) {
                    html += '<tr data-key="' + val["lottery_order_code"] + '">\n\
                            <td class="textCenter"><span class="handle pointer" onclick="modalOpen(\'' + val["lottery_order_code"] + '\')">' + val["lottery_order_code"] + '</span></td>\n\
                            <td class="textCenter">' + val["create_time"] + '</td>\n\
                            <td class="textCenter">' + val["end_time"] + '</td>\n\
                            <td class="textCenter"><span class="handle pointer" onclick="modalOpen(\'' + val["lottery_order_code"] + '\')">查看</span> </td>\n\
                            <td class="textCenter">' + val["lottery_name"] + '</td>\n\
                            <td class="textCenter">' + val["count"] + '</td>\n\
                            <td class="textCenter">' + val["bet_double"] + '</td>\n\
                            <td class="textCenter">' + val["bet_money"] + '</td>\n\
                            <td class="textCenter">' + val["win_amount"] + '</td>\n\
                            <td class="textCenter">' + val["cust_no"] + '</td>\n\
                            <td class="textCenter">' + val["user_name"] + '</td>\n\
                            <td class="textCenter">' + val["user_tel"] + '</td>\n\
                            <td class="textCenter"><div class="am-btn-toolbar" style="text-align:center;">\n\
                            ' + ((val["status"] == 2) ? ('<span class="handle pointer" onclick="modalOpen(\'' + val["lottery_order_code"] + '\',true)"> 出票 </span> | ') : "") + '\n\
                            ' + ((val["status"] == 2) ? ('<span class="handle pointer" onclick="outOrderFalse(\'' + val["lottery_order_code"] + '\')"> 拒绝 </span> | ') : "") + '\n\
                            ' + ((val["status"] == 4 && val["deal_status"] == 1) ? ('<span class="handle pointer" onclick="modalOpen(\'' + val["lottery_order_code"] + '\',true)"> 派奖 </span> | ') : "") + '\n\
                            <span class="handle pointer" onclick="modalOpen(\'' + val["lottery_order_code"] + '\',false)"> 查看 </span> \n\
                            </div></td>\n\
                            </tr>';
                });
                total = json["result"]["pages"] > 0 ? json["result"]["pages"] : 1;
                if (html == '') {
                    html = '<div style="width:100%;text-align:center;">没找到数据</div>';
                } else {

                }
                $("#pwTable tbody").html(html);
                page(data.page, total);
            }
        });
    }
    function page(current, setPageCount) {
        $('.M-box').pagination({
            pageCount: setPageCount,
            current: current,
            homePage: '首页',
            endPage: '末页',
            prevContent: '上一页',
            nextContent: '下一页',
            coping: true,
            callback: function (api) {
                getContent({page: api.getCurrent()});
            }
        });
    }
    $(function () {
        myAjax({
            url: "/api/store/store/lottery-category",
            type: "POST",
            data: {},
            async: false,
            dataType: "json",
            success: function (json) {
                if (json["code"] != 600) {
                    alert(json["msg"]);
                    return false;
                } else {
                    var html = '<option value="">全部</option>';
                    $.each(json["result"], function (k, val) {
                        html += '<option value="' + val["lottery_code"] + '">' + val["lottery_name"] + '</option>';
                    });
                    $("#lottery_code").html(html);
                }
            }
        });
        getContent({page: 1});

        $("#end_date").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [15, 60], //弹框手动偏移量;
            //format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        });
        $("#start_date").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [15, 60], //弹框手动偏移量;
            //format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        });

        $("#end_end_time").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [15, 60], //弹框手动偏移量;
            //format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        });

        $("#start_end_time").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [15, 60], //弹框手动偏移量;
            //format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        });
        $("#filterButton").click(function () {
            var data = $("#filterForm").serializeArray();
            $.each(data, function (key, val) {
                params[val["name"]] = val["value"];
            });
            params.page = 1;
            getContent(params);
            page(1, total);
        });
        $("#resetFilterButton").click(function () {
            $(".sortIcon").removeClass("sortIconActive");
            $("#filterForm").find("input").val("");
            $("#filterForm").find("select").val("");
            params.page = 1;
            getContent(params);
        });
        $(".sortIcon").click(function () {
            var hasActive = $(this).hasClass("sortIconActive");
            $(this).siblings(".sortIcon").removeClass("sortIconActive");
            if (hasActive) {
                $(this).removeClass("sortIconActive");
            } else {
                $(this).addClass("sortIconActive");
            }
            params.create_time = "";
            params.end_time = "";
            $.each($(".sortIconActive"), function () {
                params[$(this).data("name")] = $(this).data("val");
            });
            getContent(params);
        });
    });
</script>
