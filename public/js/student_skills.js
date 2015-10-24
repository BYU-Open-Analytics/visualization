// Loads the skill scores into the recommendation templates and puts them in the list
function loadSkills(data) {
	// Hide the loading spinner
	$("#skillsListSection .spinner").hide();

	var skills = data.student;
	// Sort skills weakest to strongest
	skills.sort(function(a,b) {
		return a.score - b.score;
	});

	for (var i=0; i<6; i++) {
		// Get template for each skill
		var s = $("." + skills[i].id + "SkillTemplate").appendTo("#skillsList");
		$("." + skills[i].id + "SkillTemplate .skillScoreLabel").text(skills[i].score);
		// Put score in each skill
		$("." + skills[i].id + "SkillTemplate .skillScoreLabel").text(skills[i].score);
		$("." + skills[i].id + "SkillTemplate .skillPercentileLabel").text(skills[i].score * 10);
	}
	
	// Change score bar bg color to match the score
	$(".skillTemplate").each(function() {
		//If their score is 0-3 make it red. If their score is 4-6 make it yellow, and if their score is > 6 make it green.
		var score = $($(this).find(".skillScoreLabel")[0]).text();
		var color = score >= 6 ? "#5cb85c" : score >= 4 ? "#f0ad4e" : "#d9534f";
		$(this).find(".skillScoreBar").css("background",color);
	});

	refreshView();
}

var skillIds = {
	"Time Management": "time",
	"Online Activity": "activity",
	"Consistency": "consistency",
	"Knowledge Awareness": "awareness",
	"Deep Learning": "deepLearning",
	"Persistence": "persistence"
	};
var skillTitles = {
	"time": "Time Management",
	"activity": "Online Activity",
	"consistency": "Consistency",
	"awareness": "Knowledge Awareness",
	"deepLearning": "Deep Learning",
	"persistence": "Persistence"
	};

// When a point on the radar graph is clicked
function skillsGraphPointClicked(d) {
	console.log(d);
	var title = (d && typeof d == "object") ? d.axis : d;
	// Find the skill id from the title
	var skillId = skillIds[title];
	if (!skillId) { return; }
	$("#skillsGraphRecommend").html($("#skillsListSection ." + skillId + "SkillTemplate").clone().addClass("advancedSkillsGraph"));
	refreshView();
	// Track this
	track("clicked", "radarGraph"+skillId+"Point");
	// Make sure tooltip on new skill info box works
	setupBootstrapTooltips();
}


function loadSkillsGraph(data) {

	var colorScale = d3.scale.category10();
	var radarConfig = {
		w: 400,
		h: 400,
		labelFactor: 1.15,
		maxValue: 10,
		levels: 5,
		margin: {top: 40, right: 100, bottom: 100, left: 100},
		color: colorScale,
		clickHandler: skillsGraphPointClicked
	};
	var legendOptions = ["Student", "Class Median"];
	//Hide the loading spinner
	$("#radarContainer .spinner").hide();
	// Format data
	var studentData = data.student.map(function(d) { return {axis:skillTitles[d.id], value:(d.score)}; });
	var classData = data.class.map(function(d) { return {axis:d.id, value:(d.score)}; });
	// Draw the radar chart
	RadarChart("#radarChart", [studentData, classData], radarConfig);

	// Draw the legend to the side
	// Container
	var svg = d3.select("#radarChart svg")
		.append("svg")
		.attr("width", radarConfig.w + 300)
		.attr("height", radarConfig.h);
	// Legend container
	var legend = svg.append("g")
		.attr("class", "legend classLegend")
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

	refreshView();
}

function loadTimeGraph(skillId) {
	// Default skill is time management
	skillId = skillId != null ? skillId : "time";

	// Show the loading spinner
	$("#timeGraphSection .spinner").show();
	// Remove existing graph
	$("#timeGraph").empty();

	// Largely from http://bl.ocks.org/mbostock/3883245
	var margin = {top: 20, right: 20, bottom: 50, left: 30},
	    width = 800 - margin.left - margin.right,
	    height = 300 - margin.top - margin.bottom;

	var parseDate = d3.time.format("%Y-%m-%d").parse;

	var x = d3.scale.ordinal()
		.rangeBands([0, width], 1.1);

	var y = d3.scale.linear()
	    .range([height, 0]);

	var xAxis = d3.svg.axis()
	    .scale(x)
	    .orient("bottom");

	var yAxis = d3.svg.axis()
	    .scale(y)
	    .orient("left");

	var svg = d3.select("#timeGraph").append("svg")
	    .attr("width", width + margin.left + margin.right)
	    .attr("height", height + margin.top + margin.bottom)
	  .append("g")
	    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

	d3.csv("../student_skills_stats/time_graph/" + skillId, function(error, data) {

	  // Hide the loading spinner
	  $("#timeGraphSection .spinner").hide();

	  // TODO error handling with friendly text and no infinite spinner
	  if (error) throw error;


	  data.forEach(function(d) {
	    //d.date = parseDate(d.date);
		d.time = +d.time;
		d.activity = +d.activity;
		d.consistency = +d.consistency;
		d.awareness = +d.awareness;
		d.deepLearning = +d.deepLearning;
		d.persistence = +d.persistence;
	  });

	  console.log(data);

	  x.domain(data.map(function(d) { return d.date; }));
	  y.domain([0, 10]);

	  svg.append("g")
	      .attr("class", "x axis")
	      .attr("transform", "translate(0," + height + ")")
	      .call(xAxis)
	    .append("text")
	      .attr("dy", "3em")
	      .attr("x", width / 2)
	      .style("text-anchor", "middle")
	      .text("Date");

	  svg.append("g")
	      .attr("class", "y axis")
	      .call(yAxis)
	    .append("text")
	      .attr("transform", "rotate(-90)")
	      .attr("y", 6)
	      .attr("dy", ".71em")
	      .style("text-anchor", "end")
	      .text("Score");

	  var line = d3.svg.line()
		.x(function(d) {return x(d.date); })
		.y(function(d) {return y(d[skillId]); });


	  var studentData = $.grep(data, function(d,i) {
		  return d.scope == "student";
	  });
	  svg.append("path")
	      .datum(data)
	      .attr("class", "line studentLine")
	      .attr("d", line);

	  var classData = data.map(function(d) {
		  var newD = d;
		  newD[skillId] = 5;
		  return newD;
	  });
	  svg.append("path")
	      .datum(classData)
	      .attr("class", "line classLine")
	      .attr("d", line);
	});
}

// We have to do this again when we load the new skill box in the radar chart
function setupBootstrapTooltips() {
	$('[data-toggle="tooltip"]').tooltip({
		container: 'body'
	});
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
			$(".advancedSimple").removeClass(h).addClass(s);
			break;
		case "all":
			$(".advancedSimple, .advancedAll").removeClass(h).addClass(s);
			break;
		case "allScores":
			$("#advancedToggleAllScoresClass").prop("checked",false);
			if (optionValue == true) {
				$(".advancedSimple, .advancedAll, .advancedAllScores").removeClass(h).addClass(s);
			} else {
				$(".advancedSimple, .advancedAll").removeClass(h).addClass(s);
			}
			break;
		case "allScoresClass":
			$("#advancedToggleAllScores").prop("checked",false);
			if (optionValue == true) {
				$(".advancedSimple, .advancedAll, .advancedAllScoresClass").removeClass(h).addClass(s);
			} else {
				$(".advancedSimple, .advancedAll").removeClass(h).addClass(s);
			}
			break;
		case "timeGraph":
			$(".advancedTimeGraph").removeClass(h).addClass(s);
			// Have to manually do things in the svg chart
			$("#timeGraphSection .classLine").hide();
			break;
		case "timeGraphClass":
			if (optionValue == true) {
				$(".advancedTimeGraph, .advancedTimeGraphClass").removeClass(h).addClass(s);
				$("#timeGraphSection .classLine").fadeIn();
			} else {
				$(".advancedTimeGraph").removeClass(h).addClass(s);
				$("#timeGraphSection .classLine").fadeOut();
			}
			break;
		case "skillsGraph":
			$(".advancedSkillsGraph").removeClass(h).addClass(s);
			// Have to manually do things in the svg chart
			$("#radarChart .radar-chart-serie1").hide();
			$("#radarChart .classLegend").hide();
			break;
		case "skillsGraphClass":
			if (optionValue == true) {
				$(".advancedSkillsGraph, .advancedSkillsGraphClass").removeClass(h).addClass(s);
				$("#radarChart .radar-chart-serie1").fadeIn();
				$("#radarChart .classLegend").fadeIn();
			} else {
				$(".advancedSkillsGraph").removeClass(h).addClass(s);
				$("#radarChart .radar-chart-serie1").fadeOut();
				$("#radarChart .classLegend").fadeOut();
			}
			break;
	}
}

// Called when send feedback button is clicked. Feedback is recorded in dashboard database
function sendFeedback() {
	// Make sure there's feedback text first
	if (!$.trim($("#feedbackTextArea").val())) {
		$("#feedbackEmptyAlert").removeClass("hidden").hide().slideDown("fast");
		return;
	}
	var feedbackText = $("#feedbackTextArea").val() + "\n---\n Sent from " + window.location.href + "\n" + navigator.userAgent;
	var feedbackType = $("#feedbackTypeSelector").val();
	$("#feedbackSpinner").removeClass("hidden");
	$("#feedbackForm").slideUp();
	$.post("../feedback/submit", {"feedbackType":feedbackType,"feedback":feedbackText}, function(data) {
		$("#feedbackResult").removeClass("hidden").text(data);
		$("#feedbackSpinner").addClass("hidden");
	});
}


// Called for basically every click interaction. Sends an xAPI statement with the given verb and object
// verbName is often "clicked". objectName should be string with no spaces, e.g. "viewSettingMasteryGraph"
function track(verbName, objectName) {
	console.log("Tracking: ",verbName,objectName);
	sendStatement({
		statementName: 'interacted',
		dashboardID: 'student_skills_dashboard',
		dashboardName: 'Student Skills Dashboard',
		verbName: verbName,
		objectName: objectName
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
		track("clicked", "continueButton");
	});
	$(".advancedToggle").click(function() {
		// Deselect other options
		$(".advancedToggleLi").removeClass("active");
		$(".advancedToggleOptional").prop("checked", false);
		// Select this option
		$(this).parent(".advancedToggleLi").addClass("active");
		changeView($(this).attr("data-option"));
		track("clicked","viewSetting"+$(this).attr("data-option"));
		return false;
	});
	$(".advancedToggleOptional").change(function(event) {
		changeView($(this).attr("data-option"), this.checked);
		track("clicked","viewSetting"+$(this).attr("data-option"));
		event.stopPropagation();
		event.preventDefault();
	});
	// Reload the time graph when skill selection changes
	$("input:radio[name=timeGraphSkillOption]").on("change", function() {
		loadTimeGraph($(this).val());
		track("clicked","timeGraphSkillOption"+$(this).val());
	});
	// Set up bootstrap tooltips
	setupBootstrapTooltips();

	// Set up event listener for links that we want to track
	$(document).on("click", "[data-track]", function() {
		track("clicked", $(this).attr("data-track"));
	});
	// Add feedback button to navbar (we don't want this in the phtml template, since not all pages will have feedback modal or js)
	$("#navbarButtonHolder").append('<button class="btn btn-primary" data-toggle="modal" data-track="feedbackButton" data-target="#feedbackModal"><span style="top: 3px;" class="glyphicon glyphicon-comment"></span>&nbsp; Send Feedback</button>')
	// Bind feedback submit button click event
	$("#feedbackSendButton").click(sendFeedback);
	
	// Load data
	// TODO absolute url ref fix
	d3.json("../student_skills_stats/skills", function(error, data) {
		if (!(data && data.student)) {
			$(".spinner").hide();
			$("#radarContainer").html('<p class="lead">There was an error loading skills data. Try reloading the dashboard.</p>');
			return;
		}
		loadSkills(data);
		loadSkillsGraph(data);
	});
	loadTimeGraph();

	// Go to the skills graph first
	changeView("skillsGraph");
});
