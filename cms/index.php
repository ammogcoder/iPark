<?php 
  session_start();
  ob_start("ob_gzhandler");
  require "../includes/functions.php";
  $stratek = new Stratek();
  $stratek->checkIfNotLoggedIn("stratekadmin");
  $stratek->activatessl();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>STRATEK SOLUTIONS | Welcome</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <?php
    include "../includes/scripts.php";
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
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php
    include "../includes/cms/topheader.php";
  ?>

  <!-- Left side column. contains the logo and sidebar -->
  <?php
    include "../includes/cms/sidebar.php";
  ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="row" style="margin-top: 0px; padding-top: 15px; margin-left: 15px; margin-right: 15px;">
      <?php $stratek->loadContentAdmin(); ?>
    </div>
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <?php
      include "../includes/footer.php";
    ?>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <?php
      //include "../".FNAME."/includes/rightSideBar.php";
    ?>
  </aside>
  <!-- /.control-sidebar -->
  <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->

<script type="text/javascript">
  var vname="STRATEK SOLUTIONS";
  var status = "Update Status?";
  function displayMessage(message,status){
    if(status==1){
      $('#displayRes').html('<center><span class=\'alert alert-success\' role=\'alert\'>'+message+'</span></center>').fadeOut(5000);
    }else{
      $('#displayRes').html('<center><span class=\'alert alert-danger\' role=\'alert\'>'+message+'</span></center>').fadeOut(5000);
    }
  }

  function displayMessage2(message,id,status){
    if(status==1){
      $('#'+id).html('<center><span class=\'alert alert-success\' role=\'alert\'>'+message+'</span></center>').fadeOut(5000);
    }else{
      $('#'+id).html('<center><span class=\'alert alert-danger\' role=\'alert\'>'+message+'</span></center>').fadeOut(5000);
    }
  }

  function redirect(location){
    window.location.assign(location);
  }


  function updateStatusPid(pid,table,location){
    alertify.confirm(vname,status,function(e){
      if(e){
        $.post('ajax.php',{'updateStatusPid':'y','pid':pid,'table':table},function(data){
        if(data==1){
          //delete
          displayMessage('Staus Updated',1);
        }else{
          //alert(data);
          displayMessage('Process failed',0);
        }
          redirect(location);
        });
      }else{

      }
    },function(e){
      displayMessage("Process cancelled");
    });
  }

  function deleteReq(pid,table,location){
    alertify.confirm(vname,"Delete?",function(e){
      $.post('ajax.php',{'deleteReq':'y','pid':pid,'table':table},function(data){
        if(data==1){
          //delete
          displayMessage('Details Deleted',1);
        }else{
          //alert(data);
          displayMessage('Process failed',0);
        }
          redirect(location);
      });
    },function(e){
      displayMessage("Process cancelled",0);
    });
  }

  function view(pid,table,location){
    $.post('ajax.php',{'edit':'y','pid':pid,'table':table},function(data){
      if(data==1){
        redirect(location);
      }
    });
  }

  function printDocument(pdf){
    alertify.confirm('STRATEK SOLUTIONS','Print?',function(e){
      if(e){
        // yes
        $.post('ajax.php',{'printDocument':pdf},function(data){
          if(data == 1){
            alertify.alert('STRATEK SOLUTIONS','File added to print queue..');
          }else{
            alertify.alert('STRATEK SOLUTIONS','Process failed..');
            //alertify.alert('STRATEK SOLUTIONS',data);
          }
        });
      }
    },function(e){
      //error
    });
  }

  
  $('#tableList').DataTable({
      responsive: true
  });

      function showMyImage(fileInput, id) {
        var id = id;
        var files = fileInput.files;
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var imageType = /image.*/;
            if (!file.type.match(imageType)) {
                continue;
            }
            var img = document.getElementById(id);
            img.file = file;
            var reader = new FileReader();
            reader.onload = (function (aImg) {
                return function (e) {
                    aImg.src = e.target.result;
                };
            })(img);
            reader.readAsDataURL(file);
        }
    }
</script>
</body>
</html>
