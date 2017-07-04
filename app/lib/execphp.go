package lib

import (
	"fmt"
	"os/exec"
	//"time"
	//"syscall"
	//"bytes"
)

var done chan error = make(chan error)
var phpExe string

func ExecPhp(phpscript string) string {
	phpExe, err := exec.LookPath("php")
	if err != nil {
		fmt.Println(err)
	}
	//var output bytes.Buffer
	cmd := exec.Command(phpExe, phpscript)
	out, err := cmd.Output()
	if err != nil {
		fmt.Println(err)
	}
	return string(out)
	/*cmd.Stdout = &output
	cmd.Start() //命令开始
	//cmd.SysProcAttr = &syscall.SysProcAttr{Setpgid: true}
	go func() {
			done <- cmd.Wait() //等待完成
	}()
		select {
		case <-done:
			return string(output.Bytes())
		case <-time.After(timeout): //超时1小时
			fmt.Println(timeout)
			if err := cmd.Process.Kill(); err != nil {
				fmt.Println("failed to kill: %s, error: %s", cmd.Path, err)
			}
			go func() {
				<-done
			}()
			return ""
		}*/

}
