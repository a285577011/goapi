package main

import (
	"app/conf"
	"app/lib"
	"fmt"
	"os"
	"os/signal"
	"syscall"
)

const DEBUG string = "1"

func init() {
	lib.InitConfig(DEBUG)
	fmt.Println("cronStart")

}

func main() {
	sigs := make(chan os.Signal, 1)                      //信号
	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM) //接受的信号 SIGINT ctrl+c SIGTERM kill发送的信号
	wg, cron := conf.InitCron()
	select {
	case <-sigs:
		cron.Stop() //定时任务退出，防止死循环
		wg.Wait()
		fmt.Println("exitings")
	}

}
