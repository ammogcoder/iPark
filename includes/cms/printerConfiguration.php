<?php 
	 $printer = $this->getPrinterConfiguration()
?>
<div class="row" style="margin: 15px;">
	<div style="text-align: center;"><legend><span class="glyphicon glyphicon-print"></span> Printer Configuration</legend></div>
</div>

<div id="displayRes" class="row"></div>

<div class="row" style="margin: 15px;">
    <div class="col-md-4"></div>
    <div class="col-md-4">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bgblue">
                    <h3 class="panel-title" style="text-align: center;"><span class="glyphicon glyphicon-print"></span> Configuration</h3>
                </div>
                <div class="modal-body">
                    <form method="post" action="#" class="form" id="printerForm">
                        <div class="form-group">
                            <label for="ip">IP Address:</label>
                            <input type="text" id="ip" name="ip" placeholder="e.g. 192.168.1.1" class="form-control" value="<?php echo $printer[1]; ?>" required=""/>
                            <input type="hidden" name="id" value="<?php echo $printer[0]; ?>"/>
                        </div>
                        <div class="form-group">
                            <label for="port">Port:</label>
                            <input type="number" id="port" name="port" placeholder="e.g. 9001" class="form-control" value="<?php echo $printer[2]; ?>" required=""/>
                        </div>
                        <div class="form-group">
                            <div style="text-align: center;">
                                <button type="submit" name="updateBtn" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-pencil"></span> Update Configuration</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4"></div>
</div>

<?php
    if(isset($_POST['updateBtn'])){
        $this->updatePrinterConfiguration();
    }
?>

<script type="text/javascript">
    $(function(){
        $('#printerForm').bootstrapValidator({
            message: 'This is not valid',
            feedbackIcons: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields:{
                ip:{
                    validators:{
                        notEmpty:{
                            message: 'IP Address can\'t be empty'
                        },
                        stringLength:{
                            min: 7,
                            max: 15,
                            message: 'Invalid input length'
                        },
                         regexp:{
                            regexp: /^[0-9\.]+$/,
                            message: 'Invalid IP Address'
                        }
                    }
                },
                port:{
                    validators:{
                        notEmpty:{
                            message: 'Port Address can\'t be empty'
                        },
                        stringLength:{
                            min: 2,
                            max: 5,
                            message: 'Invalid input length'
                        },
                        regexp:{
                            regexp: /^[0-9]+$/,
                            message: 'Invalid Port Address'
                        }
                    }
                }
            }
        });
    });
</script>