FROM node:16-slim

RUN apt-get update && apt-get upgrade -y
RUN apt-get autoclean -y && apt-get autoremove -y

RUN mkdir -p /.yarn && chmod -R 777 /.yarn
RUN mkdir -p /.cache/yarn && chmod -R 777 /.cache/yarn
