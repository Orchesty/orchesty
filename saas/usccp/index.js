const oasTools = require('oas-tools')
const jsyaml = require('js-yaml')
const fs = require('fs')
const path = require('path')
const http = require('http');
const express = require('express');
const bodyParser = require('body-parser');

const config = require("./src/config.js");
const storage = require("./src/storage.js");

(async () => { 

  await storage.init(config);

  const app = express();
  app.use(bodyParser.json({
    type: () => true, // because backend doesn't send a Content-type
  }));

  const spec = fs.readFileSync('openapi.yaml', 'utf8')
  const oasDoc = jsyaml.safeLoad(spec)
  
  oasTools.configure({
      controllers: path.join(__dirname, './src/controllers'),
      checkControllers: true,
      loglevel: 'info',
      logfile: './logs',
      // customLogger: myLogger,
      strict: false,
      router: true,
      validator: true,
      docs: {
        apiDocs: './zdf',
        apiDocsPrefix: '',
        //swaggerUi: '/docs',
        //swaggerUiPrefix: ''
      }
  });
  
  oasTools.initialize(oasDoc, app, () => {
      http.createServer(app).listen(8080, function() {
        console.log("USCCP up and running!");
      });
  });
  
})();
