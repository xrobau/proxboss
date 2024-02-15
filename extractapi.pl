#!/usr/bin/perl
#
# Copied from https://git.proxmox.com/?p=pve-docs.git;a=blob;f=extractapi.pl;h=2144f17b95de4e1fc0a73445eb7877ea13b406a0;hb=HEAD

use strict;
use warnings;

use PVE::RESTHandler;
use PVE::API2;
use JSON;

my $tree = PVE::RESTHandler::api_dump_remove_refs(PVE::RESTHandler::api_dump('PVE::API2'));

open (JH, '>', 'schema.json') or die $!;

print JH to_json($tree, {pretty => 1, canonical => 1});

