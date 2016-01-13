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
				.domain([0,25,50,100])
				.range(["#d9534f", "#FFCE54", "#D4D84F", "#5cb85c"]);
		
		
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
			.attr("class", "videoProgressCell advancedMore")
			.append("input")
			.attr("type", "text")
			.attr("class", "progressCircle")
			.attr("disabled", "disabled")
			.attr("value", function(d) { return d.vPercentage; });
		
		//Relative Dashboard Participation	
		studentListRows.append("td")
			.append("progress")
			.attr("value",function(d){return d.count})
			.attr("max",studentMax)
			.style("color", function(d) { return countColorScale(d.count);});
	
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
				'width': '30',
				'height': '30',
				'thickness': '.4',
				'fgColor': "#5cb85c",
				draw : function(){
					$(".progressCircle").css("font-size","40px");
				},
				'format': function(v) { return v+"%"; }
			});
		}, 1);
	});


});
