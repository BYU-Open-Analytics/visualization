// Related videos modal
$("#relatedVideosModal").on("show.bs.modal", function(e) {
	//$(this).find(".modal-body").html('<table class="table" id="relatedVideosModalTable"><tbody></tbody></table>');
	var data = getRelatedVideos($(e.relatedTarget).attr("data-assessment"), $(e.relatedTarget).attr("data-question"));
	$("#relatedVideosModalTable tbody").empty();
	var tbody = d3.select("#relatedVideosModalTable tbody");
	var tr = tbody.selectAll("tr")
		.data(data)
		.enter()
		.append("tr")
		.attr("id", function(d) { return "videoRow"+d["Video ID"]; });

	tr.append("td")
		.html(function(d) { var label = d.chapter + "." + d.section + "." + d.group + "." + d.video; return label.replace(/\.*$/, "a"); })
		.attr("class","videoRefCell");
	tr.append("td")
		// TODO absolute URL ref fix
		.html(function(d) { return '<a href="../consumer.php?app=ayamel&video_id=' + d["Video ID"] + '" data-track="ayamelLaunch' + d["Video ID"] + '" target="_blank">' + d.title + '</a>'; })
		.attr("class","videoTitleCell");
	// TODO put back in percentage watched, with actual data
	tr.append("td")
		.attr("class", "videoProgressCell advancedMore");
		//.append("input")
		//.attr("type", "text")
		//.attr("class", "progressCircle")
		//.attr("disabled", "disabled")
		//.attr("value", function() { return Math.ceil(Math.random() * 100); }); // TODO put actual percentage here
		
	// Track that the modal was shown
	track("clicked", "relatedVideos" + $(e.relatedTarget).attr('data-assessment') + '.' + $(e.relatedTarget).attr('data-question'));

	// Don't stall the UI waiting for all these to finish drawing
	//setTimeout(updateVideoProgressCircles, 1);
	refreshView();
});

// Returns videos for a given question
function getRelatedVideos(assessmentId, questionId) {
	var relatedVideos = [];
	for (var i=0; i<mappings.length; i++) {
		// See if this question's quiz is associated with this video
		if (mappings[i]["Open Assessments ID"] == assessmentId) {
			relatedVideos.push(mappings[i]);
		}
	}
	return relatedVideos;
}

// Helper function for recommendation question elements. Contains question/concept display, launch quiz button, and see associated videos button
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
	{"id": 1, "title":"Try these quiz questions", "tooltip":"<h4>You did not attempt these questions</h4> These questions were selected because you have not attempted them yet. This material will likely be on an upcoming exam, so to improve your score, it is recommended that you practice these questions."},
	{"id": 2, "title":"Watch videos before attempting these quiz questions", "tooltip":"<h4>You did not watch the videos for these questions</h4> These questions were selected because, based on your online activity, it seems you did not watch the videos before you attempted the quiz. To better learn the material, it is recommended that you watch the videos associated with these quiz questions."},
	{"id": 3, "title":"Find additional help", "tooltip":"<h4>You tried but did not succeed</h4> These questions were selected because you have spent time watching the videos, but for some reason, the quiz was still difficult for you. To learn this material, you may want to email the instructor, go into the TA lab, or ask a friend to help you."},
	{"id": 4, "title":"Practice these questions again", "tooltip":"<h4>You eventually got it right</h4> These questions were selected because even though you eventually answered it correctly, you missed them multiple times at first. These questions are recommended you to re-do to help you solidify your understanding."}
	];

	for (var i=0; i<groups.length; i++) {
		// Get the template
		var element = $("#templates #groupTemplate")[0].outerHTML;
		// Put our data values into it (this is a basic template idea from http://stackoverflow.com/a/14062431 )
		$.each(groups[i], function(k, v) {
			var regex = new RegExp("{" + k + "}", "g");
			element = element.replace(regex, v);
		});
		element = element.replace("groupTemplate", "recommend"+groups[i].id+"Group");
		$("#recommendationsAccordion").append(element);
	}
}

// Loads recommendations
function loadRecommendations(scopeOption, scopeGroupingId) {
	$("#recommendSection .spinner").show();
	$("#recommendSectionHolder p.lead, .recommendGroup").hide();
	$("#recommendSection").appendTo("#recommendSectionHolder");
	$("#recommendSection").removeClass("hidden").show();
	// Get scope with capital first letter for displaying
	var scopeOptionName = scopeOption.charAt(0).toUpperCase() + scopeOption.slice(1);
	$("#recommendationHeaderScopeLabel").text(scopeOptionName + " " + scopeGroupingId);

	// Scroll to the top of the section so recommendations are visible
	$("html, body").animate({ scrollTop: $("#recommendSectionHolder").offset().top - 55 }, "fast");

	// Get question recommendations for our scope and grouping ID (either unit number or concept number)
	d3.json("../scatterplot_recommender_stats/recommendations/" + scopeOption + "/" + scopeGroupingId, function(error, data) {
		$("#recommendSection .spinner").hide();
		if (!(data && typeof data == 'object' && "group1" in data) || error) {
			$("#recommendSection").html('<br><br><p class="lead">There was an error loading recommendations. Try reloading the dashboard.</p>');
			return;
		}
		// Flag to see if we've found the first question group with questions
		var nonemptyGroupFound = false
		// For each question group, go through and load the tables and do some formatting
		for (var i=1; i<5; i++) {
			$("#recommend"+i+"List").empty();
			d3.select("#recommend"+i+"List")
				.selectAll("tr")
				.data(data["group"+i])
				.enter()
				.append("tr")
				.attr("class", "advancedSimple")
				.html(function(d) { return questionElement(d); });
			$("#recommend"+i+"List").prepend($("#templates .recommendHeaderTemplate").clone());
			$("#recommend"+i+"CountBadge").text(data["group"+i].length);
			// Hide this group if there aren't any questions
			if (data["group"+i].length == 0) {
				$("#recommend"+i+"Group").hide();
			} else {
				// Show non-empty groups, but collapsed by default
				$("#recommend"+i+"Group").show()
				$("#recommend"+i).collapse("hide");
				// Otherwise select this group, if we haven't selected a previous nonempty group
				if (!nonemptyGroupFound) {
					$("#recommend"+i).collapse("show");
					nonemptyGroupFound = true;
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
	console.log(d);
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
	console.log($(this).attr("data-concept"));
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

	var scopeOption = "unit";
	var scopeGroupingId = 3;//$("[name=scatterplotUnitSelector]").val();

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
			.attr("title", "Video calculation explanation here!")
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
			.attr("title", "Mastery score calculation explanation here!")
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
				return "<b>" + d.id + " " + d.title + "</b><br>Video percentage: " + d.videoPercentage + "%<br>Mastery score: " + d.masteryScore;
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
			if ((data[i].masteryScore < 0.6 && data[i].videoPercentage < 6) || true) {
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
				.text(function(d) { return d.id + " " + d.title; })
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
				$('.popover-title').html('Unattempted Concepts <button type="button" onclick="$(\'#lowConceptBox\').popover(\'hide\');" class="close">&times;</button>');
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
	$(".advancedSimple, .advancedMore, .advancedMoreClass, .advancedScatterplot, .advancedScatterplotClass, .advancedMasteryGraph, .advancedAll").removeClass(s).addClass(h);
	switch (optionName) {
		case "simple":
			//console.log("Changing to simple view");
			$(".advancedSimple").removeClass(h).addClass(s);
			break;
		case "more":
			//console.log("Changing to more view");
			if (optionValue == true) {
				$(".advancedSimple, .advancedMore").removeClass(h).addClass(s);
			} else {
				$(".advancedSimple").removeClass(h).addClass(s);
			}
			// The More Class checkbox is dependent on this checkbox
			$("#advancedToggleMoreClass").prop("disabled", !optionValue).prop("checked", false);
			break;
		case "scatterplot":
			//console.log("Changing to scatterplot view");
			$(".advancedScatterplot").removeClass(h).addClass(s);
			// Have to manually do things in the svg chart
			$("#scatterplotSection .classPoint").hide();
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
				$("#scatterplotSection .classPoint").fadeIn();
			} else {
				//console.log("Changing to scatterplot view");
				$(".advancedScatterplot").removeClass(h).addClass(s);
				$("#scatterplotSection .classPoint").fadeOut();
			}
			break;
	}
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

	// Set up event listeneres
	$("#jumbotronDismiss").click(function() {
		$("#"+$(this).attr("data-dismiss")).hide();
		$("#mainContainer").removeClass("hidden").addClass("show");
		track("clicked", "continueButton");
	});
	// Filter concepts in left sidebar when unit selector changes
	$("[name=filterUnitSelector]").on("change", function() {
		filterConceptList();
		track("clicked","filterListUnit"+$(this).val());
	});
	// Reload the scatterplot when scope changes, and when concept/chapter/unit changes
	$("input:radio[name=scatterplotScopeOption]").on("change", function() {
		loadScatterplot();
		track("clicked","scatterplotScope"+$(this).val());
	});
	$("[name=scatterplotConceptSelector], [name=scatterplotChapterSelector], [name=scatterplotUnitSelector]").on("change", function() {
		loadScatterplot();
		track("clicked",$(this).attr("name")+$(this).val());
	});
	// Reload the recommendations when scope changes, and when concept/chapter/unit changes
	$("input:radio[name=recommendScopeOption]").on("change", function() {
		loadRecommendations();
		track("clicked","recommendScope"+$(this).val());
	});
	$("[name=recommendConceptSelector], [name=recommendChapterSelector], [name=recommendUnitSelector]").on("change", function() {
		loadRecommendations();
		track("clicked",$(this).attr("name")+$(this).val());
	});
	// Reload the mastery graph when scope changes, and when chapter/unit changes
	$("input:radio[name=masteryGraphScopeOption]").on("change", function() {
		loadMasteryGraph($(this).val());
		track("clicked","masteryGraphScope"+$(this).val());
	});
	$("[name=masteryGraphChapterSelector], [name=masteryGraphUnitSelector]").on("change", function() {
		loadMasteryGraph();
		track("clicked",$(this).attr("name")+$(this).val());
	});
	// Track when recommendation tabs are switched, and udpate table sticky headers
	$("#recommendTabs").on('shown.bs.tab', function(e) {
		track("clicked", $('#recommendSection .tab-pane.active').attr("id") + "Section");
		$(window).trigger('resize.stickyTableHeaders');
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
	// Set up bootstrap tooltips
	setupBootstrapTooltips();

	//$( "#recommendAccordion" ).accordion({
      //heightStyle: "fill"
    //});

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

	// First, we have to load data mappings for quiz questions/videos/concepts/dates
	d3.csv("../csv/mappings.csv", function(error, data) {
		mappings = data;
		// Then we can load other things
		//loadConcepts();
		//loadRecommendations();
		//loadConceptScores();
		// Don't load or show scatterplot for now
		//loadScatterplot();
		loadConceptScatterplot();
	});
	// Go to simple view first
	changeView("simple");
});
