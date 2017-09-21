export default {
  apiGateway: {
    initDefault: 'apiary',
    servers: {
      apiary: {
        url: 'http://private-973c6-pipes1.apiary-mock.com',
        caption: 'Apiary'
      },
      dev_docker: {
        url: 'http://127.0.0.6/api/gateway',
        caption: 'Dev - docker'
      },
      cerv_localhost: {
        url: 'http://pipes-example:81/api/gateway',
        caption: 'Cerv - localhost'
      }
    }
  }
}