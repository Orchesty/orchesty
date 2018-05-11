package generator

import "fmt"

// populateJoinSource fills workflowConfig struct with joinSrc related values
func populateJoinSource(cc *composedConfig, all []*composedConfig) error {
	joinDst := findItemByEditorId(cc.ec.Settings.JoinDestination, all)
	if joinDst == nil {
		return fmt.Errorf("unable to find editor config node with id %s", cc.ec.Settings.JoinDestination)
	}

	joinDstChild := findFirstChildItem(joinDst.ec, all)
	if joinDstChild == nil {
		populateSkip(cc, all)

		return nil;
	}

	parent := findParentItem(cc.ec, all)
	parentalStep, err := findParentalStep(cc, parent)
	if err != nil {
		return err;
	}

	parentalStep.NextFlow.Id = joinDstChild.wfc.Id

	// TODO - check if joinSrc can have classic followers and add them to parent

	return nil;
}

func populateJoinDestination(cc *composedConfig, all []*composedConfig) error {
	return populateSkip(cc, all)
}
