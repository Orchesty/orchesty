export default {
	apiGateway: {
		initDefault: 'mainServer',
		servers: {
			mainServer: {
				url: 'http://url.to.backend/api',
				url_starting_point: 'http://url.to.starting-point',
				caption: 'Main',
			},
		},
	},
};
