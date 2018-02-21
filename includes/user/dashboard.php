<section class="content-header">
      <h1>
        Dashboard
        <small>Control panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Dashboard</li>
      </ol>
</section>

<!-- Main content -->
<section class="content">
  
</section>
<!-- /.content -->

<?php 
  if(isset($_POST['uploadImg'])){
    $this->changeProfilePicAdmin($_SESSION['stratekuser']);
  }
?>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="cms/js/pages/dashboard.js"></script>
<script>
$.widget.bridge('uibutton', $.ui.button);
</script>