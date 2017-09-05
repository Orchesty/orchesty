FROM dkr.hanaboso.net/pipes/pipes/python-base:dev

WORKDIR /usr/src/app

RUN pip install Flask \
            Flask-JSON \
            lxml

EXPOSE 5000

COPY xml_parser/ /usr/src/app
COPY hb_metrics/ /usr/src/app/hb_metrics

CMD ["python2", "app.py"]
