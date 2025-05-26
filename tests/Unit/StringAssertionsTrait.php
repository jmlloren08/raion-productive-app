<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

trait StringAssertionsTrait {
    public function assertStringContains(string \, string \, string \ = ''): void
    {
        \->assertStringContainsString(\, \, \);
    }
}

class_exists(ProductiveActionDependencyInjectionTest::class) 
    && class_mixin(ProductiveActionDependencyInjectionTest::class, new StringAssertionsTrait);

function class_mixin(\, \) {
    \ = new \ReflectionClass(\);
    foreach (get_class_methods(\) as \) {
        if (!\->hasMethod(\)) {
            \::\ = \Closure::fromCallable([\, \])->bindTo(null, \);
        }
    }
}
