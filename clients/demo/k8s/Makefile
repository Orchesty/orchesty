.PHONY: apply-pvcs apply-all down destroy

KUBECTL?=$(shell which kubectl || echo microk8s.kubectl || (echo "No kubectl or microk8s.kubectl found"; exit 1))
NAMESPACE=$(shell ${KUBECTL} config view -o 'jsonpath={..namespace}')
DEV_APPS=elasticsearch influxdb mongodb rabbitmq
DEV_VOLUMES=elasticsearch-data influxdb-data mongodb-data rabbitmq-data
CALM=echo "Some of the resources were probably already gone, that's OK"
LOCAL_STORAGE=${HOME}/.ssd/k8s-local-storage
HOSTNAME=$(shell hostname)

export LOCAL_STORAGE
export HOSTNAME

check:
	@if [ "${NAMESPACE}" != "$(shell cat .namespace_lock)" ]; then \
		echo "Current namespace '${NAMESPACE}' differs from the locked one ($(shell cat .namespace_lock))"; \
		exit 1; \
	fi

apply-dev-storage: check
	for DIR in ${DEV_VOLUMES}; do \
		mkdir -p ${LOCAL_STORAGE}/$${DIR}; \
	done
	envsubst < dev/local-storage.yaml | ${KUBECTL} apply -f -

delete-dev-storage: check
	envsubst < dev/local-storage.yaml | ${KUBECTL} delete -f - || ${CALM}

apply-dev-services: check apply-dev-storage
	for APP in ${DEV_APPS}; do \
		${KUBECTL} apply -f dev/$${APP}.yaml; \
	done

delete-dev-services: check
	for APP in ${DEV_APPS}; do \
		${KUBECTL} delete -f dev/$${APP}.yaml; \
	done || ${CALM}

apply-all: check apply-dev-services
	cat *.yaml | ${KUBECTL} apply -f -

down: check
	cat *.yaml | ${KUBECTL} delete -f - || ${CALM}

destroy: check down delete-dev-services delete-dev-storage
