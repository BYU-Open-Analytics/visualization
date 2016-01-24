// Question Launch Modal
$("#questionLaunchModal").on("show.bs.modal", function(e) {
	// Set URL of button to launc the quiz from visualization LTI tool consumer
	$(this).find(".btn-primary").attr('href','../consumer.php?app=openassessments&assessment_id=' + $(e.relatedTarget).attr('data-assessment') + '&question_id=' + $(e.relatedTarget).attr('data-question'));
	// Set the question/assessment id in the button so the button can send an interaction statement
	$(this).find(".btn-primary").attr("data-assessment",$(e.relatedTarget).attr('data-assessment')).attr("data-question", $(e.relatedTarget).attr('data-question'));
	// Track that the modal was shown
	track("clicked", "confirmLaunchQuiz" + $(e.relatedTarget).attr('data-assessment') + '.' + $(e.relatedTarget).attr('data-question'));
});
$("#questionLaunchContinueButton").click(function(e) {
	$("#questionLaunchModal").modal("hide");
	track("clicked", "launchQuiz" + $(this).attr('data-assessment') + '.' + $(this).attr('data-question'));
});

// Helper function for recommendation question elements. Contains question/concept display, and launch quiz button
function questionElement(d) {
	// Get the template
	var element = $("#templates .recommendQuestionDisplay")[0].outerHTML;
	// Put our data values into it (this is a basic template idea from http://stackoverflow.com/a/14062431 )
	$.each(d, function(k, v) {
		var regex = new RegExp("{" + k + "}", "g");
		element = element.replace(regex, v);
	});
	return element;
}

// Sets up the 4 question groups from a template
function setupQuestionGroups() {
	var groups = [
		{
			"id":"Videos",
			"title":"Videos",
			"tooltip":"",
			"table":'<table class="table table-hover sticky-header" id="recommendVideosTable"> <thead><tr><th>&nbsp;</th><th>Video Name</th><th class="advancedMore">% Watched</th></tr></thead> <tbody></tbody> </table>'
		},
		{
			"id":"Questions1",
			"title":"Try these quiz questions",
			"tooltip":"<h4>You did not attempt these questions</h4> These questions were selected because you have not attempted them yet. This material will likely be on an upcoming exam, so to improve your score, it is recommended that you practice these questions.",
			"table":'<table class="table table-hover table-striped sticky-header recommendQuestionsTable" id="recommendQuestions1Table"> </table>'
		},
		{
			"id":"Questions2",
			"title":"Watch videos before attempting these quiz questions",
			"tooltip":"<h4>You did not watch the videos for these questions</h4> These questions were selected because, based on your online activity, it seems you did not watch the videos before you attempted the quiz. To better learn the material, it is recommended that you watch the videos associated with these quiz questions.",
			"table":'<table class="table table-hover table-striped sticky-header recommendQuestionsTable" id="recommendQuestions2Table"> </table>'
		},
		{
			"id":"Questions3",
			"title":"Find additional help",
			"tooltip":"<h4>You tried but did not succeed</h4> These questions were selected because you have spent time watching the videos, but for some reason, the quiz was still difficult for you. To learn this material, you may want to email the instructor, go into the TA lab, or ask a friend to help you.",
			"table":'<table class="table table-hover table-striped sticky-header recommendQuestionsTable" id="recommendQuestions3Table"> </table>'
		},
		{
			"id":"Questions4",
			"title":"Practice these questions again",
			"tooltip":"<h4>You eventually got it right</h4> These questions were selected because even though you eventually answered it correctly, you missed them multiple times at first. It is recommended that you re-do these questions to help you solidify your understanding.",
			"table":'<table class="table table-hover table-striped sticky-header recommendQuestionsTable" id="recommendQuestions4Table"> </table>'
		},
		{
			"id":"Resources",
			"title":"Additional Resources",
			"tooltip":"",
			"table":'</div><ul class="list-group resource-list" id="recommendResourcesTable"></ul><div style="display: none;">'
		}
	];

	for (var i=0; i<groups.length; i++) {
		// Get the template
		var element = $("#templates #recommendGroupTemplate")[0].outerHTML;
		// Put our data values into it (this is a basic template idea from http://stackoverflow.com/a/14062431 )
		$.each(groups[i], function(k, v) {
			var regex = new RegExp("{" + k + "}", "g");
			element = element.replace(regex, v);
		});
		element = element.replace("recommendGroupTemplate", "recommend"+groups[i].id+"Group" );
		$("#recommendAccordion").append(element);
	}
}

// Loads recommendations
function loadRecommendations(scopeOption, scopeGroupingId) {
	// Hide recommendations, show spinner, etc.
	$("#recommendSection .spinner").show();
	$("#recommendSectionHolder p.lead, .recommendGroup").hide();
	$("#recommendSection").appendTo("#recommendSectionHolder");
	$("#recommendSection").removeClass("hidden").show();
	// Get scope with capital first letter for displaying
	var scopeOptionName = scopeOption.charAt(0).toUpperCase() + scopeOption.slice(1);
	$("#recommendHeaderScopeLabel").text(scopeOptionName + " " + scopeGroupingId);

	// Scroll to the top of the section so recommendations are visible
	$("html, body").animate({ scrollTop: $("#recommendSectionHolder").offset().top - 55 }, "fast");

	// Recommendations are split into 3 groups
	loadResourceRecommendations(scopeOption, scopeGroupingId);
	loadVideoRecommendations(scopeOption, scopeGroupingId);
	loadQuestionRecommendations(scopeOption, scopeGroupingId); // This one takes the longest, so it will hide the spinner

}

// Loads video recommendations
function loadVideoRecommendations(scopeOption, scopeGroupingId) {
	d3.json("../scatterplot_recommender_stats/videoRecommendations/" + scopeOption + "/" + scopeGroupingId, function(error, data) {
		if (!(data && typeof data == 'object' && data.length > 0) || error) {
			//$("#recommendSection").html('<br><br><p class="lead">There was an error loading video recommendations. Try reloading the dashboard.</p>');
			return;
		}

		// Clear the previous video list
		$("#recommendVideosTable tbody").empty();
		// Set the badge to the number of videos
		$("#recommendVideosCountBadge").text(data.length);

		// Show the list if there are videos
		if (data.length > 0) {
			$("#recommendVideosGroup").show();
			// Expand the videos accordion group if it's not already expanded
			$("a[href=#recommendVideos][aria-expanded!=true]").click();
		}

		var tbody = d3.select("#recommendVideosTable tbody");
		var tr = tbody.selectAll("tr")
			.data(data)
			.enter()
			.append("tr")
			.attr("id", function(d) { return "videoRow"+d["Video ID"]; });

		tr.append("td")
			.html(function(d) { return ""; })
			.attr("class","videoRefCell");
		tr.append("td")
			// TODO absolute URL ref fix
			.html(function(d) { return '<a href="../consumer.php?app=ayamel&video_id=' + d["Video ID"] + '" data-track="ayamelLaunch' + d["Video ID"] + '" target="_blank">' + d["Video Title"] + '</a>'; })
			.attr("class","videoTitleCell");
		// Add the percentage watched progress circle
		tr.append("td")
			.attr("class", "videoProgressCell advancedMore")
			.append("input")
			.attr("type", "text")
			.attr("class", "progressCircle")
			.attr("disabled", "disabled")
			.attr("value", function(d) { return d.percentageWatched; });

		// Don't stall the UI waiting for all these to finish drawing
		setTimeout(function() {
			$(".progressCircle").knob({
				'readOnly': true,
				'width': '45',
				'height': '45',
				'thickness': '.25',
				'fgColor': '#444',
				'format': function(v) { return v+"%"; }
			});
		}, 1);
	});

}

// Loads additional resource recommendations
function loadResourceRecommendations(scopeOption, scopeGroupingId) {
	d3.json("../scatterplot_recommender_stats/resourceRecommendations/" + scopeOption + "/" + scopeGroupingId, function(error, data) {
		if (!(data && typeof data == 'object' && data.length > 0) || error) {
			return;
		}
		// Clear the previous resource list
		$("#recommendResourcesTable tbody").empty();

		// Set the badge to the number of resources
		$("#recommendResourcesCountBadge").text(data.length);

		// Show the accordion group item if there are resources for this concept
		if (data.length > 0) {
			$("#recommendResourcesGroup").show();
		}

		d3.select("#recommendResourcesTable")
			.selectAll("li")
			.data(data)
			.enter()
			.append("li")
			.attr("class", function(d) { return "list-group-item resource-" + d["Resource Type"]; })
			.html(function(d) {
				// Format web and ayamel links differently
				if (d["Resource Type"] == "web") {
					return '<span class="glyphicon glyphicon-globe" aria-hidden="true">&nbsp;</span>' +
					'<a href="' + d["Resource Link"] + '" data-track="concept' + d["Lecture Number"] + 'AdditionalLink' + d["Resource Tracking Number"] + '" target="_blank">' + d["Resource Title"] + '</a>';
				} else if (d["Resource Type"] == "ayamel") {
					return '<span class="glyphicon glyphicon-film" aria-hidden="true">&nbsp;</span>' +
					'<a href="../consumer.php?app=ayamel&video_id=' + d["Resource Link"] + '" data-track="concept' + d["Lecture Number"] + 'AdditionalVideo' + d["Resource Tracking Number"] + '" target="_blank">' + d["Resource Title"] + '</a>';
				} else {
					return "";
				}
			});
	});
}

// Loads question recommendations
function loadQuestionRecommendations(scopeOption, scopeGroupingId) {
	// Get question recommendations for our scope and grouping ID (either unit number or concept number)
	d3.json("../scatterplot_recommender_stats/questionRecommendations/" + scopeOption + "/" + scopeGroupingId, function(error, data) {
		$("#recommendSection .spinner").hide();
		if (!(data && typeof data == 'object' && "group1" in data) || error) {
			$("#recommendSection").html('<br><br><p class="lead">There was an error loading question recommendations. Try reloading the dashboard.</p>');
			return;
		}
		// Flag to see if we've found the first question group with questions
		var nonemptyGroupFound = false
		// For each question group, go through and load the tables and do some formatting
		for (var i=1; i<5; i++) {
			$("#recommendQuestions"+i+"Table").empty();
			d3.select("#recommendQuestions"+i+"Table")
				.selectAll("tr")
				.data(data["group"+i])
				.enter()
				.append("tr")
				.html(function(d) { return questionElement(d); });
			$("#recommendQuestions"+i+"Table").prepend($("#templates .recommendHeaderTemplate").clone());
			$("#recommendQuestions"+i+"CountBadge").text(data["group"+i].length);
			// Hide this group if there aren't any questions
			if (data["group"+i].length == 0) {
				$("#recommendQuestions"+i+"Group").hide();
			} else {
				// Show non-empty groups, but collapsed by default
				$("#recommendQuestions"+i+"Group").show();
				// Otherwise select this group, if we haven't selected a previous nonempty group
				if (!nonemptyGroupFound) {
					//$("#recommendQuestions"+i).collapse("show");
					nonemptyGroupFound = true;
				} else {
					$("#recommendQuestions"+i).collapse("hide");
				}
			}
		}
		// Set up sticky table headers
		setupStickyHeaders();
		// Set up the show more/show less for the question texts
		$(".recommendQuestionTextContainer").shorten({
			moreText: 'See more',
			lessText: 'See less',
			showChars: 100
		});
		setupBootstrapTooltips();
		// Scroll to the top of the section so recommendations are visible (Again, since page layout changed)
		setTimeout(function() { $("html, body").animate({ scrollTop: $("#recommendSectionHolder").offset().top - 55 }, "fast"); }, 200);
	});
}

// Called when a concept point in the scatterplot is clicked
function showPointConceptRecommendations(d) {
	// Track that the student clicked this
	track("clicked","conceptPoint"+d.id);
	// Hide low concepts list
	$("#lowConceptBox").popover("hide");
	// Deselect any concept in the low concepts list
	$(".lowConceptsList li").removeClass("active");
	// Deslect other points, and select this one and move it to the front of the view hierarchy
	$(".selectedConceptPoint").attr("class", "conceptPoint");
	$(d3.event.currentTarget).attr("class", "conceptPoint selectedConceptPoint");
	d3.select(d3.event.currentTarget).moveToFront();
	// Load recommendations for this concept
	var conceptId = d.id;
	loadRecommendations("concept", conceptId);
}

// Called when a concept from the low concepts list is clicked
function showLowConceptRecommendations(e) {
	//console.log($(this).attr("data-concept"));
	// Track that the student clicked this
	track("clicked","conceptPoint"+$(this).attr("data-concept"));
	// Deslect other points, and select this one and move it to the front of the view hierarchy
	$(".selectedConceptPoint").attr("class", "conceptPoint");
	// Deselect any other concept in the low concepts list
	$(".lowConceptsList li").removeClass("active");
	$(this).addClass("active");
	// Load recommendations for this concept
	var conceptId = $(this).attr("data-concept");
	loadRecommendations("concept", conceptId);
}

// Loads the concept scatterplot
function loadConceptScatterplot() {
	// Show the spinner while loading
	$("#scatterplotSection .spinner").show();
	$("#recommendSectionHolder p.lead").show();
	$("#recommendSection").hide();

	$("#lowConceptBox").popover("hide");

	// Get what scope we're filtering by (unit, chapter, or concept)
	var scopeOption = $("input[name=scatterplotScopeOption]:checked").val();
	var scopeGroupingId = "";
	// Then get which unit, chapter, or concept we're filtering by
	switch (scopeOption) {
		case "concept":
			scopeGroupingId = $("[name=scatterplotConceptSelector]").val();
			break;
		case "chapter":
			scopeGroupingId = $("[name=scatterplotChapterSelector]").val();
			break;
		case "unit":
			scopeGroupingId = $("[name=scatterplotUnitSelector]").val();
			break;
	}

	d3.json("../scatterplot_recommender_stats/concepts/" + scopeOption + "/" + scopeGroupingId, function(error, data) {
		$("#scatterplotSection .spinner").hide();
		if (error != null) {
			console.log("Scatterplot ERROR: ", error);
		}
		// Some basic error handling
		if (!(data && typeof data == 'object') || error) {
			$("#scatterplotSection").html('<p class="lead">There was an error loading concept scores. Try reloading the dashboard.</p>');
			return;
		}
		//Width and height
		var margin = {top: 10, right: 10, bottom: 50, left: 55},
		    height = 450 - margin.top - margin.bottom,
		    width = 500 - margin.left - margin.right;

		var xMax = 100;
		var yMax = 10;
		var xMin = 0;
		var yMin = 0;

		//Create scale functions
		// Don't want dots overlapping axis, so add in buffer to data domain
		var xScale = d3.scale.linear()
			 .domain([0, 100])
			 .range([0, width]);
		var yScale = d3.scale.linear()
			 .domain([0, 10])
			 .range([height, 0]);
		//Color scale
		var colorScale = d3.scale.linear()
				.domain([0, 12, 16, 20])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);
		//Define X axis
		var xAxis = d3.svg.axis()
			  .scale(xScale)
			  .orient("bottom")
			  .tickFormat("")
			  .ticks(0);
		//Define Y axis
		var yAxis = d3.svg.axis()
			  .scale(yScale)
			  .orient("left")
			  .tickFormat("")
			  .ticks(0);
		//Remove old chart
		$("#scatterplotSection svg").remove();
		//Create SVG element
		var svg = d3.select("#scatterplotSection")
			.append("svg")
			.attr("height", height+margin.top+margin.bottom)
			.attr("width", width+margin.left+margin.right)
			.append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

		//Create X axis
		svg.append("g")
			.attr("class", "axis")
			.attr("transform", "translate(0," + (height) + ")")
			.call(xAxis);

		//Create Y axis
		svg.append("g")
			.attr("class", "axis")
			.attr("transform", "translate(0,0)")
			.call(yAxis);

		//Create quadrant lines
		svg.append("line")
			.attr("x1", xScale(xMin))
			.attr("x2", xScale(xMax))
			.attr("y1", yScale((yMin + yMax) / 2))
			.attr("y2", yScale((yMin + yMax) / 2))
			.attr("class", "quadrantLine");
		svg.append("line")
			.attr("y1", yScale(yMin))
			.attr("y2", yScale(yMax))
			.attr("x1", xScale((xMin + xMax) / 2))
			.attr("x2", xScale((xMin + xMax) / 2))
			.attr("class", "quadrantLine");

		//Create custom x axis labels
		//svg.append("text")
			//.attr("x", xScale(xMin) + "px")
			//.attr("y", (height + 20) + "px")
			//.attr("text-anchor", "start")
			//.text("Low");
		svg.append("text")
			.attr("x", xScale((xMin + xMax) / 2) + "px")
			.attr("y", (height + 40) + "px")
			.attr("text-anchor", "middle")
			.attr("cursor", "default")
			.attr("data-toggle", "tooltip")
			.attr("title", "This shows the amount of video that you have watched for a particular concept.")
			.text("Video Time");
		svg.append("text")
			.attr("x", xScale(xMax) + "px")
			.attr("y", (height + 20) + "px")
			.attr("text-anchor", "end")
			.text("High");

		//Create custom y axis labels
		//svg.append("text")
			//.attr("text-anchor", "start")
			//.attr("transform", "translate(-20, " + yScale(yMin) + ")rotate(270)")
			//.text("Low");
		svg.append("text")
			.attr("text-anchor", "middle")
			.attr("transform", "translate(-40, " + yScale((yMin + yMax) / 2) + ")rotate(270)")
			.attr("data-toggle", "tooltip")
			.attr("cursor", "default")
			.attr("title", "This shows your mastery level for each concept on a scale from 0 (low) to 10 (high). It decreases if you click \"show answer\" or if you have a lot of attempts for a problem.")
			.text("Mastery Score");
		svg.append("text")
			.attr("text-anchor", "end")
			.attr("transform", "translate(-20, " + yScale(yMax) + ")rotate(270)")
			.text("High");
		//Create circles
		var dots = svg.selectAll("circle")
		   .data(data);

		dots.enter()
			.append("circle")
			.attr("class", "conceptPoint")
			.attr("r", "6px")
			.attr("title", function(d) {
				return "<b>" + d.title + "</b><br>Video percentage: " + d.videoPercentage + "%<br>Mastery score: " + d.masteryScore;
			})
			.attr("cx", function(d) {
				return xScale(d.videoPercentage);
			})
			.attr("cy", function(d) {
				return yScale(d.masteryScore);
			})
			.attr("fill", function(d) {
				return colorScale(d.masteryScore  + (d.videoPercentage / 10));
			})
			.on('click', showPointConceptRecommendations)
			;

		dots.exit()
			.remove();

		// Get all the concepts that would be overlapping in the bottom corner
		var lowConcepts = [];
		for (var i=0; i < data.length; i++) {
			if ((data[i].masteryScore < 0.6 && data[i].videoPercentage < 6)) {
				lowConcepts.push(data[i]);
			}
		}

		// Only show the box if there's more than 1 low concept
		if (lowConcepts.length > 1) {
			// Box in bottom-left corner that will contain all concepts with scores of (0,0)
			var box = svg.append("g")
				.attr("transform", "translate(-20, " + (yScale(yMin) - 20) + ")");
			box.append("rect")
				.attr("data-toggle", "tooltip")
				.attr("data-placement", "top")
				.attr("data-track", "unattemptedConceptsBox")
				.attr("title", "Click to see unattempted concepts")
				.attr("width", "40px")
				.attr("height", "40px")

				.attr("id", "lowConceptBox");
			box.append("circle")
				.attr("id", "lowConceptPoint")
				.attr("r", "10")
				.attr("fill", "#d9534f")
				.style("pointer-events", "none")
				.attr("cx", "20")
				.attr("cy", "20");


			var lowConceptItems = d3.select(".lowConceptsList").selectAll("li").data(lowConcepts);
			lowConceptItems.enter()
				.append("li")
				.attr("class", "list-group-item")
				.text(function(d) { return d.title; })
				.attr("data-concept", function(d) { return d.id; });

			$("#lowConceptBox").popover({
				html: true,
				container: "body",
				trigger: "manual",
				title: "Unattempted Concepts",
				content: function() {
					return $('.lowConceptsList')[0].outerHTML;
				}
			}).click(function(e) {
				$(this).popover('show');
				$('.popover-title').html('Unattempted Concepts <button type="button" onclick="$(\'#lowConceptBox\').popover(\'hide\');" class="close" style="margin-top: -4px;">&times;</button>');
				clickedAway = false
				isVisible = true
				e.preventDefault()
			});

			// Now bind the event listeners
			$(document).on("click", ".lowConceptsList li", showLowConceptRecommendations);

		}

		//Create quadrants
		var q1 = svg.append("rect")
			.attr("class", "quadrant")
			.attr("id", "quadrant1")
		        .on('mouseover', function() { showQuadrantInfo(1) })
			.attr("x", xScale((xMin + xMax) / 2) + "px")
			.attr("y", "0px");
		var q2 = svg.append("rect")
			.attr("class", "quadrant")
			.attr("id", "quadrant2")
		        .on('mouseover', function() { showQuadrantInfo(2) })
			.attr("x", "0px")
			.attr("y", "0px");
		var q3 = svg.append("rect")
			.attr("class", "quadrant")
			.attr("id", "quadrant3")
		        .on('mouseover', function() { showQuadrantInfo(3) })
			.attr("x", "0px")
			.attr("y", yScale((yMin + yMax) / 2) + "px");
		var q4 = svg.append("rect")
			.attr("class", "quadrant")
			.attr("id", "quadrant4")
		        .on('mouseover', function() { showQuadrantInfo(4) })
			.attr("x", xScale((xMin + xMax) / 2) + "px")
			.attr("y", yScale((yMin + yMax) / 2) + "px");

		svg.selectAll(".quadrant")
			.attr("width", width / 2 + "px")
			.attr("height", height / 2 + "px")
			.moveToBack();

		// Quadrant labels
		svg.append("text")
			.attr("x", xScale(3*((xMin + xMax) / 4)) + "px")
			.attr("y", yScale(3*((yMin + yMax) / 4)) + "px")
			.attr("class", "quadrantLabel hidden")
			.attr("id", "quadrant1Label")
			.text("You Learned It");
		svg.append("text")
			.attr("x", xScale(1*((xMin + xMax) / 4)) + "px")
			.attr("y", yScale(3*((yMin + yMax) / 4)) + "px")
			.attr("class", "quadrantLabel hidden")
			.attr("id", "quadrant2Label")
			.text("You Already Knew It");
		svg.append("text")
			.attr("x", xScale(1*((xMin + xMax) / 4)) + "px")
			.attr("y", yScale(1*((yMin + yMax) / 4)) + "px")
			.attr("class", "quadrantLabel hidden")
			.attr("id", "quadrant3Label")
			.text("Watch the Videos");
		svg.append("text")
			.attr("x", xScale(3*((xMin + xMax) / 4)) + "px")
			.attr("y", yScale(1*((yMin + yMax) / 4)) + "px")
			.attr("class", "quadrantLabel hidden")
			.attr("id", "quadrant4Label")
			.text("Get Additional Help");

		svg.selectAll(".quadrantLabel")
			.attr("text-anchor", "middle")
			.attr("font-size", "20px")
			.attr("font-weight", "300")
			.moveToBack();

		// Setup tooltips
		setupBootstrapTooltips();
		//$('.conceptPoint').tooltip({
			//animation: true,
			//container:'body'});

	});
}

// Shows description for each quadrant of the scatterplot when hovered over, and give that quadrant a background
function showQuadrantInfo(quadrant) {
	// jQuery can't add a class to an SVG element with .addClass and .removeClass
	$(".quadrant").attr("class", "quadrant");
	$("#quadrant"+quadrant).attr("class","quadrant activeQuadrant");

	$(".quadrantInfo").addClass("hidden");
	$("#quadrantInfo"+quadrant).removeClass("hidden").show();
	$(".quadrantLabel").attr("class", "quadrantLabel hidden");
	$("#quadrant"+quadrant+"Label").attr("class", "quadrantLabel");
}

// Loads the mastery over time graph
function loadTimeGraph() {
	var timeGraph = c3.generate({
		bindto: "#timeGraph",
		data: {
			x: 'date',
			rows: [
				['Unit 1', 'Unit 2', 'Unit 3', 'Unit 4']
			],
			columns: [
				['date', 'Unit 1', 'Unit 2', 'Unit 3', 'Unit 4'],
			],
			type: 'line'
		},
		axis: {
			x: {
				type: 'category'
			},
			y: {
				max: 9.99,
				min: 0.01
			}
		}
	});

	// Have to load the data separately so we can get a callback to hide the loading spinner
	timeGraph.load(
		{
			x : 'date',
			url: '../scatterplot_recommender_stats/time_graph',
			groups: [
				['Unit 3', 'Unit 4']
			],
			type: 'line',
			done: function() {
				// Hide the spinner
				$("#timeGraphSection .spinner").hide();
				// Track legend interactions
				d3.selectAll("#timeGraph .c3-legend-item-event")
					.on("click", function(d) {
						track("clicked", "timeGraphToggle" + d.replace(/\s+/, ""));
					});
			}
		}
	);

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

// When feedback modal is shown
$("#feedbackModal").on("show.bs.modal", function(e) {
	// Hide low concepts list (it appears over this modal's backdrop and looks bad)
	$("#lowConceptBox").popover("hide");
});

// Set up sticky headers for the recommendation tables
function setupStickyHeaders() {
	$('table').stickyTableHeaders('destroy');
	$('table').stickyTableHeaders({fixedOffset: $("nav")});
	$(window).trigger('resize.stickyTableHeaders');
}

// We have to do this again when we dynamically load something with tooltips
function setupBootstrapTooltips() {
	$('[data-toggle="tooltip"], .conceptPoint, #recommendSection .panel-heading, .btn-info').tooltip({
		container: 'body',
		html: true
	});
}

// Called for basically every click interaction. Sends an xAPI statement with the given verb and object
// verbName is often "clicked". objectName should be string with no spaces, e.g. "viewSettingMasteryGraph"
function track(verbName, objectName) {
	console.log("Tracking: ",verbName,objectName);
	sendStatement({
		statementName: 'interacted',
		dashboardID: 'scatterplot_recommender_dashboard',
		dashboardName: 'Scatterplot Recommender Dashboard',
		verbName: verbName,
		objectName: objectName
	});
}

// When page is done loading, show our visualizations
$(function() {
	// Send dashboard launched statement
	sendStatement({
		statementName: 'dashboardLaunched',
		dashboardID: 'scatterplot_recommender_dashboard',
		dashboardName: 'Scatterplot Recommender Dashboard'
	});
	// Record start load time for duration for statement
	var loadTime = Date.now();
	// Send exited statement when student leaves page
	window.onbeforeunload = function() { sendStatement({
		statementName: 'dashboardExited',
		duration: centisecsToISODuration( (Date.now() - loadTime) / 10),
		dashboardID: 'scatterplot_recommender_dashboard',
		dashboardName: 'Scatterplot Recommender Dashboard'
	}); }

	// Determine if we need to show the jumbotron welcome (only show once for each dashboard, save shown state in localStorage)
	if (localStorage.getItem("scatterplot_recommender_welcome_shown") == "yes") {
		$("#mainContainer").removeClass("hidden").addClass("show");
	} else {
		$("#welcomeJumbotron").removeClass("hidden").addClass("show");
	}

	// Set up event listeners
	$("#jumbotronDismiss").click(function() {
		$("#"+$(this).attr("data-dismiss")).removeClass("show").hide();
		$("#mainContainer").removeClass("hidden").addClass("show");
		track("clicked", "continueButton");
		// Booleans don't store properly in all localStorage implementations
		localStorage.setItem("scatterplot_recommender_welcome_shown", "yes");
	});
	// Reload the scatterplot when scope changes, and when concept/chapter/unit changes
	$("input:radio[name=scatterplotScopeOption]").on("change", function() {
		loadConceptScatterplot();
		track("clicked","scatterplotScope"+$(this).val());
	});
	$("[name=scatterplotConceptSelector], [name=scatterplotChapterSelector], [name=scatterplotUnitSelector]").on("change", function() {
		loadConceptScatterplot();
		track("clicked",$(this).attr("name")+$(this).val());
	});
	// Track when recommendation tabs are switched, and udpate table sticky headers
	$("#recommendTabs").on('shown.bs.tab', function(e) {
		track("clicked", $('#recommendSection .tab-pane.active').attr("id") + "Section");
		$(window).trigger('resize.stickyTableHeaders');
	});
	// Top navigation pills
	$("[href=#scatterplot]").click(function() {
		$("#recommendSectionHolder, #scatterplotSection").show();
		$("#timeGraphSection").hide();
		// Deselect other options
		$("#pillNavigation li").removeClass("active");
		// Select this option
		$(this).parent("li").addClass("active");
		track("clicked","viewSetting"+$(this).attr("data-option"));
		return false;
	});
	$(function() {
		console.log("function called");
		d3.json("../student_skills_stats/skills", function(error, data) {
			var skills = data.student;
			// Sort skills weakest to strongest
			skills.sort(function(a,b) {
				return a.score - b.score;
			});
			lowest = skills[0];
			console.log(getSkillName(lowest.id));
			$("#suggestedHelp").html("<a href=\"student_skills#"+lowest.id+"\" data-option=\"SkillsRef\">You have a low "+getSkillName(lowest.id)+" score this week.<br/> Click here to see how to improve it.</a>");

		});

		function getSkillName(id){
			if(id === 'time'){
				return "time management";
			}
			if(id === 'activity'){
				return "online activity";
			}
			if(id === 'consistency'){
				return id;
			}
			if(id === 'awareness'){
				return "knowledge awareness";
			}
			if(id === 'deepLearning'){
				return "deep learning";
			}
			if(id === 'persistence'){
				return id;
			}
		};

	});

	$("[href=#timeGraph]").click(function() {
		// Hide low concepts list
		$("#lowConceptBox").popover("hide");
		$("#recommendSectionHolder, #scatterplotSection").hide();
		$("#timeGraphSection").show();
		// Deselect other options
		$("#pillNavigation li").removeClass("active");
		// Select this option
		$(this).parent("li").addClass("active");
		track("clicked","viewSetting"+$(this).attr("data-option"));
		return false;
	});

	// Hide time graph by default
	$("#timeGraphSection").hide();

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

	// Set up recommendation question groups
	setupQuestionGroups();

	// Hide this (loadRecommendations will show it when it's done loading)
	$("#recommendContainer").hide();

	// Load the concept scatterplot and time graph
	loadConceptScatterplot();
	loadTimeGraph();
});
