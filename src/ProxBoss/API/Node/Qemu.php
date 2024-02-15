<?php

namespace ProxBoss\API\Node;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ProxBoss\API\Base;
use ProxBoss\Items\NetworkInterface;
use ProxBoss\Items\PendingChanges;

class Qemu extends Base
{
    protected Client $client;

    private $vmdata;
    private $vmconfig;
    private $nodename;

    public function __construct(string $path, array $vmdata, string $nodename)
    {
        $this->client = $this->getClient($path);
        $this->vmdata = $vmdata;
        $this->nodename = $nodename;
    }

    public function getVmId()
    {
        return $this->vmdata['vmid'];
    }

    public function getVmName()
    {
        return $this->vmdata['name'];
    }

    public function getNodeName(): string
    {
        return $this->nodename;
    }

    public function getVmConfig(bool $refresh = false)
    {
        if ($refresh || !$this->vmconfig) {
            $res = $this->client->get('config');
            $j = json_decode((string) $res->getBody(), true);
            $this->vmconfig = $j['data'];
        }
        return $this->vmconfig;
    }

    public function getUnusedDisks(bool $refresh = false)
    {
        $retarr = [];
        $conf = $this->getVmConfig($refresh);
        foreach ($conf as $row => $val) {
            if (strpos($row, 'unused') === 0) {
                $retarr[$row] = $val;
            }
        }
        return $retarr;
    }

    public function unlinkUnused(string $path)
    {
        $unused = $this->getUnusedDisks(true);
        foreach ($unused as $name => $row) {
            if ($row === $path) {
                print "Found $path\n";
                $params = ["idlist" => $name, "node" => $this->getNodeName(), "vmid" => $this->getVmId()];
                $res = $this->client->put('unlink', ['read_timeout' =>  60, 'form_params' => $params]);
                $j = json_decode((string) $res->getBody(), true);
                return ["params" => $params, "result" => $j];
            }
        }
        throw new \Exception("Could not find $path on this vm");
    }

    public function getDatastores(bool $refresh = false)
    {
        $retarr = [];
        $conf = $this->getVmConfig($refresh);
        foreach ($conf as $row => $val) {
            if (preg_match('/^(scsi|virtio)\d+$/', $row, $out)) {
                $tmparr = explode(":", $val, 2);
                $name = $tmparr[0];
                $retarr[$name][] = $row;
            } else {
                // print "$row is not scsi\n";
            }
        }
        return $retarr;
    }

    public function getDiskDetails(string $disk)
    {
        $conf = $this->getVmConfig();
        if (empty($conf[$disk])) {
            throw new \Exception("Can't find disk $disk");
        }
        $tmparr = explode(":", $conf[$disk], 2);
        $retarr = ["datastore" => $tmparr[0]];
        $paramarr = explode(",", $tmparr[1]);
        $retarr["filename"] = array_shift($paramarr);
        foreach ($paramarr as $p) {
            $tmpsetting = explode("=", $p);
            $retarr[$tmpsetting[0]] = $tmpsetting[1];
        }
        return $retarr;
    }

    /**
     * @return NetworkInterface[]
     * @throws GuzzleException
     */
    public function getNetworkInterfaces()
    {
        $retarr = [];
        $conf = $this->getVmConfig();
        foreach ($conf as $row => $val) {
            if (preg_match('/^net(\d+)$/', $row, $out)) {
                $retarr[] = NetworkInterface::fromQemu($row, $val);
            }
        }
        return $retarr;
    }

    public function moveDisk(string $srcdisk, string $deststore): array
    {
        $changes = $this->getPendingChanges();
        if ($changes->areChangesPending()) {
            throw new \Exception("Not moving disk while changes are pending");
        }
        $params = [
            "disk" => $srcdisk, "vmid" => $this->getVmId(), "node" => $this->getNodeName(),
            "delete" => 1, "storage" => $deststore
        ];
        $res = $this->client->post('move_disk', ['form_params' => $params]);
        $j = json_decode((string) $res->getBody(), true);
        return $j;
    }

    public function getMem(): int
    {
        return $this->vmdata['mem'];
    }

    public function getCpuCount(): int
    {
        return $this->vmdata['cpus'];
    }

    public function getScsiHw(): string
    {
        return $this->getVmConfig()['scsihw'];
    }

    public function isNumaEnabled(): bool
    {
        return ($this->getVmConfig()['numa'] == 1);
    }

    public function getCpuType(): string
    {
        return $this->getVmConfig()['cpu'];
    }

    public function isVmConfigCorrect($desiredCPU = null): array
    {
        $errors = [];
        if (!is_null($desiredCPU)) {
            if($this->getCpuType() != $desiredCPU) {
                $errors[] = "CPU is not $desiredCPU (Is set to " . $this->getCpuType() . ")";
            }
	}
        if ($this->getCpuCount() > 1 && !$this->isNumaEnabled()) {
            $errors[] = "More than 1 CPU, NUMA not enabled";
        }
        if ($this->getScsiHw() !== "virtio-scsi-single") {
            $errors[] = "SCSI Hardware is not virtio scsi single (Is set to " . $this->getScsiHw() . ")";
        }
        $ds = $this->getDatastores();
        foreach ($ds as $storename => $disks) {
            foreach ($disks as $diskname) {
                if (strpos($diskname, "virtio") !== 0) {
                    $errors[] = "Disk $diskname using $storename is not virtio";
                    continue;
                }
                $disksettings = $this->getDiskDetails($diskname);
                if (empty($disksettings['discard']) || $disksettings['discard'] !== 'on') {
                    $errors[] = "Disk $diskname using $storename is not set to use Discard (TRIM)";
                }
                if (empty($disksettings['iothread']) || $disksettings['iothread'] !== '1') {
                    $errors[] = "Disk $diskname using $storename does not have iothreads enabled";
                }
                if (empty($disksettings['aio']) || $disksettings['aio'] !== 'threads') {
                    $errors[] = "Disk $diskname using $storename is not set to use thread type 'threads'";
                }
            }
        }
        return $errors;
    }

    public function getPendingChanges(): PendingChanges
    {
        $res = $this->client->get('pending');
        $j = json_decode((string) $res->getBody(), true);
        return PendingChanges::fromApi($j['data']);
    }
}
