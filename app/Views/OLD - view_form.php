<html>
    <head>		
        <title>TestConX EXPO Workshop Guide</title>
    </head>
	<style>
	textarea { vertical-align: top; }
	</style>
    <body> 
        <div class="main">
            <div id="content">
                <h1 id='form_head'>TestConX EXPO Workshop Guide</h1>
				              
                <div id="form_input">
                    <?php
					$session = session(); 
					$demo_key = session('secretKey');
					$logo_dir = "/EXPOdirectory/";
					$dateformat = 'DATE_W3C';
					//IMF not needed & wrong database (this loads default) $this->load->database();
					/* $this->load->helper('date');
					$this->load->helper('html');
					$this->load->library('session');
					$this->load->helper('form');
					$this->load->helper(array('form', 'url')); */
					helper('text');
					date_default_timezone_set('America/Los_Angeles');
					/* $this->load->library('table');
					$this->load->library('user_agent'); */
					/* $this->db = $this->load->database('RegistrationDataBase',TRUE);
					$this->db->select('*');
					$this->db->from('expodirectory'); */
					
					$db = db_connect('registration');
					$builder = $db->table('expodirectory');
					
					$builder->where('SecretKey', $demo_key);
					$sql = 'SELECT * FROM expodirectory Where SecretKey = ? LIMIT 1;';
					$query =$db->query($sql, [$demo_key]);
					
					// We should check to make sure we actually returned a single row, if not die
					
					$row = $query->getRow();
					$entryid = $row->EntryID;
					$session->set('entryIDname', $entryid);
					$newviewdata = [
					"secretKey"  => $demo_key,
					'entryid'     => $entryid,
					];
					
					if ($row->Status == "Draft") 
					{
						//die('test1');
						if(session('success') == 'saved') {
							echo '<h1>Your information was successfully updated.<h1>';
							//echo heading('Your information was successfully updated.', 1, 'style="color:#52D017"');
							echo "<h2>$row->Event</h2>";
							echo "<h2>$row->Year</h2>";
							echo "<h2>Your status is $row->Status $row->Event $row->Year.</h2>";
							echo "<h2>Please make any changes below and press either Approve, Save Draft, or Cancel button.</h2>";
							//die('test2');
						} else {
							//die('test3');
							echo "<h2>$row->Event</h2>";
							echo "<h2>$row->Year</h2>";
							echo "<h2>Your status is $row->Status $row->Event $row->Year.</h2>";
							echo "<h2>Please make any changes below and press either Approve, Save Draft, or Cancel button.</h2>";
						}
					}
					else
					{
					if(session('success') == 'saved') {
						echo '<h1>Your information was successfully updated.<h1>';
						//echo heading('Your information was successfully updated.', 1, 'style="color:#52D017"');
					}
						echo "<h2>$row->Event</h2>";
						echo "<h2>$row->Year</h2>";
					echo "<h2>Your status is $row->Status $row->Event $row->Year.<br/><br/>Please contact TestConX Office if further changes are needed.</h2>";
					}
					$session->set('success', "");
					//unset(session('success'));
					if ($row->SampleEntry == '')
					{ $logo_loc = "example.png"; 
					echo "<h3>Example Exhibitor Directory entry:</h3>";
					}
					else
					{ $logo_loc = $row->SampleEntry; 
					echo "<h3>Your previous Exhibitor Directory entry:</h3>";
					}
					?>
					<img src="<?php echo $logo_dir . $logo_loc; ?> " border="2"/>
					<?php
					if ($row->Status == "Draft") {
						//die('test1');
					echo "<h3>Upload new logo file if logo shown above needs updating.</h3>";
					echo "<h3>Please contact the TestConX Office if you are not able to upload your logo file.</h3>";
					//die('test1');
					//return added here ask ira
					echo view('upload_form');
					}
					else
					{
					echo "<br />";
                    echo "<br />";
					}
					//die('test106');
					echo form_open('/form/data_submitted');
					
					//die('test106');
					echo form_label('Company Name:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    $data_name = array(
                        'name' => 'comp_name',
                        'id' => 'comp_name_id',
                        'class' => 'textarea',
						'rows' => 1,
                        'cols' => 64,
						'value' => $row->CompanyName
                     //   'placeholder' => $row->CompanyName
		            );
					echo form_textarea($data_name);
					echo "<br>";
                    echo "<br>";
                    echo form_label('Contact Name:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    $data_coord = array(
                        'name' => 'coord_name',
                        'id' => 'coord_name_id',
                        'class' => 'textarea',
						'rows' => 1,
                        'cols' => 64,
						'value' => $row->Line1
                    //    'placeholder' => $row->Line1
                    );
                    echo form_textarea($data_coord);
                    echo "<br>";
                    echo "<br>";
                    echo form_label('Email:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    $data_email = array(
						'type' => 'email',
                        'name' => 'comp_email',
                        'id' => 'comp_email_id',
                        'class' => 'textarea',
						'rows' => 1,
                        'cols' => 64,
						'value' => $row->Line2
                     //   'placeholder' => $row->Line2
                    );
                    echo form_textarea($data_email);
                    echo "<br>";
                    echo "<br>";
					
					echo form_label('Address 1:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    $address1_textarea = array(
                        'name' => 'address1_change',
						'id' => 'address1_change_id',
						'class' => 'textarea',
                        'rows' => 1,
                        'cols' => 64,
						'value' => $row->Line3
						//'placeholder' => $row->Line3
                    );
                    echo form_textarea($address1_textarea);
                    echo "<br>";
					echo "<br>";	
					
					echo form_label('Address 2:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    $address2_textarea = array(
                        'name' => 'address2_change',
						'id' => 'address2_change_id',
						'class' => 'textarea',
                        'rows' => 1,
                        'cols' => 64,
						'value' => $row->Line4
						//'placeholder' => $row->Line4
                    );
                    echo form_textarea($address2_textarea);
                    echo "<br>";
					echo "<br>";	
					
					echo form_label('Phone:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    $phone_textarea = array(
                        'name' => 'phone_change',
						'id' => 'phone_change_id',
						'class' => 'textarea',
                        'rows' => 1,
                        'cols' => 64,
						'value' => $row->Line5
						//'placeholder' => $row->Line5
                    );
                    echo form_textarea($phone_textarea);
                    echo "<br>";
					echo "<br>";	
				
					echo form_label('Website:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    $website_textarea = array(
                        'name' => 'website_change',
						'id' => 'website_change_id',
						'class' => 'textarea',
                        'rows' => 1,
                        'cols' => 64,
						'value' => $row->Line6
						//'placeholder' => $row->Line6
                    );
                    echo form_textarea($website_textarea);
                    echo "<br>";
					echo "<br>";	
					
					echo form_label('Description:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
					$description_textarea = array(
                        'name' => 'description_change',
						'id' => 'description_change_id',
						'class' => 'textarea',
                        'rows' => 6,
						'cols' => 64,
						'maxlength' => 330,
						'value' => $row->Description
					 //	'placeholder' => $row->Description
                    );
                    
                    echo form_textarea($description_textarea);
                    echo "<br>";
					echo "<br>";
			?>
				</div>
				
			<?php
				
                if ($row->Status == "Draft") {
					echo "<div id='form_button'>";
				  
					echo form_submit('approve', 'Approve'); 
					echo form_label('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
					echo form_submit('draft', 'Save Draft'); 
					echo form_label('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
					echo form_submit('cancel', 'Cancel'); 
					echo "</div>";
					echo form_close();
			
					echo "</div>";
					$request = \Config\Services::request();
//ask ira about input->post

					// This seems like bad code as $company_name isn't set anywhere that I see
					//if (isset($company_name)) {
						if($request->getPost('cancel')) {
							header("Location: https://www.testconx.org/");
						}
						//dd($request);
						if ($request->getPost('approve') || $request->getPost('draft')) {
							//echo "<h1>About to do the Post for approve and draft commands</h1>";
							//print_r($request);
							//die();
							$saved = 'saved';
							$session->set('success', $saved);
							if($request->getPost('approve')) {
								$status = 'Approved';
								$session->set('updated', "approve");
								echo "<h3>Your data was successfully updated as Approved.</h3>";
							} else {
								$status = 'Draft';
								$session->set('updated', "draft");
								echo "<h3>Your data was successfully updated as Draft.</h3>";
							}
							
							$upload_status = $session->set('upload_status');
							$data_update = array(
							'CompanyName' => $company_name,
							'Line1' => $coordinator_name,
							'Line2' => $email_address,
							'Line3' => $address1_change,
							'Line4' => $address2_change,
							'Line5' => $phone_change,
							'Line6' => $website_change,
							'Description' => $description_change,
							'Updated' => date("Y-m-d H:i:s"),
							'Status' => $status,
							'Upload' => $upload_status
							);
					
							$builder->where('SecretKey', $demo_key);
							//$this->db->where('SecretKey', $demo_key);
							//$this->db->update('test', $data_update);
							//dd($data_update);
							$builder->update($data_update);
							
								//return redirect()->back();	
								return redirect()->to('/directory?key='.$demo_key);	
					
						//return redirect('/test5?key='.session('secretKey'));
					
					}
				//}
				
			}
				
				?> 
				
			             
            </div> 
        </div>
    </body>
</html>







