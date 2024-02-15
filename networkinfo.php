#!/usr/bin/env php
<?php

use ProxBoss\API\Nodes;

require __DIR__ . "/vendor/autoload.php";

$macs = ['0e2e4b0c98ba', '12318d24919e', '222cec62c03a', '9e177c8f9595', 'aa771d6e5f4c'];

$n = new Nodes();
$nodes = $n->getAllNodes();
foreach ($nodes as $n) {
	/** @var Node $n */
	print "Checking node " . $n->getNodeName() . ":\n";
	$vms = $n->getAllQemuVms();
	$interfaces = [];
	foreach ($vms['vmid'] as $q) {
		$ints = $q->getNetworkInterfaces();
		foreach ($ints as $i) {
			$m = $i->getMac();
			if (in_array($m, $macs)) {
				print "Found $m\n";
				var_dump($q);
				exit;
			}
			continue;
			$bn = $i->getBridgeName();
			if (empty($interfaces[$bn])) {
				$interfaces[$bn] = 1;
			} else {
				$interfaces[$bn]++;
			}
		}
	}
	foreach ($interfaces as $n => $count) {
		print "  $n: $count VMs\n";
	}
}


function getMacOwner(string $mac)
{
	$url = "https://api.macvendors.com/" . urlencode($mac);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);
	return $response;
}
