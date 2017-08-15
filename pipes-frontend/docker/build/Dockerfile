FROM node:8

RUN apt-get update && \
    apt-get upgrade -y --force-yes && \
    npm i -g npm

WORKDIR app

COPY package.json package.json