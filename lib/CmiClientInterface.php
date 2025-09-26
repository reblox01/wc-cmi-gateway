<?php

namespace WC_CMI_Gateway\Lib;

/**
 * Interface for a CMI client.
 */
interface CmiClientInterface
{
    public function getDefaultOpts();

    public function getRequireOpts();

    public function generateHash($storeKey);

    public function dd(...$values);
}
