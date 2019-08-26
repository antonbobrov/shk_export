<?php

// get shopkeeper
$modx->addPackage('shopkeeper3', $modx->getOption('core_path').'components/shopkeeper3/model/');

// get statuses
$statuses = array();
$sql = "SELECT * FROM `modx_shopkeeper3_config` WHERE setting='statuses'";
$statement = $modx->query($sql);
$configs = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach ($configs as $c) {
    $ss = json_decode($c['value']);
    foreach ($ss as $s) {
        $statuses[$s->id] = $s->label;
    }
}

// a query to get all orders
$q = $modx->newQuery('shk_order');
$orders = $modx->getCollection('shk_order', $q);

// inputs data
$inputsBase = array(
    array(
        'name' => 'id',
        'label' => 'ID'
    ),
    array(
        'name' => 'status',
        'label' => 'Status'
    ),
    array(
        'name' => 'date',
        'label' => 'Date'
    ),
    array(
        'name' => 'price',
        'label' => 'Price'
    )
);
$inputsData = $inputsBase;

// orders data
$ordersData = array();

// go thru the orders and add all inputs to the stack
foreach($orders as $o) {
	$contacts = json_decode($o->contacts);
	foreach ($contacts as $contact) {
		$inputExists = false;
		foreach ($inputsData as $input) {
			if ($input['name'] === $contact->name) {
				$inputExists = true;
			}
		}
		if (!$inputExists) {
			$inputsData[] = array(
				'name' => $contact->name,
				'label' => $contact->label
			);
		}
	}
}

// get input names
$inputsLabels = array();
foreach ($inputsData as $input) {
	$inputsLabels[] = $input['label'];
}

// go thru the orders and add their data
foreach($orders as $o) {
	$arr = array();
	// add basic data
	foreach ($inputsBase as $b) {
	    if ($b['name'] == 'status') {
		    $arr[$b['name']] = $statuses[$o->get($b['name'])];
	    }
	    else {
		    $arr[$b['name']] = $o->get($b['name']);
	    }
	}
	// add contacts data
	$contacts = json_decode($o->get('contacts'));
	foreach ($contacts as $contact) {
		foreach ($inputsData as $input) {
			if ($input['name'] === $contact->name) {
			    $val = $contact->value;
			    if (in_array("mac", array('phone', 'telephone', 'tel'))) {
			        $val = str_replace("+", "", $val);
			    }
				$arr[$contact->name] = $val;
			}
		}
	}
	// add to stack
	$ordersData[] = $arr;
}

// sort by id
usort($ordersData, function($a, $b) {
    return $b['id'] - $a['id'];
});

// create file
$fp = fopen('php://output', 'w');
fputs($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

// Start output buffering (to capture stream contents)
ob_start();

// put data to csv
fputcsv($fp, $inputsLabels, ";");
foreach ($ordersData as $fields) {
    fputcsv($fp, $fields, ";");
}



// output

$string = ob_get_clean();
        
$filename = 'export_' . date('Ymd') .'_' . date('His');
        
// Output CSV-specific headers
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment filename=\"$filename.csv\";" );
header("Content-Transfer-Encoding: binary");
exit($string);
