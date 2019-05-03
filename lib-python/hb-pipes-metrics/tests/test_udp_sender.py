import unittest

import mock

from hb_pipes_metrics import udp_sender, connection_exception


class UdpSenderTests(unittest.TestCase):
    @mock.patch('socket.socket')
    def test_send(self, mock_socket):
        mock_socket.return_value.sendto.return_value = 100
        sender = udp_sender('127.0.0.1', '5555')
        result = sender.send('message')
        self.assertEqual(result, 100)

    def test_init(self):
        sender = udp_sender('127.0.0.1', 5555)
        self.assertTupleEqual(sender.s.getsockname(), ('0.0.0.0', 0))
        sender = udp_sender('127.0.0.1', '5555')
        self.assertTupleEqual(sender.s.getsockname(), ('0.0.0.0', 0))

    def test_init_exception(self):
        with self.assertRaises(connection_exception):
            udp_sender('127.0.0.1', 'port')
