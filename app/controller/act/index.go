package act

import (
	"app/controller"
	)
type Index struct {
	controller.BaseController
}
func (this *Index) IndexAction() {
	error:=map[string]string{
		"Errno":"1",
		"Errmsg":"出错啦",

	}
	this.PrintSuccessMessage(error)
}
