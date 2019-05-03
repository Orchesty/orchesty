FROM dkr.hanaboso.net/pipes/pipes/python-base3:dev

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y git joe vim mc sudo openssh-server net-tools gcc && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

RUN pip install Flask \
            Flask-JSON

EXPOSE 5000

COPY hb-pipes-sdk /usr/src/hb-pipes-sdk
COPY hb-pipes-metrics /usr/src/hb-pipes-metrics
RUN cd /usr/src/hb-pipes-sdk && rm -rf venv .idea dist build hb_pipes_sdk.egg-info __pycache__ hb_pipes_sdk/__pycache__ && \
	python3 setup.py bdist_wheel && \
	pip install -e /usr/src/hb-pipes-sdk && \
	cd /usr/src/hb-pipes-metrics && rm -rf venv .idea dist build hb_pipes_metrics.egg-info __pycache__ hb_pipes_metrics/__pycache__ && \
	python3 setup.py bdist_wheel && \
	pip install -e /usr/src/hb-pipes-metrics

COPY hb-pipes-node/ /usr/src/app/
RUN cd /usr/src/app && rm -rf venv .idea __pycache__

RUN apt-get purge -y gcc

ENV FLASK_DEBUG=0
ENV FLASK_ENV=development

COPY docker/dev/id_rsa.pub /root/.ssh/authorized_keys
COPY docker/dev/entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

CMD ["python3"]
