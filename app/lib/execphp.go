package lib

import (
	"bytes"
	"fmt"
	"os/exec"
	//"strings"
	"time"
	//"strings"
	"bufio"
	"io"
	"sync"
	"syscall"
)

var done chan error = make(chan error)
var phpExe string

func ExecPhp(phpExe string, params []string) string {
	//doCliData=append(doCliData,class+method);
	//params:= append([]string{cliFile}, arg...)
	//paramsString:=strings.Join(arg, " ")
	//fmt.Println("phpExe: %s",phpExe)
	//fmt.Println("params: %s",params)
	//fmt.Println("paramsString: %s",paramsString)
	cmd := exec.Command(phpExe, params...)
	cmd.SysProcAttr = &syscall.SysProcAttr{ //阻止捕获到信号
		Setpgid: true,
	}
	var output bytes.Buffer
	cmd.Stdout = &output
	var stderr bytes.Buffer
	cmd.Stderr = &stderr
	err := cmd.Start() //命令开始
	if err != nil {
		fmt.Println("error: %s", err)
	}
	go func() {
		done <- cmd.Wait() //等待完成
	}()
	select {
	case <-done:
		stderrStr := stderr.String()
		if stderrStr != "" {
			LogWrite(stderrStr, params[1]+"-error")
			return ""
		}
		res := string(output.Bytes())
		//fmt.Println("res: %s",res)
		return res
	case <-time.After(time.Second * 55): //超55秒
		//if err := cmd.Process.Kill(); err != nil {
		//fmt.Println("failed to kill: %s, error: %s", cmd.Path, err)
		//}
		go func() {
			<-done
		}()
		fmt.Println("time_out")
		return ""
	}
}
func ExecCommand(commandName string, params []string) bool {
	cmd := exec.Command(commandName, params...)

	//显示运行的命令
	fmt.Println(cmd.Args)

	stdout, err := cmd.StdoutPipe()

	if err != nil {
		fmt.Println(err)
		return false
	}

	cmd.Start()

	reader := bufio.NewReader(stdout)

	//实时循环读取输出流中的一行内容
	for {
		line, err2 := reader.ReadString('\n')
		if err2 != nil || io.EOF == err2 {
			break
		}
		fmt.Println(line)
	}

	cmd.Wait()
	return true
}
func GoExecPhp(phpExe string, params []string, wg *sync.WaitGroup, goruntineNum *int64) string {
	//doCliData=append(doCliData,class+method);
	//params:= append([]string{cliFile}, arg...)
	//paramsString:=strings.Join(arg, " ")
	//fmt.Println("phpExe: %s",phpExe)
	//fmt.Println("params: %s",params)
	//fmt.Println("paramsString: %s",paramsString)
	defer func() {
		fmt.Println("done-----")
		wg.Done()
		*goruntineNum--
	}()
	cmd := exec.Command(phpExe, params...)
	cmd.SysProcAttr = &syscall.SysProcAttr{ //阻止捕获到信号
		Setpgid: true,
	}
	var output bytes.Buffer
	cmd.Stdout = &output
	var stderr bytes.Buffer
	cmd.Stderr = &stderr
	err := cmd.Start() //命令开始
	if err != nil {
		fmt.Println("error: %s", err)
	}
	go func() {
		done <- cmd.Wait() //等待完成
	}()
	select {
	case <-done:
		stderrStr := stderr.String()
		if stderrStr != "" {
			LogWrite(stderrStr, params[1]+"-error")
			return ""
		}
		res := string(output.Bytes())
		//fmt.Println("res: %s",res)
		return res
	case <-time.After(time.Second * 55): //超55秒
		//if err := cmd.Process.Kill(); err != nil {
		//fmt.Println("failed to kill: %s, error: %s", cmd.Path, err)
		//}
		go func() {
			<-done
		}()
		fmt.Println("time_out")
		return ""
	}
}
func GoExecPhpTest(wg *sync.WaitGroup, goruntineNum *int64) string {
	defer func() {
		fmt.Println("done-----")
		wg.Done()
		*goruntineNum--
	}()
	time.Sleep(time.Second * 10)
	LogWrite("1", "test")
	return ""
}
