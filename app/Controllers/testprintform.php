<?php

namespace App\Controllers;

use Config\Database as ConfigDatabase;

$session = session();

if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotfound();
	die ("Login failure");
}

ini_set('display_errors', 'On');

error_reporting(E_ALL | E_STRICT);

class testprintform extends BaseController {
	 public function index()
        {
			
                return view('attendeelogintest', ['error' => ' ']);
        }
		
		
		function checkuser($graphics = TRUE)
 {
	 echo "test3";
 echo $_Post["email"];
 echo $_Post["code"];
// Here append the common URL characters. 
$link .= "://"; 
  
// Append the host(domain name, ip) to the URL. 
$link .= $_SERVER['HTTP_HOST']; 
  
// Append the requested resource location to the URL 
$link .= $_SERVER['REQUEST_URI']; 
      
// Print the link 
echo $link;
$id = ltrim($link, "https://www.testconx.org/form.php/badgemesa/TestConXsingle/");
echo  $id;


	
	$db  = \Config\Database::connect('registration');
	$builder = $db->table('guests');
	$builder->select('NameOnBadge,GivenName,CN_Company,Company,Email,EventYear,FamilyName,ContactID,
	InvitedByCompanyID,Control,HardCopy,Tutorial,Type,Message,Dinner');
	
	$builder->where('ContactID', $id);
	
	$builder->orderBy('FamilyName ASC, GivenName ASC');
	



	$query = $builder->get();
	$people = $query->getNumRows();
	
	$results = $query->getResultArray();
	
	 $height = '152.4';
 	$width = '101.6';
	
	// $height = '158.75';
// 	$width = '107.95';
	
$pageLayout = array($width, $height);



$pdf = new \TCPDF('P', 'MM',$pageLayout, true, 'UTF-8', false);
$pdf->SetTitle('Badge Single');
$pdf->SetMargins(10,10,10,10);
$pdf->SetHeaderMargin(0);
$pdf->SetTopMargin(0);
$pdf->setFooterMargin(0);
$pdf->SetAutoPageBreak(true);
$pdf->SetAuthor('Author');
$pdf->SetDisplayMode('real', 'default');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$graphics = TRUE;
//$pdf->AddPage('P',$pageLayout);
		$q=0; 
		$filler=0;
		$pages=0;
		
for($i=1; $i<=$people; $i++){ 
		$n = $i-1;
		// Define what special labels go on the badges
		$label ="0";
		
		
		if (!empty($results[$n]["GivenName"])){
		$this->qrstamp($results[$n]["GivenName"]." ".$results[$n]["FamilyName"],$results[$n]["Email"],$results[$n]["Company"],$n);
		}	
	
		$NameOnBadge=$results[$n]["NameOnBadge"];
		
		$GivenName=$results[$n]["GivenName"];
		echo "<h1>".$GivenName."</h1>";
		
		
		//$CN_Company=$results[$n]["CN_Company"];
		$FamilyName=$results[$n]["FamilyName"];
		$EventYear=$results[$n]["EventYear"];
		$Company=$results[$n]["Company"];
		$ContactID=$results[$n]["ContactID"];
		$InvitedByCompanyID=$results[$n]["InvitedByCompanyID"];
		$HardCopy=$results[$n]["HardCopy"];
		$Tutorial=$results[$n]["Tutorial"];
		$Control=$results[$n]["Control"];
		$Message=$results[$n]["Message"];
		$Dinner=$results[$n]["Dinner"];
		$type = $results[$n]["Type"];
		
		$pdf->AddPage('P',$pageLayout);
		$Dinnertext="";
		if($HardCopy==1){
		$HardCopy="HC";
		}
		else{
		$HardCopy="0";
		}
		if($EventYear == "tinyml2024"){
			if($Tutorial==1){
			$Tutorial="SYMPOSIUM";
			}
			else{
			$Tutorial="";
			}
			}
			
		if($EventYear == "tinyml2024"){
			if($Dinner==1){
			$Dinnertext="Dinner";
			}
			else{
			$Dinnertext="";
			}
			}
		if($EventYear == "emea2024"){
			if($Tutorial==1){
			$Tutorial="Social";
			}
			else{
			$Tutorial="";
			}
			}	
		if($EventYear == "emea2024"){
			if($Dinner==1){
			$Dinnertext="Dinner";
			}
			else{
			$Dinnertext="";
			}
			}
			
			/* if($Tutorial==1){
			$Tutorial="TUTORIAL";
			}
			else{
			$Tutorial="";
			}
			 */
		
		$pdf->SetFont('stsongstdlight', 'B', 75);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('helvetica', 'B', 75);
		$pdf->Ln(40);
	if (!empty($results[$n]["NameOnBadge"])){
		$pdf->SetFont('helvetica', 'B', 55);
		$pdf->Cell(0, 0, $NameOnBadge, 0, 1, 'C', 0, '', 1);
		
		$pdf->SetFont('stsongstdlight', 'B', 25);

			
	}
	else if (!empty($results[$n]["GivenName"])){
		$pdf->SetFont('helvetica', 'B', 55);
		$pdf->Cell(0, 0, $GivenName, 0, 1, 'C', 0, '', 1);
		
		$pdf->SetFont('stsongstdlight', 'B', 25);
		
		
	}
	else {
		$pdf->SetFont('stsongstdlight', 'B', 55);
		
		
	}
		$pdf->SetFont('stsongstdlight', 'B', 25);
	
		$pdf->SetFont('helvetica', 'B', 25);
		if(strlen($FamilyName)>8){
		$pdf->SetFont('helvetica', 'B', 22);
		}
		$pdf->Cell(0, 0,$GivenName." ".$FamilyName, 0, 1, 'C', 0, '', 1);
		$pdf->SetFont('helvetica', 'B', 25);
		if(strlen($Company)>12){
		$pdf->SetFont('helvetica', 'B', 17);
		}
		$pdf->Cell(0, 0,$Company, 0, 1, 'C', 0, '', 1);
		
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/tmpqr/'.$n.'08.png', 7,115, 30, 30, 'PNG', '', '',false, 1000, '', false, false, 1, false, false, false);
		
		$pdf->SetFillColor(224,146,47);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('helvetica', 'B', 15);
		$pdf->setCellPaddings(0, 6, 0, 0);
		
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(0,0,0);
		
		if($type != 'Professional'){
			if($type != "EXPOtiny"){
		$pdf->SetFont('helvetica', 'B', 16);
		$pdf->Cell(0, 0,'Ask me about:', 0, 1, 'C', 0, '', 1);
			if(strlen($Message)>8){
			$pdf->SetFont('helvetica', 'B', 22);
		}
		
		$pdf->Cell(0, 0,$Message, 0, 1, 'C', 0, '', 1);
		}
		}
		
		if($type=="EXPOtiny"){
		$pdf->SetFont('helvetica', '', 40);
		$pdf->MultiCell(45, 20,"EXPO", 0, 'L', 0, 0, 45,120, true);
		}
		
		$pdf->SetFont('helvetica', '', 8);
		
		
		
		$pdf->MultiCell(100,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -2.5,142, true);
			
		 $q++;
		 		 
	 
	 
	 
	 }
ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');

exit();		
}
		
		
			
	





}