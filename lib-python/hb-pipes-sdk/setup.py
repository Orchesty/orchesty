from setuptools import setup

setup(
    name='hb_pipes_sdk',
    # todo: add repo
    # url='https://gitlab.hanaboso.net/pipes/pipes',
    author='Pavel Severyn',
    author_email='severyn.p@hanaboso.com',
    maintainer='Hanaboso s.r.o.',
    python_requires='>=3.6',
    packages=['hb_pipes_sdk'],
    include_package_data=True,
    platforms='any',
    install_requires=['flask>=1.0.2', 'flask_json>=0.3.3', 'PyYAML=5.1'],
    version='1.0',
    license='MIT',
    description='Pipes SDK for custom python node',
    long_description=open('README.rst').read(),
)
