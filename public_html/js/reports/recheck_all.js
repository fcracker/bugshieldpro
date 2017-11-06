$(document).ready(function(){
   document.getElementById("progress").innerHTML = "<div style=\"width:0%;background-color:#ddd;\">&nbsp;</div>";
   document.getElementById("information").innerHTML = "0 row(s) processed." ;
   
   var total = $("#total").val();
   var step = 500;
   process(0);
   
   function process(index){
       if(index > total){
           return true;
       }
       $.post(
            "anti-fraud-recheck-all.php",
            {start:index, step:step},
            function (data) {                
                if(data == "1"){
                    var count = index+step;
                    if(count>total){
                        document.getElementById("progress").innerHTML="<div style=\"width:100%;background-color:#ddd;\">&nbsp;</div>";
                        document.getElementById("information").innerHTML="Process completed"
                        setTimeout(function(){
                            //window.location.href="anti-fraud.php";
                        },2000)
                    }else{
                        document.getElementById("progress").innerHTML = "<div style=\"width:"+(count/total*100)+"%;background-color:#ddd;\">&nbsp;</div>";
                        document.getElementById("information").innerHTML = count + " row(s) processed." ;
                    }
                    process(count);                    
                }
            }            
            );
   }
});
