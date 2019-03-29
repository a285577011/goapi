<!doctype html>
<html>

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>咕啦体育-彩店登录</title>
        <meta name="description" content="咕啦体育-彩店登录">
        <meta name="keywords" content="index">
        <meta name="renderer" content="webkit">
        <meta http-equiv="Cache-Control" content="no-siteapp" />
        <link rel="icon" type="image/png" href="/api/i/ico.jpg">
        <link rel="apple-touch-icon-precomposed" href="assets/i/app-icon72x72@2x.png">
        <meta name="apple-mobile-web-app-title" content="咕啦体育-彩店登录" />
        <link rel="stylesheet" href="/api/css/amazeui.min.css" />
        <link rel="stylesheet" href="/api/css/admin.css">
        <link rel="stylesheet" href="/api/css/app.css">
        <script src="/api/js/jquery.min.js"></script>
        <script src="/api/js/amazeui.min.js"></script>
        <script src="/api/js/loaddata.js"></script>
    </head>

    <body data-type="login">

        <div class="am-g myapp-login">
            <div class="myapp-login-logo-block  tpl-login-max">
                <div class="myapp-login-logo-text">
                    <div class="myapp-login-logo-text">
                        彩店<span> 登录</span> <i class="am-icon-skyatlas"></i>
                    </div>
                </div>
                <div class="login-font" id="loginWay">
<!--                    <span style="cursor:pointer" flag="1">手机登录 </span> or <span  style="cursor:pointer" flag="2"> 门店账号登录</span> -->
                </div>
                <div class="am-u-sm-10 login-am-center">
                    <form class="am-form" id="loginForm">
                        <fieldset>
                                <div class="am-form-group">
                                    <input type="hidden" name="type" value="3" />
                                    <input type="text" id="account" name="account" placeholder="请输入手机号">
                                </div>
                                <div class="am-form-group">
                                    <input type="password" id="password" name="password" placeholder="请输入密码">
                                </div>
                            <p><button type="button" class="am-btn am-btn-default" id="loginButton">登录</button></p>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            $(function () {
                //选择登录方式
//                $("#loginWay span:first").addClass("Ac");
//                $("#loginWay span").click(function(){
//                   $("#loginWay span").removeClass("Ac");
//                   $(this).addClass("Ac");
//                   var flag=$(this).attr("flag");
//                   if(flag==1){
//                      $("#account").attr('placeholder','请输入手机号')
//                   }else{
//                      $("#account").attr('placeholder','请输入门店账号')
//                   }
//                })
                //登录
                $("#loginButton").click(function () {
                    var account = $("#account").val();
                    var password = $("#password").val();
//                    var loginType="";
//                    for(var i=0;i<$("#loginWay span").length;i++){
//                        if($($("#loginWay span")[i]).hasClass("Ac")){
//                            loginType=$($("#loginWay span")[i]).attr("flag");
//                        }
//                    }
                    if(account==""||password==""){
                        alert("请将账号密码填写完整")
                    }else{
                        $.ajax({
                            url: "/api/user/user/store-back-login",
                            type: "POST",
                            data: {"account":account,"password":password,},
                            async: false,
                            dataType: "json",
                            success: function (json) {
                                if (json["code"] == 600) {
                                    loaddata.set("token", json["result"]["token"]);
                                    location.href = "/api/storeback";
                                } else {
                                    alert(json["msg"]);
                                }
                            }
                        });
                    }
                    
                });
            });
        </script>
    </body>

</html>
