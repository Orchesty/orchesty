package service

import (
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"strings"
	"sync"
	"time"

	log "github.com/hanaboso/go-log/pkg"

	"github.com/gorilla/websocket"
)

const (
	writeWait      = 10 * time.Second
	pongWait       = 60 * time.Second
	pingPeriod     = (pongWait * 9) / 10
	maxMessageSize = 4096
)

type (
	TraceService interface {
		HandleConnection(writer http.ResponseWriter, request *http.Request, authHeader string)
	}

	traceService struct {
		upgrader        websocket.Upgrader
		manifestService ManifestService
		aiService       AIService
		logger          log.Logger
	}

	session struct {
		conn              *websocket.Conn
		token             string
		userID            string
		workerSDK         string
		workerProviderURL string
		mu                sync.RWMutex
	}
)

func NewTraceService(manifestService ManifestService, aiService AIService, logger log.Logger) TraceService {
	return traceService{
		upgrader: websocket.Upgrader{
			CheckOrigin: func(_ *http.Request) bool {
				return true
			},
			ReadBufferSize:  1024,
			WriteBufferSize: 1024,
		},
		manifestService: manifestService,
		aiService:       aiService,
		logger:          logger,
	}
}

func (svc traceService) HandleConnection(writer http.ResponseWriter, request *http.Request, authHeader string) {
	conn, err := svc.upgrader.Upgrade(writer, request, nil)
	if err != nil {
		svc.logContext().Error(err)

		return
	}

	token := strings.TrimPrefix(authHeader, "Bearer ")
	sess := &session{conn: conn, token: token}

	svc.logContext().Info("WebSocket connection established")

	var wg sync.WaitGroup
	done := make(chan struct{})

	wg.Add(1)
	go svc.writePump(sess, done, &wg)

	wg.Add(1)
	go svc.readPump(sess, done, &wg)

	wg.Wait()
	svc.logContext().Info("WebSocket connection closed")
}

func (svc traceService) readPump(sess *session, done chan struct{}, wg *sync.WaitGroup) {
	defer wg.Done()
	defer close(done)

	sess.conn.SetReadLimit(maxMessageSize)
	_ = sess.conn.SetReadDeadline(time.Now().Add(pongWait))
	sess.conn.SetPongHandler(func(string) error {
		_ = sess.conn.SetReadDeadline(time.Now().Add(pongWait))

		return nil
	})

	for {
		_, raw, err := sess.conn.ReadMessage()
		if err != nil {
			if websocket.IsUnexpectedCloseError(err, websocket.CloseGoingAway, websocket.CloseNormalClosure) {
				svc.logContext().Error(err)
			}

			return
		}

		var msg Message
		if err := json.Unmarshal(raw, &msg); err != nil {
			svc.sendError(sess, 400, "invalid JSON")

			continue
		}

		switch msg.Type {
		case TypeToken:
			svc.handleToken(sess, msg.Data)
		case TypeProvider:
			svc.handleProvider(sess, msg.Data)
		case TypeRequest:
			svc.handleRequest(sess, msg.Data)
		default:
			svc.sendError(sess, 400, fmt.Sprintf("unknown message type: %s", msg.Type))
		}
	}
}

func (svc traceService) handleToken(sess *session, data json.RawMessage) {
	var td TokenData
	if err := json.Unmarshal(data, &td); err != nil || td.Token == "" {
		svc.sendError(sess, 400, "invalid token data")

		return
	}

	sess.mu.Lock()
	sess.token = td.Token
	sess.mu.Unlock()

	svc.logContext().Info("Token updated")
}

func (svc traceService) handleProvider(sess *session, data json.RawMessage) {
	var pd ProviderData
	if err := json.Unmarshal(data, &pd); err != nil || pd.Worker == "" || pd.WorkerProviderURL == "" || pd.User == "" {
		svc.sendError(sess, 400, "invalid provider data: worker, workerProviderUrl and user are required")

		return
	}

	sdks, err := svc.manifestService.FetchSDKs(sess.token)
	if err != nil {
		svc.sendError(sess, 502, fmt.Sprintf("Failed to fetch SDKs: %s", err.Error()))

		return
	}

	var matched bool
	var sdkURL string
	for _, sdk := range sdks {
		if sdk.Name == pd.Worker {
			sdkURL = "http://" + sdk.URL
			matched = true

			break
		}
	}

	if !matched {
		svc.sendError(sess, 400, fmt.Sprintf("unknown worker SDK: %s", pd.Worker))

		return
	}

	parsed, err := url.Parse(pd.WorkerProviderURL)
	if err != nil || parsed.Path == "" {
		svc.sendError(sess, 400, "invalid workerProviderUrl")

		return
	}

	constructedURL := sdkURL + parsed.Path

	sess.mu.Lock()
	sess.workerSDK = pd.Worker
	sess.workerProviderURL = constructedURL
	sess.userID = pd.User
	sess.mu.Unlock()

	svc.logContext().Info("Provider set: sdk=%s url=%s", pd.Worker, constructedURL)
}

func (svc traceService) handleRequest(sess *session, data json.RawMessage) {
	var rd RequestData
	if err := json.Unmarshal(data, &rd); err != nil || rd.Content == "" {
		svc.sendError(sess, 400, "invalid request data")

		return
	}

	sess.mu.RLock()
	token := sess.token
	userID := sess.userID
	workerSDK := sess.workerSDK
	workerProviderURL := sess.workerProviderURL
	sess.mu.RUnlock()

	if workerProviderURL == "" {
		svc.sendError(sess, 400, "provider not set: send a provider message first")

		return
	}

	actions, err := svc.manifestService.FetchManifest(token)
	if err != nil {
		svc.sendError(sess, 502, fmt.Sprintf("failed to fetch manifest: %s", err.Error()))

		return
	}

	prompt := BuildPrompt(rd.Content, actions)

	aiResponse, err := svc.aiService.SendPrompt(workerProviderURL, userID, workerSDK, prompt)
	if err != nil {
		svc.sendError(sess, 502, fmt.Sprintf("AI request failed: %s", err.Error()))

		return
	}

	var mcpAction struct {
		Audit string                 `json:"audit"`
		Data  map[string]interface{} `json:"data"`
	}

	if err := json.Unmarshal([]byte(aiResponse), &mcpAction); err != nil || mcpAction.Audit == "" {
		svc.sendMessage(sess, TypeResponse, ResponseData{Content: aiResponse})

		return
	}

	mcpResult, err := svc.manifestService.RunAction(token, []byte(aiResponse))
	if err != nil {
		svc.sendError(sess, 502, fmt.Sprintf("MCP run failed: %s", err.Error()))

		return
	}

	var logs []string
	if err := json.Unmarshal(mcpResult, &logs); err == nil {
		mcpResult = []byte(strings.Join(logs, "\n"))
	}

	svc.sendMessage(sess, TypeResponse, ResponseData{Content: string(mcpResult)})
}

func (svc traceService) sendMessage(sess *session, msgType string, data interface{}) {
	rawData, err := json.Marshal(data)
	if err != nil {
		svc.logContext().Error(err)

		return
	}

	msg := Message{Type: msgType, Data: rawData}
	payload, err := json.Marshal(msg)
	if err != nil {
		svc.logContext().Error(err)

		return
	}

	sess.mu.Lock()
	defer sess.mu.Unlock()

	_ = sess.conn.SetWriteDeadline(time.Now().Add(writeWait))
	if err := sess.conn.WriteMessage(websocket.TextMessage, payload); err != nil {
		svc.logContext().Error(err)
	}
}

func (svc traceService) sendError(sess *session, code int, message string) {
	svc.sendMessage(sess, TypeError, ErrorData{Code: code, Message: message})
}

func (svc traceService) writePump(sess *session, done chan struct{}, wg *sync.WaitGroup) {
	ticker := time.NewTicker(pingPeriod)

	defer wg.Done()
	defer ticker.Stop()
	defer sess.conn.Close()

	for {
		select {
		case <-done:
			_ = sess.conn.WriteMessage(websocket.CloseMessage, websocket.FormatCloseMessage(websocket.CloseNormalClosure, ""))

			return
		case <-ticker.C:
			sess.mu.Lock()
			_ = sess.conn.SetWriteDeadline(time.Now().Add(writeWait))
			err := sess.conn.WriteMessage(websocket.PingMessage, nil)
			sess.mu.Unlock()

			if err != nil {
				svc.logContext().Error(err)

				return
			}
		}
	}
}

func (svc traceService) logContext() log.Logger {
	return svc.logger.WithFields(map[string]interface{}{
		"service": "TRACE",
		"type":    "WebSocket",
	})
}
