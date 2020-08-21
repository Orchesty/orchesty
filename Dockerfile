FROM node:14-alpine
COPY . ./app
RUN cd /app && npm i && npm run build

FROM node:14-alpine
RUN apk update --no-cache && \
    apk upgrade --no-cache && \
    apk add \
    bash \
    bind-tools \
    procps
COPY --from=0 /app /srv/app
RUN chmod +x -R /srv/app/dist/src/bin

WORKDIR /srv/app

CMD [ "npm", "start" ]