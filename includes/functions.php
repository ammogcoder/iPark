<?php

require "dbconfig.php";
//adding printer autoload file
require "printer/autoload.php";

use Mike42\Escpos\Printer;
use Mike42\Escpos\ImagickEscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

// handle large folders/files
ini_set('max_execution_time', 600);
ini_set('memory_limit','1024M');
ini_set('post_max_size','500M');
ini_set('upload_max_filesize','500M');
ini_set('max_file_uploads', 20);

class Stratek{
	public $con;
    private $connector;
    private $printer;

    function __construct()
    {
        $this->con = new PDO("mysql:host=" . HOST . ";dbname=" . DB_NAME . "", DB_USERNAME, DB_PASSWORD);
        $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }


    function sanitize($data)
    {
        return htmlentities(trim($data));
    }

    function sanitize2($data)
    {
        return trim($data);
    }

    function activatessl()
    {
        echo "<script>
					if (window.location.protocol != \"https:\")
			    		window.location.href = \"https:\" + window.location.href.substring(window.location.protocol.length);
				</script>";
    }

    function redirect($location)
    {
        echo "<script>
					window.location.assign('" . $location . "');
				</script>";
    }

    function setHeaderTitle($title)
    {
        $title = $this->sanitize($title);
        echo "<script>
					document.getElementById('documentHeader').innerHTML = '" . $title . "';
				</script>";
    }

    function setTitle($title, $id)
    {
        $title = $this->sanitize($title);
        $id = $this->sanitize($id);
        echo "<script>
					document.getElementById('" . $id . "').innerHTML = '" . $title . "';
				</script>";
    }

    function setHeaderUrl($url)
    {
        $url = $this->sanitize($url);
        echo "<script>
				$('#documentHeader').attr('href','" . $url . "');
			</script>";
    }

    function displayMsg($message, $status)
    {
        $message = $this->sanitize($message);
        $status = $this->sanitize($status);

        if ($status == 1) {
            echo "<script>
					$('#displayRes').html('<center><span class=\'alert alert-success\' role=\'alert\'>" . $message . "</span></center>').fadeOut(5000);
					</script>";
        } else {
            echo "<script>
					$('#displayRes').html('<center><span class=\'alert alert-danger\' role=\'alert\'>" . $message . "</span></center>').fadeOut(5000);
					</script>";
        }
    }


    function displayMsg2($message, $status)
    {
        $message = $this->sanitize($message);
        $status = $this->sanitize($status);

        echo "<script>
					alertify.alert('Gold Coast Restaurant','" . $message . "');
				</script>";

    }

    function genPid()
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }

    function formatNumber($number)
    {
        return number_format(floatval($number), 2, '.', '');
    }

    function checkIfLoggedIn($user)
    {
        if (isset($_SESSION[$user]) && isset($_SESSION['lock']) && $_SESSION['lock'] == 0) {
            $this->redirect("index.php");
        }
    }

    function checkIfNotLoggedIn($user)
    {
        if (!isset($_SESSION[$user]) || !isset($_SESSION['lock']) || $_SESSION['lock'] == 1) {
            $this->redirect("login.php");
        }
    }

    function updateLastPassChng($pid)
    {
        $pid = $this->sanitize($pid);
        $curDate = $this->genDate();
        $query = "insert into lastpasschng(pid,date) values(?,?)";
        $result = $this->con->prepare($query);
        $result->bindParam("s", $pid);
        $result->bindParam("s", $curDate);
        $result->execute(array($pid, $curDate));
    }

    function lockSession()
    {
        $_SESSION['lock'] = 1;
    }

    function getDiskUsage()
    {
        $bytes = disk_free_space(".");
        $si_prefix = array('B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB');
        $base = 1024;
        $class = min((int)log($bytes, $base), count($si_prefix) - 1);
        echo sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class] . '/';
        $bytes = disk_total_space(".");
        $class = min((int)log($bytes, $base), count($si_prefix) - 1);
        echo sprintf('%1.2f', $bytes / pow($base, $class)) . ' ' . $si_prefix[$class] . '';
    }

    function getCurrentLogin($pid)
    {
        $data = null;
        $pid = $this->sanitize($pid);
        $query = "select date,ip from lastlogin where pid=? order by date desc limit 1";
        $result = $this->con->prepare($query);
        $result->bindParam("s", $pid);
        $result->execute(array($pid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data[0] = $row['date'];
            $data[1] = $row['ip'];
        }
        return $data;
    }

    function getLastLogin($pid)
    {
        $data = null;
        $pid = $this->sanitize($pid);
        $query = "select date,ip from lastlogin where pid=? order by date desc limit 2";
        $result = $this->con->prepare($query);
        $result->bindParam("s", $pid);
        $result->execute(array($pid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data[0] = $row['date'];
            $data[1] = $row['ip'];
        }
        return $data;
    }

    function updateLastLogin($pid)
    {
        $pid = $this->sanitize($pid);
        $curDate = $this->genDate();
        $ip = $this->sanitize($_SERVER['REMOTE_ADDR']);
        $query = "insert into lastlogin(pid,date,ip) values(?,?,?)";
        $result = $this->con->prepare($query);
        $result->bindParam("s", $pid);
        $result->bindParam("s", $curDate);
        $result->bindParam("s", $ip);
        $result->execute(array($pid, $curDate, $ip));
    }

    function genDate()
    {
        return date('Y-m-d h:i:s');
    }

    function genDay(){
        return date("Y-m-d");
    }

    function getFullDetails($username, $table)
    {
        $username = $this->sanitize($username);
        $table = $this->sanitize($table);
        $query = "select * from " . $table . " where username=?";
        $result = $this->con->prepare($query);
        $result->bindParam("s", $username);
        $result->execute(array($username));
        return $result->fetch();
    }

    function verifyAdmin($username, $password)
    {
        $username = $this->sanitize($username);
        $password = sha1($this->sanitize($password));
        $sql = "select * from login where username=? and password=? and uid=0 and status=1";
        $result = $this->con->prepare($sql);
        $result->bindParam("s", $username);
        $result->bindParam("s", $password);
        $result->execute(array($username, $password));
        if ($result->rowCount() >= 1) {
            //authentication successful
            $details = $this->getFullDetails($username, "login");
            $_SESSION['stratekadmin'] = $details[0];
            $_SESSION['lock'] = 0;
            $this->updateLastLogin($details[0]);
            $this->updateActiveLogin($_SESSION['stratekadmin'], 1);
            $this->displayMsg("LogIn successful...", 1);
            $this->redirect("index.php");
        } else {
            $this->displayMsg("LogIn failed...", 0);
            $this->redirect("login.php");
        }
    }

    function getFullDetailsPid($pid, $table)
    {
        $pid = $this->sanitize($pid);
        $table = $this->sanitize($table);
        $query = "select * from " . $table . " where pid=?";
        $result = $this->con->prepare($query);
        $result->bindParam("s", $pid);
        $result->execute(array($pid));
        return $result->fetch();
    }

    function getFullDetailsId($id, $table)
    {
        $id = $this->sanitize($id);
        $table = $this->sanitize($table);
        $sql = "select * from " . $table . " where id=?";
        $result = $this->con->prepare($sql);
        $result->bindParam("s", $id);
        $result->execute(array($id));
        return $result->fetch();
    }

    function getNumberOfLogins($pid)
    {
        $sql = "select distinct Date(date) from lastlogin where pid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($pid));
        return $result->rowCount();
    }

    function getCpu()
    {
        $percentage = 0;
        $os = $this->detectOs();
        if ($os == "windows") {
            return "N/A";
        } elseif ($os == "linux") {
            $percentage = sys_getloadavg();
            $percentage = $percentage[0] * 100;
            return $percentage . "<small>%</small>";
        } else {
            return "N/A";
        }
    }

    function detectOs()
    {
        $os = php_uname();
        $os = explode(" ", $os);
        return strtolower($os[0]);
    }

    function updateActiveLogin($pid, $status)
    {
        $pid = $this->sanitize($pid);
        $status = $this->sanitize($status);
        //checking if pid already exists
        $sql = "select * from active_login where pid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($pid));
        if ($result->rowCount() >= 1) {
            //pid already exists; so update it
            $query = "update active_login set status=? where pid=?";
            $res = $this->con->prepare($query);
            $res->execute(array($status, $pid));
        } else {
            //insert this new pid
            $query = "insert into active_login(pid,status) values(?,?)";
            $res = $this->con->prepare($query);
            $res->execute(array($pid, $status));
        }
    }

    function getActiveLogin()
    {
        $sql = "select * from active_login where status=1";
        $result = $this->con->query($sql);
        return $result->rowCount();
    }

    function getNumberUsers($uid)
    {
        $uid = $this->sanitize($uid);
        $sql = "select * from login where uid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($uid));
        return $result->rowCount();
    }

    function loadUserLogins()
    {
        $sql = "select * from lastlogin where pid=? order by date desc";
        $result = $this->con->prepare($sql);
        $result->execute(array($_SESSION['stratekadmin']));

        $data = "<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>";
        $data .= "<thead><tr><th><center>No.</center></th><th><center>Date</center></th><th><center>Remote IP/Location</center></th></tr></thead><tbody>";
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<tr><td><center>" . $count . "</center></td><td><center>" . $row['date'] . "</center></td><td><center>" . $row['ip'] . "</center></td></tr>";
            $count++;
        }
        $data .= "</tbody></table>";
        echo $data;
    }

    function loadActiveUsers()
    {
        $data = "<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>";
        $data .= "<thead><tr><th><center>No.</center></th><th><center>Full Name</center></th><th><center>Date</center></th><th><center>Remote IP/Location</center></th><th><center>Role</center></th></tr></thead><tbody>";

        $sql = "select * from active_login where status=1";
        $result = $this->con->query($sql);
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $userDetails = $this->getFullDetailsId($row['pid'], 'login');
            $loginDetails = $this->getCurrentLogin($row['pid']);
            if ($userDetails[6] == 0) {
                $role = "Administrator";
            } else {
                $role = "Waiter/Waitress";
            }
            //displaying data
            $data .= "<tr><td><center>" . $count . "</center></td><td>" . $userDetails[3] . "</td><td><center>" . $loginDetails[0] . "</center></td><td><center>" . $loginDetails[1] . "</center></td><td><center>" . $role . "</center></td></tr>";

            $count++;
        }

        $data .= "</tbody></table>";
        echo $data;
    }

    function loadContentAdmin()
    {
        $url = explode("?", $_SERVER['REQUEST_URI']);
        if (sizeof($url) >= 2) {
            $this->checkPermission("?" . $url[1], $_SESSION['stratekadmin']);
        }

        if (isset($_GET['?dashboard'])) {
            include "cms/dashboard.php";
        } elseif (isset($_GET['logout'])) {
            $this->updateActiveLogin($_SESSION['stratekadmin'], 0);
            unset($_SESSION['stratekadmin']);
            $this->redirect("login.php");
        } elseif (isset($_GET['profile'])) {
            include "cms/profile.php";
        } elseif (isset($_GET['passwd'])) {
            include "cms/password.php";
        } elseif (isset($_GET['users'])) {
            if (isset($_GET['edit'])) {
                include "cms/usersedit.php";
            } else {
                include "cms/users.php";
            }
        } elseif (isset($_GET['systemUsers'])) {
            if (isset($_GET['edit'])) {
                include "cms/systemUsersedit.php";
            } elseif (isset($_GET['permissions'])) {
                include "cms/systemUsersPermissions.php";
            } else {
                include "cms/systemUsers.php";
            }
        } elseif (isset($_GET['logins'])) {
            include "cms/userLogins.php";
        } elseif (isset($_GET['activeUsers'])) {
            include "cms/activeLogins.php";
        } elseif (isset($_GET['billingParams'])) {
            include "cms/billingParams.php";
        } elseif (isset($_GET['menu'])) {
            if (isset($_GET['view'])) {
                include "cms/menuView.php";
            } else {
                include "cms/menu.php";
            }
        } elseif (isset($_GET['tax'])) {
            include "cms/tax.php";
        } elseif (isset($_GET['printer'])) {
            include "cms/printerConfiguration.php";
        } elseif (isset($_GET['existingOrdersReport'])) {
            include "cms/existingOrdersReport.php";
        } elseif (isset($_GET['receiptReport'])) {
            include "cms/receiptReport.php";
        } elseif(isset($_GET['summaryReport'])){
            include "cms/summaryReport.php";
        } elseif(isset($_GET['backups'])){
            include "cms/backups.php";
        } elseif(isset($_GET['statistics'])){
            include "cms/statistics.php";
        } else {
            include "cms/dashboard.php";
        }
    }

    function verifyData($pid, $table)
    {
        if ($this->verifyDataApi($pid, $table)) {
            $_SESSION['useredit'] = $this->sanitize($pid);
            if ($table == "food" || $table == "drinks") {
                //set session for category
                $_SESSION["category"] = $table;
            }
            echo 1;
        } else {
            echo 0;
        }
    }

    function verifyDataApi($pid, $table)
    {
        $pid = $this->sanitize($pid);
        $table = $this->sanitize($table);
        if ($table == "login" || $table == "food" || $table == "drinks" || $table == "billing_params_categories") {
            $sql = "select * from " . $table . " where id=?";
        } else {
            $sql = "select * from " . $table . " where pid=?";
        }
        $result = $this->con->prepare($sql);
        $result->bindParam("s", $pid);
        $result->execute(array($pid));
        if ($result->rowCount() >= 1) {
            return true;
        } else {
            return false;
        }
    }

    function unlockSession($password)
    {
        $password = $this->sanitize($password);
        $password = sha1($password);
        $id = $_SESSION['stratekadmin'];
        $sql = "select * from login where id=? and password=?";
        $result = $this->con->prepare($sql);
        $result->bindParam("s", $id);
        $result->bindParam("s", $password);
        $result->execute(array($id, $password));
        if ($result->rowCount() >= 1) {
            $_SESSION['lock'] = 0;
            echo 1;
        } else {
            echo 0;
        }
    }

    function updateAdminProfile2($id, $username, $fullname, $mobileNo)
    {
        if ($this->updateAdminProfile($id, $username, $fullname, $mobileNo)) {
            $this->displayMsg("Profile updated..", 1);
            $this->redirect("?profile");
        }
    }

    function getPrinterConfiguration()
    {
        $sql = "select * from printer_configuration limit 1";
        $result = $this->con->query($sql);
        if ($result->rowCount() < 1) {
            //add data
            $this->con->query("insert into printer_configuration(ip,port) values('0.0.0.0',9100)");
            $query = "select * from printer_configuration limit 1";
            $res = $this->con->query($query);
            return $res->fetch();
        } else {
            return $result->fetch();
        }
    }

    function updatePrinterConfiguration()
    {
        $ip = $this->sanitize($_POST['ip']);
        $port = $this->sanitize($_POST['port']);
        $id = $this->sanitize($_POST['id']);
        $sql = "update printer_configuration set ip=?,port=? where id=?";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($ip, $port, $id))) {
            $this->displayMsg("Configuration updated..", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?printer");
    }

    function updateAdminProfile($id, $username, $fullname, $mobileNo)
    {
        $id = $this->sanitize($id);
        $username = $this->sanitize($username);
        $fullname = $this->sanitize($fullname);
        $mobileNo = $this->sanitize($mobileNo);
        $query = "update login set username=?,fullname=?,mobileNo=? where id=?";
        $result = $this->con->prepare($query);
        if ($result->execute(array($username, $fullname, $mobileNo, $id))) {
            return true;
        } else {
            return false;
        }
    }

    function updateAdminPassword($id, $oldpassword, $newpassword)
    {
        if ($this->updatePassword($id, $oldpassword, $newpassword, "login")) {
            $this->updateLastPassChng($id);
            $this->displayMsg("Password updated...", 1);
            $this->redirect("?logout");
        } else {
            $this->displayMsg("Process failed..", 0);
            $this->redirect("?passwd");
        }
    }

    function changeProfilePicAdmin()
    {
        $pid = $this->sanitize($_SESSION['stratekadmin']);
        $base64 = $this->sanitize($_POST['picture']);
        //checking if user already has a dp
        $sql = "select * from dp where pid=?";
        $res = $this->con->prepare($sql);
        $res->execute(array($pid));
        if ($res->rowCount() >= 1) {
            //dp already exists; update
            $query = "update dp set image=? where pid=?";
            $result = $this->con->prepare($query);
            $result->bindParam("s", $base64);
            $result->bindParam("s", $pid);
            if ($result->execute(array($base64, $pid))) {
                $this->displayMsg("Profile Picture updated", 1);
            } else {
                $this->displayMsg("Process failed..", 0);

            }
        } else {
            $query = "insert into dp(image,pid) values(?,?)";
            $result = $this->con->prepare($query);
            if ($result->execute(array($base64, $pid))) {
                $this->displayMsg("Profile Picture updated", 1);
            } else {
                $this->displayMsg("Process failed..", 0);

            }
        }
        $this->redirect("?dashboard");
    }

    function updatePassword($id, $oldpassword, $newpassword, $table)
    {
        $id = $this->sanitize($id);
        $oldpassword = sha1($this->sanitize($oldpassword));
        $newpassword = sha1($this->sanitize($newpassword));
        $table = $this->sanitize($table);

        $query = "select * from " . $table . " where id=? and password=?";

        $result = $this->con->prepare($query);
        $result->execute(array($id, $oldpassword));
        if ($result->rowCount() >= 1) {
            $query1 = "update " . $table . " set password=? where id=?";
            $result1 = $this->con->prepare($query1);
            $result1->bindParam("s", $newpassword);
            $result1->bindParam("s", $id);
            if ($result1->execute(array($newpassword, $id))) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    function loadUsers($uid, $location)
    {
        $uid = $this->sanitize($uid);
        $sql = "select * from login where uid=? and status != 2";
        $result = $this->con->prepare($sql);
        $result->execute(array($uid));
        $data = "<table class='table table-bordered table-condensed table-hover' id='tableList'>";
        $data .= "<thead>
					<tr><th><center>No.</center></th><th><center>Full Name</center></th><th><center>Mobile Number</center></th><th></th></tr>
				   </thead>";
        $data .= "<tbody>";
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($uid == 0 && $row['main'] == 1) {
                continue; // ignore root
            }
            $data .= "<tr><td><center>" . $count . "</center></td><td>" . $row['fullname'] . "</td><td>" . $row['mobileNo'] . "</td>";
            $data .= "<td>
					<div class='row' style='margin: 0px; padding: 0px;'>
							<center>";
            if ($uid == 0) {
                $data .= "<button type='button' onclick=\"view('" . $row['id'] . "','login','" . $location . "&permissions')\" class='btn btn-xs btn-warning br'><span class='glyphicon glyphicon-plus'></span></button>&nbsp;";
            }
            $data .= "<a href='#" . $row['id'] . "toggle' data-toggle='modal' ";
            if ($row['status'] == 1) {
                $data .= "class='btn btn-xs btn-success br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-open'></span>";
            } else {
                $data .= "class='btn btn-xs btn-warning br tooltip-bottom' title='Click to activate'><span class='glyphicon glyphicon-eye-close'></span>";
            }
            $data .= "</a>
								<button type='button' class='btn btn-xs btn-info br tooltip-bottom' title='View/Edit Details' onclick=\"view('" . $row['id'] . "','login','" . $location . "&edit')\"><span class='glyphicon glyphicon-pencil'></span></button>
								<a href='#" . $row['id'] . "delete' data-toggle='modal' class='btn btn-xs btn-danger br tooltip-bottom' title='Delete User Account'><span class='glyphicon glyphicon-remove-sign'></span></a>
							</center>
					</div>
				</td></tr>";
            $count++;
        }
        $data .= "</tbody>";
        $data .= "</table>";

        //generating toggle modals
        $sql = "select * from login where uid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($uid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<div id='" . $row['id'] . "toggle' class='modal fade'>";
            $data .= "<div class='modal-dialog modal-sm'>";
            $data .= "<div class='modal-content'>";
            $data .= "<div class='modal-header' style='background-color: #035888; color: #fff;'>";
            $data .= "<center><h3 class='panel-title'><span class='glyphicon glyphicon-flash'></span> Account Status</h3></center>";
            $data .= "</div>";
            $data .= "<div class='modal-body'>";
            $data .= "<div class='row' style='margin: 15px;'>";
            $data .= "<div class='col-md-6'>";
            $data .= "<form method='post' action='" . $location . "'>
					<center><button type='submit' class='btn btn-xs btn-success br' name='activateBtn' value='" . $row['id'] . "'><span class='glyphicon glyphicon-ok'></span> Activate</button></center>
					</form>";
            $data .= "</div>";
            $data .= "<div class='col-md-6'>";
            $data .= "<form method='post' action='" . $location . "'>
					<center><button type='submit' class='btn btn-xs btn-danger br' name='deactivateBtn' value='" . $row['id'] . "'><span class='glyphicon glyphicon-remove'></span> Deactivate</button></center>
					</form>";
            $data .= "</div>";
            $data .= "</div>";
            $data .= "</div>";
            $data .= "</div>";
            $data .= "</div>";
            $data .= "</div>";
        }

        //generating delete modals
        $sql = "select * from login where uid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($uid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<div id='" . $row['id'] . "delete' class='modal fade'>";
            $data .= "<div class='modal-dialog modal-sm'>";
            $data .= "<div class='modal-content'>";
            $data .= "<div class='modal-header' style='background-color: #035888; color: #fff;'>";
            $data .= "<center><h3 class='panel-title'><span class='glyphicon glyphicon-flash'></span> Delete Account</h3></center>";
            $data .= "</div>";
            $data .= "<div class='modal-body'>";
            $data .= "<div class='row' style='margin: 15px;'>";
            $data .= "<div class='col-md-6'>";
            $data .= "<form method='post' action='" . $location . "'>
					<center><button type='submit' class='btn btn-xs btn-success br' name='deleteBtn' value='" . $row['id'] . "'><span class='glyphicon glyphicon-ok'></span> Delete Account</button></center>
					</form>";
            $data .= "</div>";
            $data .= "<div class='col-md-6'>";
            $data .= "<center><a href='#' style='text-decoration: none;' class='btn btn-xs btn-danger br' data-dismiss='modal'><span class='glyphicon glyphicon-remove'></span> Close</a></center>";
            $data .= "</div>";
            $data .= "</div>";
            $data .= "</div>";
            $data .= "</div>";
            $data .= "</div>";
            $data .= "</div>";
        }
        //$data.="<script>$('#tableList').DataTable({responsive: true;});</script>";
        echo $data;
    }

    function genUsersOption($uid)
    {
        $uid = $this->sanitize($uid);
        $sql = "select * from login where uid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($uid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['main'] == 1) {
                continue;
            }
            echo "<option value='" . $row['id'] . "'>" . $row['fullname'] . "</option>";
        }
    }


    function addAdminUser($username, $password, $fullname, $mobileNo, $uid, $location)
    {
        if ($this->addUser($username, $password, $fullname, $mobileNo, $uid)) {
            $this->displayMsg("User Added", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect($location);
    }

    function addUser($username, $password, $fullname, $mobileNo, $uid)
    {
        $username = $this->sanitize($username);
        $password = sha1($this->sanitize($password));
        $fullname = $this->sanitize($fullname);
        $mobileNo = $this->sanitize($mobileNo);
        $uid = $this->sanitize($uid);
        $status = 1;
        $logo = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAADICAYAAACtWK6eAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABNmlDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjarY6xSsNQFEDPi6LiUCsEcXB4kygotupgxqQtRRCs1SHJ1qShSmkSXl7VfoSjWwcXd7/AyVFwUPwC/0Bx6uAQIYODCJ7p3MPlcsGo2HWnYZRhEGvVbjrS9Xw5+8QMUwDQCbPUbrUOAOIkjvjB5ysC4HnTrjsN/sZ8mCoNTIDtbpSFICpA/0KnGsQYMIN+qkHcAaY6addAPAClXu4vQCnI/Q0oKdfzQXwAZs/1fDDmADPIfQUwdXSpAWpJOlJnvVMtq5ZlSbubBJE8HmU6GmRyPw4TlSaqo6MukP8HwGK+2G46cq1qWXvr/DOu58vc3o8QgFh6LFpBOFTn3yqMnd/n4sZ4GQ5vYXpStN0ruNmAheuirVahvAX34y/Axk/96FpPYgAAACBjSFJNAAB6JQAAgIMAAPn/AACA6AAAUggAARVYAAA6lwAAF2/XWh+QAAAOmUlEQVR42uyd6XPaVheHzxVgmx2BDATwksRup520007//6/92Ok0dep4d22DjVkkIYT2+35onNexAQPGC7q/ZyYzmXbGsa706JxzlyPGOSfwvHDOyfM8cl2XbNumfr9Pqqpyy7LIcRxyHIds2ybf90mSJJIkiWKxGKVSKcrlcpTP5ymTybBoNEqMMQzoE8IgyPOKoWkatdttrmkaDQYD6vf75LruVD8nFotRqVSizc1Nls1mMbAQZHHxfZ9s26br62u6vLzkuq6T53kUBMGjf3YikaAPHz6wfD5PkUgEgw1BFocgCEjXdTo/P+dXV1dkWdaT/DuxWIw2NjZoY2ODraysYOAhyOsXw7IsOjw85K1Wi0zTfPJ/U5IkymazVK1WWbVaJUmSiDGG+gSCvK76wjRNOj4+5hcXF+R53ov8HrFYjPL5PBWLRZZKpSiZTNLS0hJkgSAvK0e9XqfT01Pe7XZfze+1srJCqVSK0uk0ra6uslwuR7FYDDcMgjyfGL1ej05OTvjFxcVcCu+nIhKJUDQaJUVRSFEUJssyxeNxkiQJNxKCPI0c7XabdnZ2uGEYC/f7p9NpqlartL6+zhBVIMhc8X2fLi4u6ODggA8Gg4W9DkmSSJZlevv2LVMU5WtxDyDIzDiOQ6enp7S/v8/DMnaMMarVavTu3TuWSqVwkyHIbARBQH///TfV63Xu+364HgTGSJZl+vXXX7GeAkFmixyfP3+mf//9N9QDls1m6ccff2T5fB43nYgwhTFhQb6/v08XFxehf5tomkY7Oztc0zTceAjyMJ7n0cHBAZ2enoYurRqFruv08eNHrus6BIEC4yNHvV6no6MjLloqqmmakNcNQabANE3a29vjL7Vt5KW5vLykZrNJIksCQUZgWRbt7Oxw27aFHQPf92lvb4/3+30IAr5NrU5PT3m73RZ+LHq9Xuhn7iDIDPn34eHhq95b9Zwvi7OzMxI1kkKQO7iui+L0Dp7n0dXVFQQBRNfX13R9fY2BuD8uQk5WQJBbBEFAx8fHws5ajWMwGJDjOBBE9NoDK8jDsW0bgoiM7/t0cnKC2mMElmU9WeMJCLIAGIZBqqpiIMYwbf8uCBIiVFV9lg4kiwyKdEHhnFOz2URuNUEaCkEEfTNi1XwiQTgEEZButyvk2xERBIJMBKZ2IQgEGXPTVVVF/QFBIMgwbNsmkbe0QxAIMhbXdYWc34cgEGQiPM8j7L2CIBAEgkAQCDI9juPgYBQEgSDD4JyjQJ8CEV8kwkcQy7IwxYsIAkHGCIInH4JAEAgynxRLtPMyEASCIIpAkNFFOjYpolCHILjhiCAQBOCFAkFww184JRVt3QgRBEzFIn+0FILM8EYE042XaZpCjRsEAVPR7XaF6jwJQcBUqKoqVIML1CBgKlzXpX/++UeYDpRCC4IZrNmwLEuY2SykWGCmcRPls2wQBGDsIAiY+4MjSRAENxmMIhqNQhAIAobBGKOVlRUIAkHAMJaXlykWi0EQEd6EYHqSyaQwYyf8KxRRZHry+bw4z4foEUSUYnNexGIxyufzDIIIlE+D6dKreDyOCCIKIt3seZDL5SCISIgyXTmvlFRRFCbS5IbwgqRSKUxlTUg0GqVCoSDUNQtfpGcyGVpaWsLTPwGFQkG4SQ3hI0gymaRkMomn/6EHRZKoXC4LF20xi7W8TJVKBWnWA8iyLFx6BUG+pFnVapVSqRQsGEO5XGYiToljGZn+W/z67rvvmCj7i6YllUpRrVYTcmsOBPmCoihUKpUwEHeIRqP0/v17JuqOAwhyK4psb28zzGjdS62EfnFAkFvE43F6+/Ytwy7f/1heXqatrS2hU08IcqdgVxQF20++jMXm5qbwU+AQ5A6ZTEao7dyjKBaLtLa2JnwohSB3B0SSqFarMdHH4N27dww7nSHIUGRZpvX1dWEPU5XLZZJlGQ8CBBn9Bt3a2mKKogh57eVyGRMVEGQ88XicfvjhB+GmfVOpFGUyGTwAEORhkskkbW1tMZFSrUwmg1k8CDIZjDEqFotCTXWm02k0soAg00WRYrHIRHkhiNSQAYLMCVG2eS8tLWFXMwSZHlmWhThJVyqV0AYJgkxPJBIJ/bpANBqlUqmE9AqCzJxmhfrhSafTlE6ncaMhyGzFayaTCXXD5tXVVYYWSBBkZuLxeGi7MDLGqFwuo5k3BHmcIIlEIpTXJssyZq8gyOML9Ww2G8pre/PmDfZeQZDHoyhK6J6ieDyOnbsQZD7kcrnQbeRLJBLoTwxB5lfMVqvVUEWRZDKJ1qsQZH6CrK6uhqqglWUZ9QcEme8bN5fLhUb4sM7MQZCXGjBJClWahXPnEOQp0pJQHCpijKH+gCBPE0XCMpuF3bsQ5EkIS+7u+z5uJgSZPysrK6GoQzzPw82EIPPP3cOwuMY5J9u2cUMhCBgliK7rGAgIgtx9FKqqcs45bigEme+b1zTNUFyLpmk0GAxwUyHI/AiCgK6vr0Px2u31etRqtXBTIch8Ikev16OdnR2uaVpoZD86OuIo1ofDkH9OhmVZdH5+To1GgxuGQUEQhOr61tfX6fvvv8cn6CDIdDiOQ61Wi3Z3d7llWRTW8WKMUaVSoe3tbRaPx9F+FII8nE7puk6Hh4e82WwKs+KcTqepVqvR5uYmgyQQZKQcl5eXtLe3xw3DEO76I5EI1Wo1ev/+PRO90zsEuYPv+9RsNumvv/7iom/DyOfz9OHDByZyQznE0DuRo16v06dPnzj2KBF1Oh3a3d3lIq+TIIJ8wfM8Oj8/p52dHQzIHYrFIv3yyy9Cfi8dEeRL5Dg5OaG9vT3IMYRms0knJydCbo0X/rSM67p0enpK+/v7PGxrG/Pk8PCQR6NRtrm5KVSLUqEFMU2T9vb2+OXlJUGO8fi+T/v7+zyRSLBisSiMJFFRb3a73aaDgwPe7Xbx9E8RbT99+sQlSWKKogghiVBFOuecOp0O1et1fn5+jqgxIysrK/Tbb7+xbDYbekmEEaTf79PZ2RnV63WO7d2PR5Zl+umnn0K/RhJaQTjn5DgOqapKjUaDNxoNRIw5k0wm6eeff2a5XC60e7dCJ0gQBGQYBl1fX1On0+HtdhudO56QRCJBGxsbbG1tLZRf4Fp4QYIgIM/zaDAYULvdpkajwQeDAbmui4jxTEiSROl0msrlMiuXy7S8vBwaWRZWENu2qdfrUafT4d1ulzqdDoR4BSwvL1OhUKBSqcRyudzC9w9bCEE451//aJpGzWaTt1otsiwLbWteKdFolBKJBK2urlKlUmHJZJIkSVq4Wa9XK4jrumRZFpmmSYZhUKfT4ZqmQYgFRVEUKpVKrFAoUCKRoEgkAkGmxXEc6nQ6pOs6aZrGbwTBztqQFLyMUSqVomw2S9Vqlcmy/OpFeRFBgiAgx3HIdV3q9/ukqiq1Wi1umib5vo9aQpDCPpFIUK1WY4VCgZLJJEWj0VeXgj2bIEEQfJXBMAzSdZ33+330ZAK0vLxMiqJQoVBgq6urr6qt65MIEgQB+b5Pvu+Trutf1yNs2ybHcbAuAYYSiUQokUiQoihUq9VYPB5/8agyN0GCICDTNKnX61G/36dOp8N1XUdRDWaWJZ/P05s3b1gul6NUKvUiojxaEM/zqNPp0MXFBTcMA0U1mHutkkwmSVEUqlQq7Lm/DzmzIKZpUr1e50dHR+S6Lu4keBZkWaZKpfJs08UzC/Lnn3/yer1OONMOXoJ0Ok25XI4qlcqTThfPJIiqqvT777+jbT54cWKxGMmyTNvb2yydTs9dlKlPFHqeR8fHx5ADvApc16Vms0mapvFKpUIbGxsskUjMraCfWhDDMAjHVMFrw7ZtOjk5oXa7zdfW1litVqNIJPJoUaZOsQ4PD2l3dxfhA7xqFEWh7e1tls/nH/Vzpj4GdnV1BTnAq6fVatHHjx95q9V61ETSVIL0+32kV2BhMAyD/vjjD358fDzz7o2pBLm8vET0AAtXxH/+/HnmJYmJBblZMQdg0QiCgPb393m73X46QUzTpH6/j9EGC8lgMPj6lbAnEWQwGNC0PxyA14SmaXR2djbVGt7EgnQ6HY5t6mDRaTQaU2VCEwsyS/4GwGvj5tDeXAWxbZt0XcfoglAU7Ofn53yugqiqil27IDTouj5x34OJBNE0DXaA0OD7PjmOMx9Bbs6VAxAmJp2RfVAQx3FwrhyECs75/AQZDAZozQNCJ4iqqhOthzwoyE2rHgDCRL/fn2gD44OC6LqOGSwQOkzTnKh0eFAQVVVhBwhlBOn1eo8T5OZzAwCEDd/3qdFoPFiHjBWk1+uh5xUILVdXVw9OQI0VBNEDCBBFZhPkyzfFUX+AUNNsNseeERkpiOM4ZJomRhCEGl3XxxbrIwWxLAsHpEDo8TxvbKeekYKYpglBgDDF+qjJKGlc6MGn0IAIOI4z8hDVSEFarRYKdCAEQRBQp9MZuiYijcrLsMUdiES/3x+aMQ0VRFVVpFdAKEbtzRoqSLvdRnoFhGIwGEwmCNIrIGqhPmzbiTTMJCwQAhHpdrv8QUEcx8EBKSAkw1bUpWHVPAQBImIYxr3DgdIQi1CgAyFxXfdeoX5PEGxxByJzt/7+RpAgCCY6hghAGOGc32ts/Y0gg8GAPM/DSAFhBTFNk48UZJqu1wCENcW6vYtEQoEOwP9xHIdc1+X3BPF9H/UHEB7btm/OhnAi4tLt/4EevAARxPmmDocgANwR5PZC+TeCYAUdiA7n/JtNi18FGXVgBADRGCqIruuYwQKAvl1N/yqIYRgYGQCGRZAgCCAIALciyM2uXunmP6D+AOA/bn/kUyIiLBACcIvb3zCUUKADcF+Qm0JdCoLg3hZfAETnawRxHIejBy8A30aQm5ksCU0aALiPbdvk+z4EAWAYruuS53kk3fwFAPB/vpwLIcmyLHwHHYBREQRdFAEYE0Ee+gwuACLieR45jkMS1kAAGM5gMEAEAWCsIL7vYyQAGIJlWaO/UQiA6JimCUEAGBdB/jcAFDMwQYvTqsYAAAAASUVORK5CYII=";
        $sql = "insert into login(username,password,fullname,mobileNo,uid,status) values(?,?,?,?,?,?)";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($username, $password, $fullname, $mobileNo, $uid, $status))) {
            //processing default dp
            $pid = $this->con->lastInsertId();
            $sql2 = "insert into dp(pid,image) values(?,?)";
            $result2 = $this->con->prepare($sql2);
            if ($result2->execute(array($pid, $logo))) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    function activateAccount($id, $status, $table, $location, $uid)
    {
        if ($this->activateAccountId($id, $status, $table, $uid)) {
            $this->displayMsg("Account status updated..", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect($location);
    }


    function activateAccountId($id, $status, $table, $uid)
    {
        $id = $this->sanitize($id);
        $status = $this->sanitize($status);
        $table = $this->sanitize($table);
        $uid = $this->sanitize($uid);
        if ($id == "all") {
            $sql = "update " . $table . " set status=? where uid=? and main=0";
            $result = $this->con->prepare($sql);
            if ($result->execute(array($status, $uid))) {
                return true;
            } else {
                return false;
            }
        } else {
            $sql = "update " . $table . " set status=? where id=?";
            $result = $this->con->prepare($sql);
            $result->bindParam("i", $status);
            $result->bindParam("s", $id);
            if ($result->execute(array($status, $id))) {
                return true;
            } else {
                return false;
            }
        }
    }

    function updateUserProfile($id, $username, $fullname, $mobileNo, $location)
    {
        if ($this->updateAdminProfile($id, $username, $fullname, $mobileNo)) {
            $this->displayMsg("Profile updated..", 1);
            unset($_SESSION['useredit']);
            $this->redirect($location);
        }
    }

    function updateDp($pid, $image)
    {
        $pid = $this->sanitize($pid);
        $image = $this->sanitize($image);
        $sql = "update dp set image=? where pid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($image, $pid));
    }

    function updateUserProfile2($username, $fullname, $mobileNo, $dp)
    {
        $location = $_SERVER['REQUEST_URI'];
        $id = $_SESSION['stratekuser'];
        $username = $this->sanitize($username);
        $fullname = $this->sanitize($fullname);
        $mobileNo = $this->sanitize($mobileNo);
        $dp = $this->sanitize($dp);
        if ($this->updateAdminProfile($id, $username, $fullname, $mobileNo)) {
            //updating profile picture
            $this->updateDp($id, $dp);
            $this->displayMsg2("Profile updated", 1);
        } else {
            $this->displayMsg2("Process failed", 0);
        }
        $this->redirect($location);
    }

    function deleteUserAccount($id, $table, $location, $uid)
    {
        if ($this->delAccount($id, $table, $uid)) {
            $this->displayMsg("Account Deleted..", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect($location);
    }

    function delAccount($id, $table, $uid)
    {
        $id = $this->sanitize($id);
        $table = $this->sanitize($table);
        $uid = $this->sanitize($uid);
        if ($id == "all") {
            $sql = "update " . $table . " set status=2 where uid=? and main=0";
            $result = $this->con->prepare($sql);
            if ($result->execute(array($uid))) {
                return true;
            } else {
                return false;
            }
        } else {
            $sql = "update " . $table . " set status=2 where id=?";
            $result = $this->con->prepare($sql);
            $result->bindParam("s", $id);
            if ($result->execute(array($id))) {
                return true;
            } else {
                return false;
            }
        }
    }

    function resetPassword($pid, $password, $table)
    {
        $pid = $this->sanitize($pid);
        $password = sha1($this->sanitize($password));
        $table = $this->sanitize($table);
        $sql = "update " . $table . " set password=? where id=?";
        $result = $this->con->prepare($sql);
        $result->bindParam("s", $password);
        $result->bindParam("s", $pid);
        if ($result->execute(array($password, $pid))) {
            return true;
        } else {
            return false;
        }
    }


    function resetUserPassword($pid, $password, $table, $location)
    {
        if ($this->resetPassword($pid, $password, $table)) {
            $this->updateLastPassChng($pid);
            $this->displayMsg("Password updated..", 1);
        } else {
            $this->displayMsg("Process failed...", 0);
        }
        $this->redirect($location);
    }


    function loadAllPermissions()
    {
        $login = $this->getFullDetailsId($_SESSION['useredit'], "login");

        $data = "<div class='row' style='margin: 15px;'>";
        $data .= "<form method='post' action='#' class='form'>";
        //displaying table
        $data .= "<div class='row'>";
        $data .= "<table class='table table-bordered table-hover table-condensed table-striped' id='tableList1'>";
        $data .= "<thead><tr><th><center>No.</center></th><th><center>Category</center></th><th><center>Authorized</center></th></tr></thead>";
        $data .= "<tbody>";

        //looping through all permissions
        $sql = "select * from permissions order by id asc";
        $result = $this->con->query($sql);
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<tr><td><center>" . $count . "</center></td><td>" . $row['name'] . "</td><td><center>";
            $authorize = $this->checkAuthorize($row['id'], $login[0]);

            $data .= "<div class='form-group'>
								<select id='" . $row['id'] . "' class='form-control' name='" . $row['id'] . "'>";
            if ($authorize == 1) {
                $data .= "<option value='1'>Yes</option>";
                $data .= "<option value='0'>No</option>";
            } else {
                $data .= "<option value='0'>No</option>";
                $data .= "<option value='1'>Yes</option>";
            }
            $data .= "</select></div>";

            $data .= "</center></td></tr>";
            $count++;
        }

        $data .= "</tbody>";
        $data .= "</table>";
        $data .= "</div>"; //end of table row

        //submit row
        $data .= "<div class='row'>
					<center><button type='submit' name='updateBtn' class='btn btn-xs btn-success tooltip-bottom' title='Update Permissions'><span class='glyphicon glyphicon-plus'></span> Update Permissions</button></center>
				</div>";

        $data .= "</form>";
        $data .= "</div>";
        echo $data;
    }

    function checkAuthorize($id, $pid)
    {
        $id = $this->sanitize($id);
        $pid = $this->sanitize($pid);
        //check whether user is admin or ordinary
        $login = $this->getFullDetailsId($pid, "login");
        if (intval($login[8]) == 1) {
            return 1;
        } else {
            $sql = "select * from user_permissions where permission=? and pid=?";
            $result = $this->con->prepare($sql);
            $result->execute(array($id, $pid));
            if ($result->rowCount() >= 1) {
                return 1;
            } else {
                return 0;
            }
        }
    }


    function updatePermissions()
    {
        $login = $this->getFullDetailsId($_SESSION['useredit'], "login");
        //getting all post data
        $sql = "select * from permissions order by id asc";
        $result = $this->con->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            //getting post data
            $id = $row['id'];
            $permission = intval($this->sanitize($_POST[$id]));
            if ($permission == 1) {
                //add/update to user_permissions
                $this->updateAuthorize($id, $login[0]);
            } else {
                //delete from user_permissions
                $this->delAuthorize($id, $login[0]);
            }
        }
        $this->displayMsg("Permissions updated", 1);
        $this->redirect("?systemUsers&permissions");
    }

    function updateAuthorize($permission, $pid)
    {
        $permission = $this->sanitize($permission);
        $pid = $this->sanitize($pid);
        $sql = "insert into user_permissions(permission,pid) values(?,?)";
        $result = $this->con->prepare($sql);
        $result->execute(array($permission, $pid));
    }

    function delAuthorize($permission, $pid)
    {
        $permission = $this->sanitize($permission);
        $pid = $this->sanitize($pid);
        $sql = "delete from user_permissions where permission=? and pid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($permission, $pid));
    }

    function checkPermission($url, $pid)
    {
        $login = $this->getFullDetailsId($pid, "login");
        $uid = intval($login[4]);
        if ($uid = !0) {
            $sql = "select * from permissions where url=? limit 1";
            $result = $this->con->prepare($sql);
            $result->execute(array($url));
            $data = $result->fetch();
            if ($result->rowCount() >= 1) {
                //valid
                $permission = $data[0];
                $res = $this->checkAuthorize($permission, $login[0]);
                if ($res == 1) {
                    //authorize
                } else {
                    include "permissionDenied.php";
                    return false;
                }
                //echo $login[1];
            }
        }
    }

    function addBillingParamResult($name)
    {
        $name = $this->sanitize($name);
        $pid = $this->sanitize($_SESSION['stratekadmin']);
        $sql = "insert into billing_params(name,pid) values(?,?)";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($name, $pid))) {
            return true;
        } else {
            return false;
        }
    }

    function addBillingParam($name)
    {
        if ($this->addBillingParamResult($name)) {
            $this->displayMsg("Billing Param Added...", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?billingParams");
    }

    function updateStatusAdminPid($pid, $table)
    {
        if ($this->updateStatusPid($pid, $table)) {
            echo 1;
        } else {
            echo 0;
        }
    }

    function updateStatusPid($pid, $table)
    {
        $pid = $this->sanitize($pid);
        $table = $this->sanitize($table);
        if ($table == 'billing_params' || $table == "billing_params_categories" || $table == "food" || $table == "drinks" || $table == "food_subcategory" || $table == "drinks_subcategory") {
            $sql = "update " . $table . " set status=(status + 1)%2 where id=?";
        } elseif ($table == "orders") {
            $pid = $_SESSION['billingParamsCategories'];
            $sql = "update orders set status=1 where billing_params_categories=? and pending=1 and bill_process=0";
            $query = "select * from orders where billing_params_categories=? and pending=1 and bill_process=0";
            $res = $this->con->prepare($query);
            $res->execute(array($pid));
            if ($res->rowCount() >= 1) {
                //disable that menu item
                $query1 = "update billing_params_categories set status=0 where id=?";
                $res1 = $this->con->prepare($query1);
                $res1->execute(array($pid));
            }
        } else {
            $sql = "update " . $table . " set status=(status + 1)%2 where pid=?";
        }
        $result = $this->con->prepare($sql);
        $result->bindParam("s", $pid);
        if ($result->execute(array($pid))) {
            return true;
        } else {
            return false;
        }
    }

    function updateBillingParam($id, $name)
    {
        $id = $this->sanitize($id);
        $name = $this->sanitize($name);
        $sql = "update billing_params set name=? where id=?";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($name, $id))) {
            return true;
        } else {
            return false;
        }
    }

    function updateBillingParamResult($id, $name)
    {
        if ($this->updateBillingParam($id, $name)) {
            $this->displayMsg("Billing Param updated..", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?billingParams");
    }

    function loadBillingParams()
    {
        $data = "<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
        $data .= "<thead><tr><th><center>No.</center></th><th><center>Name of Billing Param</center></th><th><center>Date Added</center></th><th></th></tr></thead><tbody>";
        //loading data from database
        $sql = "select * from billing_params where status!=2 order by date desc";
        $result = $this->con->query($sql);
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<tr><td><center>" . $count . "</center></td><td><center>" . $row['name'] . "</center></td><td><center>" . $row['date'] . "</center></td>";
            $data .= "<td><center>";
            if ($row['status'] == 1) {
                //displaying deactivation button
                $data .= "<button type='button' onclick=\"updateStatusPid('" . $row['id'] . "','billing_params','?billingParams')\" class='btn btn-xs btn-success br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-open'></span></button>&nbsp;";
            } else {
                //display activation button
                $data .= "<button type='button' onclick=\"updateStatusPid('" . $row['id'] . "','billing_params','?billingParams')\" class='btn btn-xs btn-warning br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-close'></span></button>&nbsp;";
            }
            //adding edit modal
            $data .= "<a href='#edit" . $row['id'] . "' data-toggle='modal' class='btn btn-xs btn-info tooltip-bottom br' title='Edit Billing Param'><span class='glyphicon glyphicon-pencil'></span></a>&nbsp;";
            //adding a delete button
            $data .= "<button type='button' onclick=\"deleteReq('" . $row['id'] . "','billing_params','?billingParams')\" class='btn btn-xs btn-danger br tooltip-bottom' title='Click to delete Billing Param'><span class='glyphicon glyphicon-remove'></span></button>";

            $data .= "</center></td></tr>";

            ####modal ####
            $data .= "<div id='edit" . $row['id'] . "' class='modal fade'>
					<div class='modal-dialog modal-sm'>
						<div class='modal-content'>
							<div class='modal-header bgblue'>
								<h3 class='panel-title' style='text-align: center;'><span class='glyphicon glyphicon-th-list'></span> Billing Param</h3>
							</div>
							<div class='modal-body'>
								<form method='post' action='#' class='form'>
									<div class='form-group'>
										<label for='name'>Name of Billing Param:</label>
										<input type='text' id='name' name='name' class='form-control' placeholder='Name of Billing Param' value='" . $row['name'] . "' required=''>
										<input type='hidden' name='id' value='" . $row['id'] . "'/>
									</div>
									<div class='form-group'>
										<div style='text-align: center;'>
											<button type='submit' name='updateBillingParamBtn' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-plus'></span> Update Billing Param</button>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>";
            $count++;
        }

        $data .= "</tbody></table>";
        echo $data;
    }

    function deleteReq($pid, $table)
    {
        $pid = $this->sanitize($pid);
        $table = $this->sanitize($table);
        if ($table == 'billing_params' || $table == "billing_params_categories" || $table == "food" || $table == "drinks" || $table == "food_subcategory" || $table == "drinks_subcategory") {
            $sql = "update " . $table . " set status=2 where id=?";
        } elseif ($table == "orders" || $table == 'split_bill' || $table == 'merge_bill' || $table == "sysbackup" || $table == "dbbackup") {
            $sql = "delete from " . $table . " where id=?";
            if($table == "sysbackup"){
                $details = $this->getFullDetailsId($pid, $table);
                unlink("backups/sys/".$details[1]);
            }elseif($table == "dbbackup"){
                $details = $this->getFullDetailsId($pid, $table);
                unlink("backups/db/".$details[1]);
            }
        } else {
            $sql = "delete from " . $table . " where pid=?";
        }
        $result = $this->con->prepare($sql);
        $result->bindParam("s", $pid);
        if ($result->execute(array($pid))) {
            return true;
        } else {
            return false;
        }
    }


    function deleteReqAdmin($status, $table)
    {
        if ($this->deleteReq($status, $table)) {
            echo 1;
        } else {
            echo 0;
        }
    }

    function addMenuCategory($table, $name)
    {
        $table = $this->sanitize($table);
        $name = $this->sanitize($name);
        $pid = $_SESSION['stratekadmin'];
        $sql = "insert into " . $table . "(name,pid) values(?,?)";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($name, $pid))) {
            return true;
        } else {
            return false;
        }
    }

    function addMenuCategoryResult($table, $name, $location)
    {
        if ($this->addMenuCategory($table, $name)) {
            $this->displayMsg("New Category Added", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect($location);
    }

    function updateMenuCategory($table, $id, $name)
    {
        $table = $this->sanitize($table);
        $id = $this->sanitize($id);
        $name = $this->sanitize($name);
        $sql = "update " . $table . " set name=? where id=?";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($name, $id))) {
            return true;
        } else {
            return false;
        }
    }

    function updateMenuCategoryResult($table, $id, $name, $location)
    {
        if ($this->updateMenuCategory($table, $id, $name)) {
            $this->displayMsg("Category Updated...", 1);
        } else {
            $this->displayMsg("Process failed...", 0);
        }
        $this->redirect($location);
    }

    function loadMenuCategory($table)
    {
        $table = $this->sanitize($table);
        $sql = "select * from " . $table . " where status !=2 order by date desc";
        $data = "<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
        $data .= "<thead><tr><th><center>No.</center></th><th><center>Category Name</center></th><th><center>Date Added</center></th><th></th></tr></thead><tbody>";

        $result = $this->con->query($sql);
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<tr><td><center>" . $count . "</center></td><td><center>" . $row['name'] . "</center></td><td><center>" . $row['date'] . "</center></td>";
            $data .= "<td><center>";

            //displaying sub category button
            $data .= "<button type='button' onclick=\"view('" . $row['id'] . "','" . $table . "','?menu&view')\" class='btn btn-xs btn-primary br tooltip-bottom' title='View/Add Sub Category'><span class='glyphicon glyphicon-th-list'></span></button>&nbsp;";

            if ($row['status'] == 1) {
                //displaying deactivation button
                $data .= "<button type='button' onclick=\"updateStatusPid('" . $row['id'] . "','" . $table . "','?menu&" . $table . "')\" class='btn btn-xs btn-success br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-open'></span></button>&nbsp;";
            } else {
                //display activation button
                $data .= "<button type='button' onclick=\"updateStatusPid('" . $row['id'] . "','" . $table . "','?menu&" . $table . "')\" class='btn btn-xs btn-warning br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-close'></span></button>&nbsp;";
            }
            //adding edit modal
            $data .= "<a href='#edit" . $row['id'] . "' data-toggle='modal' class='btn btn-xs btn-info tooltip-bottom br' title='Edit'><span class='glyphicon glyphicon-pencil'></span></a>&nbsp;";

            //modals
            $data .= "<div id='edit" . $row['id'] . "' class='modal fade'>
							<div class='modal-dialog modal-sm'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<h3 class='panel-title' style='text-align: center;'><span class='glyphicon glyphicon-th-list'></span> Edit Category</h3>
									</div>
									<div class='modal-body'>
										<form method='post' action='?menu&" . $table . "' class='form' style='margin: 5px;'>
											<div class='form-group'>
												<label for='category'>Category Name:</label>
												<input type='text' id='category' name='category' class='form-control' placeholder='Category Name' value='" . $row['name'] . "' required=''>
												<input type='hidden' name='id' value='" . $row['id'] . "'/>
											</div>
											<div class='form-group'>
												<div style='text-align: center; margin-top: 5px;'>
													<button type='submit' name='updateCategoryBtn' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-plus'></span> Update Category</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>";

            //adding a delete button
            $data .= "<button type='button' onclick=\"deleteReq('" . $row['id'] . "','" . $table . "','?menu&" . $table . "')\" class='btn btn-xs btn-danger br tooltip-bottom' title='Click to delete Billing Param'><span class='glyphicon glyphicon-remove'></span></button>";

            $data .= "</center></td></tr>";
            $count++;
        }

        $data .= "</tbody></table>";
        echo $data;
    }

    function loadMenuSubCategory($table)
    {
        $table = $this->sanitize($table);
        $category = $_SESSION['useredit'];
        $data = "<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>";
        $data .= "<thead><tr><th><center>No.</center></th><th><center>Menu Item</center></th><th><center>Price (GH&cent;)</center></th><th><center>Date Added</th><th></th></tr></thead><tbody>";

        $sql = "select * from " . $table . " where status !=2 and category=? order by date desc";
        $result = $this->con->prepare($sql);
        $result->execute(array($category));
        $count = 1;
        $url = "view";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<tr><td><center>" . $count . "</center></td><td><center>" . $row['name'] . "</center></td><td><center>" . $row['price'] . "</center></td><td><center>" . $row['date'] . "</center></td>";
            $data .= "<td><center>";

            if ($row['status'] == 1) {
                //displaying deactivation button
                $data .= "<button type='button' onclick=\"updateStatusPid('" . $row['id'] . "','" . $table . "','?menu&" . $url . "')\" class='btn btn-xs btn-success br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-open'></span></button>&nbsp;";
            } else {
                //display activation button
                $data .= "<button type='button' onclick=\"updateStatusPid('" . $row['id'] . "','" . $table . "','?menu&" . $url . "')\" class='btn btn-xs btn-warning br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-close'></span></button>&nbsp;";
            }
            //adding edit modal
            $data .= "<a href='#edit" . $row['id'] . "' data-toggle='modal' class='btn btn-xs btn-info tooltip-bottom br' title='Edit'><span class='glyphicon glyphicon-pencil'></span></a>&nbsp;";


            //adding a delete button
            $data .= "<button type='button' onclick=\"deleteReq('" . $row['id'] . "','" . $table . "','?menu&" . $url . "')\" class='btn btn-xs btn-danger br tooltip-bottom' title='Click to delete Billing Param'><span class='glyphicon glyphicon-remove'></span></button>";
            $data .= "</center></td></tr>";

            //modals
            $data .= "<div id='edit" . $row['id'] . "' class='modal fade'>
					<div class='modal-dialog modal-sm'>
						<div class='modal-content'>
							<div class='modal-header bgblue'>
								<h3 class='panel-title' style='text-align: center;'><span class='glyphicon glyphicon-th-list'></span> Edit Menu Item</h3>
							</div>
							<div class='modal-body'>
								<form method='post' action='?menu&" . $url . "' class='form' style='margin: 5px;' id='categoryForm'>
									<div class='form-group'>
										<label for='category'>Menu Item:</label>
										<input type='text' id='category' name='name' class='form-control' placeholder='Menu Item' value='" . $row['name'] . "' required=''>
										<input type='hidden' name='id' value='" . $row['id'] . "'/>
									</div>
									<div class='form-group'>
										<label for='price'>Price(GH&cent;):</label>
										<input type='text' id='price' name='price' class='form-control' value='" . $row['price'] . "' placeholder='Price (GH&cent;)' required=''/>
									</div>
									<div class='form-group'>
										<label for='side'>Side:</label>
										<input type='text' id='side' name='side' class='form-control' value='" . $row['side'] . "' placeholder='Side'/>
									</div>
									<div class='form-group'>
										<div style='text-align: center; margin-top: 5px;'>
											<button type='submit' name='updateCategoryBtn' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-plus'></span> Update Menu Item</button>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>";

            $count++;
        }

        $data .= "</tbody></table>";
        echo $data;
    }


    function addMenuItem($table, $name, $price, $category, $side)
    {
        $table = $this->sanitize($table);
        $name = $this->sanitize($name);
        $price = $this->sanitize($price);
        $category = $this->sanitize($category);
        $side = $this->sanitize($side);
        $pid = $this->sanitize($_SESSION['stratekadmin']);
        $sql = "insert into " . $table . "(name,price,category,pid,side) values(?,?,?,?,?)";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($name, $price, $category, $pid, $side))) {
            return true;
        } else {
            return false;
        }
    }

    function addMenuItemResult($table, $name, $price, $category, $side)
    {
        if ($this->addMenuItem($table, $name, $price, $category, $side)) {
            $this->displayMsg("New Menu Item Added....", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?menu&view");
    }

    function updateMenuItem($table, $name, $price, $id, $side)
    {
        $stable = $this->sanitize($table);
        $name = $this->sanitize($name);
        $price = $this->sanitize($price);
        $id = $this->sanitize($id);
        $side = $this->sanitize($side);
        $sql = "update " . $table . " set name=?,price=?,side=? where id=?";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($name, $price, $side, $id))) {
            return true;
        } else {
            return false;
        }
    }

    function updateMenuItemResult($table, $name, $price, $id, $side)
    {
        if ($this->updateMenuItem($table, $name, $price, $id, $side)) {
            $this->displayMsg("Menu Item updated..", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?menu&view");
    }

    #####################################################################################################################
    ################################..................USERS...............###############################################
    function loadContentUser()
    {
        if (isset($_GET['logout'])) {
            $this->updateActiveLogin($_SESSION['stratekuser'], 0);
            unset($_SESSION['stratekuser']);
            unset($_SESSION['useredit']);
            unset($_SESSION['existingOrders']);
            $this->redirect("login.php");
        } elseif (isset($_GET['dashboard'])) {
            $this->redirect("home.php");
        } elseif (isset($_GET['newOrder'])) {
            $this->setClassActive('newOrder');
            $this->setHeaderTitle("New Order");
            if (isset($_GET['proceed'])) {
                include "user/newOrderProceed.php";
            } else {
                include "user/newOrder.php";
            }
        } elseif (isset($_GET['existingOrder'])) {
            $this->setClassActive('existingOrder');
            $this->setHeaderTitle("Existing Order");
            include "user/existingOrder.php";
        } elseif (isset($_GET['processBill'])) {
            $this->setClassActive('processBill');
            $this->setHeaderTitle("Process Bill");
            include "user/processBill.php";
        } elseif (isset($_GET['splitBill'])) {
            $this->setClassActive('splitBill');
            $this->setHeaderTitle("Split Bill");
            include "user/splitBill.php";
        } elseif (isset($_GET['mergeBill'])) {
            $this->setClassActive('mergeBill');
            $this->setHeaderTitle("Merge Bill");
            include "user/mergeBill.php";
        } elseif (isset($_GET['password'])) {
            $this->setHeaderTitle("Change Password");
            include "user/password.php";
        } elseif(isset($_GET['processedBill'])){
            if(isset($_GET['edit'])){
                $this->setHeaderTitle("Processed Transactions/Bill | ".$_SESSION['processedBillTid']);
                $this->setHeaderUrl("?processedBill");
                include "user/processedBillEdit.php";
            }else{
                $this->setHeaderTitle("Processed Transactions/Bills");
                include "user/processedBill.php";
            }
        }else {
            $this->redirect("home.php");
        }
    }

    function setClassActive($id)
    {
        echo "<script>$('#" . $id . "').addClass('active');</script>";
    }

    function verifyUser($username, $password)
    {
        $username = $this->sanitize($username);
        $password = sha1($this->sanitize($password));
        $sql = "select * from login where username=? and password=? and status=1";
        $result = $this->con->prepare($sql);
        $result->bindParam("s", $username);
        $result->bindParam("s", $password);
        $result->execute(array($username, $password));
        if ($result->rowCount() >= 1) {
            //authentication successful
            $details = $this->getFullDetails($username, "login");
            $_SESSION['stratekuser'] = $details[0];
            $_SESSION['lock'] = 0;
            $this->updateLastLogin($details[0]);
            $this->updateActiveLogin($_SESSION['stratekuser'], 1);
            //$this->displayMsg2("LogIn successful...", 1);
            $this->redirect("index.php");
        } else {
            $this->displayMsg2("LogIn failed...", 0);
            //$this->redirect("login.php");
        }
    }

    function genNameID($table)
    {
        $table = $this->sanitize($table);
        if ($table == "billing_params") {
            $sql = "select * from " . $table . " where status!=2";
        } else {
            $sql = "select * from " . $table . "";
        }
        $result = $this->con->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
        }
    }

    function genNameID2($table)
    {
        $data = null;
        $table = $this->sanitize($table);
        if ($table == "billing_params") {
            $sql = "select * from " . $table . " where status!=2";
        } else {
            $sql = "select * from " . $table . "";
        }
        $result = $this->con->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
        }
        return $data;
    }

    function previewBillingParam()
    {
        $data = null;
        if (isset($_GET['billingParams'])) {
            $billingParams = intval($this->sanitize($_GET['billingParams']));
            $sql = "select * from billing_params where status != 2 order by date desc";
            $result = $this->con->query($sql);
            $data = null;
            $count = 1;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if ($row['id'] == $billingParams) {
                    $data .= "<a href='?newOrder&billingParams=".$row['id']."' class='white-text'>";
                    $data .= "<div class='card green active'>
              								  <div class='card-content'>
              								    <p class='card-title truncate'>
              								      ". ucwords($row['name']) . "
              								    </p>
              								  </div>
              								</div>";
                    $data .= "</a>";
                    $data .= $this->replaceTitle2($row['name'], "paramTitle");
                } else {
                    $data .= "<a href='?newOrder&billingParams=".$row['id']."' class='white-text'>";
                    $data .= "<div class='card grey'>
    								  <div class='card-content'>
    								    <p class='card-title truncate'>
    								      " . ucwords($row['name']) . "
    								    </p>
    								  </div>
    								</div>";
                    $data .= "</a>";
                }
                $count++;
            }
        } else {
            $sql = "select * from billing_params where status != 2 order by date desc";
            $result = $this->con->query($sql);
            $data = null;
            $count = 1;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if ($count == 1) {
                    $data .= "<a href='?newOrder&billingParams=" . $row['id'] . "' class='white-text'>";
                    $data .= "<div class='card green active'>
								  <div class='card-content'>
								    <p class='card-title truncate'>
								      " . ucwords($row['name']) . "
								    </p>
								  </div>
								</div>";
                    $data .= "</a>";
                    $data .= $this->replaceTitle2($row['name'], "paramTitle");
                } else {
                    $data .= "<a href='?newOrder&billingParams=" . $row['id'] . "' class='white-text'>";
                    $data .= "<div class='card grey'>
								  <div class='card-content'>
								    <p class='card-title truncate'>
								      " . ucwords($row['name']) . "
								    </p>
								  </div>
								</div>";
                    $data .= "</a>";
                }
                $count++;
            }
        }
        echo $data;
    }


    function replaceTitle($title, $id)
    {
        $title = $this->sanitize($title);
        $id = $this->sanitize($id);
        echo "<script>
					$('#" . $id . "').html(\"" . $title . "\");
				</script>";
    }

    function replaceTitle2($title, $id)
    {
        $title = $this->sanitize($title);
        $id = $this->sanitize($id);
        return "<script>
					$('#" . $id . "').html(\"" . $title . "\");
				</script>";
    }

    function previewBillingParamCategory()
    {
        $data = null;
        if (isset($_GET['billingParams'])) {
            $billing_params = intval($this->sanitize($_GET['billingParams']));
            //getting all billing_params_categories
            $sql = "select * from billing_params_categories where billing_params=? and status=1";
            $result = $this->con->prepare($sql);
            $result->execute(array($billing_params));
            $count = 1;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if ($count == 1) {
                    //beginining of row
                    $data .= "<div class=\"row negative-margins\">";
                }

                //displaying data
                $data .= "<div class=\"col s6 m4 l3\">
				                <div class=\"card grey darken-3\" style='border-radius: 50px; -webkit-border-radius: 50px; -moz-border-radius: 50px;style='padding: 0px 0px;'>
				                  <div class=\"card-content\" style='padding: 6px 4px;'>
				                    <p class=\"card-title center-align white-text\"><button class='grey darken-3' style='border-radius: 50px; -webkit-border-radius: 50px; -moz-border-radius: 50px; width: 100%;' onclick=\"view('" . $row['id'] . "','billing_params_categories','?newOrder&proceed')\">" . $row['name'] . "</button></p>
				                  </div>
				                </div>
				              </div>";

                if ($count % 4 == 0) {
                    //end of row
                    $data .= "</div>";
                }
                $count++;
            }
        } else {
            //getting default
            $sql = "select * from billing_params where status != 2 order by date desc limit 1";
            $result = $this->con->query($sql);

            $billing_params = $result->fetch();
            $billing_params = $billing_params[0];
            //getting all billing_params_categories
            $sql = "select * from billing_params_categories where billing_params=? and status=1";
            $result = $this->con->prepare($sql);
            $result->execute(array($billing_params));
            $count = 1;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if ($count == 1) {
                    //beginining of row
                    $data .= "<div class=\"row negative-margins\">";
                }

                //displaying data
                $data .= "<div class=\"col s6 m4 l3\">
				                <div class=\"card grey darken-3\" style='border-radius: 50px; -webkit-border-radius: 50px; -moz-border-radius: 50px; padding: 0px 0px;'>
				                  <div class=\"card-content\" style='padding: 6px 4px;'>
				                    <p class=\"card-title center-align white-text\"><button class='grey darken-3' style='border-radius: 50px; -webkit-border-radius: 50px; -moz-border-radius: 50px; width: 100%;' onclick=\"view('" . $row['id'] . "','billing_params_categories','?newOrder&proceed')\">" . $row['name'] . "</button></p>
				                  </div>
				                </div>
				              </div>";

                if ($count % 4 == 0) {
                    //end of row
                    $data .= "</div>";
                }
                $count++;
            }
        }
        echo $data;
    }

    function loadBillingParamsCategory($billing_params)
    {
        $id = $this->sanitize($billing_params);
        $data = "<table class='table table-bordered table-condensed table-hover table-striped' id='tableList'>";
        $data .= "<thead><tr><th><center>No</center></th><th><center>Sub Category Name</center></th><th><center>Date Added</center></th><th></th></tr></thead><tbody>";

        //getting data from database
        $sql = "select * from billing_params_categories where status!=2 and billing_params=? order by date desc";
        $result = $this->con->prepare($sql);
        $result->execute(array($id));
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data .= "<tr><td><center>" . $count . "</center></td><td><center>" . $row['name'] . "</center></td><td><center>" . $row['date'] . "</center></td>";
            $data .= "<td><center>";
            if ($row['status'] == 1) {
                //displaying deactivation button
                $data .= "<button type='button' onclick=\"updateStatusPid('" . $row['id'] . "','billing_params_categories','?billingParams&category')\" class='btn btn-xs btn-success br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-open'></span></button>&nbsp;";
            } else {
                //display activation button
                $data .= "<button type='button' onclick=\"updateStatusPid('" . $row['id'] . "','billing_params_categories','?billingParams&category')\" class='btn btn-xs btn-warning br tooltip-bottom' title='Click to deactivate'><span class='glyphicon glyphicon-eye-close'></span></button>&nbsp;";
            }
            //adding edit modal
            $data .= "<a href='#edit" . $row['id'] . "' data-toggle='modal' class='btn btn-xs btn-info tooltip-bottom br' title='Edit Billing Param'><span class='glyphicon glyphicon-pencil'></span></a>&nbsp;";

            //modals
            $data .= "<div id='edit" . $row['id'] . "' class='modal fade'>
							<div class='modal-dialog modal-sm'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<h3 class='panel-title' style='text-align: center;'><span class='glyphicon glyphicon-th-list'></span> Billing Param</h3>
									</div>
									<div class='modal-body'>
										<form method='post' action='?billingParams&category' class='form' style='margin: 5px;'>
											<div class='form-group'>
												<label for='name'>Name of Billing Param Category:</label>
												<input type='text' id='name' name='name' class='form-control' placeholder='Name of Billing Param Category' value='" . $row['name'] . "' required=''>
												<input type='hidden' name='id' value='" . $row['id'] . "'/>
											</div>
											<div class='form-group'>
												<div style='text-align: center; margin-top: 5px;'>
													<button type='submit' name='updateBillingParamCategoryBtn' class='btn btn-xs btn-success'><span class='glyphicon glyphicon-plus'></span> Update Category</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>";

            //adding a delete button
            $data .= "<button type='button' onclick=\"deleteReq('" . $row['id'] . "','billing_params_categories','?billingParams&category')\" class='btn btn-xs btn-danger br tooltip-bottom' title='Click to delete Billing Param'><span class='glyphicon glyphicon-remove'></span></button>";

            $data .= "</center></td></tr>";


            $count++;
        }

        $data .= "</tbody></table>";
        $data .= "<script>$('#tableList').DataTable({responsive: true});</script>";
        echo $data;
    }


    function addBillingParamsCategory($id, $name)
    {
        $id = $this->sanitize($id);
        $name = $this->sanitize($name);
        $sql = "insert into billing_params_categories(name,billing_params) values(?,?)";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($name, $id))) {
            return true;
        } else {
            return false;
        }
    }

    function addBillingParamsCategoryResult($id, $name)
    {
        if ($this->addBillingParamsCategory($id, $name)) {
            $this->displayMsg("New category added", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?billingParams&category");
    }

    function updateBillingParamCategoryResult($id, $name)
    {
        if ($this->updateBillingParamCategory($id, $name)) {
            $this->displayMsg("Category updated", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?billingParams&category");
    }

    function updateBillingParamCategory($id, $name)
    {
        $id = $this->sanitize($id);
        $name = $this->sanitize($name);
        $sql = "update billing_params_categories set name=? where id=?";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($name, $id))) {
            return true;
        } else {
            return false;
        }
    }

    function updateUserPassword($id, $oldpassword, $newpassword, $table)
    {
        if ($this->updatePassword($id, $oldpassword, $newpassword, $table)) {
            $this->updateLastPassChng($id);
            $this->displayMsg2("Password updated", 1);
        } else {
            $this->displayMsg2("Process failed..", 0);
        }
        $this->redirect("?logout");
    }

    function previewIndicator()
    {
        if (isset($_GET['drinks'])) {
            echo "<li class='tab col s6 no-padding'>
				          <a href='#food' class='blue-text bold-800'><i class='material-icons'>restaurant</i> <span>FOOD</span></a>
				        </li>
				        <li class='tab col s6 no-padding'>
				          <a href='#drinks'  class='active blue-text bold-800'><i class='material-icons'>local_bar</i> <span>DRINKS</span></a>
				        </li>";
        } else {
            echo "<li class='tab col s6 no-padding'>
				          <a href='#food' class='active blue-text bold-800'><i class='material-icons'>restaurant</i> <span>FOOD</span></a>
				        </li>
				        <li class='tab col s6 no-padding'>
				          <a href='#drinks'  class='blue-text bold-800'><i class='material-icons'>local_bar</i> <span>DRINKS</span></a>
				        </li>";
        }
    }


    function previewCategoryTitle()
    {
        $id = null;
        if (isset($_GET['drinks'])) {
            $id = intval($this->sanitize($_GET['drinks']));
            $sql = "select * from drinks where status=1 order by name";
        } elseif (isset($_GET['food'])) {
            $id = intval($this->sanitize($_GET['food']));
            $sql = "select * from food where status=1 order by name";
        } else {
            $sql = "select * from food where status=1 order by name";
        }
        $result = $this->con->query($sql);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['id'] == $id) {
                $this->setTitle(ucwords($row['name']), 'orderTitle');
            } elseif ($id == null) {
                $this->setTitle(ucwords($row['name']), 'orderTitle');
                break;
            }
        }
    }

    function previewCategoryList($table)
    {
        $sql = "select * from " . $table . " where status=1 order by name";
        $result = $this->con->query($sql);
        $id = null;
        if (isset($_GET['drinks'])) {
            $id = intval($this->sanitize($_GET['drinks']));
        } elseif (isset($_GET['food'])) {
            $id = intval($this->sanitize($_GET['food']));
        }
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['id'] == $id) {
                echo "<li class='active'><a href='?newOrder&proceed&" . $table . "=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
            } elseif ($id == null) {
                //default
                if ($count == 1) {
                    echo "<li class='active'><a href='?newOrder&proceed&" . $table . "=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
                } else {
                    echo "<li><a href='?newOrder&proceed&" . $table . "=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
                }
            } else {
                echo "<li><a href='?newOrder&proceed&" . $table . "=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
            }
            $count++;
        }
    }

    function previewMenuList()
    {
        $data = null;
        $subCategory = null;
        if (isset($_GET['drinks'])) {
            $id = intval($this->sanitize($_GET['drinks']));
            $subCategory = "drinks_subcategory";
            $sql = "select * from drinks_subcategory where status=1 and category=? order by name";
            $result = $this->con->prepare($sql);
            $result->execute(array($id));
        } elseif (isset($_GET['food'])) {
            $id = intval($this->sanitize($_GET['food']));
            $subCategory = "food_subcategory";
            $sql = "select * from food_subcategory where status=1 and category=? order by name";
            $result = $this->con->prepare($sql);
            $result->execute(array($id));
        } else {
            //load default
            $sql = "select id from food where status=1 order by name limit 1";
            $result = $this->con->query($sql);
            $id = $result->fetch();
            $id = $id[0]; //got id

            $query = "select * from food_subcategory where status=1 and category=? order by name";
            $result = $this->con->prepare($query);
            $result->execute(array($id));
            $subCategory = "food_subcategory";
        }

        $max = 4;

        //getting all data into an array
        $count = 1;
        $column1 = array();
        $column2 = array();
        $column3 = array();
        $column4 = array();
        $column1Counter = 0;
        $column2Counter = 0;
        $column3Counter = 0;
        $column4Counter = 0;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($count % $max == 1) {
                //first row
                $column1[$column1Counter]["id"] = $row['id'];
                $column1[$column1Counter]["name"] = $row['name'];
                $column1[$column1Counter]["price"] = $row['price'];
                $column1[$column1Counter]["category"] = $row['category'];
                $column1[$column1Counter]["pid"] = $row['pid'];
                $column1[$column1Counter]["status"] = $row['status'];
                $column1[$column1Counter]["side"] = $row['side'];
                $column1[$column1Counter]["date"] = $row['date'];
                $column1Counter++;
            } elseif ($count % $max == 2) {
                //second row
                $column2[$column2Counter]["id"] = $row['id'];
                $column2[$column2Counter]["name"] = $row['name'];
                $column2[$column2Counter]["price"] = $row['price'];
                $column2[$column2Counter]["category"] = $row['category'];
                $column2[$column2Counter]["pid"] = $row['pid'];
                $column2[$column2Counter]["status"] = $row['status'];
                $column2[$column2Counter]["date"] = $row['date'];
                $column2[$column2Counter]["side"] = $row['side'];
                $column2Counter++;
            } elseif ($count % $max == 3) {
                //third row
                $column3[$column3Counter]["id"] = $row['id'];
                $column3[$column3Counter]["name"] = $row['name'];
                $column3[$column3Counter]["price"] = $row['price'];
                $column3[$column3Counter]["category"] = $row['category'];
                $column3[$column3Counter]["pid"] = $row['pid'];
                $column3[$column3Counter]["status"] = $row['status'];
                $column3[$column3Counter]["date"] = $row['date'];
                $column3[$column3Counter]["side"] = $row['side'];
                $column3Counter++;
            } else {
                // last row
                $column4[$column4Counter]["id"] = $row['id'];
                $column4[$column4Counter]["name"] = $row['name'];
                $column4[$column4Counter]["price"] = $row['price'];
                $column4[$column4Counter]["category"] = $row['category'];
                $column4[$column4Counter]["pid"] = $row['pid'];
                $column4[$column4Counter]["status"] = $row['status'];
                $column4[$column4Counter]["date"] = $row['date'];
                $column4[$column4Counter]["side"] = $row['side'];
                $column4Counter++;
            }
            $count++;
        }


        //generating the four columns
        for ($i = 1; $i <= $max; $i++) {
            $data .= "<div class='col s6 m4 l3'>
							<ul class='menu-list'>";
            if ($i == 1) {
                //column 1
                for ($j = 0; $j < sizeof($column1); $j++) {
                    $data .= "<li style='border: 1px solid #035888; margin-bottom: 10px; border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px;'><a href='#add" . $column1[$j]["id"] . "'  class='truncate modal-trigger'>" . $column1[$j]["name"] . "</a></li>";
                    $data .= "<div id='add" . $column1[$j]["id"] . "' class='modal'>
											<div class='modal-content no-padding'>
											  <nav class='green'>
											    <div class='nav-wrapper'>
											      <a href='#!' class='brand-logo left' style='font-size: 25px;'>New Order | " . $column1[$j]["name"] . "</a>
											      <ul class='right'>
											        <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
											      </ul>
											    </div>
											    </nav>
											    <div class='row'>
											        <form class='form' method='post' action='#' id='profileForm'>
											                        <legend style='text-align: center; font-size: 20px;' class='blue-text'>Place Order</legend>
											                        <div class='row'>
											                           <div class='col s4 offset-s1'>
											                               <div class='form-group'>
											                               	<label for='menu'>Menu Item:</label>
											                               	<input type='text' class='form-control green-text' value='" . $column1[$j]["name"] . "' readonly=''/>
											                               </div>
											                               <div class='form-group'>
												                               	<label for='side'>Side:</label>
												                               	<input type='text' id='side" . $column1[$j]['id'] . "' name='side" . $column1[$j]['id'] . "' class='form-control green-text' placeholder='Side' value='" . $column1[$j]['side'] . "'/>
												                               	<input type='hidden' id='price" . $column1[$j]['id'] . "' name='price" . $column1[$j]['id'] . "' value='" . $column1[$j]['price'] . "'/>
												                               	<input type='hidden' id='table" . $column1[$j]['id'] . "' value='" . $subCategory . "'/>
												                               	<input type='hidden' id='id" . $column1[$j]['id'] . "' value='" . $column1[$j]['id'] . "'/>
											                               </div>
											                           </div>
											                           <div class='col s4 offset-s1'>
											                               <div class='quantity'>
											                               		<label for='quantity'>Quantity:</label>
											                               		<input type='number' id='quantity" . $column1[$j]['id'] . "' name='quantity" . $column1[$j]['id'] . "' min='1' class='form-control green-text' onchange='updatePrice" . $column1[$j]['id'] . "()' value='1' required=''/>
											                               </div>
											                               <div class='form-group'>
											                               		<label for='remarks'>Remarks:</label>
											                               		<textarea name='remarks" . $column1[$j]['id'] . "' label='remarks' class='form-control green-text' id='remarks" . $column1[$j]['id'] . "'></textarea>
											                               </div>
											                               <div class='form-group'>
											                               		<div style='text-align: center;font-size: 15px;' class='blue-text'>
											                               			Total Amount(GH&cent;): <span id='totalAmount" . $column1[$j]['id'] . "' class='blue-text' style='font-size: 20px;'>" . $this->formatNumber($column1[$j]['price']) . "</span>
											                               		</div>
											                               </div>
											                           </div>
											                         </div>
											                         <div class='row'>
											                           	<div style='text-align: center;'>
											                           		<button type='button' onclick='addList" . $column1[$j]['id'] . "()' name='addListBtn' class='btn btn-xs btn-success'> Add to List</button>
											                           	</div>
											                         </div>
											                     </form>
											    </div>

											</div>
											</div>";
                    ### javascript code
                    $data .= "<script>
												function updatePrice" . $column1[$j]['id'] . "(){
													var currentPrice = document.getElementById('price" . $column1[$j]['id'] . "').value;
													var quantity = document.getElementById('quantity" . $column1[$j]['id'] . "').value;
													var totalAmount = parseFloat(currentPrice) * parseFloat(quantity);
													document.getElementById('totalAmount" . $column1[$j]['id'] . "').innerHTML= roundNumber(totalAmount,2) ;
												}

												function addList" . $column1[$j]['id'] . "(){
													var category = '" . $subCategory . "';
													var side = $('#side" . $column1[$j]['id'] . "').val();
													var remarks = $('#remarks" . $column1[$j]['id'] . "').val();
													var id = $('#id" . $column1[$j]['id'] . "').val();
													var quantity = $('#quantity" . $column1[$j]['id'] . "').val();
													var price = $('#price". $column1[$j]['id']. "').val();
													//sending data
													$.post('ajax.php',{'newOrder':'y','category':category,'side':side,'remarks':remarks,'id':id,'quantity':quantity,'price':price},function(data){
														if(parseInt(data) == 1){
															displayMessage('Order placed..',1);
															//close modal
															$('#add" . $column1[$j]['id'] . "').addClass('modal-action modal-close waves-effect');
															$('#add" . $column1[$j]['id'] . "').modal().hide();
															window.location.assign('" . $_SESSION['currentUrl'] . "');
														}else{
															displayMessage('Process failed.. Try again!!',0);
														}
													});
												}
											</script>";
                }
            } elseif ($i == 2) {
                //column 2
                for ($j = 0; $j < sizeof($column2); $j++) {
                    $data .= "<li style='border: 1px solid #035888; margin-bottom: 10px; border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px;'><a href='#add" . $column2[$j]["id"] . "' class='truncate modal-trigger'>" . $column2[$j]["name"] . "</a></li>";
                    $data .= "<div id='add" . $column2[$j]["id"] . "' class='modal'>
											<div class='modal-content no-padding'>
											  <nav class='green'>
											    <div class='nav-wrapper'>
											      <a href='#!' class='brand-logo left' style='font-size: 25px;'>New Order | " . $column2[$j]["name"] . "</a>
											      <ul class='right'>
											        <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
											      </ul>
											    </div>
											    </nav>
											    <div class='row'>
											        <form class='form' method='post' action='#' id='profileForm'>
											                        <legend style='text-align: center; font-size: 20px;' class='blue-text'>Place Order</legend>
											                        <div class='row'>
											                           <div class='col s4 offset-s1'>
											                               <div class='form-group'>
											                               	<label for='menu'>Menu Item:</label>
											                               	<input type='text' class='form-control green-text' value='" . $column2[$j]["name"] . "' readonly=''/>
											                               </div>
											                               <div class='form-group'>
												                               	<label for='side'>Side:</label>
												                               	<input type='text' id='side" . $column2[$j]['id'] . "' name='side" . $column2[$j]['id'] . "' class='form-control green-text' placeholder='Side' value='" . $column2[$j]['side'] . "'/>
												                               	<input type='hidden' id='price" . $column2[$j]['id'] . "' name='price" . $column2[$j]['id'] . "' value='" . $column2[$j]['price'] . "'/>
												                               	<input type='hidden' id='table" . $column2[$j]['id'] . "' value='" . $subCategory . "'/>
												                               	<input type='hidden' id='id" . $column2[$j]['id'] . "' value='" . $column2[$j]['id'] . "'/>
											                               </div>
											                           </div>
											                           <div class='col s4 offset-s1'>
											                               <div class='quantity'>
											                               		<label for='quantity'>Quantity:</label>
											                               		<input type='number' id='quantity" . $column2[$j]['id'] . "' name='quantity" . $column2[$j]['id'] . "' min='1' class='form-control green-text' onchange='updatePrice" . $column2[$j]['id'] . "()' value='1' required=''/>
											                               </div>
											                               <div class='form-group'>
											                               		<label for='remarks'>Remarks:</label>
											                               		<textarea name='remarks" . $column2[$j]['id'] . "' label='remarks' class='form-control green-text' id='remarks" . $column2[$j]['id'] . "'></textarea>
											                               </div>
											                               <div class='form-group'>
											                               		<div style='text-align: center;font-size: 15px;' class='blue-text'>
											                               			Total Amount(GH&cent;): <span id='totalAmount" . $column2[$j]['id'] . "' class='blue-text' style='font-size: 20px;'>" . $this->formatNumber($column2[$j]['price']) . "</span>
											                               		</div>
											                               </div>
											                           </div>
											                         </div>
											                         <div class='row'>
											                           	<div style='text-align: center;'>
											                           		<button type='button' onclick='addList" . $column2[$j]['id'] . "()' name='addListBtn' class='btn btn-xs btn-success'> Add to List</button>
											                           	</div>
											                         </div>
											                     </form>
											    </div>
											</div>
											</div>";
                    ### javascript code
                    $data .= "<script>
												function updatePrice" . $column2[$j]['id'] . "(){
													var currentPrice = document.getElementById('price" . $column2[$j]['id'] . "').value;
													var quantity = document.getElementById('quantity" . $column2[$j]['id'] . "').value;
													var totalAmount = parseFloat(currentPrice) * parseFloat(quantity);
													document.getElementById('totalAmount" . $column2[$j]['id'] . "').innerHTML= roundNumber(totalAmount,2) ;
												}

												function addList" . $column2[$j]['id'] . "(){
													var category = '" . $subCategory . "';
													var side = $('#side" . $column2[$j]['id'] . "').val();
													var remarks = $('#remarks" . $column2[$j]['id'] . "').val();
													var id = $('#id" . $column2[$j]['id'] . "').val();
													var quantity = $('#quantity" . $column2[$j]['id'] . "').val();
													var price = $('#price". $column2[$j]['id'] . "').val();
													//sending data
													$.post('ajax.php',{'newOrder':'y','category':category,'side':side,'remarks':remarks,'id':id,'quantity':quantity, 'price':price},function(data){
														if(parseInt(data) == 1){
															displayMessage('Order placed..',1);
															//close modal
															$('#add" . $column2[$j]['id'] . "').addClass('modal-action modal-close waves-effect');
															$('#add" . $column2[$j]['id'] . "').modal().hide();
															window.location.assign('" . $_SESSION['currentUrl'] . "');
														}else{
															displayMessage('Process failed.. Try again!!',0);
														}
													});
												}
											</script>";
                }
            } elseif ($i == 3) {
                //column 3
                for ($j = 0; $j < sizeof($column3); $j++) {
                    $data .= "<li style='border: 1px solid #035888; margin-bottom: 10px; border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px;'><a href='#add" . $column3[$j]["id"] . "' class='truncate modal-trigger'>" . $column3[$j]["name"] . "</a></li>";
                    $data .= "<div id='add" . $column3[$j]["id"] . "' class='modal'>
											<div class='modal-content no-padding'>
											  <nav class='green'>
											    <div class='nav-wrapper'>
											      <a href='#!' class='brand-logo left' style='font-size: 25px;'>New Order | " . $column3[$j]["name"] . "</a>
											      <ul class='right'>
											        <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
											      </ul>
											    </div>
											    </nav>
											    <div class='row'>
											        <form class='form' method='post' action='#' id='profileForm'>
											                        <legend style='text-align: center; font-size: 20px;' class='blue-text'>Place Order</legend>
											                        <div class='row'>
											                           <div class='col s4 offset-s1'>
											                               <div class='form-group'>
											                               	<label for='menu'>Menu Item:</label>
											                               	<input type='text' class='form-control green-text' value='" . $column3[$j]["name"] . "' readonly=''/>
											                               </div>
											                               <div class='form-group'>
												                               	<label for='side'>Side:</label>
												                               	<input type='text' id='side" . $column3[$j]['id'] . "' name='side" . $column3[$j]['id'] . "' class='form-control green-text' placeholder='Side' value='" . $column3[$j]['side'] . "'/>
												                               	<input type='hidden' id='price" . $column3[$j]['id'] . "' name='price" . $column3[$j]['id'] . "' value='" . $column3[$j]['price'] . "'/>
												                               	<input type='hidden' id='table" . $column3[$j]['id'] . "' value='" . $subCategory . "'/>
												                               	<input type='hidden' id='id" . $column3[$j]['id'] . "' value='" . $column3[$j]['id'] . "'/>
											                               </div>
											                           </div>
											                           <div class='col s4 offset-s1'>
											                               <div class='quantity'>
											                               		<label for='quantity'>Quantity:</label>
											                               		<input type='number' id='quantity" . $column3[$j]['id'] . "' name='quantity" . $column3[$j]['id'] . "' min='1' class='form-control green-text' onchange='updatePrice" . $column3[$j]['id'] . "()' value='1' required=''/>
											                               </div>
											                               <div class='form-group'>
											                               		<label for='remarks'>Remarks:</label>
											                               		<textarea name='remarks" . $column3[$j]['id'] . "' label='remarks' class='form-control green-text' id='remarks" . $column3[$j]['id'] . "'></textarea>
											                               </div>
											                               <div class='form-group'>
											                               		<div style='text-align: center;font-size: 15px;' class='blue-text'>
											                               			Total Amount(GH&cent;): <span id='totalAmount" . $column3[$j]['id'] . "' class='blue-text' style='font-size: 20px;'>" . $this->formatNumber($column3[$j]['price']) . "</span>
											                               		</div>
											                               </div>
											                           </div>
											                         </div>
											                         <div class='row'>
											                           	<div style='text-align: center;'>
											                           		<button type='button' onclick='addList" . $column3[$j]['id'] . "()' name='addListBtn' class='btn btn-xs btn-success'> Add to List</button>
											                           	</div>
											                         </div>
											                     </form>
											    </div>
											</div>
											</div>";
                    ### javascript code
                    $data .= "<script>
												function updatePrice" . $column3[$j]['id'] . "(){
													var currentPrice = document.getElementById('price" . $column3[$j]['id'] . "').value;
													var quantity = document.getElementById('quantity" . $column3[$j]['id'] . "').value;
													var totalAmount = parseFloat(currentPrice) * parseFloat(quantity);
													document.getElementById('totalAmount" . $column3[$j]['id'] . "').innerHTML= roundNumber(totalAmount,2) ;
												}

												function addList" . $column3[$j]['id'] . "(){
													var category = '" . $subCategory . "';
													var side = $('#side" . $column3[$j]['id'] . "').val();
													var remarks = $('#remarks" . $column3[$j]['id'] . "').val();
													var id = $('#id" . $column3[$j]['id'] . "').val();
													var quantity = $('#quantity" . $column3[$j]['id'] . "').val();
													var price = $('#price".$column3[$j]['id']."').val();
													//sending data
													$.post('ajax.php',{'newOrder':'y','category':category,'side':side,'remarks':remarks,'id':id,'quantity':quantity,'price':price},function(data){
														if(parseInt(data) == 1){
															displayMessage('Order placed..',1);
															//close modal
															$('#add" . $column3[$j]['id'] . "').addClass('modal-action modal-close waves-effect');
															$('#add" . $column3[$j]['id'] . "').modal().hide();
															window.location.assign('" . $_SESSION['currentUrl'] . "');
														}else{
															displayMessage('Process failed.. Try again!!',0);
														}
													});
												}
											</script>";
                }
            } else {
                //column 4
                for ($j = 0; $j < sizeof($column4); $j++) {
                    $data .= "<li style='border: 1px solid #035888; margin-bottom: 10px; border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px;'><a href='#add" . $column4[$j]["id"] . "' class='truncate modal-trigger'>" . $column4[$j]["name"] . "</a></li>";
                    $data .= "<div id='add" . $column4[$j]["id"] . "' class='modal'>
											<div class='modal-content no-padding'>
											  <nav class='green'>
											    <div class='nav-wrapper'>
											      <a href='#!' class='brand-logo left' style='font-size: 25px;'>New Order | " . $column4[$j]["name"] . "</a>
											      <ul class='right'>
											        <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
											      </ul>
											    </div>
											    </nav>
											    <div class='row'>
											        <form class='form' method='post' action='#' id='profileForm'>
											                        <legend style='text-align: center; font-size: 20px;' class='blue-text'>Place Order</legend>
											                        <div class='row'>
											                           <div class='col s4 offset-s1'>
											                               <div class='form-group'>
											                               	<label for='menu'>Menu Item:</label>
											                               	<input type='text' class='form-control green-text' value='" . $column4[$j]["name"] . "' readonly=''/>
											                               </div>
											                               <div class='form-group'>
												                               	<label for='side'>Side:</label>
												                               	<input type='text' id='side" . $column4[$j]['id'] . "' name='side" . $column4[$j]['id'] . "' class='form-control green-text' placeholder='Side' value='" . $column4[$j]['side'] . "'/>
												                               	<input type='hidden' id='price" . $column4[$j]['id'] . "' name='price" . $column4[$j]['id'] . "' value='" . $column4[$j]['price'] . "'/>
												                               	<input type='hidden' id='table" . $column4[$j]['id'] . "' value='" . $subCategory . "'/>
												                               	<input type='hidden' id='id" . $column4[$j]['id'] . "' value='" . $column4[$j]['id'] . "'/>
											                               </div>
											                           </div>
											                           <div class='col s4 offset-s1'>
											                               <div class='quantity'>
											                               		<label for='quantity'>Quantity:</label>
											                               		<input type='number' id='quantity" . $column4[$j]['id'] . "' name='quantity" . $column4[$j]['id'] . "' min='1' class='form-control green-text' onchange='updatePrice" . $column4[$j]['id'] . "()' value='1' required=''/>
											                               </div>
											                               <div class='form-group'>
											                               		<label for='remarks'>Remarks:</label>
											                               		<textarea name='remarks" . $column4[$j]['id'] . "' label='remarks' class='form-control green-text' id='remarks" . $column4[$j]['id'] . "'></textarea>
											                               </div>
											                               <div class='form-group'>
											                               		<div style='text-align: center;font-size: 15px;' class='blue-text'>
											                               			Total Amount(GH&cent;): <span id='totalAmount" . $column4[$j]['id'] . "' class='blue-text' style='font-size: 20px;'>" . $this->formatNumber($column4[$j]['price']) . "</span>
											                               		</div>
											                               </div>
											                           </div>
											                         </div>
											                         <div class='row'>
											                           	<div style='text-align: center;'>
											                           		<button type='button' onclick='addList" . $column4[$j]['id'] . "()' name='addListBtn' class='btn btn-xs btn-success'> Add to List</button>
											                           	</div>
											                         </div>
											                     </form>
											    </div>
											</div>
											</div>";
                    ### javascript code
                    $data .= "<script>
												function updatePrice" . $column4[$j]['id'] . "(){
													var currentPrice = document.getElementById('price" . $column4[$j]['id'] . "').value;
													var quantity = document.getElementById('quantity" . $column4[$j]['id'] . "').value;
													var totalAmount = parseFloat(currentPrice) * parseFloat(quantity);
													document.getElementById('totalAmount" . $column4[$j]['id'] . "').innerHTML= roundNumber(totalAmount,2) ;
												}

												function addList" . $column4[$j]['id'] . "(){
													var category = '" . $subCategory . "';
													var side = $('#side" . $column4[$j]['id'] . "').val();
													var remarks = $('#remarks" . $column4[$j]['id'] . "').val();
													var id = $('#id" . $column4[$j]['id'] . "').val();
													var quantity = $('#quantity" . $column4[$j]['id'] . "').val();
													var price = $('#price".$column4[$j]['id']."').val();
													//sending data
													$.post('ajax.php',{'newOrder':'y','category':category,'side':side,'remarks':remarks,'id':id,'quantity':quantity, 'price':price},function(data){
														if(parseInt(data) == 1){
															displayMessage('Order placed..',1);
															//close modal
															$('#add" . $column4[$j]['id'] . "').addClass('modal-action modal-close waves-effect');
															$('#add" . $column4[$j]['id'] . "').modal().hide();
															window.location.assign('" . $_SESSION['currentUrl'] . "');
														}else{
															displayMessage('Process failed.. Try again!!',0);
														}
													});
												}
											</script>";
                }
            }


            $data .= "	</ul>
						</div>";
        }
        echo $data;
    }

    function addOrder()
    {
        $table = $this->sanitize($_POST['category']);
        $side = $this->sanitize($_POST['side']);
        $categoryId = $this->sanitize($_POST['id']);
        $remarks = $this->sanitize($_POST['remarks']);
        $price = $this->sanitize($_POST['price']);
        $pid = $this->sanitize($_SESSION['stratekuser']);
        $tn = $this->genTransactionId();
        $billingParamsCategories = $_SESSION['billingParamsCategories'];
        $quantity = $this->sanitize($_POST['quantity']);
        $status = 1;

        if (isset($_SESSION['tidEdit'])) {
            $status = 1;
        }

        if ($table == "food_subcategory") {
            $sql = "insert into orders(tid,food_subcategory,side,remarks,pid,billing_params_categories,quantity,status,price) values(?,?,?,?,?,?,?,?,?)";
        } else {
            $sql = "insert into orders(tid,drinks_subcategory,side,remarks,pid,billing_params_categories,quantity,status,price) values(?,?,?,?,?,?,?,?,?)";
        }

        //checking if this is a new order or the same order
        if (isset($_SESSION['tidEdit'])) {
            $query = "select * from orders where billing_params_categories=? and status=1 and pending=1 and bill_process=0 limit 1";
        } else {
            $query = "select * from orders where billing_params_categories=? and status=1 and pending=1 and bill_process=0 limit 1";
        }

        $res = $this->con->prepare($query);
        $res->execute(array($billingParamsCategories));
        if ($res->rowCount() >= 1) {
            //current order
            $tn = $res->fetch();
            $tn = $tn[1];
        }

        $result = $this->con->prepare($sql);
        if ($result->execute(array($tn, $categoryId, $side, $remarks, $pid, $billingParamsCategories, $quantity, $status, $price))) {
            echo 1;
        } else {
            echo 0;
        }
    }

    function updateOrder()
    {
        /*
			{'updateOrder':'y','side':side,'remarks':remarks,'id':id,'quantity':quantity}
			*/
        $side = $this->sanitize($_POST['side']);
        $remarks = $this->sanitize($_POST['remarks']);
        $id = $this->sanitize($_POST['id']);
        $quantity = $this->sanitize($_POST['quantity']);
        $sql = "update orders set side=?,remarks=?,quantity=? where id=?";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($side, $remarks, $quantity, $id))) {
            echo 1;
        } else {
            echo 0;
        }
    }

    function genTransactionId()
    {
        return strtoupper("TN000" . ucwords(substr(md5(uniqid(mt_rand(), true)), 0, 8)));
    }

    function loadCurrentOrders()
    {
        $categoryId = $this->sanitize($_POST['categoryId']);
        $billingParamsCategories = $this->sanitize($_POST['billingParamsCategories']);

        $data = "<div class='col s12 m8 offset-m2 l9 offset-l3'>";
        $data .= "<div class='submitters'>
                  <div class='input-field'>
                  	<div class='col s2 no-padding'><span class='btn blue white-text width-100 custom-blue bold-600'>&nbsp;</span></div>
                    <div class='col s8 no-padding'>
                      <input type='text' id='grandTotal' class='default-to-generic bold-700 center-align' value='GRAND TOTAL  GHC 25' disabled>
                    </div>
                    <div class='col s2 no-padding'><span class='btn blue white-text width-100 custom-blue bold-600'>&nbsp;</span></div>
                  </div>
                </div>

                <div class='summary'>
                  <div style='width: 100%; overflow-x: auto;'>
                    <div class='table-responsive'>
                      <table class='bordered highlight centered'>
                        <thead class='green'>
                          <tr class='white-text'>
                            <th width='20%'>ITEM</th>
                            <th width='15%'>SIDE</th>
                            <th width='10%'>PRICE</th>
                            <th width='7%'>QTY</th>
                            <th width='8%'>TOTAL</th>
                            <th width='30%'>REMARKS</th>
                            <th width='10%'></th>
                          </tr>
                        </thead>

                        <tbody>";


        //getting data
        if (isset($_SESSION['tidEdit'])) {
            $sql = "select * from orders where billing_params_categories=? and status=1 and pending=1 and bill_process=0 order by date desc";
        } else {
            $sql = "select * from orders where billing_params_categories=? and status=1 and pending=1 and bill_process=0 order by date desc";
        }

        $result = $this->con->prepare($sql);
        $result->bindParam("s", $billingParamsCategories);
        $result->execute(array($billingParamsCategories));
        $totalAmount = 0.0;
        $number = $result->rowCount();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $table = null;

            if ($row['food_subcategory'] == 0) {
                $itemDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } elseif ($row['drinks_subcategory'] == 0) {
                $itemDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }

            $total = $this->formatNumber($row['price'] * $row['quantity']);
            $totalAmount += $total;
            $data .= "<tr><td>" . $itemDetails[1] . "</td><td>" . $row['side'] . "</td><td>" . $this->formatNumber($row['price']) . "</td><td>" . $row['quantity'] . "</td><td>" . $total . "</td><td>" . $row['remarks'] . "</td><td>";

            $data .= "<button type='button' href='#edit" . $row['id'] . "' class='btn blue waves-effect waves-light custom-blue modal-trigger' style='height: 20px; line-height: 16px;'>&nbsp;&nbsp;&nbsp;Edit&nbsp;&nbsp;</button>";
            $data .= "<button type='button' onclick=\"deleteReq('" . $row['id'] . "','orders','" . $_SESSION['currentUrl'] . "')\" class='btn red waves-effect waves-light' style='height: 20px; line-height: 16px;'>Delete</button>";

            $data .= "</td></tr>";

            //adding the modal
            $data .= "<div id='edit" . $row['id'] . "' class='modal'>
											<div class='modal-content no-padding'>
											  <nav class='green'>
											    <div class='nav-wrapper'>
											      <a href='#!' class='brand-logo left' style='font-size: 25px;'>Edit Order Placed</a>
											      <ul class='right'>
											        <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
											      </ul>
											    </div>
											    </nav>
											    <div class='row'>
											        <form class='form' method='post' action='#'>
											                        <legend style='text-align: center; font-size: 20px;' class='blue-text'> Edit Order Placed</legend>
											                        <div class='row'>
											                           <div class='col s4 offset-s1'>
											                               <div class='form-group'>
											                               	<label for='menu'>Menu Item:</label>
											                               	<input type='text' class='form-control green-text' value='" . $itemDetails[1] . "' readonly=''/>
											                               </div>
											                               <div class='form-group'>
												                               	<label for='side'>Side:</label>
												                               	<input type='text' id='side" . $row['id'] . "' name='side" . $row['id'] . "' class='form-control green-text' placeholder='Side' value='" . $row['side'] . "'/>
												                               	<input type='hidden' id='price" . $row['id'] . "' name='price" . $row['id'] . "' value='" . $row['price'] . "'/>
												                               	<input type='hidden' id='table' value=''/>
												                               	<input type='hidden' id='id" . $row['id'] . "' value='" . $row['id'] . "'/>
											                               </div>
											                           </div>
											                           <div class='col s4 offset-s1'>
											                               <div class='quantity'>
											                               		<label for='quantity'>Quantity:</label>
											                               		<input type='number' id='quantity" . $row['id'] . "' name='quantity" . $row['id'] . "' min='1' class='form-control green-text' onchange='updatePriceList" . $row['id'] . "()' value='" . $row['quantity'] . "' required=''/>
											                               </div>
											                               <div class='form-group'>
											                               		<label for='remarks'>Remarks:</label>
											                               		<textarea name='remarks" . $row['id'] . "' label='remarks' class='form-control green-text' id='remarks" . $row['id'] . "'>" . $row['remarks'] . "</textarea>
											                               </div>
											                               <div class='form-group'>
											                               		<div style='text-align: center;font-size: 15px;' class='blue-text'>
											                               			Total Amount(GH&cent;): <span id='totalAmount" . $row['id'] . "' class='blue-text' style='font-size: 20px;'>" . $this->formatNumber($total) . "</span>
											                               		</div>
											                               </div>
											                           </div>
											                         </div>
											                         <div class='row'>
											                           	<div style='text-align: center;'>
											                           		<button type='button' onclick='updateList2" . $row['id'] . "()' name='addListBtn' class='btn btn-xs btn-success'> Update Order Details</button>
											                           	</div>
											                         </div>
											                     </form>
											    </div>
											</div>
											</div>";
            ### javascript code
            $data .= "<script>
												function updatePriceList" . $row['id'] . "(){
													var currentPrice = document.getElementById('price" . $row['id'] . "').value;
													var quantity = document.getElementById('quantity" . $row['id'] . "').value;
													var totalAmount = parseFloat(currentPrice) * parseFloat(quantity);
													document.getElementById('totalAmount" . $row['id'] . "').innerHTML= roundNumber(totalAmount,2) ;
												}

												function updateList2" . $row['id'] . "(){
													var side = $('#side" . $row['id'] . "').val();
													var remarks = $('#remarks" . $row['id'] . "').val();
													var id = $('#id" . $row['id'] . "').val();
													var quantity = $('#quantity" . $row['id'] . "').val();
													//sending data
													$.post('ajax.php',{'updateOrder':'y','side':side,'remarks':remarks,'id':id,'quantity':quantity},function(data){
														if(parseInt(data) == 1){
															displayMessage('Order details updated..',1);
															//close modal
															$('#edit" . $row['id'] . "').addClass('modal-action modal-close waves-effect');
															$('#edit" . $row['id'] . "').modal().hide();
															window.location.assign('?newOrder&proceed');
														}else{
															displayMessage('Process failed.. Try again!!',0);
														}
													});
												}
											</script>";
        }

        $data .= "</tbody>
                      </table>
                    </div>
                  </div>";

        //getting pid information and passing it to js function
        $query = "select tid from orders where billing_params_categories=? and pending=1 and bill_process=0";
        $res = $this->con->prepare($query);
        $res->execute(array($_SESSION['billingParamsCategories']));
        $currentTid = $res->fetch();
        $currentTid = $currentTid[0];
        //save button
        $data .= "<div class='row'>
                      <div class='col s4'>
                        <div class='input-field right-align'>
                          &nbsp;
                        </div>
                      </div>
                      <div class='col s4'>
                        <div class='input-field center-align'>
                          <button type='button' onclick=\"confirm('Complete Order placement?','" . $currentTid . "','orders','index.php?existingOrder')\" class='btn green waves-effect waves-light' ";
        if ($number < 1) {
            $data .= "disabled='disabled'";

            // table should be available
            $newquery = "update billing_params_categories set status=1 where id=?";
            $myResult = $this->con->prepare($newquery);
            $myResult->execute(array($_SESSION['billingParamsCategories']));
        }else{
            // table should be unavailable
            $newquery = "update billing_params_categories set status=0 where id=?";
            $myResult = $this->con->prepare($newquery);
            $myResult->execute(array($_SESSION['billingParamsCategories']));
        }

        $data .= ">SAVE</button>
                        </div>
                      </div>
                      <div class='col s4'>
                        <div class='input-field left-align'>
                          &nbsp;
                        </div>
                      </div>
                  </div>
                </div>";

        $data .= "</div>";
        $data .= "<script>
              			//updating total Amount
              			$('#grandTotal').attr('value','GRAND TOTAL  GHC " . $this->formatNumber($totalAmount) . "');
              		</script>";
        echo $data;
    }

    function genCurrentOrderTid()
    {
        $_SESSION['tid'] = $this->genTransactionId();
    }

    function genLoadOrdersFunction()
    {
        if (isset($_GET['food'])) {
            $id = $this->sanitize($_GET['food']);
        } elseif (isset($_GET['drinks'])) {
            $id = $this->sanitize($_GET['drinks']);
        } else {
            //load default
            $sql = "select id from food where status=1 order by name limit 1";
            $result = $this->con->query($sql);
            $id = $result->fetch();
            $id = $id[0]; //got id
        }

        echo "<script>
						function loadItems(){
							$.post('ajax.php',{'loadCurrentOrders':'y','billingParamsCategories':'" . $_SESSION['billingParamsCategories'] . "','categoryId':'" . $id . "'},function(data){
								$('#loadItems').html(data);
							});
						}
						loadItems();
					</script>";
    }

    function getPrintWaiterUrl()
    {
        $url = $_SERVER['REQUEST_URI'];
        $url = explode("?", $url);
        $url = $url[0] . "/../cms/print.php";
        return "http://" . $_SERVER['HTTP_HOST'] . $url;
    }


    function getPrintAdminUrl()
    {
        $url = $_SERVER['REQUEST_URI'];
        $url = explode("?", $url);
        $url = $url[0] . "/../print.php";
        return "http://" . $_SERVER['HTTP_HOST'] . $url;
    }

    function previewExistingOrders()
    {
        //setting existingOrders sesssion array
        $_SESSION['existingOrders'] = array();

        $url = $this->getPrintWaiterUrl() . "?report&existingOrders";

        $sql = "select distinct tid,pid,billing_params_categories from orders where status=1 and bill_process=0 and pending=1 order by date desc";
        $result = $this->con->query($sql);
        $data = "<div class='row' style='margin-top: 15px;'>
		          <div class='col m10 l10 s12 offset-l1 offset-m1'>
		            <div class='table-responsive'>
		              <table>
		                <thead>
		                  <tr>
		                    <th></th>
		                    <th>ORDER #</th>
		                    <th>BILL TO</th>
		                    <th>WAITER</th>
		                    <th></th>
		                  </tr>
		                </thead>

		                <tbody>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tid = strtoupper($row['tid']);
            $pid = $row['pid'];
            $billing_params_categories = $row['billing_params_categories'];
            //getting user details
            $userDetails = $this->getFullDetailsId($pid, "login");
            //getting billing_params
            $subParam = $this->getFullDetailsId($billing_params_categories, "billing_params_categories");
            $mainParam = $this->getFullDetailsId($subParam[2], "billing_params");
            $userDetails2 = $this->getFullDetailsId($_SESSION['stratekuser'], "login");
            $data .= "<tr>
                    <td>";
            if($userDetails2[6] == 0){
                $data .= "<input type='checkbox' id='" . $tid . "'>
                      <label for='" . $tid . "'></label>";
            }
            $data.="</td>
                      <td>" . $tid . "</td>
                      <td>" . $mainParam[1] . " - " . $subParam[1] . "</td>
                      <td>" . $userDetails[3] . "</td>
                      <td>
                            <div style='text-align: center;'>
                                <button type='button' onclick=\"printDocument('" . $url . "=" . $tid . "')\" class='btn' style='border-radius: 50px; -webkit-border-radius: 50px; -moz-border-radius: 50px;'><i class='material-icons'>print</i></button>
                                 <button type='button' onclick=\"splitMerge".$tid."('?splitBill')\" class='btn cyan' style='border-radius: 50px; -webkit-border-radius: 50px; -moz-border-radius: 50px;'><i class='material-icons'>pie_chart</i></button>
                                  <button type='button' onclick=\"splitMerge".$tid."('?mergeBill')\" class='btn orange' style='border-radius: 50px; -webkit-border-radius: 50px; -moz-border-radius: 50px;'><i class='material-icons'>compare_arrows</i></button>
                            </div>
                      </td>
                    </tr>";
            //adding checkbox javascript code
            $data .= "<script>
                 			$('#" . $tid . "').change(function(){
                 				if(this.checked){
                 					$.post('ajax.php',{'addExistingOrder':'y','tid':'" . $tid . "'},function(data){
                 						//console.log(data);
                 					});
                 				}else{
                 					$.post('ajax.php',{'removeExistingOrder':'y','tid':'" . $tid . "'},function(data){
                 						//console.log(data);
                 					});
                 				}
                 			});

                            function splitMerge".$tid."(location){
                                $.post('ajax.php',{'removeExistingOrder':'y','tid':'" . $tid . "'},function(data){
                                   $.post('ajax.php',{'addExistingOrder':'y','tid':'" . $tid . "'},function(data){
                                        window.location.assign(location);
                                    });
                                });
                            }
                 		</script>";
        }

        //delete ja function
        $data .= "<script>
						function deleteExistingOrder(vname,message){
							alertify.confirm(vname,message,function(e){
						      if(e){
						        $.post('ajax.php',{'deleteExistingOrder':'y'},function(data){
						        if(data==1){
						          //delete
						        	loadItems();
						        }else{
						          //alert(data);
						          loadItems();
						        }

						        });
						      }else{
						      	loadItems();
						      }
						    },function(e){
						      loadItems();
						    });
						}

						function editOrder(){
							$.post('ajax.php',{'verifyExistingOrder':'y'},function(data){
								if(data==1){
									//perform request to redirect to edit page...
									$.post('ajax.php',{'editExistingOrder':'y'},function(data){
										$.post('ajax.php',{'updateExistingOrder':'y'},function(data){

										});
										window.location.assign(data);
									});
								}else{
									displayMessage('Please select only one..!!!',0);
								}
							});
						}

						function processOrder(){
							$.post('ajax.php',{'verifyExistingOrder':'y'},function(data){
								if(data==1){
									//perform request to process bill page ...
									$.post('ajax.php',{'processOrder':'y'},function(data){
										window.location.assign(data);
									});
								}else{
									displayMessage('Please select only one..!!!',0);
								}
							});

						}
					</script>";

        $data .= " </tbody>
			              </table>
			            </div>

			            <form action='#' method='post'>
			              <div class='row'>
			                <div class='col s4'>
			                  <div class='input-field center-align'>
			                    <button type='button' id='processOrderBtn' onclick=\"processOrder()\" class='waves-effect waves-light btn blue'>
			                      <i class='material-icons left'>cached</i>Process
			                    </button>
			                  </div>
							</div>";
        //checkig if user is authorised
        $userDetails = $this->getFullDetailsId($_SESSION['stratekuser'], "login");
        if ($userDetails[6] == 0) {
            $data .= "<div class='col s4'>
			                  <div class='input-field center-align'>
			                    <button type='button' onclick=\"editOrder()\" class='waves-effect waves-light btn blue'>
			                      <i class='material-icons left'>border_color</i>Edit
			                    </button>
			                  </div>
			                </div>
			                <div class='col s4'>
			                  <div class='input-field center-align'>
			                    <button type='button' class='waves-effect waves-light btn red' onclick=\"deleteExistingOrder('Gold Coast Restaurant','Delete Order(s)?')\">
			                      <i class='material-icons left'>delete</i>Delete
			                    </button>
			                  </div>
							</div>";
        }else{
            $data .= "<script>
                        $('#processOrderBtn').hide();
                    </script>";
        }



        $data .= "      </div>
			            </form>
			          </div>
			        </div>
			        ";
        echo $data;
    }


    function addExistingOrder($tid)
    {
        $tid = $this->sanitize($tid);
        if (sizeof($_SESSION['existingOrders']) == 0) {
            $_SESSION['existingOrders'][0] = $tid;
        } else {
            //checking current number
            $count = sizeof($_SESSION['existingOrders']);
            if (!in_array($tid, $_SESSION['existingOrders'])) {
                $_SESSION['existingOrders'][$count] = $tid;
                //echo 1;
                //print_r($_SESSION['existingOrders']);
            } else {
                //echo  1;
                //print_r($_SESSION['existingOrders']);
            }
        }
    }

    function addExistingOrder1()
    {
        $id = $this->sanitize($_POST['id']);
        if (sizeof($_SESSION['existingOrders1']) == 0) {
            $_SESSION['existingOrders1'][0] = $id;
            //print_r($_SESSION['existingOrders1']);
        } else {
            //checking current number
            $count = sizeof($_SESSION['existingOrders1']);
            if (!in_array($id, $_SESSION['existingOrders1'])) {
                $_SESSION['existingOrders1'][$count] = $id;
                //echo 1;
                //print_r($_SESSION['existingOrders1']);
            } else {
                //echo  1;
                //print_r($_SESSION['existingOrders1']);
            }
        }
    }

    function addExistingOrder2()
    {
        $id = $this->sanitize($_POST['id']);
        if (sizeof($_SESSION['existingOrders2']) == 0) {
            $_SESSION['existingOrders2'][0] = $id;
            print_r($_SESSION['existingOrders2']);
        } else {
            //checking current number
            $count = sizeof($_SESSION['existingOrders2']);
            if (!in_array($id, $_SESSION['existingOrders2'])) {
                $_SESSION['existingOrders2'][$count] = $id;
                //echo 1;
                //print_r($_SESSION['existingOrders2']);
            } else {
                //echo  1;
                //print_r($_SESSION['existingOrders2']);
            }
        }
    }

    function removeExistingOrder($tid)
    {
        $tid = $this->sanitize($tid);
        if (in_array($tid, $_SESSION['existingOrders'])) {
            $_SESSION['existingOrders'] = array_merge(array_diff($_SESSION['existingOrders'], array($tid)));
            //echo 1;
            //print_r($_SESSION['existingOrders']);
        } else {
            //echo 1;
            //print_r($_SESSION['existingOrders']);
        }
    }

    function removeExistingOrder1()
    {
        $id = $this->sanitize($_POST['id']);
        if (in_array($id, $_SESSION['existingOrders1'])) {
            $_SESSION['existingOrders1'] = array_merge(array_diff($_SESSION['existingOrders1'], array($id)));
            //echo 1;
            //print_r($_SESSION['existingOrders1']);
        } else {
            //echo 1;
            //print_r($_SESSION['existingOrders1']);
        }
    }

    function removeExistingOrder2()
    {
        $id = $this->sanitize($_POST['id']);
        if (in_array($id, $_SESSION['existingOrders2'])) {
            $_SESSION['existingOrders2'] = array_merge(array_diff($_SESSION['existingOrders2'], array($id)));
            //echo 1;
            //print_r($_SESSION['existingOrders2']);
        } else {
            //echo 1;
            //print_r($_SESSION['existingOrders2']);
        }
    }

    function deleteExistingOrder()
    {
        for ($i = 0; $i < sizeof($_SESSION['existingOrders']); $i++) {
            $tid = $_SESSION['existingOrders'][$i];
            
            //activate billing_params_categories
            $query = "select billing_params_categories from orders where tid=? limit 1";
            $res = $this->con->prepare($query);
            $res->execute(array($tid));
            $billing_params_categories = $res->fetch();

            $sql = "delete from orders where tid=?";
            $result = $this->con->prepare($sql);
            $result->execute(array($tid));

            $query1 = "update billing_params_categories set status=1 where id=?";
            $res1 = $this->con->prepare($query1);
            $res1->execute(array($billing_params_categories[0]));
        }
        echo 1;
    }

    function verifyExistingOrder()
    {
        $count = sizeof($_SESSION['existingOrders']);
        if ($count != 1) {
            echo 0;
        } else {
            echo 1;
        }
    }

    function updateExistingOrder()
    {
        $tid = $this->sanitize($_SESSION['existingOrders'][0]);
        $_SESSION['tidEdit'] = $tid;
        $sql = "update orders set status=1 where tid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
    }

    function editExistingOrder()
    {
        $tid = $this->sanitize($_SESSION['existingOrders'][0]);
        $sql = "update orders set status=0 where tid=?";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($tid))) {
            //generating redirect url
            $query = "select billing_params_categories from orders where tid=? limit 1";
            $res = $this->con->prepare($query);
            $res->execute(array($tid));
            $billing_params_categories = $res->fetch();
            $_SESSION['useredit'] = $billing_params_categories[0]; //setting session value
            echo "index.php?newOrder&proceed";
        } else {
            echo 0;
        }
    }

    function getTaxServiceCharge()
    {
        $sql = "select * from tax_service_charge limit 1";
        $result = $this->con->query($sql);
        if ($result->rowCount() != 1) {
            //generate dummy data
            $query = "insert into tax_service_charge(service_charge,vat,nhil,gtbl) values(0.0,0.0,0.0,0.0)";
            $res = $this->con->query($query);
            //getting data
            $res1 = $this->con->query("select * from tax_service_charge limit 1");
            return $res1->fetch();
        } else {
            return $result->fetch();
        }
    }

    function getProcessedBillTaxServiceCharge($tid){
        $tid = $this->sanitize($tid);
        $sql = "select discount,service_charge,vat,nhil,gtbl, mode_of_payment from processed_bill where tid=? limit 1";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        return $result->fetch();
    }

    function updateTaxServiceCharges()
    {
        $id = $this->sanitize($_POST['id']);
        $service_charge = $this->sanitize($_POST['service_charge']);
        $nhil = $this->sanitize($_POST['nhil']);
        $vat = $this->sanitize($_POST['vat']);
        $gtbl = $this->sanitize($_POST['gtbl']);
        $sql = "update tax_service_charge set service_charge=?, nhil=?, vat=?, gtbl=? where id=?";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($service_charge, $nhil, $vat, $gtbl, $id))) {
            $this->displayMsg("Tax/Service Charge updated..", 1);
        } else {
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?tax");
    }

    function processOrder()
    {
        $_SESSION['billProcess'] = $this->sanitize($_SESSION['existingOrders'][0]);
        echo "index.php?processBill";
    }

    function setDiscount($discount)
    {
        $discount = $this->sanitize($discount);
        $_SESSION['discount'] = $discount;
        //echo $_SESSION['discount'];
    }

    function genInvoice(){
        return "IV".date("Ymdhis");
    }

    function completeProcessBill()
    {
        $tax_service_charge = $this->getTaxServiceCharge();
        $discount = $this->sanitize($_POST['discount']);
        $tid = $this->sanitize($_POST['tid']);
        $amount = floatval($this->sanitize($_POST['amount']));
        $service_charge = $tax_service_charge[1];
        $vat = $tax_service_charge[2];
        $nhil = $tax_service_charge[3];
        $gtbl = $tax_service_charge[4];
        $pid = $this->sanitize($_SESSION['stratekuser']);
        $invoice = $this->genInvoice();
        $mode_of_payment = $this->sanitize($_POST['mode_of_payment']);
        $sql = "insert into processed_bill(tid,discount,service_charge,vat,nhil,gtbl,pid,amount,invoice,mode_of_payment) values(?,?,?,?,?,?,?,?,?,?)";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($tid, $discount, $service_charge, $vat, $nhil, $gtbl, $pid, $amount, $invoice, $mode_of_payment))) {
            //updating orders
            $query = "update orders set pending=0, bill_process=1 where tid=?";
            $res = $this->con->prepare($query);
            if ($res->execute(array($tid))) {
                //reactivating billing_params_categories
                $query1 = "select billing_params_categories from orders where tid=? limit 1";
                $result1 = $this->con->prepare($query1);
                $result1->execute(array($tid));
                $billing_params_categories = $result1->fetch();

                //checking if it's the only billing_params_categories
                $myQuery = "select * from orders where billing_params_categories=? and pending=1 and status=1 and bill_process=0";
                $myResult = $this->con->prepare($myQuery);
                $myResult->execute(array($billing_params_categories[0]));
                if($myResult->rowCount() < 1){
                    $query2 = "update billing_params_categories set status=1 where id=?";
                    $result2 = $this->con->prepare($query2);
                    $result2->execute(array($billing_params_categories[0]));
                }
                unset($_SESSION['existingOrders']);
                echo 1;
            } else {
                echo 0;
            }
        } else {
            echo 0;
        }
    }

    function billProcess()
    {
        $totalAmount = 0.0;
        $tax_service_charge = $this->getTaxServiceCharge();
        $service_charge = $this->formatNumber($tax_service_charge[1]);
        $vat = $this->formatNumber($tax_service_charge[2]);
        $nhil = $this->formatNumber($tax_service_charge[3]);
        $gtbl = $this->formatNumber($tax_service_charge[4]);
        $tid = $this->sanitize($_SESSION['existingOrders'][0]);

        $tableDetails = $this->getTableDetailsTid($tid);
        $data = "<div class='row'>
					  <div class='table-responsive'>
					    <table class='centered bordered'>
					      <caption class='blue lighten-3 white-text'>
					        <p class='flow-text bold-300'>ORDER " . $tid . " | " . $tableDetails . "</p>
					      </caption>

					      <thead>
					        <tr>
					          <th>ITEMS</th>
					          <th>UNIT PRICE</th>
					          <th>QUANTITY</th>
					          <th>TOTAL</th>
					          <th>REMARKS</th>
					        </tr>
					      </thead>

					      <tbody>";

        $sql = "select * from orders where tid=? and status=1 and pending=1 and bill_process=0";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['food_subcategory'] == 0) {
                $categoryDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }
            $total = floatval($this->formatNumber($row['price'])) * $row['quantity'];
            $totalAmount += $total;
            $data .= "<tr><td>" . $categoryDetails[1] . "</td><td>" . $this->formatNumber($row['price']) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td>" . $row['remarks'] . "</td></tr>";
        }

        $discount = $this->formatNumber($totalAmount * ($_SESSION['discount']) / 100);
        $orderTotal = $this->formatNumber($totalAmount);
        $subTotal = $this->formatNumber($orderTotal + $service_charge - $discount);
        $vat = $this->formatNumber(($vat / 100) * $subTotal);
        $nhil = $this->formatNumber(($nhil / 100) * $subTotal);
        $gtbl = $this->formatNumber(($gtbl / 100) * $subTotal);
        $grandTotal = $this->formatNumber($subTotal + $vat + $nhil + $gtbl);
        //process discount js function
        $data .= "<script>
						function processDiscount(){
							var discount = $('#discount').val();
							$.post('ajax.php',{'setDiscount':discount},function(data){
								//console.log(data);
							});
							loadItems();
						}

						function processBill(){
							//console.log('working');
							var mode_of_payment = $('#mode_of_payment').val();
							if(mode_of_payment == 0){
								alertify.alert('Gold Coast Restaurant','Please select a mode of payment!!!');
								return;
							}
							alertify.confirm('Gold Coast Restaurant','Process bill?',function(e){
								if(e){
									var tid = '" . $_SESSION['existingOrders'][0] . "';
									var discount = $('#discount').val();
									//sending data to server
									$.post('ajax.php',{'completeProcessBill':'y','tid':tid,'discount':discount,'amount':'" . $totalAmount . "','mode_of_payment':mode_of_payment},function(data){
										if(data == 1){
											displayMessage('Bill Processing Complete..',1);
											window.location.assign('?existingOrder');
										}else{
											displayMessage('Process failed..',0);
											loadItems();
										}
									});
								}
							},function(e){
								//error
							});
						}
					</script>";

        $data .= "      </tbody>
					    </table>
					  </div>


					  <div class='blue lighten-3 white-text margin-t padding-in'>
					    <p class='center-align'>MODE OF PAYMENT</p>
					  </div>
					  <div class='row'>
					  	<div class='col m10 l6 s12 offset-l3 offset-m1'>
					  		<input type='hidden' id='mode_of_payment' value='0'/>
				            <div class='table-responsive'>
				              <table>
				                <thead>
				                  <tr>
				                    <th>#</th>
				                    <th>MODE OF PAYMENT</th>
				                  </tr>
				                </thead>
				                <tbody>";
		$sql11 = "select * from mode_of_payment";
		$result11 = $this->con->query($sql11);
		$total11 = $result11->rowCount();
		$list = array();
		$count11 = 0;
		while($row = $result11->fetch(PDO::FETCH_ASSOC)){
			$data.="<tr><td><input type='checkbox' id='md".$row['id']."'><label for='md".$row['id']."'></label></td><td>".$row['name']."</td></tr>";
			$list[$count11] = $row['id'];

			//add js code
			$data.="<script>
						$('#md".$row['id']."').change(function(){
							if(this.checked){
								$('#mode_of_payment').attr('value','".$row['id']."');
								uncheckOthers('".$row['id']."');
								";

			$data.="		}else{
								$('#mode_of_payment').attr('value','0');
							}
						});
					</script>";
			$count11++;
		}

		$data.="<script>
					function uncheckOthers(id){
						//console.log('working');";

						for($i = 0; $i < sizeof($list); $i++){
							$data.="
								document.getElementById('md".$list[$i]."').checked = false;
							";
						}
		$data.="			document.getElementById('md'+id+'').checked = true;";
		$data.="		}
				</script>";


		$data.="		        </tbody>
				              </table>
				            </div>
				        </div>
					  </div>


					  <div class='blue lighten-3 white-text margin-t padding-in'>
					    <p class='center-align'>TAX/SERVICE CHARGE</p>
					  </div>

					  <div class='row'>
					    <div class='col s12 m10 offset-m1 no-padding'>
					      <div class='row'>
					        <div class='col s4' style='height: 250px'>
					          <div class='valign-wrapper inherit-height'>
					            <form action='#' method='post'>
					                <p for='' class='bold-800 grey-text text-darken-2'><small>DISCOUNT</small></p>

					                <div class='row negative-margins'>
					                  <div class='col s12'>
					                      <input class='with-gap' name='discount' type='radio' id='percentage' checked>
					                      <label for='percentage'>Percentage</label>
					                  </div>
					                  <!--<div class='col s6'>
					                      <input class='with-gap' name='discount' type='radio' id='amount'>
					                      <label for='amount'>Amount</label>
					                  </div>-->

					                  <div class='input-field'>
					                    <input type='number' step='any' id='discount' value='" . $this->formatNumber($_SESSION['discount']) . "' min='0' max='100' class='validate center-align'>
					                  </div>

					                  <div class='input-field center-align'>
					                    <button type='button' name='process' onclick=\"processDiscount()\" class='btn blue waves-effect waves-light'>
					                      <i class='material-icons left'>cached</i> Process Discount
					                    </button>
					                  </div>
					                </div>
					            </form>
					          </div>
					        </div>
					        <div class='col s8'>
					          <table class='bold-600 remove-excess-padding'>
					            <thead>
					              <th width='50%' class='right-align'>ORDER TOTAL</th>
					              <th width='50%' class='center-align'>" . $this->formatNumber($orderTotal) . "</th>
					            </thead>

					            <tbody>
					              <tr>
					                <td class='right-align'>SERVICE CHARGE</td>
					                <td class='center-align'>" .$service_charge. "</td>
					              </tr>
					              <tr>
					                <td class='right-align'>DISCOUNT</td>
					                <td class='center-align'>" .$discount. "</td>
					              </tr>
					              <tr>
					                <td class='right-align'>SUB TOTAL</td>
					                <td class='center-align'>" .$subTotal. "</td>
					              </tr>
					              <tr>
					                <td class='right-align'>VAT AMOUNT</td>
					                <td class='center-align'>" .$vat. "</td>
					              </tr>
					              <tr>
					                <td class='right-align'>NHIL AMOUNT</td>
					                <td class='center-align'>" .$nhil. "</td>
					              </tr>
					              <tr>
					                <td class='right-align'>GTBL AMOUNT</td>
					                <td class='center-align'>" .$gtbl. "</td>
					              </tr>
					            </tbody>

					            <tfoot>
					              <tr>
					                <td class='right-align'>GRAND TOTAL</td>
					                <td class='center-align'>" .$grandTotal. "</td>
					              </tr>
					            </tfoot>
					          </table>
					        </div>
					      </div>
					    </div>
					  </div>
					</div>
					<div class='row'>
						<div style='text-align: center;'>
		                    <div class='col s4'>
			                  <div class='input-field center-align'>
			                    <button type='button' onclick=\"processBill()\" class='waves-effect waves-light btn green'>
			                      <i class='material-icons left'>cached</i>Process
			                    </button>
			                  </div>
			                </div>
			                <div class='col s4'>
			                  <div class='input-field center-align'>
			                    <button type='button' onclick=\"window.location.assign('?splitBill')\" class='waves-effect waves-light btn blue'>
			                      <i class='material-icons left'>pie_chart</i>Split Bill
			                    </button>
			                  </div>
			                </div>
			                <div class='col s4'>
			                  <div class='input-field center-align'>
			                    <button type='button' class='waves-effect waves-light btn red' onclick=\"window.location.assign('?mergeBill')\">
			                      <i class='material-icons left'>compare_arrows</i>Merge Bill
			                    </button>
			                  </div>
			                </div>
						</div>
					</div>";
        echo $data;
    }

    function setSplitTid()
    {
        $status = $this->sanitize($_POST['status']);
        $tid = $this->sanitize($_POST['splitTid']);
        if (intval($status == 0)) {
            //deactivate / unset
            unset($_SESSION['splitTid']);
        } else {
            $_SESSION['splitTid'] = $tid;
        }
    }

    function selectSplitTid()
    {
        unset($_SESSION['vtable']);
        $data = "<div class='row no-margin-bottom'>
				    <div class='col s12 m10 offset-m1'>
				        <div class='row no-margin-bottom'>
				          <div class='col s12' style='text-align: center;'>
				            <p class='flow-text center-align no-margin-bottom blue-text bold-600' id='tHeader'>Select order to split to</p>
				          </div>
				      </div>
				      <hr class='seperator'>

				      <div class='inner-content'>

				      </div>
				    </div>
				  </div>";
        $sql = "select distinct tid,pid,billing_params_categories from orders where status=1 and bill_process=0 and pending=1 order by date desc";
        $result = $this->con->query($sql);
        $data .= "<div class='row' style='margin-top: 15px;'>
		          <div class='col m10 l6 s12 offset-l3 offset-m1'>
		            <div class='table-responsive'>
		              <table>
		                <thead>
		                  <tr>
		                    <th></th>
		                    <th>ORDER #</th>
		                    <th>BILL TO</th>
		                    <th>WAITER</th>
		                  </tr>
		                </thead>

		                <tbody>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tid = strtoupper($row['tid']);
            //ignoring current tid
            if ($tid == $_SESSION['existingOrders'][0]) {
                continue;
            }

            $pid = $row['pid'];
            $billing_params_categories = $row['billing_params_categories'];
            //getting user details
            $userDetails = $this->getFullDetailsId($pid, "login");
            //getting billing_params
            $subParam = $this->getFullDetailsId($billing_params_categories, "billing_params_categories");
            $mainParam = $this->getFullDetailsId($subParam[2], "billing_params");
            $data .= "<tr>
                    <td>
                      <input type='checkbox' id='" . $tid . "'>
                      <label for='" . $tid . "'></label>
                    </td>
                      <td>" . $tid . "</td>
                      <td>" . $mainParam[1] . " - " . $subParam[1] . "</td>
                      <td>" . $userDetails[3] . "</td>
                    </tr>";
            //adding checkbox javascript code
            $data .= "<script>
                 			$('#" . $tid . "').change(function(){
                 				if(this.checked){
                 					$.post('ajax.php',{'splitTid':'" . $tid . "','status':'1'},function(data){

                 					});
                 				}else{
                 					$.post('ajax.php',{'splitTid':'" . $tid . "','status':'0'},function(data){
                 						//console.log(data);
                 					});
                 				}
                 			});
                 		</script>";
        }

        $data .= " </tbody>
			              </table>
			            </div>";
        //js script to set tid
        $data .="<script>
                    function setVTable(){
                        $.post('ajax.php',{'setVTable':'y'}, function(data){
                            if(data == 1){
                                window.location.assign('?splitBill');
                            }
                        });
                    }
                </script>";
        $data .= "<form action='#' method='post'>
			              <div class='row'>
			              	<div class='col s6'>
			                  <div class='input-field center-align'>
			                    <button type='button' id='proceedBtn' class='waves-effect waves-light btn blue' onclick=\"window.location.assign('?splitBill')\">
			                      <i class='material-icons left'>cached</i>Proceed
			                    </button>
			                  </div>
			                </div>
                            <div class='col s6'>
                              <div class='input-field center-align'>
                                <button type='button' id='virtualBtn' class='waves-effect waves-light btn red' onclick=\"setVTable()\">
                                  <i class='material-icons left'>cached</i>V-Table
                                </button>
                              </div>
                            </div>
			              </div>
			            </form>
			            </div>
			            </div>";
        echo $data;
    }

    function getTableDetailsTid($tid)
    {
        $tid = $this->sanitize($tid);
        $sql = "select * from orders where tid=? limit 1";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        $orderDetails = $result->fetch();
        $subCategory = $this->getFullDetailsId($orderDetails[10], "billing_params_categories");
        $mainCategory = $this->getFullDetailsId($subCategory[2], "billing_params");
        return $mainCategory[1] . "-" . $subCategory[1];
    }

    function getSplitBillOrderQuantityId($id)
    {
        $order_id = $this->sanitize($id);
        $sql = "select quantity from split_bill where order_id=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($order_id));
        $result = $result->fetch();
        return $result[0];
    }


    function moveSplitItem()
    {
        $tid = $this->sanitize($_POST['tid']);
        $ptid = $this->sanitize($_POST['ptid']);
        $order_id = intval($this->sanitize($_POST['order_id']));
        $quantity = $this->sanitize($_POST['quantity']);
        $pid = $this->sanitize($_POST['pid']);
        $sql = "insert into split_bill(tid,ptid,order_id,quantity,pid) values(?,?,?,?,?)";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($tid, $ptid, $order_id, $quantity, $pid))) {
            echo 1;
        } else {
            echo 0;
        }
    }

    function previewSplitBill()
    {
        if (!isset($_SESSION['splitTid'])) {
            $this->selectSplitTid();
            return;
        }
        $totalAmount = 0.0;
        $tid = $this->sanitize($_SESSION['existingOrders'][0]);
        $tableDetailsTid = $this->getTableDetailsTid($tid);

        $ptid = $this->sanitize($_SESSION['splitTid']);
        $ptableDetailsTid = $this->getTableDetailsTid($ptid);

        if(isset($_SESSION['vtable'])){
            $ptableDetailsTid = "";
        }

        $data = "<div class='row no-margin'>
						<div class='col m12 no-padding'>
						  <div class='row no-margin'>
						    <div class='col s6'>
						      <div class='vertical-80'>
						        <p class='flow-text' style='text-align: center;'>ORDER " . $tid . " | " . $tableDetailsTid . "</p>

						        <div class='alterers'>
						          <div class='row no-margin'>
						            <div class='col s6'>
						              <!--<div class='quantity inline-flex'>
						                <span class='no-margin'><b>SPLITS &nbsp; &nbsp;</b></span>
						                <span><i class='material-icons'>remove_circle_outline</i></span>
						                <span>1</span>
						                <span><i class='material-icons'>add_circle_outline</i></span>
						              </div>-->
						            </div>
						            <div class='col s6'>
						              <p class='bold-700 caption no-margin lh1-5'>TOTAL &nbsp; &nbsp; <span id='totalAmount'>0.00</span></p>
						            </div>
						          </div>
						        </div>

						        <div class='table-responsive'>
						          <table class='remove-excess-padding'>
						            <thead>
						              <tr>
						                <th>ITEMS</th>
						                <th>PRICE</th>
						                <th>QTY</th>
						                <th>TOTAL</th>
						              </tr>
						            </thead>
						            <tbody>";

        //getting all data
        $sql = "select * from orders where tid=? and status=1 and pending=1 and bill_process=0";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $splitBillQuantity = $this->getSplitBillOrderQuantityId($row['id']);
            if ($splitBillQuantity == $row['quantity']) {
                continue;
            } else {
                $row['quantity'] -= intval($splitBillQuantity);
            }

            if ($row['food_subcategory'] == 0) {
                $categoryDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }
            $total = floatval($this->formatNumber($row['price'])) * $row['quantity'];
            $totalAmount += $total;
            $data .= "<tr><td><a href='#split" . $row['id'] . "' class='modal-trigger'>" . $categoryDetails[1] . "</a></td><td>" . $this->formatNumber($row['price']) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td>" . $row['remarks'] . "</td></tr>";

            //modal
            $data .= "<div id='split" . $row['id'] . "' class='modal'>
								<div class='modal-content no-padding'>
								  <nav class='green'>
								    <div class='nav-wrapper'>
								      <a href='#!' class='brand-logo left' style='font-size: 25px;'>Split Bill</a>
								      <ul class='right'>
								        <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
								      </ul>
								    </div>
								    </nav>
								    <div class='row'>
								        <form class='form' method='post' action='#' id='profileForm'>

									          <div class='col s10 offset-s1'>
												<div class='input-field'>
									  				<input id='search' type='search' value='" . $categoryDetails[1] . "' class='green-text' readonly='' required>
									  				<label class='label-icon' for='search'>
									  					<i class='material-icons green-text'>description</i>
									  				</label>
												</div>

								          <div class='input-field'>";

            //peforming test on quantity
            if ($row['quantity'] == 1) {
                //function for move item
                $data .= "<script>
									function moveItem" . $row['id'] . "(){
										alertify.confirm('Gold Coast Restaurant','Move Item?',function(e){
											if(e){
												//sending data to api
												$.post('ajax.php',{'moveSplitItem':'y','tid':'" . $tid . "','ptid':'" . $ptid . "','order_id':'" . $row['id'] . "','quantity':'" . $row['quantity'] . "','pid':'" . $_SESSION['stratekuser'] . "'},function(data){
													if(data==1){
														window.location.assign('?splitBill');
													}else{
														displayMessage('Process failed',0);
													}
												});
											}
										},function(e){
											//error
										});
									}
								</script>";


                //display just move button
                $data .= "<div class='row' style='margin: 15px;'>
								<div style='text-align: center;'>
					                    <button type='button' class='waves-effect waves-light btn red' onclick=\"moveItem" . $row['id'] . "()\">
					                      <i class='material-icons left'>forward</i>Move
					                    </button>
								</div>
								</div>";
            } else {
                //adding js script
                $data .= "<script>
									function moveItem2" . $row['id'] . "(){
										var quantity = $('#quantity" . $row['id'] . "').val();
										alertify.confirm('Gold Coast Restaurant','Move Item?',function(e){
											if(e){
												//sending data to api
												$.post('ajax.php',{'moveSplitItem':'y','tid':'" . $tid . "','ptid':'" . $ptid . "','order_id':'" . $row['id'] . "','quantity':quantity,'pid':'" . $_SESSION['stratekuser'] . "'},function(data){
													if(data==1){
														window.location.assign('?splitBill');
													}else{
														displayMessage('Process failed',0);
													}
												});
											}
										},function(e){
											//error
										});
									}
								</script>";

                //provide option to reduce quantity
                $data .= "<div class='row' style='margin: 15px;'>";
                $data .= "<div class='col s3'></div>";
                $data .= "<div class='col s6'>";
                $data .= "<div class='form-group'>
											<label for='quantity'>Select Quantity to move:</label>
											<input type='number' id='quantity" . $row['id'] . "' min='1' max='" . $row['quantity'] . "' name='quantity' value='" . $row['quantity'] . "' class='blue-text form-control' placeholder='Select Quantity'/>
										</div>";
                $data .= "<div class='form-group'>
											<div style='text-align: center;'>
												<div class='input-field center-align'>
								                    <button type='button' onclick=\"moveItem2" . $row['id'] . "()\" class='waves-effect waves-light btn red'>
								                      <i class='material-icons left'>forward</i>Move
								                    </button>
								                </div>
											</div>
										</div>";
                $data .= "</div>";
                $data .= "<div class='col s3'></div>";
                $data .= "</div>";
            }
            $data .= "	          </div>
								        </form>
								    </div>
								</div>
							</div>";
        }

        //updating totalAmount
        $data .= "<script>
							$('#totalAmount').html('" . $this->formatNumber($totalAmount) . "');
							function exitSplitBill(){
								$.post('ajax.php',{'splitTid':'y','status':'0'},function(data){
									window.location.assign('?processBill');
								});
							}

							//saving the entire process
							function saveProcess(){
								alertify.confirm('Gold Coast Restaurant','Save?',function(e){
									if(e){
										$.post('ajax.php',{'saveSplitBillProcess':'y','tid':'" . $tid . "','ptid':'" . $ptid . "'},function(data){
											if(data==1){
												displayMessage('Process Completed..',1);
											}else{
												displayMessage('Process failed..Try again.',0);
											}
												window.location.assign('?processBill');
										});
									}
								},function(e){

								});
							}
						</script>";

        $data .= "</tbody>
						          </table>
						        </div>
						      </div>
						      <div class='vertical-10'>
						        <div class='row no-margin'>
						       
						        </div>
						        <!-- execution buttons -->
						        <div class='row'>
					                <div class='col s6'>
					                  <div class='input-field center-align'>
					                    <button type='button' id='saveBtn' onclick=\"saveProcess()\" class='waves-effect waves-light btn green'>
					                      <i class='material-icons left'>save</i>Save
					                    </button>
					                  </div>
					                </div>
					                <div class='col s6'>
					                  <div class='input-field center-align'>
					                    <button type='button' class='waves-effect waves-light btn red' onclick=\"exitSplitBill()\">
					                      <i class='material-icons left'>close</i>Exit
					                    </button>
					                  </div>
					                </div>
					              </div>
					              <!--end of execution buttons-->
						      </div>
						    </div>

						      <div class='col s6 grey lighten-1 vertical-90 overflow-y'>
						      	<div class='row no-margin'>
						      	 <p class='flow-text' style='text-align: center;'>ORDER " . $ptid . " | " . $ptableDetailsTid . "</p>
						      	</div>
						      	<!--previewing data  -->";

        $data .= $this->previewSideDataSplitBill();

        $data .= "      	<!--end of previewing data -->


						      	
						      </div>

						    </div>
						  </div>

						</div>";
        $number = $this->getSplitBillNumber();
        if ($number < 1) {
            echo "<script>
							$('#saveBtn').attr('disabled','');
						</script>";
        }
        echo $data;
    }

    function previewSideDataSplitBill()
    {
        $tid = $this->sanitize($_SESSION['existingOrders'][0]);
        $ptid = $this->sanitize($_SESSION['splitTid']);
        $sql = "select * from split_bill where tid=? and ptid=? order by date desc";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid, $ptid));
        $data = "<div class='table-responsive'>
						          <table class='remove-excess-padding'>
						            <thead>
						              <tr>
						                <th>ITEMS</th>
						                <th>PRICE</th>
						                <th>QTY</th>
						                <th>TOTAL</th>
						                <th></th>
						              </tr>
						            </thead>
						            <tbody>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $orderDetails = $this->getFullDetailsId($row['order_id'], "orders");
            if ($orderDetails[2] == 0) {
                $categoryDetails = $this->getFullDetailsId($orderDetails[3], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($orderDetails[2], "food_subcategory");
            }
            $total = $row['quantity'] * $orderDetails[13];
            $data .= "<tr><td>" . $categoryDetails[1] . "</td><td>" . $this->formatNumber($orderDetails[13]) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td><button type='button' class='red' onclick=\"deleteReq('" . $row['id'] . "','split_bill','?splitBill')\" style='border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px; color: #fff;'>Delete</button></td></tr>";
        }

        $data .= "

						            </tbody>
						          </table>
						        </div>";
        return $data;
    }

    function getSplitBillNumber()
    {
        $tid = $this->sanitize($_SESSION['existingOrders'][0]);
        $ptid = $this->sanitize($_SESSION['splitTid']);
        $sql = "select * from split_bill where tid=? and ptid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid, $ptid));
        return $result->rowCount();
    }

    function bpcTid($tid)
    {
        $tid = $this->sanitize($tid);
        $sql = "select billing_params_categories from orders where tid=? limit 1";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        $result = $result->fetch();
        return $result[0];
    }

    function saveSplitBillProcess()
    {
        $tid = $this->sanitize($_POST['tid']);
        $ptid = $this->sanitize($_POST['ptid']);
        $sql = "select * from split_bill where tid=? and ptid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid, $ptid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            //looping through each data
            //checking if it is a direct move or a reduction
            $orderDetails = $this->getFullDetailsId($row['order_id'], "orders");
            if ($row['quantity'] == $orderDetails[4]) {
                //direct move; so move
                $billing_params_categories = $this->bpcTid($ptid);
                if(isset($_SESSION['vtable'])){
                    $billing_params_categories = $this->bpcTid($tid);
                }
                $query = "update orders set tid=?,billing_params_categories=? where id=?";
                $res = $this->con->prepare($query);
                if ($res->execute(array($ptid, $billing_params_categories, $row['order_id']))) {
                    $query1 = "delete from split_bill where id=?";
                    $result1 = $this->con->prepare($query1);
                    $result1->execute(array($row['id']));
                    unset($_SESSION['splitTid']);
                }
            } else {
                //reduction process
                $newQuantity = $row['quantity'];
                $remainder = $orderDetails[4] - $newQuantity;
                //changing value of old order
                $query = "update orders set quantity=? where id=?";
                $res = $this->con->prepare($query);
                if ($res->execute(array($remainder, $row['order_id']))) {
                    //creating a new order using the ptid
                    $billing_params_categories = $this->bpcTid($ptid);
                    if(isset($_SESSION['vtable'])){
                        $billing_params_categories = $this->bpcTid($tid);
                    }
                    $query1 = "insert into orders(tid,food_subcategory,drinks_subcategory,quantity,side,remarks,status,pid,date,billing_params_categories,pending,bill_process) values(?,?,?,?,?,?,?,?,?,?,?,?)";
                    $res1 = $this->con->prepare($query1);
                    if ($res1->execute(array($ptid, $orderDetails[2], $orderDetails[3], $newQuantity, $orderDetails[5], $orderDetails[6], $orderDetails[7], $orderDetails[8], $orderDetails[9], $billing_params_categories, $orderDetails[11], $orderDetails[12]))) {
                        $query2 = "delete from split_bill where id=?";
                        $result2 = $this->con->prepare($query2);
                        $result2->execute(array($row['id']));
                        unset($_SESSION['splitTid']);
                    }
                }


            }
        }
        echo 1;
    }


    function previewMergeBill()
    {
        //checking if
        if (!isset($_SESSION['splitTid'])) {
            $this->selectSplitTid();
            echo "<script>
						$('#tHeader').html('Select order to merge with');
						$('#proceedBtn').attr('onclick',\"window.location.assign('?mergeBill')\");
                        $('#virtualBtn').hide();
						</script>";
            return;
        }

        $tid = $this->sanitize($_SESSION['existingOrders'][0]);
        $tidDetails = $this->getTableDetailsTid($tid);

        $ptid = $this->sanitize($_SESSION['splitTid']);
        $ptidDetails = $this->getTableDetailsTid($ptid);


        $data = "
					<script>
						function exitSplitBill(){
								$.post('ajax.php',{'splitTid':'y','status':'0'},function(data){
									window.location.assign('?processBill');
								});
							}
						function saveBtn(){
							alertify.confirm('Gold Coast Restaurant','Save?',function(e){
								if(e){
									$.post('ajax.php',{'saveMergeBill':'y'},function(data){
										if(data == 1){
											displayMessage('Process Completed',1);
											window.location.assign('?processBill');
										}else{
											displayMessage('Process failed',0);
										}
									});
								}
							},function(e){
								//error
							});
						}
					</script>
			        <div class='row'>
			          <div class='col s6 l4 offset-l1'>
			          	<div class='row'>
			          		<div class='col s12'>
			          			<p class='flow-text' style='text-align: center;'>ORDER " . $tid . " <br> " . $tidDetails . "</p>
			          		</div>
			          	</div>
			          </div>
			          <div class='col s6 l4'>
			          	<div class='row'>
			          		<div class='col s12 offset-l6'>
			          			<p class='flow-text' style='text-align: center;'>ORDER " . $ptid . " <br> " . $ptidDetails . "</p>
			          		</div>
			          	</div>
			          </div>
			        </div>

			        <div class='row blue lighten-3'>
			        	<div class='row no-margin'>
			              	<div class='col s6 right-align'>
			                	<p></p>
			              	</div>

			              	<div class='col s6'>
			                	<p></p>
			                	<div class='quantity inline-flex'>
			                  		<p></p>
			                	</div>
			              	</div>
			            </div>
			        </div>


			        <div class='row'>
			        	<div class='col s12 l5'>
			        		<div class='table-responsive'>
				        		<table>
				        			<thead>
				        				<tr>
				        					<th>ITEMS</th>
				        					<th>PRICE</th>
				        					<th>QTY</th>
				        					<th>TOTAL</th>
				        					<th></th>
				        				</tr>
				        			</thead>
				        			<tbody>";
        //loading first data
        $sql = "select * from orders where tid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        $totalAmount = 0.0;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($this->checkMergeBill($tid, $row['id'])) {
                continue;
            }

            if ($row['food_subcategory'] == 0) {
                $categoryDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }
            $total = floatval($this->formatNumber($row['price'])) * $row['quantity'];
            $totalAmount += $total;
            $data .= "<tr><td><a href='#split" . $row['id'] . "' class='modal-trigger'>" . $categoryDetails[1] . "</a></td><td>" . $this->formatNumber($row['price']) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td>" . $row['remarks'] . "</td><td></td></tr>";

            //move js function
            $data .= "<script>
				   					function moveItem" . $row['id'] . "(){
				   						alertify.confirm('Gold Coast Restaurant','Move Item?',function(e){
				   							if(e){
				   								//submitting data to server
				   								$.post('ajax.php',{'mergeBill':'y','tid':'" . $tid . "','ptid':'" . $ptid . "','order_id':'" . $row['id'] . "','quantity':'" . $row['quantity'] . "','pid':'" . $_SESSION['stratekuser'] . "'},function(data){
				   									if(data == 1){
				   										displayMessage('Process complete..',1);
				   									}else{
				   										displayMessage('Process failed.. Please try again!!',0);
				   									}
				   										window.location.assign('?mergeBill');
				   								});
				   							}
				   						},function(e){
				   							//error
				   						});
				   					}
				   				</script>";

            //modal
            $data .= "<div id='split" . $row['id'] . "' class='modal'>
							<div class='modal-content no-padding'>
									<nav class='green'>
								  	<div class='nav-wrapper'>
								    <a href='#!' class='brand-logo left'>Pending Orders</a>
								    <ul class='right'>
								      <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
								    </ul>
								  </div>
								</nav>

								<div class='row'>
									<div class='col s10 offset-s1'>
										<div class='input-field'>
							  				<input id='search' type='search' value='" . $categoryDetails[1] . "' class='green-text' readonly='' required>
							  				<label class='label-icon' for='search'>
							  					<i class='material-icons green-text'>description</i>
							  				</label>
										</div>



										<div class='input-field'>
											<div style='text-align: center;'>
												<button type='button' onclick=\"moveItem" . $row['id'] . "()\" class='btn red waves-effect waves-light bold-800 white-text'>
													<i class='material-icons left'>forward</i> Move
												</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							</div>";
        }

        //previewing moved data if any in the merge_bill table
        $sql = "select * from merge_bill where ptid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $orderDetails = $this->getFullDetailsId($row['order_id'], "orders");

            if ($orderDetails[2] == 0) {
                $categoryDetails = $this->getFullDetailsId($orderDetails[3], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($orderDetails[2], "food_subcategory");
            }
            $total = floatval($this->formatNumber($orderDetails[13])) * $row['quantity'];
            $totalAmount += $total;
            $data .= "<tr><td>" . $categoryDetails[1] . "</td><td>" . $this->formatNumber($orderDetails[13]) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td>" . $orderDetails[6] . "</td><td><button type='button' style='border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px; color: #fff; background-color: red;' onclick=\"deleteReq('" . $row['id'] . "','merge_bill','?mergeBill')\">Delete</button></td></tr>";
        }

	$tidAmount = $this->formatNumber($totalAmount);
        $data .= "<script>
				   				$('#tidTotal').html('" . $this->formatNumber($totalAmount) . "');
				   				function moveAllData(tid,ptid){
				   					alertify.confirm('Gold Coast Restaurant','Move All?',function(e){
				   						if(e){
				   							//sending data to server
				   							$.post('ajax.php',{'mergeMoveAll':'y','tid':tid,'ptid':ptid},function(data){
				   								if(data == 1){
				   									displayMessage('Process complete..',1);
				   								}else{
				   									displayMessage('Process failed..',0);
				   									//displayMessage(data,0);
				   								}
				   									window.location.assign('?mergeBill');
				   							});
				   						}
				   					},function(e){
				   						//error
				   					});
				   				}


				   			</script>";
        $data .= "			</tbody>
				        		</table>
			        		</div>
			        	</div>

			        	<div class='col s12 l2'>
			        		<div class='row'>
								<div class='input-field col s3 m6 l12 center-align'>
				        			<!--<button onclick=\"moveAllData('" . $tid . "','" . $ptid . "')\" class='btn green padding-5 waves-effect waves-light bold-800 modal-trigger' type='button'>
				        				<i class='material-icons right'>arrow_forward</i> MOVE ALL
				        			</button>-->
				        			<button href='#modal1' class='btn green padding-5 waves-effect waves-light bold-800 modal-trigger' type='button'>
				        				<i class='material-icons right'>arrow_forward</i> MOVE
				        			</button>
			        			</div>
								<div class='input-field col s3 m6 l12 center-align'>
				        			<!--<button onclick=\"moveAllData('" . $ptid . "','" . $tid . "')\" class='btn green padding-5 waves-effect waves-light bold-800 modal-trigger' type='button'>
				        				<i class='material-icons left'>arrow_back</i> MOVE ALL
				        			</button>-->
				        			<button href='#modal2' class='btn green padding-5 waves-effect waves-light bold-800 modal-trigger' type='button'>
				        				<i class='material-icons left'>arrow_back</i> MOVE
				        			</button>
			        			</div>
			        		</div>
			        	</div>

			        	<div class='col s12 l5'>
			        		<div class='table-responsive'>
				        		<table>
				        			<thead>
				        				<tr>
				        					<th>ITEMS</th>
				        					<th>PRICE</th>
				        					<th>QTY</th>
				        					<th>TOTAL</th>
				        					<th></th>
				        				</tr>
				        			</thead>
				        			<tbody>";
        //loading second data
        $sql = "select * from orders where tid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($ptid));
        $totalAmount = 0.0;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($this->checkMergeBill($ptid, $row['id'])) {
                continue;
            }
            if ($row['food_subcategory'] == 0) {
                $categoryDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }
            $total = floatval($this->formatNumber($row['price'])) * $row['quantity'];
            $totalAmount += $total;
            $data .= "<tr><td><a href='#splitS" . $row['id'] . "' class='modal-trigger'>" . $categoryDetails[1] . "</a></td><td>" . $this->formatNumber($row['price']) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td>" . $row['remarks'] . "</td><td></td></tr>";

            //move js function
            $data .= "<script>
				   					function moveItemS" . $row['id'] . "(){
				   						alertify.confirm('Gold Coast Restaurant','Move Item?',function(e){
				   							if(e){
				   								//submitting data to server
				   								$.post('ajax.php',{'mergeBill':'y','tid':'" . $ptid . "','ptid':'" . $tid . "','order_id':'" . $row['id'] . "','quantity':'" . $row['quantity'] . "','pid':'" . $_SESSION['stratekuser'] . "'},function(data){
				   									if(data == 1){
				   										displayMessage('Process complete..',1);
				   									}else{
				   										displayMessage('Process failed.. Please try again!!',0);
				   									}
				   										window.location.assign('?mergeBill');
				   								});
				   							}
				   						},function(e){
				   							//error
				   						});
				   					}
				   				</script>";

            //modal
            $data .= "<div id='splitS" . $row['id'] . "' class='modal'>
							<div class='modal-content no-padding'>
									<nav class='green'>
								  	<div class='nav-wrapper'>
								    <a href='#!' class='brand-logo left'>Pending Orders</a>
								    <ul class='right'>
								      <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
								    </ul>
								  </div>
								</nav>

								<div class='row'>
									<div class='col s10 offset-s1'>
										<div class='input-field'>
							  				<input id='search' type='search' value='" . $categoryDetails[1] . "' class='green-text' readonly='' required>
							  				<label class='label-icon' for='search'>
							  					<i class='material-icons green-text'>description</i>
							  				</label>
										</div>



										<div class='input-field'>
											<div style='text-align: center;'>
												<button type='button' onclick=\"moveItemS" . $row['id'] . "()\" class='btn red waves-effect waves-light bold-800 white-text'>
													<i class='material-icons left'>backspace</i> Move
												</button>
											</div>
										</div>
									</div>
								</div>
							</div>
							</div>";
        }

        //previewing moved data if any in the merge_bill table
        $sql = "select * from merge_bill where ptid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($ptid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $orderDetails = $this->getFullDetailsId($row['order_id'], "orders");

            if ($orderDetails[2] == 0) {
                $categoryDetails = $this->getFullDetailsId($orderDetails[3], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($orderDetails[2], "food_subcategory");
            }
            $total = floatval($this->formatNumber($orderDetails[13])) * $row['quantity'];
            $totalAmount += $total;
            $data .= "<tr><td>" . $categoryDetails[1] . "</td><td>" . $this->formatNumber($orderDetails[13]) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td>" . $orderDetails[6] . "</td><td><button type='button' style='border-radius: 50px; -moz-border-radius: 50px; -webkit-border-radius: 50px; color: #fff; background-color: red;' onclick=\"deleteReq('" . $row['id'] . "','merge_bill','?mergeBill')\">Delete</button></td></tr>";
        }

        $data .= "<script>
				   				$('#ptidTotal').html('" . $this->formatNumber($totalAmount) . "');
				   			</script>";


        $data .= "		</tbody>
				        		</table>
			        		</div>
			        	</div>
			        </div>

			        <div class='row'>
			        	<div class='col s12'>
			        		<table class='centered'>
			        			<thead>
			        				<tr>
			        					<th width='25%'>TOTAL</th>
			        					<th width='25%' id='tidTotal'>" . $this->formatNumber($tidAmount) . "</th>
			        					<th width='25%'>TOTAL</th>
			        					<th width='25%' id='ptidTotal'>" . $this->formatNumber($totalAmount) . "</th>
			        				</tr>
			        			</thead>
			        		</table>
			        	</div>
			        </div>

					<div class='row'>
						<div class='col s4'>
					        <div class='input-field'>
					        	<button type='button' onclick=\"saveBtn()\" class='btn green waves-effect waves-light bold-800' id='saveBtn'>
					        		<i class='material-icons left'>save</i> Save
					        	</button>
					        </div>
				        </div>
				        <div class='col s4'>
					        <div class='input-field'>
					        	<button type='button' onclick=\"exitSplitBill()\" class='btn red waves-effect waves-light bold-800'>
					        		<i class='material-icons left'>close</i> Exit
					        	</button>
					        </div>
				        </div>
				        <div class='col s4'></div>
			        </div>
			    </div>
			  		";
        if (!$this->getMergeBillNumber()) {
            $data .= "<script>
			  			$('#saveBtn').attr('disabled','');
			  		</script>";
        }

        $data .= $this->genMergeBillModal(1, $tid, $ptid);
        $data .= $this->genMergeBillModal(2, $ptid, $tid);
        echo $data;
    }

    function genMergeBillModal($id, $tid, $ptid)
    {
        $id = intval($this->sanitize($id));
        $tid = $this->sanitize($tid);
        $tidDetails = $this->getTableDetailsTid($tid);

        $ptid = $this->sanitize($ptid);
        $ptidDetails = $this->getTableDetailsTid($ptid);

        if ($id == 1) {
            $direction = "forward";
            $category = 1;
        } else {
            $direction = "backspace";
            $category = 2;
        }
        $data = "<div id='modal" . $id . "' class='modal'>
						<div class='modal-content no-padding'>
								<nav class='green'>
							  	<div class='nav-wrapper'>
							    <a href='#!' class='brand-logo left'>Merge Orders | " . $tid . " - " . $tidDetails . "</a>
							    <ul class='right'>
							      <li><a href='#!' class='modal-action modal-close waves-effect'><i class='material-icons'>close</i></a></li>
							    </ul>
							  </div>
							</nav>

							<div class='row'>
								<div class='col s10 offset-s1'>
									<div class='table-responsive'>
										<table class='centered'>
											<thead>
												<tr>
													<th></th>
													<th class='blue-text'>ITEM</th>
													<th class='blue-text'>PRICE(GH&cent;)</th>
													<th class='blue-text'>QTY</th>
													<th class='blue-text'>TOTAL</th>
												</tr>
											</thead>

											<tbody>";
        $sql = "select * from orders where tid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        $totalAmount = 0.0;
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($this->checkMergeBill($tid, $row['id'])) {
                continue;
            }
            if ($row['food_subcategory'] == 0) {
                $categoryDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }
            $total = floatval($this->formatNumber($row['price'])) * $row['quantity'];
            $totalAmount += $total;
            $data .= "<tr><td><input type='checkbox' id='" . $tid . $count . "'><label for='" . $tid . $count . "'></label></td><td>" . $categoryDetails[1] . "</td><td>" . $this->formatNumber($row['price']) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td>" . $row['remarks'] . "</td><td></td></tr>";

            //adding js script for select items function
            $data .= "<script>
                 			$('#" . $tid . $count . "').change(function(){
                 				if(this.checked){
                 					$.post('ajax.php',{'addExistingOrder" . $id . "':'y','id':'" . $row['id'] . "'},function(data){
                 						//console.log(data);
                 					});
                 				}else{
                 					$.post('ajax.php',{'removeExistingOrder" . $id . "':'y','id':'" . $row['id'] . "'},function(data){
                 						//console.log(data);
                 					});
                 				}
                 			});
                 		</script>";

            $count++;
        }
        $data .= "		</tbody>
										</table>
									</div>

									<div class='input-field'>
										<button type='submit' onclick=\"moveItem" . $tid . $id . "()\" class='btn red waves-effect waves-light bold-800 white-text'>
											<i class='material-icons left'>" . $direction . "</i> MOVE
										</button>
									</div>
								</div>
							</div>
						</div>
						</div>";
        $data .= "<script>
				   					function moveItem" . $tid . $id . "(){
				   						alertify.confirm('Gold Coast Restaurant','Move Item(s)?',function(e){
				   							if(e){
				   								//submitting data to server
				   								$.post('ajax.php',{'mergeSelectedBill':'" . $category . "','tid':'" . $tid . "','ptid':'" . $ptid . "'},function(data){
				   									if(data == 1){
				   										displayMessage('Process complete..',1);
				   									}else{
				   										displayMessage('Process failed.. Please try again!!',0);
				   										//displayMessage(data,0);
				   									}
				   										window.location.assign('?mergeBill');
				   								});
				   							}
				   						},function(e){
				   							//error
				   						});
				   					}
				   				</script>";
        return $data;
    }


    function mergeSelectedBill()
    {
        $category = intval($this->sanitize($_POST['mergeSelectedBill']));
        $tid = $this->sanitize($_POST['tid']);
        $ptid = $this->sanitize($_POST['ptid']);
        $pid = $this->sanitize($_SESSION['stratekuser']);
        $list = array();
        if ($category == 1) {
            $list = $_SESSION['existingOrders1'];
        } else {
            $list = $_SESSION['existingOrders2'];
        }
        for ($i = 0; $i < sizeof($list); $i++) {
            $order_id = $list[$i];
            $orderDetails = $this->getFullDetailsId($order_id, "orders");
            if ($this->checkMergeBill2($order_id)) {
                continue;
            }
            $this->mergeBill2($tid, $ptid, $order_id, $orderDetails[4], $pid);
        }
        echo 1;
    }

    function checkMergeBill2($order_id)
    {
        $order_id = $this->sanitize($order_id);
        $sql = "select * from merge_bill where order_id=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($order_id));
        if ($result->rowCount() >= 1) {
            return true;
        } else {
            return false;
        }
    }

    function checkMergeBill($tid, $order_id)
    {
        $tid = $this->sanitize($tid);
        $order_id = $this->sanitize($order_id);
        $sql = "select * from merge_bill where tid=? and order_id=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid, $order_id));
        if ($result->rowCount() >= 1) {
            return true;
        } else {
            return false;
        }
    }

    function mergeBill()
    {
        $tid = $this->sanitize($_POST['tid']);
        $ptid = $this->sanitize($_POST['ptid']);
        $order_id = $this->sanitize($_POST['order_id']);
        $quantity = $this->sanitize($_POST['quantity']);
        $pid = $this->sanitize($_POST['pid']);
        $sql = "insert into merge_bill(tid,ptid,order_id,quantity,pid) values(?,?,?,?,?)";
        $result = $this->con->prepare($sql);
        if ($result->execute(array($tid, $ptid, $order_id, $quantity, $pid))) {
            echo 1;
        } else {
            echo 0;
        }
    }

    function mergeBill2($tid, $ptid, $order_id, $quantity, $pid)
    {
        $tid = $this->sanitize($tid);
        $ptid = $this->sanitize($ptid);
        $order_id = $this->sanitize($order_id);
        $quantity = $this->sanitize($quantity);
        $pid = $this->sanitize($pid);
        $sql = "insert into merge_bill(tid,ptid,order_id,quantity,pid) values(?,?,?,?,?)";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid, $ptid, $order_id, $quantity, $pid));
    }

    function moveAllMerge()
    {
        $tid = $this->sanitize($_POST['tid']);
        $ptid = $this->sanitize($_POST['ptid']);
        $pid = $this->sanitize($_SESSION['stratekuser']);
        //moving all tid to ptid
        $sql = "select * from orders where tid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($this->checkMergeBill($tid, $row['id'])) {
                continue;
            }
            //add to merge
            $this->mergeBill2($tid, $ptid, $row['id'], $row['quantity'], $pid);
        }
        echo 1;
    }

    function getMergeBillNumber()
    {
        $number1 = 0;
        $number2 = 0;
        $tid = $this->sanitize($_SESSION['existingOrders'][0]);
        $ptid = $this->sanitize($_SESSION['splitTid']);
        $sql = "select * from merge_bill where tid=? and ptid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid, $ptid));
        $number1 = $result->rowCount();

        $sql = "select * from merge_bill where tid=? and ptid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($ptid, $tid));
        $number2 = $result->rowCount();
        $total = $number1 + $number2;
        if ($total >= 1) {
            return true;
        } else {
            return false;
        }
    }

    function saveMergeBill()
    {
        $tid = $this->sanitize($_SESSION['existingOrders'][0]);
        $ptid = $this->sanitize($_SESSION['splitTid']);

        //first scenario 1->2
        $sql = "select * from merge_bill where tid=? and ptid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid, $ptid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $billing_params_categories = $this->bpcTid($ptid);
            //change tid and change billing_params_categories
            $query = "update orders set tid=?,billing_params_categories=? where id=?";
            $res = $this->con->prepare($query);
            if ($res->execute(array($ptid, $billing_params_categories, $row['order_id']))) {
                //delete from merge_bill
                $query1 = "delete from merge_bill where id=?";
                $res1 = $this->con->prepare($query1);
                $res1->execute(array($row['id']));
            }

        }

        //second scenario 2->1
        $sql = "select * from merge_bill where tid=? and ptid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($ptid, $tid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $billing_params_categories = $this->bpcTid($tid);
            //change tid and change billing_params_categories
            $query = "update orders set tid=?,billing_params_categories=? where id=?";
            $res = $this->con->prepare($query);
            if ($res->execute(array($tid, $billing_params_categories, $row['order_id']))) {
                //delete from merge_bill
                $query1 = "delete from merge_bill where id=?";
                $res1 = $this->con->prepare($query1);
                $res1->execute(array($row['id']));
            }

        }
        unset($_SESSION['splitTid']);
        //returning response
        echo 1;
    }

    function loadExistingOrdersReport()
    {
        $url = $_SERVER['REQUEST_URI'];
        $url = explode("?", $url);
        $url = $url[0] . "/../print.php?report&existingOrders=";
        $url = "http://" . $_SERVER['HTTP_HOST'] . $url;
        //$url = urlencode($url);
        $data = "<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>
			<thead>
				<tr><th><center>No</center></th><th><center>Order#</center></th><th><center>Bill To</center></th><th><center>Waiter</center></th><th><center>Date</center></th><th></th></tr>
			</thead><tbody>";
        $sql = "select distinct tid,pid,billing_params_categories from orders where status=1 and bill_process=0 and pending=1 order by date desc";
        $result = $this->con->query($sql);
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tid = strtoupper($row['tid']);
            $pid = $row['pid'];
            $billing_params_categories = $row['billing_params_categories'];
            //getting user details
            $userDetails = $this->getFullDetailsId($pid, "login");
            $orderDetails = $this->getOrderInfoTid($tid);
            $row['date'] = $orderDetails[9];

            //getting billing_params
            $subParam = $this->getFullDetailsId($billing_params_categories, "billing_params_categories");
            $mainParam = $this->getFullDetailsId($subParam[2], "billing_params");
            $data .= "<tr>
                    <td>
						<center>" . $count . "</center>
                    </td>
                      <td><center>" . $tid . "</center></td>
                      <td><center>" . $mainParam[1] . " - " . $subParam[1] . "</center></td>
					  <td><center>" . $userDetails[3] . "</center></td>
					  <td><center>" . $row['date'] . "</center></td>
					  <td>
						 <div style='text-align: center;'>
						 	<a href='#print" . $row['tid'] . "' class='btn btn-xs btn-info' data-toggle='modal' data-backdrop='static'><span class='fa fa-print'></span> Print</a>
						 </div>
					  </td>
					</tr>";

            //adding modal
            $data .= "<div id='print" . $row['tid'] . "' class='modal fade'>
							<div class='modal-dialog modal-md'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<h3 class='panel-title' style='text-align: center;'>" . $tid . " | " . $mainParam[1] . " - " . $subParam[1] . "</h3>
									</div>
									<div class='modal-body'>
										<div class='form-group'>
											<center>
											<embed src='print.php?report&existingOrders=" . $tid . "' width='500' height='500' type='application/pdf'>
											</center>
										</div>
										<div class='form-group'>
											<div style='text-align: center;'>
												<button type='button' onclick=\"printDocument('" . $url . "=" . $tid . "')\" id='printBtn' class='btn btn-xs btn-success'><span class='fa fa-print'></span> Print</button> &nbsp;
												<button type='button' data-dismiss='modal' class='btn btn-xs btn-danger'><span class='fa fa-close'></span> Close</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>";

            $count++;
        }
        $data .= "</tbody></table>";
        echo $data;
    }

    function getOrderInfoTid($tid)
    {
        $sql = "select * from orders where tid=? limit 1";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        return $result->fetch();
    }

    function previewOrderedItemsReport($tid)
    {
        $tid = $this->sanitize($tid);
        $orderDetails = $this->getOrderInfoTid($tid);
        $tableDetails = $this->getTableDetailsTid($tid);
        $userDetails = $this->getFullDetailsId($orderDetails[8], "login");
        $data = "<p style='padding: 0px; margin: 5px; font-size: 14px;'><u>" . $tid . "  |  " . $tableDetails . "</u> (<b>Add to order</b>)</p>";
        $data .= "<p style='padding: 0px; margin: 5px; font-size: 14px;'><b>Order Date:</b> " . $orderDetails[9] . "</p>";
        $data .= "<p style='padding: 0px; margin: 5px; font-size: 14px;'><b>Waiter/Waitress:</b> " . $userDetails[3] . "</p>";
        $data .= "<p style='padding: 0px; margin: 5px; font-size: 14px;'><b>Print Date:</b> " . $this->genDate() . "</u></p>";
        $data .= "<table border='1' cellspadding='2' cellspacing='2' style='margin: 5px; font-size: 14px;border: 1px solid black;border-collapse: collapse; width: 100%;'>
					<thead>
						<tr><th>Item</th></th><th>Quantity</th><th>Remarks</th></tr>
					</thead>
					<tbody>";

        //search through database
        $sql = "select * from orders where tid=?";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        $count = 1;
        $totalAmount = 0.0;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['food_subcategory'] == 0) {
                $categoryDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }
            $amount = floatval($row['quantity'] * $categoryDetails[2]);
            $totalAmount += $amount;
            $data .= "<tr><td>" . $categoryDetails[1] . "</td><td><center>" . $row['quantity'] . "</center></td><td>" . $row['remarks'] . "</td></tr>";
            $count++;
        }
      /*  if ($count > 1) {
            $data .= "<tr><td colspan='3'><b>Total</b></td><td colspan='2'><b>" . $this->formatNumber($totalAmount) . "</b></td></tr>";
        }*/
        $data .= "</tbody></table>";
        return $data;
    }

    function printDocument($pdf)
    {
        $printerConfiguration = $this->getPrinterConfiguration();
        $this->connector = new NetworkPrintConnector($printerConfiguration[1], $printerConfiguration[2]);
        $this->printer = new Printer($this->connector);
        try {
            $pages = ImagickEscposImage::loadPdf($pdf);
            foreach ($pages as $page) {
                $this->printer->graphics($page);
            }
            $this->printer->cut();
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
        $this->printer->close();
        echo 1;
    }

    function loadReceiptReport()
    {
        $url = $this->getPrintAdminUrl() . "?report&receipt";
        $data = "<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>
			<thead>
				<tr><th><center>No</center></th><th><center>Order#</center></th><th><center>Bill To</center></th><th><center>Waiter</center></th><th><center>Date</center></th><th></th></tr>
			</thead><tbody>";
        $sql = "select distinct tid,pid,billing_params_categories from orders where status=1 and bill_process=1 and pending=0 order by date desc";
        $result = $this->con->query($sql);
        $count = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $tid = strtoupper($row['tid']);
            $pid = $row['pid'];
            $orderDetails = $this->getOrderInfoTid($tid);
            $row['date'] = $orderDetails[9];
            $billing_params_categories = $row['billing_params_categories'];
            //getting user details
            $userDetails = $this->getFullDetailsId($pid, "login");
            //getting billing_params
            $subParam = $this->getFullDetailsId($billing_params_categories, "billing_params_categories");
            $mainParam = $this->getFullDetailsId($subParam[2], "billing_params");
            $data .= "<tr>
                    <td>
						<center>" . $count . "</center>
                    </td>
                      <td><center>" . $tid . "</center></td>
                      <td><center>" . $mainParam[1] . " - " . $subParam[1] . "</center></td>
					  <td><center>" . $userDetails[3] . "</center></td>
					  <td><center>" . $row['date'] . "</center></td>
					  <td>
						 <div style='text-align: center;'>
						 	<a href='#print" . $row['tid'] . "' class='btn btn-xs btn-info' data-toggle='modal' data-backdrop='static'><span class='fa fa-print'></span> Print</a>
						 </div>
					  </td>
					</tr>";

            //adding modal
            $data .= "<div id='print" . $row['tid'] . "' class='modal fade'>
							<div class='modal-dialog modal-md'>
								<div class='modal-content'>
									<div class='modal-header bgblue'>
										<h3 class='panel-title' style='text-align: center;'>" . $tid . " | " . $mainParam[1] . " - " . $subParam[1] . "</h3>
									</div>
									<div class='modal-body'>
										<div class='form-group'>
											<center>
											<embed src='print.php?report&receipt=" . $tid . "' width='500' height='500' type='application/pdf'>
											</center>
										</div>
										<div class='form-group'>
											<div style='text-align: center;'>
												<button type='button' onclick=\"printDocument('" . $url . "=" . $tid . "')\" id='printBtn' class='btn btn-xs btn-success'><span class='fa fa-print'></span> Print</button> &nbsp;
												<button type='button' data-dismiss='modal' class='btn btn-xs btn-danger'><span class='fa fa-close'></span> Close</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>";

            $count++;
        }
        $data .= "</tbody></table>";
        echo $data;
    }

    function getProcessedBillTid($tid){
        $sql = "select * from processed_bill where tid=? limit 1";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        return $result->fetch();
    }

    function previewReceiptReport($tid)
    {
        $tid = $this->sanitize($tid);
        $sql = "select * from orders where tid=? and status=1 and pending=0 and bill_process=1 order by date desc";

        $processed_bill = $this->getProcessedBillTid($tid);
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));

        //getting order details
        $orderDetails = $this->getOrderInfoTid($tid);
        $userDetails = $this->getFullDetailsId($orderDetails[8], "login");
        $cashierDetails = $this->getFullDetailsId($_SESSION['stratekadmin'], "login");
        $data = "<center><h3 style='padding: 0px; margin: 5px; font-size: 14px;'><u>RECEIPT</u></h3></center>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Amount:</b> CASH A/C</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Invoice:</b> ".$processed_bill[10]."</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>T.I.N:</b> ".$orderDetails[1]."</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Waiter:</b> ".$userDetails[3]."</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Table:</b> ".$this->getTableDetailsTid($tid)."</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Cashier:</b> ".$cashierDetails[3]."</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Order Date:</b> ".$orderDetails[9]."</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Date Printed:</b> ".$this->genDate()."</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'>&nbsp;</p>";
        //loading table
        $data .= "<table border='1' cellspadding='2' cellspacing='2' style='margin: 5px; font-size: 14px;border: 1px solid black;border-collapse: collapse; width: 100%;'>
					<thead>
						<tr><th>Item</th><th>U/Price(GH&cent;)</th><th>Quantity</th><th>Total</th></tr>
					</thead>
					<tbody>";
        //search through database
        $count = 1;
        $totalAmount = 0.0;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['food_subcategory'] == 0) {
                $categoryDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }
            $amount = floatval($row['quantity'] * $row['price']);
            $totalAmount += $amount;
            $data .= "<tr><td>" . $categoryDetails[1] . "</td><td><center>" . $this->formatNumber($row['price']) . "</center></td><td><center>" . $row['quantity'] . "</center></td><td><center></center>" . $this->formatNumber($amount) . "</center></td></tr>";
            $count++;
        }

        $orderTotal = $this->formatNumber($totalAmount);
        $service_charge = $this->formatNumber($processed_bill[3]);
        $discount = $this->formatNumber(($processed_bill[2]/100)*$orderTotal);
        $subTotal = $this->formatNumber($orderTotal - $discount + $service_charge);
        $vat = $this->formatNumber(($processed_bill[4]/100)*$subTotal);
        $nhil = $this->formatNumber(($processed_bill[5]/100)*$subTotal);
        $gtbl = $this->formatNumber(($processed_bill[6]/100)*$subTotal);
        $grandTotal = $this->formatNumber($subTotal + $vat + $nhil + $gtbl);
        if ($count > 1) {
            $data .= "<tr><td colspan='3'><b>Order Total</b></td><td colspan='1'><center><b>" . $this->formatNumber($orderTotal) . "</b></center></td></tr>";
            $data .= "<tr><td colspan='3'>Service Charge</td><td colspan='1'><center>" . $this->formatNumber($service_charge) . "</center></td></tr>";
            $data .= "<tr><td colspan='3'>Discount</td><td colspan='1'><center>" . $this->formatNumber($discount) . "</center></td></tr>";
            $data .= "<tr><td colspan='3'><b>Sub Total</b></td><td colspan='1'><center><b>" . $this->formatNumber($subTotal) . "</b></center></td></tr>";
            $data .= "<tr><td colspan='3'>VAT(".$processed_bill[4]."%)</td><td colspan='1'><center>" . $this->formatNumber($vat) . "</center></td></tr>";
            $data .= "<tr><td colspan='3'>NHIL(".$processed_bill[5]."%)</td><td colspan='1'><center>" . $this->formatNumber($nhil) . "</center></td></tr>";
            $data .= "<tr><td colspan='3'>GTBL(".$processed_bill[6]."%)</td><td colspan='1'><center>" . $this->formatNumber($gtbl) . "</center></td></tr>";
            $data .= "<tr><td colspan='3'><b>Grand Total</b></td><td colspan='1'><center><b>" . $this->formatNumber($grandTotal) . "</b></center></td></tr>";
        }

        $data.= "</tbody></table>";
        return $data;
    }

    function previewSummaryReport($from,$to){
        $from = $this->sanitize($from);
        $to = $this->sanitize($to);
        $adminDetails = $this->getFullDetailsId($_SESSION['stratekadmin'], "login");
        $data = "<center><h3 style='padding: 0px; margin: 5px; font-size: 15px;'><u>Summary Report</u></h3></center>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>From:&nbsp;</b> ".$from."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>To:&nbsp;</b> ".$to."</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Cashier:</b> ".$adminDetails[3]."</p>";

        ####### sales category #######
        $sql = "select * from orders where pending=0 and status=1 and bill_process=1 and Date(date) >= ? and Date(date) <= ?";
        $result = $this->con->prepare($sql);
        $result->execute(array($from,$to));
        $food = array();
        $drinks = array();
        $foodCount = 0;
        $drinksCount = 0;
        while($row = $result->fetch(PDO::FETCH_ASSOC)){
        	if($row['food_subcategory'] == 0){
        		//belongs to drinks category
        		$drinks_subcategory = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
        		if(!in_array($drinks_subcategory[3], $drinks)){
        			$drinks[$drinksCount] = $drinks_subcategory[3];
        			$drinksCount++;
        		}
        	}else{
        		//belongs to food category
        		$food_subcategory = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
        		if(!in_array($food_subcategory[3], $food)){
        			$food[$foodCount] = $food_subcategory[3];
        			$foodCount++;
        		}
        	}
        }

        //displaying table
        $data .= "<table border='1' cellspadding='5' cellspacing='5' style='margin: 5px; font-size: 14px;border: 1px solid black;border-collapse: collapse; width: 100%;'>
					<tbody>";
		$totalItems = 0;
		$totalAmount = 0.0;
		//getting categories from arrays
		//food
		if(sizeof($food) >= 1 || sizeof($drinks) >=1){
			//include header
			$data.= "<tr><td colspan='3'><b>SALES BY CATEGORY</b></td></tr>";
			$data.="<tr><th>Category</th><th># of items</th><th>Amount</th></tr>";
		}
		for($i = 0 ; $i < sizeof($food); $i++){
			$details = $this->getFullDetailsId($food[$i], "food");
			$extraDetails = $this->getCategoryDetails($food[$i], "food", $from, $to);
			$totalItems += $extraDetails[0];
			$totalAmount += $this->formatNumber($extraDetails[1]);
			$data.="<tr><td>".$details[1]."</td><td><center>".$extraDetails[0]."</center></td><td><center>".$this->formatNumber($extraDetails[1])."</center></td></tr>";
		}

		//drinks
		for($i = 0 ; $i < sizeof($drinks); $i++){
			$details = $this->getFullDetailsId($drinks[$i], "drinks");
			$extraDetails = $this->getCategoryDetails($drinks[$i], "food", $from, $to);
			$totalItems += $extraDetails[0];
			$totalAmount += $this->formatNumber($extraDetails[1]);
			$data.="<tr><td>".$details[1]."</td><td><center>".$extraDetails[0]."</center></td><td><center>".$this->formatNumber($extraDetails[1])."</center></td></tr>";
		}
		if(sizeof($food) >= 1 || sizeof($drinks) >=1){
			//include subtotal
			$totalAmount = $this->formatNumber($totalAmount);
			$data.="<tr><th></th><th><center>".$totalItems."</center></th><th><center>".$totalAmount."</center></th></tr>";
		}



		##################### SALES BY WAITER ######################
		$sql = "select * from orders where pending=0 and status=1 and bill_process=1 and Date(date) >= ? and Date(date) <= ?";
        $result = $this->con->prepare($sql);
        $result->execute(array($from,$to));
		$waiters = array();
		$waitersCount = 0;
		while($row = $result->fetch(PDO::FETCH_ASSOC)){
			if(!in_array($row['pid'], $waiters)){
				$waiters[$waitersCount] = $row['pid'];
				$waitersCount++;
			}
		}
		$totalItems = 0;
		$totalAmount = $this->formatNumber(0);
		if(sizeof($waiters) >= 1){
			//separator
			$data.="<tr><td colspan='3'>&nbsp;</td></tr>";
			//header
			$data.= "<tr><td colspan='3'><b>SALES BY WAITER/WAITRESS</b></td></tr>";
			$data.="<tr><th>Waiter/Waitress</th><th># of Orders</th><th>Amount</th></tr>";
			for($i = 0; $i < sizeof($waiters); $i++){
				$details = $this->getFullDetailsId($waiters[$i], "login");
				$orderDetails = $this->getWaiterOrderDetails($waiters[$i],$from,$to);
				$totalItems += $orderDetails[0];
				$totalAmount += $orderDetails[1];
				$data.="<tr><td>".$details[3]."</td><td><center>".$orderDetails[0]."</center></td><td><center>".$this->formatNumber($orderDetails[1])."</center></td></tr>";
			}
			$totalAmount = $this->formatNumber($totalAmount);
			$data.="<tr><th></th><th><center>".$totalItems."</center></th><th><center>".$totalAmount."</center></th></tr>";
		}
		################### END OF SALES BY WATER ##################



		################# SALES BY KITCHEN #########################
		$sql = "select * from orders where pending=0 and status=1 and bill_process=1 and Date(date) >= ? and Date(date) <= ?";
        $result = $this->con->prepare($sql);
        $result->execute(array($from,$to));
		$billingParams = array();
		$billingParamsCount = 0;
		$totalItems = 0;
		$totalAmount = $this->formatNumber(0);
		while($row = $result->fetch(PDO::FETCH_ASSOC)){
			$billing_params_categories = $this->getFullDetailsId($row['billing_params_categories'], "billing_params_categories");
			$billing_params = $this->getFullDetailsId($billing_params_categories[2], "billing_params");
			if(!in_array($billing_params[0], $billingParams)){
				$billingParams[$billingParamsCount] = $billing_params[0];
				$billingParamsCount++;
			}
		}
		if(sizeof($billingParams) >= 1){
			//separator
			$data.="<tr><td colspan='3'>&nbsp;</td></tr>";
			//header
			$data.= "<tr><td colspan='3'><b>SALES BY KITCHEN</b></td></tr>";
			$data.="<tr><th>Kitchen</th><th># of Orders</th><th>Amount</th></tr>";
			for($i = 0 ; $i < sizeof($billingParams); $i++){
				$billing_params = $this->getFullDetailsId($billingParams[$i], "billing_params");
				$details = $this->getKitchenOrderDetails($billing_params[0],$from,$to);
				$totalItems += $details[0];
				$totalAmount += $this->formatNumber($details[1]);
				$data.="<tr><td>".$billing_params[1]."</td><td><center>".$details[0]."</center></td><td><center>".$this->formatNumber($details[1])."</center></td></tr>";
			}
			$totalAmount = $this->formatNumber($totalAmount);
			$data.="<tr><th></th><th><center>".$totalItems."</center></th><th><center>".$totalAmount."</center></th></tr>";
		}
		############## END OF SALES BY KITCHEN ######################


		############### SALES BY ITEM TYPE ##########################
		$sql = "select * from orders where pending=0 and status=1 and bill_process=1 and Date(date) >= ? and Date(date) <= ?";
        $result = $this->con->prepare($sql);
        $result->execute(array($from,$to));
		$billingParams = array();
		$billingParamsCount = 0;
		$totalItems = 0;
		$foodItems = 0;
		$drinksItems = 0;
		$foodAmount = 0;
		$drinksAmount = 0;
		$totalAmount = $this->formatNumber(0);
		while($row = $result->fetch(PDO::FETCH_ASSOC)){
			if($totalItems == 0){
				//separator
				$data.="<tr><td colspan='3'>&nbsp;</td></tr>";
				//header
				$data.= "<tr><td colspan='3'><b>SALES BY ITEM GROUP</b></td></tr>";
				$data.="<tr><th>Item Group</th><th># of Orders</th><th>Amount</th></tr>";
			}

			if($row['food_subcategory'] == 0){
				//drinks
				$drinksItems++;
				$drinksAmount += $this->formatNumber($row['quantity'] * $this->getOrderAmountPaid($row['tid'],$row['price']));
			}else{
				//food
				$foodItems++;
				$foodAmount += $this->formatNumber($row['quantity'] * $this->getOrderAmountPaid($row['tid'],$row['price']));
			}
			$totalItems++;
		}
		$totalAmount = $this->formatNumber($foodAmount + $drinksAmount);
		$totalItems = $foodItems + $drinksItems;
		if($totalItems > 0){
			$data.="<tr><td>Food</td><td><center>".$foodItems."</center></td><td><center>".$this->formatNumber($foodAmount)."</center></td></tr>";
			$data.="<tr><td>Drinks</td><td><center>".$drinksItems."</center></td><td><center>".$this->formatNumber($drinksAmount)."</center></td></tr>";
			$data.="<tr><th></th><th><center>".$totalItems."</center></th><th><center>".$totalAmount."</center></th></tr>";
		}
		############### END OF SALES BY ITEM TYPE ####################



		################ DISCOUNT & SERVICE CHARGE ####################
		$sql = "select * from processed_bill where Date(date) >=? and Date(date) <=?";
		$result = $this->con->prepare($sql);
		$result->execute(array($from,$to));
		$totalAmount = 0.0;
		$discountAmount = 0.0;
		$totalNumber = 0;
		$serviceChargeAmount = 0.0;

		if($result->rowCount() >= 1){
			//looping through data
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$discountAmount -= $this->formatNumber(($row['discount']/100)*$row['amount']);
				$totalNumber += $this->getOrderNumberTid($row['tid']);
				$serviceChargeAmount += $this->formatNumber($row['service_charge']);
			}

			$totalAmount = $this->formatNumber($discountAmount + $serviceChargeAmount);
			$discountAmount = $this->formatNumber($discountAmount);
			$serviceChargeAmount = $this->formatNumber($serviceChargeAmount);


			//separator
			$data.="<tr><td colspan='3'>&nbsp;</td></tr>";
			//header
			$data.= "<tr><td colspan='3'><b>DISCOUNT & SERVICE CHARGE</b></td></tr>";
			$data.="<tr><th>Sales Type</th><th># of Orders</th><th>Amount</th></tr>";
			$data.="<tr><td>Discount</td><td><center>".$totalNumber."</center></td><td><center>".$discountAmount."</center></td></tr>";
			$data.="<tr><td>Service Charge</td><td><center></center></td><td><center>".$serviceChargeAmount."</center></td></tr>";
			$data.="<tr><th></th><th><center>".$totalNumber."</center></th><th><center>".$totalAmount."</center></th></tr>";
		}
		################ END OF DISCOUNT & SERVICE CHARGE #############


		################ TOTAL SALES ##################################
		$sql = "select * from mode_of_payment";
		$result = $this->con->query($sql);
		if($result->rowCount() >= 1){
			//separator
			$data.="<tr><td colspan='3'>&nbsp;</td></tr>";
			//header
			$data.= "<tr><td colspan='3'><b>TOTAL SALES</b></td></tr>";
			$data.="<tr><th>Sales Type</th><th># of Orders</th><th>Amount</th></tr>";
			$totalNumber = 0;
			$totalAmount = $this->formatNumber(0);
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$mode_of_payment = $row['id'];
				$modeDetails = $this->getFullDetailsId($mode_of_payment, "mode_of_payment");
				$query = "select * from processed_bill where Date(date) >=? and Date(date) <=? and mode_of_payment=?";
				$res = $this->con->prepare($query);
				$res->execute(array($from,$to,$mode_of_payment));
                if($res->rowCount() >= 1){
                    $details = $this->getModePaymentData($from, $to, $mode_of_payment);
                    $totalNumber += $details[0];
                    $totalAmount += $this->formatNumber($details[1]);
                    $data.="<tr><td>".$modeDetails[1]."</td><td><center>".$details[0]."</center></td><td><center>".$this->formatNumber($details[1])."</center></td></tr>";
                }
			}
			$totalAmount = $this->formatNumber($totalAmount);
			$data.="<tr><th></th><th><center>".$totalNumber."</center></th><th><center>".$totalAmount."</center></th></tr>";
		}
		################ END OF TOTAL SALES ###########################
		$data.="</tbody></table>";
        //end of table

        ### received/checked by ###
        $data.="<p>&nbsp;</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Received by:</b> ..................</p>";
        $data .= "<p style='margin: 5px; padding: 0px; font-size: 14px;'><b>Checked by:</b> ..................</p>";
        $data.="<p>&nbsp;</p>";
        return $data;
    }

    function getModePaymentData($from,$to,$id){
    	$data = array();
    	$data[0] = 0;
    	$data[1] = $this->formatNumber(0);
    	$mode_of_payment = $this->sanitize($id);
    	$sql = "select * from processed_bill where Date(date) >=? and Date(date) <=? and mode_of_payment=?";
    	$result = $this->con->prepare($sql);
    	$result->execute(array($from,$to,$mode_of_payment));
    	while($row = $result->fetch(PDO::FETCH_ASSOC)){
    		$data[0] += $this->getOrderNumberTid($row['tid']);
    		$data[1] += $this->getProcessedBillAmountPaid($row['tid']);
    	}
    	return $data;
    }

    function getOrderNumberTid($tid){
    	$tid = $this->sanitize($tid);
    	$sql = "select count(*) from orders where tid=?";
    	$result = $this->con->prepare($sql);
    	$result->execute(array($tid));
    	$result = $result->fetch();
    	return $result[0];
    }

    function getKitchenOrderDetails($billing_params,$from,$to){
    	$data = array();
    	$data[0] = 0;
    	$data[1] = $this->formatNumber(0);
    	$billing_params = $this->sanitize($billing_params);
    	$from = $this->sanitize($from);
    	$to = $this->sanitize($to);
    	$sql = "select * from orders where pending=0 and status=1 and bill_process=1 and Date(date) >= ? and Date(date) <= ?";
    	$result = $this->con->prepare($sql);
    	$result->execute(array($from,$to));
    	while($row = $result->fetch(PDO::FETCH_ASSOC)){
    		$billing_params_categories = $this->getFullDetailsId($row['billing_params_categories'], "billing_params_categories");
    		$billing_params1 = $this->getFullDetailsId($billing_params_categories[2], "billing_params");
    		if($billing_params1[0] == $billing_params){
    			$data[0]++;
    			$data[1] += $this->formatNumber($row['quantity']*$this->getOrderAmountPaid($row['tid'],$row['price']));
    		}
    	}

    	return $data;
    }

    function getWaiterOrderDetails($pid,$from,$to){
    	$data = array();
    	$data[0] = 0;
    	$data[1] = $this->formatNumber(0);
    	$pid = $this->sanitize($pid);
    	$from = $this->sanitize($from);
    	$to = $this->sanitize($to);
    	$sql = "select * from orders where pending=0 and status=1 and bill_process=1 and Date(date) >= ? and Date(date) <= ? and pid=?";
    	$result = $this->con->prepare($sql);
    	$result->execute(array($from,$to,$pid));
    	while($row = $result->fetch(PDO::FETCH_ASSOC)){
    		$data[1] += $this->formatNumber($row['quantity'] * $this->getOrderAmountPaid($row['tid'],$row['price']));
    		$data[0] += 1;
    	}
    	return $data;
    }

    function getCategoryDetails($categoryId,$category,$from,$to){
    	$data = array();
    	$data[0] = 0;
    	$data[1] = $this->formatNumber(0);
    	$categoryId = $this->sanitize($categoryId);
    	$category = $this->sanitize($category);
    	$from = $this->sanitize($from);
    	$to = $this->sanitize($to);
    	if($category == "food"){
    		$sql = "select * from orders where pending=0 and status=1 and bill_process=1 and Date(date) >= ? and Date(date) <= ?";
    		$result = $this->con->prepare($sql);
    		$result->execute(array($from,$to));
    		while($row = $result->fetch(PDO::FETCH_ASSOC)){
    			//getting category
    			if($row['food_subcategory']==0){
    				continue;
    			}else{
    				$category = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
    				if($category[3] == $categoryId){
    					//update count
    					$data[0] += $row['quantity'];
    					$data[1] += ($row['quantity'] * $this->getOrderAmountPaid($row['tid'],$row['price']));
    				}
    			}
    		}
    	}else{
    		//drinks
    		$sql = "select * from orders where pending=0 and status=1 and bill_process=1 and Date(date) >= ? and Date(date) <= ?";
    		$result = $this->con->prepare($sql);
    		$result->execute(array($from,$to));
    		while($row = $result->fetch(PDO::FETCH_ASSOC)){
    			//getting category
    			if($row['drinks_subcategory']==0){
    				continue;
    			}else{
    				$category = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
    				if($category[3] == $categoryId){
    					//update count
    					$data[0] += $row['quantity'];
    					$data[1] += ($row['quantity'] * $this->getOrderAmountPaid($row['tid'],$row['price']));
    				}
    			}
    		}
    	}
    	return $data;
    }

    function getOrderAmountPaid($tid,$unitPrice){
    	$amount = $unitPrice;
    	$sql = "select * from processed_bill where tid=? limit 1";
    	$result = $this->con->prepare($sql);
    	$result->execute(array($tid));
    	$details = $result->fetch();
    	//service charge
    	$amount += $details[3];

    	//vat
    	$amount += $this->formatNumber(floatval($details[4]*$unitPrice)/100);
    	//nhil
    	$amount += $this->formatNumber(floatval($details[5]*$unitPrice)/100);
    	//gtbl
    	$amount += $this->formatNumber(floatval($details[6]*$unitPrice)/100);

    	//discount
    	$amount -= $this->formatNumber(floatval($details[2]*$unitPrice)/100);
    	return $this->formatNumber($amount);
    }

    function getProcessedBillAmountPaid($tid){
    	$sql = "select * from processed_bill where tid=? limit 1";
    	$result = $this->con->prepare($sql);
    	$result->execute(array($tid));
    	$details = $result->fetch();
    	$amount = $this->formatNumber($details[9]);
    	$initialAmount = $this->formatNumber($amount);
    	//service charge
    	$amount += $details[3];

    	//vat
    	$amount += $this->formatNumber(floatval($details[4]*$initialAmount)/100);
    	//nhil
    	$amount += $this->formatNumber(floatval($details[5]*$initialAmount)/100);
    	//gtbl
    	$amount += $this->formatNumber(floatval($details[6]*$initialAmount)/100);

    	//discount
    	$amount -= $this->formatNumber(floatval($details[2]*$initialAmount)/100);
    	return $this->formatNumber($amount);
    }

    function previewProcessedBill(){
        //setting processedBill sesssion array
        $_SESSION['processedBill'] = array();


        $sql = "select tid, date, pid from processed_bill order by date desc";
        $result = $this->con->query($sql);
        $data = "<div class='row' style='margin-top: 0px; margin: 0px;'>
                  <div class='col m12 l8 s12 offset-l2'>
                    <div class=''>
                      <table id='tableList' class='mdl-data-table'>
                        <thead>
                          <tr>
                            <th></th>
                            <th>ORDER #</th>
                            <th>DATE</th>
                            <th>WAITER</th>
                            <th></th>
                          </tr>
                        </thead>

                        <tbody>";
        $counter = 1;
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $userDetails = $this->getFullDetailsId($row['pid'], "login");
                $data.="<tr>
                    <td><center>".$counter."</center></td>
                    <td>".$row['tid']."</td><td>".$row['date']."</td><td>".$userDetails[3]."</td><td>
                    <div class='input-field center-align'>
                                <button type='button' onclick=\"editProcessedBill('".$row['tid']."')\" class='waves-effect waves-light btn blue' style='margin: 5px;'>
                                  <i class='material-icons left'>border_color</i>
                                </button>
                                <button type='button' onclick=\"deleteProcessedBill('".$row['tid']."')\" class='waves-effect waves-light btn red' style='margin: 5px;'>
                                  <i class='material-icons right'>delete</i>
                                </button>
                    </div>

                    </td></tr>";
                $counter++;
            }

        //adding js code
        $data.="
            <script>
                function editProcessedBill(tid){
                    $.post('ajax.php',{'setProcessedBillTid':tid}, function(data){
                        if(data==1){
                            window.location.assign('?processedBill&edit');
                        }
                    });
                }


                function deleteProcessedBill(tid){
                    alertify.confirm('Gold Coast Restaurant','Delete?', function(e){
                        $.post('ajax.php',{'deleteProcessedBill': tid}, function(data){
                            if(data == 1){
                                alertify.alert('Gold Coast Restaurant', 'Process Completed');
                                window.location.assign('?processedBill');
                            }else{
                                alert.alert('Gold Coast Restaurant', 'Process failed.. Try again');
                            }
                        });
                    }, function(c){

                    });
                }
            </script>
        ";

        $data.="</tbody></table>";
        $data.="<script>
                    $('#tableList').DataTable({
                       columnDefs: [
                            {
                                targets: [ 0, 1, 2 ],
                                className: 'mdl-data-table__cell--non-numeric'
                            }
                        ],
                        'pageLength': 5
                    });
                    $('#mdl-button--colored').addClass('blue');
                </script>";
        echo $data;
    }

    function deleteProcessedBill($tid){
        $tid = $this->sanitize($tid);
        $sql = "delete from processed_bill where tid=?";
        $result = $this->con->prepare($sql);
        if($result->execute(array($tid))){
            $query = "update orders set status=2 where tid=?";
            $res = $this->con->prepare($query);
            if($res->execute(array($tid))){
                echo 1;
            }else{
                echo 0;
            }
        }else{
            echo 0;
        }
    }

    function editProcessedBill(){
        $totalAmount = 0.0;
        $tid = $this->sanitize($_SESSION['processedBillTid']);
        $tax_service_charge = $this->getProcessedBillTaxServiceCharge($tid);
        $service_charge = $this->formatNumber($tax_service_charge[1]);
        $vat = $this->formatNumber($tax_service_charge[2]);
        $nhil = $this->formatNumber($tax_service_charge[3]);
        $gtbl = $this->formatNumber($tax_service_charge[4]);
        $discount = $_SESSION['discount'];
        $mode_of_payment = $_SESSION['mode_of_payment'];

        $tableDetails = $this->getTableDetailsTid($tid);
        $data = "<div class='row'>
                      <div class='table-responsive'>
                        <table class='centered bordered'>
                          <caption class='blue lighten-3 white-text'>
                            <p class='flow-text bold-300'>ORDER " . $tid . " | " . $tableDetails . "</p>
                          </caption>

                          <thead>
                            <tr>
                              <th>ITEMS</th>
                              <th>UNIT PRICE</th>
                              <th>QUANTITY</th>
                              <th>TOTAL</th>
                              <th>REMARKS</th>
                            </tr>
                          </thead>

                          <tbody>";

        $sql = "select * from orders where tid=? and status=1 and pending=0 and bill_process=1";
        $result = $this->con->prepare($sql);
        $result->execute(array($tid));
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['food_subcategory'] == 0) {
                $categoryDetails = $this->getFullDetailsId($row['drinks_subcategory'], "drinks_subcategory");
            } else {
                $categoryDetails = $this->getFullDetailsId($row['food_subcategory'], "food_subcategory");
            }
            $total = floatval($this->formatNumber($row['price'])) * $row['quantity'];
            $totalAmount += $total;
            $data .= "<tr><td>" . $categoryDetails[1] . "</td><td>" . $this->formatNumber($row['price']) . "</td><td>" . $row['quantity'] . "</td><td>" . $this->formatNumber($total) . "</td><td>" . $row['remarks'] . "</td></tr>";
        }

        $discount = $this->formatNumber($totalAmount * ($discount) / 100);
        $orderTotal = $this->formatNumber($totalAmount);
        $subTotal = $this->formatNumber($orderTotal + $service_charge - $discount);
        $vat = $this->formatNumber(($vat / 100) * $subTotal);
        $nhil = $this->formatNumber(($nhil / 100) * $subTotal);
        $gtbl = $this->formatNumber(($gtbl / 100) * $subTotal);
        $grandTotal = $this->formatNumber($subTotal + $vat + $nhil + $gtbl);
        //process discount js function
        $data .= "<script>
                        function processDiscount(){
                            var discount = $('#discount').val();
                            $.post('ajax.php',{'setDiscount':discount},function(data){
                                //console.log(data);
                            });
                            loadItems();
                        }

                        function processBill(){
                            //console.log('working');
                            var mode_of_payment = $('#mode_of_payment').val();
                            if(mode_of_payment == 0){
                                alertify.alert('Gold Coast Restaurant','Please select a mode of payment!!!');
                                return;
                            }
                            alertify.confirm('Gold Coast Restaurant','Save?',function(e){
                                if(e){
                                    var tid = '" . $_SESSION['processedBillTid'] . "';
                                    var discount = $('#discount').val();
                                    //sending data to server
                                    $.post('ajax.php',{'completeProcessedBill':'y','tid':tid,'discount':discount,'mode_of_payment':mode_of_payment},function(data){
                                        if(data == 1){
                                            displayMessage('Processing Complete..',1);
                                            window.location.assign('?processedBill');
                                        }else{
                                            displayMessage('Process failed..',0);
                                            loadItems();
                                        }
                                    });
                                }
                            },function(e){
                                //error
                            });
                        }
                    </script>";

        $data .= "      </tbody>
                        </table>
                      </div>


                      <div class='blue lighten-3 white-text margin-t padding-in'>
                        <p class='center-align'>MODE OF PAYMENT</p>
                      </div>
                      <div class='row'>
                        <div class='col m10 l6 s12 offset-l3 offset-m1'>
                            <input type='hidden' id='mode_of_payment' value='0'/>
                            <div class='table-responsive'>
                              <table>
                                <thead>
                                  <tr>
                                    <th>#</th>
                                    <th>MODE OF PAYMENT</th>
                                  </tr>
                                </thead>
                                <tbody>";

        //setting mode of payment
        $data.="<script>
                    $('#mode_of_payment').attr('value','".$mode_of_payment."');
                </script>";

        $sql11 = "select * from mode_of_payment";
        $result11 = $this->con->query($sql11);
        $total11 = $result11->rowCount();
        $list = array();
        $count11 = 0;
        while($row = $result11->fetch(PDO::FETCH_ASSOC)){
            $data.="<tr><td><input type='checkbox' id='md".$row['id']."'><label for='md".$row['id']."'></label></td><td>".$row['name']."</td></tr>";
            $list[$count11] = $row['id'];

            //add js code
            $data.="<script>
                        $('#md".$row['id']."').change(function(){
                            if(this.checked){
                                $('#mode_of_payment').attr('value','".$row['id']."');
                                uncheckOthers('".$row['id']."');
                                ";

            $data.="        }else{
                                $('#mode_of_payment').attr('value','0');
                            }
                        });
                    </script>";
            $count11++;
        }


        $data.="<script>
                   document.getElementById('md".$mode_of_payment."').checked = true;

                    function uncheckOthers(id){
                        //console.log('working');";

                        for($i = 0; $i < sizeof($list); $i++){
                            $data.="
                                document.getElementById('md".$list[$i]."').checked = false;
                            ";
                        }
        $data.="            document.getElementById('md'+id+'').checked = true;";
        $data.="        }
                </script>";


        $data.="                </tbody>
                              </table>
                            </div>
                        </div>
                      </div>


                      <div class='blue lighten-3 white-text margin-t padding-in'>
                        <p class='center-align'>TAX/SERVICE CHARGE</p>
                      </div>

                      <div class='row'>
                        <div class='col s12 m10 offset-m1 no-padding'>
                          <div class='row'>
                            <div class='col s4' style='height: 250px'>
                              <div class='valign-wrapper inherit-height'>
                                <form action='#' method='post'>
                                    <p for='' class='bold-800 grey-text text-darken-2'><small>DISCOUNT</small></p>

                                    <div class='row negative-margins'>
                                      <div class='col s12'>
                                          <input class='with-gap' name='discount' type='radio' id='percentage' checked>
                                          <label for='percentage'>Percentage</label>
                                      </div>
                                      <!--<div class='col s6'>
                                          <input class='with-gap' name='discount' type='radio' id='amount'>
                                          <label for='amount'>Amount</label>
                                      </div>-->

                                      <div class='input-field'>
                                        <input type='number' step='any' id='discount' value='" . $this->formatNumber($_SESSION['discount']) . "' min='0' max='100' class='validate center-align'>
                                      </div>

                                      <div class='input-field center-align'>
                                        <button type='button' name='process' onclick=\"processDiscount()\" class='btn blue waves-effect waves-light'>
                                          <i class='material-icons left'>cached</i> Process Discount
                                        </button>
                                      </div>
                                    </div>
                                </form>
                              </div>
                            </div>
                            <div class='col s8'>
                              <table class='bold-600 remove-excess-padding'>
                                <thead>
                                  <th width='50%' class='right-align'>ORDER TOTAL</th>
                                  <th width='50%' class='center-align'>" . $this->formatNumber($orderTotal) . "</th>
                                </thead>

                                <tbody>
                                  <tr>
                                    <td class='right-align'>SERVICE CHARGE</td>
                                    <td class='center-align'>" .$service_charge. "</td>
                                  </tr>
                                  <tr>
                                    <td class='right-align'>DISCOUNT</td>
                                    <td class='center-align'>" .$discount. "</td>
                                  </tr>
                                  <tr>
                                    <td class='right-align'>SUB TOTAL</td>
                                    <td class='center-align'>" .$subTotal. "</td>
                                  </tr>
                                  <tr>
                                    <td class='right-align'>VAT AMOUNT</td>
                                    <td class='center-align'>" .$vat. "</td>
                                  </tr>
                                  <tr>
                                    <td class='right-align'>NHIL AMOUNT</td>
                                    <td class='center-align'>" .$nhil. "</td>
                                  </tr>
                                  <tr>
                                    <td class='right-align'>GTBL AMOUNT</td>
                                    <td class='center-align'>" .$gtbl. "</td>
                                  </tr>
                                </tbody>

                                <tfoot>
                                  <tr>
                                    <td class='right-align'>GRAND TOTAL</td>
                                    <td class='center-align'>" .$grandTotal. "</td>
                                  </tr>
                                </tfoot>
                              </table>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class='row'>
                        <div style='text-align: center;'>
                            <div class='col s12'>
                              <div class='input-field center-align'>
                                <button type='button' onclick=\"processBill()\" class='waves-effect waves-light btn green'>
                                  <i class='material-icons left'>save</i>Save
                                </button>
                              </div>
                            </div>
                        </div>
                    </div>";
        echo $data;
    }

    function setProcessedBillTid($tid){
        $tid = $this->sanitize($tid);
        $_SESSION['processedBillTid'] = $tid;
        echo 1;
    }

    function completeProcessedBill(){
        $tid = $this->sanitize($_POST['tid']);
        $discount = $this->sanitize($_POST['discount']);
        $mode_of_payment = $this->sanitize($_POST['mode_of_payment']);
        $sql = "update processed_bill set discount=?, mode_of_payment=? where tid=?";
        $result = $this->con->prepare($sql);
        if($result->execute(array($discount, $mode_of_payment, $tid))){
            unset($_SESSION['processedBillTid']);
            echo 1;
        }else{
            echo 0;
        }
    }

    function loadBackup($table){
        $table = $this->sanitize($table);
        if($table == "sysbackup"){
            $fileLocation = "backups/sys/";
            $name = "system";
        }else{
            $fileLocation = "backups/db/";
            $name = "db";
        }
        $data="<table class='table table-bordered table-condensed table-striped table-hover' id='tableList'>";
        $data.="<thead>
                <tr><th><center>No.</center></th><th><center>File Name</center></th><th><center>File Size</center></th><th><center>Date</center></th>";
        if($table == "dbbackup"){
            $data.="<th></th>";
        }
        $data.="<th></th></tr>
                </thead><tbody>";
        $sql = "select * from ".$table." order by date desc";
        $result = $this->con->query($sql);
        $count = 1;
        while($row = $result->fetch(PDO::FETCH_ASSOC)){
            if(!file_exists($fileLocation.$row['filename'])){
                continue;
            }
            $data.="<tr><td><center>".$count."</center></td><td><center>".$row['filename']."</center></td><td><center>".$this->fsize($fileLocation.$row['filename'])."</center></td><td><center>".$row['date']."</center></td>";
            if($table == "dbbackup"){
                $data.="<td><form method='post' style='margin: 0px; padding: 0px;' action='?backups&db' id='dbRestore".$row['id']."' class='form'>
                            <div style='text-align: center;'>
                                <button type='button' onclick=\"restore".$row['id']."()\" class='btn btn-xs btn-default br tooltip-bottom' title='Restore Database' style='background-color: #607D8B; color: #fff;'><span class='fa fa-upload'></span> Restore Database</button>
                                <input type='hidden' name='restoreBtn' value='".$row['filename']."'/>
                            </div>
                        </form></td>";
                //js code
                $data.="<script>
                            function restore".$row['id']."(){
                                alertify.confirm('Gold Coast Restaurant', 'Restore?', function(y){
                                    $('#dbRestore".$row['id']."').submit();
                                    progress();
                                }, function(c){});
                            }
                        </script>";
            }
            $data.="<td>";
            $data.="<center>";

            $data.="<button onclick=\"window.location='".$fileLocation.$row['filename']."'\" class='btn btn-xs btn-info br'><span class='fa fa-download'></span></button>&nbsp;";
            $data.="<button onclick=\"deleteReq('".$row['id']."','".$table."','?backups&".$name."')\" class='btn btn-xs btn-danger br'><span class='fa fa-remove'></span></button>";
            $data.="</center>";
            $data.="</td></tr>";
            $count++;
        }

        $data.="</tbody></table>";
        echo $data;
    }

    function sysBackup(){
        //detecting operating system
        $os = $this->detectOs();
        if($os == "windows"){
            $root = ROOT_PATH_WINDOWS;
            $mysql = MYSQL_PATH_WINDOWS;
            $mysqldump = MYSQLDUMP_PATH_WINDOWS;
            $zipPath = $root."cms\backups\sys\\";
        }else{
            //assumes linux
            $root = ROOT_PATH_LINUX;
            $mysql = MYSQL_PATH_LINUX;
            $mysqldump = MYSQLDUMP_PATH_LINUX;
            $zipPath = $root."cms/backups/sys/";
        }


        // system file backup
        $filename = date("Ymdhis").".zip";
        $lastBackup = $this->getLastBackup("sysbackup");

        if($this->zipData($root, $zipPath.$filename)){
            if(filesize($zipPath.$lastBackup[1]) !== null){
                if(filesize($zipPath.$lastBackup[1]) == filesize($zipPath.$filename)){
                    unlink($zipPath.$filename);
                    $this->displayMsg2("No changes detected in system files", 1);
                }else{
                    $this->addBackupList($filename, "sysbackup");
                     $this->displayMsg2("Backup Completed", 1);
                }
            }else{
                $this->addBackupList($filename, "sysbackup");
                $this->displayMsg2("Backup Completed", 1);
            }
        }
        $this->redirect("?backups&system");
    }

    function zipData($source, $destination) {
        if (extension_loaded('zip')) {
            if (file_exists($source)) {
                $zip = new ZipArchive();
                if ($zip->open($destination, ZIPARCHIVE::CREATE)) {
                    $source = realpath($source);
                    if (is_dir($source)) {
                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
                        foreach ($files as $file) {
                            $file = realpath($file);
                            if (is_dir($file)) {
                                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                            } else if (is_file($file)) {
                                //checking file extension
                                $ext = explode(".", $file);
                                $extension = strtolower(end($ext));
                                if($extension !== "zip"){
                                    if($extension !== "sql"){
                                        $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                                    }
                                }
                            }
                        }
                    } else if (is_file($source)) {
                        //checking file extension
                        $ext = explode(".", $file);
                        $extension = strtolower(end($ext));
                        if($extension !== "zip"){
                            if($extension !== "sql"){
                                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                            }
                        }
                    }
                }
                return $zip->close();
            }
        }
        return false;
    }

    function addBackupList($filename,$table){
        $filename = $this->sanitize($filename);
        $table = $this->sanitize($table);
        $sql = "insert into ".$table."(filename) values(?)";
        $result = $this->con->prepare($sql);
        $result->execute(array($filename));
    }

    function fsize($filepath){
        $bytes = filesize($filepath);
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    function dbBackup(){
        //detecting operating system
        $os = $this->detectOs();
        if($os == "windows"){
            $root = ROOT_PATH_WINDOWS;
            $mysql = MYSQL_PATH_WINDOWS;
            $mysqldump = MYSQLDUMP_PATH_WINDOWS;
            $dbPath = $root."cms\backups\db\\"; # two slashes becomes of windows path resolution
        }else{
            //assumes linux
            $root = ROOT_PATH_LINUX;
            $mysql = MYSQL_PATH_LINUX;
            $mysqldump = MYSQLDUMP_PATH_LINUX;
            $dbPath = $root."cms/backups/db/";
        }

        // database backup
        $lastBackup = $this->getLastBackup("dbbackup");
        $filename = date("Ymdhis").".sql";
        $command = sprintf("".$mysqldump." --opt -h%s -u%s -p%s --databases %s > ".$dbPath."%s",HOST,DB_USERNAME,DB_PASSWORD, DB_NAME, $filename);
        system($command);
        if(filesize($dbPath.$lastBackup[1]) !== null){
                if(filesize($dbPath.$lastBackup[1]) == filesize($dbPath.$filename)){
                    unlink($dbPath.$filename);
                }else{
                    $this->addBackupList($filename, "dbbackup");
                }
        }else{
            $this->addBackupList($filename, "dbbackup");
        }
        $this->redirect("?backups&db");
    }

    function getBackupTime(){
        $sql = "select * from backuptime limit 1";
        $result = $this->con->query($sql);
        return $result->fetch();
    }

    function updateBackupTime($hours){
        $hours = $this->sanitize($hours);
        $sql = "update backuptime set hours=?";
        $result = $this->con->prepare($sql);
        if($result->execute(array($hours))){
            $this->displayMsg("Backup Time updated..", 1);
        }else{
            $this->displayMsg("Process failed..", 0);
        }
        $this->redirect("?backups&crontasks");
    }

    function dbRestore($filename){
        //detecting operating system
        $os = $this->detectOs();
        if($os == "windows"){
            $root = ROOT_PATH_WINDOWS;
            $mysql = MYSQL_PATH_WINDOWS;
            $mysql = MYSQL_PATH_WINDOWS;
            $dbPath = $root."cms\backups\db\\";
        }else{
            //assumes linux
            $root = ROOT_PATH_LINUX;
            $mysql = MYSQL_PATH_LINUX;
            $mysql = MYSQL_PATH_LINUX;
            $dbPath = $root."cms/backups/db/";
        }

        // database backup
        $filename = $this->sanitize($filename);
        $command = sprintf("".$mysql." -h%s -u%s -p%s < ".$dbPath."%s",HOST,DB_USERNAME,DB_PASSWORD, $filename);
        system($command);
        $this->redirect("?backups&db");
    }

    #####################################################################################################################


    ##### CRON TASK ################################################33
    function getLastBackup($table){
        $table = $this->sanitize($table);
        $sql = "select * from ".$table." order by date desc limit 1";
        $result = $this->con->query($sql);
        return $result->fetch();
    }
    function backgroundTask(){
        $path = __DIR__."/";

        //detecting operating system
        $os = $this->detectOs();
        if($os == "windows"){
            $root = ROOT_PATH_WINDOWS;
            $mysql = MYSQL_PATH_WINDOWS;
            $mysqldump = MYSQLDUMP_PATH_WINDOWS;
            $zipPath = $root."cms\backups\sys\\";
            $dbPath = $root."cms\backups\db\\";
        }else{
            //assumes linux
            $root = ROOT_PATH_LINUX;
            $mysql = MYSQL_PATH_LINUX;
            $mysqldump = MYSQLDUMP_PATH_LINUX;
            $zipPath = $root."cms/backups/sys/";
            $dbPath = $root."cms/backups/db/";
        }


        // system file backup
        $filename = date("Ymdhis").".zip";
        $lastBackup = $this->getLastBackup("sysbackup");

        if($this->zipData($root, $zipPath.$filename)){
            if(filesize($zipPath.$lastBackup[1]) !== null){
                if(filesize($zipPath.$lastBackup[1]) == filesize($zipPath.$filename)){
                    unlink($zipPath.$filename);
                }else{
                    $this->addBackupList($filename, "sysbackup");
                }
            }else{
                $this->addBackupList($filename, "sysbackup");
            }
        }


        // database backup
        $lastBackup = $this->getLastBackup("dbbackup");
        $filename = date("Ymdhis").".sql";
        $command = sprintf("".$mysqldump." --opt -h%s -u%s -p%s --databases %s > ".$dbPath."%s",HOST,DB_USERNAME,DB_PASSWORD, DB_NAME, $filename);
        system($command);
        if(filesize($dbPath.$lastBackup[1]) !== null){
                if(filesize($dbPath.$lastBackup[1]) == filesize($dbPath.$filename)){
                    unlink($dbPath.$filename);
                }else{
                    $this->addBackupList($filename, "dbbackup");
                }
        }else{
            $this->addBackupList($filename, "dbbackup");
        }
    }

    function genCronConfigurationFiles(){
        $backupTime = $this->getBackupTime();
        $path = "backups/";

        ## Linux configuration
        $handler = fopen($path."gcr", "w");
        $data = "0 */".$backupTime[1]." * * * root ".PHP_PATH_LINUX." ".CRON_FILE_PATH_LINUX."\n";
        fwrite($handler, $data);
        fclose($handler);

        ## Windows Configuration
        $handle = fopen($path."cron.bat", "w");
        $data = "".PHP_PATH_WINDOWS. " " . CRON_FILE_PATH_WINDOWS."\n";
        fwrite($handle, $data);
        fclose($handle);

        $handle = fopen($path."gcr.bat", "w");
        $data = "schtasks /delete /tn 'GCR' /F\n";
        $data .= "schtasks /create /sc hourly /mo ".$backupTime[1]." /tn 'GCR' /tr ".ROOT_PATH_WINDOWS."cms\backups\cron.bat\n";
        fwrite($handle, $data);
        fclose($handle);

    }

    function setVTable(){
        $ptid = $this->genTransactionId();
        $_SESSION['splitTid'] = $ptid;
        $_SESSION['vtable'] = $ptid;
        echo 1;
    }
}//end class