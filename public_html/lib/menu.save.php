<?php
	/********$content를 받아서 자료기지에 보관하기**********/

	header("Cache-Control:no-cache; must-revalidate");
	header("Content-type:text/html; charset=utf-8");
	include_once("../lib/config.inc.php");
	include_once("../lib/database.inc.php");

	$con = connect_database();
	$table_name=$_POST["table_name"];	//"menu_table";//테블이름
	$class_max=$_POST["class_max"];		//3;				//계층수
	$menu_url=$_POST["menu_url"];
	$content=$_POST["content"];
	
	$value=$_GET["value"];
	/***********f()를 초기화***************************/
	$fields = "";
	$f = array();
	for($i=1;$i<=$class_max;$i++){
		$fields=$fields."f".$i.",";
		$f[$i]=0;
	}
	if($value!=0)
		$fields = "value," . $fields;
	if($content!=""){
		$content = explode("&&&",$content);
	/************Menu항목을 content()배렬에 보관*************/
		for($i=0; $i<count($content)-1; $i++){
			$excstr = "";
			$key = explode("_@_", $content[$i]);
				
	/*****************삭제될 항목이 아니면 f()를 갱신*******/
			if($key[0]*1 != -1){
				$f[$key[1]] = $f[$key[1]]+1;
				for($j=$key[1]+1; $j<=$class_max; $j++){
					$f[$j] = 0;
				}
			}
	/*****************새끼메뉴가 없으면 flag=1 없으면 flag=0 ****/
			if($i==(count($content)-2)){
				$flag=1;
			}else{
				$key1=explode("_@_", $content[$i+1]);
				if($key[1]<$key1[1]){
					$flag=0;
				}
				else{
					$flag=1;
				}
			}
	/***************자료기지에  보관 *********************/
			if($key[0]*1 == 0){	//새로 추가되였으면
				$excstr .= "insert into ".$table_name."(name,".$fields."flag) values('".$key[2]."'";
				if($value!=0)
					$excstr .= ",'" . $key[count($key)-1] . "'";
				for($j=1;$j<=$class_max;$j++){
					$excstr = $excstr.",".$f[$j];
				}
				$excstr .= ",".$flag.")";
			}
			elseif($key[0]*1==-1){	//삭제될 항목이면
				$excstr .= "delete from ".$table_name." where nor=".$key[1];
			}
			else{				//기타 나머지는 갱신
				$excstr .= "update ".$table_name." set name='".$key[2]."'";
				if($value!=0)
					$excstr .= ",value='" . $key[count($key)-1] . "'";
				for($j=1;$j<=$class_max;$j++){
					$excstr .= ",f".$j."=".$f[$j];
				}
				$excstr .= ",flag=".$flag." where nor=".$key[0];
			}
			@mysql_query($excstr);
		}
	}
	close_database($con);
	echo "<script language=javascript>
		alert(\"Successfully saved!\");
		window.open('$menu_url','_self');
		</script>";

?>
