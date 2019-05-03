# encoding: utf-8
import unittest

import flask
from flask import json

from hb_pipes_sdk.output_render import HbPipesOutputRenderer, OutputRenderer


class HbPipesOutputRendererTest(unittest.TestCase):
    """Test Output renderer
    """
    def test_is_implement_output_renderer(self):
        """Test whether implement class OutputRenderer
        """
        renderer = HbPipesOutputRenderer()
        self.assertIsInstance(renderer, OutputRenderer)

    def test_render(self):
        """Test output response
        """

        renderer = HbPipesOutputRenderer()

        for data, headers, headers_out, content_type, status_code in self.render_provider():
            out = renderer.render(data=data, status=status_code, content_type=content_type, headers=headers)

            self.assertIsInstance(out, flask.Response)
            self.assertEqual(out.status_code, status_code)
            self.assertEqual(json.dumps(data), out.get_data(True))
            self.assertDictEqual(headers_out, dict(out.headers))

    @staticmethod
    def render_provider():
        test_data = [
            {
                'data': {'foo': 'bar'},
                'headers': {'Accept': 'application/json'},
                'headers_out': {'Accept': 'application/json', 'Content-Type': 'application/json',
                                'Content-Length': '14'},
                'status_code': 201,
                'content_type': 'application/json'
            },
            {
                'data': {},
                'headers': {'Accept': 'text/plain'},
                'headers_out': {'Accept': 'text/plain', 'Content-Type': 'text/plain',
                                'Content-Length': '2'},
                'status_code': 400,
                'content_type': 'text/plain'
            }
        ]

        for item in test_data:
            yield item['data'], item['headers'], item['headers_out'], item['content_type'], item['status_code']
