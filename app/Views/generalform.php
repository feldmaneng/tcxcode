<?php

helper('form');
echo form_open('Generalform/certs');
$data = [
    'name'     => 'year',
    'id'       => 'year',
    'value'    => '',
    'required' => true,
];
echo form_label('What is the Date and Location of Event', 'year');
echo form_input($data);

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