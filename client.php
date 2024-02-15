#!/usr/bin/env php
<?php

use ProxBoss\API\Cluster\Tasks;
use ProxBoss\API\Node\Qemu;
use ProxBoss\API\Nodes;

require __DIR__ . "/vendor/autoload.php";

$r = (new Tasks())->getRunningTasks('qmmove');
if ($r) {
	print "Running tasks, try again later\n";
	exit;
}
$srcpool = "pool2-storage";
$destpool = "store2-image";

$n = new Nodes();
$nodes = $n->getAllNodes();
foreach ($nodes as $n) {
	/** @var Node $n */
	if ($n->getNodeName() == "larry") {
		continue;
	}
	print "Checking node " . $n->getNodeName() . "\n";
	$vms = $n->getAllQemuVms();
	foreach ($vms['vmid'] as $q) {
		/** @var Qemu $q */
		$stores = $q->getDatastores();
		if (!empty($stores[$srcpool])) {
			print $q->getVmName() . " has storage on $srcpool\n";
		}
	}
}

/*
use ProxBoss\Client;

$client = new Client();

$nodes = getNodes($client);

$node = $nodes['zoidberg'];
$vms = getVMsOnNode($node);
$storage = getVmStorage($vms['vmid'][203]);

function getNodes($client) {
	$res = $client->get('nodes');
	$data = json_decode($res->getBody(), true);
	$retarr = [];
	foreach ($data['data'] as $r) {
		$r['client'] = $client;
		$node = $r['node'];
		$retarr[$node] = $r;
	}
	return $retarr;
}

function getVMsOnNode($node) {
	$client = $node['client'];
	$name = $node['node'];
	$res = $client->get('nodes/'.$name."/qemu");
	$j = json_decode((string) $res->getBody(), true);
	$retarr = [ "namemap" => [], "vmid" => []];
	foreach ($j['data'] as $v) {
		$v['node'] = $node;
		$name = $v['name'];
		$vmid = $v['vmid'];
		$retarr["namemap"][$name] = $vmid;
		$retarr["vmid"][$vmid] = $v;
	}
	return $retarr;
}

function getVmStorage($vm) {
	$client = $vm['node']['client'];
	$name = $vm['node']['node'];
	$vmid = $vm['vmid'];
	$res = $client->get('nodes/'.$name."/qemu/$vmid/config");
	$j = json_decode((string) $res->getBody(), true);
	var_dump($j);
}


*/
