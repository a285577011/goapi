package chat

import (
	"encoding/json"
	"fmt"
	"log"

	"github.com/googollee/go-socket.io"
)

type post struct {
	Name string
	Text string
}

var SoconMap map[string]socketio.Socket

var Server *socketio.Server

func init() {
	var err error
	SoconMap = make(map[string]socketio.Socket)
	Server, err = socketio.NewServer(nil)
	if err != nil {
		log.Fatal(err)
	}

	var users = make(map[string]string)

	Server.On("connection", func(so socketio.Socket) {
		log.Println("on connection")
		//SoconMap := make(map[string]socketio.Socket)
		SoconMap["sys"] = so
		joinErr := so.Join("chat")
		if joinErr != nil {
			log.Fatal(joinErr)
		}

		so.On("newUser", func(username string) {
			log.Println(username)

			outputMsg := fmt.Sprintf("%v [ENTROU]", username)

			id := so.Id()

			users[id] = username

			so.BroadcastTo("chat", "newUser", outputMsg)
		})

		so.On("chatMessage", func(msg string) {
			jsonBytes := []byte(msg)

			var posts post
			json.Unmarshal(jsonBytes, &posts)

			outputMsg := fmt.Sprintf("%v: %v", posts.Name, posts.Text)
			fmt.Println(outputMsg)
			//log.Println("emit:", so.Emit("chatMessage", outputMsg))

			so.BroadcastTo("chat", "chatMessage", outputMsg) //广播
			so.Emit("chatMessage", outputMsg)                //对自己
		})

		so.On("disconnection", func() {
			fmt.Println("leavel")
			id := so.Id()

			username := users[id]

			outputMsg := fmt.Sprintf("%v [SAIU]", username)

			so.BroadcastTo("chat", "newUser", outputMsg)
		})
	})

	Server.On("error", func(so socketio.Socket, err error) {
		log.Println("error:", err)
	})
}
