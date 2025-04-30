<?php 

declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

abstract class AbstractDateBasedMessage extends AbstractBasicMessage
{
    protected ?string $fromDate;
    protected ?string $tillDate;

    public function __construct(
        string   $jobId,
        ?string  $name = null,
        ?Context $context = null,
        ?string   $fromDate = null,
        ?string   $tillDate = null,
    ) {
        parent::__construct($jobId, $name, $context);
        $this->fromDate = $fromDate;
        $this->tillDate = $tillDate;
    }

    public function getFromDate(): ?string
    {
        return $this->fromDate;
    }

    public function getTillDate(): ?string
    {
        return $this->tillDate;
    }
    
    public function applyDateRangeFilter(Criteria $criteria, string $dateTimeAttr = 'createdAt'): void
    {
        $fromDate = !empty($this->getFromDate()) ? new \DateTime($this->getFromDate()) : null;
        $tillDate = !empty($this->getTillDate()) ? new \DateTime($this->getTillDate()) : null;

        $rangeFilter = [];

        if ($fromDate !== null) {
            $rangeFilter[RangeFilter::GTE] = $fromDate->format('Y-m-d');
        }

        if ($tillDate !== null) {
            $rangeFilter[RangeFilter::LTE] = $tillDate->format('Y-m-d');
        }
        
        if (!empty($rangeFilter)) {
            $criteria->addFilter(new RangeFilter($dateTimeAttr, $rangeFilter));
        }
    }
}
