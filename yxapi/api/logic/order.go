package logic

import (
	"fmt"
	"sync"
)

type OrderLogic struct {
}

func (this *OrderLogic) CancleOrder(data map[string]string, wg *sync.WaitGroup) {
	defer func() {
		wg.Done()
	}()
	fmt.Print(data)

}
