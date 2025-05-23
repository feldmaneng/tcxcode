<?php 

namespace App\Controllers;

use CodeIgniter\Files\File;
use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;

$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

}

class PrintBadge extends BaseController
{
	public function __construct()
        {
                
                helper('form');
				helper('text');
				helper('url');
        }

    public function index()
    {
		echo "<a href=" . site_url('/PrintBadge/korea') . ">Korea</a> ";
		echo "<a href=" . site_url('/PrintBadge/china') . ">China</a> ";

    }
	
	 public function korea()
    {
        return view('PrintView', ['errors' => []]);
    }
	
	public function china()
    {
        return view('PrintViewChina', ['errors' => []]);
    }
	
	
	public function printpreview()
    {
        return view('PrintView2', ['errors' => []]);
    }
	
	public function printpreviewchina()
    {
        return view('PrintView2China', ['errors' => []]);
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
	
	private function _example_output($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

        return view('testconx_template.php', (array)$output);
    }
	
   
      function printgeneral(){
		
			$model = model(DirectoryEntry::class);
			$session = session();

			$id = $_POST["BadgeID"];
			$eventYear = $_POST["eventYearID"];
			$db = \Config\Database::connect('registration');
			$builder = $db->table('guests');
			$builder->select('NameOnBadge,GivenName,ChineseName,CN_Company,Company,Email,EventYear,FamilyName,ContactID,
	InvitedByCompanyID,Control,HardCopy,Tutorial,Type,Message,Dinner,PrintTime');
			
			$builder->where('Email', $id);
			$builder->where('EventYear', $eventYear);
			$builder->orwhere('ContactID',$id);
			$builder->where('EventYear', $eventYear);
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
	
	$time = date("d-m-y h:i:s");
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
				//$pdf->SetFont('helvetica', 'B', 55);
				//$pdf->SetFont('stsongstdlight', '', 55);
				//$pdf->SetFont('cid0jp', '', 40);
				//你好
				//$txt = 'こんにちは世界';
				//$txt = '你好';
				//$pdf->Write(0, $txt, '', 0, 'L', true, 0, false, false, 0);
				$pdf->Cell(0, 0, $NameOnBadge, 0, 1, 'C', 0, '', 1);
				
				//$pdf->SetFont('stsongstdlight', 'B', 25);

					
			
				
			
				//$pdf->Cell(0, 0,$CN_Company, 0, 1, 'C', 0, '', 1);
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
				
				
				//$pdf->MultiCell(90,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,140, true);
				//Control Text IRA POSITION
				//$pdf->MultiCell(x size,y size,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, x position,y position, true);
				$pdf->MultiCell(100,10,$Dinnertext." ".$Tutorial." ".$Control." ".$i, 0, 'L', 0, 0, 7,140, true);
				//$pdf->MultiCell(90,10,$Tutorial." ".$Control." ".$i, 0, 'R', 0, 0, -8.5,144, true);
				
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
		//$pdf->SetFillColor(255,255,255);
		//$pdf->SetTextColor(0,0,0);
		
		// force print dialog
		
		$js = <<<EOD
	print();
	function Print() {
  
    print();
	window.location.href = "https://www.testconx.org/forms.php/print";
}

EOD;

// Add Javascript code
$pdf->IncludeJS($js);
		
		
		$pdf->Output($_SERVER["DOCUMENT_ROOT"].'tmpqr/BadgeTest.pdf', 'F');

		//$pdf->Output('BadgeTest.pdf', 'I');
		//echo($pdf);
		//return view('PrintView', ['errors' => []]);
		//exit();	
		if($eventYear == "Korea2024"){
		return redirect()->to('https://www.testconx.org/forms.php/PrintBadge/printpreview');
		//return view('PrintView', ['errors' => []]);		
		}
		if($eventYear == "China2024"){
		return redirect()->to('https://www.testconx.org/forms.php/PrintBadge/printpreviewchina');
		//return view('PrintView', ['errors' => []]);		
		}
	  }

}


?>