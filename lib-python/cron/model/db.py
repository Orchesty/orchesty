# encoding: utf-8
import logging

from connection import Connection
from errors.record_exist import RecordExist
from errors.record_not_found import RecordNotFound

logger = logging.getLogger(__name__)


class Db:
    """
    interface for db manipulation
    """
    
    def __init__(self, conn: Connection, collection: str):
        """
        
        :param conn: connection class
        :type conn: Connection
        """
        self.collection = collection
        self.conn = conn
    
    def add(self, topology: str, node: str, time: str, command: str):
        """
        :param topology: raw ident
        :type topology: str
        :param node: raw ident
        :type node: str
        :param time: action time
        :type time: str
        :param command: action to execute
        :type command: str
        """
        if self.conn.select(self.collection, {'command': command}):
            message = 'Record "{} {}" has not unique command: {}'.format(topology, node, command)
            logger.info(message)
            raise RecordExist(message)
        
        elif self.conn.select(self.collection, {'topology': topology, 'node': node}):
            message = 'Record "{} {}" exist'.format(topology, node)
            logger.info(message)
            raise RecordExist(message)
        else:
            return self.conn.insert(self.collection, {'topology': topology, 'node': node, 'time': time, 'command': command})
    
    def remove(self, topology: str, node: str):
        """
        
        :param topology:
        :type topology: str
        :param node:
        :type node: str
        :return:
        """
        result = self.conn.delete(self.collection, {'topology': topology, 'node': node})
        if not result:
            message = 'Record "{} {}" not found'.format(topology, node)
            logger.info(message)
            raise RecordNotFound(message)
    
    def remove_all(self) -> int:
        """

        :rtype: inbt
        :return:
        """
        return self.conn.remove(self.collection)
    
    def update(self, topology: str, node: str, time: str, command: str):
        """
        
        :param topology:
        :type topology: str
        :param node:
        :type node: str
        :param time:
        :type time: str
        :param command:
        :type command: str
        :return:
        """
        result = self.conn.update(self.collection, {'topology': topology, 'node': node}, {'time': time, 'command': command})
        if not result:
            message = 'Record "{} {}" not found'.format(topology, node)
            logger.info(message)
            raise RecordNotFound(message)
        
        return result
    
    def patch(self, topology: str, node: str, time: str, command: str):
        """

        :param topology:
        :type topology: str
        :param node:
        :type node: str
        :param time:
        :type time: str
        :param command:
        :type command: str
        :return:
        """
        result = self.conn.update(self.collection, {'topology': topology, 'node': node}, {'time': time, 'command': command})
        if not result:
            result = self.conn.insert(self.collection, {'topology': topology, 'node': node, 'time': time, 'command': command})
        
        return result
    
    def get_all(self):
        """
        
        :return:
        :rtype list
        """
        all_records = self.conn.select(self.collection)
        return all_records if all_records is not None else []

    def get_all_cron(self):
        """

        :return:
        :rtype list
        """
        crons = []

        for row in self.get_all():
            crons.append({'topology': row['topology'], 'node': row['node'], 'time': row['time']})

        return crons
