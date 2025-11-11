<?php

declare(strict_types=1);

namespace Juling\DevTools\Resolvers;

use Illuminate\Support\Facades\Blade;
use Juling\DevTools\Support\DevConfig;
use Juling\DevTools\Support\SchemaTrait;

class ServiceResolver extends Foundation
{
    use SchemaTrait;

    public function build(DevConfig $devConfig, array $data): void
    {
        if ($devConfig->getMultiModule()) {
            $groupName = $this->getTableGroupName($data['tableName']);
            $dist = $devConfig->getDist('app/Bundles/'.$groupName.'/Services');
            $data['namespace'] = "App\\Bundles\\$groupName";
        } else {
            $dist = $devConfig->getDist('app/Services');
            $data['namespace'] = 'App';
        }
        $this->ensureDirectoryExists($dist);

        $tpl = file_get_contents(__DIR__.'/stubs/service/bundle.stub');
        $content = Blade::render($tpl, $data, deleteCachedView: true);
        file_put_contents($dist.'/'.$data['className'].'BundleService.php', "<?php\n\n".$content);

        $this->baseService($devConfig, $data);
    }

    private function baseService(DevConfig $devConfig, array $data): void
    {
        $data['groupName'] = $this->getTableGroupName($data['tableName']);
        $data['namespace'] = 'App\\Services\\'.$data['groupName'];
        $dist = $devConfig->getDist('app/Services/'.$data['groupName']);
        $this->ensureDirectoryExists($dist);

        if ($devConfig->getMultiModule()) {
            $data['useNamespace'] = 'App\\Bundles\\'.$data['groupName'];
        } else {
            $data['useNamespace'] = 'App';
        }

        $serviceFile = $dist.'/'.$data['className'].'Service.php';
        if (! file_exist($serviceFile)) {
            $tpl = file_get_contents(__DIR__.'/stubs/service/service.stub');
            $content = Blade::render($tpl, $data, deleteCachedView: true);
            file_put_contents($serviceFile, "<?php\n\n".$content);
        }
    }
}
