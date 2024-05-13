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
 
class Pdftest extends BaseController {

  
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
		echo "<LI>Manage <a href=" . site_url('/Pdftest/testpdf') . ' target="_blank" ">Test pdf</a></LI>';
		
		
		echo "</OL>";
		echo "<br><br>";
		
	}
	 

  



    



function Test()
{
	
	
	
	
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

$pdf->AddPage('P',$pageLayout);
$pdf->Cell(0, 0, 'TEST CELL STRETCH: no stretch', 1, 1, 'C', 0, '', 0);

	 
 
		
			
//ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');
echo($pdf);			

	} 



		
		


		
		
	
	





function testpdf (){
	$this->Test();
}
	
}

/* End of file Main.php */
/* Location: ./application/controllers/Main.php */
?>