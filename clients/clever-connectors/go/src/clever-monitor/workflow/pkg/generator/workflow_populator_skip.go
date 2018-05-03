package generator

// TODO - test
func PopulateSkip(cc *composedConfig, all []*composedConfig) error {
	parent := findParentItem(cc.ec, all)
	if parent == nil {
		// root item, we don't need to update it's parent
		return nil
	}

	child := findFirstChildItem(cc.ec, all)
	if child == nil {
		// item does not have any child
		for _, parStep := range parent.wfc.Steps {
			parStep.NextFlow = nil
		}

		return nil
	}

	// has parent and has children - link them
	for _, parStep := range parent.wfc.Steps {
		parStep.NextFlow.Id = child.wfc.Id
	}

	return nil
}
