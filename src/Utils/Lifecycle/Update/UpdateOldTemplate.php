<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Utils\Lifecycle\Update;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use League\Flysystem\FilesystemInterface;

class UpdateOldTemplate
{
    private const MD5_HASH_OLD_TEMPLATE = "e89d84423f5f4f8cdc575f49ef5d3933";

    private Connection $connection;

    private FilesystemInterface $filesystem;

    public function __construct(
        FilesystemInterface $filesystem, 
        Connection $connection
    ) {
        $this->connection = $connection;
        $this->filesystem = $filesystem;
    }


    public function getNewTemplate(): string
    {
        $path = '/Resources/app/administration/src/product-export-templates/klaviyo/body.xml.twig';
        return $this->filesystem->read($path);
    }

    public function updateTemplateByMD5hash(): int
    {
        $template = $this->getNewTemplate();

        $query = $this->connection->createQueryBuilder();
        $query->update('product_export');
        $query->set('product_export.body_template', ':template');
        $query->set('product_export.updated_at', ':updatedAt');
        $query->where("MD5(product_export.body_template) = :templateHash");
        $query->setParameter('templateHash', self::MD5_HASH_OLD_TEMPLATE);
        $query->setParameter('template', $template);
        $query->setParameter('updatedAt', (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT));
        $results = $query->execute();

        return $results;
    }
}
