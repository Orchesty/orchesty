"use strict";
import React from 'react';
import ReactDOM from 'react-dom';

import Application from './application';
import MainApp from './views/gentelella/main_app';

var application = new Application();

ReactDOM.render(React.createElement(MainApp, {application}), document.getElementById('app'));

