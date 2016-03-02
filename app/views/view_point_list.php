<script type="text/javascript">
$(document).ready(function(){
//************************************//
	$( "#dialog" ).dialog({
		autoOpen: false, modal: true, width: 400,
		buttons: [{text: "Закрыть", click: function() {$( this ).dialog( "close" );}}]
	});

// Creating grid1
	$("#grid1").jqGrid({
		sortable: true,
		url:"../engine/jqgrid3?action=point_list_full&f1=ClientID&f2=CurrentIP&f3=DateAct&f4=AppVersion&f5=1C&f6=BalanceActivity&f7=NameShort&f8=NameValid&f9=City&f10=Address\n\
			&f11=Telephone&f12=Label&f13=CountTerminal&f14=PriceType&f15=Matrix&UserID=<?php echo $_SESSION['UserID']; ?>",
//		url:"../lists/get_points_list?param=list",
		datatype: "json",
		height:'auto',
		width:'auto',
		colNames:['Код','IP адрес','Дата ост.','Версия','1C','Контроль','Торговая точка','Название','Город','Адрес','Телефон','Вид собств.','К-во комп.','Тип цены','Матрица'],
		colModel:[
			{name:'ClientID',	index:'ClientID', width: 60, align:"center", sorttype:"text", search:true},
			{name:'CurrentIP',	index:'CurrentIP', width: 100, align:"center", sorttype:"text", search:true},
			{name:'DateAct',	index:'DateAct',  width: 80, align:"center", sorttype:"date", search:true},
			{name:'AppVersion',	index:'AppVersion',width:90, align:"center", sorttype:"text", search:true},
			{name:'1С',			index:'1C',		  width: 50, align:"center", sorttype:"text", search:true},
			{name:'BalanceActivity',index:'BalanceActivity',width: 50, align:"center", sorttype:"text", search:true},
			{name:'NameShort',	index:'NameShort',width:150, sorttype:"text", search:true},
			{name:'NameValid',	index:'NameValid',width:120, sorttype:"text", search:true},
			{name:'City',		index:'City',	  width: 80, sorttype:"text", search:true},
			{name:'Address',	index:'Address',  width:130, sorttype:"text", search:true},
			{name:'Telephone',  index:'Telephone',width: 80, sorttype:"text", search:true},
			{name:'Label',		index:'Label',	  width: 70, align:"left", search:true},
			{name:'Status',		index:'Status',	  width: 50, align:"left", search:true},
			{name:'PriceType',	index:'PriceType',width: 50, align:"center", search:true},
			{name:'Matrix',		index:'MatrixID', width: 70, align:"left", search:false}
		],
		shrinkToFit:false,
//		loadonce: true,
//		rowNum:10000000,
		rowNum:20,
		rowList:[20,30,40,50,100],
		sortname: "ClientID",
		viewrecords: true,
		gridview : true,
		toppager: true,
		caption: "Список торговых точек",
		pager: '#pgrid1',
//		grouping: true,
//		groupingView : { 
//			groupField : ['City','Version'],
//			groupColumnShow : [true,true],
//			groupText : ['<b>{0}</b>'],
//			groupCollapse : false,
//			groupOrder: ['asc','asc'],
//			//groupSummary : [true,true]
//	    }
	});
	$("#grid1").jqGrid('navGrid','#pgrid1', {edit:false, add:false, del:false, search:false, refresh: true, cloneToTop: true});
	$("#grid1").navButtonAdd('#grid1_toppager', {
		title: 'Открыть информационную карту', buttonicon: "ui-icon-pencil", caption: 'Открыть информационную карту', position: "last",
		onClickButton: function () {
		    var id = $("#grid1").jqGrid('getGridParam', 'selrow');
		    var node = $("#grid1").jqGrid('getRowData', id);
		    //console.log(id,node,node.Name);
		    if (id != null) {
				window.location = "../lists/point_info?clientID=" + id;
			} else {
				$("#dialog>#text").html('Сначала выберите запись в таблице!');
				$("#dialog").dialog("open");
		    }
		}
    });
	$("#grid1").jqGrid('filterToolbar', { autosearch: true,	searchOnEnter: true	});

	$("#pg_pgrid1").remove();
	$("#pgrid1").removeClass('ui-jqgrid-pager');
	$("#pgrid1").addClass('ui-jqgrid-pager-empty');

	//клавиатура
	$("#grid1").jqGrid('bindKeys', {"onEnter":function( rowid ) { alert("You enter a row with id:"+rowid)} } );

	//$("#grid1").draggable();
	$("#grid1").gridResize();
});
</script>
<div class="container-fluid center-block center min570">
	<div id='div1' class='frameL ml40 pt5 center-block'>
		<table id="grid1" ></table>
		<div id="pgrid1"></div>
	</div>
</div>
<div id="dialog" title="ВНИМАНИЕ!">
	<p id='text'></p>
</div>
