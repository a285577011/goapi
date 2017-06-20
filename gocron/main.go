package main

import (
	"gocron/lib"
	"fmt"
	//"gocron/models"
	//"gocron/lib/db"
	//"reflect"
	"gocron/logic"
	"os/signal"
	//"log"
	"syscall"
	"os"
)
const DEBUG string = "1"
func init() {
	lib.InitConfig(DEBUG);
	fmt.Println("cronStart")

}

func main() {
		//BaseModel:=models.Base{Table:"duobao"}
	//res:=BaseModel.FetchRow(db.Select{});
	sigs := make(chan os.Signal, 1) //信号
	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM) //接受的信号
	wg,cron:=logic.InitCron()
	select{
	case <-sigs:
		cron.Stop();//定时任务退出，防止死循环
		wg.Wait();
		fmt.Println("exitings") 
	}
	
}