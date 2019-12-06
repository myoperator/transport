<?php

namespace MyOperator\Transport\OAuth;

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