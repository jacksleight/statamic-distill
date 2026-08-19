<?php

namespace JackSleight\StatamicDistill\Tests\Collector;

use JackSleight\StatamicDistill\Facades\Distill;
use JackSleight\StatamicDistill\Tests\Concerns\BuildsBardValues;
use JackSleight\StatamicDistill\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BardSetQueryPruningTest extends TestCase
{
    use BuildsBardValues;

    #[Test]
    public function it_skips_non_matching_sets_when_type_filter_targets_bard_nodes_only(): void
    {
        $value = $this->bardValue([
            $this->bardHeading(1, 'Page heading'),
            $this->bardSet('heavy', [
                'heading' => 'Heavy set',
                'heading_type' => 'h2',
                'slow' => 'payload',
                'tags' => ['alpha', 'beta', 'gamma'],
            ]),
        ]);

        $items = Distill::query($value)
            ->type('node:heading')
            ->get();

        $this->assertCount(1, $items);
        $this->assertSame('0', $items->first()->info->path);
        $this->assertTrue($items->every(fn ($item) => ! str_contains($item->info->path, '.tags.')));
    }

    #[Test]
    public function it_still_expands_sets_when_type_filter_can_match_nested_values(): void
    {
        $value = $this->bardValue([
            $this->bardSet('heavy', [
                'heading' => 'Heavy set',
                'heading_type' => 'h2',
                'slow' => 'payload',
                'tags' => ['alpha'],
            ]),
        ]);

        $items = Distill::query($value)
            ->type('raw:string')
            ->where('value', 'alpha')
            ->get();

        $this->assertCount(1, $items);
        $this->assertStringEndsWith('tags.0', $items->first()->info->path);
    }
}
