package generator

import (
	ws "clever-monitor/workflow/pkg/workflowservice"
	"fmt"
	"gopkg.in/mgo.v2/bson"
)

const root = "root"

const (
	typeCondition       = "COND"
	typeConditionBranch = "COND_BRANCH"
	typeTrigger         = "TRIGGER"
	typeWait            = "WAIT"
	typeEmail           = "EMAIL"
	typeNotify          = "NOTIFY"
	typeJoinSrc         = "JOIN_SRC"
	typeJoinDst         = "JOIN_DST"
	typeEmpty           = "EMPTY"
	typeEnd             = "END"
)

type composedConfig struct {
	ec *ws.EditorConfig_EditorConfigItem
	wfc *ws.WorkflowConfig
}

type workflowGenerator struct{}

func NewWorkflowGenerator() *workflowGenerator {
	return &workflowGenerator{}
}

func (gen *workflowGenerator) Generate(
	editor *ws.EditorConfig,
	clientId int,
	guid string,
) ([]*ws.WorkflowConfig, error) {
	pairs := pairConfigs(editor, clientId, guid)

	var wfs []*ws.WorkflowConfig
	for _, cc := range pairs {
		// TODO - run concurrently in goroutines
		cfg, err := generateWorkflowConfig(cc, pairs)
		if err != nil {
			return wfs, fmt.Errorf("unable to generate worflow from editor item '%s'. Error: %s", cc.ec.Id, err)
		}

		wfs = append(wfs, cfg)
	}

	return wfs, nil
}

func pairConfigs(editor *ws.EditorConfig, clientId int, guid string) []*composedConfig {
	var pairs []*composedConfig

	for _, item := range editor.Items {
		cc := &composedConfig{
			ec: item,
			wfc: &ws.WorkflowConfig{
				Id: bson.NewObjectId().Hex(),
				ClientId: int32(clientId),
				ClientGuid: guid,
			},
		}

		pairs = append(pairs, cc)
	}

	return pairs
}

func generateWorkflowConfig(cc *composedConfig, all []*composedConfig) (*ws.WorkflowConfig, error) {

	cc.wfc.Type = "" // TODO - where to get?
	cc.wfc.ClientDomain = "" // TODO - where to get?

	populateSpecifics(cc, all)

	return cc.wfc, nil
}

func populateSpecifics(cc *composedConfig, all []*composedConfig) error {
	switch cc.ec.Type {
	case typeTrigger:
		return PopulateTrigger(cc, all)
	case typeCondition:
		return PopulateCondition(cc, all)
	default:
		// return fmt.Errorf("cannot set type specifics to workflow config of type '%s'", cc.ec.Type)
		return PopulateDefault(cc, all)
	}
}

func findItemById(id string, ec *ws.EditorConfig) *ws.EditorConfig_EditorConfigItem {
	for _, item := range ec.Items {
		if item.Id == id {
			return item
		}
	}

	return nil
}

func findRootItem(ec *ws.EditorConfig) *ws.EditorConfig_EditorConfigItem {
	for _, item := range ec.Items {
		if item.Id == root {
			return item
		}
	}

	return nil
}

func findChildItems(parent *ws.EditorConfig_EditorConfigItem, all []*composedConfig) []*composedConfig {
	var children []*composedConfig

	for _, item := range all {
		if item.ec.ParentId == parent.Id {
			children = append(children, item)
		}
	}

	return children
}
