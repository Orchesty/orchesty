# encoding: utf-8
import flask


def get_json_content(status, body):
    response = flask.Response(status=status, content_type='application/json')
    response.set_data(str(body))

    return response
