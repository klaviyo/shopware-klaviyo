<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\FrontendApi\DTO;

class SyncProgressInfo implements \JsonSerializable
{
    private int $page;
    private string $hash;
    private string $salesChannelId;

    public function __construct(
        int $page,
        string $hash,
        string $salesChannelId
    ) {
        $this->page = $page;
        $this->hash = $hash;
        $this->salesChannelId = $salesChannelId;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function jsonSerialize(): array
    {
        return [
            'page' => $this->getPage(),
            'hash' => $this->getHash()
        ];
    }
}