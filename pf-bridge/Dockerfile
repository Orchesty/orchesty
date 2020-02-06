FROM node:10-alpine

RUN apk update --no-cache && apk upgrade --no-cache && apk add --no-cache nano netcat-openbsd vim

COPY . /srv/app

WORKDIR /srv/app

CMD [ "npm", "start" ]
