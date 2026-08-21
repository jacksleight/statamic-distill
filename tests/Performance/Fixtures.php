<?php

use Illuminate\Support\Facades\Storage;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\Fields\Value;

function makeBenchmarkContent(int $count = 5): void
{
    Storage::fake('benchmark');
    AssetContainer::make('media')->disk('benchmark')->save();

    Collection::make('articles')->save();
    Taxonomy::make('topics')->save();

    for ($i = 0; $i < $count; $i++) {
        Storage::disk('benchmark')->put("image-{$i}.jpg", '');
        Entry::make()->collection('articles')->id("article-{$i}")->slug("article-{$i}")->data(['title' => "Article {$i}"])->save();
        Term::make("topic-{$i}")->taxonomy('topics')->data(['title' => "Topic {$i}"])->save();
        User::make()->id("user-{$i}")->email("user-{$i}@example.com")->save();
    }
}

function benchmarkSets(bool $withRelationships): array
{
    $textBlock = [
        'heading' => 'text',
        'intro' => 'textarea',
        'body' => [
            'type' => 'bard',
            'sets' => makeSets(['quote' => ['text_field' => 'text', 'attribution' => 'text']]),
        ],
    ];

    $mediaBlock = ['caption' => 'text'];
    $relatedBlock = ['summary' => 'text'];

    if ($withRelationships) {
        $mediaBlock['images'] = ['type' => 'assets', 'container' => 'media'];
        $relatedBlock['articles'] = ['type' => 'entries', 'collections' => ['articles']];
        $relatedBlock['topics'] = ['type' => 'terms', 'taxonomies' => ['topics']];
        $relatedBlock['editors'] = ['type' => 'users'];
    }

    $relatedBlock['rows'] = [
        'type' => 'grid',
        'dtl_type' => 'row',
        'fields' => makeFields(['label' => 'text', 'note' => 'textarea']),
    ];

    return makeSets([
        'text_block' => $textBlock,
        'media_block' => $mediaBlock,
        'related_block' => $relatedBlock,
    ]);
}

function makeLargeValue(int $blocks = 90, bool $withRelationships = true): Value
{
    $content = [];

    for ($i = 0; $i < $blocks; $i++) {
        $content[] = match ($i % 3) {
            0 => makeReplicatorSet('text_block', [
                'heading' => "Heading {$i}",
                'intro' => "Intro {$i}",
                'body' => [
                    makeParagraph("Paragraph {$i}"),
                    makeBardSet('quote', ['text_field' => "Quote {$i}", 'attribution' => "Author {$i}"], id: "q-{$i}"),
                ],
            ], id: "t-{$i}"),
            1 => makeReplicatorSet('media_block', array_filter([
                'caption' => "Caption {$i}",
                'images' => $withRelationships ? ['image-0.jpg', 'image-1.jpg', 'image-2.jpg', 'image-3.jpg', 'image-4.jpg'] : null,
            ]), id: "m-{$i}"),
            2 => makeReplicatorSet('related_block', array_filter([
                'summary' => "Summary {$i}",
                'articles' => $withRelationships ? ['article-0', 'article-1', 'article-2', 'article-3', 'article-4'] : null,
                'topics' => $withRelationships ? ['topic-0', 'topic-1', 'topic-2'] : null,
                'editors' => $withRelationships ? ['user-0', 'user-1'] : null,
                'rows' => [
                    ['id' => "r-{$i}-0", 'label' => "Label {$i}", 'note' => "Note {$i}"],
                    ['id' => "r-{$i}-1", 'label' => "Label {$i}", 'note' => "Note {$i}"],
                ],
            ]), id: "r-{$i}"),
        };
    }

    return makeValue([
        'type' => 'replicator',
        'sets' => benchmarkSets($withRelationships),
    ], $content, 'builder');
}

function benchmark(callable $callback, int $runs = 3): array
{
    $callback();

    $times = [];
    $result = null;

    for ($i = 0; $i < $runs; $i++) {
        $start = hrtime(true);
        $result = $callback();
        $times[] = (hrtime(true) - $start) / 1_000_000;
    }

    return ['ms' => round(min($times), 1), 'count' => count($result)];
}

function reportBenchmark(string $title, array $rows): void
{
    $width = max(array_map('strlen', array_keys($rows))) + 2;

    fwrite(STDERR, "\n  {$title}\n");
    foreach ($rows as $label => $row) {
        fwrite(STDERR, sprintf("    %-{$width}s %8s ms  %6d items\n", $label, $row['ms'], $row['count']));
    }
}
