<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
  
<style type='text/css'>
body
{
    font-family: Arial;
    font-size: 14px;
}
a {
    color: blue;
    text-decoration: none;
    font-size: 14px;
}
a:hover
{
    text-decoration: underline;
}

/* DivTable.com */
.divTable{
	display: table;
	width: 100%;
}
.divTableRow {
	display: table-row;
}
.divTableHeading {
	background-color: #EEE;
	display: table-header-group;
}
.divTableCell, .divTableHead, .divTableTotal {
	border: 1px solid #999999;
	display: table-cell;
	padding: 3px 10px;
}
.divTableHeading {
	background-color: #EEE;
	display: table-header-group;
	font-weight: bold;
}
.divTableFoot {
	background-color: #EEE;
	display: table-footer-group;
	font-weight: bold;
}
.divTableBody {
	display: table-row-group;
}

</style>
</head>
<body>
<?php 
$session = session(); 
echo $data['title'];



?>
<!-- Beginning header -->

<!-- End of header-->

    <div>
    
 		<h1>TestConX</h1>
 		
		<h2><? $session = session(); 
		echo $data['title']; ?></h2>
		
		<div class="divTable">
			<div class="divTableHeading">
				<div class="divTableRow">
					<? foreach($header as $x) {
						echo '<div class="divTableHead">' . $x . '</div>';
					} ?>
				</div>
			</div>
			
			<div class="divTableBody">
				<? foreach($table as $x ) {
					echo '<div class="divTableRow">';
					foreach($x as $y => $y_value) {
						echo '   <div class="divTableCell">'. $y_value . '</div>';
					}
					echo '</div>';				
					
				 // " . $x_value . "<br>";
				} ?>
 			</div>
 			
 			<div class="divTableFoot">
 				<? foreach($totals as $t) { 
					echo '<div class="divTableRow">';
					foreach($t as $x) {
							echo '<div class="divTableTotal">' . $x . '</div>';
					}
					echo '</div>';
				} ?>
			</div>
 		</div>

    </div>


<!-- Beginning footer -->
<div></div>
<!-- End of Footer -->
</body>
</html>