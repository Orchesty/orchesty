FROM node:10-alpine

RUN apk update --no-cache && apk upgrade --no-cache && apk add --no-cache curl nano vim

COPY . /srv/app

WORKDIR /srv/app

CMD [ "npm", "start" ]
