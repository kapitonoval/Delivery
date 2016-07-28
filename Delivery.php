<?php


include_once '../libs/php/classes/aplStdClass.php';
/**
 * Created by PhpStorm.
 * User: alexey
 * Date: 27.07.16
 * Time: 16:41
 */
class Delivery extends aplStdAJAXMethod
{
    // для перевода всех приложений в режим разработки раскоментировать и установить FALSE
    protected   $production = false;

    public 	    $user_access = 0; 		// user right (int)
    protected 	$user_id = 0;			// user id with base
    public 		$User = array(); 		// authorised user info

    public function __construct()
    {
        // connectin to database
        $this->db();

        $this->setUserId(isset($_SESSION['access']['user_id'])?$_SESSION['access']['user_id']:0);

        if ($this->getUserId() == 0 ){
            echo '*** - '.$this->getUserId().' - '.$_SESSION['access']['user_id'];
//            header('Location: ../');
            exit;
        }

        $this->setUser($this->getUserId());

        // geting rights
        $this->setUserAccess($this->get_user_access_Database_Int($this->getUserId()));


        // calls ajax methods from POST
        if(isset($_POST['AJAX'])){
            $this->_AJAX_($_POST['AJAX']);
            $this->responseClass->response['data']['access'] = $this->user_access;
            $this->responseClass->response['data']['id'] = $this->user_id;
        }

        // calls ajax methods from GET
        if(isset($_GET['AJAX'])){
            $this->_AJAX_($_GET['AJAX']);
        }

    }

    function __destruct()
    {
//        $this->db();
//        $this->mysqli->close();
    }

    public function printArray($arr){
        return $this->printArr($arr);
    }

    /**
     * окно с деталями
     */
    protected function showDetails_AJAX(){
        $query = "SELECT*FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `id` = '".(int)$_GET['id']."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);

        $targetRows = [];
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()) {
                $targetRows = $row;
            }
        }

        $echo1 = '{@1}'.nl2br($targetRows['target']);

        $echo2 = '{@2}';
        $echo3 = '{@3}'.nl2br($targetRows['docs']);
        $echo3 .= '{@4}'.nl2br($targetRows['date_delivery']);
        $echo3 .= '{@5}'.nl2br($targetRows['contacts']);

        $query = "SELECT * FROM `".DOSTAVKA_SMALL_ROW_TBL."` inner join `os__manager_list` AS `omml` ON `karta_kurijera_task`.`id_manager` = `omml`.`id`   WHERE `karta_kurijera_task`.`id_parent`='".(int)$_GET['id']."'";

        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $i=1;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $echo2 .= $i.'). '.$row['actions'].' <span style="color:grey">'.$row['name'].' '.substr($row['last_name'], 0, 2).'.</span><br/>';
                $i++;
            }
        }
        echo $echo1.$echo2.$echo3;
        exit;
    }

    /**
     * получаем всех пользователей
     * @return array
     */
    public function getAllUsers(){
        $query = "SELECT * FROM `". MANAGERS_TBL ."`";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);

        $data = [];
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $data[$row['id']] = $row['name'].' '.$row['last_name'];
            }
        }
        return $data;
    }
    /**
     * @param array $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * запрос таблицы адресов
     */
    protected function queryForAddress_AJAX(){
        $id_row = $_POST['id_row'];
//        $id_big_row = $_POST['id_big_row'];
        $address_data = $_POST['address_data'];
        $access = $_POST['crops'];
        $amanager_id = $_POST['user_id'];

        $tableName['suppliers']='os__supplier_list';//nickName    addres
        $tableName['clients']='os__client_list'; //company      delivery_address

        switch($address_data){
            case 'suppliers':

//
                echo '<form id="get_client_addres_for_new_row">';

                unset($_POST['name']);
                foreach ($_POST as $key => $value) {
                    echo '<input type="hidden" name="'.$key.'" value="'.$value.'">';
                }


                // запрашиваем данные по клиентам
                $query = "SELECT * FROM `".SUPPLIERS_TBL."`";

                $query .= " ORDER BY nickName ASC";

                $result = $this->mysqli->query($query) or die($this->mysqli->error);
                $supplierData = [];
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {

                        $supplierData[$row['id']] = $row;
                    }
                }
                // echo $query;
                // echo '<pre>';
                // print_r($clients_arr);
                // echo '<pre>';
                echo '<div class="tableAddress" style="display:table">';
                // делаем запрос на контактные лица по клиенту и их данные
                // $query = "SELECT * FROM `".CLIENT_CONT_FACES_TBL."` ";
                echo '<h2>Выберите поставщика из списка:</h2>';
                foreach ($supplierData as $key => $row) {

                    echo '<div class="row2 checkThisClient" data-id="'.$row['id'].'">
							<div class="cell2">'.$row['nickName'].'</div>
						</div>';
                }

                echo '<input type="hidden" name="client_id" value="'.$supplierData[0]['id'].'">';
                echo '<input type="hidden" name="AJAX" value="get_supplier_addres_for_new_row">';

                echo '</div>';
                // echo '*2*';
                echo '</form>';
                break;

                break;
            case 'clients':

                echo '<form id="get_client_addres_for_new_row">';

                unset($_POST['name']);
                foreach ($_POST as $key => $value) {
                    echo '<input type="hidden" name="'.$key.'" value="'.$value.'">';
                }


                // запрашиваем данные по клиентам
                $query = "SELECT * FROM `".CLIENTS_TBL."`";
                // если юзер админ ненужно грузить базу лишней выборкой - выгружаем все
                if($access!=1){
                    // получаем id сонтактных клиентов и контактных лиц прикрепленнных к менеджеру
                    $query_get_relate = "SELECT * FROM `".RELATE_CLIENT_MANAGER_TBL."` ";
                    $query_get_relate .= " WHERE `manager_id`= $amanager_id ";

                    $result = $mysqli->query($query_get_relate) or die($mysqli->error);

                    $clients_id =  array();
                    $i = 0;
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // запоминаем id клиентов
                            $clients_id[$row['client_id']] = $row['client_id'];
                            $i++;
                        }
                    }


                    if($i == 0){
                        echo "<div style='padding:5px 5px 5px 10px; font-size:15px;color:grey; text-align:center; margin-top:200px'>К сожалению у вас пока не заведено ни одного клиента.</div>";
                        return;
                    }

                    $clients_id_str = "'".implode("','", $clients_id)."'";
                    $query .= " WHERE id IN (".$clients_id_str.")";
                }
                $query .= " ORDER BY company ASC";

                $result = $this->mysqli->query($query) or die($this->mysqli->error);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // $clients_row[$row['client_id']]['address_row'] = array();
                        $clients_arr[$row['id']] = $row;
                    }
                }
                // echo $query;
                // echo '<pre>';
                // print_r($clients_arr);
                // echo '<pre>';
                echo '<div class="tableAddress" style="display:table">';
                // делаем запрос на контактные лица по клиенту и их данные
                // $query = "SELECT * FROM `".CLIENT_CONT_FACES_TBL."` ";
                echo '<h2>Выберите клиента из списка:</h2>';
                foreach ($clients_arr as $key => $item) {

                    echo '<div class="row2 checkThisClient" data-id="'.$item['id'].'">
							<div class="cell2">'.$item['company'].'</div>
						</div>';
                }

                echo '<input type="hidden" name="client_id" value="'.$clients_arr[0]['id'].'">';
                echo '<input type="hidden" name="AJAX" value="get_client_addres_for_new_row">';

                echo '</div>';
                // echo '*2*';
                echo '</form>';
                break;
            default:
                echo ("адреса не найдены");
        }
        exit;
    }

    /**
     * получение списка контактов для выбранного клиента
     */
    protected function get_client_addres_for_new_row_AJAX()
    {
        echo '<form id="get_client_address">';
        unset($_POST['AJAX']);
        foreach ($_POST as $key => $value) {
            echo '<input type="hidden" name="'.$key.'" value="'.$value.'">';
        }
        // CLIENT_ADRES_TBL

        $client_id = $_POST['client_id'];

        // получаем адреса по клиенту
        $query = "SELECT * FROM `".CLIENTS_TBL."` WHERE id = '".$client_id."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $client =  array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // запоминаем id клиентов
                $client = $row;
            }
        }


        // получаем адреса по клиенту
        $query = "SELECT * FROM `".CLIENT_ADRES_TBL."` WHERE parent_id = '".$_POST['client_id']."' AND `table_name` = 'CLIENTS_TBL'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $client['adress'] =  array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // запоминаем id клиентов
                $client['adress'][] = $row;
            }
        }

        // получаем телефоны по клиенту
        $query = "SELECT * FROM `".CONT_FACES_CONTACT_INFO_TBL."` WHERE parent_id = '".$_POST['client_id']."' AND `table` = 'CLIENTS_TBL' AND type = 'phone' ";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $client['phone'] =  array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // запоминаем id клиентов
                $client['phone'][] = $row;
            }
        }

        // получаем контактные лица по клиенту
        $query = "SELECT * FROM `".CLIENT_CONT_FACES_TBL."` WHERE client_id = '".$_POST['client_id']."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $cont_face_arr =  array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // запоминаем конт. лица
                $client['cont_face'][$row['id']] = $row;
                // запоминаем id конт. лиц
                $cont_face_arr[$row['id']] = $row['id'];
            }
        }

        if(count($cont_face_arr) > 0){
            // получаем телефоны по клиенту
            $query = "SELECT * FROM `".CONT_FACES_CONTACT_INFO_TBL."` WHERE parent_id IN ('".implode("','", $cont_face_arr)."') AND `table` = 'CLIENT_CONT_FACES_TBL' AND type = 'phone' ";
            $result = $this->mysqli->query($query) or die($this->mysqli->error);
            $cont_face_phone_arr =  array();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // запоминаем id клиентов
                    $cont_face_phone_arr[$row['parent_id']][] = $row;
                }
            }

            // echo '<pre>';
            // print_r($cont_face_phone_arr);
            // echo '<pre>';
            foreach ($client['cont_face'] as $key => $value) {
                $client['cont_face'][$key]['phone'] = $cont_face_phone_arr[$key];
            }

        }


        echo '<div class="tableAddress" style="display: table;">';
        if(count($client['adress']) > 0){
            // foreach ($variable as $key => $value) {
            // 	# code...
            // }
            foreach($client['adress'] as $item){
                echo '<div class="row2" onClick="getThisAddress(this,\''.$_POST['id_row'].'\')">

					<div class="cell2" data-id="'.$client['id'].'">'.$client['company'].'</div><div class="cell2">';
                // вывод адреса
                echo ($item['postal_code']>0)?$item['postal_code'].', ':'';
                echo ($item['city']!="")?$item['city'].', ':'';
                echo ($item['street']!="")?$item['street'].', ':'';
                echo ($item['house_number']>0)?'дом '.$item['house_number'].', ':'';
                echo ($item['bilding']>0)?'строение '.$item['bilding'].'':'';
                echo ($item['korpus']>0)?'/'.$item['korpus'].',  ':'';
                echo ($item['liter']!="")?'/'.$item['liter'].', ':'';
                echo ($item['office']!="")?'оф. '.$item['office'].'<br/>':'';
                echo ($item['note']!="")?$item['note'].'<br/>':'';
                // вывод телефонов
                if(count($client['phone']) > 0){
                    foreach ($client['phone'] as $value) {
                        echo ($value['contact']!="")?$value['contact'].'':'';
                        echo ($value['dop_phone']!="")?' доб. '. $value['dop_phone'].'<br/>':'';
                    }
                }

                // вывод адресов
                if(count($client['cont_face']) > 0){
                    echo '<br>Контактные лица:';

                    foreach ($client['cont_face'] as $cont_face) {
                        $html = '<br>';
                        $html .= ($cont_face['name']!="")?$cont_face['name']:'';
                        $html .= ($cont_face['last_name']!="")?' '.$cont_face['last_name']:'';
                        $html .= ($cont_face['surname']!="")?' '.$cont_face['surname']:'';
                        $html .= ($cont_face['position']!="")?' ('.$cont_face['position'].')':'';
                        $html .= ($html != '')?'':'';
                        echo $html;
                        if(is_array($cont_face['phone']) && count($cont_face['phone']) >0){
                            // echo '<pre>';
                            // print_r($cont_face['phone']);
                            // echo '<pre>';
                            foreach ($cont_face['phone'] as $phones) {
                                $phone_html = '<br>';
                                $phone_html .= ($phones['contact']!="")?'тел.: '.$phones['contact'].'':'';
                                $phone_html .= ($phones['contact']!="" && $phones['dop_phone']!="")?' доб. '. $phones['dop_phone'].'':'';
                                echo ($phone_html!='<br>')?$phone_html:'';
                            }
                        }
                    }




                    // if($item['phone']!=""){echo 'тел.: '.$item['phone'].'<br/> ';}
                    echo '</div></div>';
                }
            }
        }else{
            echo 'У клиента "'.$client['company'].'" не заведён адрес.';
        }
        echo '</div>';
        echo '<input type="hidden" name="client_id" value="'.$client_id.'">';
        echo '<input type="hidden" name="AJAX" value="get_client_addres_for_new_row">';
        echo '</form>';
        exit;
    }
    /**
     * получение списка контактов для выбранного поставщика
     */
    protected function get_supplier_addres_for_new_row_AJAX()
    {
        echo '<form id="get_client_address">';
        unset($_POST['AJAX']);
        foreach ($_POST as $key => $value) {
            echo '<input type="hidden" name="'.$key.'" value="'.$value.'">';
        }
        // CLIENT_ADRES_TBL

        $client_id = $_POST['client_id'];

        // получаем адреса по клиенту
        $query = "SELECT * FROM `".SUPPLIERS_TBL."` WHERE id = '".$client_id."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $client =  array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // запоминаем id клиентов
                $client = $row;
            }
        }


        // получаем адреса по клиенту
        $query = "SELECT * FROM `".CLIENT_ADRES_TBL."` WHERE parent_id = '".$_POST['client_id']."' AND `table_name` = 'SUPPLIERS_TBL'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $client['adress'] =  array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // запоминаем id клиентов
                $client['adress'][] = $row;
            }
        }

        // получаем телефоны по клиенту
        $query = "SELECT * FROM `".CONT_FACES_CONTACT_INFO_TBL."` WHERE parent_id = '".$_POST['client_id']."' AND `table` = 'SUPPLIERS_TBL' AND type = 'phone' ";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $client['phone'] =  array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // запоминаем id клиентов
                $client['phone'][] = $row;
            }
        }

        // получаем контактные лица по клиенту
        $query = "SELECT * FROM `".SUPPLIERS_CONT_FACES_TBL."` WHERE supplier_id = '".$_POST['client_id']."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $cont_face_arr =  array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // запоминаем конт. лица
                $client['cont_face'][$row['id']] = $row;
                // запоминаем id конт. лиц
                $cont_face_arr[$row['id']] = $row['id'];
            }
        }

        if(count($cont_face_arr) > 0){
            // получаем телефоны по клиенту
            $query = "SELECT * FROM `".CONT_FACES_CONTACT_INFO_TBL."` WHERE parent_id IN ('".implode("','", $cont_face_arr)."') AND `table` = 'SUPPLIERS_CONT_FACES_TBL' AND type = 'phone' ";
            $result = $this->mysqli->query($query) or die($this->mysqli->error);
            $cont_face_phone_arr =  array();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // запоминаем id клиентов
                    $cont_face_phone_arr[$row['parent_id']][] = $row;
                }
            }

            // echo '<pre>';
            // print_r($cont_face_phone_arr);
            // echo '<pre>';
            foreach ($client['cont_face'] as $key => $value) {
                $client['cont_face'][$key]['phone'] = $cont_face_phone_arr[$key];
            }

        }


        echo '<div class="tableAddress" style="display: table;">';
        if(count($client['adress']) > 0){
            // foreach ($variable as $key => $value) {
            // 	# code...
            // }
            foreach($client['adress'] as $item){
                echo '<div class="row2" onClick="getThisAddress(this,\''.$_POST['id_row'].'\')">

					<div class="cell2" data-id="'.$client['id'].'">'.$client['nickName'].'</div><div class="cell2">';
                // вывод адреса
                echo ($item['postal_code']>0)?$item['postal_code'].', ':'';
                echo ($item['city']!="")?$item['city'].', ':'';
                echo ($item['street']!="")?$item['street'].', ':'';
                echo ($item['house_number']>0)?'дом '.$item['house_number'].', ':'';
                echo ($item['bilding']>0)?'строение '.$item['bilding'].'':'';
                echo ($item['korpus']>0)?'/'.$item['korpus'].',  ':'';
                echo ($item['liter']!="")?'/'.$item['liter'].', ':'';
                echo ($item['office']!="")?'оф. '.$item['office'].'<br/>':'';
                echo ($item['note']!="")?$item['note'].'<br/>':'';
                // вывод телефонов
                if(count($client['phone']) > 0){
                    foreach ($client['phone'] as $value) {
                        echo ($value['contact']!="")?$value['contact'].'':'';
                        echo ($value['dop_phone']!="")?' доб. '. $value['dop_phone'].'<br/>':'';
                    }
                }

                // вывод адресов
                if(isset($client['cont_face']) && count($client['cont_face']) > 0){
                    echo '<br>Контактные лица:';

                    foreach ($client['cont_face'] as $cont_face) {
                        $html = '<br>';
                        $html .= ($cont_face['name']!="")?$cont_face['name']:'';
                        $html .= ($cont_face['last_name']!="")?' '.$cont_face['last_name']:'';
                        $html .= ($cont_face['surname']!="")?' '.$cont_face['surname']:'';
                        $html .= ($cont_face['position']!="")?' ('.$cont_face['position'].')':'';
                        $html .= ($html != '')?'':'';
                        echo $html;
                        if(is_array($cont_face['phone']) && count($cont_face['phone']) >0){
                            // echo '<pre>';
                            // print_r($cont_face['phone']);
                            // echo '<pre>';
                            foreach ($cont_face['phone'] as $phones) {
                                $phone_html = '<br>';
                                $phone_html .= ($phones['contact']!="")?'тел.: '.$phones['contact'].'':'';
                                $phone_html .= ($phones['contact']!="" && $phones['dop_phone']!="")?' доб. '. $phones['dop_phone'].'':'';
                                echo ($phone_html!='<br>')?$phone_html:'';
                            }
                        }
                    }




                    // if($item['phone']!=""){echo 'тел.: '.$item['phone'].'<br/> ';}
                    echo '</div></div>';
                }
            }
        }else{
            echo 'У поставщика "'.$client['nickName'].'" не заведён адрес.';
        }
        echo '</div>';
        echo '<input type="hidden" name="client_id" value="'.$client_id.'">';
        echo '<input type="hidden" name="AJAX" value="get_client_addres_for_new_row">';
        echo '</form>';
        exit;
    }

    /**
     * добавление поездки в базу
     */
    protected function add_new_address_AJAX(){
        $date = $_POST['date'];
        $flag = 'off';
        $add_num_rows = $_POST['add_num_rows'];

        $query = "INSERT INTO `".DOSTAVKA_BIG_ROW_TBL."` SET ";
        $query .= "  `num_rows` =?";
        $query .= " , `status` =?";
        $query .= " , `date` =?";


        $stmt = $this->mysqli->prepare($query) or die($this->mysqli->error);
        $stmt->bind_param( 'sss', $add_num_rows ,$flag, $date ) or die($this->mysqli->error);
        $stmt->execute() or die($this->mysqli->error);
        $result = $stmt->get_result();
        $stmt->close();


        echo $this->mysqli->insert_id;
        exit;
    }

    /**
     * пометка строки как удалённой
     * псевдоудаление для менеджеров
     */
    protected function del_big_row_AJAX(){

        $id =  trim($_POST['id_kurier']);
        $query = "UPDATE `".DOSTAVKA_BIG_ROW_TBL."` SET `disable_editing` = '".$this->getUserId()."' WHERE `id` = '".(int)$id."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
//        echo $this->mysqli->insert_id;

        echo "OK";
        exit;
    }


    /**
     * полное удаление строки
     */
    protected function del_big_row_real_AJAX(){
        $id =  trim($_POST['id_kurier']);
        # удаление малых строк
        $query = "DELETE FROM `".DOSTAVKA_SMALL_ROW_TBL."` WHERE `id_parent` = '".(int)$id."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        # удаление больших строк
        $query = "DELETE FROM `".DOSTAVKA_BIG_ROW_TBL."` WHERE `id` = '".(int)$id."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        echo "OK";
        exit;
    }

    /**
     * @return int
     */
    public function getUserAccess()
    {
        return $this->user_access;
    }

    /**
     * @param int $user_access
     */
    public function setUserAccess($user_access)
    {
        if ($this->getUserId() > 0) {
            $this->user_access = $user_access;
        }
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }


    /**
     * get user access
     *
     * @param $id
     * @return int
     */
    private function get_user_access_Database_Int($id){
        $query = "SELECT * FROM `".MANAGERS_TBL."` WHERE id = '".$id."'";
        $result = $this->mysqli->query($query) or die($this->mysqli->error);
        $int = 0;
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
                $int = (int)$row['access'];
                $this->user = $row;
            }
        }
        return $int;
    }

}