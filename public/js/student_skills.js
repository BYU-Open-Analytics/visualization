// Question Launch Modal
$("#questionLaunchModal").on("show.bs.modal", function(e) {
	console.log(e);
	$(this).find(".btn-primary").attr('href','../consumer.php?app=openassessments&assessment_id=' + $(e.relatedTarget).attr('data-assessment') + '&question_id=' + $(e.relatedTarget).attr('data-question'));
});
$("#questionLaunchContinueButton").click(function(e) {
	$("#questionLaunchModal").modal("hide");
});

function updateRadarChart() {
	var radarConfig = {
		w: 500,
		h: 500,
		maxValue: 1,
		levels: 10,
		ExtraWidthX: 300
	};
	var legendOptions = ["Class Median", "Student"];
	var colorScale = d3.scale.category10();
	// TODO absolute url ref fix
	d3.json("../student_skills_stats", function(error, data) {
		//Hide the loading spinner
		$("#radarContainer .spinner").hide();
		console.log(data, error);
		// TODO error checking
		// Format data
		var studentData = data.student.map(function(d) { return {axis:d.axis, value:(d.value / 10)}; });
		var classData = data.class.map(function(d) { return {axis:d.axis, value:(d.value / 10)}; });
		// Draw the radar chart
		RadarChart.draw("#radarChart", [classData, studentData], radarConfig);

		// Draw the legend to the side
		// Container
		var svg = d3.select("#radarChart svg")
			.append("svg")
			.attr("width", radarConfig.w + 300)
			.attr("height", radarConfig.h);
		// Legend container
		var legend = svg.append("g")
			.attr("class", "legend")
			.attr("height", 100)
			.attr("width", 200)
			.attr("transform", "translate(90,20)");
		// Color squares
		legend.selectAll("rect")
			.data(legendOptions)
			.enter()
			.append("rect")
			.attr("x", radarConfig.w - 65)
			.attr("y", function(d, i) { return i * 20; })
			.attr("width", 10)
			.attr("height", 10)
			.style("fill", function(d, i) { return colorScale(i); });
		// Text next to squares
		legend.selectAll("text")
			.data(legendOptions)
			.enter()
			.append("text")
			.attr("x", radarConfig.w - 52)
			.attr("y", function(d, i) { return i * 20 + 9; })
			.attr("font-size", "11px")
			.attr("fill", "#737373")
			.text(function(d) { return d; });


		
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
	// Send exited statement when student leaves page
	window.onbeforeunload = function() { sendStatement({
		statementName: 'dashboardExited',
		dashboardID: 'student_skills_dashboard',
		dashboardName: 'Student Skills Dashboard'
	}); }

	updateRadarChart();
});
