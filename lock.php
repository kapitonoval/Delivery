<?php
     	
   $auth = array('karta' => 'online');//md5()

   if(empty($_SERVER['PHP_AUTH_USER'])){
        header ('WWW-Authenticate: Basic realm="Admin Page"'); //     realm="��������� ����"
        header ('HTTP/1.0 401 Unauthorized');
        exit;
   }

   foreach($auth as $user => $pass){// ��������� ������ $auth �������� ������ � ������
       if($_SERVER['PHP_AUTH_USER'] === $user && $_SERVER['PHP_AUTH_PW'] === $pass) $��� = true;
       else $key = false;
   }


   if(!$���){
        header ('WWW-Authenticate: Basic realm="Admin Page"'); //     realm="��������� ����"
        header ('HTTP/1.0 401 Unauthorized');
        exit;
   }


?>