<?php 


namespace App\Controllers;

//include(APPPATH.'Libraries/GroceryCrudEnterprise/autoload.php');






use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;

$session = session();
if ( !$session->tcx_logged_in ) {
	throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); 
	die ("Login failure"); // Shouldn't execute but here just in case.

}
 
class Badgemesatest extends BaseController {

  
	function __construct()
	{
		//parent::__construct();
		helper('text');
		
	}
 
	public function index()
	{
		
		echo "<h1>Testonly</h1>";
		echo "<h4>Test</h4>";
		echo "<OL>";
	
		echo "<LI>Clear <a href=" . site_url('/badgemesatest/test') . ">Test Case</a></LI>";
		echo "</OL>";
		echo "<br><br>";
		
	}
	 

    
	
	



 








    



function test()
{

$height = '158.75';
$width = '107.95';
$pageLayout = array($width, $height);


$pdf = new \TCPDF('P', 'MM',$pageLayout, true, 'UTF-8', false);
$pdf->SetAuthor('author');
$pdf->SetTitle('title');
$pdf->SetSubject('subject');
$pdf->SetKeywords('key, words');


$pdf->SetMargins(10,10,10,10);
$pdf->SetHeaderMargin(0);
$pdf->SetTopMargin(0);
$pdf->setFooterMargin(0);
$pdf->SetAutoPageBreak(true);


//$pdf->SetDisplayMode('real', 'default');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

//$pdf->AddPage('P',$pageLayout);
//$html = '<h1>Testing simple</h1>';		
//$pdf->writeHTML($html, true, false, true, false, '');		

 
		
			
ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');
echo($pdf);		

	} 



	

		
		


		
		
	
	


	
		
}

/* End of file Main.php */
/* Location: ./application/controllers/Main.php */
?>