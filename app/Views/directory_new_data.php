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

					echo form_open('/directory'); ///data_submitted');
					
					echo form_label('Company Name:' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    $data_name = array(
                        'name' => 'comp_name',
                        'id' => 'comp_name_id',
                        'class' => 'textarea',
						'rows' => 1,
                        'cols' => 64,
						'value' => $Entry->CompanyName
                     //   'placeholder' => $Entry->CompanyName
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
						'value' => $Entry->Line1
                    //    'placeholder' => $Entry->Line1
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
						'value' => $Entry->Line2
                     //   'placeholder' => $Entry->Line2
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
						'value' => $Entry->Line3
						//'placeholder' => $Entry->Line3
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
						'value' => $Entry->Line4
						//'placeholder' => $Entry->Line4
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
						'value' => $Entry->Line5
						//'placeholder' => $Entry->Line5
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
						'value' => $Entry->Line6
						//'placeholder' => $Entry->Line6
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
						'value' => $Entry->Description
					 //	'placeholder' => $Entry->Description
                    );
                    
                    echo form_textarea($description_textarea);
                    echo "<br>";
					echo "<br>";
			?>
				</div>
				
		             
            </div> 
        </div>
<!--    </body>
</html>
-->






