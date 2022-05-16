<?php declare(strict_types=1);

namespace Od\Scheduler\Entity\JobMessage;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(JobMessageEntity $entity)
 * @method void              set(string $key, JobMessageEntity $entity)
 * @method JobMessageEntity[]    getIterator()
 * @method JobMessageEntity[]    getElements()
 * @method JobMessageEntity|null get(string $key)
 * @method JobMessageEntity|null first()
 * @method JobMessageEntity|null last()
 */
class JobMessageCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return JobMessageEntity::class;
    }
}
