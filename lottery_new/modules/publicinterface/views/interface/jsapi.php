<html>
    <head>
        <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/> 
        <title>微信支付样例-支付</title>

        <script>
            (function (doc, win) {
                var docEl = doc.documentElement,
                        resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
                        recalc = function () {
                            var clientWidth = docEl.clientWidth;
                            if (!clientWidth)
                                return;
                            if (innerWidth > 640) {
                                docEl.style.fontSize = '32px';
                            } else {
                                docEl.style.fontSize = 16 * (clientWidth / 320) + 'px';
                            }
                        };
                if (!doc.addEventListener)
                    return;
                win.addEventListener(resizeEvt, recalc, false);
                doc.addEventListener('DOMContentLoaded', recalc, false);
            })(document, window);
        </script>

        <script type="text/javascript">
            //调用微信JS api 支付
            function jsApiCall()
            {
                WeixinJSBridge.invoke(
                        'getBrandWCPayRequest',
<?php echo $data['jsApiParameters']; ?>,
                        function (res) {
                            WeixinJSBridge.log(res.err_msg);
                            if (res.err_msg == "get_brand_wcpay_request:ok") {
                                window.location.href = '/pay/success/<?php echo $data["order_code"]; ?>?total_amount=<?php echo $data["total_money"];
echo (isset($recharge) ? "&recharge=1" : ""); ?>';
                            } else {
//                                alert("支付失败");
                            }
//                            alert(res.err_code + res.err_desc + res.err_msg);
                        }
                );
            }

            function callpay()
            {
                if (typeof WeixinJSBridge == "undefined") {
                    if (document.addEventListener) {
                        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                    } else if (document.attachEvent) {
                        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                    }
                } else {
                    jsApiCall();
                }
            }
        </script>
        <script type="text/javascript">
            //获取共享地址
            function editAddress()
            {
                WeixinJSBridge.invoke(
                        'editAddress',
<?php echo $data['editAddress']; ?>,
                        function (res) {
                            var value1 = res.proviceFirstStageName;
                            var value2 = res.addressCitySecondStageName;
                            var value3 = res.addressCountiesThirdStageName;
                            var value4 = res.addressDetailInfo;
                            var tel = res.telNumber;

//                            alert(value1 + value2 + value3 + value4 + ":" + tel);
                        }
                );
            }

            window.onload = function () {
                if (typeof WeixinJSBridge == "undefined") {
                    if (document.addEventListener) {
                        document.addEventListener('WeixinJSBridgeReady', editAddress, false);
                    } else if (document.attachEvent) {
                        document.attachEvent('WeixinJSBridgeReady', editAddress);
                        document.attachEvent('onWeixinJSBridgeReady', editAddress);
                    }
                } else {
                    editAddress();
                }
            };

        </script>
        <style>
            * {
                margin: 0;
                padding: 0;
            }
            html,
            body {
                height: 100%;
                font-size: 0.83rem;
                color: #444;
            }
            .container {
                width: 100%;
                height: 100%;
                background-color: #eee;
            }
            header {
                width: 100%;
                height: 2.5rem;
                background-color: #dc3b40;
                font-size: 1rem;
                text-align: center;
                color: #fff;
                line-height: 2.5rem;
            }
            .pay_money {
                width: 100%;
                height: 2.33rem;
                margin-top: 0.83rem;
                padding-left: 1rem;
                line-height: 2.33rem;
                font-size: 0.83rem;
                background-color: #fff;
                display: -webkit-box;
                display: -ms-flexbox;
                display: flex;
            }
            .pay_money span {
                color: #dc3b40;
                margin-left: 0.2rem;
            }
            .title {
                margin-top: 1rem;
                height: 1.6rem;
                widht: 100%;
                line-height: 1.6rem;
                color: #999;
                padding-left: 1rem;
            }
            .wechat_pay {
                width: 100%;
                height: 2.4rem;
                position: relative;
                background-color: #fff;
                display: -webkit-box;
                display: -ms-flexbox;
                display: flex;
                -webkit-box-align: center;
                -ms-flex-align: center;
                align-items: center;
                padding-left: 1rem;
            }
            .wechat_pay span {
                display: block;
                width: 1.33rem;
                height: 1.33rem;
                margin-right: 1rem;
                background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyFpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDoxMzYyREMwNzM1MjkxMUU3QjdEMEZCMkFFNDAyQjlFRCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDoxMzYyREMwODM1MjkxMUU3QjdEMEZCMkFFNDAyQjlFRCI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjEzNjJEQzA1MzUyOTExRTdCN0QwRkIyQUU0MDJCOUVEIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjEzNjJEQzA2MzUyOTExRTdCN0QwRkIyQUU0MDJCOUVEIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+9b/GMQAAA+ZJREFUeNrcmVlIVVEUho+mZRNFA6mVlGlkheEUVGZpRFKYBQURvTQZDVIIPliU1ENBAz00QjZhAxGBVFDgQ/RihmClURYNFFhaZJGRmaH9q/4Lh9vZ9wz3XPW04OOe697n3vV79l57rXXDMivSNdpAsA+sBQO03mnfQBkoAT/kD+EciAdPQWEvdl5sENgGnoA4n4AoUA3Gat6x8eAeiBQBR8FIzXsWCw6Hc8171TaHax43zwuIcHjfK26iBvAOfOHfB4NokAgyQHJvE3AWnAd3Lc4XAcu5z2J6cgldBpPAGhvOi9WBnSABbAddPSFgNVgJngXxPd95yieBB90l4BeYDs65+H3yT0gFt7pDwBxQE6K9txDcD6WAYlClGFsFdoPJJp+9gPMWKsZzQFsoopCEyIOK+cfAJl7v4HH+wWDeBnBS936uweaXfVEAyt1+AqUB5q/QXfdhzDeyRX7vUxTzLoCXbgpoB1dMBLzn9TWmtaolWM/reoZhlZW4uYTkdO0IML+Sqawsndcm0SaZc/XzxoGlYCpP7Xbm+K4JqLNwT7uJ83rzzVvMg0wENIPnTEOaQAs4waIqlfsl0amAVpfD5QhwE0wBl7i5H1m4b76kyiDf7h7o66LzM8BHhsp4G877luoSPrm3dgRMcMn5FJ4je0A2hTixG3x6VVYFZLngfD9QS+dLXepEzAIPrQiQNTs7yC+sYDBQOR9mcr9qPNsoQhodZHuDcF7S5lzFk+zLWuItT3r/ABIJznD8kMG4FE1F/6jNrEg3ytFl9193IEAcHEUR/pbI8OmzMaDR74x4HWDcZ5/BULNk7hoPK7uWBi4qxt6wopPX/TwP9CbOlvEJHDAY1/tmWlJG8FTOUCRrqs0r67daMf6TFV2YojKT9b0+wLjPavStoED1QBzjdrpFATF0otlkXleQ4812SspoKp5pQUAHN2pkiBsRUU6KeivLqJFLLyHEApLstlXugBe6GF3EiFHDhEwixyfQyUMnx61yUWHL7Aoo1L1uNUg32phRdvIgzGUHIhSW5V/Kmglo4U3lAaqq/mC035dMs5G42W2saXYESNFx3GE6Md5l508xq7XV2HIaUWSP3HbReUkM19ntCwVrCxgAooP4jGHgiPa3Pal1twBfO0Ui1UaH988DW5w0tty0IdxHDexApDHtMDJpIOfpCv1Ksw9XZaOhtiZmpvIqPdjhYKJu48tvDrvAaSZ+xVaL+u6yaJO9EcvMNJ9iulSFTk8JsGp5drsSnrP/QsBVD/tfLgIKNBf69D1g0kX880O3VPuZ4KuHnG9hkdXq2wO1LBQue8D5C0ypH8ub3wIMAHX0xUL2K+mwAAAAAElFTkSuQmCC");
                background-size: cover;
            }
            .wechat_pay i {
                position: absolute;
                top: 50%;
                margin-top: -0.4rem;
                right: 2rem;
                display: block;
                width: 0.8rem;
                height: 0.8rem;
                background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFIAAABSCAYAAADHLIObAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyFpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo5QkMxRDRDNTU2RjUxMUU3OTFEOUJCMzkxNTMwMzVGQSIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo5QkMxRDRDNjU2RjUxMUU3OTFEOUJCMzkxNTMwMzVGQSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjlCQzFENEMzNTZGNTExRTc5MUQ5QkIzOTE1MzAzNUZBIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjlCQzFENEM0NTZGNTExRTc5MUQ5QkIzOTE1MzAzNUZBIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+8NA+HgAAB2NJREFUeNrsXQtsFEUY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");
                background-size: cover;
            }
            button {
                display: block;
                width: 18rem;
                height: 2.33rem;
                background-color: #dc3b40;
                font-size: 0.89rem;
                color: #fff;
                border: 0;
                margin: 1.5rem auto;
                border-radius: 0.3rem;
            }

        </style>
    </head>
    <body>
        <div class="container">
            <header>在线支付</header> 
            <div class="pay_money">
                <p>需支付金额：</p>
                <span><?php echo $data['total_money']; ?>元</span>
            </div>
            <p class="title">选择支付方式</p>
            <div class="wechat_pay">
                <span></span>
                <p>微信支付</p>
                <i></i>
            </div>
            <button onclick="callpay()">立 即 支 付</button>
        </div>
    </body>
</html>

