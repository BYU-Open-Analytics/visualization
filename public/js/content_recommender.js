// Table helper function that uses d3 to create a table based on column information and data passed
function tableHelper(table, columns, data) {
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
			{ head: 'Question', cl: '', html: function(d) { return d.text; } },
			{ head: 'Attempts', cl: '', html: function(d) { return d.attempts; } },
			{ head: 'Correct', cl: function(d) { return d.correct ? 'bg-success' : 'bg-danger'; }, html: function(d) { return d.correct ? '<span class="glyphicon glyphicon-ok"></span>' : '<span class="glyphicon glyphicon-remove"></span>'; } }
		];
		var table = d3.select("#questionsTable table");
		tableHelper(table, columns, data);
	});
}

// Videos table
function updateVideosTable() {
	d3.csv("../csv/ChemPathVideos.csv", function(error, data) {
		//Hide the loading spinner
		$("#videosTable .spinner").hide();
		console.log("csv", error, data);
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
			.html(function(d) { return d.title; })
			.attr("class","videoTitleCell");
		tr.append("td")
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


// When page is done loading, show our visualizations
$(function() {
	//updateOpenAssessmentStats();
	//updateAyamelStats();
	//updateConfidencePie();
	//setupConfidenceAverage();
	updateQuestionsTable();
	updateVideosTable();
});
