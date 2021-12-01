<?php
require_once('../../api/v1/config/constants.php');
try{
    $data = file_get_contents('php://input');
}catch(Exception $e){
     $log.= 'error: '.$e->getMessage();
     error_log($log);
}
$order_id = 0;
$orderJson = FALSE;
if(!empty($data)){
    $order_information = json_decode($data);
    
    $isOrder = 0;
    $order_id =$order_information->id;
    $order_num =$order_information->order_number;
    $conn = new mysqli(API_DB_HOST, API_DB_USER, API_DB_PASS, API_DB_NAME);
    if (!$conn) {
        error_log("Connection failed: " . $conn->connect_error);
        exit;
    } 
    // check order sync
    $sql = "SELECT * from " . TABLE_PREFIX . "webhook_data WHERE order_id = '".$order_id."' AND order_number = '".$order_num."'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
              header('HTTP/1.0 200 OK');
              exit();
        }
    } else {
       // curl request qill go here
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, API_URL."api/v1/orders/create-order-files/".$order_id);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $ack = curl_exec($curl);
        curl_close($curl);
        if ($ack) {
           header('HTTP/1.0 200 OK');
           exit();
        }
    }
    if($conn) mysqli_close($conn); 
}
?>