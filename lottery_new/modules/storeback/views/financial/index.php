<div>
    <table class="table" id="pwTable">
        <thead>
            <tr>
                <th style="text-align: center;">用途</th>
                <th style="text-align: center;">账户余额</th>
                <th style="text-align: center;">交易金额</th>
                <th style="text-align: center;">交易时间</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
   <div class="paginationContainer" style="bottom: 40px;position: fixed;right: 8px;">
        <div class="M-box"></div>
    </div>
</div>
<script>
    var total = 0;
    var params = {};
    $(function () {
        getDetail({page:1});
        function getDetail(options) {
            var data = $.extend({token_type:"storeBack", page: 1, token: loaddata.get("token")}, params, options);
            params = data;
            myAjax({
                url: "/api/store/store/get-trans-detail",
                async: false,
                data: data,
                type: "POST",
                dataType: "json",
                success: function (json){
                    console.log(json);
                    var html = "";
                    if(json["result"]["records"]!=""||json["result"]["records"]!=null){
                        $.each(json["result"]["records"], function (key, val) {
                            html += '<tr><td class="textCenter">' + val["body"] + '</td>\n\
                                     <td class="textCenter">' + val["balance"] + '</td>\n\
                                    <td class="textCenter">' + val["pay_money"] + '</td>\n\
                                    <td class="textCenter">' + val["create_time"] + '</td></tr>';
                        });
                    }
                    total = json["result"]["pages"] > 0 ? json["result"]["pages"] : 1;
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
                    getDetail({page: api.getCurrent()});
                }
            });
        }
    })
</script>

