NS=monitoring-dashboard

create-namespace:
	kubectl create ns ${NS}

diff:
	helm -n ${NS} diff -C4 upgrade -f values.yaml ${NS} --allow-unreleased ./

deploy:
	kubectl -n ${NS} apply -f secrets.yaml
	helm -n ${NS} upgrade -i -f values.yaml ${NS} ./
