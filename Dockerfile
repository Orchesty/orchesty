### Build step
FROM node:24-slim AS builder

RUN npm i -g pnpm

RUN mkdir "/.local" && chmod -R 777 "/.local"

# Build
WORKDIR /build
COPY ./ ./
RUN pnpm install
RUN cd app-ui && npm run build

### Packaging step
FROM nginx:alpine

COPY --from=builder /build/app-ui/dist /var/www/html

COPY app-ui/nginx.conf /etc/nginx/nginx.conf

COPY app-ui/entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

CMD ["nginx", "-g", "daemon off;"]
