# encoding: utf-8
from abc import ABC


class HbPipesSdkException(Exception, ABC):
    def __init__(self, message: str, status_code: int = 500, payload: dict = None):
        """
        Init exception
        :param message: str
        :param status_code: int
        :param payload: dict
        """
        Exception.__init__(self)
        self.message = message
        self.status_code = status_code
        self.payload = payload

    def to_dict(self) -> dict:
        """
        Prepare format for output handler
        :return: dict
        """
        rv = dict(self.payload or ())
        rv['message'] = self.message
        return rv

    def get_status(self) -> int:
        """
        Get http response status
        :return: int
        """
        return self.status_code


class HbPipesProcessException(HbPipesSdkException):
    pass
