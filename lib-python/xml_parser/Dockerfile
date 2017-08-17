FROM python:2-alpine

COPY . /usr/src/app

WORKDIR /usr/src/app

RUN apk add --update --no-cache \
        g++ \
        gcc \
        libxslt-dev \
        rm  -rf /tmp/* /var/cache/apk/*

RUN pip install Flask \
            Flask-JSON \
            pyyaml \
            mock \
            lxml

EXPOSE 5000
CMD [ "python2", "app.py"]
