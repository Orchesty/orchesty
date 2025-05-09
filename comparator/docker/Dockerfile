FROM node:slim

ENV CRYPT_SECRET=ThisIsNotSoSecret
ENV TENANT_ID=test-tenant

RUN npm i -g pnpm

RUN mkdir "/.local" && chmod -R 777 "/.local"