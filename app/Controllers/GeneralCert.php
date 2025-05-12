<?php

namespace App\Controllers;
use Config\Database as ConfigDatabase;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;
class GeneralCert extends BaseController {
	public function __construct()
	{
		helper('form');
		helper('text');
		helper('url');
	
	}
	
	public function index()
	{
		return view('GeneralCertView');
	}
	
	public function certificates()
	{
		
		 $year = $this->request->getPost('year');
		 $event = $this->request->getPost('event');
		 $date = $this->request->getPost('date');
		 $chair1 = $this->request->getPost('chair1');
		 $chair2 = $this->request->getPost('chair2');
		 $chair3 = $this->request->getPost('chair3');
		 //echo $year." ".$event;
		 $db = db_connect();
		 $builder = $db->table('presentations');
		 $builder -> join('authors', 'presentations.PresentationID = authors.PresentationID');
		 $builder -> where('Year', $year);
		 //$builder -> where('Year','2025');
		//$builder ->where('Event','Mesa');
		 $builder -> where('Event', $event);
		 $builder -> where('Session !=', 'Cancel');
		 $builder -> where('Session !=', 'Cancel-Poster');
		 $builder -> where('Session !=', '3AB');
		 $builder -> where('Session !=', '2AB');
		 $builder -> where('Session !=', 'dropped');
		 $builder -> orderBy('Session', 'ASC');
		 $builder -> orderBy('PresentationNumber','ASC');
		 $builder -> orderBy('Title','ASC');
		 $query = $builder->get();
		 $people = $query->getNumRows();
		 $results = $query->getResultArray();
			
			if($event == 'Mesa'){
				$width = 279.4;
				$height = 215.9;
				$pageLayout = array($width, $height);
			}
			
			if($event == 'China'){
				$width = 297;
				$height = 210;
				$pageLayout = array($width, $height); 
			}
			
			if($event == 'Korea'){
				$width = 297;
				$height = 210;
				$pageLayout = array($width, $height); 
			}
			
		$pdf = new \TCPDF('L','mm', $pageLayout, true, 'UTF-8', false);
		
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('CMPVTESTCONX');
		$pdf->SetTitle('Certificates');
		$pdf->SetSubject('');
		$pdf->SetKeywords('');
		
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		
		$pdf->setTopMargin(0);
		$pdf->SetRightMargin(0);
		$pdf->SetLeftMargin(0);
		
		$pdf->setHeaderMargin(0);
		$pdf->SetFooterMargin(0);
		
		$pdf->SetAutoPageBreak(TRUE, 5.0);
		
		$pdf->SetFont('helvetica', '',10);
		
		$pdf->AddPage('L');
		
		for($i=1 ; $i <= $people ; $i++)
{
//this determines how many rows the sheet has

    
			$n=$i-1;

			$FIRSTNAME=$results[$n]["GivenName"];
			$LASTNAME=$results[$n]["FamilyName"];
			$TITLE=$results[$n]["Title"];
			$SESSION=$results[$n]["Session"];

			   
				$pdf->setCellMargins(0,0,2.5,0);
				// The width is set to the the same as the cell containing the name.
				// The Y position is also adjusted slightly.
				
				if($event == 'Mesa'){
					$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images_new/TestConXletterframe2.png', 0, 0, 279, 214.9, 'PNG', '', '',true,300, '', false, false, 0, false, false, false);
	
						$y=55;
						$z=10;
					   $pdf->SetFont('times', '', 24);  
					  $pdf->MultiCell(100, 25,"Certificate of Appreciation", 0, 'C', 0, 0, 87.5, $y, true);
					  $pdf->SetFont('times', '', 18);
						$pdf->MultiCell(100, 25,"This Certificate is Awarded to", 0, 'C', 0, 0, 87.5, $y+1.5*$z, true);
						 $pdf->SetFont('times', '', 24);
						 
					   $pdf->MultiCell(100, 25,$FIRSTNAME." ".$LASTNAME, 0, 'C', 0, 0, 87.5, $y+2.5*$z, true);
						$pdf->SetFont('times', '', 18);
						if($SESSION == 'Poster' || $SESSION == 'Best Poster')
						{
						$pdf->MultiCell(100, 25,"for the poster", 0, 'C', 0, 0, 87.5,$y+3.9*$z, true);
						}
						else if($SESSION == 'Keynote')
						{
						$pdf->MultiCell(100, 25,"for the Keynote", 0, 'C', 0, 0, 87.5,$y+3.9*$z, true);
						} else
					   {
					   $pdf->MultiCell(100, 25,"for the presentation", 0, 'C', 0, 0, 87.5,$y+3.9*$z, true);
					   }
						$pdf->SetFont('times', '', 24);
						$length = strlen($TITLE);
						if($length > 75)
						{
						$pdf->SetFont('times', '', 20);
						}
						if($length > 95)
						{
						$pdf->SetFont('times', '', 18);
						}
						$pdf->MultiCell(200, 25,$TITLE, 0, 'C', 0, 0, 37.5, $y+5*$z, true);
						$pdf->SetFont('times', '', 18);
						//$pdf->MultiCell(200, 25,"presented at TestConX" .$year. "Virtual Event \n May ". $year, 0, 'C', 0, 0, 37.5, $y+7*$z+5, true); 
						
						
						$pdf->SetFont('times', '', 18);
						$pdf->MultiCell(200, 25,"presented at TestConX ".$year." Workshop \n " . $date, 0, 'C', 0, 0, 37.5, $y+7*$z+5, true);
						$style = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)); 
						$pdf->Line( 25.58+10+4.4, 170,25.58+63+4.4, 170, $style = array() );
						$pdf->Line( 25.58+83+4.4, 170,25.58+136+4.4, 170, $style = array() );
						$pdf->Line( 25.58+156+4.4, 170,25.58+209+4.4, 170, $style = array() );
						$pdf->SetFont('times', '', 12);
						$pdf->MultiCell(60, 23,$chair1." \nTPC Co - Chair", 0, 'L', 0, 0, 25.58+12.7+4.4, 172, true);
						$pdf->MultiCell(60, 23,$chair2." \nTPC Co - Chair", 0, 'L', 0, 0, 25.58+85.7+4.4, 172, true);
						$pdf->MultiCell(60, 23,"Ira Feldman \nTestConX General Chair", 0, 'L', 0, 0, 25.58+158.7+4.4, 172, true);
					   
					   
					 //$pdf->AddPage();
					 $pdf->AddPage('L');
 }
				if($event == 'China'){
					$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images_new/TestConXChinaOrangeFrameA4.png', 5, 5, 287, 205, 'PNG', '', '',true,300, '', false, false, 0, false, false, false);
	
					$x=102-2;
					$y=60;
					$z=10;
					$x2=49.7-2;
					$x3=30.58-2;
				   $pdf->SetFont('times', '', 24);  
				  $pdf->MultiCell(100, 25,"Certificate of Appreciation", 0, 'C', 0, 0, $x, $y, true);
				  $pdf->SetFont('times', '', 18);
					$pdf->MultiCell(100, 25,"This Certificate is Awarded to", 0, 'C', 0, 0, $x, $y+1.5*$z, true);
					 $pdf->SetFont('times', '', 24);
					 
				   $pdf->MultiCell(100, 25,$FIRSTNAME." ".$LASTNAME, 0, 'C', 0, 0, $x, $y+2.5*$z, true);
					$pdf->SetFont('times', '', 18);
					if($SESSION == 'Poster' || $SESSION == 'Best Poster')
					{
					$pdf->MultiCell(100, 25,"for the poster", 0, 'C', 0, 0, $x,$y+3.9*$z, true);
					}
					else if($SESSION == 'Keynote')
					{
					$pdf->MultiCell(100, 25,"for the Keynote", 0, 'C', 0, 0, $x,$y+3.9*$z, true);
					} else if($SESSION == 'Tutorial1' || $SESSION == 'Tutorial2'|| $SESSION == 'Tutorial')
					{
					$pdf->MultiCell(100, 25,"for the Tutorial", 0, 'C', 0, 0, $x,$y+3.9*$z, true);
					} else
				   {
				   $pdf->MultiCell(100, 25,"for the presentation", 0, 'C', 0, 0, $x,$y+3.9*$z, true);
				   }
					$pdf->SetFont('times', '', 24);
					$length = strlen($TITLE);
					if($length > 75)
					{
					$pdf->SetFont('times', '', 20);
					}
					if($length > 95)
					{
					$pdf->SetFont('times', '', 18);
					}
					$pdf->MultiCell(200, 25,$TITLE, 0, 'C', 0, 0, $x2, $y+5*$z, true);
					$pdf->SetFont('times', '', 18);
					$pdf->MultiCell(200, 25,"presented at TestConX China ".$year." Workshop \n ".$date, 0, 'C', 0, 0, $x2, $y+7*$z+5, true);
					$style = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)); 
					$pdf->Line( $x3+15+4.4, 170,25.58+63+4.4, 170, $style = array() );
					$pdf->Line( $x3+88+4.4, 170,25.58+136+4.4, 170, $style = array() );
					$pdf->Line( $x3+161+4.4, 170,25.58+209+4.4, 170, $style = array() );
					$pdf->SetFont('times', '', 12);
					$pdf->MultiCell(60, 23,$chair1." \nChina Program Chair", 0, 'L', 0, 0, $x3+17.7+4.4, 170, true);
					$pdf->MultiCell(60, 23,$chair2." \nChina General Chair", 0, 'L', 0, 0, $x3+90.7+4.4, 170, true);
					$pdf->MultiCell(60, 23,"Ira Feldman \nTestConX General Chair", 0, 'L', 0, 0, $x3+163.7+4.4, 170, true);
									
					}
				if($event == 'Korea'){
					$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images_new/TestConXKoreaOrangeFrameA4.png', 5, 5, 287, 205, 'PNG', '', '',true,300, '', false, false, 0, false, false, false);
					$x=102-2;
					$y=60;
					$z=10;
					$x2=49.7-2;
					$x3=30.58-2;
					$pdf->SetFont('times', '', 24);  
					$pdf->MultiCell(100, 25,"Certificate of Appreciation", 0, 'C', 0, 0, $x, $y, true);
					$pdf->SetFont('times', '', 18);
					$pdf->MultiCell(100, 25,"This Certificate is Awarded to", 0, 'C', 0, 0, $x, $y+1.5*$z, true);
					$pdf->SetFont('times', '', 24);
					 
					$pdf->MultiCell(100, 25,$FIRSTNAME." ".$LASTNAME, 0, 'C', 0, 0, $x, $y+2.5*$z, true);
					$pdf->SetFont('times', '', 18);
					if($SESSION == 'Poster' || $SESSION == 'Best Poster')
					{
					$pdf->MultiCell(100, 25,"for the poster", 0, 'C', 0, 0, $x,$y+3.9*$z, true);
					}
					else if($SESSION == 'Keynote'||$SESSION == 'Keynote 1'||$SESSION == 'Keynote 2')
					{
					$pdf->MultiCell(100, 25,"for the Keynote", 0, 'C', 0, 0, $x,$y+3.9*$z, true);
					} else if($SESSION == 'Tutorial1' || $SESSION == 'Tutorial2'|| $SESSION == 'Tutorial')
					{
					$pdf->MultiCell(100, 25,"for the Tutorial", 0, 'C', 0, 0, $x,$y+3.9*$z, true);
					} else
				   {
				   $pdf->MultiCell(100, 25,"for the presentation", 0, 'C', 0, 0, $x,$y+3.9*$z, true);
				   }
					$pdf->SetFont('times', '', 24);
					$length = strlen($TITLE);
					if($length > 75)
					{
					$pdf->SetFont('times', '', 20);
					}
					if($length > 95)
					{
					$pdf->SetFont('times', '', 18);
					}
					
					$pdf->MultiCell(200, 25,$TITLE, 0, 'C', 0, 0, $x2, $y+5*$z, true);
					$pdf->SetFont('times', '', 18);
					$pdf->MultiCell(200, 25,"presented at TestConX Korea".$year." workshop \n ".$date, 0, 'C', 0, 0, $x2, $y+7*$z+5, true);
					$style = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)); 
					$pdf->Line( $x3+15+4.4, 170,25.58+63+4.4, 170, $style = array() );
					$pdf->Line( $x3+88+4.4, 170,25.58+136+4.4, 170, $style = array() );
					$pdf->Line( $x3+161+4.4, 170,25.58+209+4.4, 170, $style = array() );
					$pdf->SetFont('times', '', 12);
					$pdf->MultiCell(60, 25,$chair1." \nKorea Program Chair", 0, 'L', 0, 0, $x3+17.7+4.4, 170, true);
					$pdf->MultiCell(60, 25,$chair2." \nKorea General Chair", 0, 'L', 0, 0, $x3+90.7+4.4, 170, true);
					$pdf->MultiCell(60, 25,"Ira Feldman \nTestConX General Chair", 0, 'L', 0, 0, $x3+163.7+4.4, 170, true);
				}
	
				
		}	



// ---------------------------------------------------------
ob_end_clean();
//Close and output PDF document
$pdf->Output('certificates2024.pdf', 'I');
echo($pdf);
exit;
}	
	
}


