package generator

import (
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"fmt"
	"gopkg.in/mgo.v2/bson"
)

const (
	typeCondition       = "CONDITION"
	typeConditionBranch = "CONDITION_BRANCH"
	typeDistribute      = "DISTRIBUTE"
	typeEmail           = "EMAIL"
	typeEmpty           = "EMPTY"
	typeEnd             = "END"
	typeJoinDst         = "JOIN_DST"
	typeJoinSrc         = "JOIN_SRC"
	typeNotify          = "NOTIFY"
	typeTrigger         = "TRIGGER"
	typeWait            = "WAIT"
)

type composedConfig struct {
	ec   *ws.EditorConfig_EditorConfigItem
	wfc  *ws.WorkflowConfig
	skip bool
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

		if cc.skip == false {
			wfs = append(wfs, cfg)
		}
	}

	return wfs, nil
}

func pairConfigs(editor *ws.EditorConfig, clientId int, guid string) []*composedConfig {
	var pairs []*composedConfig

	for _, item := range editor.Items {
		cc := &composedConfig{
			ec: item,
			wfc: &ws.WorkflowConfig{
				Id:         bson.NewObjectId().Hex(),
				ClientId:   int32(clientId),
				ClientGuid: guid,
			},
			skip: false,
		}

		pairs = append(pairs, cc)
	}

	return pairs
}

func generateWorkflowConfig(cc *composedConfig, all []*composedConfig) (*ws.WorkflowConfig, error) {
	trigger := findTrigger(all)

	cc.wfc.EditorItemId = cc.ec.Id
	cc.wfc.Type = trigger.ec.Settings.Trigger.EventOptions.Type
	cc.wfc.ClientDomain = trigger.ec.Settings.Trigger.EventOptions.ClientDomain

	populateSpecifics(cc, all)

	return cc.wfc, nil
}

func populateSpecifics(cc *composedConfig, all []*composedConfig) error {
	switch cc.ec.Type {
	case typeCondition:
		return PopulateCondition(cc, all)
	case typeConditionBranch:
		cc.skip = true
		return PopulateConditionBranch(cc, all)
	case typeDistribute:
		return PopulateDefault(cc, all)
	case typeEmail:
		return PopulateEmail(cc, all)
	case typeNotify:
		return PopulateNotify(cc, all)
	case typeWait:
		return PopulateWait(cc, all)
	case typeEmpty, typeEnd, typeJoinDst, typeJoinSrc, typeTrigger:
		cc.skip = true
		return PopulateSkip(cc, all)
	default:
		return fmt.Errorf("cannot set type specifics to workflow config of type '%s'", cc.ec.Type)
	}
}

func findItemById(id string, all []*composedConfig) *composedConfig {
	for _, item := range all {
		if item.wfc.Id == id {
			return item
		}
	}

	return nil
}

func findRootItem(ec *ws.EditorConfig) *ws.EditorConfig_EditorConfigItem {
	for _, item := range ec.Items {
		if item.ParentId == "" && item.Type == typeTrigger {
			return item
		}
	}

	return nil
}

func findTrigger(all []*composedConfig) *composedConfig {
	triggers := findItemsByType(typeTrigger, all)
	if len(triggers) > 0 {
		return triggers[0]
	}

	return nil
}

func findItemsByType(desiredType string, all []*composedConfig) []*composedConfig {
	var matching []*composedConfig
	for _, cc := range all {
		if cc.ec.Type == desiredType {
			matching = append(matching, cc)
		}
	}

	return matching
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

func findFirstChildItem(parent *ws.EditorConfig_EditorConfigItem, all []*composedConfig) *composedConfig {
	children := findChildItems(parent, all)

	if len(children) > 0 {
		return children[0]
	}

	return nil
}

func findParentItem(child *ws.EditorConfig_EditorConfigItem, all []*composedConfig) *composedConfig {
	if child.ParentId == "" {
		return nil
	}

	for _, item := range all {
		if item.ec.Id == child.ParentId {
			return item
		}
	}

	return nil
}
