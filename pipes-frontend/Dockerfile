FROM node:7.9.0-alpine

ADD . /app

WORKDIR /app

RUN npm install

ENV NODE_ENV=development

EXPOSE 8080

CMD ["npm", "start"]