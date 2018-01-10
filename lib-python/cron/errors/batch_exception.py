# encoding: utf-8


class BatchException(Exception):
    status_code = 400
    
    def __init__(self, message: dict, status_code: int = None, payload: str = None):
        # type: (str, int, str) -> object
        Exception.__init__(self)
        self.message = message
        if status_code is not None:
            self.status_code = status_code
        self.payload = payload
    
    def to_dict(self):
        # type: () -> dict
        return self.message
