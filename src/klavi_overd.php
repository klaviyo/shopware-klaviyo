<?php

namespace Klaviyo\Integration;

use Doctrine\DBAL\Connection;
use Klaviyo\Integration\Utils\Lifecycle\Update\UpdateOldTemplate;
use Klaviyo\Integration\Utils\Lifecycle\Update\UpdateTo105;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class klavi_overd extends Plugin
{
    public function update(UpdateContext $updateContext): void
    {
        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.0.5', '<=')) {
            (new UpdateTo105(
                $this->container->get(SystemConfigService::class),
                $this->container->get('sales_channel.repository')
            ))->execute($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.0.6', '<=')) {
            $adapter = new LocalFilesystemAdapter(__DIR__);
            $filesystem = new Filesystem($adapter);
            $connection = $this->container->get(Connection::class);
            (new UpdateOldTemplate($filesystem, $connection))->updateTemplateByMD5hash();
        }

        parent::update($updateContext);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $locator = new FileLocator('Resources/config');

        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $confDir = \rtrim($this->getPath(), '/') . '/Resources/config';
        $configLoader->load($confDir . '/{packages}/*.yaml', 'glob');
    }

    public function executeComposerCommands(): bool
    {
        return true;
    }
}
