.PHONY: manual-renew image-build image-push

IMAGE=europe-west1-docker.pkg.dev/orchesty-cloud-prod/infrastructure/certbot:latest

# That's what I call opinionated!
SA_STAGE=~/Downloads/orchesty-cloud-stage-hm-certbot.json

accounts.b64:
	tar cz accounts | base64 -w0 > accounts.b64

# Not ready for direct use! Read the code!
manual-renew-stage:
	@docker run --privileged --rm -e INFIX=stage -e BUCKET=oc-certbot-dir-stage -e GC_PROXY_NAME=lb1-target-proxy-3 -e GOOGLE_SA_KEY='$(shell cat $(SA_STAGE))' $(IMAGE) renew.sh -stage

image-build:
	docker build -t ${IMAGE} .

image-push:
	docker push ${IMAGE}
