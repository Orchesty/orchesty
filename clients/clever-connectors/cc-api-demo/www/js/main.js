$(function(){

	// Stream client scripts
	var socket = io(wsHost);
	var token = "";

	socket.on('connect', function () {
		console.log('Connection to websockets created.');
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
		addMessage(data.event, data.content);
	});

	$("#subscribeStream").on("click", function() {
		var userId = $("input#userId").val();
		var groups = $("input#groups").val().split(',');

		$.ajax({
			url: "subscription",
			type: "post",
			contentType: "application/json",
			data: JSON.stringify({userId: userId, groups: $("input#groups").val()})
		}).done(function(data) {
			token = data.token;
			socket.emit('subscribe', { userId: userId, groups: groups, token: token });
		});

		$("form#subscribeForm").hide();
		$("button#subscribeStream").hide();
		$("button#unsubscribeStream").show();
	});

	$("#unsubscribeStream").on("click", function() {
		socket.emit('unsubscribe', { userId: userId, groups: groups, token: token });

		$.ajax({
			url: "unsubscription",
			type: "post",
			contentType: "application/json",
			data: JSON.stringify({token: token})
		}).done(function(data) {
			token = data.token;
			socket.emit('subscribe', { userId: userId, groups: groups, token: token });
		});

		$("#streamMessages tbody").prepend("<tr><td>info</td><td>You unsubscribed</td></tr>")

		$("form#subscribeForm").show();
		$("button#subscribeStream").show();
		$("button#unsubscribeStream").hide();
	});

	function addMessage(type, message) {
		var d = new Date();
		$("#streamMessages tbody").prepend(
			"<tr><td>"+type+"</td><td>" + message + "</td><td>"+d.toLocaleTimeString()+"</td></tr>"
		);
	}

});
