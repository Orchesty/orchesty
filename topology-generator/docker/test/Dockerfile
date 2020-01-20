FROM mongo:3.4.23-xenial
# install dependencies for docker
RUN apt-get update
RUN apt-get install -y\
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg2 \
        software-properties-common
RUN curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add -
RUN add-apt-repository \
   "deb [arch=amd64] https://download.docker.com/linux/debian \
   buster \
   stable"
# install docker
RUN apt-get update
RUN apt-get install -y docker-ce docker-ce-cli containerd.io
RUN curl -L "https://github.com/docker/compose/releases/download/1.25.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose && \
    chmod +x /usr/local/bin/docker-compose
#RUN docker network create client
RUN apt-get install -y wget
RUN apt-get install -y build-essential
# install go
RUN wget https://dl.google.com/go/go1.13.linux-amd64.tar.gz && \
    tar -C /usr/local -xzf go1.13.linux-amd64.tar.gz
ENV PATH $PATH:/usr/local/go/bin
ENV GENERATOR_MODE compose
ENV GENERATOR_PATH /srv/topology
ENV PROJECT_SOURCE_PATH /tmp
ENV MONGO_HOST mongodb://localhost
ENV MONGO_DATABASE testdb
ENV API_HOST 0.0.0.0:8000
ADD . /topology-generator
WORKDIR /topology-generator
#ENTRYPOINT  ["go", "test", "-run TestAll"]
CMD mongod