<?php 
  session_start();
  ob_start("ob_gzhandler");
  require "includes/functions.php";
  $stratek = new Stratek();
  $stratek->checkIfLoggedIn("stratekuser");
  $stratek->activatessl();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>STRATEK SOLUTIONS | Log In</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <?php
    include "includes/user/scripts.php";
  ?>
  <style type="text/css">
    .loader {
        border: 16px solid #f3f3f3;
        border-radius: 50%;
        border-top: 16px solid blue;
        border-right: 16px solid red;
        border-bottom: 16px solid yellow;
        border-left: 16px solid green;
        width: 120px;
        height: 120px;
        -webkit-animation: spin 2s linear infinite;
        animation: spin 2s linear infinite;
      }
      @-webkit-keyframes spin {
        0% { -webkit-transform: rotate(0deg); }
        100% { -webkit-transform: rotate(360deg); }
      }
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
  </style>
</head>
<body style="background: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%,rgba(0,0,0,0.6) 100%), url('cms/images/bg.png'); background-size: 100%;">
  <div class="row" style="margin: 5px;">
      <div class="col-md-4">

      </div>
      <div class="col-md-4">
          <br><br><br>
      </div>
      <div class="col-md-4">

      </div>
  </div>
  <div class="container">
      <div class="row" style="margin: 15px;">
        <div class="col-md-4"></div>
        <div class="col-md-4">
          <form class="form-signin form well br" method="post" action="#" id="signin1" style="background-color: #fff;">
            <div id="displayRes" style="text-align: center;"></div>
            <h3 style='font-family: flower; font-size: 25px; color: #367fa9; font-weight: bold;' class="form-signin-heading"><center>STRATEK SOLUTIONS</center></h3>
            <h3 style='font-family: helvetica; font-size: 20px; color: grey;' class="form-signin-heading"><center>Users Portal</center></h3>
            <center><img src="cms/images/logo.png" style="width: auto; height: 70px; margin: 5px;" /></center>
            <div class="form-group">
              <label for="username" class="sr-only">User Name</label>
              <input type="text" id="username" name="username" class="form-control" placeholder="User Name" required autofocus>
            </div>
            <div class="form-group">
              <label for="password" class="sr-only">Password</label>
              <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button class="btn btn-lg btn-primary btn-block br" name="loginBtn" type="submit"><span class="glyphicon glyphicon-log-in"></span> Sign in</button>
          </form>
        </div>
        <div class="col-md-4"></div>
      </div>
    </div> <!-- /container -->
    <div class="row">
      <div class="col-md-2">

      </div>
      <div class="col-md-8" style="color: grey">
          <center><img src="cms/images/separator.png" /><br/>
          <p style="color: #fff; font-family: flower; font-weight: bold; font-size: 18px;">For support and further inquiries please call <a href="tel:0205737153" style="text-decoration: none; color: #fff;">0205737153</a></p>
          <p style="font-weight: bolder; font-size: 18px;"><a href="mailto:admin@strateksolutions.org" target="_blank" style="color: #fff; text-decoration: none; font-family: flower;">www.strateksolutions.org</a></p>
          </center>

      </div>
      <div class="col-md-2">

      </div>
  </div>
<?php
  if(isset($_POST['loginBtn']) || (isset($_POST['username']) && isset($_POST['password']))){
    $stratek->verifyAdmin($_POST['username'],$_POST['password']);
  }
?>
<script type="text/javascript">
  $('#signin1').bootstrapValidator({
      message:  'This value is not valid',
      feedbackIcons:{
        valid: 'glyphicon glyphicon-ok',
        invalid: 'glyphicon glypicon-remove',
        validating: 'glyphicon glyphicon-refresh'
      },
      fields:{
        username:{
          validators:{
            notEmpty:{
              message: 'User name can\'t be empty'
            },
            stringLength:{
              min: 2,
              max: 100,
              message: 'Invalid input'
            },
            regexp:{
              regexp: /^[a-zA-Z\_\.]+$/,
              message: 'Invalid input'
            }
          }
        },
        password:{
          validators:{
            notEmpty:{
              message: 'Password can\'t be empty'
            },
            stringLength:{
              min: 2,
              max: 100,
              message: 'Invalid input'
            },
            regexp:{
              regexp: /^[a-zA-Z\-\_\.]+$/,
              message: 'Invalid input'
            }
          }
        }
      }
    });
</script>
</body>
</html>