// Question Launch Modal
$("#questionLaunchModal").on("show.bs.modal", function(e) {
	console.log(e);
	$(this).find(".btn-primary").attr('href','../consumer.php?app=openassessments&assessment_id=' + $(e.relatedTarget).attr('data-assessment') + '&question_id=' + $(e.relatedTarget).attr('data-question'));
});
$("#questionLaunchContinueButton").click(function(e) {
	$("#questionLaunchModal").modal("hide");
});

function updateRadarChart() {
	// TODO absolute url ref fix
	d3.json("../student_skills_stats", function(error, data) {
		console.log(data, error);
	});
}

// When page is done loading, show our visualizations
$(function() {

	// Send dashboard launched statement
	sendStatement({
		statementName: 'dashboardLaunched',
		dashboardID: 'student_skills_dashboard',
		dashboardName: 'Student Skills Dashboard'
	});
	updateRadarChart();
});
