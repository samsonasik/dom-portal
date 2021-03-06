<?php

namespace App\Commands;

use App\Entities\Host;
use App\Entities\HostStat;
use App\Entities\Server;
use App\Libraries\VirtualMinShell;
use App\Models\HostModel;
use App\Models\HostStatModel;
use App\Models\ServerModel;
use App\Models\ServerStatModel;
use CodeIgniter\CLI\BaseCommand;
use Symfony\Component\Yaml\Yaml;

class CronJob extends BaseCommand
{
    protected $group       = 'demo';
    protected $name        = 'cronjob';
    protected $description = 'Do Scheduled Server Collection.';

    public function run(array $params)
    {
        /*
            Things to check:
            Updating quota info for users
            Slave usage + health data collection
			Collecting bandwidth usage for users
            Disabling users who exceeded their disk quota
			Disabling users who meets the expiration date
			Deleting users who not reactivating within two weeks
        */
        /** @var Server */
        foreach ((new ServerModel())->find() as $server) {
            $domains = (new VirtualMinShell())->listDomainsInfo($server->alias);
            $bandwidths = (new VirtualMinShell())->listBandwidthInfo($server->alias);
            /** @var Host[] */
            $hosts = (new HostModel())->atServer($server->id)->find();
            foreach ($hosts as $host) {
                if (!($domain = ($domains[$host->domain] ?? '')))
                    continue;
                $stat = $host->stat;
                $plan = $host->plan;
                $newStat = [
                    'host_id' => $host->id,
                    'domain' => $host->domain,
                    'identifier' => $domain['ID'],
                    'password' => $domain['Password'],
                    'quota_server' => intval($domain['Server byte quota used']),
                    'quota_user' => intval($domain['User byte quota used']),
                    'quota_db' => intval($domain['Databases byte size'] ?? 0),
                    'quota_net' => intval($domain['Bandwidth byte usage'] ?? 0),
                    'features' => $domain['Features'],
                    'bandwidths' => $bandwidths[$host->domain] ?? null,
                    'disabled' => $domain['Disabled'] ?? null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if (!$stat) {
                    $stat = new HostStat();
                    $stat->fill($newStat);
                } else {
                    if ($stat->quota_net > $newStat['quota_net']) {
                        // Roll over time
                        log_message('notice', 'ROLLOVER ' . $newStat['domain'] . ': ' . json_encode([$stat->quota_net, $newStat['quota_net']]));
                        $host->addons = max(0, $host->addons - (($stat->quota_net / 1024 / 1024) - ($plan->net * 1024 / 12)));
                        (new VirtualMinShell())->adjustBandwidthHost(
                            ($host->addons + ($plan->net * 1024 / 12)),
                            $host->domain,
                            $server->alias
                        );
                    }
                    $stat->fill($newStat);
                }
                (new HostStatModel())->replace($stat->toRawArray());
                $expired = time() >= $host->expiry_at->getTimestamp();
                $overDisk = ($stat->quota_server) > $plan->disk * 1024 * 1024;
                $overBw = ($stat->quota_net) > $plan->net * 1024 * 1024 * 1024 / 12 + $host->addons * 1024 * 1024;
                // CLI::write('INJURY TIME ' . json_encode([$host->domain, $stat->disabled, $expired, $overDisk, $overBw]));
                if (!$stat->disabled) {
                    if ($overDisk) {
                        // Disable
                        (new VirtualMinShell())->disableHost($host->domain, $server->alias, 'Running out Disk Space');
                        $host->status = 'suspended';
                    } else if ($overBw) {
                        // Disable
                        (new VirtualMinShell())->disableHost($host->domain, $server->alias, 'Running out Bandwidth');
                        $host->status = 'suspended';
                    } else if ($expired) {
                        // Disable
                        (new VirtualMinShell())->disableHost($host->domain, $server->alias, 'host expired');
                        $host->status = 'expired';
                    }
                } else {
                    if ((strtotime('-2 weeks', time()) >= $host->expiry_at->getTimestamp()) || ($stat->quota_server > $plan->disk * 1024 * 1024 * 3)) {
                        if ($host->plan_id === 1) {
                            // Paid hosts should be immune from this, in case error logic happens...
                            (new VirtualMinShell())->deleteHost($host->domain, $server->alias);
                            $host->status = 'removed';
                        }
                        // TODO: Deleted email
                    } else if (!($expired || $overDisk || $overBw)) {
                        // Enable
                        (new VirtualMinShell())->enableHost($host->domain, $server->alias);
                        $host->status = 'active';
                    }
                }
                if ($host->hasChanged()) {
                    (new HostModel())->save($host);
                }
            }
            $yaml = Yaml::parse((new VirtualMinShell())->listSystemInfo($server->alias));
            $data = [
                'server_id' => $server->id,
                // php_yaml can't handle 64 bit ints properly
                'metadata' => json_encode($yaml),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            (new ServerStatModel())->replace($data);
        }
    }
}
