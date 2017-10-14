<?php
function connection(){
    $hostname   = '';
    $type		= 'mysql5';
    $port       =  3306;
    $username	= '';
    $password	= '';
    $database	= '';

    try {
        $connection = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exc) {
        die("Connection error: " . $exc->getMessage());
    }
    return $connection;

}



if (isset($state) && !empty($state) && isset($_POST['product_id']) && !empty($_POST['product_id'])){
    $product_id = $_POST['product_id'];
    $defaultProfiles = array(
        "SI" =>6,
        "HR" =>7,
        "IT" =>8,
        "AT" =>9,
        "CZ" =>10,
        "PL" =>12,
        "HU" =>13,
        "BG" =>14,
        "RO" =>15,
        "GR" =>16,
        "BA" =>17,
        "RS" =>18,
        "MK" =>19,
        "SK" =>27,
        "LT" =>29,
        "LV" =>30,
        "EE" =>31,
        "DE" =>32
    );

    if (!empty($state) &&  !empty($product_id)){
        $sql    = "SELECT * FROM phone_order_productProfiles WHERE product_id = {$product_id} AND state = '{$state}' ORDER BY id DESC LIMIT 1";
        $pdo =  connection();
        $statement = $pdo->prepare($sql);
        $statement->execute();
        $data = $statement->fetch();
     
        if ($data && $data['profile'] != 0){
            $_POST['profile'] = $data['profile'];
        } else {
            $_POST['profile'] = $defaultProfiles[$state];
        }

    }
}




?>