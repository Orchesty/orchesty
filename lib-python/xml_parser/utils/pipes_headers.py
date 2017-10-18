# encoding: utf-8

from werkzeug.datastructures import EnvironHeaders


class PipesHeaders:
    # Prefix
    PF_PREFIX = 'pf-'

    # Framework headers
    CORRELATION_ID = 'correlation-id'
    PROCESS_ID = 'process-id'
    PARENT_ID = 'parent-id'
    SEQUENCE_ID = 'sequence-id'
    NODE_ID = 'node-id'
    NODE_NAME = 'node-name'
    TOPOLOGY_ID = 'topology-id'
    TOPOLOGY_NAME = 'topology-name'
    RESULT_CODE = 'result-code'
    RESULT_STATUS = 'result-status'
    RESULT_MESSAGE = 'result-message'
    RESULT_DETAIL = 'result-detail'

    # White list for headers
    WHITE_LIST = ['content-type']

    @staticmethod
    def clear(headers):
        # type: (EnvironHeaders) -> dict
        """
        :param headers: EnvironHeaders
        :return: dict
        """
        clear_headers = {}
        for (key, value) in headers:
            if key[0:3].lower() == PipesHeaders.PF_PREFIX or key.lower() in PipesHeaders.WHITE_LIST:
                clear_headers[key.lower()] = value

        return clear_headers

    @staticmethod
    def create_key(key):
        # type: (str) -> str
        """
        :param key: str
        :return: str
        """

        return PipesHeaders.PF_PREFIX + key

    @staticmethod
    def get(key, headers):
        # type: (str, EnvironHeaders) -> str | None
        """
        :param key: str
        :param headers: dict
        :return: str | None
        """

        return headers.get(PipesHeaders.create_key(key), None)

    @staticmethod
    def debug_info(headers):
        # type: (EnvironHeaders) -> dict
        """
        :param headers: EnvironHeaders
        :return: dict
        """

        clear_headers = PipesHeaders.clear(headers)
        for key in clear_headers.copy():
            if key not in [PipesHeaders.create_key(PipesHeaders.CORRELATION_ID),
                           PipesHeaders.create_key(PipesHeaders.NODE_ID)]:
                clear_headers.pop(key)

        return clear_headers
