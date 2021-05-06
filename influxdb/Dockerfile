FROM influxdb:1.8-alpine

COPY ./influxdb.conf /etc/influxdb/influxdb.conf
COPY ./bin/ /docker-entrypoint-initdb.d/
