<div class="row" style="margin: 15px;">
	<div style="text-align: center;"><legend><span class="glyphicon glyphicon-user"></span> Management System Users</legend></div>
</div>
<div class="row" style="margin: 5px;">
	<div class="col-md-3">
		<center><a href="#addUser" data-backdrop="static" data-toggle="modal" class="btn btn-xs btn-primary br"><span class="glyphicon glyphicon-plus-sign"></span> Add User</a></center>
	</div>
	<div class="col-md-3">
		<center><a href="#delUsers" data-toggle="modal" class="btn btn-xs btn-danger br"><span class="glyphicon glyphicon-remove-sign"></span> Delete All Users</a></center>
	</div>
	<div class="col-md-3">
		<center><a href="#deactivate" data-toggle="modal" class="btn btn-xs btn-warning br"><span class="glyphicon glyphicon-eject"></span> Activate/Deactivate All Users</a></center>
	</div>
	<div class="col-md-3">
		<center><a href="#resetPassword" data-toggle="modal" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-lock"></span> Reset Password</a></center>
	</div>
</div>
<div class="row" id="displayRes" style="margin: 15px;"></div>
<div class="row" style="margin: 15px;">
	<?php $this->loadUsers(0,"?systemUsers"); ?>
</div>

<div id="addUser" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #035888; color: #fff;">
				<center><h3 class="panel-title"><span class="glyphicon glyphicon-user"></span> Management System Users</h3></center>
			</div>
			<div class="modal-body">
				<form method="post" action="?systemUsers" class="form" id="addUserForm">
					<div class="row" style="margin: 5px;">
						<div class="form-group">
							<label for="fullname">Full Name:</label>
							<input type="text" id="fullname" name="fullname" class="form-control" placeholder="Full Name" required>
						</div>
						<div class="form-group">
							<label for="username">Username:</label>
							<input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
						</div>
						<div class="form-group">
							<label for="password">Password:</label>
							<input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
						</div>
						<div class="form-group">
							<label for="password1">Confirm Password:</label>
							<input type="password" id="password1" name="password1" class="form-control" placeholder="Confirm Password" required>
						</div>
						<div class="form-group">
							<label for="mobileNo">Mobile Number:</label>
							<input type="text" id="mobileNo" name="mobileNo" class="form-control" placeholder="Mobile Number" required>
						</div>
						<div class="form-group">
							<center><button type="submit" name="addUserBtn" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-plus-sign"></span> Add User</button>&nbsp;<a href="#" data-dismiss="modal" class="btn btn-xs btn-danger br"><span class="glyphicon glyphicon-remove"></span> Close</a></center>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php 
	if(isset($_POST['addUserBtn'])){
		$this->addAdminUser($_POST['username'],$_POST['password'],$_POST['fullname'],$_POST['mobileNo'],0,"?systemUsers");
	}elseif(isset($_POST['activateBtn'])){
		$this->activateAccount($_POST['activateBtn'],1,"login","?systemUsers",0);
	}elseif(isset($_POST['deactivateBtn'])){
		$this->activateAccount($_POST['deactivateBtn'],0,"login","?systemUsers",0);
	}elseif(isset($_POST['deleteBtn'])){
		$this->deleteUserAccount($_POST['deleteBtn'],"login","?systemUsers",0);
	}elseif(isset($_POST['chngpwdBtn'])){
		$this->resetUserPassword($_POST['user'],$_POST['password'],"login","?systemUsers");
	}
?>

<!--deleting all users -->
<div id="delUsers" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #035888; color: #fff;">
				<center><h3 class="panel-title"><span class="glyphicon glyphicon-user"></span> Delete All Accounts</h3></center>
			</div>
			<div class="modal-body">
				<form method="post" action="?systemUsers" class="form">
					<div class="row" style="margin: 15px;">
						<div class="col-md-6">
							<center>
								<form method="post" action="?systemUsers" class="form">
									<button type="submit" name="deleteBtn" value="all" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-ok"></span> Delete All Users</button>
								</form>
							</center>
						</div>
						<div class="col-md-6">
							<center><a href="#" data-dismiss="modal" style="text-decoration: none;" class="btn btn-xs btn-danger br"><span class="glyphicon glyphicon-remove"></span> Close</a></cener>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<!--reset password -->
<div id="resetPassword" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #035888; color: #fff;">
				<center><h3 class="panel-title"><span class="glyphicon glyphicon-lock"></span> Change Account Password</h3></center>
			</div>
			<div class="modal-body">
				<form method="post" action="?systemUsers" class="form" id="chngpwdForm">
					<div class="form-group">
						<label for="user">User:</label>
						<select name="user" id="user" class="form-control" required>
							<?php 
								$this->genUsersOption(0);
							?>
						</select>
					</div>
					<div class="form-group">
						<label for="password">Password:</label>
						<input type="password" id="password" name="password" class="form-control" placeholder="Password" required/>
					</div>
					<div class="form-group">
						<label for="password1">Confirm Password:</label>
						<input type="password" id="password1" name="password1" class="form-control" placeholder="Confirm Password" required/>
					</div>
					<div class="form-group">
						<center><button type="submit" name="chngpwdBtn" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-pencil"></span> Update Password</button></center>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!--deactivate all accounts -->
<div id="deactivate" class="modal fade">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #035888; color: #fff;">
				<center><h3 class="panel-title"><span class="glyphicon glyphicon-user"></span> Deactivate All Accounts</h3></center>
			</div>
			<div class="modal-body">
					<div class="row" style="margin: 15px;">
						<div class="col-md-6">
							<center>
								<form method="post" action="?systemUsers" class="form">
									<button type="submit" name="deactivateBtn" value="all" class="btn btn-xs btn-danger br"><span class="glyphicon glyphicon-remove"></span> Deactivate</button>
								</form>
							</center>
						</div>
						<div class="col-md-6">
							<center>
								<form method="post" action="?systemUsers" class="form">
									<button type="submit" name="activateBtn" value="all" class="btn btn-xs btn-success br"><span class="glyphicon glyphicon-ok"></span> Activate</button>
								</form>
							</center>
						</div>
					</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(function(){
		$('#chngpwdForm').bootstrapValidator({
			message: 'This is not valid',
			feedbackIcons: {
				valid: 'glyphicon glyphicon-ok',
				invalid: 'glyphicon glyphicon-remove',
				validating: 'glyphicon glyphicon-refresh'
			},
			fields:{
				user:{
					validators:{
						notEmpty:{
							message: 'Full Name can\'t be empty'
						},
						stringLength:{
							min: 1,
							max: 100,
							message: 'Invalid input length'
						}
					}
				},
				password:{
					validators:{
						notEmpty:{
							message: 'Password can\'t be empty'
						},
						stringLength:{
							min: 5,
							max: 100,
							message: 'Invalid input length'
						},
						identical:{
							field: 'password1',
							message: 'Passwords don\'t match'
						}
					}
				},
				password1:{
					validators:{
						notEmpty:{
							message: 'Confirm Password can\'t be empty'
						},
						stringLength:{
							min: 5,
							max: 100,
							message: 'Invalid input length'
						},
						identical:{
							field: 'password',
							message: 'Passwords don\'t match'
						}
					}
				}
			}
		});

		$('#addUserForm').bootstrapValidator({
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
				password:{
					validators:{
						notEmpty:{
							message: 'Password can\'t be empty'
						},
						stringLength:{
							min: 5,
							max: 100,
							message: 'Invalid input length'
						},
						identical:{
							field: 'password1',
							message: 'Passwords don\'t match'
						}
					}
				},
				password1:{
					validators:{
						notEmpty:{
							message: 'Confirm Password can\'t be empty'
						},
						stringLength:{
							min: 5,
							max: 100,
							message: 'Invalid input length'
						},
						identical:{
							field: 'password',
							message: 'Passwords don\'t match'
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