import unittest

import mock

from metrics import Metrics


class MetricsTests(unittest.TestCase):
    @mock.patch('service.udp_sender.UdpSender')
    def setUp(self, mock_sender):
        mock_sender.get_hostname.return_value = 'foo'
        mock_sender.send.return_value = 100
        self.metrics = Metrics(mock_sender)
    
    def test_send(self):
        self.assertEquals(self.metrics.send({}), 100)
    
    @mock.patch('time.time')
    def test_get_message(self, mock_time):
        mock_time.return_value = 1502891211.420394
        for fields, host, result in self.provider_get_message():
            self.assertEquals(self.metrics._get_message(fields, host), result)
    
    def test_get_prefix(self):
        for host, res in self.provider_get_prefix():
            self.assertEquals(res, self.metrics._get_prefix(host))
    
    @staticmethod
    def provider_get_prefix():
        data = (
            ('boo', 'python-service,name=boo,host=boo'),
            ('localhost', 'python-service,name=localhost,host=localhost'),
            (None, 'python-service,name=foo,host=foo'),
        )
        
        for host, result in data:
            yield host, result
    
    @staticmethod
    def provider_get_message():
        data = (
            (
                {},
                'server',
                'python-service,name=server,host=server 1502891211420000000'
            ),
            (
                {'boolean': True, 'string': 'text', 'integer': 12, 'bool': False},
                'localhost',
                'python-service,name=localhost,host=localhost integer=12,boolean=true,bool=false,string="text" 1502891211420000000'
            ),
            (
                {'key': 'value', 'number': 0, 'string': '1234'},
                'localhost',
                'python-service,name=localhost,host=localhost number=0,key="value",string="1234" 1502891211420000000'
            ),
            (
                {'key': 'value', 'key2': 'value2'},
                None,
                'python-service,name=foo,host=foo key2="value2",key="value" 1502891211420000000'
            ),
        )
        
        for fields, host, result in data:
            yield fields, host, result
