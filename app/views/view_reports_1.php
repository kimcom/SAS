<script type="text/javascript">
var table;
$(document).ready(function () {
	$("#dialog").dialog({
		autoOpen: false, modal: true, width: 600, height: 300,
		buttons: [{text: "Закрыть", click: function () {
			    $(this).dialog("close");
			}}]
	});
	$("#dialog_progress").dialog({
		autoOpen: false, modal: true, width: 400, height: 400,
		show: {effect: "explode", duration: 1000},
		hide: {effect: "explode", duration: 1000}
    });
	dt = new Date();
	dt.setMonth(dt.getMonth() - 1, 1);
	//alert(dt.toLocaleDateString()+' '+dt.toLocaleTimeString());
	$("#DT_start").datepicker({
		showOn: "both", 
		numberOfMonths: 3,
		showButtonPanel: true, 
		dateFormat: 'dd/mm/yy',
		showWeek: true,
	});
	$("#DT_start").datepicker("setDate", dt);
	dt = new Date();
	dt.setDate(0);
    $("#DT_stop").datepicker({
		showOn: "both", 
		numberOfMonths: 3,
		showButtonPanel: true,
		dateFormat: 'dd/mm/yy',
		showWeek: true,
	});
	$("#DT_stop").datepicker("setDate", dt);
	$(".ui-datepicker-trigger").addClass("hidden-print");
	
	$('#example2')
		.removeClass('display')
		.addClass('table table-striped table-bordered');

	table = $('#example2').dataTable({
		"searching": false,
		"paging": false,
		"info": false,
		"ordering": true,
//		"processing": true,
//		"serverSide": true,
		"order": [[ 1, "asc" ]],
//		"language": {
//            "decimal": " ",
//		    "thousands": "."
//	     },
//		"ajax": "../reports/report1_data"
		autoWidth: false,
		columnDefs: [
			{ title: "№ маг.",				width: 50, className: "TAC", targets: [ 0 ] },
			{ title: "Название",			width: 150, className: "TAL", targets: [ 1 ] },
			{ title: "Город",				width: 150, className: "TAL", targets: [ 2 ] },
			{ title: "Средн. сумма чека в день",	width: 50, className: "TAR", targets: [ 3 ] },
			{ title: "Средн. кол-во чеков в день", width: 50, className: "TAR", targets: [ 4 ] },
			{ title: "Дата начала работы",	width: 60, className: "TAC", targets: [ 5 ] },
			{ title: "Дата заверш. работы",	width: 60, className: "TAC", targets: [ 6 ] },
			{ title: "К-во раб. дней",		width: 50, className: "TAR", targets: [ 7 ] },
	    ]
    }).api();
	$('#example2').dataTable().on('xhr.dt', function (e, settings, data) {
		$("#dialog_progress").dialog("close");
	});
	$('#example2').on('preXhr.dt', function (e, settings, data) {
		$('#timestamp').html('');
		table.clear().draw();
	});
	$('#example2').on('xhr.dt', function (e, settings, json) {
		dt = new Date();
		$('#timestamp').html('Отчет сформирован: ' + dt.toLocaleDateString() + ' ' + dt.toLocaleTimeString());
	});
	$("#button_submit").click(function() {
		$("#dialog_progress").dialog("option", "title", 'Ожидайте! Выполняется формирование отчета...');
	    $("#dialog_progress").dialog("open");
		table.ajax.url('../reports/report1_data?DT_start=' + $("#DT_start").val() + '&DT_stop=' + $("#DT_stop").val() + "&UserID=<?php echo $_SESSION['UserID']; ?>").load();
	});
});
</script>
<div class="container center min570">
	<legend>Отчет по торговым точкам. Средняя сумма чека и среднее кол-во чеков в день.</legend>
	<p></p>
	<label class='w70' for='DT_start'>Период с:</label>
	<input class='w80' type="text" id="DT_start" name="DT_start">
	<label class='w20' for='DT_stop'>по:</label>
	<input class='w80' type="text" id="DT_stop" name="DT_stop">
	<button id="button_submit" class="btn btn-xs btn-success hidden-print" class1="hidden-print ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only ui-state-focus">
		<span class="ui-button-text" style1='width:120px;height:22px;'>Сформировать</span>
	</button>
	<p></p>
	<table id="example2" class="display">
	</table>
	<p id="timestamp" class="small"></p>
</div>
<div id="dialog" title="ВНИМАНИЕ!">
	<p id='text'></p>
</div>
<div id="dialog_progress" title="Ожидайте!">
	<img class="ml30 mt20 border0 w300" src="../../img/progress_circle5.gif">
</div>
