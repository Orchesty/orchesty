import socket

from . import ConnectionException


class UdpSender:
    """Class for sending UDP measurements packets
    """
    port: int
    hostname: str
    s: socket.socket
    host: str

    def __init__(self, host: str, port: int):
        """Parameters for creating socket connection

        :param host:
        :param port:
        :raise errors.ConnectionError
        """
        try:
            self.port = int(port)
        except (ValueError, AttributeError) as e:
            raise ConnectionException(e)

        self.host = str(host)
        self.s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self.hostname = socket.gethostname()

    def connect(self):
        """Make socket connection
        """
        self.s.connect((self.host, self.port))

    def send(self, message: bytes) -> int:
        """Send packet to hb_metrics collector

        :param message:
        :return: Count send characters
        """
        try:
            res = self.s.sendto(message, (self.host, self.port))
            return res
        except (socket.gaierror, TypeError):
            return 0

    def get_hostname(self) -> str:
        """Get current server hostname

        :return: hostname of current host
        """
        return self.hostname

    def __del__(self):
        if hasattr(self, 's') and self.s:
            self.s.close()
