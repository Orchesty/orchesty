CONFIG:= --kubeconfig ~/.kube/config_k3s_hb_office
HELM:= helm $(CONFIG)
KUBECTL:= kubectl $(CONFIG)
PROJECT:=pipes
NAMESPACE:=--namespace $(PROJECT)

SECRET_FILE:=secrets.stage.yaml
SECRET:=pipes-secrets
PULL_SECRET:=hanaboso-registry-pull-secret

VALUES_FILE:=values.stage.yaml

create-namespace:
	$(KUBECTL) create namespace $(PROJECT)

deploy:
	$(KUBECTL) $(NAMESPACE) apply -f $(SECRET_FILE)
	$(KUBECTL) $(NAMESPACE) label secret $(SECRET) app.kubernetes.io/managed-by=Helm 
	$(KUBECTL) $(NAMESPACE) annotate secret $(SECRET) meta.helm.sh/release-name=$(PROJECT) --overwrite 
	$(KUBECTL) $(NAMESPACE) annotate secret $(SECRET) meta.helm.sh/release-namespace=$(PROJECT) --overwrite

diff:
	$(HELM) ${NAMESPACE} diff -C4 upgrade -f $(VALUES_FILE) --allow-unreleased $(PROJECT) ./

remove:
	$(HELM) uninstall $(PROJECT) $(NAMESPACE)

copy-global-pull-secret:
	$(KUBECTL) -n default get secret $(PULL_SECRET) -ojson | jq 'del(.metadata.namespace)' | $(KUBECTL) $(NAMESPACE) apply -f -