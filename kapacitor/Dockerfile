FROM kapacitor:1.4

COPY ./tasks /root/.kapacitor/load/tasks
COPY ./entrypoint.sh /pipes/
ENTRYPOINT ["/pipes/entrypoint.sh"]

CMD ["kapacitord"]
