import unittest
import mock

import errors
from service.udp_sender import UdpSender


class UdpSenderTests(unittest.TestCase):
    @mock.patch('socket.socket')
    def test_send(self, mock_socket):
        mock_socket.return_value.sendto.return_value = 100
        sender = UdpSender('127.0.0.1', '5555')
        result = sender.send('message')
        self.assertEquals(result, 100)

    def test_init(self):
        sender = UdpSender('127.0.0.1', 5555)
        self.assertTupleEqual(sender.s.getsockname(), ('0.0.0.0', 0))
        sender = UdpSender('127.0.0.1', '5555')
        self.assertTupleEqual(sender.s.getsockname(), ('0.0.0.0', 0))

    def test_init_exception(self):
        with self.assertRaises(errors.ConnectionError):
            UdpSender('127.0.0.1', 'port')
