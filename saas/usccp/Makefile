postprocess:
	npx api-spec-converter --from openapi_3 --to swagger_2 -s yaml openapi.yaml > swagger.yaml
	yq '. += {"host": "usccp.cloud.orchesty.io"}' -i swagger.yaml

deploy:
	gcloud endpoints services deploy swagger.yaml
