import time

from psutil import long

from . import udp_sender


class Metrics:
    """Metrics class for sending measurements data
    Packet format
    python-service,name=ubuntu,host=ubuntu key=value 1502875884010000000
    """
    MEASUREMENT = 'python-service'

    def __init__(self, sender: udp_sender):
        """Metrics collector
        :param sender: UDP sender service
        """
        self.sender = sender

    def send(self, fields: dict, host: str = None) -> int:
        """Send UDP packet

        :param fields: dict with message fields
        :param host: sender host address
        :return: send message length
        """
        message = self._get_message(fields, host)
        return self.sender.send(message)

    def _get_message(self, fields: dict, host: str = None) -> bytes:
        """Prepare message for sending

        :rtype: object
        :param fields: dict with message fields
        :param host: sender host address
        :return: prepared message
        """
        micro_time = '{}000000'.format(long(time.time() * 1000))
        prefix = self._get_prefix(host)
        keys = ['{}={}'.format(key, self.prepare_value_format(value)) for key, value in fields.items()]

        if len(keys):
            message = '{0} {1} {2}'.format(prefix, ",".join(keys), micro_time)
        else:
            message = f'{prefix} {micro_time}'

        return message.encode()

    def _get_prefix(self, host=None):
        # type: (str) -> str
        """Get prefix for message, as prefix is used hostname

        :param host:
        :return:
        """
        if host is None:
            host = self.sender.get_hostname()

        return '{},name={},host={}'.format(self.MEASUREMENT, host, host)

    @staticmethod
    def prepare_value_format(value):
        """
        :type value: mixed
        """
        if type(value) == int:
            pass
        elif type(value) == bool:
            value = 'true' if value else 'false'
        else:
            value = '"{}"'.format(value)

        return value
