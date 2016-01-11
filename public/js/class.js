

$(function() {
	// d3.json("../result_inspector_stats/results", function(error, resultList) {
  //
	// 	// Create color scale
	// 	var colorScale = d3.scale.linear()
	// 			.domain([0, 12, 16, 20])
	// 			.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);
  //
	// 	var masteryColorScale = d3.scale.linear()
	// 			.domain([0, 3, 6, 10])
	// 			.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);
  //
	// 	resultList.sort();
  //
	// 	var tbody = d3.select("#resultList tbody");
	// 	var resultListRows = tbody.selectAll("tr");
  //
	// 	resultListRows = resultListRows.data(resultList)
	// 		.enter()
	// 		.append("tr");
  //
	// 	resultListRows.append("td")
	// 		.html(function(result) { return result.name; });
  //
	// 	resultListRows.append("td")
	// 		.html(function(d) { return d.name.length; })
	// 		.style("color", function(d) { return colorScale(d.name.length); });
  //
	// 	resultListRows.append("td")
	// 		.html(function(d) { return d.score })
	// 		.style("color", function(d) { return masteryColorScale(d.score);});
	// });
  d3.json("../class_stats/students", function(error, resultList){
    console.log(error);
    console.log(resultList);
    resultList.sort();

		var tbody = d3.select("#resultList tbody");
		var resultListRows = tbody.selectAll("tr");

		resultListRows = resultListRows.data(resultList)
			.enter()
			.append("tr");

		resultListRows.append("td")
			.html(function(result) { return result.name; });

		resultListRows.append("td")
			.html(function(d) { return d.id; })

		resultListRows.append("td")
			.html(function(d) { return d.count })
  });

});
