# encoding: utf-8
import json
import logging

from errors.bad_body_parameters import BadBodyParameters
from errors.record_not_found import RecordNotFound
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
            topology, node, time, command = body['topology'], body['node'], body['time'], body['command']
            logger.debug('create rules: {} {} {} {}'.format(topology, node, time, command))
            
            if self.valid_time(time):
                self.db.add(topology, node, time, command)
            else:
                message = 'Invalid time format {}'.format(time)
                logger.warning(message)
                raise BadBodyParameters(message, 400)
        except KeyError as e:
            message = 'Unknown body format key: {}'.format(e)
            logger.error(message)
            raise BadBodyParameters(message, 400)
        
        return get_json_content(200, "")
    
    def update(self, topology: str, node: str, request: Request):
        """
        
        :param topology:
        :param node:
        :param request:
        :return:
        """
        body = request.get_body()
        
        try:
            time, command = body['time'], body['command']
            logger.debug('update rules: {} {} {} {}'.format(topology, node, time, command))
            
            if self.valid_time(time):
                self.db.update(topology, node, time, command)
            else:
                message = 'Invalid time format {}'.format(time)
                logger.warning(message)
                raise BadBodyParameters(message, 400)
        except KeyError as e:
            message = 'Unknown body format key: {}'.format(e)
            logger.error(message)
            raise BadBodyParameters(message, 400)
        
        return get_json_content(200, "")
    
    def patch(self, topology: str, node: str, request: Request):
        """

        :param topology:
        :param node:
        :param request:
        :return:
        """
        body = request.get_body()
        try:
            time, command = body['time'], body['command']
            logger.debug('update rules: {} {} {} {}'.format(topology, node, time, command))
            
            if self.valid_time(time):
                self.db.patch(topology, node, time, command)
            else:
                message = 'Invalid time format {}'.format(time)
                logger.warning(message)
                raise BadBodyParameters(message, 400)
        except KeyError as e:
            if len(body) == 0:
                try:
                    self.db.remove(topology, node)
                    logger.debug('remove: {} {} '.format(topology, node))
                except RecordNotFound as e:
                    pass
            else:
                message = 'Unknown body format key: {}'.format(e)
                logger.error(message)
                raise BadBodyParameters(message, 400)
        
        return get_json_content(200, "")
    
    def delete(self, topology: str, node: str):
        """
        :param topology:
        :param node:
        :return:
        """
        self.db.remove(topology, node)
        logger.debug('remove: {} {} '.format(topology, node))
        
        return get_json_content(200, "")
    
    def clear(self):
        result = self.db.remove_all()
        logger.debug('remove all')
        
        return get_json_content(200, json.dumps({"message": "Purged {} entries".format(result)}))

    def get_all(self):
        result = self.db.get_all_cron()
        logger.debug('get all')

        return get_json_content(200, json.dumps(result))
