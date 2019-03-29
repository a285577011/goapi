<ul class="am-nav am-nav-tabs" id="statusArr" style="margin-bottom:10px;">
    <li role="presentation" class="am-active" flag="0"><a onclick="statusArrClick($(this));">按日</a></li>
    <li role="presentation" flag="1"><a onclick="statusArrClick($(this));">按月 </a></li>
    <li role="presentation" flag="2"><a  onclick="statusArrClick($(this));">按彩种</a></li>
</ul>
<div>
    <form class="myForm" id="filterForm">
        <ul class="third_team_ul">
            <li>
                <label>投注时间:</label>
                <input type="text" name="start_date" class='ECalendar form-control' id="start_date" style="width: 100px;display: inline;margin-left:5px;"  value="<?php echo date('Y-m-d', strtotime("-7 days")) ?>" placeholder="开始时间"/>
                -
                <input type="text" name="end_date" class='ECalendar form-control' id="end_date" style="width: 100px;display: inline;"  value=<?php echo date('Y-m-d') ?> placeholder="结束时间"/>
                <input type="button" class="am-btn am-btn-primary" id="filterButton" value="统计">
            </li>
        </ul>
    </form>
    <form class="myForm" id="filterForm1" style="display:none">
        <ul class="third_team_ul">
            <li>
                <label>投注年份:</label>
                <select name="lottery_code" class="form-control" id="years" style="width: 100px;display: inline;margin-left:5px;">
                    <option><?php echo date("Y"); ?></option>
                    <option><?php echo date("Y") - 1; ?></option>
                </select>
                <input type="button" class="am-btn am-btn-primary" id="sendYears" value="统计">
            </li>
        </ul>
    </form>
    <form class="myForm" id="filterForm2" style="display:none">
        <label style="margin-left:15px;" for="">彩种:</label>
        <select name="lottery_code" class="form-control" id="lottery_code" style="width: 100px;display: inline;margin-left:5px;">

        </select>
        <label style="margin-left:15px;" for="">时间: </label>
        <select name="lottery_code" class="form-control" id="timer" style="width: 100px;display: inline;margin-left:5px;">
            <option value="0">今日销售</option>
            <option value="7">最近一周</option>
            <option value="30">最近一个月</option>
            <option value="">自定义时间</option>
        </select>
        <div style="display:none;" id="filterForm4">
            <label>投注时间:</label>
            <input type="text" name="start_time" class='ECalendar form-control' id="start_time" style="width: 100px;display: inline;margin-left:5px;"  value="<?php echo date('Y-m-d', strtotime("-20 days")) ?>" placeholder="开始时间"/>
            -
            <input type="text" name="end_time" class='ECalendar form-control' id="end_time" style="width: 100px;display: inline;"  value=<?php echo date('Y-m-d') ?> placeholder="结束时间"/>
        </div>
        <input type="button" class="am-btn am-btn-primary" id="send" value="统计">
    </form>
    <table class="table" id="pwTable">
        <thead>
            <tr>
                <th style="text-align: center;">日期</th>
                <th style="text-align: center;">下单人数</th>
                <th style="text-align: center;">订单数</th>
                <th style="text-align: center;">订单总金额</th>
                <th style="text-align: center;">出票扣款</th>
                <th style="text-align: center;">实际收入</th>
                <th style="text-align: center;">中奖金额</th>
                <th style="text-align: center;">实兑金额</th>
                <th style="text-align: center;">操作</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(function () {
        getReport();
        //时间框插件弹窗
        $("#start_date").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [160, 120], //弹框手动偏移量;
            format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        });
        $("#end_date").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [180, 120], //弹框手动偏移量;
            format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        })
        $("#start_time").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [120, 120], //弹框手动偏移量;
            format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        });
        $("#end_time").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [150, 120], //弹框手动偏移量;
            format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        })
        $("#filterButton").click(function () {
            getReport();
        })
        $("#send").click(function () {
            getLotteryReport();
        })
        $("#sendYears").click(function () {
            getMonthReport();
        })

    })
//    获取日统计数据
    function getReport() {
        var star = dateChange($("#start_date").val());
        var end = dateChange($("#end_date").val());
        var timestamp2 = Date.parse(new Date(star));
        var timestamp1 = Date.parse(new Date(end));
        if (timestamp1 < timestamp2) {
            alert("请选择正确时间")
        } else {
            myAjax({
                url: "/api/store/store/get-report",
                type: "POST",
                data: {start_date: star, end_date: end},
                async: false,
                dataType: "json",
                success: function (json) {
                    if (json["result"] == "") {
                        $("#pwTable tbody").html("暂无此项统计数据");
                        return false;
                    }
                    var html = "";
                    var counts = 0;
                    var ordernums = 0;
                    var salemoneys = 0;
                    var paymoneys = 0;
                    var winmoneys = 0;
                    var awardmoneys = 0;
                    $.each(json["result"], function (key, val) {
                        counts += eval(val.count);
                        ordernums += eval(val.ordernum);
                        salemoneys += eval(val.salemoney);
                        paymoneys += eval(val.paymoney);
                        winmoneys += eval(val.winmoney);
                        if(val.award_amount==null){
                            val.award_amount=0;
                        }
                        awardmoneys += eval(val.award_amount);
                        html += "<tr styly='text-align: center'>"
                        html += "<td style='text-align: center'><a>" + val.days + "</a></td>"
                        html += "<td style='text-align: center'>" + val.count + "</td>"
                        html += "<td style='text-align: center'>" + val.ordernum + "</td>"
                        html += "<td style='text-align: center'>" + val.salemoney + "</td>"
                        html += "<td style='text-align: center'>" + val.paymoney + "</td>"
                        html += "<td style='text-align: center'>" + eval(val.salemoney - val.paymoney) + "</td>"
                        html += "<td style='text-align: center'>" + val.winmoney + "</td>"
                        html += "<td style='text-align: center'>" + val.award_amount+ "</td>"
                        html += "<td style='text-align: center' ><a onclick='location.href = \"/api/storeback/report/saledetail?type=1&days=" + val.days + "\"'>明细</a></td>"
                        html += "</tr>"
                    });
                    html += "<tr style='font-weight:bold;background-color:#E9ECF3'><td style='text-align: center;font-size:16px'>统计</td><td style='text-align: center;font-size:16px'>" + counts + "</td><td style='text-align: center;font-size:16px'>" + ordernums + "</td><td style='text-align: center;font-size:16px'>" + salemoneys + "</td><td style='text-align: center;font-size:16px'>" + paymoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'>" + eval(salemoneys - paymoneys) + "</td><td style='text-align: center;font-size:16px'>" + winmoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'>" + awardmoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'></td></tr>"
                    if (html == '') {
                        html = '<div style="width:100%;text-align:center;">没找到数据</div>';
                    }
                    $("#pwTable tbody").html(html);
                }
            })
        }

    }
//    获取月统计数据
    function getMonthReport() {
        var years = $("#years").val();
        myAjax({
            url: "/api/store/store/get-month-report",
            type: "POST",
            data: {years: years},
            async: false,
            dataType: "json",
            success: function (json) {
                if (json["result"] == "") {
                    $("#pwTable tbody").html("暂无此项统计数据");
                    return false;
                }
                var html = "";
                var counts = 0;
                var ordernums = 0;
                var salemoneys = 0;
                var paymoneys = 0;
                var winmoneys = 0;
                var awardmoneys = 0;
                $.each(json["result"], function (key, val) {
                    counts += eval(val.count);
                    ordernums += eval(val.ordernum);
                    salemoneys += eval(val.salemoney);
                    paymoneys += eval(val.paymoney);
                    winmoneys += eval(val.winmoney);
                    if(val.award_amount==null){
                        val.award_amount=0;
                    }
                    awardmoneys += eval(val.award_amount);
                    
                    html += "<tr styly='text-align: center'>"
                    html += "<td style='text-align: center'><a>" + val.months + "</a></td>"
                    html += "<td style='text-align: center'>" + val.count + "</td>"
                    html += "<td style='text-align: center'>" + val.ordernum + "</td>"
                    html += "<td style='text-align: center'>" + val.salemoney + "</td>"
                    html += "<td style='text-align: center'>" + val.paymoney + "</td>"
                    html += "<td style='text-align: center'>" + eval(val.salemoney - val.paymoney) + "</td>"
                    html += "<td style='text-align: center'>" + val.winmoney + "</td>"
                    html += "<td style='text-align: center'>" + val.award_amount+ "</td>"
                    html += "<td style='text-align: center' ><a onclick='location.href = \"/api/storeback/report/saledetail?type=1&months=" + val.months + "\"'>明细</a></td>"
                    html += "</tr>"
                });
                html += "<tr style='font-weight:bold;background-color:#E9ECF3'><td style='text-align: center;font-size:16px'>统计</td><td style='text-align: center;font-size:16px'>" + counts + "</td><td style='text-align: center;font-size:16px'>" + ordernums + "</td><td style='text-align: center;font-size:16px'>" + salemoneys + "</td><td style='text-align: center;font-size:16px'>" + paymoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'>" + eval(salemoneys - paymoneys) + "</td><td style='text-align: center;font-size:16px'>" + winmoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'>" + awardmoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'></td></tr>"
                if (html == '') {
                    html = '<div style="width:100%;text-align:center;">没找到数据</div>';
                }
                $("#pwTable tbody").html(html);
            }
        })
    }
    //获取彩种统计数据
    function getLotteryReport() {
       var lottery = $("#lottery_code").val();
        var timer = $("#timer").val();
        var star="";
        var end="";
        if (timer == "") {
            var star = dateChange($("#start_time").val());
            var end = dateChange($("#end_time").val());
            var timestamp2 = Date.parse(new Date(star));
            var timestamp1 = Date.parse(new Date(end));
            if (timestamp1 < timestamp2) {
                alert("请选择正确时间")
            }
        }
        myAjax({
            url: "/api/store/store/get-lottery-report",
            type: "POST",
            data: {lottery_code: lottery,timer: timer,star:star,end:end},
            async: false,
            dataType: "json",
            success: function (json) {
                if (json["result"] == "") {
                    $("#pwTable tbody").html("暂无此项统计数据");
                    return false;
                }
                var html = "";
                var counts = 0;
                var ordernums = 0;
                var salemoneys = 0;
                var paymoneys = 0;
                var winmoneys = 0;
                var awardmoneys = 0;
                $.each(json["result"], function (key, val) {
                    counts += eval(val.count);
                    ordernums += eval(val.ordernum);
                    salemoneys += eval(val.salemoney);
                    paymoneys += eval(val.paymoney);
                    winmoneys += eval(val.winmoney);
                    if(val.award_amount==null){
                        val.award_amount=0;
                    }
                    awardmoneys += eval(val.award_amount);
                    
                    html += "<tr styl='text-align: center'>"
                    html += "<td style='text-align: center'><a>" + val.lottery_name + "</a></td>"
                    html += "<td style='text-align: center'>" + val.count + "</td>"
                    html += "<td style='text-align: center'>" + val.ordernum + "</td>"
                    html += "<td style='text-align: center'>" + val.salemoney + "</td>"
                    html += "<td style='text-align: center'>" + val.paymoney + "</td>"
                    html += "<td style='text-align: center'>" + eval(val.salemoney - val.paymoney) + "</td>"
                    html += "<td style='text-align: center'>" + val.winmoney + "</td>"
                    html += "<td style='text-align: center'>" + val.award_amount+ "</td>"
                    html += "<td style='text-align: center' ><a onclick='location.href = \"/api/storeback/report/saledetail?type=1&lotteryId=" + val.lottery_id + "&totaldays=" + timer +"&lotteryname=" + val.lottery_name +"&star=" + star +"&end=" + end + "\"'>明细</a></td>"
                    html += "</tr>"
                });
                html += "<tr style='font-weight:bold;background-color:#E9ECF3'><td style='text-align: center;font-size:16px'>统计</td><td style='text-align: center;font-size:16px'>" + counts + "</td><td style='text-align: center;font-size:16px'>" + ordernums + "</td><td style='text-align: center;font-size:16px'>" + salemoneys + "</td><td style='text-align: center;font-size:16px'>" + paymoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'>" + eval(salemoneys - paymoneys) + "</td><td style='text-align: center;font-size:16px'>" + winmoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'>" + awardmoneys.toFixed(2) + "</td><td style='text-align: center;font-size:16px'></td></tr>"
                if (html == '') {
                    html = '<div style="width:100%;text-align:center;">没找到数据</div>';
                }
                $("#pwTable tbody").html(html);
            }
        })
    }
    //监测时间框select的值变化
    $("#timer").change(function () {
        if ($("#timer").val() == "") {
            $("#filterForm4").css("display", "inline-block");
        } else {
            $("#filterForm4").css("display", "none");
        }
    })
    //页面切换选择
    function statusArrClick(_this) {
        var statusArr = _this.data("val");
        $("#statusArr").find("li").removeClass("am-active");
        _this.parent("li").addClass("am-active");
        if (_this.parent("li").attr("flag") == 0) {
            $("#filterForm").css("display", "block");
            $("#filterForm1").css("display", "none");
            $("#filterForm2").css("display", "none");
            $("#filterForm4").css("display", "none");
            $($("#pwTable thead th")[0]).html("日期");
            $("#pwTable tbody").html("");
            getReport()
        } else if (_this.parent("li").attr("flag") == 1) {
            $("#filterForm").css("display", "none");
            $("#filterForm1").css("display", "block");
            $("#filterForm2").css("display", "none");
            $("#filterForm4").css("display", "none");
            $($("#pwTable thead th")[0]).html("月份");
            $("#pwTable tbody").html("");
            getMonthReport()
        } else {
            $("#filterForm").css("display", "none");
            $("#filterForm1").css("display", "none");
            $("#filterForm2").css("display", "block");
            $($("#pwTable thead th")[0]).html("彩种");
            $("#pwTable tbody").html("");
            getSaleLottery();
            getLotteryReport();
        }

    }
    //获取在售彩种
    function getSaleLottery(){
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
                    var html = '<option value="0">全部</option>';
                    $.each(json["result"], function (k, val) {
                        html += '<option value="' + val["lottery_code"] + '">' + val["lottery_name"] + '</option>';
                    });
                    $("#lottery_code").html(html);
                }
            }
        });
    }
    //日期格式转换
    function dateChange($date){
        var dateAry = $date.split("-");
        var month = dateAry["1"];
        var day = dateAry["2"];
        if(month<10){
            month = "0"+month;
        }
        if(day<10){
            day = "0"+day;
        }
        var newDate = dateAry["0"]+"-"+month+"-"+day;
        return newDate;
    }
</script>