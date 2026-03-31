### Build step
FROM node:24-slim AS builder

RUN npm i -g pnpm

RUN mkdir "/.local" && chmod -R 777 "/.local"

# Build
WORKDIR /build
COPY ./ ./
RUN pnpm install
RUN cd app-ui-new-enterprise && npm run build

### Packaging step
FROM nginx:alpine

COPY --from=builder /build/app-ui-new-enterprise/dist /var/www/html

COPY app-ui-new-enterprise/nginx.conf /etc/nginx/nginx.conf

CMD ["nginx", "-g", "daemon off;"]
