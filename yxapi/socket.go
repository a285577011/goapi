package main

import (
	"log"
	"net/http"
	"os"
	//"app/src/github.com/golang/tools/playground/socket"
	//"encoding/json"
	//"fmt"
	"github.com/googollee/go-socket.io"
	_ "net/http/pprof"
	//"time"
	"yxapi/chat"
)

var Server *socketio.Server

func servePush(w http.ResponseWriter, r *http.Request) {
	if r.Method != "GET" {
		http.Error(w, "Method not allowed", 405)
		return
	}
	//t := time.Now().Unix()
	var so socketio.Socket
	so = chat.SoconMap["sys"]
	so.Emit("chatMessage", "sys: test")
	//h.broadcast <- &tmessage{content: []byte("test"), fromuser: []byte("system"), touser: []byte("all"), mtype: 1, createtime: time.Unix(t, 0).String()}
}
func main() {
	go func() {
		log.Println(http.ListenAndServe("localhost:10000", nil))
	}()
	port := os.Getenv("PORT")
	if port == "" {
		port = ":85"
	} else {
		port = ":" + port
	}

	http.Handle("/socket.io/", chat.Server)
	http.Handle("/", http.FileServer(http.Dir("./chat/asset")))
	http.HandleFunc("/push", servePush)
	log.Println("Serving at localhost:85...")
	log.Fatal(http.ListenAndServe(port, nil))
}
