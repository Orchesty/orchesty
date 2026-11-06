package topology

import (
	"testing"

	"github.com/hanaboso/pipes/bridge/pkg/enum"
	"github.com/hanaboso/pipes/bridge/pkg/model"
	"github.com/stretchr/testify/assert"
)

func node(id string, worker enum.WorkerType, followers ...string) model.Node {
	n := model.Node{ID: id, Worker: worker}
	for _, f := range followers {
		n.Followers = append(n.Followers, model.Follower{Id: f})
	}

	return n
}

func TestReplyPath_SideBranchIsExcluded(t *testing.T) {
	// start -> conn -> response
	//       \-> notify -> mail
	topology := model.Topology{Nodes: []model.Node{
		node("start", enum.WorkerType_Null, "conn", "notify"),
		node("conn", enum.WorkerType_Http, "response"),
		node("response", enum.WorkerType_Response),
		node("notify", enum.WorkerType_Http, "mail"),
		node("mail", enum.WorkerType_Http),
	}}

	assert.Equal(t, map[string]bool{"start": true, "conn": true, "response": true}, ReplyPath(topology))
}

func TestReplyPath_ConfirmNoBranchWithoutResponse(t *testing.T) {
	// start -> confirm -(yes)-> conn -> response
	//                  \-(no)-> log
	topology := model.Topology{Nodes: []model.Node{
		node("start", enum.WorkerType_Null, "confirm"),
		node("confirm", enum.WorkerType_Confirm, "conn", "log"),
		node("conn", enum.WorkerType_Http, "response"),
		node("response", enum.WorkerType_Response),
		node("log", enum.WorkerType_Http),
	}}

	path := ReplyPath(topology)

	assert.True(t, path["confirm"])
	assert.False(t, path["log"])
}

func TestReplyPath_NoResponseNode(t *testing.T) {
	topology := model.Topology{Nodes: []model.Node{
		node("start", enum.WorkerType_Null, "conn"),
		node("conn", enum.WorkerType_Http),
	}}

	assert.Empty(t, ReplyPath(topology))
}
