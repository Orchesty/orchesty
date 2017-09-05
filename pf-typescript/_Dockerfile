FROM node:8

RUN echo Etc/UTC >/etc/timezone && \
    dpkg-reconfigure -f noninteractive tzdata

COPY . /usr/app

WORKDIR /usr/app

RUN npm install

RUN ./node_modules/.bin/tsc

RUN chmod +x /usr/app/dist/src/bin/pipes.js

CMD [ "npm", "start" ]
