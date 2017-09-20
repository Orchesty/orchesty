FROM debian:stretch

# TODO: better cleanup
RUN apt-get update && \
    apt-get install -y --force-yes nginx-extras && \
    apt-get clean

COPY nginx.conf /etc/nginx/

WORKDIR /var/www/html
COPY dist/ frontend/

CMD nginx -g 'daemon off;'
