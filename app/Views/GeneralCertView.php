<?php

helper('form');

echo form_open('GeneralCert/certificates');
$data = [
	'name' => 'year',
	'id' => 'year',
	'value' => '',
	'required' => true,
	];
	
$data2 = [
	'name' => 'date',
	'id' => 'date',
	'value' => '',
	'required' => true,
	];
$data3 = [
	'name' => 'chair1',
	'id' => 'chair1',
	'value' => '',
	'required' => true,
	];
$data4 = [
	'name' => 'chair2',
	'id' => 'chair2',
	'value' => '',
	'required' => true,
	];
	
	echo form_label('What is the Date and Location of the Event','date');
	echo form_input($data2);
	echo form_label('What is the Year of the event','year');
	echo form_input($data);
	echo form_label('Who is first chair','chair1');
	echo form_input($data3);
	echo form_label('Who is second chair','chair2');
	echo form_input($data4);
	
	$options = [
    'Mesa'  => 'Mesa',
    'China'    => 'China',
    'Korea'  => 'Korea',
];


echo form_dropdown('event', $options);

echo form_submit('mysubmit', 'Submit Post!');

$string = '</div></div>';

echo form_close($string);

?>
	