<div>
    <form class="myForm" id="filterForm">
        <ul class="third_team_ul">
            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">用户编号  </label>
                <input type="text"class="form-control" name="conCustNo" style="width: 200px;display: inline;margin-left:5px;"  value=""/>
            </li>
            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">用户姓名  </label>
                <input type="text" class="form-control" name="conUserName" style="width: 200px;display: inline;margin-left:5px;"  value=""/>
            </li>
            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">手机号  </label>
                <input type="text" class="form-control" name="conUserTel" style="width: 200px;display: inline;margin-left:5px;"  value=""/>
            </li>
            <li class="third_team_ul">
                <label style="margin-left:15px;" for="">使用状态  </label>
                <select class="form-control" name="conStatus" style="width: 100px;display: inline;margin-left:5px;">
                    <option value="">请选择</option>
                    <option value="1">启用</option>
                    <option value="2">禁用</option>
                </select>
            </li>
        </ul>
    </form>
    <div style="float: left;margin: 6px;">
        <button type="button" class="am-btn am-btn-primary" id="resetFilterButton" style="display: inline-block;position: relative;margin-right: 5px;">重置</button>
        <button type="button" class="am-btn am-btn-primary" id="addButton" style="display: inline-block;position: relative;margin-right: 5px;">新增</button>
        <button type="button" class="am-btn am-btn-primary" id="filterButton" style="display: inline-block;position: relative;">搜索</button>
    </div>

    <table class="table" id="pwTable">
        <thead>
            <tr>
                <th style="text-align: center;">序号</th>
                <th style="text-align: center;">用户编号</th>
                <th style="text-align: center;">用户姓名</th>
                <th style="text-align: center;">手机号</th>
                <th style="text-align: center;">使用状态</th>
                <th style="text-align: center;">创建时间</th>
                <th style="text-align: center;">操作</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>

    <div class="am-modal am-modal-no-btn" tabindex="-1" id="your-modal">
        <div class="am-modal-dialog" style="width: 700px;">
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
</div>
<script type="text/javascript">
    var params = {};
    function delStoreOperator(id, name) {
        var ret = confirm("确认删除【" + name + "】操作员");
        if (ret == true) {
            myAjax({
                url: "/api/store/store/del-store-operator",
                data: {store_operator_id: id},
                type: "POST",
                dataType: "json",
                success: function (json) {
                    if (json["code"] == 600) {
                        getOperator(params);
                    }
                    alert(json["msg"]);
                }
            });
        }
    }
    function insertStoreOperator() {
        var userTel = $("#userTel").val()
        myAjax({
            url: "/api/store/store/insert-operator",
            type: "POST",
            data: {userTel: userTel},
            dataType: "json",
            success: function (json) {
                if (json["code"] == 600) {
                    getOperator(params);
                    $('#your-modal').myModal('close');
                }
                alert(json["msg"]);
            }
        });
    }
    function getOperator(options) {
        params = $.extend({conStoreOperatorName: "", conStoreOperatorAccount: "", conStatus: ""}, options);
        myAjax({
            url: "/api/store/store/get-store-operator",
            async: false,
            data: params,
            type: "Post",
            success: function (json) {
                var html = "";
                $.each(json.result, function (key, val) {
                    html += '<tr><td class="textCenter">' + (key + 1) + '</td>\n\
                            <td class="textCenter">' + val["cust_no"] + '</td>\n\
                            <td class="textCenter">' + val["user_name"] + '</td>\n\
                            <td class="textCenter">' + val["user_tel"] + '</td>\n\
                            <td class="textCenter">' + val["status_name"] + '</td>\n\
                            <td class="textCenter">' + val["create_time"] + '</td>\n\
                            <td class="textCenter"><div class="am-btn-toolbar" style="text-align:center;">\n\
                            <span class="handle pointer" onclick="statusSwitch( ' + val["store_operator_id"] + ',' + (val["status"] == 1 ? 2 : 1) + ')"> ' + (val["status"] == 1 ? "禁用" : "启用") + ' |</span>\n\
                            <span class="handle pointer" onclick="delStoreOperator(' + val["store_operator_id"] + ',\'' + val["user_name"] + '\');"> 删除 </span> \n\</div></td></tr>';
                });
                $("#pwTable tbody").html(html);
            }
        });
    }
    function statusSwitch(id, status) {
        myAjax({
            url: "/api/store/store/status-switch",
            data: {store_operator_id: id, status: status},
            type: "POST",
            dataType: "json",
            success: function (json) {
                if (json["code"] == 600) {
                    getOperator(params);
                }
                alert(json["msg"]);
            }
        });
    }
    $(function () {
        getOperator(params);
        $("#addButton").click(function () {
            var $modal = $("#your-modal");
            var html = '<form id="insertStoreOperator" enctype="multipart/form-data"><table class="table am-table am-table-bordered am-table-striped tablePadding6">\n\
                        <tr><td style="width:80px;"><span style="color:red;">*</span>手机号</td><td style="text-align: left;"><input name="userTel" id="userTel" type="text" class="form-control" style="width:200px;display:inline-block;" minlength="11" maxlength="11" required/><span style="color:#bbb;margin-left:10px;">要绑定升级为门店操作员对应会员手机号</span></td></tr>\n\
                        </table>\n\
                    <div style="text-align:center;">\n\
                        <button type="button" class="am-btn am-btn-default" onclick="$(\'#your-modal\').myModal(\'close\');" style="width:100px;margin-right:20px;">返回</button><button type="button" class="am-btn am-btn-primary" onclick="insertStoreOperator();" style="width:100px;margin-right:20px;">升级操作员</button>\n\
                    </div>';
            $modal.find(".modalContent").html(html);
            $modal.myModal();
            $('#insertStoreOperator').validator({});
        });
        $("#filterButton").click(function () {
            var condition = $("#filterForm").serializeArray();
            var data = [];
            $.each(condition, function (key, val) {
                data[val["name"]] = val["value"];
            });
            getOperator(data);
        });

        $("#resetFilterButton").click(function () {
            $("#filterForm").find("input").val("");
            $("#filterForm").find("select").val("");
            getOperator({});
        });
    });
</script>