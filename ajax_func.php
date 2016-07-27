<?php
header('Content-Type: text/html; charset=utf-8');




include_once("../config.php");
include_once("../libs/mysql.php");
include_once("../libs/mysqli.php");


// echo DOSTAVKA_BIG_ROW_TBL;exit;
//////////////////////////////////////////////

//////////////////////ФУНКЦИЯ ОТПРАВКИ БЕЗ ВЛОЖЕНИЯ *****START******
function sendmail($to,$fromemail,$from_name,$subject,$message,$file_path) {
	 	$charset="UTF-8";
		$from = '=?'. $charset .'?b?'. $from_name .'?='; 
		$mailfrom = ' <'. $fromemail .'>';
		$headers  = "Content-type: text/html;  \r\n";
	    $headers .= "From: ".$from." <dostavka@apelburg.ru>\r\n";
		if(!mail($to, $subject, $message, $headers)){
			echo "<br><b style='color:red'>Собщение НЕ отправлено</b>";  
		}
};	
//////////////////////ФУНКЦИЯ ОТПРАВКИ БЕЗ ВЛОЖЕНИЯ *****END******
//////////////////////////////////////////////
//////////////////////////////////////////////
////////next
	if(isset($_GET['name']) && $_GET['name']=='changeTextareaTD'){
		$column = $_GET['column'];
		if($column=='second_point'){
		$task = DOSTAVKA_SMALL_ROW_TBL;	
		}else{
		$task = DOSTAVKA_BIG_ROW_TBL;
		}		
		$id_big_row = $_GET['id_big_row'];
		$text = $_GET['text'];
		
		$query = "UPDATE `$task` SET `$column`='$text' WHERE `id` = $id_big_row";
		//echo $query;
		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
		echo "rec_text";		
	}


if(isset($_POST['name'])){
	/////////next


	////////next
	if($_POST['name']=='getThisAddress'){
		$company = $_POST['company'];
		$address = $_POST['address'];
		$id_row = $_POST['id_row'];
		$id_row = str_replace('tr_for_id_','',$id_row);
		$parent_id_address=$_POST['parent_id_address'];
		$target_typpe=$_POST['target_typpe'];
		
		$query = "UPDATE `".DOSTAVKA_BIG_ROW_TBL."` SET 
			`target`='$company',
			`contacts`='$address',
			`parent_id_address`='$parent_id_address', 
			`target_typpe`='$target_typpe' 
			WHERE `id` = $id_row";

		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
		//echo "$company    $address    $id_row     $target_typpe     $parent_id_address";
		echo "OK";
	}
	////////next
	if($_POST['name']=='kk_task'){
		$id_parent = $_POST['id_parent'];
		$id_manager = $_POST['id_manager'];
		$date = $_POST['date'];
		$give_take = $_POST['give_take'];
		$actions = $_POST['actions'];
		$second_point = $_POST['point_start'];
		//print_r ($_POST);
		//".DOSTAVKA_SMALL_ROW_TBL."
		$query = "
		INSERT INTO `".DOSTAVKA_SMALL_ROW_TBL."` (
			`id` ,
			`id_parent` ,
			`id_manager` ,
			`date` ,
			`actions` ,
			`query_change` ,
			`dop_redactor` ,
			`status_task`,
			`give_take`,
			`second_point`
			)
			VALUES (
			NULL , '$id_parent', '$id_manager', '$date', '$actions', '', '', '', '$give_take', '$second_point'
			);
		";
		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
		echo mysql_insert_id();
		//echo $query;
	}
	////////next
	if($_POST['name']=="cleanThisePosition"){
	$text_id = trim($_POST['textarea_id']);
	$id = str_replace('task_','',$text_id);
	$query = "DELETE FROM `".DOSTAVKA_SMALL_ROW_TBL."` WHERE `".DOSTAVKA_SMALL_ROW_TBL."`.`id` = $id";
	$result = mysql_query($query,$db);
	//echo $query.'     ';
	if(!$result)exit(mysql_error());
	echo "OK";	}
	////////next
	if($_POST['name']=='checkOne'){
		$id_smal_row = $_POST['id_smal_row'];
		$status_task = $_POST['status_task'];
		$man_id = $_POST['user_id'];
		
		//////////////////////меняем статус задачи
		$query = "UPDATE `".DOSTAVKA_SMALL_ROW_TBL."` SET 
			`status_task`='".$status_task."' 
			WHERE `id` = '".$id_smal_row."'";
		//echo $query;
		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
		echo "check_OK";	
		
		//////////////////////узнаем email автора задачи, отправляем оповещение		
		$query = "SELECT * FROM `".DOSTAVKA_SMALL_ROW_TBL."` INNER JOIN `".MANAGERS_TBL."` AS `omml` ON `".DOSTAVKA_SMALL_ROW_TBL."`.`id_manager` = `omml`.`id`   WHERE `".DOSTAVKA_SMALL_ROW_TBL."`.`id` = $id_smal_row";
		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
		
		if(mysql_num_rows($result) > 0){
			while($item = mysql_fetch_assoc($result)){
			////*****
			$email = $item['email'];
			$email_2 = $item['email_2'];
			}
			if($status_task == 1){$status = 'ВЫПОЛНЕНО';}else{$status = 'НЕ ВЫПОЛНЕНО';}
			if(trim($email_2)=='')$email_2=trim($email);
			if(trim($_POST['target'])!='')$text="\"".$_POST['target']."\"<br/><br/>";
			$message="
			<strong>Статус Вашей задачи на ".$_POST['date'].":</strong><br/><br/>
			$text
			<strong>по адресу:</strong> \"".$_POST['adress']."\" <br/> 
			<br/>
			<strong>изменен на</strong> \"$status\".
			<br/><br/>
			С уважением, APELBURG.RU<br/>
			СПБ:      +7  (812)  438-00-55<br/>
			Москва:  +7 (495)  781-57-09<br/>
			www.apelburg.ru
			";
		}
		$fromname="APELBURG / Служба доставки";
		$fromemail="dostavka@apelburg.ru";
		$subject="Оповещение службы доставки APELBURG.RU";
		$file_path='';
		//$email = 'kapitonoval2012@gmail.com';
		sendMail($email_2,$fromemail,$fromname,$subject,$message,$file_path); /// ОТПРАВКА СООБЩЕНИЯ О ГОтовности			
	}
	////////next
	/*
	/// изменил запрос на GET
	if($_POST['name']=='changeTextareaTD'){
		$column = $_POST['column'];
		if($column=='second_point'){
		$task = '".DOSTAVKA_SMALL_ROW_TBL."';	
		}else{
		$task = '".DOSTAVKA_BIG_ROW_TBL."';
		}		
		$id_big_row = $_POST['id_big_row'];
		$text = $_POST['text'];
		
		$query = "UPDATE `$task` SET `$column`='$text' WHERE `id` = $id_big_row";
		//echo $query;
		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
		echo "rec_text";		
	}
	*/
	////////next
	if($_POST['name']=='changeTextareaTableMin'){
		$id_min_row = $_POST['id_min_row'];
		$text = $_POST['text'];
		
		$query = "UPDATE `".DOSTAVKA_SMALL_ROW_TBL."` SET `actions`='".$text."' WHERE `id` = '".$id_min_row."'";
		//echo $query;
		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
		echo "rec_text";	
	}
	////////next
	if($_POST['name']=='checkAllRow'){
		$id_str = $_POST['checkbox'];
		$query = "UPDATE `".DOSTAVKA_SMALL_ROW_TBL."` SET `status_task` = '1' WHERE `id` IN (";
		$query .= "$id_str)";
		//echo $query;
		$result = mysql_query($query,$db);	
		if(!$result)exit(mysql_error());
		echo "update_ok";	
	}
	////////next
	if($_POST['name']=='uncheckAllRow'){
		$id_str = $_POST['checkbox'];
		$query = "UPDATE `".DOSTAVKA_SMALL_ROW_TBL."` SET `status_task` = '0' WHERE .`id` IN (";
		$query .= "$id_str)";
		//echo $query;
		$result = mysql_query($query,$db);	
		if(!$result)exit(mysql_error());
		echo "update_ok";
	}
	////////next
	if($_POST['name']=='checkOneRow'){
		$id_str = $_POST['checkbox'];
		$query = "UPDATE `".DOSTAVKA_SMALL_ROW_TBL."` SET `status_task` = '0' WHERE `id` = $id_str";
		//echo $query;
		$result = mysql_query($query,$db);	
		if(!$result)exit(mysql_error());
		echo "update_ok";
	}
	////////next
	if($_POST['name']=='changeStatusBigRow'){
		$id_big_row = $_POST['id_big_row'];
		$status = $_POST['status'];
		
		$query = "UPDATE `".DOSTAVKA_BIG_ROW_TBL."` SET `status`='$status' WHERE `id` = $id_big_row";
		//echo $query;
		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
		echo "change_status_ok";	
	}
	////////next
	
	if($_POST['name']=='rec_date'){
	
	if(isset($_POST['id_min_row']) && $_POST['id_min_row']==''){echo "Невозможно перенести уже выполненное задание"; return;}
	$id_row = $_POST['id_big_row'];
	$id_min_row = $_POST['id_min_row'];//список ID мелких строк через запятэ
	$old_date = trim($_POST['old_date']);
	$new_date = trim($_POST['new_date']);
	if($old_date == $new_date){echo "Невозможно перенести доставку на текущий день"; return;}
	$date_log = "пермещено с $old_date на $new_date <br/>";
	
	//проверяем наличие данного адреса в  день на который производится перемещение
	
		$query = "SELECT `id` FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `parent_id_address` = (SELECT `parent_id_address` FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `id` = '$id_row') AND `target_typpe`= (SELECT `target_typpe` FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `id` = '$id_row') AND `date`= '$new_date'";
	
	
	
	$query = "SELECT * FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `id`= '".$id_row."'";
		$result = mysql_query($query,$db);
		if(!$result)exit(mysql_error());
			
		if(mysql_num_rows($result) > 0){//если адрес уже существует - берем его ID
			while($item = mysql_fetch_assoc($result)){
			$target_type = $item['target_typpe'];		
			}
		}	
	if($target_type == "" || $target_type == 0){
		$query = "SELECT `id` FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `target` = (SELECT `target` FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `id` = '$id_row') AND `date`= '$new_date'";
	}
	
	$result = mysql_query($query,$db);
	if(!$result)exit(mysql_error());
		
	if(mysql_num_rows($result) > 0){//если адрес уже существует - берем его ID
		while($item = mysql_fetch_assoc($result)){
		$id_big_row = $item['id'];//запоминаем ID адреса
		}
	}else{//если адреса в данный день не существует, создаем новый и берем его ID
		$query = "
	INSERT INTO `".DOSTAVKA_BIG_ROW_TBL."` (`num_rows`,`status`, `parent_id_address`, `target_typpe`,`target` , `actions`, `docs`, `date_delivery`, `contacts`, `disable_editing`) 
  	SELECT  `num_rows`,`status`, `parent_id_address`, `target_typpe`,`target`, `actions`, `docs`, `date_delivery`, `contacts`, `disable_editing` FROM `".DOSTAVKA_BIG_ROW_TBL."` 
    WHERE `id` = $id_row	
	";
		$result = mysql_query($query,$db);
		$id_big_row = mysql_insert_id();//запоминаем id созданной поездки
		}
	
	//----------------- удаляем старую поездку при условии, что все поставленные задачи не были выполнены (условие отработано в javascript)--------------//
	if(isset($_POST['del_old_big_row']) && $_POST['del_old_big_row']>0){
	$query = "DELETE FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `id` = $id_row";	
	//echo $query .'    ';
	$result = mysql_query($query,$db);
	}
	
	//----------------- пишем в новую поездку новую дату --------------//	
	$query = "UPDATE `".DOSTAVKA_BIG_ROW_TBL."` SET `date`='$new_date' WHERE `id` = $id_big_row";
	//echo $query .'    ';
	$result = mysql_query($query,$db);
	
	
	//----------------- присваиваем невыполненным задачам новую поездку --------------//	
	$query = "UPDATE `".DOSTAVKA_SMALL_ROW_TBL."` SET `id_parent` = '$id_big_row', date_log = concat( date_log, '".$date_log."') WHERE `id` IN ($id_min_row)";
	$result = mysql_query($query,$db);		
	//echo $query .'     ';
	if(!$result)exit(mysql_error());
	echo "rec_date";	
	
	}
}
//////////////////////////////////////////////



if(isset($_POST['change_action']) && $_POST['change_action']!=''){
	$action = $_POST['change_action'];
	$text_id = trim($_POST['id_task']);
	$id = str_replace('task_','',$text_id);
}

//////////////////////////////////////////////
//////////////////////////////////////////////
//////////////////////////////////////////////
?>