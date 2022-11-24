FROM debian:bullseye-slim

RUN apt-get update && \
    apt-get install -y curl && \
    echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] https://packages.cloud.google.com/apt cloud-sdk main" > /etc/apt/sources.list.d/google-cloud-sdk.list && \
    curl https://packages.cloud.google.com/apt/doc/apt-key.gpg > /usr/share/keyrings/cloud.google.gpg && \
    apt-get update && \
    apt-get install -y \
      google-cloud-cli \
      skopeo && \
    rm -rf /var/lib/apt/*

RUN curl -fsSL https://raw.githubusercontent.com/helm/helm/main/scripts/get-helm-3 | bash

COPY entrypoint.sh /

ENTRYPOINT ["/entrypoint.sh"]
