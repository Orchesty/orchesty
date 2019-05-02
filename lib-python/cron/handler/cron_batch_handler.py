# encoding: utf-8
import logging

from flask import Response

from errors.bad_body_parameters import BadBodyParameters
from errors.batch_exception import BatchException
from errors.record_exist import RecordExist
from errors.record_not_found import RecordNotFound
from handler.cron_handler_base import CronHandlerBase
from handler.response_handler import get_json_content
from model.request import Request

logger = logging.getLogger(__name__)


class CronBatchHandler(CronHandlerBase):
    """
    
    """

    def batch_create(self, request) -> Response:
        """
        
        :param request:
        :return:
        """
        body = request.get_body()

        result = []
        for item in body:
            try:
                topology, node, time, command = item['topology'], item['node'], item['time'], item['command']
                if self.valid_time(time):
                    try:
                        self.db.add(topology, node, time, command)
                    except RecordExist as e:
                        logger.info('batch create {} {} {}'.format(topology, node, e.message))
                        result.append({'topology': topology, 'node': node, 'message': e.message})
                else:
                    result.append({
                        'topology': topology,
                        'node': node,
                        'message': 'Invalid time format {}'.format(time)
                    })
            except KeyError as e:
                message = 'Item key {} missing'.format(str(e))
                logger.info(message)
                result.append({
                    'row': repr(item),
                    'message': message
                })
            except TypeError as e:
                logger.error(str(e))
                raise BadBodyParameters(str(e), 400)

        if len(result):
            raise BatchException(result, 400)

        return get_json_content(200, "")

    def batch_update(self, request: Request) -> Response:
        """
        
        :param request:
        :return:
        """
        body = request.get_body()

        result = []

        for item in body:
            try:
                topology, node, time, command = item['topology'], item['node'], item['time'], item['command']
                if self.valid_time(time):
                    try:
                        self.db.update(topology, node, time, command)
                    except RecordNotFound as e:
                        logger.info('batch update {} {} {}'.format(topology, node, e.message))
                        result.append({'topology': topology, 'node': node, 'message': e.message})
                else:
                    result.append({
                        'topology': topology,
                        'node': node,
                        'message': 'Invalid time format {}'.format(time)
                    })
            except KeyError as e:
                message = 'Item key {} missing'.format(str(e))
                logger.error(message)
                result.append({
                    'row': repr(item),
                    'message': message
                })
            except TypeError as e:
                logger.error(str(e))
                raise BadBodyParameters(str(e), 400)

        if len(result):
            raise BatchException(result, 400)

        return get_json_content(200, "")

    def batch_patch(self, request: Request) -> Response:
        """

        :param request:
        :return:
        """
        body = request.get_body()

        result = []

        for item in body:
            try:
                topology, node, time, command = item['topology'], item['node'], item['time'], item['command']
                if self.valid_time(time):
                    try:
                        self.db.update(topology, node, time, command)
                    except RecordNotFound as e:
                        logger.info('batch update {} {} {} try to insert'.format(topology, node, e.message))
                        try:
                            self.db.add(topology, node, time, command)
                        except RecordExist as e:
                            logger.info('batch patch {} {} {}'.format(topology, node, e.message))
                            result.append({
                                'topology': topology,
                                'node': node,
                                'message': 'Unknown problem {}'.format(str(e))
                            })
                else:
                    result.append({
                        'topology': topology,
                        'node': node,
                        'message': 'Invalid time format {}'.format(time)
                    })
            except KeyError as e:
                if 'topology' in item and 'node' in item and len(item) == 1:
                    try:
                        self.db.remove(item['topology'], item['node'])
                    except RecordNotFound as e:
                        logger.info('batch patch => delete {} {} {}'.format(item['topology'], item['node'], e.message))
                        # result.append({'topology': item['topology'], 'node': item['node'], 'message': e.message})
                else:
                    message = 'Item key {} missing'.format(str(e))
                    logger.error(message)
                    result.append({
                        'row': repr(item),
                        'message': message
                    })
            except TypeError as e:
                logger.error(str(e))
                raise BadBodyParameters(str(e), 400)

        if len(result):
            raise BatchException(result, 400)

        return get_json_content(200, "")

    def batch_delete(self, request: Request) -> Response:
        """
        
        :param request:
        :return:
        """
        body = request.get_body()

        result = []

        for item in body:
            try:
                try:
                    self.db.remove(item['topology'], item['node'])
                except RecordNotFound as e:
                    logger.info('batch delete {} {} {}'.format(item['topology'], item['node'], e.message))
                    result.append({'topology': item['topology'], 'node': item['node'], 'message': e.message})
            except KeyError as e:
                message = 'Item key {} missing'.format(str(e))
                logger.error(message)
                result.append({
                    'row': repr(item),
                    'message': message
                })

        if len(result):
            raise BatchException(result, 400)

        return get_json_content(200, "")
