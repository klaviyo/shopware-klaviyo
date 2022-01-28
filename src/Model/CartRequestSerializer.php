<?php

namespace Klaviyo\Integration\Model;

class CartRequestSerializer
{
    public function decode(string $serializedRequest): object
    {
        $serializedRequest = stripslashes(base64_decode($serializedRequest));

        return unserialize($serializedRequest);
    }

    public function encode($request): string
    {
        return addslashes(base64_encode(serialize($request)));
    }
}