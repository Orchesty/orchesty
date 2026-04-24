package service

import (
	"encoding/json"
	"fmt"
	"net/http"
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
	authTimeout    = 10 * time.Second
)

type (
	TraceService interface {
		HandleConnection(writer http.ResponseWriter, request *http.Request, userID string)
	}

	traceService struct {
		upgrader        websocket.Upgrader
		authService     AuthService
		manifestService ManifestService
		aiService       AIService
		logger          log.Logger
	}

	session struct {
		conn          *websocket.Conn
		token         string
		userID        string
		authenticated bool
		mu            sync.RWMutex
	}
)

func NewTraceService(authService AuthService, manifestService ManifestService, aiService AIService, logger log.Logger) TraceService {
	return traceService{
		upgrader: websocket.Upgrader{
			CheckOrigin: func(_ *http.Request) bool {
				return true
			},
			ReadBufferSize:  1024,
			WriteBufferSize: 1024,
		},
		authService:     authService,
		manifestService: manifestService,
		aiService:       aiService,
		logger:          logger,
	}
}

func (svc traceService) HandleConnection(writer http.ResponseWriter, request *http.Request, userID string) {
	conn, err := svc.upgrader.Upgrade(writer, request, nil)
	if err != nil {
		svc.logContext().Error(err)

		return
	}

	sess := &session{conn: conn, userID: userID}

	svc.logContext().Info("WebSocket connection established (awaiting auth)")

	var wg sync.WaitGroup
	done := make(chan struct{})

	// Close the connection if no valid token arrives within authTimeout.
	authTimer := time.AfterFunc(authTimeout, func() {
		sess.mu.RLock()
		authed := sess.authenticated
		sess.mu.RUnlock()

		if !authed {
			svc.sendError(sess, http.StatusUnauthorized, "authentication timeout: token frame not received")
			_ = sess.conn.Close()
		}
	})
	defer authTimer.Stop()

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
		svc.sendError(sess, http.StatusBadRequest, "invalid token data")

		return
	}

	authHeader := fmt.Sprintf("Bearer %s", td.Token)

	user, body, statusCode, err := svc.authService.CheckLogged(authHeader)
	if err != nil {
		svc.sendError(sess, http.StatusBadGateway, fmt.Sprintf("backend unreachable: %s", err.Error()))
		_ = sess.conn.Close()

		return
	}

	if statusCode != http.StatusOK {
		svc.sendError(sess, statusCode, fmt.Sprintf("authentication failed: %s", strings.TrimSpace(string(body))))
		_ = sess.conn.Close()

		return
	}

	if user == nil || user.ID == "" {
		svc.sendError(sess, http.StatusInternalServerError, "could not parse user from auth response")
		_ = sess.conn.Close()

		return
	}

	// The token is the source of truth for the user identity. The ?user= query parameter is
	// only an opaque connection hint provided by the browser (it may be a Mongo ObjectId in
	// standalone mode or an Auth0 sub in cloud mode). We log a mismatch for observability but
	// always promote the token-derived user to the canonical session userID.
	if sess.userID != "" && sess.userID != user.ID && sess.userID != user.Email {
		svc.logContext().Info(
			"userID hint mismatch: query=%s tokenUser=%s tokenEmail=%s (using token identity)",
			sess.userID, user.ID, user.Email,
		)
	}

	sess.mu.Lock()
	sess.token = td.Token
	sess.userID = user.ID
	sess.authenticated = true
	sess.mu.Unlock()

	svc.logContext().Info("Authenticated as user %s (%s)", user.ID, user.Email)
}

func (svc traceService) handleRequest(sess *session, data json.RawMessage) {
	sess.mu.RLock()
	authed := sess.authenticated
	sess.mu.RUnlock()

	if !authed {
		svc.sendError(sess, http.StatusUnauthorized, "not authenticated; send a 'token' frame first")

		return
	}

	var rd RequestData
	if err := json.Unmarshal(data, &rd); err != nil || rd.Content == "" {
		svc.sendError(sess, http.StatusBadRequest, "invalid request data")

		return
	}

	sess.mu.RLock()
	token := sess.token
	userID := sess.userID
	sess.mu.RUnlock()

	actions, err := svc.manifestService.FetchManifest(token)
	if err != nil {
		svc.sendError(sess, 502, fmt.Sprintf("failed to fetch manifest: %s", err.Error()))

		return
	}

	prompt := BuildPrompt(rd.Content, actions)

	aiResponse, err := svc.aiService.SendPrompt(token, userID, prompt)
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
