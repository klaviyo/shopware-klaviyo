<?php declare(strict_types=1);

namespace Klaviyo\Integration\Utils;

use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\Migration\MigrationCollection;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Migration\MigrationSource;

class MigrationHelper
{
    private MigrationCollectionLoader $migrationLoader;

    public function __construct(MigrationCollectionLoader $migrationLoader)
    {
        $this->migrationLoader = $migrationLoader;
    }

    public function getMigrationCollection(Bundle $bundleInstance): MigrationCollection
    {
        $migrationPath = str_replace(
            '\\',
            '/',
            $bundleInstance->getPath() . str_replace(
                $bundleInstance->getNamespace(),
                '',
                $bundleInstance->getMigrationNamespace()
            )
        );

        if (!is_dir($migrationPath)) {
            return $this->migrationLoader->collect('null');
        }

        $this->migrationLoader->addSource(new MigrationSource($bundleInstance->getName(), [
            $migrationPath => $bundleInstance->getMigrationNamespace(),
        ]));

        $collection = $this->migrationLoader->collect($bundleInstance->getName());
        $collection->sync();

        return $collection;
    }
}
