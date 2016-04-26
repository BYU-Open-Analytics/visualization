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

function loadQuiz(scopeOption, scopeGroupingId){
	//Do this if we want to get statistics for the practice quizzes
	// d3.json("../scatterplot_recommender_stats/videoRecommendations/" + scopeOption + "/" + scopeGroupingId, function(error, data) {
	// 	$('#recommendSection').append('<span>&nbsp;</span>');
	// });
	$('#quiz-launch').empty()
	quiz = getRelatedQuiz(scopeGroupingId);
	if(quiz === 0){
		$('#quiz-launch').html('<span class="glyphicon glyphicon-log-in"></span> &nbsp; Launch Quiz &nbsp;');
		$('#quiz-launch').on('click', function(){
				$('#questionLaunchModal').modal('show')
		});
	}
	else{
		$('#quiz-launch').attr('href','../consumer.php?app=openassessments&assessment_id=' + quiz[0]["OA Quiz ID"])
		$('#quiz-launch').html('<span class="glyphicon glyphicon-log-in"></span> &nbsp; Launch Quiz &nbsp;')
		$('#quiz-launch').on('click', function(){
			$("#questionLaunchModal").modal("hide");
			track("clicked", "confirmLaunchQuiz" + scopeGroupingId);
		});
	}
	refreshView();
};

function loadVideos(scopeOption, scopeGroupingId){
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
		$('#recommendSection').append('<span>&nbsp;</span>');
	});

	refreshView();
};

// Returns videos for a given concept from the mappings
function getRelatedVideos(conceptId) {
	var relatedVideos = [];
	for (var i=0; i<mappings.length; i++) {
		// See if this question's quiz is associated with this video
		if (mappings[i]["Lecture Number"] == conceptId) {
			relatedVideos.push(mappings[i]);
		}
	}
	return relatedVideos;
}
function getRelatedResources(conceptId) {
	var relatedResources = [];
	for (var i=0; i<resourceMappings.length; i++) {
		// See if this question's quiz is associated with this video
		if (resourceMappings[i]["Lecture Number"] == conceptId) {
			relatedResources.push(resourceMappings[i]);
		}
	}
	return relatedResources;
}
function getRelatedQuiz(conceptId) {
	var relatedQuiz = [];
	for (var i=0; i<questionMappings.length; i++) {
		// See if this question's quiz is associated with this video
		if (questionMappings[i]["Lecture Number"] == conceptId) {
			relatedQuiz.push(questionMappings[i]);
			return relatedQuiz;
		}
	}
	return 0;
}
// Called when a concept is clicked
function filterConceptClick(d) {
	// See if it was already active when clicked, and hide the recommendations if it is
	if ($(d3.event.currentTarget).hasClass("active")) {
		$("#recommendSection").removeClass("inList").slideUp("fast");
		setTimeout(function() {
			$("#filterList .active").removeClass("active");
		}, 200);
		return;
	}
	// Make the currently active concept button not active
	$("#filterList .active").removeClass("active");
	// Then make this one active
	$(d3.event.currentTarget).addClass("active");
	// Track the click
	track("clicked","conceptList"+d.id);
	// Then load recommendations for the concept associated with the clicked concept button
	loadRecommendations("concept", d.id);
	//$("#recommendSection").appendTo($(d3.event.currentTarget));
	$("#recommendSection").removeClass("inList").hide();
	$("#recommendSection").insertAfter($(d3.event.currentTarget));
	setTimeout(function() {
		$("#recommendSection").removeClass("hidden").slideDown("fast");
		setTimeout(function() {
			$("#recommendSection").addClass("inList");
		}, 140);
	}, 300);
	// Scroll to the top of the clicked element so recommendations are visible
	$("html, body").animate({ scrollTop: $(d3.event.currentTarget).offset().top - 55 }, "fast");
}

// Loads scores for all concepts, which are used in the filter navigation sidebar
function loadConceptScores() {
	$("#filterSection .spinner").show();
	$("#filterLoadingContainer").hide();
	// Get the list of all concepts and their scores
	d3.json("../content_recommender_stats/masteryGraph/all/all", function(error, data) {
		$("#filterSection .spinner").hide();
		$("#filterLoadingContainer").show();

		// Some basic error handling
		if (!(data && typeof data == 'object') || error) {
			$("#filterLoadingContainer").html('<p class="lead">There was an error loading concept scores. Try reloading the dashboard.</p>');
			return;
		}

		//Color scale
		var colorScale = d3.scale.linear()
				.domain([0, 3.3, 6.6, 10])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);

		// Remove any existing concepts
		$("#filterList .filterListConcept").remove();

		//Create tooltips
		var tip = d3.tip().attr('class', 'd3-tip').offset([-10,0]).html(function(d) { return "Score: " + d.score + ". Click to view recommendations."; });

		// Create element for each concept
		var conceptList = d3.select("#filterList");
		var concepts = conceptList.selectAll(".filterListConcept")
			.data(data)
			.enter()
			.append("a")
			.on("click", filterConceptClick)
			//.attr("data-toggle", "tooltip")
			//.attr("data-placement", "right")
			//.attr("title", function(d) { return "Score: " + d.score; })
			.attr("class", function(d) { return "filterListConcept unit" + d.unit + "Concept"; });

		var labels = concepts.append("div")
			.attr("class", "filterListItemText")
			.html(function(d) { return d.display; });

		var scoreLabels = concepts.append("div")
			.attr("class", "filterListItemScore")
			.html(function(d) { return d.score + ' <small>/ 10</small>'; });

		// Progress bar-like display at bottom of each concept that shows mastery score
		var rects = concepts.append("span")
			.attr("class", "conceptProgressBar")
			.style("width", 0)//function(d) { return Math.max(4, d.score * 10) + "%"; })
			//.style("background-color", function(d) { return d.score >= 6 ? "#5cb85c" : d.score >= 4 ? "#f0ad4e" : "#d9534f"; })
			.style("background", function(d) { return colorScale(d.score); });

		animateConceptScores();
		setupBootstrapTooltips();
		// Now we've got all concepts. Filter to current unit by default
		filterConceptList();
	});
}

// Filters concepts to a given unit
function filterConceptList() {
	var selectedUnit = $("[name=filterUnitSelector]").val();
	// Hide all concepts
	$(".filterListConcept").hide();
	// And then show the ones for the selected unit
	$(".unit" + selectedUnit + "Concept").show();
	// Change the "All concepts for this unit" item to have the current unit number
	$("#filterListUnitName").text(selectedUnit);
	animateConceptScores();
	// Hide recommendations
	$("#recommendSection").hide();
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
function loadAdditionalResources(concept){
	var webResources = getRelatedResources(concept);

	$("#relatedResourcesTable tbody").empty();
	var tbody = d3.select("#relatedResourcesTable tbody");
	var tr = tbody.selectAll("tr")
		.data(webResources)
		.enter()
		.append("tr")
		.attr("id", function(d) { return "webResourcesRow"+d["Concept Title"]; });

	tr.append("td")
		.html(function(d) { return '<a href="' + d["Resource Link"] + '" data-track="webResourceLaunchID' + d["WebResourceID"] + '" target="_blank">' + d["Concept Title"] + '</a>'; })
		.attr("class","resourceLinkCell");
	refreshView();
}
// Loads recommendations
function loadRecommendations(scopeOption, scopeGroupingId) {
	loadAdditionalResources(scopeGroupingId);
	loadVideos(scopeOption, scopeGroupingId);
	loadQuiz(scopeOption, scopeGroupingId);
	$("#recommendSection .spinner").show();
	$("#recommendContainer").hide();
	// Get scope with capital first letter for displaying
	var scopeOptionName = scopeOption.charAt(0).toUpperCase() + scopeOption.slice(1);
	$("#recommendationHeaderScopeLabel").text(scopeOptionName + " " + scopeGroupingId);

	// Get question recommendations for our scope and grouping ID (either unit number or concept number)
	d3.json("../content_recommender_stats/recommendations/" + scopeOption + "/" + scopeGroupingId, function(error, data) {
		$("#recommendSection .spinner").hide();
		$("#recommendContainer").show();
		if (!(data && typeof data == 'object' && "group1" in data) || error) {
			$("#recommendContainer").html('<br><br><p class="lead">There was an error loading recommendations. Try reloading the dashboard.</p>');
			return;
		}
		// Flag to see if we've found the first question group with questions
		var nonemptyGroupFound = false
		// Set up sticky table headers
		setupStickyHeaders();
		// Set up the show more/show less for the question texts
		$(".recommendQuestionTextContainer").shorten({
			moreText: 'See more',
			lessText: 'See less',
			showChars: 180
		});
		// Go to the first visible question group

	});
}

// Loads the scatterplot
function loadScatterplot() {
	// Show the spinner while loading
	$("#scatterplotSection .spinner").show();
	// Determine what current scope and grouping id (concept/chapter/unit id) are
	var scopeOption = $("input[name=scatterplotScopeOption]:checked").val();
	var scopeGroupingId = "";
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

	d3.csv("../content_recommender_stats/scatterplot/" + scopeOption + "/" + scopeGroupingId, coerceTypes, function(error, data) {
		$("#scatterplotSection .spinner").hide();
		if (error != null) {
			console.log("Scatterplot ERROR: ", error);
		}

		//Width and height
		var margin = {top: 10, right: 10, bottom: 50, left: 55},
		    height = 450 - margin.top - margin.bottom,
		    width = 500 - margin.left - margin.right;

		var xMax = 100;//d3.max(data, function(d) { return d.x; });
		var yMax = 10;//d3.max(data, function(d) { return d.y; });
		var xMin = 0;//d3.min(data, function(d) { return d.x; });
		var yMin = 0;//d3.min(data, function(d) { return d.y; });

		//Create scale functions
		// Don't want dots overlapping axis, so add in buffer to data domain
		var xScale = d3.scale.linear()
			 .domain([0, 100])
			 .range([0, width]);
		var yScale = d3.scale.linear()
			 .domain([0, 10])
			 .range([height, 0]);

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

		//Data elements are as follows (from ContentRecommenderStatsController.php, in scatterplotAction())
		//$headerRow = ["group", "quiz_number", "question_number", "x", "y"];

		//Create tooltips
		//var tip = d3.tip().attr('class', 'd3-tip').offset([-10,0]).html(function(d) { return d.assessment_id + "." + d.question_id; });
		var tip = d3.tip().attr('class', 'd3-tip').offset([-10,0]).html(function(d) { return d.group == "student" ? "Question " + d.quiz_number + "." + d.question_number : ""; });
		svg.call(tip);

		//Create circles
		var dots = svg.selectAll("circle")
		   .data(data);

		dots.enter()
		   .append("circle")
		   .attr("cx", function(d) {
				return xScale(d.x);
		   })
		   .attr("cy", function(d) {
				return yScale(d.y);
		   })
		   .attr("r", function(d) {
			   	return d.group == "student" ? "6px" : "2px";
		   })
		   .attr("fill", function(d) {
			   	return d.group == "student" ? "#337ab7" : "gray";
		   })
		   .attr("class", function(d) {
			   	return d.group + "Point";
		   })
		   .on('mouseover', tip.show)
		   .on('mouseout', tip.hide);

		dots.exit()
		    .remove();

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

		// Make sure that student points show over class points and quadrant lines
		svg.selectAll(".studentPoint").moveToFront();

		//Create custom x axis labels
		svg.append("text")
			.attr("x", xScale(xMin) + "px")
			.attr("y", (height + 20) + "px")
			.attr("text-anchor", "start")
			.text("Low");
		svg.append("text")
			.attr("x", xScale((xMin + xMax) / 2) + "px")
			.attr("y", (height + 40) + "px")
			.attr("text-anchor", "middle")
			.text("Video Time");
		svg.append("text")
			.attr("x", xScale(xMax) + "px")
			.attr("y", (height + 20) + "px")
			.attr("text-anchor", "end")
			.text("High");
		//Create custom y axis labels
		svg.append("text")
			.attr("text-anchor", "start")
			.attr("transform", "translate(-20, " + yScale(yMin) + ")rotate(270)")
			.text("Low");
		svg.append("text")
			.attr("text-anchor", "middle")
			.attr("transform", "translate(-40, " + yScale((yMin + yMax) / 2) + ")rotate(270)")
			.text("Quiz Question Attempts");
		svg.append("text")
			.attr("text-anchor", "end")
			.attr("transform", "translate(-20, " + yScale(yMax) + ")rotate(270)")
			.text("High");

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

		refreshView();
	});

	function coerceTypes(d) {
		d.x = +d.x;
		d.y = +d.y;
		return d;
	}
}

// Shows description for each quadrant of the scatterplot when hovered over, and give that quadrant a background
function showQuadrantInfo(quadrant) {
	// jQuery can't add a class to an SVG element with .addClass and .removeClass
	$(".quadrant").attr("class", "quadrant");
	$("#quadrant"+quadrant).attr("class","quadrant activeQuadrant");

	$(".quadrantInfo").addClass("hidden");
	$("#quadrantInfo"+quadrant).removeClass("hidden").show();
}

function filterRecommendationsToConcept(d,i) {
	var conceptId = d.id;
	// Change back to recommendations view
	// Set Filter for recommendations to this concept
	// Choose concept radio button
	$("#recommendConceptButton").click();
	$("input[name=recommendScopeOption][value='concept']").prop("checked",true);
	// Choose concept in dropdown list
	$("#recommendConceptSelector").val(conceptId);
	loadRecommendations();
	$("#advancedToggleSimple").click();
}

// Loads the mastery graph
function loadMasteryGraph() {
	// Show the spinner while loading
	$("#masteryGraphSection .spinner").show();
	// Determine what current scope and grouping id (concept/chapter/unit id) are
	var scopeOption = $("input[name=masteryGraphScopeOption]:checked").val();
	var scopeGroupingId = "all";
	switch (scopeOption) {
		case "chapter":
			scopeGroupingId = $("[name=masteryGraphChapterSelector]").val();
			break;
		case "unit":
			scopeGroupingId = $("[name=masteryGraphUnitSelector]").val();
			break;
	}
	//console.log(scopeOption, scopeGroupingId);
	// Default scope is chapter
	scopeOption = scopeOption != null ? scopeOption : "chapter";
	// TODO don't use absolute url ref here
	d3.json("../content_recommender_stats/masteryGraph/" + scopeOption + "/" + scopeGroupingId, function(error, data) {
		$("#masteryGraphSection .spinner").hide();

		//Width and height
		var margin = {top: 40, right: 10, bottom: 10, left: 180},
		    width = 550 - margin.left - margin.right,
		    height = (35 * data.length + 100) - margin.top - margin.bottom;

		var x = d3.scale.linear()
			.range([0, width]);
		var y = d3.scale.ordinal()
			.rangeRoundBands([0, height], .1);

		var xAxis = d3.svg.axis()
			.scale(x)
			.orient("top");
		var yAxis = d3.svg.axis()
			.scale(y)
			.orient("left");

		//Color scale
		var colorScale = d3.scale.linear()
				.domain([0, 3.3, 6.6, 10])
				.range(["red", "orange", "yellow", "green"]);

		//Remove old chart and tooltips
		$("#masteryGraphSection svg").remove();
		$(".d3-tip").remove();
		//Create SVG element with padded container for chart
		var chart = d3.select("#masteryGraphSection .svgContainer")
			.append("svg")
			.attr("height", height+margin.top+margin.bottom)
			.attr("width", width+margin.left+margin.right)
			.append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

		x.domain([0, 10]);
		y.domain(data.map(function(d) { return d.display; }));

		//Create tooltips
		var tip = d3.tip().attr('class', 'd3-tip').offset([-10,0]).html(function(d) { return "Score: " + d.score + ". Click to view recommendations."; });
		chart.call(tip);

		var bars = chart.selectAll(".bar")
			.data(data)
			.enter().append("g")
			.attr("transform", function(d) { return "translate(0, " + y(d.display) + ")"; })
			.attr("class", "bar");

		var rects = bars.append("rect")
			//.attr("x", function(d) { return x(d.name); })
			//x and width are temporary, but must have initial values for transition to work
			.attr("x", 0 + "px")
			.attr("width", 0 + "px")
			.attr("height", y.rangeBand())
			.attr("fill", function(d) { return colorScale(d.score); })
			.attr("cursor", "pointer")
			.attr("data-toggle", "modal")
			.attr("data-target", "#openAssessmentStatsModal")
			.attr("data-name", function(d) { return d.id; })
			.on('click', filterRecommendationsToConcept)
			.on('mouseover', tip.show)
			.on('mouseout', tip.hide);

		// Y axis with rotated and wrapped labels
		chart.append("g")
			.attr("class", "axis y")
			//.attr("transform", "translate(" + width + ",0)")
			.call(yAxis)
			.selectAll(".tick text")
			.attr("dy", "-.3em")
			.attr("dx", "-1em")
			.call(wrap, 170);
		// X axis
		chart.append("g")
			.attr("class", "x axis")
			.call(xAxis)
		chart.selectAll(".axis.y .tick text")
			.style("text-anchor", "end")
			//.attr("transform", function(d) {
				//return "rotate(-90)"
			//})
			.selectAll("tspan");

		rects.transition()
			.duration(500)
			.delay(function(d, i) { return i * 10; })
			//.attr("x", function(d) { return width - x(d.score); })
			.attr("width", function(d) { return d.score > 0 ? x(d.score) : 10; });
		//refreshView();
	});

	function coerceTypes(d) {
		d.x = +d.x;
		d.y = +d.y;
		return d;
	}
}

// Function to make the mastery graph bar chart animate
function animateConceptScores() {
	$(".conceptProgressBar").css("width","0px");
	d3.selectAll(".conceptProgressBar")
		.transition()
		.duration(500)
		//.delay(function(d, i) { return i * 10; })
		.style("width", function(d) { return Math.max(4, d.score * 10) + "%"; });
}
// Function to make the mastery graph bar chart animate
function animateMasteryGraph() {
	// TODO 360 is a magic number
	var x = d3.scale.linear()
		.range([0, 360])
		.domain([0, 10]);
	d3.selectAll("#masteryGraphSection svg .bar rect")
		.attr("width", 0)
		.transition()
		.duration(500)
		.delay(function(d, i) { return i * 10; })
		.attr("width", function(d) { return d.score > 0 ? x(d.score) : 10;  });
}

// Helper function from http://bl.ocks.org/mbostock/7555321
function wrap(text, width) {
  text.each(function() {
    var text = d3.select(this),
        words = text.text().split(/\s+/).reverse(),
        word,
        line = [],
        lineNumber = 0,
        lineHeight = 1.1, // ems
        y = text.attr("y"),
        dy = parseFloat(text.attr("dy")),
        dx = parseFloat(text.attr("dx")),
        tspan = text.text(null).append("tspan").attr("x", 0).attr("y", y).attr("dy", dy + "em");
    while (word = words.pop()) {
      line.push(word);
      tspan.text(line.join(" "));
      if (tspan.node().getComputedTextLength() > width) {
        line.pop();
        tspan.text(line.join(" "));
        line = [word];
        tspan = text.append("tspan").attr("x", 0).attr("y", y).attr("dy", ++lineNumber * lineHeight + dy + "em").attr("dx", dx + "em").text(word);
      }
    }
  });
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
	// console.log("Tracking: ",verbName,objectName);
	sendStatement({
		statementName: 'interacted',
		dashboardID: 'content_recommender_dashboard',
		dashboardName: 'Content Recommender Dashboard',
		verbName: verbName,
		objectName: objectName
	});
}

// When page is done loading, show our visualizations
$(function() {
	// Send dashboard launched statement
	sendStatement({
		statementName: 'dashboardLaunched',
		dashboardID: 'content_recommender_dashboard',
		dashboardName: 'Content Recommender Dashboard'
	});
	// Record start load time for duration for statement
	var loadTime = Date.now();
	// Send exited statement when student leaves page
	window.onbeforeunload = function() { sendStatement({
		statementName: 'dashboardExited',
		duration: centisecsToISODuration( (Date.now() - loadTime) / 10),
		dashboardID: 'content_recommender_dashboard',
		dashboardName: 'Content Recommender Dashboard'
	}); }

	// Determine if we need to show the jumbotron welcome (only show once for each dashboard, save shown state in localStorage)
	if (localStorage.getItem("content_recommender_welcome_shown") == "yes") {
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
		localStorage.setItem("content_recommender_welcome_shown", "yes");
		animateConceptScores();
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

	// Set up event listener for links that we want to track
	$(document).on("click", "[data-track]", function() {
		track("clicked", $(this).attr("data-track"));
	});
	// Add feedback button to navbar (we don't want this in the phtml template, since not all pages will have feedback modal or js)
	$("#navbarButtonHolder").append('<button class="btn btn-primary" data-toggle="modal" data-track="feedbackButton" data-target="#feedbackModal"><span style="top: 3px;" class="glyphicon glyphicon-comment"></span>&nbsp; Send Feedback</button>')
	// Bind feedback submit button click event
	$("#feedbackSendButton").click(sendFeedback);

	// Hide this (loadRecommendations will show it when it's done loading)
	$("#recommendContainer").hide();

	// First, we have to load data mappings for quiz questions/videos/concepts/dates (do we really?)
	d3.csv("../csv/videos.csv", function(error, data) {
		mappings = data;
		// Then we can load other things
		//loadRecommendations();
		loadConceptScores();
		// Don't load or show scatterplot for now
		//loadScatterplot();
	});
	//load resources
	d3.csv("../csv/webresources.csv", function(error, data) {
		resourceMappings = data;
		// loadConceptScores();
	});
	d3.csv("../csv/questionsNotGraded.csv", function(error, data) {
		questionMappings = data;
	});
	// Go to simple view first
	changeView("simple");
});
