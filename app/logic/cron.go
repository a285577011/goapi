package logic

import (
	"app/lib"
	"fmt"
	"github.com/robfig/cron"
	"sync"
	//"time"
	//"app/models"
	//"app/lib/db"
	"os/exec"
)

func InitCron() (*sync.WaitGroup, *cron.Cron) {
	cron := cron.New()
	wg := &sync.WaitGroup{} //锁 防任务未完成退出
	phpExe := lib.GetConfig("phpcli")["phpExe.name"].String()
	//var doCliData []string
	cronList := lib.GetConfig("phpcli")["cronList.list"]
	length := cronList.Len()
	for i := 0; i < length; i++ {
		iv := cronList.Index(i)
		cron.AddFunc(iv.Index(2).String(), func() {
			ExecPhp(wg, phpExe, iv.Index(0).String(), iv.Index(1).String())
		})
	}
	cron.AddFunc("*/2 * * * * *", func() {
		sendDuobaoCode()
	})
	//fmt.Println(doCliData)
	//if len(cronList)>0{
	//}
	//var phpCron []string
	//phpCron = make([]string, cronList["list"].Len())
	//fmt.Println(len(cronList))
	//@yearly (or @annually) | Run once a year, midnight, Jan. 1st        | 0 0 0 1 1 *
	//@monthly               | Run once a month, midnight, first of month | 0 0 0 1 * *
	//@daily (or @midnight)  | Run once a day, midnight                   | 0 0 0 * * *
	//@hourly                | Run once an hour, beginning of hour        | 0 0 * * * *
	cron.Start() //定时开始
	return wg, cron
}
func ExecPhp(wg *sync.WaitGroup, phpExe string, class string, method string) {
	wg.Add(1)
	fmt.Println("error")
	//doCliData=append(doCliData,class+method);
	cmd := exec.Command(phpExe, lib.GetConfig("phpcli")["cli.file"].String(), class, method)
	err := cmd.Start() //开始执行
	if err != nil {
		fmt.Println("Error: %s\n", err)
	}
	cmd.Wait() //wait下边的函数等待执行完成 不等待则命令退出
	wg.Done()

}
