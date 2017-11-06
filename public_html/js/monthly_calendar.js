$(function(){

//$("tr.drow").each(function(){$(this).hide();});

$("td.date_trigger").each(function(){

$(this).css("cursor","pointer");

var id = $(this).attr("id");

var text = $(this).text();

$(this).text(text+" - "+$("tr."+id).length+" users");


$(this).toggle(

function(){
	var id = $(this).attr("id");
	$("tr."+id).each(function(){$(this).slideDown("fast");});
},
function(){
	var id = $(this).attr("id");
	$("tr."+id).each(function(){$(this).slideUp("fast");});
}
);

});



});