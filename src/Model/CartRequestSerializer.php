<?php

namespace Klaviyo\Integration\Model;

class CartRequestSerializer
{
    public function decode(string $serializedRequest): object
    {
        $serializedRequest = base64_decode($serializedRequest);

        return unserialize($serializedRequest);
    }

    public function encode($request): string
    {
        return base64_encode(serialize($request));
    }
}
