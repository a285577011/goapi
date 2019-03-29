<script type="text/javascript" src="/js/jquery.min.js"></script>
<?php

use yii\helpers\Html;

echo Html::label("url:");
echo Html::input("text", "url", "http://", ["id" => "url", "class" => "form-control"]) . "<br >";
echo Html::label("type:");
echo Html::dropDownList("type", "POST", ["POST" => "POST", "GET" => "GET"], ["id" => "type", "class" => "form-control"]) . "<br >";
echo Html::label("data:");
echo Html::textarea("data", "{}", ["id" => "data", "class" => "form-control", "style" => "height:200px;"]) . "<br >";
echo Html::button("提交", ["id" => "tj", "class" => "form-control btn btn-primary"]) . "<br >";
echo Html::label("结果:");
echo Html::textarea("result", "", ["id" => "result", "class" => "form-control", "style" => "height:400px;"]);
?>
<script type="text/javascript">
    $(function () {
        $("#url").val(getCookie('url'));
        $("#type").val(getCookie('type'));
        $("#data").val(getCookie('data'));
        $("#tj").click(function () {
            var url = $("#url").val();
            var type = $("#type").val();
            var data = $("#data").val();
            setCookie('url', url);
            setCookie('type', type);
            setCookie('data', data);
            data = eval("(" + data + ")");
            $.ajax({
                url: url,
                type: type,
                data: data,
                dataType: "text",
                async: false,
                success: function (result) {
//                    console.log(eval("(" + result + ")"));
                    $("#result").val(result);
                }
            });
        });
        function setCookie(c_name, value, expiredays)
        {
            var exdate = new Date()
            exdate.setDate(exdate.getDate() + expiredays)
            document.cookie = c_name + "=" + escape(value) + ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString());
        }
        function getCookie(c_name)
        {
            if (document.cookie.length > 0)
            {
                var c_start = document.cookie.indexOf(c_name + "=")
                if (c_start != -1)
                {
                    c_start = c_start + c_name.length + 1
                    var c_end = document.cookie.indexOf(";", c_start)
                    if (c_end == -1)
                        c_end = document.cookie.length
                    return unescape(document.cookie.substring(c_start, c_end))
                }
            }
            return "";
        }
    });
</script>
