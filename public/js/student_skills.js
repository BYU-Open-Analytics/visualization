function loadSkills(data) {
	// Hide the loading spinner
	$("#skillsListSection .spinner").hide();
	var skills = data.student;
	// Sort skills weakest to strongest
	skills.sort(function(a,b) {
		return a.score - b.score;
	});
	console.log(skills);

	// Display two weakest skills
	var s0 = $("." + skills[0].id + "SkillTemplate").appendTo("#weakestSkillsList")
	$("." + skills[0].id + "SkillTemplate .skillScoreLabel").text(skills[0].score);
	var s1 = $("." + skills[1].id + "SkillTemplate").appendTo("#weakestSkillsList")
	$("." + skills[1].id + "SkillTemplate .skillScoreLabel").text(skills[1].score);
	s1.addClass("advancedAll");

	// Display two strongest skills
	var s4 = $("." + skills[4].id + "SkillTemplate").appendTo("#strongestSkillsList")
	$("." + skills[4].id + "SkillTemplate .skillScoreLabel").text(skills[4].score);
	var s3 = $("." + skills[3].id + "SkillTemplate").appendTo("#strongestSkillsList")
	$("." + skills[3].id + "SkillTemplate .skillScoreLabel").text(skills[3].score);
	s3.addClass("advancedAll");

	// Determine what to do with middle skill. Put it in whichever category its score is closest to.
	
	
	
	// Change score bar bg color to scale
	var colorScale = d3.scale.linear()
			.domain([0, 3.3, 6.6, 10])
			.range(["red", "orange", "yellow", "green"]);
	$(".skillTemplate")
}

function loadSkillsGraph(data) {
	var radarConfig = {
		w: 500,
		h: 500,
		maxValue: 10,
		levels: 10,
		ExtraWidthX: 300
	};
	var legendOptions = ["Class Median", "Student"];
	var colorScale = d3.scale.category10();
	//Hide the loading spinner
	$("#radarContainer .spinner").hide();
	// Format data
	var studentData = data.student.map(function(d) { return {axis:d.id, value:(d.score)}; });
	var classData = data.class.map(function(d) { return {axis:d.id, value:(d.score)}; });
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
}


// Sometimes we're just refreshing the current view, if we added advanced elements and need those to show/hide accordingly.
function refreshView() {
	changeView(currentView[0], currentView[1], true);
}

// Toggles on right of page to change what we're showing
function changeView(optionName, optionValue, refreshOnly) {
	currentView = [optionName, optionValue];

	var h = "advancedHide";
	var s = "advancedShow";
	// Hide all advanced things first
	$(".advancedSimple, .advancedAll, .advancedAllScores, .advancedAllScoresClass, .advancedTimeGraph, .advancedTimeGraphClass, .advancedSkillsGraph").removeClass(s).addClass(h);
	switch (optionName) {
		case "simple":
			//console.log("Changing to simple view");
			$(".advancedSimple").removeClass(h).addClass(s);
			break;
		case "all":
			//console.log("Changing to more view");
			$(".advancedSimple, .advancedAll").removeClass(h).addClass(s);
			break;
		case "allScores":
			//console.log("Changing to more view");
			if (optionValue == true) {
				$(".advancedSimple, .advancedAll, .advancedAllScores").removeClass(h).addClass(s);
			} else {
				$(".advancedSimple, .advancedAll").removeClass(h).addClass(s);
			}
			break;
		case "allScoresClass":
			//console.log("Changing to more view");
			if (optionValue == true) {
				$(".advancedSimple, .advancedAll, .advancedAllScores, .advancedAllScoresClass").removeClass(h).addClass(s);
			} else {
				$(".advancedSimple, .advancedAll, .advancedAllScores").removeClass(h).addClass(s);
			}
			break;
		case "timeGraph":
			//console.log("Changing to scatterplot view");
			$(".advancedTimeGraph").removeClass(h).addClass(s);
			break;
		case "timeGraphClass":
			//console.log("Changing to scatterplot view");
			if (optionValue == true) {
				$(".advancedTimeGraph, .advancedTimeGraphClass").removeClass(h).addClass(s);
			} else {
				$(".advancedTimeGraph").removeClass(h).addClass(s);
			}
			break;
		case "skillsGraph":
			//console.log("Changing to scatterplot view");
			$(".advancedSkillsGraph").removeClass(h).addClass(s);
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
	// TODO absolute url ref fix
	d3.json("../student_skills_stats", function(error, data) {
		loadSkills(data);
		loadSkillsGraph(data);
	});

	// Go to simple view first
	changeView("simple");
});
