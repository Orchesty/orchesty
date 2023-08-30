export default {
	apiGateway: {
		initDefault: 'cm_dev_docker',
		servers: {
			apiary: {
				url: 'http://private-973c6-pipes1.apiary-mock.com',
				noCredentials: true,
				caption: 'Apiary',
			},
			demo_dev_docker: {
				url: 'http://127.0.0.66/api',
				url_starting_point: 'http://127.0.0.66:82',
				caption: 'Demo - Dev - docker',
			},
			tutorial_dev_docker: {
				url: 'http://127.0.0.10:8080/api',
				url_starting_point: 'http://127.0.0.10:82',
				caption: 'Tutorial Pipes - docker',
			},
		},
	},
};
