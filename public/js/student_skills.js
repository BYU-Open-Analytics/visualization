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
		maxValue: 10,
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
		var studentData = data.student.map(function(d) { return {axis:d.axis, value:(d.value)}; });
		var classData = data.class.map(function(d) { return {axis:d.axis, value:(d.value)}; });
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

// Toggles on right of page to change what we're showing
function changeView(optionName, optionValue, refreshOnly) {
	currentView = [optionName, optionValue];

	var h = "advancedHide";
	var s = "advancedShow";
	// Hide all advanced things first
	$(".advancedSimple, .advancedMore, .advancedMoreClass, .advancedScatterplot, .advancedScatterplotClass, .advancedMasteryGraph, .advancedAll").removeClass(s).addClass(h);
	switch (optionName) {
		case "simple":
			//console.log("Changing to simple view");
			$(".advancedSimple").removeClass(h).addClass(s);
			break;
		case "more":
			//console.log("Changing to more view");
			$(".advancedSimple, .advancedMore").removeClass(h).addClass(s);
			break;
		case "scatterplot":
			//console.log("Changing to scatterplot view");
			$(".advancedScatterplot").removeClass(h).addClass(s);
			break;
		case "masteryGraph":
			//console.log("Changing to mastery graph view");
			$(".advancedMasteryGraph").removeClass(h).addClass(s);
			if (!refreshOnly) {
				loadMasteryGraph();
			}
			animateMasteryGraph();
			break;
		case "all":
			//console.log("Changing to all view");
			$(".advancedAll").removeClass(h).addClass(s);
			if (!refreshOnly) {
				loadAllRecommendations();
			}
			break;
		case "moreClass":
			if (optionValue == true) {
				//console.log("Changing to more + class compare view");
				$(".advancedSimple, .advancedMore, .advancedMoreClass").removeClass(h).addClass(s);
			} else {
				//console.log("Changing to more view");
				$(".advancedSimple, .advancedMore").removeClass(h).addClass(s);
			}
			break;
		case "scatterplotClass":
			if (optionValue == true) {
				//console.log("Changing to scatterplot + class compare view");
				$(".advancedScatterplot, .advancedScatterplotClass").removeClass(h).addClass(s);
			} else {
				//console.log("Changing to scatterplot view");
				$(".advancedScatterplot").removeClass(h).addClass(s);
			}
			break;
	}
}

// When page is done loading, show our visualizations
$(function() {

	// Send dashboard launched statement
	sendStatement({
		statementName: 'dashboardLaunched',
		dashboardID: 'student_skills_dashboard',
		dashboardName: 'Student Skills Dashboard'
	});
	// Record start load time for duration for statement
	var loadTime = Date.now();
	// Send exited statement when student leaves page
	window.onbeforeunload = function() { sendStatement({
		statementName: 'dashboardExited',
		duration: centisecsToISODuration( (Date.now() - loadTime) / 10),
		dashboardID: 'student_skills_dashboard',
		dashboardName: 'Student Skills Dashboard'
	}); }

	// Set up event listeneres
	$("#jumbotronDismiss").click(function() {
		$("#"+$(this).attr("data-dismiss")).hide();
		$("#mainContainer").removeClass("hidden").addClass("show");
	});
	$(".advancedToggle").click(function() {
		// Deselect other options
		$(".advancedToggleLi").removeClass("active");
		$(".advancedToggleOptional").prop("checked", false);
		// Select this option
		$(this).parent(".advancedToggleLi").addClass("active");
		changeView($(this).attr("data-option"));
		return false;
	});
	$(".advancedToggleOptional").change(function(event) {
		changeView($(this).attr("data-option"), this.checked);
		event.stopPropagation();
		event.preventDefault();
	});
	// Set up bootstrap tooltips
	$('[data-toggle="tooltip"]').tooltip({
		container: 'body'
	});
	
	// Load data
	updateRadarChart();

	// Go to simple view first
	changeView("simple");
});
