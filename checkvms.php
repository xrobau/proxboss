#!/usr/bin/env php
<?php

use ProxBoss\API\Node\Qemu;
use ProxBoss\API\Nodes;

require __DIR__ . "/vendor/autoload.php";

$opts = getopt('h', ['cluster:','cpu:']);

$cluster = $opts['cluster'] ?? "default";
$cpu = $opts['cpu'] ?? null;

if ($cluster === "default") {
	print "** No cluster selected - Using default 'cipau' cluster\n";
	$cluster = "cipau";
}

\ProxBoss\API\Base::$connection = $cluster;

// Get all VMs
$vms = [];
$n = new Nodes();
$nodes = $n->getAllNodes();
foreach ($nodes as $n) {
	/** @var Node $n */
	$allvms = $n->getAllQemuVms();
	foreach ($allvms['vmid'] as $id => $q) {
		$vms[$id] = $q;
	}
}

foreach ($vms as $id => $q) {
	/** @var Qemu $q */
	$checks = $q->isVmConfigCorrect($cpu);
	if (!empty($checks)) {
		print "VM id $id (" . $q->getVmName() . ") has config errors:\n";
		foreach ($checks as $e) {
			print "  - " . $e . "\n";
		}
	}
}
