# subnetstorage — FOG 1.6 plugin

Links storage groups and storage nodes to IPv4 subnets (CIDR). When a
host boots, images, or pulls a snapin, the plugin uses the host's address to
pick the storage group and node of its subnet. If an address matches more
than one entry, the narrowest subnet wins. It works like the Location plugin,
but there is no manual host association.

Requires FOG **1.6.0** or newer. For the 1.5 layout (`class/`, `hooks/`,
`pages/`), see the history of this directory.

## Install

Install it as a third-party plugin, under `/opt/fog/plugins`. That directory
survives `installfog.sh` upgrades; `lib/plugins` in the web root does not.

```bash
git clone https://github.com/roldaojr/fogproject.git /tmp/fogsrc
sudo cp -r /tmp/fogsrc/plugins/subnetstorage /opt/fog/plugins/
sudo rm -f /opt/fog/cache/*.json   # class list is cached for 300 s
```

On SELinux systems the directory must be readable by the web server, as
described in FOG's plugin documentation.

Next, in the web UI go to **Plugin System → Plugin Management**. Install
**subnetstorage**, which creates or upgrades the `subnetstorage` table, then
activate it. **Subnet Storage** shows up in the sidebar.

## Upgrading from the 1.5 version

You don't need to drop anything. Copy the new directory over the old one and
click **Update available** (or Install) in Plugin Management. Schema steps 1–3
convert the 1.5 table in place:

- `ssTftpEnabled`: ENUM to TINYINT(1)
- `ssStorageNodeID`: "no node" changes from 0 to NULL
- `ssStorageNodeProto`: becomes nullable VARCHAR

## Test

```bash
php plugins/subnetstorage/tests/plugin.test.php
```

The test loads the plugin through FOG's own autoloader from a temporary
plugin root and checks the subnet matching and the schema.
