function sendStatement(params) {
	params.timestamp = new Date().toISOString();
	// TODO absolute url ref fix
	$.post("../xapi", params);
}
function sendExitStatement()
{
	sendStatement({
		statementName: "dashboardExited"
	});
}

$(function() {
	window.onbeforeunload = sendExitStatement;
});
