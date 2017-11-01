$(function () {

	$.nette.init();

	// Stream client scripts
	var socket = io(wsHost);
	var token = "";
	var groups = [];

	socket.on('connect', function () {
		console.log('Connection to websockets created.');

		var streamToken = localStorage.getItem("stream-token");
		if (isLogged === true) {
			if (streamToken === null) {
				$.ajax({
					url: "/api-demo/stream?do=subscribe",
					type: "post",
					contentType: "application/json"
				}).done(function (data) {
					token = data.token;
					console.log('Subscribe with new token: ' + token);
					localStorage.setItem('stream-token', token);
					socket.emit('subscribe', {token: token});
				});
			} else {
				// subscribe
				console.log('Subscribe to stream with token: ' + streamToken);
				socket.emit('subscribe', {token: streamToken});
			}
		} else {
			if (streamToken !== null) {
				$.ajax({
					url: "/api-demo/stream?do=unsubscribe&token=" + streamToken,
					type: "post",
					contentType: "application/json"
				}).done(function () {
					console.log('Unsubscribe and remove token: ' + streamToken);
					localStorage.removeItem('stream-token');
				});
			}
		}
	});

	socket.on('disconnect', function () {
		console.log('Connection to web sockets closed.');
	});

	socket.on('error_message', function (data) {
		console.log(data);
		addMessage("error", data);
	});

	socket.on('info_message', function (data) {
		console.log(data);
		addMessage("info", data);
	});

	socket.on('message', function (data) {
		console.log(data);
		switch (data.event) {
			case "event-name":
				addMessage(data.event, data.content);
				break;
			default:
				addMessage(data.event, data.content);
				break;
		}
	});

	// DEMO
	$("#subscribeStream").on("click", function () {
		var userId = $("input#userId").val();
		var groupsString = $("input#groups").val();
		groups = groupsString.split(',');

		$.ajax({
			url: "subscribe-demo",
			type: "post",
			contentType: "application/json",
			data: JSON.stringify({userId: userId, groups: groupsString})
		}).done(function (data) {
			token = data.token;
			socket.emit('subscribe', {token: token, groups: groups});
		});

		$("form#subscribeForm").hide();
		$("button#subscribeStream").hide();
		$("button#unsubscribeStream").show();
	});

	$("#unsubscribeStream").on("click", function () {
		socket.emit('unsubscribe', {token: token, groups: groups});

		$.ajax({
			url: "unsubscribe-demo",
			type: "post",
			contentType: "application/json",
			data: JSON.stringify({token: token})
		}).done(function (data) {
			// user unsubscribed
		});

		$("form#subscribeForm").show();
		$("button#subscribeStream").show();
		$("button#unsubscribeStream").hide();
	});

	function addMessage(type, message) {
		var d = new Date();
		$("#streamMessages tbody").prepend(
			"<tr><td>" + type + "</td><td>" + message + "</td><td>" + d.toString() + "</td></tr>"
		);
	}

});
