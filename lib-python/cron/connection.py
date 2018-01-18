# encoding: utf-8
from typing import Union

from pymongo import MongoClient
from pymongo.cursor import Cursor
from pymongo.errors import ServerSelectionTimeoutError

from errors.mongo_exception import MongoException


class Connection:
    """
    Create conenction to mongodb
    """
    
    def __init__(self, database: str, host: str, port: int, timeout: int = 2000):
        """
        
        :param database: collection name
        :type database: str
        :param host: mongo host
        :type host: str
        :param port: mongo port
        :type port: int
        :param timeout: mongo connection timeout
        :type timeout: int
        """
        self.host = host
        try:
            self.port = int(port)
        except ValueError as e:
            raise MongoException(str(e), 500)
        
        self.database = database
        self.client = MongoClient(
            host,
            int(port),
            connectTimeoutMS=timeout,
            socketTimeoutMS=timeout,
            serverSelectionTimeoutMS=timeout
        )
        self.db = self.client[database]
    
    def insert(self, collection: str, data: dict) -> int:
        """

        :param collection: collection name
        :type collection: str
        :param data: data to insert
        :type data: dict
        :type: int
        :rtype: int
        """
        try:
            coll = self.db[collection]
            result = coll.insert_one(data)
        except ServerSelectionTimeoutError as e:
            raise MongoException(str(e), 500)
        
        return result.inserted_id
    
    def update(self, collection: str, where: dict, data: dict, upsert: bool = False) -> int:
        """
        
        :param collection: collection name
        :type collection: str
        :param where: condition
        :type where: dict
        :param data: data for update
        :type data: dict
        :param upsert: whether insert when doesn't exist
        :type upsert: bool
        :return:
        """
        try:
            result = self.db[collection].update_one(
                where,
                {"$set": data},
                upsert=upsert
            )
        except ServerSelectionTimeoutError as e:
            raise MongoException(str(e), 500)
        
        return result.matched_count
    
    def delete(self, collection: str, where: dict) -> int:
        """
        
        :param collection: collection name
        :type collection: str
        :param where: condition
        :type where: dict
        :return:
        :rtype: int
        """
        try:
            result = self.db[collection].delete_one(where)
        except ServerSelectionTimeoutError as e:
            raise MongoException(str(e), 500)
        
        return result.deleted_count
    
    def select(self, collection: str, where: dict = {}) -> Union[Cursor, None]:
        """
        
        :param collection: collection name
        :type collection: str
        :param where: condition
        :type where: dict
        :return:
        """
        try:
            result = self.db[collection].find(where)
            if result.count():
                return result
            else:
                return None
        
        except ServerSelectionTimeoutError:
            return None

    def remove(self, collection: str) -> int:
        """

        :param collection: collection name
        :type collection: str
        :return:
        :rtype: int
        """
        try:
            result = self.db[collection].remove()
        except ServerSelectionTimeoutError as e:
            raise MongoException(str(e), 500)

        return result['n']
