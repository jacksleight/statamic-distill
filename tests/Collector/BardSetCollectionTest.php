<?php

namespace JackSleight\StatamicDistill\Tests\Collector;

use JackSleight\StatamicDistill\Facades\Distill;
use JackSleight\StatamicDistill\Tests\Concerns\BuildsBardValues;
use JackSleight\StatamicDistill\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BardSetCollectionTest extends TestCase
{
    use BuildsBardValues;

    #[Test]
    public function it_collects_bard_sets_from_stored_json_without_augmenting(): void
    {
        $value = $this->bardValue([
            $this->bardSet('heavy', [
                'heading' => 'Set heading',
                'heading_type' => 'h1',
                'slow' => 'ignored',
            ], 'abc123'),
        ]);

        $items = Distill::query($value)
            ->type('set:heavy')
            ->where('heading_type', 'h1')
            ->get();

        $this->assertCount(1, $items);
        $this->assertSame('set:heavy', $items->first()->info->type);
        $this->assertSame('abc123', $items->first()->getQueryableValue('id'));
        $this->assertSame('Set heading', $items->first()->getQueryableValue('heading'));
        $this->assertSame('h1', $items->first()->getQueryableValue('heading_type'));
    }

    #[Test]
    public function it_finds_h1_headings_alongside_heavy_sets(): void
    {
        $value = $this->bardValue([
            $this->bardHeading(1, 'Page heading'),
            $this->bardSet('heavy', [
                'heading' => 'Heavy set',
                'heading_type' => 'h2',
                'slow' => 'payload',
            ]),
        ]);

        $items = Distill::query($value)
            ->type('node:heading')
            ->get();

        $this->assertCount(1, $items);
        $this->assertSame(1, $items->first()->getQueryableValue('attrs')['level']);
    }

    #[Test]
    public function it_skips_disabled_bard_sets(): void
    {
        $value = $this->bardValue([
            $this->bardSet('heavy', [
                'heading' => 'Disabled',
                'heading_type' => 'h1',
                'slow' => 'payload',
            ], enabled: false),
        ]);

        $items = Distill::query($value)
            ->type('set:heavy')
            ->where('heading_type', 'h1')
            ->get();

        $this->assertCount(0, $items);
    }
}
