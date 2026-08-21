<?php

use Illuminate\Support\Facades\Storage;
use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Facades\User;

beforeEach(function () {
    Storage::fake('test');
    AssetContainer::make('media')->disk('test')->save();
    Storage::disk('test')->put('donut.jpg', '');
    Storage::disk('test')->put('pizza.jpg', '');

    Collection::make('articles')->save();
    Entry::make()->collection('articles')->id('article-one')->slug('one')->data(['title' => 'One'])->save();

    Taxonomy::make('topics')->save();
    Term::make('php')->taxonomy('topics')->data(['title' => 'PHP'])->save();

    User::make()->id('user-one')->email('one@example.com')->save();
});

it('collects assets by container', function () {
    $value = makeValue(['type' => 'assets', 'container' => 'media'], ['donut.jpg', 'pizza.jpg']);

    expect(itemTypes(Distill::query($value)->type('asset:media')->get()))
        ->toBe(['asset:media', 'asset:media']);
});

it('collects entries by collection', function () {
    $value = makeValue(['type' => 'entries', 'collections' => ['articles']], ['article-one']);

    expect(itemTypes(Distill::query($value)->type('entry:articles')->get()))->toBe(['entry:articles']);
});

it('collects terms by taxonomy', function () {
    $value = makeValue(['type' => 'terms', 'taxonomies' => ['topics']], ['php']);

    expect(itemTypes(Distill::query($value)->type('term:topics')->get()))->toBe(['term:topics']);
});

it('collects users', function () {
    $value = makeValue(['type' => 'users'], ['user-one']);

    expect(itemTypes(Distill::query($value)->type('user')->get()))->toBe(['user']);
});

it('does not walk into related objects', function () {
    $value = makeValue(['type' => 'entries', 'collections' => ['articles']], ['article-one']);

    expect(itemPaths(Distill::query($value)->get()))->toBe(['0']);
});

it('walks the blueprint when the source is an entry', function () {
    $blueprint = Blueprint::makeFromFields([
        'title' => ['type' => 'text'],
        'related' => ['type' => 'entries', 'collections' => ['articles']],
    ]);

    Blueprint::shouldReceive('in')->with('collections/pages')->andReturn(collect([$blueprint]));

    Collection::make('pages')->save();

    $entry = Entry::make()
        ->collection('pages')
        ->id('page-one')
        ->slug('one')
        ->data(['title' => 'Two', 'related' => ['article-one']]);

    expect(itemMap(Distill::query($entry)->type(['value:text', 'value:entries', 'entry:*'])->get()))->toBe([
        'value:text @ title',
        'value:entries @ related',
        'entry:articles @ related.0',
    ]);
});

it('dedupes repeated relationships with unique', function () {
    $value = makeValue(['type' => 'assets', 'container' => 'media'], ['donut.jpg', 'donut.jpg', 'pizza.jpg']);

    expect(Distill::query($value)->type('asset:*')->get())->toHaveCount(3);
    expect(Distill::query($value)->type('asset:*')->unique(true)->get())->toHaveCount(2);
});
