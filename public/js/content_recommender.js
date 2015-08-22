// Table helper function that uses d3 to create a table based on column information and data passed
function tableHelper(table, columns, data) {
	table.append('colgroup')
		.select('col')
		.data(columns)
		.enter()
		.append('col')
		.attr('class', function(d) { return d.cl; });

	table.append('thead').append('tr')
		.selectAll('th')
		.data(columns)
		.enter()
		.append('th')
		.html(function(d) { return d.head; });

	table.append('tbody')
		.selectAll('tr')
		.data(data).enter()
		.append('tr')
		.selectAll('td')
		.data(function(row, i) {
			return columns.map(function(c) {
				var cell = {};
				d3.keys(c).forEach(function(k) {
					cell[k] = typeof c[k] == 'function' ? c[k](row,i) : c[k];
				});
				return cell;
			});
		}).enter()
		.append('td')
		.html(function(d) { return d.html; })
		.attr('class', function(d) { return d.cl; });
}


// Questions table
function updateQuestionsTable() {
	// Load stats data
	// TODO don't use absolute url ref here
	d3.json("../content_recommender_stats/questions_table", function(error, data) {
		//Hide the loading spinner
		$("#questionsTable .spinner").hide();
		// TODO error checking
		var columns = [
			{ head: '', cl: 'questionNumberCell', html: function(d) { return d.question_id + "."; } },
			{ head: 'Question', cl: 'questionTextCell', html: function(d) { return d.text; } },
			{ head: 'Attempts', cl: 'questionAttemptsCell', html: function(d) { return d.attempts; } },
			{ head: 'Correct', cl: function(d) { return 'questionCorrectCell ' + (d.correct ? 'bg-success' : 'bg-danger'); }, html: function(d) { return d.correct ? '<span class="glyphicon glyphicon-ok"></span>' : '<span class="glyphicon glyphicon-remove"></span>'; } },
			// TODO absolute URL ref fix
			{ head: 'Launch Quiz', cl: 'questionLaunchCell', html: function(d) { return '<a data-toggle="modal" data-target="#questionLaunchModal" data-assessment="' + d.assessment_id + '" data-question="' + d.question_id + '" href="#"><span class="glyphicon glyphicon-log-in"></span></a>'; } }
		];
		var table = d3.select("#questionsTable table");
		tableHelper(table, columns, data);
	});
}

// Question Launch Modal
$("#questionLaunchModal").on("show.bs.modal", function(e) {
	$(this).find(".btn-primary").attr('href','../consumer.php?app=openassessments&assessment_id=' + $(e.relatedTarget).attr('data-assessment') + '&question_id=' + $(e.relatedTarget).attr('data-question'));
});
$("#questionLaunchContinueButton").click(function(e) {
	$("#questionLaunchModal").modal("hide");
});

// Videos table
function updateVideosTable() {
	// TODO absolute URL ref fix
	d3.csv("../csv/ChemPathVideos.csv", function(error, data) {
		//Hide the loading spinner
		$("#videosTable .spinner").hide();
		//console.log("csv", error, data);
		// Filter the data to only show required videos
		data = data.filter(function(d) { return d.optional != 1; });
		//var columns = [
			//{ head: '&nbsp;', cl: 'videoRefCell', html: function(d) { return d.chapter + "." + d.section + "." + d.group + "." + d.video; } },
			//{ head: 'Video Name', cl: 'videoTitleCell', html: function(d) { return d.attempts; } },
			//{ head: '% Watched', cl: '', html: function(d) { return Math.ceil(Math.random() * 100); } }
		//];
		var tbody = d3.select("#videosTable table tbody");
		var tr = tbody.selectAll("tr")
			.data(data)
			.enter()
			.append("tr")
			.attr("id", function(d) { return "videoRow"+d.ID; });

		tr.append("td")
			.html(function(d) { return d.chapter + "." + d.section + "." + d.group + "." + d.video; })
			.attr("class","videoRefCell");
		tr.append("td")
			// TODO absolute URL ref fix
			.html(function(d) { return '<a href="../consumer.php?app=ayamel&video_id=' + d.ID + '" target="_blank">' + d.title + '</a>'; })
			.attr("class","videoTitleCell");
		tr.append("td")
			.attr("class", "videoProgressCell")
			.append("input")
			.attr("type", "text")
			.attr("class", "progressCircle")
			.attr("disabled", "disabled")
			.attr("value", function() { return Math.ceil(Math.random() * 100); });
			
		// Don't stall the UI waiting for all these to finish drawing
		setTimeout(updateVideoProgressCircles, 1);
	});
}

function updateVideoProgressCircles() {
	$(".progressCircle").knob({
		'readOnly': true,
		'width': '45',
		'height': '45',
		'thickness': '.25',
		'fgColor': '#444',
		'format': function(v) { return v+"%"; }
	});
}

// Toggles on right of page to change what we're showing
function changeView(optionName, optionValue) {
	switch (optionName) {
		case "simple":
			console.log("Changing to simple view");
			break;
		case "more":
			console.log("Changing to more view");
			break;
		case "scatterplot":
			console.log("Changing to scatterplot view");
			break;
		case "masteryGraph":
			console.log("Changing to mastery graph view");
			break;
		case "all":
			console.log("Changing to all view");
			break;
		case "moreClass":
			if (optionValue == true) {
				console.log("Changing to more + class compare view");
			} else {
				console.log("Changing to more view");
			}
			break;
		case "scatterplotClass":
			if (optionValue == true) {
				console.log("Changing to scatterplot + class compare view");
			} else {
				console.log("Changing to scatterplot view");
			}
			break;
	}
}

// When page is done loading, show our visualizations
$(function() {
	// Send dashboard launched statement
	sendStatement({
		statementName: 'dashboardLaunched',
		dashboardID: 'content_recommender_dashboard',
		dashboardName: 'Content Recommender Dashboard'
	});

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
	
	// Load data
	updateQuestionsTable();
	updateVideosTable();
	// Go to simple view first
	changeView("simple");
});
