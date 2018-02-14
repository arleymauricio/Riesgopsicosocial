<!-- start breadcrumb -->
<div class="row">
	<div class="col-sm-12">
		<ol class="breadcrumb">
			<li><a href="#">Home</a></li>
			<li class="active">Registro de Actividades</li>
		</ol>
	</div>
</div>
<!-- end breadcrumb -->

<div class="row">
	<div class="col-sm-12">
		<table id="activity_log_data" class="table table-striped table-bordered"
			cellspacing="0" width="100%">
			<thead>
				<tr>
					<th>Fecha</th>
                    <th>Actividad</th>
					<th>Detalle</th>
				</tr>
			</thead>
		</table>
	</div>
</div>


<!-- start own script-->
<script>
    var table;
	$(document).ready(function() {
        table = $('#activity_log_data').DataTable({
            "select": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.13/i18n/Spanish.json"
            },
            "ajax": {
                "url": "http://riesgopsicosocial.azurewebsites.net/index.php/api/rquestionary/list_activity_log_by_company_id",
                "type": "GET",
                "data" : {
                    "company_id" : company_id,
                }

            },
            "initComplete": function( settings, json ) {
                $("#activity_log_filter").append("&nbsp;&nbsp;<button id='refresh' "
                    +"class='btn btn-button' "
                    +"data-loading-text='Actualizando...'><span class='glyphicon glyphicon-refresh'></span></button>");
                $("#refresh").click(function(){
                    table.ajax.reload( null, false );
                    var $this = $(this);
                    $this.button('loading');
                    setTimeout(function() {
                        $this.button('reset');
                    }, 3000);
                });
            },
            "showRefresh": true,
            "sAjaxDataProp" : "response",
            "columns": [
                {
                    "data": "log_date"
                },
                {
                    "data": "activity_name"
                },
                {
                    "data": "activity_info",
                }
            ],
            "order": [1,"desc"]
        });
		} );
		</script>
<!-- end own script -->