<?php
include '_DB.php';

/**************DB CONNECT**************/
$_CONFIG_DATABASE = array
					(
						'type'		=>	'mysql5',
						'address'	=>	'',
						'port'		=>	3306,
						'username'	=>	'',
						'password'	=>	'',
						'database'	=>	''
					);
 $db = new Database($_CONFIG_DATABASE, true);
 $sql="SET NAMES utf8";
 $db->query($sql, 1);
