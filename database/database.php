<?php 
$host_db = "localhost";
$user_db = "root";
$pass_db = "";
$nama_db = "laboratorium";
$con = mysqli_connect($host_db,$user_db,$pass_db,$nama_db);

mysqli_select_db($con, $nama_db);

?> 