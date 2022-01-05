### Build step
FROM node:16-slim as builder

RUN apt-get update && apt-get upgrade -y && apt-get install -y yarn g++ make python3
RUN apt-get autoclean -y && apt-get autoremove -y

# Pre-cache packages
WORKDIR /precache
COPY package.json ./
COPY yarn.lock ./
RUN yarn install --production

### Build step
FROM node:16-slim as pre-cache

RUN apt-get update && apt-get upgrade -y && apt-get install -y yarn
RUN apt-get autoclean -y && apt-get autoremove -y

# Build
WORKDIR /build
COPY ./ ./
COPY --from=builder /precache ./
RUN yarn build


### Packaging step
FROM nginx:alpine

COPY --from=pre-cache /build/dist /var/www/html

COPY nginx.conf /etc/nginx/nginx.conf

COPY entrypoint.sh /

ENTRYPOINT ["/entrypoint.sh"]

CMD nginx -g 'daemon off;'
