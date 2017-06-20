package logic

import (
	"github.com/robfig/cron"
	//"gocron/lib"
	//"fmt"
	"sync"
	//"time"
	//"gocron/models"
	//"gocron/lib/db"
)
func InitCron() (*sync.WaitGroup,*cron.Cron){
	cron := cron.New()
	wg := &sync.WaitGroup{}//锁 防任务未完成退出
	/*phpExe, err := exec.LookPath("php")
	if err != nil {
		fmt.Println(err)
	}*/
	cron.AddFunc("* * * * * *", func() {
		wg.Add(1)
		sendCode();
		wg.Done()
	})
	
	//fmt.printf(cron.Entries())
	cron.Start()//定时开始
	return wg,cron;
	//select{}
	//defer cron.Stop()
}

