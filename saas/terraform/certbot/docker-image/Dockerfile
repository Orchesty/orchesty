FROM google/cloud-sdk:slim

RUN apt-get update && \
    apt-get install -y fuse jq python3-wheel python3-certbot-dns-google  && \
    rm -rf /var/cache/apt /var/lib/apt

RUN curl -L https://github.com/GoogleCloudPlatform/gcsfuse/releases/download/v0.41.9/gcsfuse_0.41.9_amd64.deb > /tmp/gcfuse.deb && \
    dpkg -i /tmp/gcfuse.deb

COPY cli.ini /etc/letsencrypt/
COPY entrypoint.sh config.sh manage-gc.sh /
COPY renew.sh certonly.sh /usr/local/bin/

ENTRYPOINT ["/entrypoint.sh"]
