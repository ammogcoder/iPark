<div class="row" style="margin: 15px;">
    <div style="text-align: center;"><legend><span class="glyphicon glyphicon-print"></span> Summary Report</legend></div>
</div>

<div id="displayRes" class="row"></div>

<div class="row" style="margin: 15px;">
    <div class="col-md-2"></div>
    <div class="col-md-8">
        <form class="form-inline">
            <div class="form-group">
                <label for="from">From:</label>
                <input type="text" id="from" class="form-control day" onchange="loadItems()" name="from" placeholder="From.." value="<?php echo $this->genDay(); ?>" readonly="" style="background-color: white; color: #333;"/>
            </div>
            <div class="form-group">
                <label for="to">To:</label>
                <input type="text" id="to" class="form-control day" onchange="loadItems()" name="to" placeholder="To.." value="<?php echo $this->genDay(); ?>" readonly="" style="background-color: white; color: #333;"/>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-xs btn-success" id="printBtn" style="margin: 50px;"><span class="fa fa-print"></span> Print</button>
            </div>
        </form>
    </div>
    <div class="col-md-2"></div>
</div>

<div class="row" style="margin: 15px;">
    <div class="col-md-2"></div>
    <div class="col-md-8">
        <div style="text-align: center;" id="loadItems"></div>
    </div>
    <div class="col-md-2"></div>
</div>

<script>
    function printDocument(pdf){
        alertify.confirm('Stratek Solutions','Print?',function(e){
            if(e){
                // yes
                $.post('ajax.php',{'printDocument':pdf},function(data){
                    if(data == 1){
                        alertify.alert('Stratek Solutions','File added to print queue..');
                    }else{
                       alertify.alert('Stratek Solutions','Process failed..');
                        //alertify.alert('Stratek Solutions',data);
                    }
                });
            }
        },function(e){
            //error
        });
    }

    $(function(){
        $('#printBtn').click(function(){
            var from = $('#from').val();
            var to = $('#to').val();
            var url = "http://" + window.location.host + window.location.pathname.split('index.php')[0];
            url += "print.php?report&summary&from="+from+"&to="+to;
            printDocument(url);
        });
    });
    function loadItems(){
        var from = $('#from').val();
        var to = $('#to').val();
        $('#loadItems').html("<embed src='print.php?report&summary&from="+from+"&to="+to+"' width='500' height='500' type='application/pdf'>");
    }
    //default
    loadItems();
</script>