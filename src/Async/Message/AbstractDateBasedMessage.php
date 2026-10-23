<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

abstract class AbstractDateBasedMessage extends AbstractBasicMessage
{
    // A job serialized before these properties existed must
    // still unserialize cleanly into this class after a plugin update
    protected ?string $fromDate = null;
    protected ?string $tillDate = null;

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
            // Exclusive bound on the day *after* tillDate, not LTE on tillDate itself: the stored
            // column is a datetime, so an inclusive "<=" on a bare date string would compare against
            // midnight and exclude almost every record actually on that day.
            $rangeFilter[RangeFilter::LT] = $tillDate->modify('+1 day')->format('Y-m-d');
        }

        if (!empty($rangeFilter)) {
            $criteria->addFilter(new RangeFilter($dateTimeAttr, $rangeFilter));
        }
    }
}
