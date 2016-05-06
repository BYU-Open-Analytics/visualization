$(function() {

	//TODO fix dummy variable, student_inspector_stats/studentCount will return proper number but you need to figure
	//out how to parse it.
	var studentRange = 151;
//	alert(studentRange);
	var showBy = 151;
	var pages = Math.floor(studentRange/showBy);
	var buttons = "";
	for (var i = 1; i <= pages; i++){
		buttons += '<input type="button" id= "page_'+i+'"class= "myPages" value ='+i+'></input>';
	}
//	$("#pageButtons").html(buttons);
	var loadThesePages = function(i){
		loadStudentsOnPage(i,showBy);
	//	alert(i);
	}
//	$('.myPages').click(function(){
	//	loadThesePages(this.id.replace('page_',''));
//	});
	loadStudentsOnPage(1,showBy);

});

$.tablesorter.addParser({
	id:'confidence',
	is:function(s){
		return false;
	},
	format:function(s){
		 return s.toLowerCase().replace(/high/,2).replace(/medium/,1).replace(/low/,0); 
	},
	type:'numeric'

});

$.tablesorter.addParser({
	id:'video',
	is:function(s){
		return false;
	},
	format:function(s){
		 console.log(typeof s);
		 return $(s).attr('value'); 
	},
	parsed:false,
	type:'numeric'

});

$.tablesorter.addParser({
	id:'dashboard',
	is:function(s){
		return false;
	},
	format:function(s){
		 console.log(typeof s);
		 return $(s).attr('aria-valuenow'); 
	},
	parsed:false,
	type:'numeric'

});

$(document).ready(function() { 
     $("#studentList").tablesorter({
        headers:{
		//Add the parsers defined above to their appropriate columns
//		2:{
//		  sorter:'video'
//		},
//		3:{
//		  sorter:'dashboard'
//		},
		0:{ sorter:'false'},
		8:{ sorter:'confidence'}
	},   
	debug: true 
      }); 
}); 

function check(box){
	console.log("box checked");
}

$(function move() {
    	var elem = document.getElementById("myBar"); 
    	var width = 1;
	//Adjust the second parameter in this function (a time in ms) to better estimate the time it takes to load the dashboard
    	var id = setInterval(frame, 30);
    	function frame() {
        	if (width >= 100) {
        	    document.getElementById("myBar").style.visibility = "hidden";
	  	    document.getElementById("myProgress").style.visibility = "hidden";
	       	clearInterval(id);
		} else {
        	    width++; 
        	    elem.style.width = width + '%';
        	}
    	}
});


function loadStudentsOnPage(i,showBy){
//	alert(i + " " + showBy);
	d3.json("../student_inspector_stats/students/"+(i-1)*showBy+"/"+i*showBy, function(error, studentList) {

		var studentMax = studentList[0].max;
		studentList.splice(0,1);
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

		studentListRows.append("td")
			.html('<INPUT type="checkbox" onchange="check(this)" name="chk[]" />');
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
			.html(function(d) {return d.vPercentage});

		//Relative Dashboard Participation
		studentListRows.append("td")
			.append("progress")
			.attr("value",function(d){return d.count})
			.attr("max",studentMax)
			.attr("aria-valuenow",function(d) { return ((d.count/studentMax)*100);})
			.html(function(d) {return ((d.count/studentMax)*100);});
	/*	if(function(d){return d.count/studentMax} == 100){
			studentListRows.attr("class",progress-bar progress-bar-success);
		}*/
		//Attempted Questions
		studentListRows.append("td")
			.html(function(d) {return Math.round((d.correct/d.attempts).toFixed(2)*100) + '%';});

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
		$("#studentList").trigger("update");
	});
}
