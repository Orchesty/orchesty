#!/usr/bin/env python
# encoding: utf-8
import json
import logging
import os
import sys

import connection
from __init__ import __collection__ as collection
from __init__ import __database__ as database
from __init__ import __version__
from errors.batch_exception import BatchException
from errors.invalid_request import InvalidRequest
from errors.mongo_exception import MongoException
from handler.cron_batch_handler import CronBatchHandler
from handler.cron_handler import CronHandler
from handler.response_handler import get_json_content
from model.cron_service import CronService
from model.db import Db
from model.request import Request

os.environ.setdefault('CRON_PERIOD', '60000')
os.environ.setdefault('CRON_NAME', 'cron_service')
os.environ.setdefault('CRON_HOST', '0.0.0.0')
os.environ.setdefault('CRON_PORT', '5000')
os.environ.setdefault('CRON_MONGODB_HOST', 'mongo')
os.environ.setdefault('CRON_MONGODB_PORT', '27017')

try:
    import flask
    import flask_json
except ImportError as e:
    sys.stderr.write("Import error [{}]".format(e.output))
    sys.exit(0)

app = flask.Flask(__name__)
flask_json.FlaskJSON(app)


@app.route("/cron-api/create", methods=['POST'])
def create_cron():
    request = Request(flask.request)
    return cron_handler.create(request)


@app.route("/cron-api/update/<hash_key>", methods=['POST'])
def update_cron(hash_key):
    request = Request(flask.request)
    return cron_handler.update(hash_key, request)


@app.route("/cron-api/patch/<hash_key>", methods=['POST'])
def patch_cron(hash_key):
    request = Request(flask.request)
    return cron_handler.patch(hash_key, request)


@app.route("/cron-api/delete/<hash_key>", methods=['POST'])
def delete_cron(hash_key):
    return cron_handler.delete(hash_key)


@app.route("/cron-api/batch_create", methods=['POST'])
def batch_create_cron():
    request = Request(flask.request)
    return cron_batch_handler.batch_create(request)


@app.route("/cron-api/batch_update", methods=['POST'])
def batch_update_cron():
    request = Request(flask.request)
    return cron_batch_handler.batch_update(request)


@app.route("/cron-api/batch_patch", methods=['POST'])
def batch_patch_cron():
    request = Request(flask.request)
    return cron_batch_handler.batch_patch(request)


@app.route("/cron-api/batch_delete", methods=['POST'])
def batch_delete_cron():
    request = Request(flask.request)
    return cron_batch_handler.batch_delete(request)


@app.route("/cron-api/clear", methods=['GET', 'POST'])
def clear_cron():
    return cron_handler.clear()


@app.route("/cron-api/get_all", methods=['GET'])
def get_all_cron():
    return cron_handler.get_all()


@app.errorhandler(404)
def page_not_found(error):
    return get_json_content(404, json.dumps({"message": str(error)}))


@app.errorhandler(InvalidRequest)
def handle_bad_request(error):
    response = flask.jsonify(error.to_dict())
    response.status_code = error.status_code
    return response


@app.errorhandler(BatchException)
def handle_bad_batch(error):
    response = flask.jsonify(error.to_dict())
    response.status_code = error.status_code
    return response


@app.errorhandler(MongoException)
def handle_mongodb_exception(error):
    response = flask.jsonify(error.to_dict())
    response.status_code = error.status_code
    return response


@app.errorhandler(TypeError)
def handle_type_error_exception(error):
    response = flask.jsonify({"message": "GLOBAL EXCEPTION => " + str(error)})
    response.status_code = 400
    return response


def get_connection():
    try:
        db = Db(connection.Connection(
            database,
            os.environ.get('CRON_MONGODB_HOST'),
            os.environ.get('CRON_MONGODB_PORT')
        ), collection)
        
        return db
    except MongoException as e:
        print('Error({}) during db create'.format(str(e.message)))
        sys.exit(1)


if __name__ == '__main__':
    logger = logging.getLogger(__name__)
    logger.info('Start application {}'.format(__version__))
    
    db = get_connection()
    cron_handler = CronHandler(db)
    cron_batch_handler = CronBatchHandler(db)
    
    service = CronService(get_connection(), os.environ.get('CRON_PERIOD'))
    service.start()
    
    app.run(
        host=os.environ.get('CRON_HOST'),
        port=int(os.environ.get('CRON_PORT')),
        use_reloader=0,
        debug=True
    )
    service.stop()
    service.join()
