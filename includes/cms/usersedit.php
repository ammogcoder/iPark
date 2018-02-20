<?php
    if(!isset($_SESSION['useredit'])){
        $this->redirect("?users");
    }else{
        $details=$this->getFullDetailsId($_SESSION['useredit'],"login");
    }
?>
<div class="row" style="margin: 15px;">
    <div style="text-align: center;"><legend><span class="glyphicon glyphicon-user"></span> Waiters/Waitresses</legend></div>
</div>
<div class="row" id="displayRes"></div>
<div class="row" style="margin: 15px;">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #035888; color: #fff;">
                <center><h3 class="panel-title"><span class="glyphicon glyphicon-pencil"></span> Edit Details</h3></center>
            </div>
            <div class="modal-body">
                <form method="post" action="?users&edit" class="form" id="userEdit">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" placeholder="Username" class="form-control" value="<?php echo $details[1]; ?>" required/>
                    </div>
                    <div class="form-group">
                        <label for="fullname">Full Name:</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Full Name" class="form-control" value="<?php echo $details[3]; ?>" required/>
                    </div>
                    <div class="form-group">
                        <label for="mobileNo">Mobile Number:</label>
                        <input type="text" id="mobileNo" value="<?php echo $details[4]; ?>" name="mobileNo" placeholder="Mobile Number" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <center><button type="submit" name="updateUserBtn" value="<?php echo $_SESSION['useredit']; ?>" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-pencil"></span> Update Details</button>&nbsp;<a href='?users' class="btn btn-xs btn-danger br tooltip-bottom" title="Close/Exit" style="text-decoration: none;"><span class="glyphicon glyphicon-remove"></span> Close</a></center>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
    if(isset($_POST['updateUserBtn'])){
        $this->updateUserProfile($_SESSION['useredit'],$_POST['username'],$_POST['fullname'],$_POST['mobileNo'],"?users");
    }
?>

<script type="text/javascript">
    $(function(){
        $('#userEdit').bootstrapValidator({
            message: 'This is not valid',
            feedbackIcons: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields:{
                fullname:{
                    validators:{
                        notEmpty:{
                            message: 'Full Name can\'t be empty'
                        },
                        stringLength:{
                            min: 5,
                            max: 100,
                            message: 'Invalid input length'
                        }
                    }
                },
                username:{
                    validators:{
                        notEmpty:{
                            message: 'Username can\'t be empty'
                        },
                        stringLength:{
                            min: 5,
                            max: 100,
                            message: 'Invalid input length'
                        }
                    }
                },
                mobileNo:{
                    validators:{
                        notEmpty:{
                            message: 'Mobile Number can\'t be empty'
                        },
                        stringLength:{
                            min: 10,
                            max: 15,
                            message: 'Invalid mobile number'
                        },
                        regexp:{
                            regexp: /^[0-9\+]+$/,
                            message: 'Invalid mobile number'
                        }
                    }
                }
            }
        });
    });
</script>