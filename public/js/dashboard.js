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
  {name: "Kwon",     value: 42}
];

var width = $("#barGraph svg").width(),
    barHeight = $("#barGraph svg").height() / barGraphData.length;

function updateBarGraph() {
	var bars = d3.select("#barGraph svg").selectAll("g").data(barGraphData);

	var x = d3.scale.linear()
		.domain([0, d3.max(barGraphData, function(d) { return d.value; })])
		.range([0, width]);

	bars.enter()
		.append("g")
		.attr("transform", function(d, i) { return "translate(0," + i * barHeight + ")"; });
	
	bars.append("rect")
		.style("fill", function() {
			return "hsl(" + Math.random() * 360 + ",100%, 50%)";
		})
		.attr("height", barHeight - 1 + "px")
		.attr("width", function(d) { return x(d.value) + "px"; })

	bars.append("text")
		.attr("x", function(d) { return x(d.value) - 24; })
		.attr("y", barHeight / 2)
		.text(function(d) { return d.value; });
}

updateBarGraph();
	
