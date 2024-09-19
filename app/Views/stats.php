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

<!-- Beginning header -->

<!-- End of header-->

    <div>
    
 		<h1>TestConX</h1>
 		
		
		
<?php 


		$session = session(); 
		echo esc($title);
		
 
		?>
		<div class="divTable">
			<div class="divTableHeading">
				<div class="divTableRow">
					<?php foreach ($header as $x): ?>

						<div class="divTableHead"><?= esc($x) ?></div>

					<?php endforeach ?>
				</div>
			</div>
		<div class="divTableBody">	
					<?php foreach ($table as $x): ?>
						<div class="divTableRow">
						<?php foreach ($x as $y => $y_value): ?>
						<div class="divTableCell"><?= $y_value ?></div>
						<?php endforeach ?>
						</div>
					<?php endforeach ?>
					</div>
					
		<div class="divTableFoot">
			<?php foreach ($totals as $t): ?>
				<div class="divTableRow">
				<?php foreach ($t as $x): ?>	
					<div class="divTableTotal"><?= esc($x) ?></div>
				<?php endforeach ?>
				</div>
			<?php endforeach ?>
					
			</div>
 		</div>

    </div>


<!-- Beginning footer -->
<div></div>
<!-- End of Footer -->
</body>
</html>