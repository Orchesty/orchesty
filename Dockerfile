### Build step
FROM node:16-slim as builder

# Pre-cache packages
WORKDIR /precache
COPY package.json ./
COPY yarn.lock ./
RUN yarn install

### Build step
FROM node:16-slim as pre-cache

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

