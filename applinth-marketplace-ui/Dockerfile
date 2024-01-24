### Build step
FROM node:18-slim as builder

RUN npm i -g pnpm

# Pre-cache packages
WORKDIR /precache
COPY package.json ./
COPY pnpm-lock.yaml ./
RUN pnpm install

### Build step
FROM node:18-slim as pre-cache

# Build
WORKDIR /build
COPY ./ ./
COPY --from=builder /precache ./
RUN npm run build


### Packaging step
FROM nginx:alpine

RUN apk --no-cache add lz4

COPY --from=pre-cache /build/dist /var/www/html

COPY nginx.conf /etc/nginx/nginx.conf

COPY entrypoint.sh /

RUN rm -rf /var/www/html/mockServiceWorker.js

ENTRYPOINT ["/entrypoint.sh"]

CMD nginx -g 'daemon off;'
