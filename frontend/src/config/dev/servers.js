export default {
  apiGateway: {
    initDefault: 'dev_docker',
    servers: {
      apiary: {
        url: 'http://private-973c6-pipes1.apiary-mock.com',
        caption: 'Apiary'
      },
      dev_docker: {
        url: 'http://127.0.0.66/api',
        caption: 'Dev - docker'
      }
    }
  }
}