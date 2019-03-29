<header class="am-topbar am-topbar-inverse admin-header">
    <div class="am-topbar-brand">
        <a href="javascript:;" class="tpl-logo">
            <img src="/img/logo.png" alt="">
        </a>
    </div>
    <div class="am-icon-list tpl-header-nav-hover-ico am-fl am-margin-right">

    </div>

    <button class="am-topbar-btn am-topbar-toggle am-btn am-btn-sm am-btn-success am-show-sm-only" data-am-collapse="{target: '#topbar-collapse'}"><span class="am-sr-only">导航切换</span> <span class="am-icon-bars"></span></button>

    <div class="am-collapse am-topbar-collapse" id="topbar-collapse">

        <ul class="am-nav am-nav-pills am-topbar-nav am-topbar-right admin-header-list tpl-header-list">
            <li class="am-dropdown" data-am-dropdown data-am-dropdown-toggle>
                <a class="am-dropdown-toggle tpl-header-list-link" href="javascript:;">
                    <span class="tpl-header-list-user-ico"> <i class="am-icon-user-md" style="font-size: 18px;margin-right: 5px;"></i></span><span class="tpl-header-list-user-nick" id="username" style="font-size: 16px;"></span>
                </a>
            </li>
            <li><a onclick="logout();" class="tpl-header-list-link"><span class="am-icon-sign-out tpl-header-list-ico-out-size"></span></a></li>
        </ul>
    </div>
    <div class="topmenu">
        <div id="topnavContainer">
            <?= $html_topnav ?>
        </div>
    </div>

    <script type="text/javascript">
        function logout() {
            loaddata.remove("token");
            window.location.href = "/api/storeback/login";
        }
        $(function () {
            myAjax({
                url: "/api/store/store/basic-info",
                type: "POST",
                async: false,
                dataType: "json",
                success: function (json) {
                    var name;
                    if (json["code"] == 600) {
                        if (json["result"]["optUser"] == null) {
                            name = json["result"]["store_name"];
                        } else {
                            name = json["result"]["optUser"]["user_name"];
                        }
                        $("header #username").html(name);
                    } else {
                        alert(json["msg"]);
                    }
//                    $("header #username").html(json["result"]["user_name"]);
                }
            });
        });
    </script>
</header>