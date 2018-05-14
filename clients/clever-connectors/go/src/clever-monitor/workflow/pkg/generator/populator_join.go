package generator

import "fmt"

// populateJoinSource fills workflowConfig struct with joinSrc related values
func populateJoinSource(cc *composedConfig, all []*composedConfig) error {
	joinDst := findItemByEditorId(cc.ec.Settings.JoinDestination, all)
	if joinDst == nil {
		return fmt.Errorf("unable to find editor config node with id %s", cc.ec.Settings.JoinDestination)
	}

	joinDstChild := findFirstChildItem(joinDst, all)
	if joinDstChild == nil {
		populateSkip(cc, all)

		return nil;
	}

	parent := findParentItem(cc.ec, all)
	if parent == nil {
		return fmt.Errorf("unable to find parent for join node")
	}

	parentalStep := findParentalStep(cc, parent)
	if parentalStep == nil {
		return fmt.Errorf("unable to find parental step for join node")
	}

	parentalStep.NextFlow.Id = joinDstChild.wfc.Id

	return nil;
}

func populateJoinDestination(cc *composedConfig, all []*composedConfig) error {
	return populateSkip(cc, all)
}
