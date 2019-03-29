<?php

use yii\helpers\Html;
use yii\grid\GridView;

echo GridView::widget([
    "dataProvider" => $data,
    "columns" => [
        [
            'label' => '线程队列ID',
            'value' => 'queue_id'
        ],[
            'label' => '任务名称',
            'value' => function($model) {
                $queueNames = [
                    "lottery_job" => "生成子单",
                    "programme_job" => "合买出单",
                    "custom_made_job" => "定制跟单"
                ];
                return isset($queueNames[$model["job"]]) ? $queueNames[$model["job"]] : "{$model["job"]}";
            }
        ],[
            'label' => '任务参数',
            'value' => function($model) {
                return json_encode($model["args"], true);
            }
        ],[
            'label' => '推送状态',
            'value' => function($model) {
                $pushStatus = [
                    "" => "全部",
                    "1" => "未推送",
                    "2" => "已推送"
                ];
                return $pushStatus[$model['push_status']];
            }
        ],[
            'label' => '线程状态',
            'value' => "statusName"
        ],[
            'label' => '创建时间',
            'value' => 'create_time'
        ],[
            'label' => '操作',
            'format' => 'raw',
            'value' => function($model) {
                return '<div class="am-btn-group am-btn-group-xs">
            ' . ($model["job"] == "lottery_job" && $model["suborder_status"] == "0" ? ('<span class="handle pointer" onclick="subOrder(' . $model["args"]["orderId"] . ')">生成子单</span>') : "") . '
            <span class="handle pointer" onclick="reQueue(' . $model["queue_id"] . ')">重跑</span>
        </div>';
            }
        ]
       ]
      ]);
?>
                <script type="text/javascript">
                    $(function () {
                        $("body").css("margin", "0");
                    });
                    function reQueue(queueId) {
                        if (confirm("确定重跑?")) {
                            $.ajax({
                                url: "/api/cron/cron/re-queue",
                                data: {queueId: queueId},
                                type: "GET",
                                dataType: "json",
                                async: false,
                                success: function (json) {
                                    if (json["code"] == 600) {
                                        alert(json["msg"]);
                                        location.reload();
                                    } else {
                                        alert(json["msg"]);
                                    }
                                }
                            });
                        }

                    }
                    function subOrder(orderId) {
                        if (confirm("确定生成子单?")) {
                            $.ajax({
                                url: "/api/cron/cron/sub-order",
                data: {order_id: orderId},
                type: "GET",
                dataType: "json",
                async: false,
                success: function (json) {
                    if (json["code"] == 600) {
                        alert(json["msg"]);
                        location.reload();
                    } else {
                        alert(json["msg"]);
                    }
                }
            });
        }
    }
</script>
