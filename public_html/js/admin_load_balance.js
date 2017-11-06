
$(function(){	

		//callback function
		var ongeneralSlide = function(e){
			var columns = $(e.currentTarget).find("td");
			var ranges = [], total = 0, i, s ="Ranges: ", w,current_id;
			for(i = 0; i<columns.length; i++){
			//w = columns.eq(i).width()-10 - (i==0?1:0);        
        w = columns.eq(i).width();
				ranges.push(w);
				total+=w;
			}		 
			for(i=0; i<columns.length; i++){			
				ranges[i] = 100*ranges[i]/total;
        current_id = columns.eq(i).attr("rel");
        columns.eq(i).children("input").val(Math.round(ranges[i]));
        $("#mpi" + current_id).text(Math.round(ranges[i]));
			}		
		}
    
    var onamexSlide = function(e){
			var columns = $(e.currentTarget).find("td");
			var ranges = [], total = 0, i, s ="Ranges: ", w,current_id;
			for(i = 0; i<columns.length; i++){
			//w = columns.eq(i).width()-10 - (i==0?1:0);        
        w = columns.eq(i).width();
				ranges.push(w);
				total+=w;
			}		 
			for(i=0; i<columns.length; i++){			
				ranges[i] = 100*ranges[i]/total;
        current_id = columns.eq(i).attr("rel");
        columns.eq(i).children("input").val(Math.round(ranges[i]));
        $("#amx" + current_id).text(Math.round(ranges[i]));
			}		
		}
	
		$("#load_balance_table").colResizable({
			liveDrag:true, 
			gripInnerHtml:"<div class='grip'></div>", 
			draggingClass:"dragging", 
			onResize:ongeneralSlide});
      
      $("#amex_load_balance_table").colResizable({
			liveDrag:true, 
			gripInnerHtml:"<div class='grip'></div>", 
			draggingClass:"dragging", 
			onResize:onamexSlide});
		
	});	