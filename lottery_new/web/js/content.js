/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function urlActive(url) {
    $('.tpl-page-container .tpl-left-nav-menu .tpl-left-nav-item a').removeClass('active');
    if (url != '') {
        if ($('.tpl-page-container .tpl-left-nav-menu .tpl-left-nav-item a[data-url="' + url + '"]').length > 0) {
            $('.tpl-page-container .tpl-left-nav-menu .tpl-left-nav-item a[data-url="' + url + '"]').addClass('active');
            var topnavUrl = $('.tpl-page-container .tpl-left-nav-menu .tpl-left-nav-item a[data-url="' + url + '"]').parents(".tpl-left-nav-menu").data("url");
            var topUrl = $("#topnavContainer .mytopnav[data-url='" + topnavUrl + "']").data("url");
            $("#topnavContainer .mytopnav").removeClass("topnavactive");
            $("#topnavContainer .mytopnav[data-url='" + topnavUrl + "']").addClass("topnavactive");
            $(".tpl-left-nav-list .tpl-left-nav-menu").hide();
            $(".tpl-left-nav-list .tpl-left-nav-menu[data-url='" + topUrl + "']").show();
        } else {
//            $('.tpl-page-container .tpl-left-nav-menu .tpl-left-nav-item[data-url="/admin/admin/index"]').children('a').addClass('active');
        }
    } else {
//        $('.tpl-page-container .tpl-left-nav-menu .tpl-left-nav-item[data-url="/admin/admin/index"]').children('a').addClass('active');
    }
}
function msgConfirm(title, msg, onConfirmfunction, onCancelfunction) {
    var defaultfun = function () {};
    onConfirmfunction = onConfirmfunction || defaultfun;
    onCancelfunction = onCancelfunction || defaultfun;
    if ($("#my-confirm").length > 0) {
        $("#my-confirm").remove();
    }
    var html = '<div class="am-modal am-modal-confirm" tabindex="-1" id="my-confirm">\
                            <div class="am-modal-dialog">\
                              <div class="am-modal-hd">' + title + '</div>\
                              <div class="am-modal-bd">\
                                ' + msg + '\
                              </div>\
                              <div class="am-modal-footer">\
                                <span class="am-modal-btn" data-am-modal-confirm>确定</span>\
                                <span class="am-modal-btn" data-am-modal-cancel>取消</span>\
                              </div>\
                            </div>\
                          </div>';
    $("body").append(html);
    $('#my-confirm').modal({
        relatedTarget: this,
        onConfirm: function () {
            onConfirmfunction();
        },
        // closeOnConfirm: false,
        onCancel: function () {
            onCancelfunction();
        }
    });
}
function msgAlert(msg, fun) {
    var emptyfun = function () {};
    fun = fun || emptyfun;
    if ($("#my-alert").length > 0) {
        $("#my-alert").remove();
    }
    var html = '<div class="am-modal am-modal-alert" tabindex="-1" id="my-alert">\
                                <div class="am-modal-dialog">\
                                  <div class="am-modal-hd">提示</div>\
                                  <div class="am-modal-bd">\
                                    ' + msg + '\
                                  </div>\
                                  <div class="am-modal-footer">\
                                    <span class="am-modal-btn" data-am-modal-confirm>确定</span>\
                                  </div>\
                                </div>\
                              </div>';
    $("body").append(html);
    $('#my-alert').modal({
        relatedTarget: this,
        onConfirm: function () {
            fun();
        },
        onCancel: function () {
            fun();
        }
    });
}

function closepagenavclick(_this) {
    var url = $(_this).parents("li").data("url");
    var i = $(_this).parents("li.iframenav").index();
    $(".pageiframe[data-url='" + url + "']").remove();
    $(_this).parents("li").remove();
    if ($('#iframenavs .iframenav').eq(i).length > 0) {
        iframenavclick($('#iframenavs .iframenav').eq(i).children(".pagenav"));
        return false;
    }
    if ($('#iframenavs .iframenav').eq(i - 1).length > 0) {
        iframenavclick($('#iframenavs .iframenav').eq(i - 1).children(".pagenav"));
        return false;
    }
}
function iframenavclick(_this) {
    var url = $(_this).parents("li").data("url");
    $('#iframenavs .iframenav span').removeClass("activepage");
    $(_this).addClass("activepage");
    $(".pageiframe").removeClass("pageiframeactive");
    $(".pageiframe[data-url='" + url + "']").addClass("pageiframeactive");
    urlActive(url);
}
function leftNavItemclick(_this) {
    var url = $(_this).data("url");
    if ($(_this).data("url") == "") {
        return false;
    }
    if ($("#iframenavs .iframenav[data-url='" + url + "']").length > 0) {
        iframenavclick($("#iframenavs .iframenav[data-url='" + url + "']").children("span"));
        $(".content iframe[data-url='" + url + "']").attr("src", url);
        return false;
    }
//    var url = $(_this).data("url");
    var controllername = $(_this).find(".controllername").text();
    var html_nav = '<li class="iframenav" data-url="' + url + '">\
                        <span class="pagenav activepage"><span style="display: inline-block;height: 19px;width:100%;">' + controllername + '<i class="am-icon-close closepagenav"></i></span></span>\
                    </li>';
    var html = '<iframe src="' + url + '" class="pageiframe pageiframeactive"  data-url="' + url + '"></iframe>'
    $('#iframenavs .iframenav span').removeClass("activepage");
    $(".pageiframe").removeClass("pageiframeactive");
    $(".content").append(html);
    $("#iframenavs").append(html_nav);
    $('#iframenavs li .closepagenav').unbind("click");
    $('#iframenavs .iframenav span').unbind("click");
    $('#iframenavs li .closepagenav').click(function () {
        closepagenavclick(this);
    });
    $('#iframenavs .iframenav span').click(function () {
        iframenavclick(this);
    });
    urlActive(url);
}
function mytopnavClick(_this) {
    var url = $(_this).data("url");
    $("#topnavContainer .mytopnav").removeClass("topnavactive");
    $(_this).addClass("topnavactive");
    $(".tpl-left-nav-list .tpl-left-nav-menu").hide();
    $(".tpl-left-nav-list .tpl-left-nav-menu[data-url='" + url + "']").show();
    $(".tpl-left-nav-list .tpl-left-nav-menu[data-url='" + url + "']").find("a").each(function () {
        if ($(this).data("url") != undefined && $(this).data("url") != "") {
            leftNavItemclick(this);
            return false;
        }
    });
}
function modDisplay(options) {
    var okFun = function () {}
    var cacelFun = function () {
        closeMask();
    }
    var data = $.extend({width: 800, height: 600, title: "", content: "", onOk: okFun, onCacel: cacelFun, url: "", needFooter: false}, options);
    if (data.url != "") {
        $.ajax({
            url: data.url,
            type: "get",
            async: false,
            dataType: "html",
            success: function (html) {
                data.content += html;
            }
        });
    }
    var html = '<div class="masklayer">\
                    <div class="centerContent" style = "width: ' + data.width + 'px;height: ' + data.height + 'px;margin-top: -' + (data.height / 2) + 'px;" >\
                        <div class="contentTitle" style="margin-left:20px;padding-top:20px;width:' + (data.width - 40) + 'px;height:50px;"><span style="font-size:16px;font-weight:700;">' + data.title + '</span><a class="buttomspan closeMyMask" style="display:inline-block;float:right;">关闭</a><hr style="margin-top:5px;"/></div>\
                        <div class="contentBody" style="' + (data.needFooter ? ('height:' + (data.height - 100) + 'px;') : ('height:' + (data.height - 50) + 'px;')) + 'margin-left:20px;margin-right:20px;overflow:auto;">' + data.content + '</div>\
                        ' + (data.needFooter ? '<div style="text-align:center;"><button class="am-btn am-btn-primary myclickSubmit" style="margin-right:5px;">提交</button><button class="am-btn am-btn-primary myclickCacel" style="margin-left:5px;">取消</button></div>' : '')
    '\n\
                    </div>\
                </div>';
    $("body").append(html);
    $(".myclickSubmit").click(function () {
        data.onOk();
    });
    $(".myclickCacel").click(function () {
        data.onCacel();
    });
    $(".closeMyMask").click(function () {
        closeMask();
    });
}
function closeMask() {
    $('#your-modal').myModal('close');
}
$.fn.setTime = function (options) {
    $(this).click(function () {
        options = $.extend({time: "00:00:00", h: true, i: true, s: true}, options, {time: $(this).val()});
        var strs = [];
        for (var d = 0; d < 60; d++) {
            if (d < 10) {
                strs[d] = "0" + d;
            } else {
                strs[d] = d + "";
            }
        }
        var vals = (options.time).split(":");
        var id = "setTime" + (Date.parse(new Date()));
        var _this = this;
        var html = '<div style="margin-top:10px;text-align:right;" id="' + id + '">';
        html += '<div style="margin-top:10px;text-align:right;width:100%;">';
        if (options.h) {
            html += '<select class="form-control setTimeH" style="width:80px;display:inline-block;">';
            for (var h = 0; h < 24; h++) {
                html += '<option value="' + strs[h] + '" ' + (vals[0] == strs[h] ? 'selected="selected"' : '') + '>' + strs[h] + '</option>';
            }
            html += '</select><span style="width:20px;display:inline-block;text-align:center;color:#0e90d2;font-size:12px;">时</span>';
        }
        if (options.i) {
            html += '<select class="form-control setTimeI" style="width:80px;display:inline-block;">';
            for (var i = 0; i < 60; i++) {
                html += '<option value="' + strs[i] + '" ' + (vals[1] == strs[i] ? 'selected="selected"' : '') + '>' + strs[i] + '</option>';
            }
            html += '</select><span style="width:20px;display:inline-block;text-align:center;color:#0e90d2;font-size:12px;">分</span>';
        }
        if (options.s) {
            html += '<select class="form-control setTimeS" style="width:80px;display:inline-block;">';
            for (var s = 0; s < 60; s++) {
                html += '<option value="' + strs[s] + '" ' + (vals[2] == strs[s] ? 'selected="selected"' : '') + '>' + strs[s] + '</option>';
            }
            html += '</select><span style="width:20px;display:inline-block;text-align:center;color:#0e90d2;font-size:12px;">秒</span>';
        }
        html += '</div>';
        html += '<button class="am-btn am-btn-primary setTimesubmit" style="margin-top:10px;margin-right:10px;">确定</button><button class="am-btn am-btn-primary" onclick="closeMask();" style="margin-top:10px;margin-right:10px;">取消</button>';
        html += '</div>';
        modDisplay({width: 400, height: 150, title: "时间选择:", content: html});
        $("#" + id + " .setTimesubmit").click(function () {
            var time = "";
            if (options.h) {
                time += $("#" + id + " .setTimeH").val();
            }
            if (options.i) {
                if (time != "") {
                    time += ":";
                }
                time += $("#" + id + " .setTimeI").val();
            }
            if (options.s) {
                if (time != "") {
                    time += ":";
                }
                time += $("#" + id + " .setTimeS").val();
            }
            $(_this).val(time);
            closeMask();
        });
    });
}

function myAjax(options) {
    options = $.extend({url: "", data: {}, async: false, dataType: "json", type: "GET", success: function () {}}, options);
    options.data.token = loaddata.get("token");
    options.data.token_type = "storeBack";
    $.ajax({
        url: options.url,
        data: options.data,
        async: options.async,
        dataType: options.dataType,
        type: options.type,
        success: function (json) {
//            window.top.loadMask(false);
            if (json["code"] == 400 || json["code"] == 402 || json["code"] == 422) {
                window.top.location.href = "/api/storeback/login";
            } else {
                options.success(json);
            }
        },
        complete: function (XMLHttpRequest, textStatus) {
            if (textStatus == 'error') {
                alert("网络错误");
            }
        }
    });
}
$.fn.myModal = function (str) {
    if (str == "close") {
        this.modal("close");
        setTimeout('window.top.body.find(".content").css("z-index", 0);', 300);
    } else {
        window.top.body.find(".content").css("z-index", 2);
        this.modal();
    }
}
function connectSocket() {
    var custNo;
    myAjax({
        url: "/api/store/store/basic-info",
        type: "POST",
        data: {},
        async: false,
        dataType: "json",
        success: function (json) {
            if (json["code"] == 600) {
                custNo = json["result"]["cust_no"];
            }
        }
    });
    // 连接服务端
    var socket = io(window.top.webSocketIp);
    // 连接后登录
    console.log('connect');
    socket.on('connect', function () {
        socket.emit('login', custNo);
    });
    // 后端推送来消息时
    socket.on('new_msg', function (msg) {
//        if (params.statusArr == "2") {
//            getContent(params);
//        }
        if (window.top.soundSwitch == true) {
            var url = "http://tts.baidu.com/text2audio?lan=zh&ie=UTF-8&text=" + encodeURI(msg);
            var n = new Audio(url);
            n.src = url;
            n.play();
        }
    });
}

$.fn.bigShow = function () {
    var _this = $(this);
    _this.click(function () {
        $(".imgPreClose").unbind();
        var src = $(this).attr("src");
        var div = $("body").append("\
<div class='imgPreBigDiv'>\n\
<img class='imgPreBigShow' src='" + src + "' />\n\
<div><i class='am-icon-times-circle imgPreClose'></i></div>\n\
<div class='imgBtnContainer'><span class='btnSpan'><button id='leftBtn' class='imgBtn'><i class='am-icon-rotate-left' style='margin:3px;'></i>左旋转</button><button id='rightBtn' class='imgBtn'><i class='am-icon-repeat' style='margin:3px;'></i>右旋转</button><button class='imgBtn' id='closeImgBtn'><i class='am-icon-remove' style='margin:3px;'></i>关  闭</button></span></div>\n\
</div>");

        var rotate = 0;
        $("#leftBtn").click(function () {
            rotate = rotate - 90;
            $(".imgPreBigShow").css("transform", 'translate(-50%,-50%) rotate(' + (rotate) + 'deg)');
        });
        $("#rightBtn").click(function () {
            rotate = rotate + 90;
            $(".imgPreBigShow").css("transform", 'translate(-50%,-50%) rotate(' + (rotate) + 'deg)');
        });
        $(".imgPreClose,#closeImgBtn").click(function () {
            $(".imgPreBigDiv").remove();
        });
    });
}

function msgPrompt(title, msg,value, onConfirmfunction, onCancelfunction) {
    var defaultfun = function () {};
    onConfirmfunction = onConfirmfunction || defaultfun;
    onCancelfunction = onCancelfunction || defaultfun;
    if ($("#my-confirm").length > 0) {
        $("#my-confirm").remove();
    }
    var html = '<div class="am-modal am-modal-confirm" tabindex="-1" id="my-confirm">\
                            <div class="am-modal-dialog">\
                              <div class="am-modal-hd">' + title + '</div>\
                              <div class="am-modal-bd">\
                                ' + msg + '\
                              </div>\<div class="am-modal-bd">\
                                ' + value + '\
                              </div>\
                              <div class="am-modal-footer">\
                                <span class="am-modal-btn" data-am-modal-confirm>确定</span>\
                                <span class="am-modal-btn" data-am-modal-cancel>取消</span>\
                              </div>\
                            </div>\
                          </div>';
    $("body").append(html);
    $('#my-confirm').modal({
        relatedTarget: this,
        onConfirm: function () {
            onConfirmfunction();
        },
        // closeOnConfirm: false,
        onCancel: function () {
            onCancelfunction();
        }
    });
}