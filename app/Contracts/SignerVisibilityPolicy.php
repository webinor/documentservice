<?php

namespace App\Contracts;

interface SignerVisibilityPolicy
{
    public function isVisible(array $participant): bool;
}