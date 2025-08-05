FROM node:24-slim

RUN apt update && apt install -y \
    python3 \
    make \
    g++ \
 && rm -rf /var/lib/apt/lists/*

RUN npm i -g pnpm

RUN mkdir "/.local" && chmod -R 777 "/.local"
