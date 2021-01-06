<?php

// Copy this file to where you need it, this is used to receive and process the updates.
// @todo constructor here should be told to not create a new one if it doesnt exist.
require __DIR__ . '/vendor/autoload.php';
$wiki = new Embeddedwiki($_GET['field']);
$wiki->save($_POST['update']);
print trim($wiki->getContent());



