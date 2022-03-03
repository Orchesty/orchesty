FROM docker.elastic.co/logstash/logstash-oss:6.8.23

ARG TYPE

# EVNS
ENV MONGO_HOST=''
ENV MONGO_USER=''
ENV MONGO_PASS=''
ENV MONGO_DATABASE=''
ENV MONGO_COLLECTION=''
ENV MONGO_SIZE_COLLETION=524288000

COPY ./mongodb-org-3.6.repo /etc/yum.repos.d/mongodb-org-3.6.repo
COPY ./logstash-output-mongodb-3.1.7.gem /logstash-output-mongodb-3.1.7.gem
COPY ./conf /usr/share/logstash/pipeline

USER root

RUN if [ "$TYPE" = "mongo" ] ; then \
    cd /usr/share/logstash/pipeline/  \
    && rm logstash_elastics.conf && mv logstash_mongo.conf logstash.conf \
    && echo "Using config for MongoDB!"; \
  else \
    cd /usr/share/logstash/pipeline/  \
    && rm logstash_mongo.conf && mv logstash_elastics.conf logstash.conf \
    && echo "Using config for ElasticSearch!"; \
  fi


RUN yum install -y mongodb-org-shell

USER logstash

# https://github.com/singhksandeep25/logstash-output-mongodb/releases/tag/v3.1.7
RUN logstash-plugin install --no-verify /logstash-output-mongodb-3.1.7.gem
