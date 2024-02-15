#!/usr/bin/env php
<?php

use ProxBoss\API\Cluster\Tasks;
use ProxBoss\API\Node\Qemu;
use ProxBoss\API\Nodes;
use ProxBoss\API\Storage;

require __DIR__ . "/vendor/autoload.php";


$opts = getopt('h', [
	'help', 'force', 'live', 'single',
	'src:', 'dest:', 'node:', 'cluster:', 'match:'
]);

$cluster = $opts['cluster'] ?? "";
$help = isset($opts['help']) || isset($opts['h']);
if (!$cluster || $help) {
	print "Usage: " . $argv[0] . " --cluster=[clustername] (Mandatory) --opt --opt...\n";
	print "  --help\tThis help\n";
	print "  --cluster=\tCluster to process - See token.json\n";
	print "  --node=\tOnly process VMs on this node\n";
	print "  --match=\tWildcard match of VM names\n";
	print " Flags:\n";
	print "  --live\tCleanup unused\n";
	print "  --single\tOnly process VMs on this node\n";
	print "  --force\t(Unimplemented) Force move even if another is in progress\n";
	exit;
}

$onlynode = $opts['node'] ?? false;
$glob = $opts['match'] ?? "*";
$single = isset($opts['single']);
$force = isset($opts['force']);
$live = isset($opts['live']);

\ProxBoss\API\Base::$connection = $cluster;

if ($onlynode) {
	print " * NOTE: Only checking $onlynode\n";
}

if ($single) {
	print " * NOTE: Only creating one cleanup event\n";
}

$toclean = [];

$n = new Nodes();
$nodes = $n->getAllNodes();
foreach ($nodes as $n) {
	/** @var Node $n */
	if ($onlynode && $onlynode !== $n->getNodeName()) {
		continue;
	}
	print " - Node " . $n->getNodeName() . "\n";
	$vms = $n->getAllQemuVms();
	foreach ($vms['vmid'] as $q) {
		/** @var Qemu $q */
		if (!fnmatch($glob, $q->getVmName())) {
			// print "Skipping " . $q->getVmName() . " as it does not match $glob\n";
			continue;
		}
		$unused = $q->getUnusedDisks();
		if (!empty($unused)) {
			print "    " . $q->getVmName() . " has unused stuff\n";
			$toclean[] = $q;
			if ($single) {
				break (2);
			}
		}
	}
}

if (!$toclean) {
	print "Nothing to clean\n";
	exit;
}

if (!$live) {
	print "** Dry Run! Not changing anything. Add --live to actually clean VMs **\n\n";
	print "Found " . count($toclean) . " VMs to clean\n";
	while ($q = array_shift($toclean)) {
		$unused = $q->getUnusedDisks();
		print "  VM " . $q->getVmName() . " has " . json_encode($unused) . "\n";
	}
	exit;
}

print "*** LIVE MODE ENABLED ***\n";
while (count($toclean)) {
	print count($toclean) . " VM(s) to process...\n";
	/** @var Qemu $q */
	$q = array_shift($toclean);
	print "Starting to clean " . $q->getVmName() . "\n";

	$unused = $q->getUnusedDisks();
	foreach ($unused as $u => $path) {
		print "Removing $u linked to $path\n";
		print "  Result: " . json_encode($q->unlinkUnused($path)) . "\n";
	}
}
