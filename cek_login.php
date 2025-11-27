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

	// 1. Ambil input (JANGAN di-MD5-kan lagi!)
	$username = $_POST['username'];
	$password_input = $_POST['password'];

	// 2. Ambil data user berdasarkan username saja
	// Kita gunakan Prepared Statement agar aman dari hack SQL Injection
	$stmt = mysqli_prepare($con, "SELECT * FROM user WHERE username = ?");
	mysqli_stmt_bind_param($stmt, "s", $username);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	// 3. Cek apakah username ditemukan?
	if ($row = mysqli_fetch_assoc($result)) {

		// 4. Cek Password menggunakan password_verify()
		// Ini akan mencocokkan input "123456" dengan hash "$2y$10$..." di database
		if (password_verify($password_input, $row['password'])) {

			// === LOGIN BERHASIL ===

			// Regenerasi ID session biar aman
			session_regenerate_id(true);

			// === SECURITY: GENERATE CSRF TOKEN ===
			if (empty($_SESSION['csrf_token'])) {
				$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
			}
			// =====================================

			$_SESSION['username'] = $username;
			$_SESSION['status'] = "login";
			$_SESSION['user_id'] = $row['id_user'];
			$_SESSION['nama_lengkap'] = $row['nama']; // Mengambil nama asli dari database
			$_SESSION['level'] = $row['level'];

			// Arahkan sesuai level
			$redirect_url = ($row['level'] == "Admin") ? 'admin/dashboard_lab.php' : 'user/halaman_user.php';

			echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Login Berhasil',
                text: 'Selamat datang, " . htmlspecialchars($row['nama']) . "',
                width: 320,
                padding: '1em',
                timer: 1500,
                showConfirmButton: false,
                backdrop: `rgba(0,0,0,0.3)`,
                position: 'center'
            }).then(() => {
                window.location.href = '$redirect_url';
            });
        </script>";
		} else {
			// Password Salah
			header("location:index.php?pesan=gagal");
		}
	} else {
		// Username Tidak Ditemukan
		header("location:index.php?pesan=gagal");
	}

	mysqli_stmt_close($stmt);
	mysqli_close($con);
	?>

</body>

</html>