package act

import (
	"app/api/controller"
	"app/lib/db"
	"app/logic"
	"app/models"
	//"fmt"
	"strconv"
)

type Duobao struct {
	controller.BaseController
}

func (this *Duobao) IndexAction() {
	duobaoModel := models.GetActModel("duobao_goods")
	periodsModel := models.GetActModel("duobao_goods_periods")
	where := map[string]interface{}{
		"status": "1",
	}
	data := duobaoModel.FetchAll(db.Select{Where: where}) //->where(['is_show'=>1])->order('sort ASC')->select();
	if len(data) > 0 {
		logic := &logic.DuobaoLogic{}
		for key, value := range data {
			whereP := map[string]interface{}{
				"status":          "0",
				"duobao_goods_id": value["id"],
			}
			periodsData := periodsModel.FetchRow(db.Select{Where: whereP})
			data[key]["periods"] = periodsData["periods"]
			data[key]["joinNum"] = logic.GetDuobaoNumStore(value["id"], periodsData["id"])
			joinNum, _ := strconv.ParseFloat(data[key]["joinNum"], 64)
			need_number, _ := strconv.ParseFloat(value["need_number"], 64)
			//fmt.Println(1 + 2)
			//strconv.FormatFloat(input_num, 'f', 6, 64)
			data[key]["remainNum"] = strconv.FormatFloat(need_number-joinNum, 'f', -1, 64)         //剩余人数
			data[key]["completePer"] = strconv.FormatFloat((joinNum/need_number)*100, 'f', -1, 64) //夺宝完成度 百分比
			//data[key]["link"]=U('detail',['duobaoId'=>$v['id'],'periodsId'=>$duobaoPeriods['id']]);
		}
	}
	this.PrintSuccessMessage(data)
}
