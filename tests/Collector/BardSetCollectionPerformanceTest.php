<?php

namespace JackSleight\StatamicDistill\Tests\Collector;

use JackSleight\StatamicDistill\Facades\Distill;
use JackSleight\StatamicDistill\Tests\Concerns\BuildsBardValues;
use JackSleight\StatamicDistill\Tests\Fieldtypes\SlowAugment;
use JackSleight\StatamicDistill\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BardSetCollectionPerformanceTest extends TestCase
{
    use BuildsBardValues;

    #[Test]
    public function it_does_not_augment_bard_sets_during_collection(): void
    {
        $value = $this->bardValue($this->heavyBardContent(250));

        $start = hrtime(true);

        $items = Distill::query($value)
            ->type('node:heading')
            ->get();

        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $this->assertCount(1, $items);
        $this->assertSame('node:heading', $items->first()->info->type);
        $this->assertSame(0, SlowAugment::$augmentCalls);
        $this->assertLessThan(500, $elapsedMs, 'Expected bard set collection to stay fast without augmenting sets.');
    }
}
