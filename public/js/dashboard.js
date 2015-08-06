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

// Simple bar graph visualization
var barGraphData = [
  {name: "Locke",    value:  4},
  {name: "Reyes",    value:  8},
  {name: "Ford",     value: 15},
  {name: "Jarrah",   value: 16},
  {name: "Shephard", value: 23},
  {name: "Kwon",     value: 32}
];

var margin = {top: 20, right: 30, bottom: 30, left: 40};
    height = $("#barGraph svg").height() - margin.left - margin.right,
    width = $("#barGraph svg").width() - margin.top - margin.bottom;

function updateBarGraph() {
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
	
	bars.append("rect")
		.style("fill", function(d) {
			return "hsl(" + colorScale(d.value) + ",100%, 50%)";
		})
		.attr("y", function(d) { return y(d.value); })
		.attr("width", x.rangeBand())
		.attr("height", function(d) { return height - y(d.value) + "px"; })

	bars.append("text")
		.attr("y", function(d) { return y(d.value) + 3; })
		.attr("x", x.rangeBand() / 2)
		.attr("dy", ".75em")
		.text(function(d) { return d.value; });
}

updateBarGraph();


// Open Assessments User Statistics Bar Graph
function updateOpenAssessmentStats() {
	var margin = {top: 10, right: 10, bottom: 30, left: 40};
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
	d3.json("/lti_php/assessment_stats", function(error, data) {
		//Hide the loading spinner
		$("#openAssessmentStats .spinner").hide();
		//Resize the svg container
		$("#openAssessmentStats svg").height(height+margin.top+margin.bottom).width(width+margin.left+margin.right);
		console.log(error, data);
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

		bars.append("rect")
			//.attr("x", function(d) { return x(d.name); })
			.attr("y", function(d) { return y(d.value); })
			.attr("height", function(d) { return height - y(d.value); })
			.attr("width", x.rangeBand())
			.attr("fill", function(d) { return fillColor(d.name); });

		bars.append("text")
			.attr("x", function(d) { return x.rangeBand() / 2; })
			.attr("y", function(d) { return y(d.value) + 5; })
			.attr("dy", ".75em")
			.text(function(d) { return d.value; });
			
	});


}


// Ayamel Global Statistics Bar Graph
function updateAyamelStats() {
	var margin = {top: 0, right: 10, bottom: 30, left: 40};
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
	d3.json("/lti_php/ayamel_stats", function(error, data) {
		//Hide the loading spinner
		$("#ayamelStats .spinner").hide();
		//Resize the svg container
		$("#ayamelStats svg").height(height+margin.top+margin.bottom).width(width+margin.left+margin.right);
		console.log(error, data);
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

		bars.append("rect")
			//.attr("x", function(d) { return x(d.name); })
			.attr("y", function(d) { return y(d.value); })
			.attr("height", function(d) { return height - y(d.value); })
			.attr("width", x.rangeBand())
			.attr("fill", function(d) { return fillColor(d.name); });

		bars.append("text")
			.attr("x", function(d) { return x.rangeBand() / 2; })
			.attr("y", function(d) { return y(d.value) + 5; })
			.attr("dy", ".75em")
			.text(function(d) { return d.value; });
			
	});


}

function fillColor(barName) {
	var colors = {"Question Attempts":"#5bc0de","Correct Attempts":"#5cb85c","Incorrect Attempts":"#d9534f"};
	return colors[barName] || "hsl(" + Math.random() * 360 + ",100%,50%)";
}

function type(d) {
	d.value = +d.value;
	return d;
}


// Confidence Level pie chart
function updateConfidencePie() {
	$("#confidencePie .spinner").hide();
var confidencePie = new d3pie("confidencePie", {
	"header": {
		"title": {
			"fontSize": 1
		},
		"subtitle": {
			"fontSize": 1
		},
		"titleSubtitlePadding": 0
	},
	"footer": {
		"color": "#999999",
		"fontSize": 10,
		"font": "open sans",
		"location": "bottom-left"
	},
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
				"label": "Java",
				"value": 157618,
				"color": "#aeec41"
			},
			{
				"label": "PHP",
				"value": 114384,
				"color": "#a4a0c9"
			},
			{
				"label": "Python",
				"value": 95002,
				"color": "#312749"
			},
			{
				"label": "Ruby",
				"value": 218812,
				"color": "#608d1b"
			},
			{
				"label": "C+",
				"value": 78327,
				"color": "#0a6097"
			},
			{
				"label": "C",
				"value": 67706,
				"color": "#1ee678"
			},
			{
				"label": "JavaScript",
				"value": 264139,
				"color": "#bf273e"
			},
			{
				"label": "Objective-C",
				"value": 36344,
				"color": "#7c9058"
			},
			{
				"label": "Shell",
				"value": 28561,
				"color": "#bca349"
			},
			{
				"label": "Cobol",
				"value": 24131,
				"color": "#913e99"
			},
			{
				"label": "C#",
				"value": 100,
				"color": "#d1c77e"
			},
			{
				"label": "Coldfusion",
				"value": 68,
				"color": "#7b37c0"
			},
			{
				"label": "Fortran",
				"value": 218812,
				"color": "#8fc467"
			},
			{
				"label": "Coffeescript",
				"value": 157618,
				"color": "#ac83d5"
			},
			{
				"label": "Node",
				"value": 114384,
				"color": "#8b6834"
			},
			{
				"label": "Basic",
				"value": 95002,
				"color": "#cd29eb"
			},
			{
				"label": "Cola",
				"value": 36344,
				"color": "#44b9ae"
			},
			{
				"label": "Perl",
				"value": 32170,
				"color": "#e98125"
			},
			{
				"label": "Dart",
				"value": 28561,
				"color": "#830909"
			},
			{
				"label": "Go",
				"value": 264131,
				"color": "#2181c1"
			},
			{
				"label": "Groovy",
				"value": 218812,
				"color": "#dac861"
			},
			{
				"label": "Processing",
				"value": 157618,
				"color": "#85f71a"
			},
			{
				"label": "Smalltalk",
				"value": 114384,
				"color": "#cb2121"
			},
			{
				"label": "Scala",
				"value": 95002,
				"color": "#e4a049"
			},
			{
				"label": "Visual Basic",
				"value": 78327,
				"color": "#228835"
			},
			{
				"label": "Scheme",
				"value": 67706,
				"color": "#e65314"
			},
			{
				"label": "Rust",
				"value": 36344,
				"color": "#4baa49"
			},
			{
				"label": "FoxPro",
				"value": 32170,
				"color": "#cc9fb0"
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
			"fontSize": 11
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
		"pullOutSegmentOnClick": {
			"effect": "linear",
			"speed": 400,
			"size": 8
		}
	}
});
}

// When page is done loading, show our visualizations
$(function() {
	updateOpenAssessmentStats();
	updateAyamelStats();
	updateConfidencePie();
});
