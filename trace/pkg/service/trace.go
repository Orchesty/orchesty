package service

import (
	"encoding/json"
	"errors"
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

	// maxHistoryTurns caps the per-session conversation kept in memory and sent
	// to the LLM. The window slides — older user/assistant turns drop off so
	// token usage stays bounded across long chats.
	maxHistoryTurns = 20
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
		// history is the rolling chat memory for this WebSocket session. It is
		// guarded by mu and trimmed to the last maxHistoryTurns entries before
		// every LLM call.
		history []ChatTurn
		mu      sync.RWMutex
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

	// Capture the current snapshot of session state once, then work with locals.
	sess.mu.RLock()
	token := sess.token
	userID := sess.userID
	sess.mu.RUnlock()

	// Append the user turn first so the model always sees the latest message
	// at the tail of the history window, even if a downstream call fails.
	svc.appendTurn(sess, ChatTurn{Role: "user", Content: rd.Content})

	actions, err := svc.manifestService.FetchManifest(token)
	if err != nil {
		svc.sendError(sess, upstreamErrorCode(err), fmt.Sprintf("failed to fetch manifest: %s", err.Error()))

		return
	}

	system := BuildSystemPrompt(actions)
	history := svc.snapshotHistory(sess)

	aiResponse, err := svc.aiService.SendChat(token, userID, system, history)
	if err != nil {
		svc.sendError(sess, upstreamErrorCode(err), fmt.Sprintf("AI request failed: %s", err.Error()))

		return
	}

	// The model is contracted to return one of two JSON envelopes. Anything
	// else is treated as a degraded "raw text" fallback so the user is not
	// stranded with a stack trace when the model misbehaves.
	envelope := parseEnvelope(aiResponse)

	switch {
	case envelope.Tool != "":
		svc.dispatchTool(sess, token, userID, envelope, aiResponse)
	case envelope.Audit != "":
		svc.dispatchAudit(sess, token, envelope, aiResponse)
	case envelope.Reply != "":
		svc.appendTurn(sess, ChatTurn{Role: "assistant", Content: envelope.Reply})
		svc.sendMessage(sess, TypeResponse, ResponseData{Content: envelope.Reply})
	default:
		svc.appendTurn(sess, ChatTurn{Role: "assistant", Content: aiResponse})
		svc.sendMessage(sess, TypeResponse, ResponseData{Content: aiResponse})
	}
}

// dispatchTool forwards a `{tool, args}` envelope to /mcp/run and turns the
// compact JSON result into user-facing text.
//
// Two-tier strategy:
//  1. Known structured kinds (`list`, `timeseries`) are rendered
//     deterministically in Go. This keeps every field the aggregator returns
//     (nodeName + topologyName, totals, peak bucket) and avoids spending an
//     LLM call when the shape is already structured.
//  2. Unknown / future kinds fall back to a second LLM pass with the
//     summariser system prompt so we do not silently drop new tool results
//     while the renderer catches up.
//
// Only the final short text lands in conversation history — the bulky JSON
// stays out of the model's context window either way.
func (svc traceService) dispatchTool(sess *session, token, userID string, envelope chatEnvelope, raw string) {
	mcpResult, err := svc.manifestService.RunAction(token, []byte(raw))
	if err != nil {
		svc.sendError(sess, upstreamErrorCode(err), fmt.Sprintf("MCP run failed: %s", err.Error()))

		return
	}

	if rendered, ok := renderToolResult(mcpResult); ok {
		rendered = strings.TrimSpace(rendered)
		if rendered == "" {
			rendered = fmt.Sprintf("Action %q returned no readable result.", envelope.Tool)
		}

		svc.appendTurn(sess, ChatTurn{Role: "assistant", Content: rendered})
		svc.sendMessage(sess, TypeResponse, ResponseData{Content: rendered})

		return
	}

	summariser := BuildSummariserPrompt(envelope.Tool)
	turn := ChatTurn{Role: "user", Content: string(mcpResult)}
	summary, err := svc.aiService.SendChat(token, userID, summariser, []ChatTurn{turn})
	if err != nil {
		svc.sendError(sess, upstreamErrorCode(err), fmt.Sprintf("AI summary failed: %s", err.Error()))

		return
	}

	summary = strings.TrimSpace(summary)
	if summary == "" {
		summary = fmt.Sprintf("Action %q returned no readable summary.", envelope.Tool)
	}

	svc.appendTurn(sess, ChatTurn{Role: "assistant", Content: summary})
	svc.sendMessage(sess, TypeResponse, ResponseData{Content: summary})
}

// dispatchAudit forwards a recognised action envelope to /mcp/run, formats the
// result for the user and records a compact assistant turn in the history so
// the model can refer back to "the last search" in subsequent turns without
// having the full Loki dump replayed in the context window.
func (svc traceService) dispatchAudit(sess *session, token string, envelope chatEnvelope, raw string) {
	mcpResult, err := svc.manifestService.RunAction(token, []byte(raw))
	if err != nil {
		svc.sendError(sess, upstreamErrorCode(err), fmt.Sprintf("MCP run failed: %s", err.Error()))

		return
	}

	var logs []string
	if err := json.Unmarshal(mcpResult, &logs); err == nil {
		mcpResult = []byte(strings.Join(logs, "\n"))
	}

	content := string(mcpResult)

	summary := fmt.Sprintf("(ran action %q, returned %d chars)", envelope.Audit, len(content))
	svc.appendTurn(sess, ChatTurn{Role: "assistant", Content: summary})

	svc.sendMessage(sess, TypeResponse, ResponseData{Content: content})
}

// upstreamErrorCode classifies an error from the manifest / AI HTTP clients
// and returns the WebSocket error code we want the FE to see. Auth failures
// surface as 401 so the browser-side socket can rotate the access token in
// place; everything else stays 502 (Bad Gateway) which the FE renders as a
// generic transport error.
func upstreamErrorCode(err error) int {
	if errors.Is(err, ErrUnauthorized) {
		return http.StatusUnauthorized
	}

	return http.StatusBadGateway
}

// appendTurn pushes a turn onto the session history under the write lock and
// drops the oldest turns to keep the window bounded.
func (svc traceService) appendTurn(sess *session, turn ChatTurn) {
	sess.mu.Lock()
	defer sess.mu.Unlock()

	sess.history = append(sess.history, turn)
	if overflow := len(sess.history) - maxHistoryTurns; overflow > 0 {
		sess.history = sess.history[overflow:]
	}
}

// snapshotHistory returns a defensive copy so the caller can pass it to the AI
// client without holding the session lock during the network round trip.
func (svc traceService) snapshotHistory(sess *session) []ChatTurn {
	sess.mu.RLock()
	defer sess.mu.RUnlock()

	snapshot := make([]ChatTurn, len(sess.history))
	copy(snapshot, sess.history)

	return snapshot
}

// chatEnvelope is the multi-shape JSON the model is instructed to emit.
//
//   - {audit, data, [day|from|to|period]} — entity history flow, FE renders
//     a structured run report.
//   - {tool, args}                        — generic MCP tool flow, the
//     compact JSON result is summarised by a second LLM pass into prose.
//   - {reply}                             — small-talk / clarification text.
//
// Unset fields stay zero-valued and are ignored by the dispatcher.
type chatEnvelope struct {
	Audit string                 `json:"audit"`
	Data  map[string]interface{} `json:"data"`
	Tool  string                 `json:"tool"`
	Args  map[string]interface{} `json:"args"`
	Reply string                 `json:"reply"`
}

// parseEnvelope tolerates light formatting noise around the JSON (whitespace,
// stray prose before/after the object, accidental ```json fences) and falls
// back to a zero envelope when nothing parses, which the caller treats as
// "raw text reply".
func parseEnvelope(raw string) chatEnvelope {
	var env chatEnvelope
	trimmed := strings.TrimSpace(raw)

	if trimmed == "" {
		return env
	}

	if err := json.Unmarshal([]byte(trimmed), &env); err == nil {
		return env
	}

	start := strings.Index(trimmed, "{")
	end := strings.LastIndex(trimmed, "}")
	if start >= 0 && end > start {
		_ = json.Unmarshal([]byte(trimmed[start:end+1]), &env)
	}

	return env
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
