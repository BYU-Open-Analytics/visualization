function sendStatement(params) {
	params.timestamp = new Date().toISOString();
	// TODO absolute url ref fix
	$.post("../xapi", params);
}
