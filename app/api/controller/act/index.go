package act

import (
	"app/api/controller"
)

type Index struct {
	controller.BaseController
}

func (this *Index) IndexAction() {
	this.PrintErrorMessage(1000, "我错了")
}
