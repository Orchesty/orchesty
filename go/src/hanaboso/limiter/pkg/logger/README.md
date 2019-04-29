Logger
======

Using
------
```go
package main

import "logger"

func main()  { 
	logger.GetLogger().AddHandler(NewHandler())
	
	logger.GetLogger().Info("My message", nil)
	logger.GetLogger().Warning("My message", nil)
	logger.GetLogger().Error("My message", logger.Context{"error": err.Error()})
}
```
