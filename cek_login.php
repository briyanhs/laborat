<link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">

<?php include 'database/database.php';
//aktifkan session
session_start();
//menangkap data dari form login
$username = $_POST['username'];
$password = md5($_POST['password']);

// menyeleksi data user dengan username dan password yang sesuai
$login = mysqli_query($con,"select * from user where username='$username' and password='$password'");

// menghitung jumlah data yang ditemukan
$cek = mysqli_num_rows($login);
// cek apakah username dan password di temukan pada database
if($cek > 0){
 
	$data = mysqli_fetch_assoc($login);
 
	// cek jika user login sebagai admin
	if($data['level']=="Admin"){
 
		// buat session login dan username
		$_SESSION['username'] = $username;
		$_SESSION['level'] = "Admin";
        $_SESSION['status'] = "login";
		// alihkan ke halaman dashboard admin
		echo '<div class="alert alert-success text-center" role="alert">Berhasil Log In</div>';
        header("refresh: 3; url=admin/dashboard_lab.php");
		//header("location:admin/dashboard_lab.php");
 
	// cek jika user login sebagai user
	}else if($data['level']=="User"){
		// buat session login dan username
		$_SESSION['username'] = $username;
		$_SESSION['level'] = "User";
        $_SESSION['status'] = "login";
		// alihkan ke halaman dashboard user
		echo '<div class="alert alert-success text-center" role="alert">Berhasil Log In</div>';
        header("refresh: 3; url=user/halaman_user.php");
		//header("location:user/halaman_user.php");
 
	}else{
		// alihkan ke halaman login kembali
		header("location:index.php?pesan=gagal");
	}	
}else{
	header("location:index.php?pesan=gagal");
}

?>