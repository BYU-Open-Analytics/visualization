// Circle fun visualization
var circleData = [];
var circleScale = 10.0;
function updateCircles() {
	var circles = d3.select("#circleFun svg").selectAll("circle").data(circleData);
	
	circles.enter()
		.append("circle")
		.style("fill", function() {
			return "hsl(" + Math.random() * 360 + ",100%, 50%)";
		})
		.attr("r", 0)
		.attr("cy", 40)
		.attr("cx", function(d, i) { return (i * 55) + 50; });

	circles.transition()
		.duration(750)
		.delay(function(d, i) { return i * 10; })
		.attr("r", function(d) { return Math.sqrt(d * circleScale); });

	circles.exit().remove();
}

$("#circleDataSize").on("input",function() {
	var dataSize = $(this).val();
	$("#circleDataSizeLabel").text(dataSize);
	circleData = [];
	while(circleData.length < dataSize) {
		circleData.push(Math.random() * 100);
	}
	updateCircles();
});

$("#circleDataSize").trigger("input");

function updateBarGraph() {
	// Simple bar graph visualization
	var barGraphData = [
	  {name: "Locke",    value:  4},
	  {name: "Reyes",    value:  8},
	  {name: "Ford",     value: 15},
	  {name: "Jarrah",   value: 16},
	  {name: "Shephard", value: 23},
	  {name: "Kwon",     value: 32}
	];

	var margin = {top: 20, right: 30, bottom: 30, left: 40},
	    height = $("#barGraph svg").height() - margin.left - margin.right,
	    width = $("#barGraph svg").width() - margin.top - margin.bottom;

	var chart = d3.select("#barGraph svg").append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");
	
	var bars = chart.selectAll(".bar").data(barGraphData);

	var y = d3.scale.linear()
		.domain([0, d3.max(barGraphData, function(d) { return d.value; })])
		.range([height, 0]);
	
	var x = d3.scale.ordinal()
		.domain(barGraphData.map(function(d) { return d.name; }))
		.rangeBands([0, width]);
	
	var colorScale = d3.scale.linear()
		.domain([0, d3.max(barGraphData, function(d) { return d.value; })])
		.range([0,360]);
	
	var xAxis = d3.svg.axis()
		.scale(x)
		.orient("bottom");
	
	var yAxis = d3.svg.axis()
		.scale(y)
		.orient("left");
	
	chart.append("g")
		.attr("class", "axis")
		.attr("transform", "translate(0," + height + ")")
		.call(xAxis);

	chart.append("g")
		.attr("class", "axis")
		.attr("transform", "translate(0, 0)")
		.call(yAxis);

	bars.enter()
		.append("g")
		.attr("class","bar")
		.attr("transform", function(d, i) { return "translate(" + x(d.name) + ",0)"; });
	
	var rects = bars.append("rect")
		.style("fill", function(d) {
			return "hsl(" + colorScale(d.value) + ",100%, 50%)";
		})
		.attr("y", height + "px")
		.attr("width", x.rangeBand())
		.attr("height", "0px");

	bars.append("text")
		.attr("y", function(d) { return y(d.value) + 3; })
		.attr("x", x.rangeBand() / 2)
		.attr("dy", ".75em")
		.attr("text-anchor", "middle")
		.text(function(d) { return d.value; });

	rects.transition()
		.duration(500)
		.delay(function(d, i) { return i * 10; })
		.attr("y", function(d) { return y(d.value); })
		.attr("height", function(d) { return height - y(d.value) + "px"; })
}

updateBarGraph();


// Open Assessments User Statistics Bar Graph
function updateOpenAssessmentStats() {
	var margin = {top: 10, right: 10, bottom: 30, left: 40},
	    height = 300 - margin.left - margin.right,
	    width = 400 - margin.top - margin.bottom;
	
	var x = d3.scale.ordinal()
		.rangeRoundBands([0, width], .1);
	var y = d3.scale.linear()
		.range([height, 0]);
	
	var xAxis = d3.svg.axis()
		.scale(x)
		.orient("bottom");
	var yAxis = d3.svg.axis()
		.scale(y)
		.orient("left");
	
	// This adds a padded container that the bars will go 
	var chart = d3.select("#openAssessmentStats svg")
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");
	
	// Load stats data
	// TODO don't use absolute url ref here
	d3.json("assessment_stats/attempt_counts", function(error, data) {
		//Hide the loading spinner
		$("#openAssessmentStats .spinner").hide();
		//Resize the svg container
		$("#openAssessmentStats svg").height(height+margin.top+margin.bottom).width(width+margin.left+margin.right);
		x.domain(data.map(function(d) { return d.name; }));
		y.domain([0, d3.max(data, function(d) { return d.value; })]);

		chart.append("g")
			.attr("class", "axis x")
			.attr("transform", "translate(0," + height + ")")
			.call(xAxis);
		chart.append("g")
			.attr("class", "axis y")
			.call(yAxis);

		var bars = chart.selectAll(".bar")
			.data(data)
			.enter().append("g")
			.attr("transform", function(d) { return "translate(" + x(d.name) + ", 0)"; })
			.attr("class", "bar");

		var rects = bars.append("rect")
			//.attr("x", function(d) { return x(d.name); })
			//y and height are temporary, but must have initial values for transition to work
			.attr("y", height + "px")
			.attr("height", 0 + "px")
			.attr("width", x.rangeBand())
			.attr("fill", function(d) { return fillColor(d.name); });

		bars.append("text")
			.attr("x", function(d) { return x.rangeBand() / 2; })
			.attr("y", function(d) { return y(d.value) + 5; })
			.attr("dy", ".75em")
			.attr("text-anchor", "middle")
			.text(function(d) { return d.value; });
		
		rects.transition()
			.duration(500)
			.delay(function(d, i) { return i * 10; })
			.attr("y", function(d) { return y(d.value); })
			.attr("height", function(d) { return height - y(d.value); });
	});


}


// Ayamel Global Statistics Bar Graph
function updateAyamelStats() {
	var margin = {top: 0, right: 10, bottom: 30, left: 40},
	    height = 300 - margin.left - margin.right,
	    width = 625 - margin.top - margin.bottom;
	
	var x = d3.scale.ordinal()
		.rangeRoundBands([0, width], .1);
	var y = d3.scale.linear()
		.range([height, 0]);
	
	var xAxis = d3.svg.axis()
		.scale(x)
		.orient("bottom");
	var yAxis = d3.svg.axis()
		.scale(y)
		.orient("left");
	
	// This adds a padded container that the bars will go 
	var chart = d3.select("#ayamelStats svg")
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");
	
	// Load stats data
	// TODO don't use absolute url ref here
	d3.json("ayamel_stats/verb_counts", function(error, data) {
		//Hide the loading spinner
		$("#ayamelStats .spinner").hide();
		//Resize the svg container
		$("#ayamelStats svg").height(height+margin.top+margin.bottom).width(width+margin.left+margin.right);
		x.domain(data.map(function(d) { return d.name; }));
		y.domain([0, d3.max(data, function(d) { return d.value; })]);

		chart.append("g")
			.attr("class", "axis x")
			.attr("transform", "translate(0," + height + ")")
			.call(xAxis);
		chart.append("g")
			.attr("class", "axis y")
			.call(yAxis);

		var bars = chart.selectAll(".bar")
			.data(data)
			.enter().append("g")
			.attr("transform", function(d) { return "translate(" + x(d.name) + ", 0)"; })
			.attr("class", "bar");

		var rects = bars.append("rect")
			//.attr("x", function(d) { return x(d.name); })
			//y and height are temporary, but must have initial values for transition to work
			.attr("y", height + "px")
			.attr("height", 0 + "px")
			.attr("width", x.rangeBand())
			.attr("fill", function(d) { return fillColor(d.name); });

		bars.append("text")
			.attr("x", function(d) { return x.rangeBand() / 2; })
			.attr("y", function(d) { return y(d.value) + 5; })
			.attr("dy", ".75em")
			.attr("text-anchor", "middle")
			.text(function(d) { return d.value; });
			
		rects.transition()
			.duration(400)
			.delay(function(d, i) { return i * 30; })
			.attr("y", function(d) { return y(d.value); })
			.attr("height", function(d) { return height - y(d.value); })
	});


}

function fillColor(barName) {
	var colors = {"Question Attempts":"#5bc0de","Correct Attempts":"#5cb85c","Incorrect Attempts":"#d9534f"};
	return colors[barName] || "hsl(" + Math.random() * 360 + ",100%,50%)";
}

// Confidence Level pie chart
function updateConfidencePie() {
    // Load stats data
    // TODO don't use absolute url ref here
    d3.json("assessment_stats/confidence_counts", function(error, data) {
	$("#confidencePie .spinner").hide();
	var confidencePie = new d3pie("confidencePie", {
		"size": {
			"canvasWidth": 590,
			"canvasHeight": 500,
			"pieOuterRadius": "90%"
		},
		"data": {
			"sortOrder": "value-desc",
			"smallSegmentGrouping": {
				"enabled": true
			},
			"content": [
				{
					"label": "Just A Guess",
					"value": data["low"],
					"color": "#CF0000"
				},
				{
					"label": "Pretty Sure",
					"value": data["medium"],
					"color": "#71B889"
				},
				{
					"label": "Very Sure",
					"value": data["high"],
					"color": "#3299BB"
				}
			]
		},
		"labels": {
			"outer": {
				"pieDistance": 32
			},
			"inner": {
				"hideWhenLessThanPercentage": 3
			},
			"mainLabel": {
				"fontSize": 12
			},
			"percentage": {
				"color": "#ffffff",
				"decimalPlaces": 0
			},
			"value": {
				"color": "#adadad",
				"fontSize": 11
			},
			"lines": {
				"enabled": true
			},
			"truncation": {
				"enabled": true
			}
		},
		"tooltips": {
			"enabled": true,
			"type": "placeholder",
			"string": "{label}: {value}, {percentage}%"
		},
		"effects": {
			"load": {
				"speed": 400
			},
			"pullOutSegmentOnClick": {
				"effect": "linear",
				"speed": 200,
				"size": 8
			}
		}
	});
    });
}


// Line graph that shows average user confidence
var confidenceAverageOption = "overall";
var confidenceAverageData = {};
function setupConfidenceAverage() {
	$("input:radio[name=confidenceAverageOption]").on("change", function() {
		confidenceAverageOption = $(this).val();
		updateConfidenceAverage();
	});

	d3.json("assessment_stats/confidence_average", function(error, data) {
		var margin = {top: 0, right: 55, bottom: 30, left: 35},
		    height = 100 - margin.top - margin.bottom,
		    width = 450 - margin.left - margin.right;
		//Hide the loading spinner
		$("#confidenceAverage .spinner").hide();
		//Resize the svg container
		$("#confidenceAverage svg").height(height+margin.top+margin.bottom).width(width+margin.left+margin.right);

		var x = d3.scale.linear()
			.domain([-1, 1])
			.range([0, width]);

		var axis = d3.svg.axis()
			.scale(x)
			.tickFormat("")
			.innerTickSize(12)
			.orient("bottom");

		var chart = d3.select("#confidenceAverage svg")
			.append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")")
			.attr("class", "container");
		
		chart.append("text")
			.attr("x", x(-1.0) + "px")
			.text("Just A Guess");
		chart.append("text")
			.attr("x", x(0) + "px")
			.text("Pretty Sure");
		chart.append("text")
			.attr("x", x(1.0) + "px")
			.text("Very Sure");

		chart.selectAll("text")
			.attr("text-anchor", "middle")
			.attr("y", "80px");

		chart.append("g")
			.attr("transform", "translate(0, " + (height / 2 +  10) + ")")
			.attr("class", "axis x")
			.call(axis);

		//Single point that we'll be moving around
		chart.append("circle")
			.attr("fill","rgb(255, 230, 0)")
			.attr("cy", "47px")
			.attr("class","point")
			.attr("r", "10px");

		// TODO error checking
		confidenceAverageData["overall"] = x(data["overall"]);
		confidenceAverageData["correct"] = x(data["correct"]);
		confidenceAverageData["incorrect"] = x(data["incorrect"]);
		updateConfidenceAverage();
	});
}
function updateConfidenceAverage() {
	//We only want one data point: whichever average (overall, correct, or incorrect) is currently selected
	var data = [confidenceAverageData[confidenceAverageOption]];
	d3.select("#confidenceAverage svg .point")
		.transition()
		.duration(750)
		.attr("cx", data + "px");
}

// When page is done loading, show our visualizations
$(function() {
	updateOpenAssessmentStats();
	updateAyamelStats();
	updateConfidencePie();
	setupConfidenceAverage();
});
