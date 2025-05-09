### Build step
FROM node:20-slim AS builder

RUN npm i -g pnpm

# Pre-cache packages
WORKDIR /precache
COPY package.json ./
COPY pnpm-lock.yaml ./
RUN pnpm install

### Build step
FROM node:20-slim AS pre-cache

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

ENV NODE_ENV=prod

ENTRYPOINT ["/entrypoint.sh"]

CMD ["nginx", "-g", "daemon off;"]
