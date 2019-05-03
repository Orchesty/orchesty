# encoding: utf-8
import unittest

from hb_pipes_sdk.exceptions import HbPipesProcessException, HbPipesSdkException


class HbPipesSdkExceptionTest(unittest.TestCase):
    def test_default_exception_class(self):
        """ Test default exception class
        """
        message = {'bar': {'foo': 'doo'}}
        exception = HbPipesProcessException(message)

        self.assertEqual(exception.get_status(), 500)
        self.assertDictEqual(exception.to_dict(), {'message': message})
        self.assertIsInstance(exception, HbPipesSdkException)

    def test_exception_class(self):
        """ Test exception class with param
        """

        for message, status_code, payload, output in self.exception_provider():
            exception = HbPipesProcessException(message=message, status_code=status_code, payload=payload)

            self.assertEqual(exception.get_status(), status_code)
            self.assertDictEqual(exception.to_dict(), output)
            self.assertIsInstance(exception, HbPipesSdkException)

    @staticmethod
    def exception_provider():

        test_data = [
            {
                'message': 'error text',
                'status_code': 201,
                'payload': {},
                'output': {'message': 'error text'},
            },
            {
                'message': 'error text',
                'status_code': 204,
                'payload': {'error': ['message_one', 'message_two']},
                'output': {'error': ['message_one', 'message_two'], 'message': 'error text'},
            }
        ]
        for item in test_data:
            yield item['message'], item['status_code'], item['payload'], item['output']
