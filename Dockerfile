FROM node:8

RUN apt-get update && apt-get install -y netcat vim nano

COPY . /srv/app

WORKDIR /srv/app

CMD [ "npm", "start" ]
