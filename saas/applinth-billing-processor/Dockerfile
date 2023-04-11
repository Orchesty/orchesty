FROM node:19-alpine as build

WORKDIR /build
COPY package.json pnpm-lock.yaml ./
RUN pnpm install
COPY . .
RUN pnpm build


FROM node:19-alpine as final

WORKDIR /app

COPY --from=build /build/dist ./dist
COPY --from=build /build/package.json /build/pnpm-lock.yaml ./
RUN pnpm install --production --frozen-lockfile

ENTRYPOINT ["node", "dist/main.js"]
