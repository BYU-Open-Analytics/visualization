$(function(){

  d3.json("../class_stats/concepts", function(error, conceptList) {
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
        .html(function(d) { return d.history[d.history.length-1]});
  });
});
