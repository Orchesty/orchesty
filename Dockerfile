FROM node:8

COPY . /srv/app

WORKDIR /srv/app

CMD [ "npm", "start" ]
