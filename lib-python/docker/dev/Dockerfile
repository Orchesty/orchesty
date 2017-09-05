FROM dkr.hanaboso.net/pipes/pipes/python-base:dev

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y git joe vim mc sudo openssh-server net-tools && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

RUN pip install Flask \
            Flask-JSON \
            lxml

EXPOSE 5000

COPY xml_parser/ /usr/src/app
COPY hb_metrics/ /usr/src/app/hb_metrics

COPY docker/dev/id_rsa.pub /root/.ssh/authorized_keys
COPY docker/dev/entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

CMD ["python2"]
