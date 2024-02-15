# proxboss
Collection of Proxmox tools

token.json resides in the proxboss root.  Example:
```
{
        "herpo": {
                "id": "root@pam!<APIKEY name from Proxmox>",
                "secret": "<APIKEY secret>",
                "base_uri": "https://a.proxmox.server:8006/api2/json",
                "verify": false
        },
        "derpo": {
                ...
        }
}
```

movestorage.php:  Mass move multiple proxmox VMs from one storage unit to another via Proxmox API.
```
Usage: ./movestorage.php --cluster=[clustername] (Mandatory) --opt --opt...
  --help        This help
  --cluster=    Cluster to process - See token.json
  --src=        Source datastore to move from
  --dest=       Destination datastore to move to
  --node=       Only process VMs on this node
  --match=      Wildcard match of VM names
 Flags:
  --live        Launch move tasks (if not provided, will only show what WOULD be moved)
  --single      Only process VMs on this node
  --force       (Unimplemented) Force move even if another is in progress
```
Example:  ` ./movestorage.php --src=hp-store3 --dest=hp-store1 --cluster=herpo `
