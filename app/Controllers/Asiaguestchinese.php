<?php  



// When updating for each year, don't forget to change the random numbers at the end of
// the company and contact function calls
namespace App\Controllers;
//resync grocery
use Config\Database as ConfigDatabase;
use Config\GroceryCrud as ConfigGroceryCrud;
use GroceryCrud\Core\GroceryCrud;
use App\Libraries\PdfLibrary;
use CodeIgniter\Database\RawSql;
use CodeIgniter\Database\BaseBuilder;

// Some variables for each year
define ("BiTSEvent", "TestConX China 2019"); // What is displayed
define("EventYear", "China2019");// For selecting records only for this year's event.

$session = session(); 

class Asiaguestchinese extends BaseController {

 
function __construct()
{
      
 
helper('text');
 
}
 
public function index()
{
echo "<h1>Please use the special link provided to you.</h1>";//Just an example to ensure that we get into the function
die();
}

 



public function company123()
{
	
		$crud = $this->_getGroceryCrudEnterprise('registration');

        $crud->setCsrfTokenName(csrf_token());
        $crud->setCsrfTokenValue(csrf_hash());
	

	
	$crud->setTable('chinacompany');
   
	$crud->where(['chinacompany.EventYear' => EventYear]);
   		
	
	$crud->setRelation('StaffID','guests','{ContactID} - {GivenName} {FamilyName}',['EventYear' => EventYear]);
	
	$crud->fieldType('EventYear', 'hidden',);
	
	
	$crud->displayAs('StaffID', 'Exhibitor Staff');
	
	
	$crud->callbackBeforeInsert(function ($stateParameters) {
			$stateParameters->data['DBUser'] = $this->determine_user();
			$stateParameters = $this->setSecretKey($stateParameters);
			return $stateParameters;
		});
	/* $crud->setActionButton('Avatar', 'fa fa-user', function ($row) {
    return '#/avatar/' . $row->'StaffID';
});	 */
	$output = $crud->render();

	return $this->_example_output($output);        
}
 
public function contact585442()
{
    //include 'singleprint.php';
	
		$crud = $this->_getGroceryCrudEnterprise('registration');

        $crud->setCsrfTokenName(csrf_token());
        $crud->setCsrfTokenValue(csrf_hash());
		
	
	
	$crud->setTable('guests');
	$crud->setSubject('Guest 来宾', 'Guests 来宾');
	
	
	
	
   	$crud->where(['guests.EventYear' => EventYear]);
	
	$crud->columns (['InvitedByCompanyID','Email','GivenName','FamilyName','ChineseName','NameOnBadge','Company','CN_Company','MasterContactID']);
	$crud->fields (['MasterContactID','InvitedByCompanyID', 'BanquetCompanyID','Email','GivenName','FamilyName', 
		'ChineseName','NameOnBadge','Title','Company','CN_Company',
		'Address1', 'Address2', 'City', 'State', 'PCode', 'Country', 'Phone', 'Mobile',
		'Invited', 'EventYear','ToPrint','Message','OfficeNotes','NoShow','BusinessCard']);


	$crud->setRule('Email','required|email|callback_uniqueEmail[Email]'); 
	$crud->setRule('Company','callback_companyVerify[CN_Company]');
	$crud->setRule('CN_Company','callback_companyVerify[Company]');
	$crud->setRule('GivenName','callback_givenNameVerify[ChineseName]');
	$crud->setRule('FamilyName','callback_familyNameVerify[ChineseName]');
	$crud->setRule('ChineseName','callback_givenNameVerify[FamilyName]');
	$crud->setRule('Phone','callback_phoneVerify[Mobile]');
	$crud->setRule('Mobile','callback_phoneVerify[Phone]');
		
	$crud->displayAs('InvitedByCompanyID','Invited by');
	$crud->displayAs('Email','Email Address 电邮地址');
	$crud->displayAs('GivenName','Given (First) Name 名（英文）');
	$crud->displayAs('FamilyName','Family (Last) Name 姓（英文）');
	$crud->displayAs('ChineseName','Chinese Name （中文）');
	$crud->displayAs('Company','Company Name 公司名称（英文）');
	$crud->displayAs('CN_Company','Chinese Company Name 公司名称（中文）');
	$crud->displayAs('NameOnBadge','Given Name on Badge 名牌显示名');
	$crud->displayAs('Title','Job Title 抬头');
	$crud->displayAs('Address1','Street 地址行1');
	$crud->displayAs('Address2','Street 地址行2');
	$crud->displayAs('City','City 城市');
	$crud->displayAs('State','State/Province 州/省');
	$crud->displayAs('PCode','Postal/Zip Code 邮编');
	$crud->displayAs('Country','Country 国家');
	$crud->displayAs('Phone','Work Phone 单位电话');
	$crud->displayAs('Mobile','Mobile Phone 手机');
	$crud->displayAs('ToPrint','Queue badge for printing');
	$crud->displayAs('Message','Message to show to guest');
	$crud->displayAs('OfficeNotes','Internal office notes');
	$crud->displayAs('NoShow',"Did they no show? (yes = didn't attend)");
	$crud->displayAs('BusinessCard','Do we have their business card?');


	
	$crud->fieldType('EventYear', 'hidden');
	$crud->fieldType('Invited','hidden');
	$crud->fieldType('ToPrint','enum',array('No','Yes'));
	$crud->fieldType('NoShow','enum',array('No','Yes'));
	$crud->fieldType('BusinessCard','enum',array('No','Yes'));
	
	
	$crud->setLangString('delete_success_message',
		 'Your data has been successfully deleted from the database.<br/>Please wait while you are redirecting to the list page.\\n已从数据库里成功删除您的数据。正在返回列表，请稍后
		 <script type="text/javascript">
		  window.location = "'.site_url(strtolower(__CLASS__).'/'.strtolower(__FUNCTION__)).'";
		 </script>
		 <div style="display:none">
		 '
   ); 
	
	//$crud->setLanguage("english-chinese");
			
	$output = $crud->render();

	return $this->_example_output($output);         
} 

function singleprintchina ($type = "China2020", $graphics = TRUE)
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
$id = ltrim($link, "https://www.testconx.org/tools/secure.php/testconxbadge/Meptecsingle/");
echo  $id;

	
	//$this->db = $this->load->database('RegistrationDataBase', TRUE);
	$db = db_connect('registration');
	$builder = $db->table('guests');	
	$db = \Config\Database::connect();
	$db->setDatabase('bits_registration');
	//$db->select('NameOnBadge,ChineseName,GivenName,CN_Company,Company,Email,EventYear,FamilyName,ContactID,InvitedByCompanyID');

	
	//$this->db->where('EventYear', $type); //'professional');
	$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			$builder->where('ContactID',$id);
			$builder->where('ToPrint','Yes');
			
			$query = $builder->get();
	


	// one line of $results = $query->row_array();

	$people = $query->getNumRows();
	$results = $query->getResultArray();
	
	$height = '152.4'; // size in mm
	$width =  '101.6'; 
	
$pageLayout = array($width, $height);
$pdf = new Pdf('P', 'MM',$pageLayout, true, 'UTF-8', false);
$pdf->SetTitle('My Title');
$pdf->SetMargins(5,5,5,0);
$pdf->SetHeaderMargin(0);
$pdf->SetTopMargin(0);
$pdf->setFooterMargin(0);
$pdf->SetAutoPageBreak(true);
$pdf->SetAuthor('Author');
$pdf->SetDisplayMode('real', 'default');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
//$pdf->setImageScale(0.25);
$pdf->AddPage('P',$pageLayout);
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
	else if (!empty($results[$n]["ChineseName"])){
		 $this->qrstamp($results[$n]["NativeName"],$results[$n]["Email"],$results[$n]["Company"],$n);
	}
		$NameOnBadge=$results[$n]["NameOnBadge"];
		$ChineseName=$results[$n]["NativeName"];
		$GivenName=$results[$n]["GivenName"];
		$CN_Company=$results[$n]["CN_Company"];
		$FamilyName=$results[$n]["FamilyName"];
		$EventYear=$results[$n]["EventYear"];
		$Company=$results[$n]["Company"];
		$ContactID=$results[$n]["ContactID"];
		$InvitedByCompanyID=$results[$n]["InvitedByCompanyID"];
		$pdf->AddPage('P',$pageLayout);
		
		if($graphics){
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/images/TestConX-China-Black_750x133.png',0,12, 90, 15.7, 'PNG', '', '',false, 10, 'C', false, false, 1, false, false, false);
		}
		$pdf->SetFont('stsongstdlight', 'B', 75);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('helvetica', 'B', 75);
		$pdf->Ln(30);
	if (!empty($results[$n]["NameOnBadge"])){
		$pdf->SetFont('helvetica', 'B', 55);
		$pdf->Cell(0, 0, $NameOnBadge, 0, 1, 'C', 0, '', 1);
		
		$pdf->SetFont('stsongstdlight', 'B', 25);
		
		$pdf->Cell(0, 0, $ChineseName, 0, 1, 'C', 0, '', 1);	
	}
	else if (!empty($results[$n]["GivenName"])){
		$pdf->SetFont('helvetica', 'B', 55);
		$pdf->Cell(0, 0, $GivenName, 0, 1, 'C', 0, '', 1);
		
		$pdf->SetFont('stsongstdlight', 'B', 25);
		
		$pdf->Cell(0, 0, $ChineseName, 0, 1, 'C', 0, '', 1);
	}
	else {
		$pdf->SetFont('stsongstdlight', 'B', 55);
		$pdf->Cell(0, 0, $ChineseName, 0, 1, 'C', 0, '', 1);
		
	}
		$pdf->SetFont('stsongstdlight', 'B', 25);
		
		$pdf->Cell(0, 0,$CN_Company, 0, 1, 'C', 0, '', 1);
		$pdf->SetFont('helvetica', 'B', 25);
		
		$pdf->Cell(0, 0,$GivenName." ".strtoupper($FamilyName), 0, 1, 'C', 0, '', 1);
		
		$pdf->Cell(0, 0,$Company, 0, 1, 'C', 0, '', 1);
	
		$pdf->Image($_SERVER["DOCUMENT_ROOT"].'/tmpqr/'.$n.'08.png', 7, 118, 30, 30, 'PNG', '', '',false, 0, '', false, false, 1, false, false, false);

		$pdf->SetFillColor(224,146,47);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFont('helvetica', 'B', 15);
		$pdf->setCellPaddings(0, 6, 0, 0);
		if ($graphics){
		if($results[$n]["EventYear"]=='Suzhou2018'){
		$pdf->MultiCell(55,25,"Suzhou
		 October 23, 2018", 0, 'C', 1, 0, 37,110, true);
		}
		if($results[$n]["EventYear"]=='Shenzhen2018'){
		$pdf->SetFillColor(150,0,0);
		$pdf->MultiCell(55,25,"Shenzhen 
		October 25, 2018", 0, 'C', 1, 0, 37,110, true);
		}
		}
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('helvetica', '', 10);
	
		$pdf->MultiCell(90,10,$n."-".$ContactID."-".$InvitedByCompanyID, 0, 'R', 0, 0, 0,142, true);

		
		 $q++;
		 
			
} 
		
		

		
		
//ob_clean();
$pdf->Output('My-File-Name.pdf', 'I');
		

	}  

function edit_master_contact_URL($primary_key, $row) 
{
	$url = '';
	if ($row->MasterContactID > 0) {
		$url = 'https://www.testconx.org/tools/menu.php/database/contacts/edit/'. $row->MasterContactID;
	};
	return $url;
}

public function stats397927( $raw = FALSE )
{
	
	//$this->db = $this->load->database('RegistrationDataBase', TRUE);
	$db->setDatabase('bits_registration');
	$crud->setTable('chinacompany');
	$crud->defaultOrdering('chinacompany.Company', 'ASC');
	$query = $crud->getWhere(['EventYear'=> EventYear]);
	
	$inviteStats = array();
	$totalLimit = 0;
	$totalInvited = 0;
	$totalRelated = 0;
	$totalNoShow = 0;
	$totalNoShowRelated = 0;
	
	// Remember that the array keeps it order. So the order the variables are initialized control
	// which cell in the table they are output in.
	
	// Cycle through the companies
	foreach ($query->result() as $row)
	{
		//echo $row->Company . " id = " . $row->CompanyID . "<br>";
		$inviteStats[$row->CompanyID]['Company'] = $row->Company;
		$inviteStats[$row->CompanyID]['InviteCount'] = $row->InviteCount;
		$totalLimit += $row->InviteCount;
			
		$guestQuery = $this->db->get_where('guests',
			array('EventYear'=> EventYear, 
				  'InvitedByCompanyID' => $row->CompanyID) 
		);
		
		/* foreach ($guestQuery->result() as $guestRow)
		{
			echo $guestRow->Email . "<br>";
		}
		echo "Rows: " . $guestQuery->num_rows() . "<br>";
		*/
		
		$inviteStats[$row->CompanyID]['Guests'] = $guestQuery->num_rows();
		$totalInvited += $guestQuery->num_rows();
		
		$inviteStats[$row->CompanyID]['Remaining'] = $inviteStats[$row->CompanyID]['InviteCount'] - $inviteStats[$row->CompanyID]['Guests'];
		

		// Figure out how many guests are related to the inviting Company
		// i.e. non-customers
		$guestQuery = $crud->getWhere('guests',
			array('EventYear'=> EventYear, 
				  'InvitedByCompanyID' => $row->CompanyID,
				  'Related' => '1') 
		);

		$inviteStats[$row->CompanyID]['Related'] = $guestQuery->num_rows();
		$totalRelated += $guestQuery->num_rows();
		

		if ($inviteStats[$row->CompanyID]['Guests'] > 0) {
			$inviteStats[$row->CompanyID]['PercentRelated'] = round($inviteStats[$row->CompanyID]['Related'] / $inviteStats[$row->CompanyID]['Guests']*100,0) . "%";
		} else {
			$inviteStats[$row->CompanyID]['PercentRelated'] = "&nbsp;";
		}	
				
		// Count no-shows for post-event
		$guestQuery = $crud->getWhere('guests',
			array('EventYear'=> EventYear, 
				  'InvitedByCompanyID' => $row->CompanyID,
				  'NoShow' => 'Yes') 
		);
		
		$inviteStats[$row->CompanyID]['NoShows'] = $guestQuery->num_rows();
		$totalNoShow += $guestQuery->num_rows();
				
		// No-shows who are related
		$guestQuery = $this->db->get_where('guests',
			array('EventYear'=> EventYear, 
				  'InvitedByCompanyID' => $row->CompanyID,
				  'Related' => '1',
				  'NoShow' => 'Yes') 
		);
		$inviteStats[$row->CompanyID]['NoShow_Related'] = $guestQuery->num_rows();
		$totalNoShowRelated += $guestQuery->num_rows();
		

		$inviteStats[$row->CompanyID]['Notes'] = '&nbsp;';
	}
   		
   	$data['title'] = BiTSEvent . " Guest List Statistics"; 
   	$data['header'] = array ("Company", "Invite Limit", "Invited Guests", "Remaining", "Related Guests", "Related", "No Show", "No Show Related", "Notes");
   	
 	$data['table'] = $inviteStats;
 	$data['totals'][1] = array ("Totals", $totalLimit, $totalInvited, $totalLimit-$totalInvited, $totalRelated, "&nbsp;", $totalNoShow, $totalNoShowRelated, "&nbsp;");
 	$data['totals'][2] = array ("&nbsp;", "&nbsp;", round($totalInvited/$totalLimit*100,1)."% of Limit", "&nbsp;", "&nbsp;", round($totalRelated/$totalInvited*100,0)."%", 
 		round($totalNoShow/$totalInvited*100,0)."%", round($totalNoShowRelated/$totalRelated*100,0)."%","&nbsp;");
 	// ask ira about function load view look in bitscode view for stats
 	if (! $raw) {
		
	   	$this->load->view('stats', $data);
   	} else {
   		foreach($data['header'] as $x) { echo $x . ','; }; 
   		echo "<br>\n";
   		foreach($data['table'] as $x ) {
			foreach($x as $y => $y_value) {
				echo $y_value . ',';
			}
			echo "<br>\n";
		}
   		foreach($data['totals'] as $t) { 
   			foreach($t as $x) {
	   			echo $x . ','; 
	   		}
	   		echo "<br>\n";
	   	}
   	}
   	
	//$this->_example_output($output);        
}
 
public function stats397927raw()
{
	$this->stats397927(TRUE);
}
//ask ira leave alone and circleback
public function guest_listtest2(){
	$crud = $this->_getGroceryCrudEnterprise('registration');
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());
	
	
	
	$crud->setTable('guests');
	$crud->where(['InvitedByCompanyID'=>'2',
					'EventYear'=> 'China2019']); 
	$crud->setSubject('Guest','Guests');
	$output = $crud->render();
	
	
	
	
	return $this->_example_output($output);
}
public function guest_listtest()
{
	//https://www.testconxchina.org/ci.php/china/guest_list/?id=iwp093bczs
	$session = session(); 
	
	
	/* Check if a secret key is passed */
	if (isset($_GET["id"]))  {
		$secretKey = preg_replace("/[^a-zA-Z0-9]+/", "", $_GET["id"]); /* Try to santize any inputs */
		$_SESSION["SecretKey"] = $secretKey;
	} else {
		$secretKey = $_SESSION["SecretKey"];
	}
	//echo "secretkey = ".$secretKey."\n <br>";
	
	$db = db_connect('registration');
	$builder = $db->table('chinacompany');
	$builder->where('SecretKey', $secretKey);
	
	if ($builder->countAllResults(false) != 1) {
		sleep(20); /* slow down a brute force */ 
		echo "<pre>";
		echo "<h1>Error - Please use the special link provided or contact the office for assistance.</h1>";
		echo "</pre>";
		echo "countAllResults = ".$builder->countAllResults(false)."\n <br>";
		echo "secretkey = ".$secretKey."\n <br>";
		die();
	}
	$query = $builder->get();
	//$sql = 'SELECT * FROM chinacompany Where SecretKey = ? LIMIT 1;';
	//$query =$db->query($sql, [$secretKey]);
	$row = $query->getRow();
	//ask ira
	$companyID = $row->CompanyID;
	$_SESSION["CompanyID"] = $companyID; 
	$_SESSION["Company"] = $row->Company;
	$guestLimit = $row->InviteCount;
	$_SESSION["GuestLimit"] = $guestLimit;
	$staffID = $row->StaffID;
	$_SESSION["Event"] = BiTSEvent;
	$staffName = "TBD";
	
	/* echo "companyID = ".$companyID."\n <br>";
	echo "Session CompanyID = ".$_SESSION["CompanyID"]."\n <br>";
	echo "Session Company = ".$_SESSION["Company"]."\n <br>";
	echo "guestLimit = ".$guestLimit."\n <br>";
	echo "GuestLimit = ".$_SESSION["GuestLimit"]."\n <br>";
	echo "staffID = ".$staffID."\n <br>";
	echo "Session Event = ".$_SESSION["Event"]."\n <br>";
	echo "staffName = ".$staffName."\n <br>"; */
	
	
	if ($staffID > 0) {
	// ask ira
	$db3 = db_connect('registration');
	//$builder3 = $db3->table('guests');
	$sql3 = 'SELECT * FROM guests Where ContactID = ?;';
	$query3 =$db3->query($sql3, [$staffID]);
	$row = $query3->getRow();
	
	
		$staffName = $row->GivenName . " " . $row->FamilyName;
		//echo "staffName = ".$staffName."\n <br>";
	}
	$_SESSION["StaffName"] = $staffName;
	//echo "Session staffname".$_SESSION["StaffName"]."\n <br>";
	
	$db4 = db_connect('registration');
	$builder4 = $db4->table('guests');
	$builder4->where('InvitedByCompanyID' , $companyID);
	$builder4->where('EventYear', EventYear);
	//echo "builder count 4".$builder4->countAllResults(false)."\n <br>";
	$query4 = $builder4->get();
	$crud = $this->_getGroceryCrudEnterprise('registration');
	  if ($builder4->countAllResults(false) >= $guestLimit) {
		$crud->unsetAdd();
		
		//echo "builder count 4".$builder4->countAllResults(false)."\n <br>";
	}   
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());
	
	
	$crud->setTable('guests');
	$crud->where(['InvitedByCompanyID'=>$companyID,
					'EventYear'=> EventYear]); 
	$crud->setSubject('Guest 来宾', 'Guests 来宾');
	$output = $crud->render();
	 $newdata = [
    "SecretKey"  => $secretKey,
    "CompanyID"     => $companyID,
    "Company" => $row->Company,
	"GuestLimit" => $guestLimit,
	"Event" => BiTSEvent,
	"StaffName" => $staffName,
	"Output" => $output,
]; 

$session->set($newdata);		
	
	
	
	//return $this->_example_output($output);
	return $this->_one_company_output($output);     
	}
	public function multigrid() {
		$crud = $this->_getGroceryCrudEnterprise();

		$crud->setApiUrlPath('/Asiaguestchinese/guest_list');
		$output = $crud->render();

		$crud2 = $this->_getGroceryCrudEnterprise();

		$crud2->setApiUrlPath('/Asiaguestchinese/guest_list2');
		$output2 = $crud2->render();

		$output->output .= '<br/>' . $output2->output;

		return $this->_example_output($output);
	}
	public function guest_list()
	{
	//https://www.testconxchina.org/ci.php/china/guest_list/?id=iwp093bczs
	$session = session(); 
	
	
	/* Check if a secret key is passed */
	if (isset($_GET["id"]))  {
		$secretKey = preg_replace("/[^a-zA-Z0-9]+/", "", $_GET["id"]); /* Try to santize any inputs */
		$_SESSION["SecretKey"] = $secretKey;
	} else {
		$secretKey = $_SESSION["SecretKey"];
	}
$crud = $this->_getGroceryCrudEnterprise('registration');
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());

/* example 
$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			$builder->where('ContactID',$id);
			$builder->where('ToPrint','Yes');
			
			$query = $builder->get(); */
			
$db = db_connect('registration');
$builder = $db->table('chinacompany');
$builder->where('SecretKey', $secretKey);



	
	
	if ($builder->countAllResults(false) != 1) {
		sleep(20); /* slow down a brute force */ 
		echo "<pre>";
		echo "<h1>Error - Please use the special link provided or contact the office for assistance.</h1>";
		echo "</pre>";
		
		die();
	}
	$query = $builder->get();
	//$sql = 'SELECT * FROM chinacompany Where SecretKey = ? LIMIT 1;';
	//$query =$db->query($sql, [$secretKey]);
	$row = $query->getRow();
	//ask ira
	$companyID = $row->CompanyID;
	$_SESSION["CompanyID"] = $companyID; 
	$_SESSION["Company"] = $row->Company;
	$guestLimit = $row->InviteCount;
	$_SESSION["GuestLimit"] = $guestLimit;
	$staffID = $row->StaffID;
	$_SESSION["Event"] = BiTSEvent;
	
	$staffName = "TBD";
	if ($staffID > 0) {
	// ask ira
	$db3 = db_connect('registration');
	$builder3 = $db3->table('guests');
	$sql3 = 'SELECT * FROM guests Where ContactID = ?;';
	$query3 =$db3->query($sql3, [$staffID]);
	$row = $query3->getRow();
	
	
		$staffName = $row->GivenName . " " . $row->FamilyName;
	}
	$_SESSION["StaffName"] = $staffName;
	$db4 = db_connect('registration');
	$builder4 = $db4->table('guests');
	$builder4->where('InvitedByCompanyID' , $companyID);
	$builder4->where('EventYear', EventYear);
	//echo $builder4->countAllResults(false);
	if ($builder4->countAllResults(false) >= $guestLimit) {
		$crud->unsetAdd();
		
	}   
	$query4 = $builder4->get();
	//echo $builder4->countAllResults(false);
	  
	

	$crud->setTable('guests');
	$crud->where(['InvitedByCompanyID'=>$companyID,
					'EventYear'=> EventYear]); 
	$crud->setSubject('Guest 来宾', 'Guests 来宾');

	
	 $crud->columns(['Email',
	'GivenName',
	'FamilyName',
	'ChineseName',
	'Company',
	'CN_Company']); 
	$crud->fields([
	'Email',
	'GivenName',
	'FamilyName',
	'InvitedByCompanyID',
	'EventYear',
	'ChineseName',
	'NameOnBadge',
	'Title',
	'Company',
	'CN_Company',
	'Address1',
	'Address2',
	'City',
	'State',
	'PCode',
	'Country',
	'Phone',
	'Mobile',
	'ToPrint'
	]);
	$crud->readOnlyFields([
	'InvitedByCompanyID',
	'EventYear']);
	

\Valitron\Validator::addRule('checkCompany', function($field, $value, array $params, array $fields) {
  $text=trim($value);

  if ($text === null || $text === '') {
  if($fields['CN_Company'] || $fields['CN_Company']){
	  return false;
  }
  return false;
}
  return true;
  

}, 'Use English or Chinese company name. 请使用英文公司名或中文公司名');



\Valitron\Validator::addRule('checkFamilyName', function($field, $value, array $params, array $fields) {
	$text=trim($value);
 
  if ($text === null || $text === '') {
  
  return false;
}
  return true;



}, 'English Family (Last) or Chinese Name required. 请输入中文/英文姓');
	
	
 

\Valitron\Validator::addRule('checkPhone', function($field, $value, array $params, array $fields) {
  $text=trim($value);
 
 if ($text === null || $text === '') {
  
  return false;
}
  return true;
 

},'Work or Mobile phone number required. 请输入联系方式');
  
 
\Valitron\Validator::addRule('checkEmail', function($field, $value, array $params, array $fields)
{
	
	$db2 = db_connect('registration');

	$builder2 = $db2->table('guests');

	$builder2->where('EventYear', EventYear);
	$builder2->where('Email', $value);
   
   $rowcount = (int)$builder2->countAllResults(false);
 
	if($rowcount != 0)
	{
		$sql = 'SELECT ContactID FROM guests Where EventYear = ? AND Email = ?;';

		$query2 =$db2->query($sql,[EventYear,$value]);
		$row2 = $query2->getRow();
		
		$foundID =$row2->ContactID;
	
			if($foundID != $fields['ContactID'])
			{
			return false;
			}
		
	}
	return true;
	
	
},'Someone has already invited that person since the email already exists on the guest list. Email addresses must be unique.<br>该客户已被邀请，邮箱地址已出现在客户列表上。邮箱地址不能重复。');





	
	$crud->setRule('Email','required');
	$crud->setRule('Email','email');
	$crud->setRule('Email','checkEmail');
	$crud->setRule('Company','checkCompany');
	$crud->setRule('Company','required');
	$crud->setRule('CN_Company','checkCompany');
	$crud->setRule('GivenName','checkFamilyName');
	$crud->setRule('GivenName','required');
	$crud->setRule('FamilyName','checkFamilyName');
	$crud->setRule('FamilyName','required');
	$crud->setRule('ChineseName','checkFamilyName');
	$crud->setRule('Phone','checkPhone');
	$crud->setRule('Mobile','checkPhone');
		
	$crud->displayAs('Email','Email Address 电邮地址');
	$crud->displayAs('GivenName','Given (First) Name 名（英文）');
	$crud->displayAs('FamilyName','Family (Last) Name 姓（英文）');
	$crud->displayAs('ChineseName','Chinese/Korean Name');
	$crud->displayAs('Company','Company Name 公司名称（英文）');
	$crud->displayAs('CN_Company','Chinese Company Name 公司名称（中文）');
	$crud->displayAs('NameOnBadge','First Name on Badge 名牌显示名');
	$crud->displayAs('Title','Job Title 抬头');
	$crud->displayAs('Address1','Street 地址行1');
	$crud->displayAs('Address2','Street 地址行2');
	$crud->displayAs('City','City 城市');
	$crud->displayAs('State','State/Province 州/省');
	$crud->displayAs('PCode','Postal/Zip Code 邮编');
	$crud->displayAs('Country','Country 国家');
	$crud->displayAs('Phone','Work Phone 单位电话');
	$crud->displayAs('Mobile','Mobile Phone 手机');

	
	/* $crud->fieldType('ContactID', 'hidden');
	$crud->fieldType('InvitedByCompanyID','hidden');
	$crud->fieldType('EventYear','hidden');
	$crud->fieldType('BanquetCompanyID','hidden');
	$crud->fieldType('Invited','hidden');
	$crud->fieldType('ToPrint','hidden');  */
	
	$crud->fieldType('hidden','ContactID');
	$crud->fieldType('hidden','InvitedByCompanyID');
	$crud->fieldType('hidden','EventYear');
	$crud->fieldType('hidden','BanquetCompanyID');
	$crud->fieldType('hidden','Invited');
	$crud->fieldType('hidden','ToPrint'); 
	// if we've edited it or added it we should set it to print
	
	// Don't set so default update occurs $this->grocery_crud->field_type('Stamp','hidden');
	
	//No need to do this as a callback since can set value with hidden type immediately above
	//$this->grocery_crud->callback_before_insert(array($this,'set_invited_by'));

	// Force a refresh after a delete in case the number of guests falls below the guest 
	// limit so the add button is shown again	
	/* $crud->setLangString('delete_success_message',
		 'Your data has been successfully deleted from the database.<br/>Please wait while you are redirecting to the list page.\\n已从数据库里成功删除您的数据。正在返回列表，请稍后
		 <script type="text/javascript">
		  window.location = "'.site_url(strtolower(__CLASS__).'/'.strtolower(__FUNCTION__)).'";
		 </script>
		 <div style="display:none">
		 '
   );  */
	//$crud->setLanguagePath('/tcxcode/vendor/grocrey-crud/enterprise/src/GroceryCrud/i18n/');
	$crud->setLanguage('Spanish');
	//$crud->setLanguage("english-chinese");
	$output = $crud->render();
	$newdata = [
    "SecretKey"  => $secretKey,
    "CompanyID"     => $companyID,
    "Company" => $row->Company,
	"GuestLimit" => $guestLimit,
	"Event" => BiTSEvent,
	"StaffName" => $staffName,
	"Output" => $output,
	];

	$session->set($newdata);		
	
	
	
	//return $this->_example_output($output);
	return $this->_one_company_output($output);        
	}
  public function guest_list2()
{
	//https://www.testconxchina.org/ci.php/china/guest_list/?id=iwp093bczs
	$session = session(); 
	
	
	/* Check if a secret key is passed */
	if (isset($_GET["id"]))  {
		$secretKey = preg_replace("/[^a-zA-Z0-9]+/", "", $_GET["id"]); /* Try to santize any inputs */
		$_SESSION["SecretKey"] = $secretKey;
	} else {
		$secretKey = $_SESSION["SecretKey"];
	}
$crud = $this->_getGroceryCrudEnterprise('registration');
	$crud->setCsrfTokenName(csrf_token());
    $crud->setCsrfTokenValue(csrf_hash());

/* example 
$db = \Config\Database::connect();
			$builder = $db->table('contacts');
			$builder->select('*');
			$builder->where('ContactID',$id);
			$builder->where('ToPrint','Yes');
			
			$query = $builder->get(); */
			
$db = db_connect('registration');
$builder = $db->table('chinacompany');
$builder->where('SecretKey', $secretKey);



	
	
	if ($builder->countAllResults(false) != 1) {
		sleep(20); /* slow down a brute force */ 
		echo "<pre>";
		echo "<h1>Error - Please use the special link provided or contact the office for assistance.</h1>";
		echo "</pre>";
		
		die();
	}
	$query = $builder->get();
	//$sql = 'SELECT * FROM chinacompany Where SecretKey = ? LIMIT 1;';
	//$query =$db->query($sql, [$secretKey]);
	$row = $query->getRow();
	//ask ira
	$companyID = $row->CompanyID;
	$_SESSION["CompanyID"] = $companyID; 
	$_SESSION["Company"] = $row->Company;
	$guestLimit = $row->InviteCount;
	$_SESSION["GuestLimit"] = $guestLimit;
	$staffID = $row->StaffID;
	$_SESSION["Event"] = BiTSEvent;
	
	$staffName = "TBD";
	if ($staffID > 0) {
	// ask ira
	$db3 = db_connect('registration');
	$builder3 = $db3->table('guests');
	$sql3 = 'SELECT * FROM guests Where ContactID = ?;';
	$query3 =$db3->query($sql3, [$staffID]);
	$row = $query3->getRow();
	
	
		$staffName = $row->GivenName . " " . $row->FamilyName;
	}
	$_SESSION["StaffName"] = $staffName;
	$db4 = db_connect('registration');
	$builder4 = $db4->table('guests');
	$builder4->where('InvitedByCompanyID' , $companyID);
	$builder4->where('EventYear', EventYear);
	//echo $builder4->countAllResults(false);
	if ($builder4->countAllResults(false) >= $guestLimit) {
		$crud->unsetAdd();
		
	}   
	$query4 = $builder4->get();
	//echo $builder4->countAllResults(false);
	  
	

	$crud->setTable('guests');
	$crud->where(['InvitedByCompanyID'=>$companyID,
					'EventYear'=> EventYear]); 
	$crud->setSubject('Guest 来宾', 'Guests 来宾');

	
	 $crud->columns(['Email',
	'GivenName',
	'FamilyName',
	'ChineseName',
	'Company',
	'CN_Company']); 
	$crud->fields([
	'Email',
	'GivenName',
	'FamilyName',
	'InvitedByCompanyID',
	'EventYear',
	'ChineseName',
	'NameOnBadge',
	'Title',
	'Company',
	'CN_Company',
	'Address1',
	'Address2',
	'City',
	'State',
	'PCode',
	'Country',
	'Phone',
	'Mobile',
	'ToPrint'
	]);
	$crud->readOnlyFields([
	'InvitedByCompanyID',
	'EventYear']);
	

\Valitron\Validator::addRule('checkCompany', function($field, $value, array $params, array $fields) {
  $text=trim($value);

  if ($text === null || $text === '') {
  if($fields['CN_Company'] || $fields['CN_Company']){
	  return false;
  }
  return false;
}
  return true;
  

}, 'Use English or Chinese company name. 请使用英文公司名或中文公司名');



\Valitron\Validator::addRule('checkFamilyName', function($field, $value, array $params, array $fields) {
	$text=trim($value);
 
  if ($text === null || $text === '') {
  
  return false;
}
  return true;



}, 'English Family (Last) or Chinese Name required. 请输入中文/英文姓');
	
	
 

\Valitron\Validator::addRule('checkPhone', function($field, $value, array $params, array $fields) {
  $text=trim($value);
 
 if ($text === null || $text === '') {
  
  return false;
}
  return true;
 

},'Work or Mobile phone number required. 请输入联系方式');
  
 
\Valitron\Validator::addRule('checkEmail', function($field, $value, array $params, array $fields)
{
	
	$db2 = db_connect('registration');

	$builder2 = $db2->table('guests');

	$builder2->where('EventYear', EventYear);
	$builder2->where('Email', $value);
   
   $rowcount = (int)$builder2->countAllResults(false);
 
	if($rowcount != 0)
	{
		$sql = 'SELECT ContactID FROM guests Where EventYear = ? AND Email = ?;';

		$query2 =$db2->query($sql,[EventYear,$value]);
		$row2 = $query2->getRow();
		
		$foundID =$row2->ContactID;
	
			if($foundID != $fields['ContactID'])
			{
			return false;
			}
		
	}
	return true;
	
	
},'Someone has already invited that person since the email already exists on the guest list. Email addresses must be unique.<br>该客户已被邀请，邮箱地址已出现在客户列表上。邮箱地址不能重复。');





	
	$crud->setRule('Email','required');
	$crud->setRule('Email','email');
	$crud->setRule('Email','checkEmail');
	$crud->setRule('Company','checkCompany');
	$crud->setRule('Company','required');
	$crud->setRule('CN_Company','checkCompany');
	$crud->setRule('GivenName','checkFamilyName');
	$crud->setRule('GivenName','required');
	$crud->setRule('FamilyName','checkFamilyName');
	$crud->setRule('FamilyName','required');
	$crud->setRule('ChineseName','checkFamilyName');
	$crud->setRule('Phone','checkPhone');
	$crud->setRule('Mobile','checkPhone');
		
	$crud->displayAs('Email','Email Address 电邮地址');
	$crud->displayAs('GivenName','Given (First) Name 名（英文）');
	$crud->displayAs('FamilyName','Family (Last) Name 姓（英文）');
	$crud->displayAs('ChineseName','Chinese/Korean Name');
	$crud->displayAs('Company','Company Name 公司名称（英文）');
	$crud->displayAs('CN_Company','Chinese Company Name 公司名称（中文）');
	$crud->displayAs('NameOnBadge','First Name on Badge 名牌显示名');
	$crud->displayAs('Title','Job Title 抬头');
	$crud->displayAs('Address1','Street 地址行1');
	$crud->displayAs('Address2','Street 地址行2');
	$crud->displayAs('City','City 城市');
	$crud->displayAs('State','State/Province 州/省');
	$crud->displayAs('PCode','Postal/Zip Code 邮编');
	$crud->displayAs('Country','Country 国家');
	$crud->displayAs('Phone','Work Phone 单位电话');
	$crud->displayAs('Mobile','Mobile Phone 手机');

	
	/* $crud->fieldType('ContactID', 'hidden');
	$crud->fieldType('InvitedByCompanyID','hidden');
	$crud->fieldType('EventYear','hidden');
	$crud->fieldType('BanquetCompanyID','hidden');
	$crud->fieldType('Invited','hidden');
	$crud->fieldType('ToPrint','hidden');  */
	
	$crud->fieldType('hidden','ContactID');
	$crud->fieldType('hidden','InvitedByCompanyID');
	$crud->fieldType('hidden','EventYear');
	$crud->fieldType('hidden','BanquetCompanyID');
	$crud->fieldType('hidden','Invited');
	$crud->fieldType('hidden','ToPrint'); 
	// if we've edited it or added it we should set it to print
	
	// Don't set so default update occurs $this->grocery_crud->field_type('Stamp','hidden');
	
	//No need to do this as a callback since can set value with hidden type immediately above
	//$this->grocery_crud->callback_before_insert(array($this,'set_invited_by'));

	// Force a refresh after a delete in case the number of guests falls below the guest 
	// limit so the add button is shown again	
	/* $crud->setLangString('delete_success_message',
		 'Your data has been successfully deleted from the database.<br/>Please wait while you are redirecting to the list page.\\n已从数据库里成功删除您的数据。正在返回列表，请稍后
		 <script type="text/javascript">
		  window.location = "'.site_url(strtolower(__CLASS__).'/'.strtolower(__FUNCTION__)).'";
		 </script>
		 <div style="display:none">
		 '
   );  */
	//$crud->setLanguagePath('/tcxcode/vendor/grocrey-crud/enterprise/src/GroceryCrud/i18n/');
	$crud->setLanguage('Spanish');
	//$crud->setLanguage("english-chinese");
	$output = $crud->render();
	$newdata = [
    "SecretKey"  => $secretKey,
    "CompanyID"     => $companyID,
    "Company" => $row->Company,
	"GuestLimit" => $guestLimit,
	"Event" => BiTSEvent,
	"StaffName" => $staffName,
	"Output" => $output,
	];

	$session->set($newdata);		
	
	
	
	//return $this->_example_output($output);
	return $this->_one_company_output($output);        
	}                                
public function companyVerify($company, $otherField) {
  $this->form_validation->set_message('companyVerify','Use English or Chinese company name. 请使用英文公司名或中文公司名');
  return (trim($company) != '' || trim($this->input->post($otherField)) != '');
}

public function givenNameVerify($name, $otherField) {
  $this->form_validation->set_message('givenNameVerify','English Given (First) or Chinese Name required. 请输入中文/英文名');
  return (trim($name) != '' || trim($this->input->post($otherField)) != '');
}

public function familyNameVerify($name, $otherField) {
  $this->form_validation->set_message('familyNameVerify','English Family (Last) or Chinese Name required. 请输入中文/英文姓');
  return (trim($name) != '' || trim($this->input->post($otherField)) != '');
}
 
public function phoneVerify($phone, $otherField) {
  $this->form_validation->set_message('phoneVerify','Work or Mobile phone number required. 请输入联系方式');
  return (trim($phone) != '' || trim($this->input->post($otherField)) != '');
}

public function uniqueEmail($str)
{
	$uri = service('uri');
	$db2 = db_connect('registration');

$builder2 = $db2->table('guests');
  $builder2->select('Email');
  //ask ira this takes part of the uri, you can find it in the old codeigniter 3 docs
  $id =$uri->getSegment(4);
   // ask ira get where query
  if(!empty($id) && is_numeric($id))
  {
   $email_old = $builder2->where("ContactId",$id)->get('guests')->row()->Email;
   $builder2->whereNotIn("Email",$email_old);
  }
      
   $builder2->where('Email',$str);

  	// Only look for email dupes for this event year. The person may have attended in prior years. 
	$builder2->where('EventYear', EventYear); 
	$row_count = $builder2->get('guests')->countAllResults(false);

     
  if ($row_count >= 1)
  {
   $this->form_validation->set_message('uniqueEmail', 'Someone has already invited that person since the email already exists on the guest list. Email addresses must be unique.<br>该客户已被邀请，邮箱地址已出现在客户列表上。邮箱地址不能重复。');
   return FALSE;
  }
  else
  {
   return TRUE;
  }
}

  
function set_invited_by ($post_array) {
	$post_array['InvitedByCompanyID'] = $_SESSION["CompanyID"];
	
	return $post_array;
}

// Generate a SecretKey if one hasn't been set
function setSecretKey ($post_array) {
	// Technically not cryptographically strong - but good enough 
	// see docs https://www.codeigniter.com/user_guide/helpers/string_helper.html
	// Better code from http://stackoverflow.com/questions/4356289/php-random-string-generator/3110742
	// however issues with random_string function name
	//
	// Also note:
	//    Using case insenstive (_ci) MySQL so no difference between upper & lower case
	//    And possibly even better to check for uniqeness on the new secret key
	if (trim($post_array['SecretKey']) == '') {
		$post_array['SecretKey'] = random_string('alnum', 10); //'doof'; //random_str(10);
	}
	return $post_array;
}
		 


function _one_company_output($output = null)
 
{
	
	  if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

//$this->load->view('one_company.php',$output);    
return view('one_company.php',(array)$output);
}


 private function _example_output($output = null) {
        if (isset($output->isJSONResponse) && $output->isJSONResponse) {
            header('Content-Type: application/json; charset=utf-8');
            echo $output->output;
            exit;
        }

        return view('testconx_template.php', (array)$output);
    }

    private function _getDbData($dbgroup = 'default') {
       // $db = (new ConfigDatabase())->default;
		$db = (new ConfigDatabase())->$dbgroup;
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
    private function _getGroceryCrudEnterprise($dbgroup = 'default', $bootstrap = true, $jquery = true) {
        $db = $this->_getDbData($dbgroup);

        $config = (new ConfigGroceryCrud())->getDefaultConfig();

        $groceryCrud = new GroceryCrud($config, $db);
        return $groceryCrud;
    }    
}
 
