.PHONY: run-tests

run-tests:
	python -m unittest discover -v -s tests

.env:
	@if ! [ -f .env ]; then \
		sed -e "s|{DEV_UID}|$(shell id -u)|g" \
			-e "s|{DEV_GID}|$(shell id -u)|g" \
			.env.dist >> .env; \
	fi;
