<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\FrontendApi\DTO;

use Klaviyo\Integration\Entity\FlagStorage\FlagStorageEntity;

class SyncProgressInfo implements \JsonSerializable
{
    private FlagStorageEntity $pageFlag;
    private FlagStorageEntity $hashFlag;
    private string $salesChannelId;

    public function __construct(
        FlagStorageEntity $pageFlag,
        FlagStorageEntity $hashFlag,
        string $salesChannelId
    ) {
        $this->pageFlag = $pageFlag;
        $this->hashFlag = $hashFlag;
        $this->salesChannelId = $salesChannelId;
    }

    public function getPage(): int
    {
        return (int)$this->pageFlag->getValue();
    }

    public function getHash(): string
    {
        return $this->hashFlag->getValue();
    }

    public function setPage(int $page): void
    {
        $this->pageFlag->setValue((string)$page);
    }

    public function setHash(string $hash): void
    {
        $this->hashFlag->setValue($hash);
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getPageFlagEntity(): FlagStorageEntity
    {
        return $this->pageFlag;
    }

    public function getHashFlagEntity(): FlagStorageEntity
    {
        return $this->hashFlag;
    }

    public function jsonSerialize(): array
    {
        return [
            'page' => $this->getPage(),
            'hash' => $this->getHash()
        ];
    }
}
