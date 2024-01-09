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
    				
    				echo "<h1>$StatusMessage</h1>";
					
					echo "<h2>TestConX Event: $Event $Year</h2>";
					echo "<h2>Your status is ".$Entry->Status."</h2>";
					echo "<h2>$PromptMessage</h2>";
					
					// Maybe move this logic to model?
					if ($PriorEntryImage == "example.png") {
						echo "<h3>Example Exhibitor Directory entry:</h3>";
					} else {
						echo "<h3>Your previous Exhibitor Directory entry:</h3>";
					}
					?>
					
					<img src="<?php echo $logo_dir . $PriorEntryImage ?> " border="2"/>
					
			             
            </div> 
        </div>






