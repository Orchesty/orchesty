# encoding: utf-8


class BadRequest(Exception):
    status_code = 400

    def __init__(self, message, status_code=None, payload=None):
        # type: (str, int, str) -> object
        Exception.__init__(self)
        self.message = message
        if status_code is not None:
            self.status_code = status_code
        self.payload = payload

    def to_dict(self):
        # type: () -> dict
        rv = dict(self.payload or ())
        rv['message'] = self.message
        return rv
