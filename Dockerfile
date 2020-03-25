FROM docker.elastic.co/logstash/logstash-oss:6.2.2

# EVNS
ENV MONGO_HOST=''
ENV MONGO_USER=''
ENV MONGO_PASS=''
ENV MONGO_DATABASE=''
ENV MONGO_COLLECTION=''
ENV MONGO_SIZE_COLLETION=524288000

COPY ./mongodb-org-3.6.repo /etc/yum.repos.d/mongodb-org-3.6.repo

USER root

RUN yum install -y mongodb-org-shell

USER logstash

# https://github.com/logstash-plugins/logstash-output-mongodb/issues/60
RUN logstash-plugin install --version=3.1.5 logstash-output-mongodb

COPY ./entrypoint.sh /

ENTRYPOINT ["/entrypoint.sh"]