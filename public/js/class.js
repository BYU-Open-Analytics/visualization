

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
  d3.json("../class_stats/concepts", function(error, resultList){
    console.log(error);
    console.log(resultList);
    resultList.sort();

		var tbody = d3.select("#resultList tbody");
		var resultListRows = tbody.selectAll("tr");

		resultListRows = resultListRows.data(resultList)
			.enter()
			.append("tr");

    resultListRows.append("td")
			.html(function(d) { return d.id; })

		resultListRows.append("td")
			.html(function(result) { return result.title; });

		resultListRows.append("td")
      .attr("id", function(d) { var classID = 'graph' + d.id; return classID; })
			.html(function(d) {
        timeGraph = c3.generate({
      		bindto: "#graph" + d.id,
          data: {
    json: [
      {name: 'www.site1.com', upload: 200, download: 200, total: 400},
      {name: 'www.site2.com', upload: 100, download: 300, total: 400},
      {name: 'www.site3.com', upload: 300, download: 200, total: 500},
      {name: 'www.site4.com', upload: 400, download: 100, total: 500},
    ],
    keys: {
      // x: 'name', // it's possible to specify 'x' when category axis
      value: ['upload', 'download'],
    }
  },
      		axis: {
      			x: {
      				type: 'category'
      			},
      			y: {
      				max: 9.99,
      				min: 0.01
      			}
      		}
      	});

        return d.history
      })
  });
  // function loadTimeGraph() {
  // 	timeGraph = c3.generate({
  // 		bindto: "#timeGraph",
  // 		data: {
  // 			x : 'date',
  // 			url: '../student_skills_stats/time_graph',
  // 			type: 'line'
  // 		},
  // 		axis: {
  // 			x: {
  // 				type: 'category'
  // 			},
  // 			y: {
  // 				max: 9.99,
  // 				min: 0.01
  // 			}
  // 		}
  // 	});

});
