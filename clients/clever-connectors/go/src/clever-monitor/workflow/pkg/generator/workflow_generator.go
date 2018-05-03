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

// NewWorkflowGenerator returns new instance of WorkflowGenerator
func NewWorkflowGenerator() *workflowGenerator {
	return &workflowGenerator{}
}

// Generate returns the slice of generated workflow config due to given editor config data
func (gen *workflowGenerator) Generate(
	editor *ws.EditorConfig,
	clientId int,
	guid string,
) ([]*ws.WorkflowConfig, error) {
	pairs := pairConfigs(editor, clientId, guid)

	var wfs []*ws.WorkflowConfig
	for _, cc := range pairs {
		// TODO - try to run concurrently in goroutines
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

// pairConfigs links EditorConfig's node and WorkflowConfig node for easier work
// It creates simple WorkflowConfig item with generated unique ID
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

// generateWorkflowConfig fills workflowConfig with real data
func generateWorkflowConfig(cc *composedConfig, all []*composedConfig) (*ws.WorkflowConfig, error) {
	trigger := findTrigger(all)

	cc.wfc.EditorItemId = cc.ec.Id
	cc.wfc.Type = trigger.ec.Settings.Trigger.EventOptions.Type
	cc.wfc.ClientDomain = trigger.ec.Settings.Trigger.EventOptions.ClientDomain

	populateSpecifics(cc, all)

	return cc.wfc, nil
}

// populateSpecifics decides how to fill workflowConfig's properties due to its type
func populateSpecifics(cc *composedConfig, all []*composedConfig) error {
	switch cc.ec.Type {
	case typeCondition:
		return populateCondition(cc, all)
	case typeConditionBranch:
		cc.skip = true // treat specifically byt do not include to output
		return populateConditionBranch(cc, all)
	case typeDistribute:
		return populateDefault(cc, all)
	case typeEmail:
		return populateEmail(cc, all)
	case typeJoinSrc:
		panic("join src not implemented yet")
	case typeJoinDst:
		panic("join dst not implemented yet")
	case typeNotify:
		return populateNotify(cc, all)
	case typeWait:
		return populateWait(cc, all)
	case typeEmpty, typeEnd, typeTrigger:
		cc.skip = true // do not include to output
		return populateSkip(cc, all)

	default:
		return fmt.Errorf("cannot set type specifics to workflow config of type '%s'", cc.ec.Type)
	}
}

// findItemById tries to find composedConfig by workflowConfig's ID
func findItemById(id string, all []*composedConfig) *composedConfig {
	for _, item := range all {
		if item.wfc.Id == id {
			return item
		}
	}

	return nil
}

// findTrigger find the root composedConfig
func findTrigger(all []*composedConfig) *composedConfig {
	triggers := findItemsByType(typeTrigger, all)
	if len(triggers) > 0 {
		return triggers[0]
	}

	return nil
}

// findItemsByType finds all composedConfig by the type
func findItemsByType(desiredType string, all []*composedConfig) []*composedConfig {
	var matching []*composedConfig
	for _, cc := range all {
		if cc.ec.Type == desiredType {
			matching = append(matching, cc)
		}
	}

	return matching
}

// findChildItems finds all child configs of given composedConfig from provided slice of composedConfigs
func findChildItems(parent *ws.EditorConfig_EditorConfigItem, all []*composedConfig) []*composedConfig {
	var children []*composedConfig

	for _, item := range all {
		if item.ec.ParentId == parent.Id {
			children = append(children, item)
		}
	}

	return children
}

// findFirstChildItem returns the first found child item of given composedConfig from provided slice
func findFirstChildItem(parent *ws.EditorConfig_EditorConfigItem, all []*composedConfig) *composedConfig {
	children := findChildItems(parent, all)

	if len(children) > 0 {
		return children[0]
	}

	return nil
}

// findParentItem returns parental composedConfig of givend composedConfig if it exists
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
