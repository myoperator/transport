<?php

namespace MyOperator\Transport\Oauth;

interface TokenProviderInterface
{
    /**
     * @return string
     */
    public function getToken();

    /**
     * @return int|null
     */
    public function getExpiry();
}