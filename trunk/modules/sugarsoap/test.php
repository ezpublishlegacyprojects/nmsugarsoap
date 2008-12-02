<?php

require_once("extension/nmsugarsoap/classes/sugarsoap.php");
include_once( "kernel/common/template.php" );

// initiate objects
$soap 		= new SugarSoap();
$tpl 		=& templateInit();

// fetch contacts
$contactsList = $soap->getContactsList("", 100, " contacts.first_name asc");

$tpl->setVariable("contacts_list", $contactsList);

$Result['content'] =& $tpl->fetch( "design:content/datatype/edit/nmsugarcontact.tpl" );

?> 