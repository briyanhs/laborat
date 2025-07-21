<?php include 'database/database.php'; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Renbang Recap</title>
    <link rel="stylesheet" href="style.css">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>
  
    <!------------ main cointainer ----------->
    
    <div class="cointainer d-flex justify-content-center align-items-center min-vh-100">
      

    <!------------ login cointainer ----------->

      <div class="row border rounded-5 p-3 bg-white shadow box-area">
        <?php 
          if(isset($_GET['pesan'])){
            if($_GET['pesan'] == "gagal"){
              echo '<div class="alert alert-warning text-center" role="alert">Login gagal! username dan password salah</div>';
              header("refresh: 3; url=index.php");
            }else if($_GET['pesan'] == "logout"){
              echo '<div class="alert alert-warning text-center" role="alert">Anda telah berhasil logout</div>';
              header("refresh: 3; url=index.php");
            }else if($_GET['pesan'] == "belum_login"){
              echo '<div class="alert alert-warning text-center" role="alert">Anda harus login</div>';
              header("refresh: 3; url=index.php");
            }
          }
        ?>

    <!------------ left box ----------->
      <div class="col-md-6 rounded-4 d-flex justify-content-center align-items-center flex-column left-box" style="background: #103cb3;">
        <div class="featured-image mb-3">
          <img src="image/logo.png" class="img-fluid" style="width: 250px;">
        </div>
        <p class="text-white fs-2" style="font-family: 'Courier New', Courier, monospace; font-weight: 600;">Be Verified</p>
        <small class="text-white text-wrap text-center" style="width: 17rem;font-family: 'Courier New', Courier, monospace;">Every small step brings you closer to a big goal.</small>
      </div>

    <!------------ right box ----------->
      <div class="col-md-6 right-box p-5">
        <div class="row align-items-center">
          <div class="header-text mb-4">
            <h2>Hello Again</h2>
            <p>We are happy to have you back.</p>
          </div>
          <form action="cek_login.php" method="post">
              <div class="input-group mb-3">
                <input type="text" name="username" required="required" class="form-control form-control-lg bg-light fs-6" placeholder="Username">
              </div>
              <div class="input-group mb-4">
                <input type="password" name="password" required="required" class="form-control form-control-lg bg-light fs-6" placeholder="Password">
              </div>
              <div class="input-group mb-3">
                <button class="btn btn-lg btn-primary w-100 fs-6" value="LOGIN">Login</button>
              </div>
          </form>
        </div>
      </div>


    <script src="bootstrap/js/bootstrap.min.js"></script>
      </div>
    </div>

  </body>
</html>