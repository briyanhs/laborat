<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Proses Login</title>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<style>
		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background-color: #f4f6f9;
		}

		.swal2-popup {
			font-size: 0.9rem !important;
			border-radius: 15px !important;
		}

		.swal2-title {
			font-size: 1.2rem !important;
		}
	</style>
</head>

<body>

	<?php
	include 'database/database.php';

	session_start();

	$username = $_POST['username'];
	$password = md5($_POST['password']);

	// Pastikan kolom-kolom ini sesuai dengan tabel user Anda (id_user, username, password, level, nama)
	$login = mysqli_query($con, "select id_user, username, level, nama from user where username='$username' and password='$password'");
	$cek = mysqli_num_rows($login);

	if ($cek > 0) {
		$data = mysqli_fetch_assoc($login);

		$_SESSION['username'] = $username;
		$_SESSION['status'] = "login";
		$_SESSION['user_id'] = $data['id_user'];
		$_SESSION['nama_lengkap'] = $data['nama'];

		// Tentukan target redirect
		$redirect_url = ($data['level'] == "Admin") ? 'admin/dashboard_lab.php' : 'user/halaman_user.php';

		// Set session level
		$_SESSION['level'] = $data['level'];

		// Tampilkan Notifikasi Minimalis
		echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Login Berhasil',
            text: 'Selamat datang, " . $data['nama'] . "',
            width: 320,             // Lebar kotak lebih kecil (minimalis)
            padding: '1em',         // Padding lebih rapat
            timer: 3000,            // Waktu tampil lebih cepat (1.5 detik)
            showConfirmButton: false,
            backdrop: `rgba(0,0,0,0.3)`, // Background redup yang lebih soft
            position: 'center'      // Posisi tetap di tengah
        }).then(() => {
            window.location.href = '$redirect_url';
        });
    </script>";
	} else {
		header("location:index.php?pesan=gagal");
	}
	?>

</body>

</html>