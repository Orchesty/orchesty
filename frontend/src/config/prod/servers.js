export default {
	apiGateway: {
		initDefault: 'mainServer',
		servers: {
			mainServer: {
				url: 'http://url.to.api.gateway/api',
				url_starting_point: 'http://url.to.api.gateway/starting-point',
				caption: 'Main',
			},
		},
	},
};
