FROM influxdb:1.7-alpine

COPY ./influxdb.conf /etc/influxdb/influxdb.conf
COPY ./bin/ /docker-entrypoint-initdb.d/
