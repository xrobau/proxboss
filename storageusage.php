#!/usr/bin/env php
<?php

use ProxBoss\API\Cluster\Tasks;
use ProxBoss\API\Node\Qemu;
use ProxBoss\API\Nodes;
use ProxBoss\API\Storage;

require __DIR__ . "/vendor/autoload.php";


$opts = getopt('h', [
	'help', 'cluster:',
]);

$cluster = $opts['cluster'] ?? "";
$help = isset($opts['help']) || isset($opts['h']);
if (!$cluster || $help) {
	print "Usage: " . $argv[0] . " --cluster=[clustername] (Mandatory) --opt --opt...\n";
	print "  --help\tThis help\n";
	print "  --cluster=\tCluster to process - See token.json\n";
	exit;
}

\ProxBoss\API\Base::$connection = $cluster;

$s = new Storage();
$stores = [];
$usage = [];
$servers = [];
foreach ($s->getAll() as $ds) {
	$name = $ds->getName();
	if (!$ds->isNfs()) {
		// print "  SKIPPING Datastore $name, is not NFS\n";
		continue;
	}
	$nfssrv = $ds->getNfsServer();
	$stores[$name] = $ds;
	$servers[$nfssrv][$name] = $ds;
	$usage[$name] = [];
}

$n = new Nodes();
$nodes = $n->getAllNodes();
foreach ($nodes as $n) {
	/** @var Node $n */
	$vms = $n->getAllQemuVms();
	foreach ($vms['vmid'] as $q) {
		/** @var Qemu $q */
		$unused = $q->getUnusedDisks();
		if ($unused) {
			print "  **** Unused Disks found! Clean them up with cleanunused first\n";
			print json_encode($unused) . "\n";
			var_dump($q);
			exit;
		}
		$vmstores = $q->getDatastores();
		foreach ($vmstores as $name => $disks) {
			if (empty($stores[$name])) {
				print "   ".$q->getVmName()." has a disk on an unknown store - " . json_encode($vmstores) . "\n";
				continue;
			}
			foreach ($disks as $d) {
				$key = $q->getVmName() . ".$d";
				$usage[$name][$key] = true;
			}
		}
	}
}


print "Storage Usage:\n";
foreach ($servers as $nfssrv => $d) {
	print "  NFS Server $nfssrv:\n";
	$counts = [];
	foreach ($stores as $name => $ds) {
		if (isset($servers[$nfssrv][$name])) {
			$counts[$name] = count($usage[$name]);
		}
	}
	arsort($counts);
	foreach ($counts as $name => $c) {
		print "    $name - $c disks\n";
	}
}
