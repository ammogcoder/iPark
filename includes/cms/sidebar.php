  <?php
    $userDetails = $stratek->getFullDetailsId($_SESSION['stratekadmin'],"login");
    $dp = $stratek->getFullDetailsPid($_SESSION['stratekadmin'],"dp");
  ?>
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- Sidebar user panel -->
      <div class="user-panel">
        <div class="pull-left image">
          <img src="<?php echo $dp[2]; ?>" class="img-circle" alt="User Image">
        </div>
        <div class="pull-left info">
          <p><?php echo $userDetails[3]; ?></p>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>
      <!-- search form -->
      <form action="#" method="get" class="sidebar-form">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Search...">
              <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
        </div>
      </form>
      <!-- /.search form -->
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu">
        <li class="header">MAIN NAVIGATION</li>
        <li class="treeview">
          <a href="?dashboard">
            <i class="fa fa-dashboard"></i> <span>Dashboard</span>
            <span class="pull-right-container">
            </span>
          </a>
        </li>
        <!-- <li class="treeview">
          <a href="?statistics">
            <i class="glyphicon glyphicon-stats"></i> <span>Statistics</span>
            <span class="pull-right-container">
            </span>
          </a>
        </li> -->
        <li class="treeview">
          <a href="#">
            <i class="fa fa-book"></i>
            <span>Reports</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="?existingOrdersReport"><i class="fa fa-circle-o text-red"></i> Existing Orders</a></li>
              <li><a href="?receiptReport"><i class="fa fa-circle-o text-green"></i> Processed Bill Receipt</a></li>
              <li><a href="?summaryReport"><i class="fa fa-circle-o text-blue"></i> Summary Report</a></li>
          </ul>
        </li>
        <li class="treeview">
          <a href="#">
            <i class="fa fa-cog"></i>
            <span>Configurations</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li><a href="?billingParams"><i class="fa fa-circle-o text-red"></i> Billing Params</a></li>
            <li><a href="?menu"><i class="fa fa-circle-o text-green"></i> Menu</a></li>
            <li><a href="?tax"><i class="fa fa-circle-o text-blue"></i> Tax/Service Charge</a></li>
            <li><a href="?printer"><i class="fa fa-circle-o text-yellow"></i> Printer Configuration</a></li>
          </ul>
        </li>
        <li class="treeview">
          <a href="?backups">
            <i class="fa fa-archive"></i> <span>System Backups/Cron Tasks</span>
            <span class="pull-right-container">
            </span>
          </a>
        </li>
        <li class="header">SYSTEM USERS</li>
          <li><a href="?systemUsers"><i class="fa fa-users"></i> <span>System Admins</span></a></li>
          <li><a href="?users"><i class="fa fa-users"></i> <span>Waiters/Waitresses</span></a></li>
        </ul>
    </section>
    <!-- /.sidebar -->
  </aside>
