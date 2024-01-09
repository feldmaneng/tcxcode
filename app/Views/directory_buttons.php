<?php
					echo "<div id='form_button'>";
				  
					echo form_submit('approve', 'Approve'); 
					echo form_label('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
					echo form_submit('draft', 'Save Draft'); 
					echo form_label('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
					echo form_submit('cancel', 'Cancel'); 
					echo "</div>";
					echo form_close();
			
					echo "</div>";
?>
