<section class="content-header">
      <h1>
        System
        <small>Backups &amp; Cron Tasks</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-archive"></i> System</a></li>
        <li class="active">Backups | Cron Tasks</li>
      </ol>
</section>

<!-- Main content -->
<section class="content">
    <div class="row" style="margin: 15px;">
      <ul class="nav nav-tabs">
        <li id="database"><a href="?backups&db"><span class="fa fa-file"></span> Database Backup</a></li>
        <li id="system"><a href="?backups&system"><span class="fa fa-folder"></span> Main System Backup</a></li>
        <li id="cron"><a href="?backups&crontasks"><span class="fa fa-folder"></span> Cron Tasks</a></li>
      </ul>
    </div>

    <div class="row" style="margin: 15px;" id="displayRes"></div>

    <?php
      if(isset($_GET['system'])){
        $this->setClassActive("system");
        include "backupsSys.php";
      }elseif(isset($_GET['crontasks'])){
        $this->setClassActive("cron");
        include "tasks.php";
      }else{
        //load default view
        $this->setClassActive("database");
        include "backupsDb.php";
      }
    ?>

</section>
<!-- /.content -->