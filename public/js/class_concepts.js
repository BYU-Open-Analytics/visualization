$(function(){

  d3.json("../class_stats/concepts", function(error, conceptList) {
    var masteryColorScale = d3.scale.linear()
				.domain([0, 3, 6, 10])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);

    var tbody = d3.select("#resultList tbody");
    var resultListRows = tbody.selectAll("tr");
    resultListRows = resultListRows.data(conceptList)
      .enter()
      .append("tr");

      //Lecture number
      resultListRows.append("td")
        .html(function(d){ return d.id; });

      //Concept Name
      resultListRows.append("td")
        .html(function(d) {return d.title;});

      //Mastery Score
      resultListRows.append("td")
        .html(function(d) { return d.history.average})
        .style("color", function(d) { return masteryColorScale(d.history.average);});
  });
});

function loadConceptScatterplot(){
  // Show the spinner while loading
  $("#scatterplotSection .spinner").show();
  $("#recommendSectionHolder p.lead").show();
	$("#recommendSection").hide();
  $("#lowConceptBox").popover("hide");

  // Get what scope we're filtering by (unit, chapter, or concept)
  scopeGroupingId = $("[name=scatterplotUnitSelector]").val();
  	d3.json("../class_stats/concepts/"+scopeGroupingId, function(error, data) {
  		var percentMax = data[0].max;
  		data.splice(0,1);
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

  //Add the biggest percentage here in place of 10
  		var xMax = percentMax;
  		var yMax = 10;
  		var xMin = 0;
  		var yMin = 0;

  		//Create scale functions
  		// Don't want dots overlapping axis, so add in buffer to data domain
  		var xScale = d3.scale.linear()
      //Add the biggest percentage here in place of 10
  			 .domain([0, percentMax])
  			 .range([0, width]);
  		var yScale = d3.scale.linear()
  			 .domain([0, 10])
  			 .range([height, 0]);
  		//Color scale
  		var colorScale = d3.scale.linear()
  				.domain([0, 4, 8, 10])
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
  			.attr("title", "The average percentage of videos for this concept that are viewed across by all students.")
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
  			.attr("title", "The average mastery score for all students on this concept on a scale from 0 (low) to 10 (high). Mastery score accounts for students clicking \"Show Answer\" and for how many attempts a question takes them.")
  			.text("Mastery Score");
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
    			.attr("height", height / 2 + "px");

    		// Quadrant labels
    		svg.append("text")
    			.attr("x", xScale(3*((xMin + xMax) / 4)) + "px")
    			.attr("y", yScale(3*((yMin + yMax) / 4)) + "px")
    			.attr("class", "quadrantLabel hidden")
    			.attr("id", "quadrant1Label")
    			.text("Material Learned  ");
    		svg.append("text")
    			.attr("x", xScale(1*((xMin + xMax) / 4)) + "px")
    			.attr("y", yScale(3*((yMin + yMax) / 4)) + "px")
    			.attr("class", "quadrantLabel hidden")
    			.attr("id", "quadrant2Label")
    			.text("Learned Without Videos");
    		svg.append("text")
    			.attr("x", xScale(1*((xMin + xMax) / 4)) + "px")
    			.attr("y", yScale(1*((yMin + yMax) / 4)) + "px")
    			.attr("class", "quadrantLabel hidden")
    			.attr("id", "quadrant3Label")
    			.text("");
    		svg.append("text")
    			.attr("x", xScale(3*((xMin + xMax) / 4)) + "px")
    			.attr("y", yScale(1*((yMin + yMax) / 4)) + "px")
    			.attr("class", "quadrantLabel hidden")
    			.attr("id", "quadrant4Label")
    			.text("Students Need Help");

    		svg.selectAll(".quadrantLabel")
    			.attr("text-anchor", "middle")
    			.attr("font-size", "20px")
    			.attr("font-weight", "300");

  		//Create circles
  		var dots = svg.selectAll("circle")
  		   .data(data);

  		dots.enter()
  			.append("circle")
  			.attr("class", "conceptPoint")
  			.attr("r", "6px")
  			.attr("title", function(d) {
  				return "<b>" + d.title + "</b><br>Video percentage: " + d.history.percent.toPrecision(3) + "%<br>Mastery score: " + Number(d.history.average).toPrecision(3) ;
  			})
  			.attr("cx", function(d) {
  				return xScale(d.history.percent);
  			})
  			.attr("cy", function(d) {
  				return yScale(d.history.average);
  			})
  			.attr("fill", function(d) {
  				return colorScale(d.history.average );
  			})
  			.on('click', showPointConceptRecommendations)
  			;

  		dots.exit()
  			.remove();

  		// Get all the concepts that would be overlapping in the bottom corner
  		var lowConcepts = [];
  		for (var i=0; i < data.length; i++) {
  			if ((data[i].history.average < 0.6 && data[i].history.percent < 6)) {
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

  			$(".list-group-item").remove();
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
//  			$(document).on("click", ".lowConceptsList li", showLowConceptRecommendations);

  		}

  		// Setup tooltips
  		setupBootstrapTooltips();
  		//$('.conceptPoint').tooltip({
  			//animation: true,
  			//container:'body'});

  	});


}

$("[name=scatterplotConceptSelector], [name=scatterplotChapterSelector], [name=scatterplotUnitSelector]").on("change", function() {
  loadConceptScatterplot();
  //track("clicked",$(this).attr("name")+$(this).val());
});
function showQuadrantInfo(quadrant) {
	// jQuery can't add a class to an SVG element with .addClass and .removeClass
	$(".quadrant").attr("class", "quadrant");
	$("#quadrant"+quadrant).attr("class","quadrant activeQuadrant");

	$(".quadrantInfo").addClass("hidden");
	$("#quadrantInfo"+quadrant).removeClass("hidden").show();
	$(".quadrantLabel").attr("class", "quadrantLabel hidden");
	$("#quadrant"+quadrant+"Label").attr("class", "quadrantLabel");
}

function setupBootstrapTooltips() {
	$('[data-toggle="tooltip"], .conceptPoint').tooltip({
		container: 'body',
		html: true
	});
}
function showPointConceptRecommendations(d) {
	// Track that the student clicked this
//track("clicked","conceptPoint"+d.id);
	// Hide low concepts list
	$("#lowConceptBox").popover("hide");
	// Deselect any concept in the low concepts list
	$(".lowConceptsList li").removeClass("active");
	// Deslect other points, and select this one and move it to the front of the view hierarchy
	$(".selectedConceptPoint").attr("class", "conceptPoint");
	$(d3.event.currentTarget).attr("class", "conceptPoint selectedConceptPoint");
	// Load recommendations for this concept
	var conceptId = d.id;
	loadRecommendations("concept", conceptId);
}
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
	loadQuestionRecommendations(scopeOption, scopeGroupingId); // This one takes the longest, so it will hide the spinner

}
function setupStickyHeaders() {
	$('table').stickyTableHeaders('destroy');
	$('table').stickyTableHeaders({fixedOffset: $("nav")});
	$(window).trigger('resize.stickyTableHeaders');
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

loadConceptScatterplot();
$("#recommendContainer").hide();
setupQuestionGroups();
setupBootstrapTooltips();
