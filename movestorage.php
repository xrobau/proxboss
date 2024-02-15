#!/usr/bin/env php
<?php

use ProxBoss\API\Cluster\Tasks;
use ProxBoss\API\Node\Qemu;
use ProxBoss\API\Nodes;
use ProxBoss\API\Storage;

require __DIR__ . "/vendor/autoload.php";


$opts = getopt('h', [
	'help', 'force', 'live', 'single',
	'src:', 'dest:', 'node:', 'cluster:', 'match:',
	'skip:',
]);

$cluster = $opts['cluster'] ?? "";
$help = isset($opts['help']) || isset($opts['h']);
if (!$cluster || $help) {
	print "Usage: " . $argv[0] . " --cluster=[clustername] (Mandatory) --opt --opt...\n";
	print "  --help\tThis help\n";
	print "  --cluster=\tCluster to process - See token.json\n";
	print "  --src=\tSource datastore to move from\n";
	print "  --dest=\tDestination datastore to move to\n";
	print "  --node=\tOnly process VMs on this node\n";
	print "  --match=\tWildcard match of VM names\n";
	print "  --skip=\tComma separated list of VM IDs to not move (eg, big vms)\n";
	print " Flags:\n";
	print "  --live\tLaunch move tasks (if not provided, will only show what WOULD be moved)\n";
	print "  --single\tOnly process VMs on this node\n";
	print "  --force\t(Unimplemented) Force move even if another is in progress\n";
	exit;
}

$srcpool = $opts['src'] ?? '';
$destpool = $opts['dest'] ?? '';
$onlynode = $opts['node'] ?? false;
$glob = $opts['match'] ?? "*";
$single = isset($opts['single']);
$force = isset($opts['force']);
$live = isset($opts['live']);
$skip = [];
foreach (explode(',', $opts['skip'] ?? "") as $s) {
	if (!$s) {
		continue;
	}
	$skip[$s] = true;
}

\ProxBoss\API\Base::$connection = $cluster;

$s = new Storage();
$stores = [];
foreach ($s->getAll() as $ds) {
	if (!$ds->isNfs()) {
		continue;
	}
	$stores[] = $ds;
}

$src = false;
$dest = false;

foreach ($stores as $ds) {
	if (!$src && $ds->getName() === $srcpool) {
		$src = $ds;
	}
	if (!$dest && $ds->getName() === $destpool) {
		$dest = $ds;
	}
}

if (!$src) {
	foreach ($stores as $id => $datastore) {
		if ($dest && $datastore->getName() == $dest->getName()) {
			continue;
		}
		print "  $id - " . $datastore->getName() . "\n";
	}
	print "Unknown src pool name, or no --src provided\n";
	$sel = readline("Please select the src pool by number: ");
	$src = $stores[$sel];
}

if (!$dest) {
	foreach ($stores as $id => $datastore) {
		if ($src->getName() == $datastore->getName()) {
			continue;
		}
		print "  $id - " . $datastore->getName() . "\n";
	}
	print "Unknown dest pool name, or no --dest provided\n";
	$sel = readline("Please select the dest pool by number: ");
	$dest = $stores[$sel];
}

if ($onlynode) {
	print " * NOTE: Only checking $onlynode\n";
}

if ($single) {
	print " * NOTE: Only creating one move event\n";
}

$tomove = [];
$srcpool = $src->getName();

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
		if (isset($skip[$q->getVmId()])) {
			print "*** " . $q->getVmName() . " being skipped as it has vmid " . $q->getVmId() . "\n";
			continue;
		}
		/** @var Qemu $q */
		if (!fnmatch($glob, $q->getVmName())) {
			// print "Skipping " . $q->getVmName() . " as it does not match $glob\n";
			continue;
		}
		$changes = $q->getPendingChanges();
		if ($changes->areChangesPending()) {
			print "  **** Skipping " . $q->getVmName() . " as changes are pending - " . json_encode($changes->getPendingChanges()) . "\n";
			continue;
		}
		$unused = $q->getUnusedDisks();
		if ($unused) {
			print "  **** Unused Disks found on " . $q->getVmName() . "! Clean them up with cleanunused first\n";
			print json_encode($unused) . "\n";
			exit;
		}
		$stores = $q->getDatastores();
		if (!empty($stores[$srcpool])) {
			print "    " . $q->getVmName() . " has storage on $srcpool\n";
			$tomove[] = $q;
			if ($single) {
				break (2);
			}
		}
	}
}

if (!$tomove) {
	print "Nothing to move\n";
	exit;
}

$destpool = $dest->getName();
print "\n";

if (!$live) {
	print "** Dry Run! Not moving anything. Add --live to actually move VMs **\n\n";
	print "Found " . count($tomove) . " VMs to move\n";
	while ($q = array_shift($tomove)) {
		print "  VM " . $q->getVmName() . "\n";
		$vmstores = $q->getDatastores()[$srcpool] ?? [];
		foreach ($vmstores as $srcdisk) {
			print "    Want to move $srcdisk to $destpool\n";
		}
	}
	exit;
}

$tasks = new Tasks();
print "*** LIVE WAS ENABLED *** Moving VMs one at a time.\n";
while (count($tomove)) {
	print count($tomove) . " VM(s) to process...\n";
	/** @var Qemu $q */
	$q = array_shift($tomove);
	print "Starting on " . $q->getVmName() . "\n";

	$r = $tasks->getTasksForVm($q->getVmId(), true);
	if ($r) {
		print "Skipping vm " . $q->getVmName() . ", already a running task, something has broken.\n";
		exit;
	}
	$vmstores = $q->getDatastores(true)[$srcpool] ?? [];
	foreach ($vmstores as $srcdisk) {
		while (true) {
			$running = $tasks->getRunningTasks('qmmove');
			if (!$running) {
				break;
			}
			print microtime(true) . ": qmmove running tasks found. Sleeping for 5 seconds\n";
			foreach ($running as $t) {
				print "  VM ID " . $t->getVmId() . " has a task running for " . $t->getRunLength() . " seconds: ";
				$n = $t->getNodeTask();
				print $n->getLastLogLine() . "\n";
			}
			sleep(5);
		}
		print "Moving " . $q->getVmName() . " $srcdisk to $destpool\n";
		$task = $q->moveDisk($srcdisk, $destpool)['data'];
		print "Task created. Waiting for it to complete...\n";
		while (true) {
			$t = $tasks->getTaskById($task);
			if (!$t) {
				throw new \Exception("Could not find task, crashing");
			}
			print "\r  Task running for " . $t->getRunLength() . " seconds ... ";
			$n = $t->getNodeTask();
			print $n->getLastLogLine();
			// Erase the rest of the line from the cursor
			printf("\33[0K");
			if (!$t->isRunning()) {
				print " - Complete!\n";
				break;
			}
			sleep(5);
		}
	}
}
