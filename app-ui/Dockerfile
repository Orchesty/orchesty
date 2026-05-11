### Build step
FROM node:24-slim AS builder

RUN apt update && apt install -y \
    python3 \
    make \
    g++ \
 && rm -rf /var/lib/apt/lists/*

RUN npm i -g pnpm

RUN mkdir "/.local" && chmod -R 777 "/.local"

# Pre-cache packages
WORKDIR /precache
COPY package.json ./
COPY pnpm-lock.yaml ./
COPY pnpm-workspace.yaml ./
RUN pnpm install

### Build step
FROM node:24-slim AS pre-cache

# Build
WORKDIR /build
COPY ./ ./
COPY --from=builder /precache ./
RUN npm run build


### Packaging step
FROM nginx:alpine

COPY --from=pre-cache /build/dist /var/www/html

COPY nginx.conf /etc/nginx/nginx.conf

COPY entrypoint.sh /

ENTRYPOINT ["/entrypoint.sh"]

CMD nginx -g 'daemon off;'
