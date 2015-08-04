var sampleData = [];
var scale = 10.0;

function updateCircles() {
	var circles = d3.select("#assessmentStats svg").selectAll("circle").data(sampleData);
	
	circles.enter()
		.append("circle")
		.style("fill", function() {
			return "hsl(" + Math.random() * 360 + ",100%, 50%)";
		})
		.attr("r", 0)
		.attr("cy", 40)
		.attr("cx", function(d, i) { return (i * 50) + 50; });

	circles.transition()
		.duration(750)
		.delay(function(d, i) { return i * 10; })
		.attr("r", function(d) { return Math.sqrt(d * scale); });

	circles.exit().remove();
}

$("#dataSize").on("input",function() {
	var dataSize = $(this).val();
	$("#dataSizeLabel").text(dataSize);
	sampleData = [];
	while(sampleData.length < dataSize) {
		sampleData.push(Math.random() * 50);
	}
	updateCircles();
});

$("#dataSize").trigger("input");
