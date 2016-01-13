

$(function() {
	d3.json("../student_inspector_stats/students", function(error, studentList) {

		// Create color scale
		var colorScale = d3.scale.linear()
				.domain([0, 12, 16, 20])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);

		var masteryColorScale = d3.scale.linear()
				.domain([0, 3, 6, 10])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);

		studentList.sort();

		var tbody = d3.select("#studentList tbody");
		var studentListRows = tbody.selectAll("tr");

		studentListRows = studentListRows.data(studentList)
			.enter()
			.append("tr");

		studentListRows.append("td")
			.html(function(student) { return student.name; });

		studentListRows.append("td")
			.html(function(d) { return d.name.length; })
			.style("color", function(d) { return colorScale(d.name.length); });

		studentListRows.append("td")
			.html(function(d) { return d.average })
			.style("color", function(d) { return masteryColorScale(d.average);});
	});


});
