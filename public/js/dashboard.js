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
	    height = $("#openAssessmentStats svg").height() - margin.left - margin.right,
	    width = $("#openAssessmentStats svg").width() - margin.top - margin.bottom;
	
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
			.attr("width", x.rangeBand());
		bars.append("text")
			.attr("x", function(d) { return x.rangeBand() / 2; })
			.attr("y", function(d) { return y(d.value) + 5; })
			.attr("dy", ".75em")
			.text(function(d) { return d.value; });
			
	});


}
updateOpenAssessmentStats();

function type(d) {
	d.value = +d.value;
	return d;
}
