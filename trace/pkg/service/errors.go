package service

import "errors"

// ErrUnauthorized is wrapped by manifest/AI service errors when the upstream
// HTTP call returned 401/403. The trace WebSocket dispatcher uses
// errors.Is(err, ErrUnauthorized) to relay the auth failure to the client as
// a code:401 frame so the FE can rotate the access token silently instead of
// surfacing a confusing 502 ("Bad Gateway") in the chat.
var ErrUnauthorized = errors.New("upstream unauthorized")
