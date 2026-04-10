HELM:= helm
KUBECTL:= kubectl
PROJECT:=cloud-control
NAMESPACE:=--namespace $(PROJECT)
deps:
	$(HELM) dependency update ./

apply-secret:
	$(KUBECTL) apply -f secret.prod.yaml $(NAMESPACE)
	$(KUBECTL) apply -f secret.gcs.yaml $(NAMESPACE)

diff:
	$(HELM) diff -C4 -f values.yaml $(PROJECT) ./ $(NAMESPACE)

deploy:
	$(HELM) upgrade -i --values values.yaml $(PROJECT) ./ $(NAMESPACE) --create-namespace

remove:
	$(HELM) uninstall $(PROJECT) $(NAMESPACE)
