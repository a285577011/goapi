<?php

namespace app\modules\storeback\controllers;

use Yii;
use yii\web\Controller;
use yii\data\ArrayDataProvider;

/**
 * Default controller for the `storeback` module
 */
class PiaowuController extends Controller {

    /**
     * Renders the index view for the module
     * @return string
     */
    public $layout = 'main';

    public function actionIndex() {
        return $this->render('index');
    }

    public function actionPlayAwards() {
        return $this->render('playawards');
    }

    public function actionAwardsRecords() {
        return $this->render('awardsrecords');
    }

    public function actionProblemList() {
        $time = date("Y-m-d H:i:s", strtotime("-10 minutes"));
        $statusNames = [
            "1" => "未跑线程",
            "2" => "线程失败",
            "4" => "线程异常",
        ];
        $data = (new \yii\db\Query())->select("*")->from("queue")->where(["in", "status", [1, 2, 4]])->andWhere(["<", "create_time", $time])->orderBy("create_time desc")->all();
        foreach ($data as &$val) {
            $val["args"] = json_decode($val["args"], true);
            $val["statusName"] = $statusNames[$val["status"]];
            $val["suborder_status"] = "1";
            if ($val["job"] == "lottery_job") {
                $jobL = (new \yii\db\Query())->select("suborder_status")->from("lottery_order")->where(["lottery_order_id" => $val["args"]["orderId"]])->one();
                $val["suborder_status"] = $jobL["suborder_status"];
            }
        }

        $data = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => 100,
            ]
        ]);
        return $this->render('problemlist', ['data' => $data]);
    }

}
