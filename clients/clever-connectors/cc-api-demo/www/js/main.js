$(function () {

	$.nette.init();

	// Stream client scripts
	var socket = io(wsHost);

	// Connect
	socket.on('connect', function () {
		console.log('Connection to websockets created.');

		// Subscribe and unsubscribe to stream
		var streamToken = getStreamToken();
		if (isLogged === true) {
			if (streamToken === null) {
				subscribe();
			} else {
				subscribeToStream(streamToken)
			}
		} else {
			if (streamToken !== null) {
				unsubscribe(streamToken);
			}
		}
	});

	// Disconnect
	socket.on('disconnect', function () {
		console.log('Connection to web sockets closed.');
	});

	// Error messages from stream server
	socket.on('error_message', function (data) {
		console.log(data);
		addDemoMessage("error", data);
	});

	// Info messages from stream server
	socket.on('info_message', function (data) {
		console.log(data);
		addDemoMessage("info", data);
	});

	// Main switch for stream messages
	socket.on('message', function (data) {
		switch (data.event) {
			case "demo_event":
				addDemoMessage(data.event, data.content);
				break;
			case "sync_event":
				addSyncMessage(data.content);
				break;
			default:
				console.log(data);
				break;
		}
	});

	// LOGIN, LOGOUT

	/**
	 * Get stream token from local storage
	 */
	function getStreamToken() {
		return localStorage.getItem("stream-token");
	}

	/**
	 * Remove token from local storage
	 */
	function removeStreamToken() {
		localStorage.removeItem('stream-token');
	}

	/**
	 * Set toke to local storage
	 *
	 * @param token
	 */
	function setStreamToken(token) {
		localStorage.setItem('stream-token', token);
	}

	var refreshConnectionTimeout = null;

	/**
	 * Refresh socket connection every 1 min
	 *
	 * @param token
	 */
	function refreshConnection(token) {
		refreshConnectionTimeout = setTimeout(function () {
			subscribeToStream(token);
		}, 1000 * 60);
	}

	/**
	 * Stop refresh loop
	 */
	function stopRefresh() {
		if (refreshConnectionTimeout !== null) {
			clearTimeout(refreshConnectionTimeout);
		}
	}

	/**
	 * Subscribe to stream with token
	 *
	 * @param token
	 * @param groups
	 */
	function subscribeToStream(token, groups) {
		if (groups === undefined) {
			groups = [];
		}
		console.log('Subscribe to stream with token: ' + token);
		socket.emit('subscribe', {token: token, groups: groups});
		refreshConnection(token);
	}

	/**
	 * Unsubscribe from stream with token
	 *
	 * @param token
	 * @param groups
	 */
	function unsubscribeFromStream(token, groups) {
		if (groups === undefined) {
			groups = [];
		}
		console.log('Unsubscribe from stream with token: ' + token);
		socket.emit('unsubscribe', {token: token, groups: groups});
		stopRefresh();
	}

	/**
	 * Subscribe action form stream after login
	 */
	function subscribe() {
		$.ajax({
			url: "/api-demo/stream?do=subscribe",
			type: "post",
			contentType: "application/json"
		}).done(function (data) {
			token = data.token;
			console.log('Received new token: ' + token);
			setStreamToken(token);
			subscribeToStream(token);
		});
	}

	/**
	 * Unsubscribe action fro stream after logout
	 *
	 * @param token
	 */
	function unsubscribe(token) {
		unsubscribeFromStream(token);
		$.ajax({
			url: "/api-demo/stream?do=unsubscribe&token=" + token,
			type: "post",
			contentType: "application/json"
		}).done(function () {
			console.log('Unsubscribe and remove token: ' + token);
			removeStreamToken();
		});
	}

	// STREAM DEMO

	var demoToken = "";
	var demoGroups = [];

	// Subscribe action for demo stream
	$("form#frm-subscribeForm").on("submit", function (event) {
		event.preventDefault();
		var userId = $("input#frm-subscribeForm-user_id").val();
		var groupsString = $("input#frm-subscribeForm-groups").val();
		demoGroups = groupsString.split(',');

		if (Nette.validateForm(this, true)) {
			$.ajax({
				url: "subscribe-demo",
				type: "post",
				contentType: "application/json",
				data: JSON.stringify({userId: userId, groups: groupsString})
			}).done(function (data) {
				demoToken = data.token;
				subscribeToStream(demoToken, demoGroups);
			});

			$("form#frm-subscribeForm").hide();
			$("button#unsubscribeStream").show();
		}
	});

	// Unsubscribe action for demo stream
	$("#unsubscribeStream").on("click", function () {
		unsubscribeFromStream(demoToken, demoGroups);
		$.ajax({
			url: "unsubscribe-demo",
			type: "post",
			contentType: "application/json",
			data: JSON.stringify({token: demoToken})
		}).done(function (data) {
			// user unsubscribed
		});

		$("form#frm-subscribeForm").show();
		$("button#unsubscribeStream").hide();
	});

	/**
	 * Add message to demo table
	 *
	 * @param type
	 * @param message
	 */
	function addDemoMessage(type, message) {
		var d = new Date();
		$("#streamMessages tbody").prepend(
			"<tr><td>" + type + "</td><td>" + JSON.stringify(message) + "</td><td>" + d.toString() + "</td></tr>"
		);
	}

	var syncData = {};

	/**
	 * Show stream progress
	 *
	 * @param data
	 */
	function addSyncMessage(data) {

		console.log(data);

		syncData[data.process_id] = data;

		var itemHtml = '<div class="col-md-6">PROCESS ID: {ID}</div>' +
			'<div class="col-md-6">' +
			'<div class="progress">' +
			'<div class="progress-bar progress-bar-striped progress-bar-animated {BG}" role="progressbar" style="width: {PROGRESS}%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>' +
			'</div>' +
			'</div>' +
			'<div class="clearfix"></div>';

		var html = '';
		Object.keys(syncData).forEach(function (processId) {

			var item = syncData[processId];
			var progress = (item.progress / item.total) * 100;

			if (progress > 100) {
				progress = 100;
			}

			var data = itemHtml;

			switch (item.status) {
				case "success":
					data = data.replace("{BG}", 'bg-success');
					break;
				case "failed":
					data = data.replace("{BG}", 'bg-danger');
					progress = 100;
					break;
				default:
					data = data.replace("{BG}", 'bg-info');
					break;
			}

			data = data.replace("{ID}", processId);
			data = data.replace("{PROGRESS}", progress);

			if (item.status !== 'in_progress') {
				delete(syncData[processId]);
			}

			html += data;
		});

		$("#sync-stream").html(html);
	}

});
