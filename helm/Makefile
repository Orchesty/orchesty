helm-repo:
	git clone git@github.com:Orchesty/helm-charts.git -b gh-pages helm-repo
	
orchesty-release:
	helm package charts/orchesty -d helm-repo

prepare:
	helm repo index helm-repo --url https://orchesty.github.io/helm-charts
	git -C helm-repo add .

push:
	git -C helm-repo commit -m "Update releases"
	git -C helm-repo push
