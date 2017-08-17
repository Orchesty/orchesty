import socket

import errors


class UdpSender:
    """Class for sending UDP measurements packets

    """

    def __init__(self, host, port):
        # type: (str, int) -> None
        """Parameters for creating socket connection

        :param host:
        :param port:
        :raise errors.ConnectionError
        """
        try:
            self.port = int(port)  # type: int
        except (ValueError, AttributeError) as e:
            raise errors.ConnectionError(e.message)

        self.host = str(host)  # type: str
        self.s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)  # type: socket.socket
        self.hostname = socket.gethostname()  # type: str

    def connect(self):
        """Make socket connection
        """
        self.s.connect((self.host, self.port))

    def send(self, message):
        # type: (str) -> int|None
        """Send packet to hb_metrics collector

        :param message:
        :return: Count send characters
        """
        try:
            res = self.s.sendto(message, (self.host, self.port))
            return res
        except (socket.gaierror, TypeError):
            return None

    def get_hostname(self):
        # type: () -> str
        """Get curent server hostname

        :return: hostname of current host
        """
        return self.hostname

    def __del__(self):
        if hasattr(self, 's') and self.s:
            self.s.close()
