<?php

namespace Tests;

trait StringAssertions
{
    /**
     * Assert that a string contains another string
     */
    public function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertStringContainsString($needle, $haystack, $message);
    }
}