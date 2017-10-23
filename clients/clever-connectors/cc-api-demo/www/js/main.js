$(function(){

	$.nette.init();

	// Stream client scripts
	var socket = io(wsHost);
	var token = "";
	var groups = [];

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
		var groupsString = $("input#groups").val();
		groups = groupsString.split(',');

		$.ajax({
			url: "subscribe",
			type: "post",
			contentType: "application/json",
			data: JSON.stringify({userId: userId, groups: groupsString})
		}).done(function(data) {
			token = data.token;
			socket.emit('subscribe', { token: token, groups: groups });
		});

		$("form#subscribeForm").hide();
		$("button#subscribeStream").hide();
		$("button#unsubscribeStream").show();
	});

	$("#unsubscribeStream").on("click", function() {
		socket.emit('unsubscribe', { token: token, groups: groups });

		$.ajax({
			url: "unsubscribe",
			type: "post",
			contentType: "application/json",
			data: JSON.stringify({token: token})
		}).done(function(data) {
			// user unsubscribed
		});

		$("form#subscribeForm").show();
		$("button#subscribeStream").show();
		$("button#unsubscribeStream").hide();
	});

	function addMessage(type, message) {
		var d = new Date();
		$("#streamMessages tbody").prepend(
			"<tr><td>"+type+"</td><td>" + message + "</td><td>"+d.toString()+"</td></tr>"
		);
	}

});
