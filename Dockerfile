FROM kapacitor:alpine

COPY ./tasks /root/.kapacitor/load/tasks
COPY ./kapacitor.conf /etc/kapacitor/kapacitor.conf
