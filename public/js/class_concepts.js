$(function(){

  d3.json("../class_stats/concepts", function(error, conceptList) {
    var masteryColorScale = d3.scale.linear()
				.domain([0, 3, 6, 10])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);

    var tbody = d3.select("#resultList tbody");
    var resultListRows = tbody.selectAll("tr");
    resultListRows = resultListRows.data(conceptList)
      .enter()
      .append("tr");

      //Lecture number
      resultListRows.append("td")
        .html(function(d){ return d.id; });

      //Concept Name
      resultListRows.append("td")
        .html(function(d) {return d.title;});

      //Mastery Score
      resultListRows.append("td")
        .html(function(d) { return d.history[d.history.length-1].average})
        .style("color", function(d) { return masteryColorScale(d.history[d.history.length-1].average);});
  });
});
