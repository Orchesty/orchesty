$(function(){

	// Stream client scripts
	var socket = io(wsHost);
	var userId = "test";
	var groups = ['a'];
	var token = 'bcb5609a-b7f4-4df1-ad8e-e26ef52a273c';

	socket.on('connect', function () {
		console.log('Connection to websockets created.');
	});

	socket.on('disconnect', function () {
		console.log('Connection to web sockets closed.');
	});

	socket.on('error_message', function (data) {
		console.log(data);
		$("#streamMessages tbody").append("<tr><td>error</td><td>" + data + "</td></tr>")
	});

	socket.on('info_message', function (data) {
		console.log(data);
		$("#streamMessages tbody").append("<tr><td>info</td><td>" + data + "</td></tr>")
	});

	socket.on('message', function (data) {
		console.log(data);
		$("#streamMessages tbody").append("<tr><td>" + data.event + "</td><td>" + data.content + "</td></tr>")
	});

	$("#subscribeStream").on("click", function() {


		socket.emit('subscribe', { userId: userId, groups: groups, token: token });
	});

	$("#unsubscribeStream").on("click", function() {
		socket.emit('unsubscribe', { userId: userId, groups: groups, token: token });
	});

});
