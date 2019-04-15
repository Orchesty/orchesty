from .errors import ConnectionException
from .metrics import Metrics
from .udp_sender import UdpSender

connection_exception = ConnectionException
metrics = Metrics
udp_sender = UdpSender

__version__ = '1.1'
__all__ = ['ConnectionException', 'metrics', 'udp_sender']
