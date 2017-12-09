FROM dkr.hanaboso.net/pipes/pipes/python-base3:dev

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y gcc git joe vim mc sudo openssh-server net-tools python3-pymongo cron curl && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

RUN pip install Flask Flask-JSON pymongo python-crontab

EXPOSE 5000

COPY cron/id_rsa.pub /root/.ssh/authorized_keys
COPY cron/entrypoint.sh /

COPY cron/ /usr/src/app
ENTRYPOINT [ "/entrypoint.sh" ]
CMD cd /usr/src/app && python app.py
