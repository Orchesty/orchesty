package generator

import (
	ws "clever-monitor/workflow/pkg/workflowservice/clevermonitor/analytics/protos/workflow"
	"fmt"
	"gopkg.in/mgo.v2/bson"
)

const (
	typeCondition          = "CONDITION"
	typeConditionBranchYes = "CONDITION_BRANCH_YES"
	typeConditionBranchNo  = "CONDITION_BRANCH_NO"
	typeDistribute         = "DISTRIBUTE"
	typeEmail              = "EMAIL"
	typeEmpty              = "EMPTY"
	typeEnd                = "END"
	typeJoinDst            = "JOIN_DST"
	typeJoinSrc            = "JOIN_SRC"
	typeNotify             = "NOTIFY"
	typeTrigger            = "TRIGGER"
	typeWait               = "WAIT"
)

type composedConfig struct {
	ec   *ws.EditorConfig_EditorConfigItem
	wfc  *ws.WorkflowConfig
	skip bool
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

func populateDefaults(all []*composedConfig) error {
	for _, cc := range all {
		err := populateDefault(cc, all)
		if err != nil {
			return err
		}

		cc.skip = isSkippable(cc)
	}

	return nil
}

// populateSpecifics decides how to fill workflowConfig's properties due to its type
func populateSpecifics(cc *composedConfig, all []*composedConfig) error {
	switch cc.ec.Type {
	case typeCondition:
		return populateCondition(cc, all)
	case typeConditionBranchYes:
		return populateConditionBranch(true, cc, all)
	case typeConditionBranchNo:
		return populateConditionBranch(false, cc, all)
	case typeDistribute:
		return populateDistribute(cc, all)
	case typeEmail:
		return populateEmail(cc, all)
	case typeJoinSrc:
		return populateJoinSource(cc, all)
	case typeJoinDst:
		return populateJoinDestination(cc, all)
	case typeNotify:
		return populateNotify(cc, all)
	case typeWait:
		return populateWait(cc, all)
	case typeEmpty, typeEnd, typeTrigger:
		return populateSkip(cc, all)

	default:
		return fmt.Errorf("cannot set type specifics to workflow config of type '%s'", cc.ec.Type)
	}
}

// isSkippable returns true if given composedConfig should not be present in output list
func isSkippable(cc *composedConfig) bool {
	switch cc.ec.Type {
	case typeCondition, typeDistribute, typeEmail, typeNotify, typeWait:
		return false
	default:
		return true
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

// findItemByEditorId tries to find composedConfig by editorConfig's ID
func findItemByEditorId(id string, all []*composedConfig) *composedConfig {
	for _, item := range all {
		if item.ec.Id == id {
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

func findChildItemsByEcId(ecId string, all []*composedConfig) []*composedConfig {
	var children []*composedConfig

	for _, item := range all {
		if item.ec.ParentId == ecId {
			children = append(children, item)
		}
	}

	return children
}

// findChildItems finds all child configs of given composedConfig from provided slice of composedConfigs
func findChildItems(parent *composedConfig, all []*composedConfig) []*composedConfig {
	var children []*composedConfig

	for _, item := range all {
		for _, parStep := range parent.wfc.Steps {
			if parStep.NextFlow == nil {
				continue
			}
			if parStep.NextFlow.Id == item.wfc.Id {
				children = append(children, item)
			}
		}
	}

	return children
}

// findFirstChildItem returns the first found child item of given composedConfig from provided slice
func findFirstChildItem(parent *composedConfig, all []*composedConfig) *composedConfig {
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

// findParentalStep finds corresponding parental Step for current composedConfig
func findParentalStep(cc *composedConfig, parent *composedConfig) *ws.WorkflowConfig_Step {
	for _, parStep := range parent.wfc.Steps {
		if parStep.NextFlow.Id == cc.wfc.Id {
			return parStep
		}
	}

	return nil
}
