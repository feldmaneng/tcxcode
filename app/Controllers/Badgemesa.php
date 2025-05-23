<?php 


namespace App\Controllers;

//include(APPPATH.'Libraries/GroceryCrudEnterprise/autoload.php');



/* 
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Files\File;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Controller;
 */


use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;

$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

}
 
class Badgemesa extends BaseController {

  
	function __construct()
	{
		//parent::__construct();
		helper('text');
		
	}
 
	public function index()
	{
		
		echo "<h1>Tinyml Badges - tinyML Office use only</h1>";
		echo "<h4>tinyml Summit Confidential</h4>";
		echo "<OL>";
		echo "<LI>Manage <a href=" . site_url('/badgemesa/testconxguests') . ' target="_blank" ">Manage badge</a></LI>';
		
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgestinymlProfessional') . ">Summit</a></LI>";
		//echo "<LI>Print <a href=" . site_url('/badgemesa/BadgestinymlSymposium') . ">Symposium</a></LI>";
		//echo "<LI>Print <a href=" . site_url('/badgemesa/BadgestinymlEXPOONLY') . ">EXPOtinyml ONLY</a></LI>";
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgestinymlBlankProfessional') . ' target="_blank" ">Blank Summit</a></LI>';
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgestinymlBlankExhibitor') . ' target="_blank" ">Blank Symposium</a></LI>';
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgestinymlBlankEXPO') . ' target="_blank" ">Blank EXPOtinyml ONLY</a></LI>';
		echo "<LI>Clear <a href=" . site_url('/badgemesa/clearprint') . ">To Print flag</a></LI>";
		
		echo"</OL>";
		
		echo "<h1>Tinyml EMEA Badges - tinyML Office use only</h1>";
		echo "<h4>tinyml EMEA Confidential</h4>";
		echo "<OL>";
		echo "<LI>Print <a href=" . site_url('/badgemesa/Badgenumber') . ">BadgeNumber</a></LI>";
		echo "<LI>Print <a href=" . site_url('/badgemesa/Related') . ">Related China</a></LI>";
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgesEMEAAttendee') . ">Attendee</a></LI>";
		
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgesEMEABlankAttendee') . ' target="_blank" ">Blank Attendee</a></LI>';
		
		echo"</OL>";
 		
 		
		echo "<h1>TestConX Badges - TestConX Office use only</h1>";
		echo "<h4>TestConX Workshop Confidential</h4>";
		echo "<OL>";
		echo "<LI>Manage <a href=" . site_url('/badgemesa/testconxguests') . ' target="_blank" ">Manage badge</a></LI>';
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgesMesaProfessional') . ">Professional</a></LI>";
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgesMesaExhibitor') . ">Exhibitor</a></LI>";
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgesMesaEXPOONLY') . ">EXPO ONLY</a></LI>";
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgesMesaBlankProfessional') . ' target="_blank" ">Blank Professional</a></LI>';
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgesMesaBlankExhibitor') . ' target="_blank" ">Blank Exhibitor</a></LI>';
		echo "<LI>Print <a href=" . site_url('/badgemesa/BadgesMesaBlankEXPO') . ' target="_blank" ">Blank EXPO ONLY</a></LI>';
		echo "<LI>Clear <a href=" . site_url('/badgemesa/clearprint') . ">To Print flag</a></LI>";
		echo "</OL>";
		echo "<br><br>";
		
	}
	 

    private function _getDbData() {
        $db = (new ConfigDatabase())->registration;
        return [
            'adapter' => [
                'driver' => 'Pdo_Mysql',
                'host'     => $db['hostname'],
                'database' => $db['database'],
                'username' => $db['username'],
                'password' => $db['password'],
                'charset' => 'utf8'
            ]
        ];
    }
	private function _getGroceryCrudEnterprise($bootstrap = true, $jquery = true) {
        $db = $this->_getDbData();

        $config = (new ConfigGroceryCrud())->getDefaultConfig();

        $groceryCrud = new GroceryCrud($config, $db);
        return $groceryCrud;
    }    
	
	function testconxguests()
	{
	
		$crud = $this->_getGroceryCrudEnterprise();

        $crud->setCsrfTokenName(csrf_token());
        $crud->setCsrfTokenValue(csrf_hash());
       
		
        $crud->setTable('guests');
        $crud->setSubject('Guest', 'Guests');
		$crud->where([
    'guests.EventYear' => 'mesa2025'
]);
		//echo site_url('/badgemesa/TestConXsingle/');
		$crud->columns(['EventYear','ToPrint','GivenName','FamilyName','NameOnBadge','Email','Company','Type','Tutorial']);
		//$crud->columns(['Email','GivenName']);

		//$crud->uniqueFields(['ContactID']);

		//$this->grocery_crud->add_action('Print Badge', '', site_url('/badgemesa/TestConXsingle/'),'ui-icon-image'); 
		//$crud->setActionButton('Print Badge', 'fa fa-user', site_url('/badgemesa/TestConXsingle/'));
		//$crud->setActionButton('Print Badge', 'fa fa-user', site_url('/badgemesa/TestConXsingle/'),true);
		//$crud->setActionButton('Print Badge', 'fa fa-user', site_url('/badgemesa/TestConXsingle/'));

		$crud->setActionButton('Print Badge', 'fa fa-user', function ($row) {
    			return site_url('/badgemesa/TestConXsingle/') . $row->ContactID;
		});

		// Try restricting fields...
		//$crud->fields(['Email','GivenName']);
		
		$crud->fields(['EventYear','ToPrint','GivenName','FamilyName','NameOnBadge','Company','Email','Type','Tutorial','Dinner','Message','MasterContactID','NoShow']);
		$crud ->fieldtype('Type','enum',['Professional','EXPO','Exhibitor','Summit','Symposium','EXPOtiny']);
		$crud ->fieldtype('ToPrint','enum',['Yes','No']);
		$crud ->fieldtype('Dinner','enum',['1','0']);
		$crud ->fieldtype('EventYear','enum',['mesa2025']);

		
		
	
		$output = $crud->render();

	return $this->_example_output($output);  

		
	


	} 
/* function _example_output($output = null)
 
{
	$this->load->view('bits_template.php',$output);    
}  */

private function _example_output($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

        return view('testconx_template.php', (array)$output);
    }
 function qrstamp2($a)
 {
	

	$tempDir = $_SERVER["DOCUMENT_ROOT"]."/tmpqr/"; 
	//$filename = 'testconx'.$a.'.png';
   
	/* $codeContents  = 'BEGIN:VCARD'."\n"; 
	$codeContents .= 'VERSION:3.0'."\n";
    $codeContents .= 'NOTE:'.$a."\n";
	$codeContents .= 'END:VCARD'; */
	
    $codeContents = strval($a);
	$fileName = $a.'.png';
    
	
	
	return \QRcode::png($codeContents, $tempDir.$fileName);
	//return \QRcode::png($codeContents);
}
//4080
function BadgeNumber($start= 3727,$end = 4500)
 {
	 for($i=$start;$i<=$end;$i++)
	 {
		 $this->qrstamp2($i);
		 echo "Badge ".$i." printed <br>";
	 }
	 
	 
	 
}




function TestConXsingle($graphics = TRUE)
 {
 if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    $link = "https"; 
else
    $link = "http"; 
  
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
	
	// IRA POSITION
				$height = '150';
			$width = '101.6';
			$pageLayout = array($width, $height);



		$pdf = new \TCPDF('P', 'MM',$pageLayout, true, 'UTF-8', false);
		$pdf->SetTitle('Badge Single');
		$pdf->SetMargins(0,0,0,0);
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
			$time = date('Y-m-d H:i:s');
			//date('Y-m-d H:i:s')
			$data = [
			'PrintTime' => $time,
			];

			$builder->where('ContactID', $results[$n]["ContactID"]);
			$builder->update($data);
			
				// Define what special labels go on the badges
				$label ="0";
			if (!empty($results[$n]["NameOnBadge"])){
				
				$NameOnBadge=$results[$n]["NameOnBadge"];
					
			}
			else {
				
				$NameOnBadge=$results[$n]["GivenName"];
				
			}
			
				
				
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
				$Email = $results[$n]["Email"];
				//$ChineseName = $results[$n]["ChineseName"];
				
				$pdf->AddPage('P',$pageLayout);
				$Dinnertext="";
				
				
				
					
					 if($Tutorial==1){
					$Tutorial="TUTORIAL";
					}
					else{
					$Tutorial="";
					}
					 
				//$pdf->Button('print', 30, 10, 'Print Badge', 'Print()', array('lineWidth'=>2, 'borderStyle'=>'solid', 'fillColor'=>array(255, 255, 255), 'strokeColor'=>array(0, 0, 0)));

			
				$pdf->Ln(40);
				
				
				//here is where we need the fonts
				
				// convert TTF font to TCPDF format and store it on the fonts folder
			//$fontname = TCPDF_FONTS::addTTFfont(site_url('tcxcode/ThirdParty/NotoSerifKR-VariableFont_wght.ttf'), 'TrueTypeUnicode', '', 96);
			//hysmyeoungjostdmedium
			//hysmyeongjostdmedium.php
			// use the font
			if($EventYear == "Korea2024"){
			$pdf->SetFont('cid0kr', '', 55,);
			}
			else if($EventYear == "China2024"){
				$pdf->SetFont('cid0cs', '', 55,);
			}
			else{
				$pdf->SetFont('helvetica', 'B', 55);
			}
			
				$pdf->Cell(0, 0, $NameOnBadge, 0, 1, 'C', 0, '', 1);
				
			
				$pdf->SetFont('helvetica', 'B', 25);
				if(strlen($FamilyName)>8){
				$pdf->SetFont('helvetica', 'B', 22);
				}
				$pdf->Cell(0, 0,$GivenName." ".$FamilyName, 0, 1, 'C', 0, '', 1);
				$pdf->SetFont('helvetica', 'B', 25);
				if(strlen($Company)>12){
				$pdf->SetFont('helvetica', 'B', 17);
				}
				if(strlen($Company)>30){
				$pdf->SetFont('helvetica', 'B', 12);
				}
				/*if(strlen($Company)>12){
				$pdf->SetFont('stsongstdlight', 'B', 17);
				}*/
				$pdf->Cell(0, 0,$Company, 0, 1, 'C', 0, '', 1);
				
				
				
				$pdf->SetFillColor(224,146,47);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFont('helvetica', 'B', 15);
				$pdf->setCellPaddings(0, 6, 0, 0);
				
				$pdf->setCellPaddings(0, 0, 0, 0);
				$pdf->SetFillColor(255,255,255);
				$pdf->SetTextColor(0,0,0);
				
				
				
			
				
				$pdf->SetFont('helvetica', '', 8);
				$Control = substr($Control, -4);
				
			
				$pdf->MultiCell(100,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'L', 0, 0, 7,140, true);
				
				
				// new style
				$style = array(
					'border' => 0,
					'padding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => array(255,255,255)
				);
				
				
				$codeContents  = 'BEGIN:VCARD'."\n";
				$codeContents .= 'VERSION:3.0'."\n";
				$codeContents .= 'N:'.$FamilyName.";".$GivenName.";;;\n";
				if (!empty($results[$n]["NameOnBadge"])){
					$codeContents .= 'NICKNAME:'.$NameOnBadge."\n";
				}
				 				
				$codeContents .= 'FN:'.$GivenName." ".$FamilyName."\n";
				$codeContents .= 'EMAIL:'.$Email."\n"; 
				$codeContents .= 'ORG:'.$Company."\n"; 
				$codeContents .= 'END:VCARD'; 
				
				 $code="Name: ".$GivenName." ".$FamilyName."\n"
				."Email: ".$Email."\n"
				."Company: ".$Company; 
				
				//$code="3880";
				// QRCODE,H : QR-CODE Best error correction
				//QR CODE IRA POSITION
				//$pdf->write2DBarcode($codeContents, 'QRCODE,L', x position, y position, x size, y size, $style, 'N');
				$pdf->write2DBarcode($codeContents, 'QRCODE,L', 7, 110, 30, 30, $style, 'N');
				
					
				 $q++;
						 
			 
			 
			 
			 }
ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');
//echo($pdf);
exit();		
}
function qrstamp($a,$b,$c,$d)
 {
	
//include('phpqrcode/qrlib.php'); 
//file_put_contents("test5.png",file_get_contents("test6.png"));

/* $codeContents  = 'BEGIN:VCARD'."\n"; 
	$codeContents .= 'VERSION:3.0'."\n";
    $codeContents .= 'NOTE:'.$a."\n";
	$codeContents .= 'END:VCARD'; */
	
	
	$tempDir = $_SERVER["DOCUMENT_ROOT"]."tmpqr/" . $d; 
 
    $name = $a; 
	$email= $b;
	$orgName = $c; 
	$codeContents  = 'BEGIN:VCARD'."\n"; 
    $codeContents .= 'FN:'.$name."\n";
	$codeContents .= 'EMAIL:'.$email."\n"; 
	$codeContents .= 'ORG:'.$orgName."\n"; 
    $codeContents .= 'END:VCARD'; 
	
	//return QRcode::svg($codeContents,false, $tempDir.'08.svg', QR_ECLEVEL_L, false,false); 
	return \QRcode::png($codeContents, $tempDir.'08.png', QR_ECLEVEL_L, 200,0);
	}
	
private function printBadge ($label, $nickname, $firstname, $lastname,$company, $QRfile,$type) {

echo '<div id = "rect1"><h1>';

		
			echo $nickname;
			echo '</h1><p><h3 class="small">'. $firstname. " " .$lastname."<br>".$company."</h3></p>";
			//echo '<p class="bar">'.$type.'</p>';
			echo '<img class="logo" src="/images/TestConX-China-Black_750x133.png" height= 50px alt = "TestConx Logo">';
			echo '<img class="qr" src=/tmpqr/'.$QRfile."08.png".' height= 75px    alt = "Qr Code">';
			echo '<p class="barbottom">Suzhou - October 23, 2018</p></div>';
			

}

private function printPDFBadge ($label, $nickname, $firstname, $lastname,$company, $QRfile,$type) {



$pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetTitle('My Title');
$pdf->SetHeaderMargin(0);
$pdf->SetTopMargin(0);
$pdf->setFooterMargin(0);
$pdf->SetAutoPageBreak(true);
$pdf->SetAuthor('Author');
$pdf->SetDisplayMode('real', 'default');

$pdf->AddPage();

			$html= '<div id = "rect1"><h1>';
			$html.= $nickname;
			$html.= '</h1><p><h3 class="small">'. $firstname. " " .$lastname."<br>".$company."</h3></p>";
			$html.= '<img class="logo" src="/images/TestConX-China-Black_750x133.png" height= 50px alt = "TestConx Logo">';
			$html.= '<img class="qr" src=/tmpqr/'.$QRfile."08.png".' height= 75px    alt = "Qr Code">';
			$html.= '<p class="barbottom">Suzhou - October 23, 2018</p></div>';
			
$pdf->writeHTML($html, true, 0, true, 0);

}

private function printEXPOBadge ($label, $nickname, $firstname, $lastname,$company, $QRfile,$type) {
	echo '<div id = "rect1">';

		
			echo "<h1>".$nickname;
			echo '</h1><img class="logo" src="/images/TestConX-China-Black_750x133.png" height= 50px alt = "TestConx Logo">';
			echo '<img class="qr" src=/tmpqr/'.$QRfile."08.svg".' height= 75px    alt = "Qr Code">';
			echo'<p><h3 class="small">'. $firstname. " " .$lastname."<br>".$company."</h3></p>";
			switch ($label) {
    case "0":
        echo '<p class="Tutorial">&nbsp;</p>';
        break;
    case "1":
        echo '<p class="Tutorial">Tutorial</p>';
        break;
    case "2":
        echo '<p class="Tutorial">Companion</p>';
        break;
    default:
        echo '<p class="Tutorial">&nbsp;</p>';
}
			echo'<p class ="Expo">&nbsp;<br>EXPO ONLY</p></div>';
			
			
}
    



function Testbadge($convention = "testconx",$event = "test2022", $graphics = FALSE,$type = "Professional")
{
	
	$db      = \Config\Database::connect('registration');
	$builder = $db->table('guests');
	$builder->select('NameOnBadge,GivenName,CN_Company,Company,Email,EventYear,FamilyName,ContactID,InvitedByCompanyID,Control,HardCopy,Tutorial,Type,Message,Dinner');
	$builder->where('EventYear', $event);
	$builder->where('ToPrint', 'Yes');
	$builder->where('Type', $type);
	$builder->orderBy('FamilyName ASC, GivenName ASC');
	

	$query = $builder->get();
	$people = $query->getNumRows();
	
	$results = $query->getResultArray();
	
	$height = '158.75';
	$width = '107.95';
$pageLayout = array($width, $height);


$pdf = new \TCPDF('P', 'MM',$pageLayout, true, 'UTF-8', false);
$pdf->SetTitle('My Title');
$pdf->SetMargins(10,10,10,10);
$pdf->SetHeaderMargin(0);
$pdf->SetTopMargin(0);
$pdf->setFooterMargin(0);
$pdf->SetAutoPageBreak(true);
$pdf->SetAuthor('Author');
$pdf->SetDisplayMode('real', 'default');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

//$pdf->AddPage('P',$pageLayout);
		$q=0; 
		$filler=0;
		$pages=0;
		
for($i=1; $i<=$people; $i++){ 
		$n = $i-1;
		// Define what special labels go on the badges
		$label ="0";
		
		
		
	
		$NameOnBadge=$results[$n]["NameOnBadge"];
		
		$GivenName=$results[$n]["GivenName"];
		echo "<h1>".$GivenName."</h1>";
		
		
		$CN_Company=$results[$n]["CN_Company"];
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
		$Email = $results[$n]["Email"];
		
		$pdf->AddPage('P',$pageLayout);
		
		if($HardCopy==1){
		$HardCopy="HC";
		}
		else{
		$HardCopy="0";
		}
		if($convention == "testconx"){
			if($Tutorial==1){
			$Tutorial="TUTORIAL";
			}
			else{
			$Tutorial="";
			}
			}
		if($convention == "tinyml"){
			if($Tutorial==1){
			$Tutorial="SYMPOSIUM";
			}
			else{
			$Tutorial="";
			}
			}
		if($convention == "emea"){
			if($Tutorial==1){
			$Tutorial="Social";
			}
			else{
			$Tutorial="";
			}
			}	
		if($convention == "emea"){
			if($Dinner==1){
			$Dinnertext="Dinner";
			}
			else{
			$Dinnertext="";
			}
			}	
		if($convention == "tinyml"){
			if($Dinner==1){
			$Dinnertext="Dinner";
			}
			else{
			$Dinnertext="";
			}
			}
		if($type == "Professional"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/Professional - Front 2024.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Exhibitor"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/Exhibitor - Front 20244.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "EXPO"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/EXPO Only - Front 2024.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Summit"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/2024tinyMLsummit.png',0,0,107.95,158.75, 'PNG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Symposium"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/2024tinyMLsymposium.png',0,0,107.95,158.75, 'PNG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "EXPOtiny"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/2024tinyMLexpo.png',0,0,107.95,158.75, 'PNG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Attendee"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/tinyML-EMEA2024-Badge-Front-PRINT.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		$pdf->SetFont('stsongstdlight', 'B', 75);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('helvetica', 'B', 75);
		$pdf->Ln(45);
if($convention == "testconx"){		
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
	
		$pdf->Cell(0, 0,$CN_Company, 0, 1, 'C', 0, '', 1);
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
		
		$pdf->SetFillColor(224,146,47);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('helvetica', 'B', 15);
		$pdf->setCellPaddings(0, 6, 0, 0);
		
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(0,0,0);
		
		$pdf->SetFont('helvetica', '', 10);
		if($type == "EXPO"){
		$pdf->MultiCell(90,10,$Tutorial." ".$Control."-".$i, 0, 'R', 0, 0, -8.5,150, true);
		}
		else{
		$pdf->MultiCell(90,10,$Tutorial." ".$Control."-".$i, 0, 'R', 0, 0, -8.5,150, true);
			}
		 $q++;
}	
if($convention == "tinyml"){		
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
		$pdf->Ln(5);
		//$pdf->Cell(0, 0,$CN_Company, 0, 1, 'C', 0, '', 1);
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
		$pdf->Ln(5);
		if($convention == 'tinyml'){
			if($type != "EXPOtiny"){
		$pdf->SetFont('helvetica', 'B', 16);
		$pdf->Cell(0, 0,'Ask me about:', 0, 1, 'C', 0, '', 1);
			if(strlen($Message)>8){
			$pdf->SetFont('helvetica', 'B', 22);
		}
		
		$pdf->Cell(0, 0,$Message, 0, 1, 'C', 0, '', 1);
		}
		}
		
		
		$pdf->SetFillColor(224,146,47);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('helvetica', 'B', 15);
		$pdf->setCellPaddings(0, 6, 0, 0);
		
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(255,255,255);
		
		$pdf->SetFont('helvetica', '', 14);
		
		
		if($type == "EXPO"){
		$pdf->MultiCell(90,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,148, true);
		}
		else{
		$pdf->MultiCell(90,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,148, true);
			}
		
		 $q++;
}		
if($convention == "emea"){		
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
		$pdf->Ln(5);
		//$pdf->Cell(0, 0,$CN_Company, 0, 1, 'C', 0, '', 1);
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
		$pdf->Ln(5);
		if($convention == 'emea'){
		$pdf->SetFont('helvetica', 'B', 16);
		$pdf->Cell(0, 0,'Ask me about:', 0, 1, 'C', 0, '', 1);
			if(strlen($Message)>8){
			$pdf->SetFont('helvetica', 'B', 22);
		}
		
		$pdf->Cell(0, 0,$Message, 0, 1, 'C', 0, '', 1);
		}
		
		
	
		$pdf->SetFillColor(224,146,47);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('helvetica', 'B', 15);
		$pdf->setCellPaddings(0, 6, 0, 0);
		
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(50,50,50);
		
		$pdf->SetFont('helvetica', '', 8);
		
		
		$pdf->SetTextColor(25,25,25);
		if($type=="EXPO"){
		$pdf->MultiCell(100,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,148, true);
		}
		else{
		$pdf->MultiCell(100,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -2.5,148, true);
			}
		
		$style = array(
					'border' => 2,
					'padding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => array(255,255,255)
				);
				$code="Name: ".$GivenName." ".$FamilyName."\n"
				."Email: ".$Email."\n"
				."Company: ".$Company;
				// QRCODE,H : QR-CODE Best error correction
				//$pdf->write2DBarcode('Name:'.$GivenName.' '.$FamilyName.'<br>'.'Email:'.$Email.'<br>'.'Company: '.$Company, 'QRCODE,H', 7, 115, 30, 30, $style, 'N');
		$pdf->write2DBarcode($code, 'QRCODE,H', 7, 115, 30, 30, $style, 'N');
		
		 $q++;
} 		 		 
//back of badge	 


$pdf->AddPage('P',$pageLayout);

		if($type == "Professional"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/Professional - Back 2024.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Exhibitor"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/Exhibitor - Back 2024.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "EXPO"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/EXPO Only - Back 2024.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}	 
		if($type == "Summit"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/2024tinyMLback.png',0,0,107.95,158.75, 'PNG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Symposium"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/2024tinyMLback.png',0,0,107.95,158.75, 'PNG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "EXPOtiny"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/2024tinyMLback.png',0,0,107.95,158.75, 'PNG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Attendee"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/tinyML-EMEA2024-Badge-Back-PRINT.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}

	 }
 
		
			
ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');
//echo($pdf);			
exit();
	} 

function Relatedbadge($convention = "testconx",$event = "test2022", $graphics = FALSE,$type = "Professional",$eventYear = "China2024")
{
	
	
			$db = \Config\Database::connect('registration');
			$builder = $db->table('guests');
			$builder->select('NameOnBadge,GivenName,ChineseName,CN_Company,Company,Email,EventYear,FamilyName,ContactID,
	InvitedByCompanyID,Control,HardCopy,Tutorial,Type,Message,Dinner,PrintTime');
			
			
			$builder->where('EventYear', $eventYear);
			$builder->where('Related', 1);
			$builder->where('ToPrint', 'Yes');
			
			
			$query = $builder->get();
			$people = $query->getNumRows();
			$results = $query->getResultArray();
			
			
			if ($people != 1){
				if($eventYear == "China2024"){
				return view('PrintViewChinaError', ['errors' => []]);
				}
				if($eventYear == "Korea2024"){
				return view('PrintViewKoreaError', ['errors' => []]);
				}
			}
				
	// $height = '158.75';
	// 	$width = '107.95';
	
	$time = date('Y-m-d H:i:s');
			if($results[0]["PrintTime"] == NULL){
			$data = [
			'PrintTime' => $time,
			];

					$builder->where('ContactID', $results[0]["ContactID"]);
					$builder->update($data);
			}
			else{
				if($eventYear == "China2024"){
				return view('PrintViewChinaDuplicate', ['errors' => []]);
				}
				if($eventYear == "Korea2024"){
				return view('PrintViewKoreaDuplicate', ['errors' => []]);
				}
				
				return;
			}
			// IRA POSITION
			$height = '152.4';
			$width = '101.6';
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
				$time = date('Y-m-d H:i:s');
			if($results[$n]["PrintTime"] == NULL){
			$data = [
			'PrintTime' => $time,
			];

					$builder->where('ContactID', $results[$n]["ContactID"]);
					$builder->update($data);
			}
				// Define what special labels go on the badges
				$label ="0";
			if (!empty($results[$n]["ChineseName"])){
				
					$NameOnBadge=$results[$n]["ChineseName"];
			}	
			else if (!empty($results[$n]["NameOnBadge"])){
				
					$NameOnBadge=$results[$n]["NameOnBadge"];
					
			}
			else {
				
				$NameOnBadge=$results[$n]["GivenName"];
				
			}
			
				
				
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
				$Email = $results[$n]["Email"];
				$ChineseName = $results[$n]["ChineseName"];
				
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
				$pdf->Button('print', 30, 10, 'Print Badge', 'Print()', array('lineWidth'=>2, 'borderStyle'=>'solid', 'fillColor'=>array(255, 255, 255), 'strokeColor'=>array(0, 0, 0)));

				$pdf->SetFont('stsongstdlight', 'B', 75);
				$pdf->SetFillColor(255, 255, 255);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('helvetica', 'B', 75);
				$pdf->Ln(40);
				
				
				
			if($EventYear == "Korea2024"){
			$pdf->SetFont('cid0kr', '', 55,);
			}
			else if($EventYear == "China2024"){
				$pdf->SetFont('cid0cs', '', 55,);
			}
			else{
				$pdf->SetFont('helvetica', 'B', 55);
			}
				
				$pdf->Cell(0, 0, $NameOnBadge, 0, 1, 'C', 0, '', 1);
				
			

					
			
				
			
				
				$pdf->SetFont('helvetica', 'B', 25);
				if(strlen($FamilyName)>8){
				$pdf->SetFont('helvetica', 'B', 22);
				}
				$pdf->Cell(0, 0,$GivenName." ".$FamilyName, 0, 1, 'C', 0, '', 1);
				$pdf->SetFont('stsongstdlight', 'B', 25);
				if(strlen($Company)>12){
				$pdf->SetFont('stsongstdlight', 'B', 17);
				}
				$pdf->Cell(0, 0,$Company, 0, 1, 'C', 0, '', 1);
				
				
				
				$pdf->SetFillColor(224,146,47);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFont('helvetica', 'B', 15);
				$pdf->setCellPaddings(0, 6, 0, 0);
				
				$pdf->setCellPaddings(0, 0, 0, 0);
				$pdf->SetFillColor(255,255,255);
				$pdf->SetTextColor(0,0,0);
				
				
				
				if($type=="EXPOtiny"){
				$pdf->SetFont('helvetica', '', 40);
				
				//$pdf->MultiCell(x size,y size,"EXPO", 0, 'L', 0, 0, x position,y position, true);
				$pdf->MultiCell(45, 20,"EXPO", 0, 'L', 0, 0, 45,120, true);
				//$pdf->Rect(45, 123, 80, 30, 'F',array(), array(250,174,2));
				}
				
				$pdf->SetFont('helvetica', '', 8);
				
				
				
				$pdf->MultiCell(100,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'L', 0, 0, 7,140, true);
				
				$style = array(
					'border' => 0,
					'padding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => array(255,255,255)
				);
				
				
				$codeContents  = 'BEGIN:VCARD'."\n";
				$codeContents .= 'VERSION:3.0'."\n";
				$codeContents .= 'N:'.$FamilyName.";".$GivenName.";;;\n";
				if (!empty($results[$n]["NameOnBadge"])){
					$codeContents .= 'NICKNAME:'.$NameOnBadge."\n";
				}
				 				
				$codeContents .= 'FN:'.$GivenName." ".$FamilyName."\n";
				$codeContents .= 'EMAIL:'.$Email."\n"; 
				$codeContents .= 'ORG:'.$Company."\n"; 
				$codeContents .= 'END:VCARD'; 
				
				 $code="Name: ".$GivenName." ".$FamilyName."\n"
				."Email: ".$Email."\n"
				."Company: ".$Company; 
				
				//$code="3880";
				// QRCODE,H : QR-CODE Best error correction
				//QR CODE IRA POSITION
				//$pdf->write2DBarcode($codeContents, 'QRCODE,L', x position, y position, x size, y size, $style, 'N');
				$pdf->write2DBarcode($codeContents, 'QRCODE,L', 7, 110, 30, 30, $style, 'N');
				
					
				 $q++;
						 
			 
			 
			 
			 }
		ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');
//echo($pdf);			
exit();
	} 

function Blankbadge2($convention = 'testconx', $event = 'Mesa2025', $graphics = FALSE,$type = 'Professional')
{

	$db  = \Config\Database::connect('registration');
	$builder = $db->table('guests');
	$builder->select('NameOnBadge,GivenName,CN_Company,Company,Email,EventYear,FamilyName,ContactID,
	InvitedByCompanyID,Control,HardCopy,Tutorial,Type,Message,Dinner');
	$builder->where('EventYear', $event);
	$builder->where('ToPrint', 'Yes');
	$builder->where('Type', $type);
	$builder->orderBy('FamilyName ASC, GivenName ASC');
	



	$query = $builder->get();
	$people = $query->getNumRows();
	
	$results = $query->getResultArray();
	
	// $height = '158.75';
    // 	$width = '107.95';
	
	$height = '152.4';
 	$width = '101.6';
	$pageLayout = array($width, $height);

//$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf = new \TCPDF('P', 'MM',$pageLayout, true, 'UTF-8', false);
$pdf->SetTitle('My Title');
$pdf->SetMargins(10,10,10,10);
$pdf->SetHeaderMargin(0);
$pdf->SetTopMargin(0);
$pdf->setFooterMargin(0);
$pdf->SetAutoPageBreak(true);
$pdf->SetAuthor('Author');
$pdf->SetDisplayMode('real', 'default');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);


		$q=0; 
		$filler=0;
		$pages=0;
		

 
for($i=1; $i<=$people; $i++){ 
		$n = $i-1;
		// Define what special labels go on the badges
		$label ="0";
		
		
		
	
		$NameOnBadge=$results[$n]["NameOnBadge"];
		
		$GivenName=$results[$n]["GivenName"];
		echo "<h1>".$GivenName."</h1>";
		
		
		$CN_Company=$results[$n]["CN_Company"];
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
		$Email=$results[$n]["Email"];
		
		$pdf->AddPage('P',$pageLayout);
		
		if($HardCopy==1){
		$HardCopy="HC";
		}
		else{
		$HardCopy="0";
		}
		if($convention == "testconx"){
			if($Tutorial==1){
			$Tutorial="TUTORIAL";
			}
			else{
			$Tutorial="";
			}
			}
		if($convention == "tinyml"){
			if($Tutorial==1){
			$Tutorial="SYMPOSIUM";
			}
			else{
			$Tutorial="";
			}
			}
			
		if($convention == "tinyml"){
			if($Dinner==1){
			$Dinnertext="Dinner";
			}
			else{
			$Dinnertext="";
			}
			}
			if($convention == "emea"){
			if($Tutorial==1){
			$Tutorial="Social";
			}
			else{
			$Tutorial="";
			}
			}	
		if($convention == "emea"){
			if($Dinner==1){
			$Dinnertext="Dinner";
			}
			else{
			$Dinnertext="";
			}
			}
		if($type == "Professional"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/Combo Badge 2023.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Exhibitor"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/Combo Badge 20235.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "EXPO"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/Combo Badge 20233.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Summit"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/tinyML2023Summit.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Symposium"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/tinyML2023Symposium.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "EXPOtiny"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/tinyML2023EXPO.jpg',0,0,107.95,158.75, 'JPG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		if($type == "Attendee"){
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/emea2023.png',0,0,107.95,158.75, 'PNG', '', '',false, 10, 'C', false, false, 0, false, false, false);
		}
		}
		$pdf->SetFont('stsongstdlight', 'B', 75);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('helvetica', 'B', 75);
		$pdf->Ln(45);
if($convention == "testconx"){		
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
	
		$pdf->Cell(0, 0,$CN_Company, 0, 1, 'C', 0, '', 1);
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
		
		
		$pdf->SetFillColor(224,146,47);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('helvetica', 'B', 15);
		$pdf->setCellPaddings(0, 6, 0, 0);
		
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(0,0,0);
		
		$pdf->SetFont('helvetica', '', 10);
		if($type=="EXPO"){
		$pdf->MultiCell(90,10,$Tutorial." ".$Control."-".$i, 0, 'R', 0, 0, -8.5,144, true);
		}
		else{
		$pdf->MultiCell(90,10,$Tutorial." ".$Control."-".$i, 0, 'R', 0, 0, -8.5,144, true);
			}
		 $q++;
}	
if($convention == "tinyml"){		
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
		$pdf->Ln(5);
		//$pdf->Cell(0, 0,$CN_Company, 0, 1, 'C', 0, '', 1);
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
		$pdf->Ln(1);
		if($convention == 'tinyml'){
		$pdf->SetFont('helvetica', 'B', 16);
		$pdf->Cell(0, 0,'Ask me about:', 0, 1, 'C', 0, '', 1);
			if(strlen($Message)>8){
			$pdf->SetFont('helvetica', 'B', 22);
		}
		
		$pdf->Cell(0, 0,$Message, 0, 1, 'C', 0, '', 1);
		}
		
		
		
		$pdf->SetFillColor(224,146,47);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('helvetica', 'B', 15);
		$pdf->setCellPaddings(0, 6, 0, 0);
		
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(0,0,0);
		
		$pdf->SetFont('helvetica', '', 40);
		
		
		//colored rectangle
		if($type=="Symposium"){
		$pdf->SetFont('helvetica', '', 50);
		$pdf->MultiCell(45, 123,"RS", 0, 'C', 0, 0, 45,120, true);
		//$pdf->Rect(45, 123, 80, 30, 'F',array(), array(118,215,61));
		}
		if($type=="EXPOtiny"){
		$pdf->SetFont('helvetica', '', 40);
		$pdf->MultiCell(45, 123,"EXPO", 0, 'L', 0, 0, 45,120, true);
		//$pdf->Rect(45, 123, 80, 30, 'F',array(), array(250,174,2));
		}
		$pdf->SetFont('helvetica', '', 10);
		if($type=="EXPO"){
		$pdf->MultiCell(90,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,141, true);
		}
		else{
		$pdf->MultiCell(90,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,141, true);
			}
		
		 $q++;
}		 		 		 
if($convention == "emea"){		
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
		$pdf->Ln(5);
		//$pdf->Cell(0, 0,$CN_Company, 0, 1, 'C', 0, '', 1);
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
		$pdf->Ln(5);
		if($convention == 'emea'){
		$pdf->SetFont('helvetica', 'B', 16);
		$pdf->Cell(0, 0,'Ask me about:', 0, 1, 'C', 0, '', 1);
			if(strlen($Message)>8){
			$pdf->SetFont('helvetica', 'B', 22);
		}
		
		$pdf->Cell(0, 0,$Message, 0, 1, 'C', 0, '', 1);
		}
		
		
		
		$pdf->SetFillColor(224,146,47);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('helvetica', 'B', 15);
		$pdf->setCellPaddings(0, 6, 0, 0);
		
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(0,0,0);
		
		$pdf->SetFont('helvetica', '', 10);
		
		if($type=="Symposium"){
		$pdf->Rect(45, 123, 80, 30, 'F',array(), array(118,215,61));
		}
		if($type=="EXPOtiny"){
		$pdf->Rect(45, 123, 80, 30, 'F',array(), array(250,174,2));
		}
		
		if($type=="EXPO"){
		$pdf->MultiCell(90,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,148, true);
		}
		else{
		$pdf->MultiCell(90,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,148, true);
			}
		$style = array(
					'border' => 2,
					'padding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => array(255,255,255)
				);
				$code="Name: ".$GivenName." ".$FamilyName."\n"
				."Email: ".$Email."\n"
				."Company: ".$Company;
				// QRCODE,H : QR-CODE Best error correction
				//$pdf->write2DBarcode('Name:'.$GivenName.' '.$FamilyName.'<br>'.'Email:'.$Email.'<br>'.'Company: '.$Company, 'QRCODE,H', 7, 115, 30, 30, $style, 'N');
		$pdf->write2DBarcode($code, 'QRCODE,H', 7, 115, 30, 30, $style, 'N');
		 $q++;
} 

	 
	 }		
			
ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');
//echo($pdf);		
exit();
	} 

		
function Blankbadge($convention = 'testconx', $event = 'Mesa2025', $graphics = FALSE,$type = 'Professional'){


	$db  = \Config\Database::connect('registration');
	$builder = $db->table('guests');
	$builder->select('NameOnBadge,GivenName,CN_Company,Company,Email,EventYear,FamilyName,ContactID,
	InvitedByCompanyID,Control,HardCopy,Tutorial,Type,Message,Dinner');
	$builder->where('EventYear', $event);
	$builder->where('ToPrint', 'Yes');
	$builder->where('Type', $type);
	$builder->orderBy('FamilyName ASC, GivenName ASC');
	



	$query = $builder->get();
	$people = $query->getNumRows();
	
	$results = $query->getResultArray();
	
	// $height = '158.75';
    // 	$width = '107.95';
	
	
		

		
			
	
			
			
			// IRA POSITION
			$height = '150';
			$width = '101.6';
			$pageLayout = array($width, $height);



		$pdf = new \TCPDF('P', 'MM',$pageLayout, true, 'UTF-8', false);
		$pdf->SetTitle('Badge Single');
		$pdf->SetMargins(0,0,0,0);
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
			$time = date('Y-m-d H:i:s');
			
			$data = [
			'PrintTime' => $time,
			];

			$builder->where('ContactID', $results[$n]["ContactID"]);
			$builder->update($data);
			
				// Define what special labels go on the badges
				$label ="0";
			if (!empty($results[$n]["NameOnBadge"])){
				
				$NameOnBadge=$results[$n]["NameOnBadge"];
					
			}
			else {
				
				$NameOnBadge=$results[$n]["GivenName"];
				
			}
			
				
				
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
				$Email = $results[$n]["Email"];
				//$ChineseName = $results[$n]["ChineseName"];
				
				$pdf->AddPage('P',$pageLayout);
				$Dinnertext="";
				
				
				
					
					 if($Tutorial==1){
					$Tutorial="TUTORIAL";
					}
					else{
					$Tutorial="";
					}
					 
				//$pdf->Button('print', 30, 10, 'Print Badge', 'Print()', array('lineWidth'=>2, 'borderStyle'=>'solid', 'fillColor'=>array(255, 255, 255), 'strokeColor'=>array(0, 0, 0)));

			
				$pdf->Ln(40);
				
				
				//here is where we need the fonts
				
				// convert TTF font to TCPDF format and store it on the fonts folder
			//$fontname = TCPDF_FONTS::addTTFfont(site_url('tcxcode/ThirdParty/NotoSerifKR-VariableFont_wght.ttf'), 'TrueTypeUnicode', '', 96);
			//hysmyeoungjostdmedium
			//hysmyeongjostdmedium.php
			// use the font
			if($EventYear == "Korea2024"){
			$pdf->SetFont('cid0kr', '', 55,);
			}
			else if($EventYear == "China2024"){
				$pdf->SetFont('cid0cs', '', 55,);
			}
			else{
				$pdf->SetFont('helvetica', 'B', 55);
			}
				if(strlen($NameOnBadge)>8){
				$pdf->SetFont('helvetica', 'B', 50);
				}
				$pdf->Cell(0, 0, $NameOnBadge, 0, 1, 'C', 0, '', 1);
				
			
				$pdf->SetFont('helvetica', 'B', 25);
				if(strlen($FamilyName)>8){
				$pdf->SetFont('helvetica', 'B', 22);
				}
				$NameLength = strlen($FamilyName)+strlen($GivenName);
				if(strlen($NameLength)>19){
				$pdf->SetFont('helvetica', 'B', 18);
				}
				
				$pdf->Cell(0, 0,$GivenName." ".$FamilyName, 0, 1, 'C', 0, '', 1);
				$pdf->SetFont('helvetica', 'B', 25);
				if(strlen($Company)>12){
				$pdf->SetFont('helvetica', 'B', 17);
				}
				if(strlen($Company)>20){
				$pdf->SetFont('helvetica', 'B', 15);
				}
				if(strlen($Company)>25){
				$pdf->SetFont('helvetica', 'B', 12);
				}
				
				/*if(strlen($Company)>12){
				$pdf->SetFont('stsongstdlight', 'B', 17);
				}*/
				$pdf->Cell(0, 0,$Company, 0, 1, 'C', 0, '', 1);
				
				
				
				$pdf->SetFillColor(224,146,47);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFont('helvetica', 'B', 15);
				$pdf->setCellPaddings(0, 6, 0, 0);
				
				$pdf->setCellPaddings(0, 0, 0, 0);
				$pdf->SetFillColor(255,255,255);
				$pdf->SetTextColor(0,0,0);
				
				
				
			
				
				$pdf->SetFont('helvetica', '', 8);
				$Control = substr($Control, -4);
				
			
				$pdf->MultiCell(100,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'L', 0, 0, 7,140, true);
				
				
				// new style
				$style = array(
					'border' => 0,
					'padding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => array(255,255,255)
				);
				
				
				$codeContents  = 'BEGIN:VCARD'."\n";
				$codeContents .= 'VERSION:3.0'."\n";
				$codeContents .= 'N:'.$FamilyName.";".$GivenName.";;;\n";
				if (!empty($results[$n]["NameOnBadge"])){
					$codeContents .= 'NICKNAME:'.$NameOnBadge."\n";
				}
				 				
				$codeContents .= 'FN:'.$GivenName." ".$FamilyName."\n";
				$codeContents .= 'EMAIL:'.$Email."\n"; 
				$codeContents .= 'ORG:'.$Company."\n"; 
				$codeContents .= 'END:VCARD'; 
				
				 $code="Name: ".$GivenName." ".$FamilyName."\n"
				."Email: ".$Email."\n"
				."Company: ".$Company; 
				
				//$code="3880";
				// QRCODE,H : QR-CODE Best error correction
				//QR CODE IRA POSITION
				//$pdf->write2DBarcode($codeContents, 'QRCODE,L', x position, y position, x size, y size, $style, 'N');
				$pdf->write2DBarcode($codeContents, 'QRCODE,L', 7, 110, 30, 30, $style, 'N');
				
					
				 $q++;
						 
			 
			 
			 
			 }
		
		
ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');
//echo($pdf);		
exit();


		
		
	
	  }		


		
		
	
	

function BadgesMesaProfessional () {
	$this->Testbadge("testconx","Mesa2024", TRUE,"Professional");
}
function BadgesMesaExhibitor () {
	$this->Testbadge("testconx","Mesa2024", True,"Exhibitor");
}
function BadgesMesaEXPOONLY () {
	$this->Testbadge("testconx","Mesa2024", True,"EXPO");
}


function BadgestinymlProfessional () {
	$this->Testbadge("tinyml","tinyml2024", TRUE,"Summit");
}
function BadgestinymlSymposium () {
	$this->Testbadge("tinyml","tinyml2024", TRUE,"Symposium");
}
function BadgestinymlEXPOONLY () {
	$this->Testbadge("tinyml","tinyml2024", TRUE,"EXPOtiny");
}


function BadgesMesaBlankProfessional (){
	$this->Blankbadge('testconx','Mesa2025', FALSE,'Professional');
	//$this->Blankbadge();
}
function BadgesMesaBlankExhibitor (){
	$this->Blankbadge("testconx","Mesa2025", FALSE,"Exhibitor");
}
function BadgesMesaBlankEXPO (){
	$this->Blankbadge("testconx","Mesa2025", FALSE,"EXPO");
}

function BadgestinymlBlankProfessional (){
	$this->Blankbadge("tinyml","tinyml2024", FALSE,"Summit");
	
}
function BadgestinymlBlankExhibitor (){
	$this->Blankbadge("tinyml","tinyml2024", FALSE,"Symposium");
}
function BadgestinymlBlankEXPO (){
	$this->Blankbadge("tinyml","tinyml2024", FALSE,"EXPOtiny");
}

function BadgesEMEAAttendee (){
	$this->Testbadge("emea","emea2024", TRUE,"Attendee");
}

function BadgesEMEABlankAttendee (){
	$this->Blankbadge("emea","emea2024", FALSE,"Attendee");
}

function Related (){
	$this->Relatedbadge("testconx","China2024", FALSE,"Professional");
}
	function ClearPrint ()
	{

	
	
	
	$db  = \Config\Database::connect('registration');
	$builder = $db->table('guests');
	$builder->where('ToPrint', 'Yes');
	$data = array (
		'ToPrint' => 'No');
	$builder->update($data);
	

	}
		
}

/* End of file Main.php */
/* Location: ./application/controllers/Main.php */
?>