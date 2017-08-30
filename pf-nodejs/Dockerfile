FROM node:8

RUN echo Etc/UTC >/etc/timezone && \
    dpkg-reconfigure -f noninteractive tzdata

COPY . /usr/src/app

WORKDIR /usr/src/app

RUN npm install

CMD [ "npm", "start" ]
