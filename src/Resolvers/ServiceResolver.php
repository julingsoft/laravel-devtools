<?php

declare(strict_types=1);

namespace Juling\DevTools\Resolvers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;

class ServiceResolver extends Foundation
{
    use SchemaTrait;

    public function build(DevConfig $devConfig, array $data): void
    {
        if ($devConfig->getMultiModule()) {
            $groupName = $this->getTableGroupName($data['tableName']);
            $dist = $devConfig->getDist('app/Modules/'.$groupName.'/Services');
            $data['namespace'] = "App\\Modules\\$groupName";
        } else {
            $dist = $devConfig->getDist('app/Services');
            $data['namespace'] = 'App';
        }
        $this->ensureDirectoryExists($dist);

        $tpl = file_get_contents(__DIR__ . '/stubs/service/service.stub');
        $content = Blade::render($tpl, $data, deleteCachedView: true);
        file_put_contents($dist . '/' . $data['className'] . 'Service.php', "<?php\n\n" . $content);

        $this->bundleService($devConfig, $data);
    }

    private function bundleService(DevConfig $devConfig, array $data): void
    {
        $data['groupName'] = $this->getTableGroupName($data['tableName']);
        $data['namespace'] = "App\\Bundles\\".$data['groupName'];
        $dist = $devConfig->getDist('app/Bundles/'.$data['groupName'].'/Services');
        $this->ensureDirectoryExists($dist);

        if ($devConfig->getMultiModule()) {
            $data['useNamespace'] = "App\\Modules\\".$data['groupName'];
        } else {
            $data['useNamespace'] = 'App';
        }

        $tpl = file_get_contents(__DIR__ . '/stubs/service/bundle.stub');
        $content = Blade::render($tpl, $data, deleteCachedView: true);
        file_put_contents($dist . '/' . $data['className'] . 'BundleService.php', "<?php\n\n" . $content);
    }
}
