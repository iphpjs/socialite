<?php

namespace Iphpjs\Socialite\Contracts;

interface FactoryInterface
{
    /**
     * @param string $driver
     *
     * @return \Iphpjs\Socialite\Contracts\ProviderInterface
     */
    public function create(string $driver): ProviderInterface;
}
