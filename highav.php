#!/usr/bin/env php
<?php

use ProxBoss\API\Cluster\HA\Groups;
use ProxBoss\API\Cluster\HA\Resources;
use ProxBoss\API\Nodes;

require __DIR__ . "/vendor/autoload.php";


$opts = getopt('h', ['cluster:']);

$cluster = $opts['cluster'] ?? "default";
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
$g = new Groups();
$r = (new Resources())->usingQemu($vms);
foreach ($g->getAll() as $grp) {
	$gname = $grp->getName();
	print "Found group $gname:\n";
	$grpres = $r->getAllInGroup($gname);
	$usage = Resources::getResourceUsage($grpres);
	$memmb = ceil($usage['mem'] / 1024 / 1024);
	print "  Usage: " . $usage['count'] . " vms assigned " . $memmb . " MB ram and " . $usage['cores'] . " cpus\n";
}
