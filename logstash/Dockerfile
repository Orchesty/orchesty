FROM docker.elastic.co/logstash/logstash-oss:6.8.23

# ENVs
ENV LOG_STORAGE=mongodb
ENV MONGO_DSN=''
ENV MONGO_DB=''
ENV MONGO_COLLECTION=''

# not implemented yet
ENV MONGO_COLLECTION_SIZE=524288000

USER root

COPY ./mongodb-org-3.6.repo /etc/yum.repos.d/mongodb-org-3.6.repo

# https://github.com/singhksandeep25/logstash-output-mongodb/releases/tag/v3.1.7
COPY ./logstash-output-mongodb-3.1.7.gem /logstash-output-mongodb-3.1.7.gem

# https://rubygems.org/gems/logstash-filter-drop
COPY ./logstash-filter-drop-3.0.5.gem /logstash-filter-drop-3.0.5.gem

RUN rm -rf /usr/share/logstash/pipeline/*
COPY ./conf /usr/share/logstash/pipeline-variants

RUN yum install -y mongodb-org-shell

USER logstash

RUN logstash-plugin install --no-verify /logstash-output-mongodb-3.1.7.gem
RUN logstash-plugin install --no-verify /logstash-filter-drop-3.0.5.gem

COPY entrypoint.sh /
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/bin/sh", "-c", "#(nop) "]
