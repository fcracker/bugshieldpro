<?php
// header("Cache-Control:no-cache,must-revalidate");
function get_menuEditor($table_name, $class_max=3, $value=0){
	global $cfg;
	$save_url = "../lib/menu.save.php";
	$return = "";
	$fields="";
	for($i=1;$i<$class_max;$i++){
		$fields=$fields."f".$i.",";
	}
	
	$fields=$fields."f".$class_max;
	$font_size = "FONT-SIZE:12px;";
	$input_style="BORDER-BOTTOM:1px solid; BORDER-LEFT:1px solid; BORDER-RIGHT:1px solid; BORDER-TOP:1px solid; ".$font_size;
	$return .= "
		<style>
			div.menueditor_modul{margin-top:5px;margin-bottom:10px; width:623px;}
		</style>";
	$return .= "
		<script langauge=javascript>
			//document.onhelp=fn_help;
			var nor;
			var class_num=1;
			var class_max=$class_max;
			var sel_num=-1;
			var del_excstr=\"\";
			var char_set=0;
			var scroll_value=0;
			var temp_txtname=\"\";
			
			var FormURL = \"form.php?formID=\";
			var PageURL = \"page.php?pageID=\";
			var preMenuType = 1;
			var b_LinkTitle = new Array(\"Link URL\", \"Form Name\", \"Page Name\");";
	
			

//	$return .= "	
//		function fn_help(){
//			if(document.getElementById(\"my_help\").style.display!=\"\")
//				document.getElementById(\"my_help\").style.display=\"\"
//			else
//				document.getElementById(\"my_help\").style.display=\"none\"
//			return false;
//		}";
//	$return .= "
//		function tbl_help(){
//			if(document.getElementById(\"tbl_help\").style.display!=\"\"){
//				document.getElementById(\"tbl_help\").style.display=\"\"
//				document.getElementById(\"b_help\").innerHTML=\"Hide Help\";
//			}else{
//				document.getElementById(\"tbl_help\").style.display=\"none\"
//				document.getElementById(\"b_help\").innerHTML=\"Show Help\";
//			}
//			return false;
//		}
//	";
	$return .= "
		function item_add(){
			txt_name.value=\"\";
			var beg=\"\";
			for(i=1;i<class_num;i++){
				beg=beg+\"……\";
			}
			len=eval(lists.length*1);
			var oOpt=new Array();";
		
		$return .= "
			if(preMenuType==1)
				var nOpt=new Option(beg+txt_name.value,\"0_@_\"+class_num+\"_@_\"+txt_name.value + \"_@_\");
			if(preMenuType==2)//Form
					var nOpt=new Option(beg+txt_name.value,\"0_@_\"+class_num+\"_@_\"+txt_name.value + \"_@_\");
			if(preMenuType==3)//Page
					var nOpt=new Option(beg+txt_name.value,\"0_@_\"+class_num+\"_@_\"+txt_name.value + \"_@_\");
			";
		
		$return .= "
			if(len!=0){
				if(eval(len*1-1)!=sel_num){
					for(i=0;i<len;i++){
						oOpt[i]=lists[i];
					}
					k=0;
					for(i=0;i<len;i++){
						if(i==sel_num){
							k=1;
							lists[i]=nOpt;
						}
						lists[i+k]=oOpt[i];
					}
				}else{
					lists[len]=nOpt;
					sel_num=len;
				}
			}else{
				nOpt.value=\"0_@_1_@_\";
				nOpt.text=\"\";
				lists[0]=nOpt;
				sel_num=0;
			}
			lists.selectedIndex=sel_num;
			lists_change();
		}";

		$return .= "
		function lists_change(){
			if(lists.selectedIndex<0) return;
			var temp= new Array();
			var typeTemp = new Array();
			temp=lists.item(lists.selectedIndex).value.split(\"_@_\");
			if(lists.selectedIndex==-1){
				sel_num=0;
				lists.selectedIndex=0;
			}else{
				if(lists.value==\"\"){return;}
				txt_name.value=temp[2];";
				
		$return .= "				
				if(temp[temp.length-1].split(FormURL).length>1){
					fn_sel_type_change('2');
					typeTemp = temp[temp.length-1].split(FormURL);
					document.getElementById('txt_value2').value = typeTemp[1];	
				}else if(temp[temp.length-1].split(PageURL).length>1){
					fn_sel_type_change('3');
					typeTemp = temp[temp.length-1].split(PageURL);
					document.getElementById('txt_value3').value = typeTemp[1];	
				}else{
					fn_sel_type_change('1');
					typeTemp = temp[temp.length-1];
					document.getElementById('txt_value1').value = typeTemp[1];
				}
				document.getElementById('sel_type').value = preMenuType;
				txt_value.value=temp[temp.length-1];";
		$return .= "
				class_num=lists.item(lists.selectedIndex).value.split(\"_@_\")[1];
			}
			txt_name.focus();
			setTimeout('kgi()',100);
		}
		function kgi(){
			lists.scrollTop=scroll_value;
		}
		";
		$return .= "
		function item_del(){
			if(lists.selectedIndex<0) return;
			if(lists[sel_num].value==\"\"){return}
			if(!confirm(\"Sure to delete this item?\")){
				return;
			}
			if(lists[sel_num].value.split(\"_@_\")[0]*1!=0){
				del_excstr=del_excstr + \"-1_@_\"+ lists[sel_num].value.split(\"_@_\")[0] + \"_@_\" + \"&&&\";
			}
			lists.remove(sel_num);
			lists.selectedIndex=sel_num;
			lists_change();
		}";

	$return .= "
		function to_save() {
			var pre_class=0;
			var temp_list;
			var errorMsg = \"\";
			document.save_form.content.value=del_excstr;
			for(i=0;i<lists.length-1;i++){
				lists.selectedIndex=i;
				
				temp_list = lists.value.split(\"_@_\");
				
				if(temp_list[1]-pre_class>1) errorMsg=\"All items must be nested correctly.\";
				
				if(temp_list[2]==\"\") errorMsg=\"Please enter the item name.\";
				
				if(temp_list[1]==2 && temp_list[3]==\"\") errorMsg=\"Please enter the item URL.\";
				
				if(temp_list[1]==1 && temp_list[3]!=\"\"){
					if(i<lists.length-2){
						if(lists[i+1].value.split(\"_@_\")[1]>1) errorMsg=\"A top level item URL value should be empty.\";
					}
				}
				
				if(temp_list[1]==1 && temp_list[3]==\"\"){
					if(i<lists.length-2){
						if(lists[i+1].value.split(\"_@_\")[1]==1) errorMsg=\"Please enter the item URL.\";
					}else{
						errorMsg = \"Please enter the item URL.\";
					}
				}
				if(errorMsg != \"\"){
					alert(errorMsg);
					lists.selectedIndex=i;
					lists_change();
					return;
				}
				
				pre_class=temp_list[1];
				
				document.save_form.content.value=document.save_form.content.value + lists.value  + \"&&&\";			
			}
			if(document.save_form.content.value.substring(0,3)==\"&&&\")
				document.save_form.content.value=document.save_form.content.value.substring(3,document.save_form.content.value.length);
			document.save_form.table_name.value=\"$table_name\";
			document.save_form.class_max.value=\"$class_max\";
			document.save_form.menu_url.value=window.location.href;			
			if(!confirm(\"Save changes?\"))
				return;
			document.save_form.submit();
	}";

	$return .= "
		function class_change(f){
			if(sel_num<0){return}
			if(eval(class_num*1+f*1)<1 || eval(class_num*1+f*1)>class_max){return}
			class_num=eval(class_num*1+f*1)
			item_update(3);
	}";

	$return .= "
		function item_update(f){
			lists[sel_num].value=lists[sel_num].value.split(\"_@_\")[0] + \"_@_\" + class_num + \"_@_\" + txt_name.value;";
		$return .= "
			switch(preMenuType){
				case '1':
					lists[sel_num].value=lists[sel_num].value + \"_@_\" + document.getElementById(\"txt_value1\").value;
					break;
				case '2'://Form
					lists[sel_num].value=lists[sel_num].value + \"_@_\" + FormURL + document.getElementById(\"txt_value2\").value;
					break;
				case '3'://Page
					lists[sel_num].value=lists[sel_num].value + \"_@_\" + PageURL + document.getElementById(\"txt_value3\").value;
					break;
			}";
		$return .= 	"
			beg=\"\";
			for(i=1;i<class_num;i++){
				beg=beg+\"……\";
			}
			lists[sel_num].text=beg+ txt_name.value;";

		$return .= "
		switch(preMenuType){
				case '1':
					lists[sel_num].text=lists[sel_num].text + \"(\" + document.getElementById(\"txt_value1\").value + \")\";
					break;
				case '2'://Form
					lists[sel_num].text=lists[sel_num].text + \"(\" + FormURL + document.getElementById(\"txt_value2\").value + \")\";
					break;
				case '3'://Page
					lists[sel_num].text=lists[sel_num].text + \"(\" + PageURL + document.getElementById(\"txt_value3\").value + \")\";
					break;
			}";
		
		$return .= "if(f<3) sel_num=sel_num+f;
			if(f==0){
				sel_num=sel_num;
				item_add();
			}
			lists.selectedIndex=sel_num;
			lists_change();
	}";

	$return .= "
		function order_change(f){
			nor=eval(sel_num*1+f*1);
			if(nor<0 || nor>(lists.length-1)){return}
			var V_temp=lists[nor].value;
			var T_temp=lists[nor].text;
			lists[nor].value=lists[sel_num].value;
			lists[nor].text=lists[sel_num].text;
			lists[sel_num].text=T_temp;
			lists[sel_num].value=V_temp;
			sel_num=nor;
			lists.selectedIndex=sel_num;
			lists_change();
	}";

	$return .= 	"
		function lists_select(){
			sel_num=eval(lists.selectedIndex*1);
			scroll_value=lists.scrollTop;
	}";
	$return .= "function txt_press(evt,k){";
		$return .= "if(evt.keyCode==13){";
	if($value!=0){
		$return .= "if(k==0) txt_value.focus();
			else item_input();";
	}else{
		$return .= "
			if(txt_name==\"\") return;
			item_input();";
	}
	$return .= "}}";
	$return .= "
	function txt_keydown(evt,k){
		if(evt.ctrlKey){
			switch(evt.keyCode){
				case 37:
					class_change(-1);
					break;
				case 38:
					order_change(-1);
					break;
				case 39:
					class_change(1);
					break;
				case 40:
					order_change(1);
					break;
				case 45:
					item_add()
					break;
				case 46:
					item_del()
					break;
				case 13:
					to_save();
					break;
			}
		}
		else{
			switch(evt.keyCode){
				case 38:
					if(sel_num>0)
						sel_num--;
					lists.selectedIndex=sel_num;
					lists_change();
					break;
				case 40:
					if(sel_num<lists.length-1)
						sel_num++;
					lists.selectedIndex=sel_num;
					lists_change();
					break;
			}
		}
	}";
	$return .= 	"
	function item_input(){
		var _txtValue;
		var _txtSize = 50;
		_txtValue = txt_name.value;
		if(_txtValue==\"\"){
			alert(\"Enter the item name.\");
			return;
		}
		if(_txtValue.length>_txtSize){
			alert(\"An item name must be less than \" + _txtSize + \" characters long.\");
			return;
		}
		if(lists.length==sel_num+1){
			item_update(0);
		}
		else{
			item_update(1);
		}
		txt_name.focus;
		txt_name.select;
	}";
	$return .= 	"
	function fn_sel_type_change(selType){
		document.getElementById(\"txt_value\"+preMenuType).style.display = \"none\";
		preMenuType = selType;
		document.getElementById(\"link_title\").innerHTML = b_LinkTitle[preMenuType-1];
		document.getElementById(\"txt_value\"+preMenuType).style.display = \"\";
	}
	</script>";
	$return .= 	"
	<div class=\"menueditor_modul\">
		<table cellpadding=0 width=\"95%\" height=\"100%\" align=center>
		<tr><td>
			<table width=\"100%\" height=\"100%\" style=\"margin-top:10;margin-left:3\">";
			$return .= 	"
				<tr><td align=\"left\" style=\"padding-left:10px;\">
					<b style=\"$font_size width:50;\" onselectstart=\"return false\">Menu Name</b>&nbsp;&nbsp;
					</td>
					<td>
					<input type=\"text\" name=\"txt_name\" id=\"txt_name\" style=\"width:300px;height:20;cursor:default;BACKGROUND-COLOR:white;$input_style\"  onKeyPress=\"txt_press(event,0)\" onKeyDown=\"txt_keydown(event,0)\">
					</td>
					<td align=\"right\">
					<b style=\"$font_size width:50;\" onselectstart=\"return false\">Type</b>&nbsp;:&nbsp;
					</td>
					<td>
					<select id=\"sel_type\" style=\"$font_size width:50;\" onchange=\"fn_sel_type_change(this.value)\">
						<option value=\"1\">Script</option>
						<option value=\"2\">Form</option>
						<option value=\"3\">Page</option>
					</select>
				</td></tr>";
			$return .= 	"
				<tr>
					<td align=\"left\" style=\"padding-left:10px;\">
						<b style=\"$font_size width:50;\" onselectstart=\"return false\" id=\"link_title\">Link URL</b>&nbsp;&nbsp;
					</td>
					<td align=\"left\" colspan=2>
						<input type=\"text\" name=\"txt_value\" id=\"txt_value1\" style=\"width:350px;height:20;cursor:default;BACKGROUND-COLOR:white;$input_style\"  onKeyPress=\"txt_press(event,1)\" onkeydown=\"txt_keydown(event,1)\"></input>";
			
			$return .= 	"
						<select id=\"txt_value2\" style=\"display:none; width:350px; height:20;$font_size\">";
			$sql = "SELECT FormID,FormTitle FROM ".$cfg['database']['prefix']."form ";
			$rst = mysql_query($sql);
			if(mysql_num_rows($rst)){	
				while($row=mysql_fetch_array($rst, MYSQL_NUM)){
					$return .= 	"
							<option value=\"".$row[0]."\">".$row[1]."</option>";
				}
			}
			$return .= 	"
						</select>";
			$return .= 	"
						<select id=\"txt_value3\" style=\"display:none; width:350px; height:20; $input_style\">";
			$sql = "SELECT PageID, PageName FROM ".$cfg['database']['prefix']."pages ";
			$rst = mysql_query($sql);
			if(mysql_num_rows($rst)){	
				while($row=mysql_fetch_array($rst, MYSQL_NUM)){
					$return .= 	"
							<option value=\"".$row[0]."\">".$row[1]."</option>";
				}
			}
			$return .= 	"
						</select>";
			$return .= 	"
					</td>
				<td align=\"right\">
					<input type=button name=\"input_text\" id=\"input_text\" onclick=\"item_input()\" value=\"Enter\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" ></input>
				</td></tr>";
			$return .= 	"
				<tr style=\"height:30\" onselectstart=\"return false\"><td colspan=4>
					<input type=\"button\" name=\"btn_add\" id=\"btn_add\" title=\"Add\" onclick=\" item_add()\"  value=\"Add\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" ></input>&nbsp;&nbsp;
					<input type=\"button\" name=\"btn_del\" id=\"btn_del\" title=\"Delete\" value=\"Delete\" onclick=\"item_del()\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"></input>&nbsp;&nbsp;
					<input type=\"button\" name=\"btn_save\" id=\"btn_save\"  value=\"Save\" onclick=\"to_save()\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"></input>
				</td></tr>";
			$return .= 	"
				<tr style=\"height:30\" onselectstart=\"return false\"><td colspan=4>
					<input type=\"button\" name=\"btn_left\" id=\"btn_left\" title=\"Move Left\" style=\"width:58px;height:20;\" value=\"Left\" onclick=\"class_change(-1)\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"></input>&nbsp;&nbsp;
					<input type=\"button\" name=\"btn_right\" id=\"btn_right\" title=\"Move Right\" style=\"width:58px;height:20;\" value=\"Right\" onclick=\"class_change(1)\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\" ></input>&nbsp;&nbsp;
					<input type=\"button\" name=\"btn_up\" id=\"btn_up\" title=\"Move Up\" style=\"width:58px;height:20;\" value=\"Up\" onclick=\"order_change(-1)\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"></input>&nbsp;&nbsp;
					<input type=\"button\" name=\"btn_down\" id=\"btn_down\" title=\"Move Down\" style=\"width:58px;height:20;\" value=\"Down\" onclick=\"order_change(1)\" class=\"btn\" onmouseover=\"this.className='btnhov'\" onmouseout=\"this.className='btn'\"></input>";
				$return .= "</td></tr>";
			$return .= 	"
				<tr onselectstart=\"return false\"><td id=\"container\" align=\"center\" colspan=4>
					<select name=\"lists\" id=\"lists\" style=\"width:100%;$font_size\" size=20 onchange=\"lists_change()\"  onclick=\"lists_select()\">";
					$sqlstr="select * from ".$table_name." order by ".$fields;
					$rst = mysql_query($sqlstr);
					if(sizeof(mysql_num_rows($rst))){
						while($row=mysql_fetch_assoc($rst)){
							for($i=1;$i<=$class_max;$i++){
								if($row["f".$i]==0)
									break;
							}
							$fclass=$i-1;
							$caption=str_repeat("……",($fclass-1)).trim($row["name"]);
							if($value!=0){
								$caption=$caption."(".trim($row["value"]).")";
								$return .= 	"
									<option value='".$row['nor']."_@_".$fclass."_@_".trim($row['name'])."_@_".trim($row['value'])."'>".$caption."</option>";
							}else{
								$return .= "
									<option value='".$row['nor']."_@_".$fclass."_@_".trim($row['name'])."'>".$caption."</option>";
							}
						}
					}
					$return .= "
						</select>
				</td></tr>
			</table>
		</td></tr>
		</table>
	</div>";
	$return .= "
		<form action=\"".$save_url."?value=".$value."\" name=\"save_form\"  id=\"save_form\" method=\"post\">
		<input type=\"hidden\" name=\"content\" id=\"content\">
		<input type=\"hidden\" name=\"table_name\" id=\"table_name\">
		<input type=\"hidden\" name=\"class_max\" id=\"class_max\">
		<input type=\"hidden\" name=\"menu_url\" id=\"menu_url\">
	</form>";

	
	$return .= "
		<script language=javascript>
		var lists = document.getElementById('lists');
		var txt_name = document.getElementById('txt_name');
		try {
			var txt_value = document.getElementById('txt_value1');
		} catch (Except) {}
		sel_num=lists.length-1;
		item_add();
	</script>";
	return $return;
}

?>