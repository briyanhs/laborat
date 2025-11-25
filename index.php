<?php include 'database/database.php'; ?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Laboratory</title>
  <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #ececec;
    }

    /* Container utama agar centering bekerja baik di mobile */
    .main-container {
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      /* Tambahkan padding agar tidak mentok di layar HP */
    }

    .box-area {
      border-radius: 20px;
      overflow: hidden;
      /* PERBAIKAN 1: Pindahkan style inline ke sini */
      max-width: 900px;
      width: 100%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
      /* Bayangan lebih halus */
    }

    /* Bagian Kiri (Gambar & Teks) */
    .left-box {
      background: linear-gradient(135deg, #0044ff, #002a9e);
      padding: 40px 30px;
      /* Padding standar desktop */
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }

    .left-box .featured-image img {
      width: 100%;
      max-width: 250px;
      filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.2));
      transition: transform 0.3s ease;
    }

    .left-box:hover .featured-image img {
      transform: scale(1.05);
    }

    /* Bagian Kanan (Form) */
    .right-box {
      padding: 40px 30px;
    }

    .header-text h2 {
      color: #333;
      font-weight: 600;
    }

    .form-control {
      height: 50px;
      border: 2px solid #eee;
      padding-left: 20px;
      font-size: 15px;
      border-radius: 10px;
      transition: all 0.3s;
    }

    .form-control:focus {
      border-color: #0044ff;
      box-shadow: none;
      background-color: #fff;
    }

    .btn-primary {
      height: 50px;
      border-radius: 10px;
      background: #0044ff;
      border: none;
      font-weight: 600;
      letter-spacing: 1px;
      transition: 0.3s;
    }

    .btn-primary:hover {
      background: #0033cc;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 68, 255, 0.3);
    }

    /* PERBAIKAN 2: CSS Responsif untuk layar kecil (HP/Tablet) */
    @media only screen and (max-width: 768px) {
      .box-area {
        /* Memaksa layout menjadi kolom (menumpuk ke bawah) */
        flex-direction: column;
      }

      .left-box {
        /* Tinggi otomatis menyesuaikan konten, jangan difix 200px */
        height: auto;
        padding: 30px 20px;
        /* Padding sedikit diperkecil */
        border-radius: 20px 20px 0 0;
        /* Radius hanya di atas */
      }

      .left-box .featured-image img {
        max-width: 150px;
        /* Logo lebih kecil di HP */
        margin-bottom: 15px;
      }

      .left-box p.fs-2 {
        font-size: 1.5rem !important;
        /* Ukuran font judul diperkecil */
        margin-bottom: 5px;
      }

      .right-box {
        padding: 30px 20px;
        /* Padding form diperkecil */
        border-radius: 0 0 20px 20px;
        /* Radius hanya di bawah */
      }

      .header-text {
        text-align: center;
        /* Header form jadi rata tengah di HP */
      }
    }
  </style>
</head>

<body>

  <div class="container-fluid main-container">

    <div class="row border-0 rounded-5 p-0 bg-white box-area">

      <div class="col-lg-6 col-md-12 left-box">
        <div class="featured-image mb-3">
          <img src="image/logo.png" class="img-fluid" alt="Logo">
        </div>
        <p class="text-white fs-2 text-center" style="font-weight: 700;">Be Verified</p>
        <small class="text-white text-wrap text-center px-2" style="font-weight: 400; opacity: 0.9;">
          Every small step brings you closer to a big goal.
        </small>
      </div>

      <div class="col-lg-6 col-md-12 right-box">
        <div class="row align-items-center">
          <div class="header-text mb-4">
            <h2>Hello Again!</h2>
            <p class="text-secondary">We are happy to have you back.</p>
          </div>

          <form action="cek_login.php" method="post">
            <div class="mb-3">
              <input type="text" name="username" required="required" class="form-control bg-light" placeholder="Username">
            </div>
            <div class="mb-4">
              <input type="password" name="password" required="required" class="form-control bg-light" placeholder="Password">
            </div>
            <div class="mb-3">
              <button class="btn btn-lg btn-primary w-100 fs-6" value="LOGIN">Login</button>
            </div>
          </form>

        </div>
      </div>

    </div>
  </div>

  <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

  <?php if (isset($_GET['pesan'])): ?>
    <script>
      const pesan = "<?= $_GET['pesan'] ?>";

      const ToastInfo = Swal.mixin({
        width: 360,
        padding: '1.5em',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        backdrop: `rgba(0,0,0,0.4)`
      });

      if (pesan === 'gagal') {
        ToastInfo.fire({
          icon: 'error',
          title: 'Login Gagal',
          text: 'Username atau Password salah!'
        });
      } else if (pesan === 'logout') {
        ToastInfo.fire({
          icon: 'success',
          title: 'Logout Berhasil',
          text: 'Sampai jumpa lagi!'
        });
      } else if (pesan === 'belum_login') {
        ToastInfo.fire({
          icon: 'warning',
          title: 'Akses Ditolak',
          text: 'Silahkan login terlebih dahulu.'
        });
      }

      // Bersihkan URL
      if (window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.delete('pesan');
        window.history.replaceState(null, '', url.toString());
      }
    </script>
  <?php endif; ?>

</body>

</html>