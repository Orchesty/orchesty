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
    
    def add(self, hash_key: str, time: str, command: str):
        """
        :param hash_key: raw ident
        :type hash_key: str
        :param time: action time
        :type time: str
        :param command: action to execute
        :type command: str
        """
        if self.conn.select(self.collection, {'hash': hash_key}):
            message = 'Record with hash key: "{}" exist'.format(hash_key)
            logger.info(message)
            raise RecordExist(message)
        else:
            return self.conn.insert(self.collection, {'hash': hash_key, 'time': time, 'command': command})
    
    def remove(self, hash_key: str):
        """
        
        :param hash_key:
        :type hash_key: str
        :return:
        """
        result = self.conn.delete(self.collection, {'hash': hash_key})
        if not result:
            message = 'Record "{}" not found'.format(hash_key)
            logger.info(message)
            raise RecordNotFound(message)

    def update(self, hash_key: str, time: str, command: str):
        """
        
        :param hash_key:
        :type hash_key: str
        :param time:
        :type time: str
        :param command:
        :type command: str
        :return:
        """
        result = self.conn.update(self.collection, {'hash': hash_key}, {'time': time, 'command': command})
        if not result:
            message = 'Record "{}" not found'.format(hash_key)
            logger.info(message)
            raise RecordNotFound(message)
        
        return result
    
    def patch(self, hash_key: str, time: str, command: str):
        """

        :param hash_key:
        :type hash_key: str
        :param time:
        :type time: str
        :param command:
        :type command: str
        :return:
        """
        result = self.conn.update(self.collection, {'hash': hash_key}, {'time': time, 'command': command})
        if not result:
            result = self.conn.insert(self.collection, {'hash': hash_key, 'time': time, 'command': command})

        return result

    def get_all(self):
        """
        
        :return:
        :rtype list
        """
        all_records = self.conn.select(self.collection)
        return all_records if all_records is not None else []
