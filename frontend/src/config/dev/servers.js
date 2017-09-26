export default {
  apiGateway: {
    initDefault: 'apiary',
    servers: {
      apiary: {
        url: 'http://private-973c6-pipes1.apiary-mock.com',
        caption: 'Apiary'
      },
      dev_docker: {
        url: 'http://127.0.0.66/api/gateway',
        caption: 'Dev - docker'
      }
    }
  }
}