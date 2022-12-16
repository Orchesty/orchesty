.PHONY: check-workspace reconfigure

SHELL=/bin/bash
BUCKET=orchesty-cloud-terraform-jzx1

# Valid workspaces for current stack 
# # - overridable in Makefile
# # - separate multiple entries by |
VALID_WORKSPACES?="default"

# Override in Makefile
STACK?="no-stack-defined"

TERRAFORM?=$(shell which terraform1 || echo terraform)

check-workspace:
	@if [ -z "$(TF_WORKSPACE)" ]; then \
		echo "Please 'export TF_WORKSPACE=someenv' first"; \
		exit 1; \
	fi
	@if [[ ! "$(TF_WORKSPACE)" =~ ^($(VALID_WORKSPACES))$$ ]]; then \
		echo "Bad workspace: '$(TF_WORKSPACE)'. Valid  workspaces: $(VALID_WORKSPACES)"; \
		exit 1; \
	fi

reconfigure: check-workspace
	$(TERRAFORM) init \
		-reconfigure \
		-backend-config="bucket=$(BUCKET)" \
		-backend-config="prefix=$(TF_WORKSPACE)/$(STACK)" \
		-backend-config="credentials=../account-$(TF_WORKSPACE).json" \
		$(ARGS)
