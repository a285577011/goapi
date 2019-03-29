
<form class="myForm" id="mxForm">
    <label>投注时间:</label> 
    <input type="text" class='ECalendar form-control' id="days" style="width: 100px;display: inline;margin-left:5px;"  value="<?php echo isset($_GET['days']) ? $_GET['days'] : "" ?>"  disabled="true"/>
    <label>投注月份:</label> 
    <input type="text" class='ECalendar form-control' id="months" style="width: 100px;display: inline;margin-left:5px;"  value="<?php echo isset($_GET['months']) ? $_GET['months'] : "" ?>"  disabled="true"/>
    <label>投注彩种:</label> 
    <input type="text"  class='ECalendar form-control' id="lotteryId" style="width: 100px;display: inline;margin-left:5px;" flag="<?php echo isset($_GET['lotteryId']) ? $_GET['lotteryId'] : "" ?>" value="<?php echo isset($_GET['lotteryname']) ? $_GET['lotteryname'] : "" ?>"  disabled="true" >
    <label>投注天数:</label> 
    <input type="text"  class='ECalendar form-control' id="totaldays" style="width: 100px;display: inline;margin-left:5px;" flag="<?php echo isset($_GET['totaldays']) ? $_GET['totaldays'] : "" ?>"value="<?php if (isset($_GET['lotteryname'])) {
    if($_GET['totaldays']==""){
        echo "";
    }else if($_GET['totaldays']!=""&&$_GET['totaldays'] == 0) {
        echo "今日销售";
    }else{
        echo "最近" . $_GET['totaldays'] . "天";
    }
} ?>"  disabled="true">
    <label>投注开始时间:</label> 
    <input type="text"  class='ECalendar form-control' id="star" style="width: 100px;display: inline;margin-left:5px;" flag="<?php echo isset($_GET['star']) ? $_GET['star'] : "" ?>"value="<?php if (isset($_GET['star'])) {echo $_GET['star'];} ?>"  disabled="true">
    -
    <input type="text"  class='ECalendar form-control' id="end" style="width: 100px;display: inline;margin-left:5px;" flag="<?php echo isset($_GET['end']) ? $_GET['end'] : "" ?>"value="<?php if (isset($_GET['end'])) {echo $_GET['end'];} ?>"  disabled="true">
    <input type="button" class="am-btn am-btn-primary" style="width:80px;display: inline;margin-left:20px;" value="返回" onclick="window.history.back()">
</form>

<table class="table" id="pwTable">
    <thead>
        <tr>
            <th style="text-align: center;">方案编号</th>
            <th style="text-align: center;">投注时间</th>
            <!--<th style="text-align: center;">投注方案</th>-->
            <th style="text-align: center;">过关方式</th>
            <th style="text-align: center;">彩种玩法</th>
            <th style="text-align: center;">注数</th>
            <th style="text-align: center;">倍数</th>
            <th style="text-align: center;">投注金额(元)</th>
            <th style="text-align: center;">中奖金额(元)</th>
            <th style="text-align: center;">实兑金额(元)</th>
            <th style="text-align: center;">投注会员</th>
            <th style="text-align: center;">会员手机号</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
<script type="text/javascript">
    $(function (){
        getSaleDetail()
        //时间框插件弹窗
        $("#start_date").ECalendar({
            type: "date", //模式，time: 带时间选择; date: 不带时间选择;
            stamp: false, //是否转成时间戳，默认true;
            offset: [120, 120], //弹框手动偏移量;
            format: "yyyy-mm-dd", //时间格式 默认 yyyy-mm-dd hh:ii;
            skin: 3, //皮肤颜色，默认随机，可选值：0-8,或者直接标注颜色值;
            step: 10, //选择时间分钟的精确度;
            callback: function (v, e) {
            } //回调函数
        });
        $("#end_date").ECalendar({
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
            getSaleDetail();
        })
        // 获取订单明细
        function getSaleDetail(options) {
            var timer = $("#days").val();
            var months = $("#months").val();
            var lotteryId = $("#lotteryId").attr("flag");
            var totaldays = $("#totaldays").attr("flag");
            var star = $("#star").attr("flag");
            var end = $("#end").attr("flag");
            var data = $.extend({timer: timer, months: months, page: 1, lotteryId: lotteryId, totaldays: totaldays,star:star,end:end}, options);
            myAjax({
                url: "/api/store/store/get-sale-detail",
                type: "POST",
                data: data,
                async: false,
                dataType: "json",
                success: function (json) {
                    if (json["code"] != 100) {
                        alert(json["msg"]);
                        return false;
                    }
                    var html = "";
                    $.each(json["result"]["list"], function (key, val) {
                        if(val.award_amount==null){
                            val.award_amount=0;
                        }
                        html += "<tr styly='text-align: center'>"
                        html += "<td style='text-align: center'><a>" + val.lottery_order_code + "</a></td>"
                        html += "<td style='text-align: center'>" + val.create_time + "</td>"
//                        html += "<td style='text-align: center'><a> 查看</a> </td>"
                        html += "<td style='text-align: center'>" + val.play_name + "</td>"
                        html += "<td style='text-align: center'>" + val.lottery_name + "</td>"
                        html += "<td style='text-align: center'>" + val.count + "</td>"
                        html += "<td style='text-align: center'>" + val.bet_double + "</td>"
                        html += "<td style='text-align: center'>" + val.bet_money + "</td>"
                        html += "<td style='text-align: center'>" + val.win_amount + "</td>"
                        html += "<td style='text-align: center'>" + val.award_amount+ "</td>"
                        html += "<td style='text-align: center'>" + val.cust_no + "</td>"
                        html += "<td style='text-align: center'>" + val.user_tel + "</td>"
                        html += "</tr>"
                    });
                    if (html == '') {
                        html = '<div style="width:100%;text-align:center;">没找到数据</div>';
                    }

                    $("#pwTable tbody").html(html);
                    total = json["result"]["pages"] > 0 ? json["result"]["pages"] : 1;
                    if (html == '') {
                        html = '<div style="width:100%;text-align:center;">没找到数据</div>';
                    }
                    page(data.page, total);
                }
            })
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
                    getSaleDetail({page: api.getCurrent()});
                }
            });
        }
    })
</script>
