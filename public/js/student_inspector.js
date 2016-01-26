$(function() {
	d3.json("../student_inspector_stats/students", function(error, studentList) {

		var studentMax = studentList[0].max;
		studentList.splice(0,1);
		studentList.sort();
		// Create color scale for Mastery Average
		var masteryColorScale = d3.scale.linear()
				.domain([0, 3, 6, 10])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);

		var countColorScale = d3.scale.linear()
				.domain([0, studentMax/4, 3*studentMax/4,studentMax])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);

		var videoColorScale = d3.scale.linear()
				.domain([0, 25, 50, 100])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);
	  function	setupBootstrapTooltips(){
			$('[data-toggle="tooltip"]').tooltip({
				container: 'body'
			});
		}
		setupBootstrapTooltips();

		var tbody = d3.select("#studentList tbody");
		var studentListRows = tbody.selectAll("tr");
		studentListRows = studentListRows.data(studentList)
			.enter()
			.append("tr");

		//Name
		studentListRows.append("td")
			.html(function(d) { return d.name; });

		//Average Mastery over the past two weeks
		studentListRows.append("td")
			.html(function(d) { return d.average })
			.style("color", function(d) { return masteryColorScale(d.average);});

		//Video Percentage
		studentListRows.append("td")
			.append("input")
			.attr("type", "text")
			.attr("class", "progressCircle")
			.attr("disabled", "disabled")
			.attr("value", function(d) { return d.vPercentage; })


		//Relative Dashboard Participation
		studentListRows.append("td")
			.append("progress")
			.attr("value",function(d){return d.count})
			.attr("max",studentMax)
			.attr("aria-valuenow",function(d) { return ((d.count/studentMax)*100);});
		/*
		if(function(d){return d.count/studentMax} == 100){
			studentListRows.attr("class",progress-bar progress-bar-success);
		}*/
		//Attempted Questions
		studentListRows.append("td")
			.html(function(d) {return d.correct + "/"+ d.attempts;});

		//# of times a hint was viewed
		studentListRows.append("td")
			.html(function(d) {return d.hintsShowed;});

		//# of times the answer was shown
		studentListRows.append("td")
			.html(function(d) {return d.answersShowed;});


		//Median confidence level
		studentListRows.append("td")
			.html(function(d) { return d.confidence; });

		// Don't stall the UI waiting for all these to finish drawing

		setTimeout(function() {
			$(".progressCircle").knob({
				'readOnly': true,
				'width': '35',
				'height': '35',
				'thickness': '1',
				'fontSize' : '12px',
				//'fgColor': '#5cb85c',
				'inputColor': '#000000',
				'format': function(v) { return v+"%"; }
			})
		}, 1);

	});

/*$(document).ready(function()
    {
        $("#studentList").tablesorter();
    }
); */



});
