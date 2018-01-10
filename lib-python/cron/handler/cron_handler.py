# encoding: utf-8
import logging

from errors.bad_body_parameters import BadBodyParameters
from handler.cron_handler_base import CronHandlerBase
from handler.response_handler import get_json_content
from model.request import Request

logger = logging.getLogger(__name__)


class CronHandler(CronHandlerBase):
    """
    
    """

    def create(self, request: Request):
        """
        
        :param request:
        :return:
        """
        body = request.get_body()

        try:
            hash_key, time, command = body['hash'], body['time'], body['command']
            logger.debug('create rules: {} {} {}'.format(hash_key, time, command))

            if self.valid_time(time):
                self.db.add(hash_key, time, command)
            else:
                message = 'Invalid time format {}'.format(time)
                logger.warning(message)
                raise BadBodyParameters(message, 400)
        except KeyError as e:
            message = 'Unknown body format key: {}'.format(e)
            logger.error(message)
            raise BadBodyParameters(message, 400)

        return get_json_content(200, "")

    def update(self, hash_key: str, request: Request):
        """
        
        :param hash_key:
        :param request:
        :return:
        """
        body = request.get_body()

        try:
            time, command = body['time'], body['command']
            logger.debug('update rules: {} {} {}'.format(hash_key, time, command))

            if self.valid_time(time):
                self.db.update(hash_key, time, command)
            else:
                message = 'Invalid time format {}'.format(time)
                logger.warning(message)
                raise BadBodyParameters(message, 400)
        except KeyError as e:
            message = 'Unknown body format key: {}'.format(e)
            logger.error(message)
            raise BadBodyParameters(message, 400)

        return get_json_content(200, "")

    def patch(self, hash_key: str, request: Request):
        """

        :param hash_key:
        :param request:
        :return:
        """
        body = request.get_body()

        try:
            time, command = body['time'], body['command']
            logger.debug('update rules: {} {} {}'.format(hash_key, time, command))

            if self.valid_time(time):
                self.db.patch(hash_key, time, command)
            else:
                message = 'Invalid time format {}'.format(time)
                logger.warning(message)
                raise BadBodyParameters(message, 400)
        except KeyError as e:
            message = 'Unknown body format key: {}'.format(e)
            logger.error(message)
            raise BadBodyParameters(message, 400)

        return get_json_content(200, "")

    def delete(self, hash_key: str):
        """
        :param hash_key:
        :return:
        """
        self.db.remove(hash_key)
        logger.debug('remove hash: {} '.format(hash_key))

        return get_json_content(200, "")
