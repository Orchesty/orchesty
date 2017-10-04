export default {
  apiGateway: {
    initDefault: 'cm_dev_docker',
    servers: {
      apiary: {
        url: 'http://private-973c6-pipes1.apiary-mock.com',
        caption: 'Apiary'
      },
      cm_dev_docker: {
        url: 'http://127.0.0.67/api',
        caption: 'CM - Dev - docker'
      },
	    demo_dev_docker: {
		    url: 'http://127.0.0.66/api',
		    caption: 'Demo - Dev - docker'
	    }
    }
  }
}