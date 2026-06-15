<?php

use App\Services\Genomics\Acmg\HgvsProtein;

it('parses a 3-letter protein change', function () {
    $p = HgvsProtein::parse('p.Arg175His');
    expect($p)->toMatchArray(['ref' => 'R', 'position' => 175, 'alt' => 'H']);
});

it('parses a 1-letter protein change with NP prefix', function () {
    $p = HgvsProtein::parse('NP_000537.3:p.R175H');
    expect($p)->toMatchArray(['ref' => 'R', 'position' => 175, 'alt' => 'H']);
});

it('parses the parenthesized predicted-consequence form (as used by ClinVar)', function () {
    expect(HgvsProtein::parse('p.(Arg175His)'))->toMatchArray(['ref' => 'R', 'position' => 175, 'alt' => 'H']);
    expect(HgvsProtein::parse('NP_000537.3:p.(Arg175His)'))->toMatchArray(['ref' => 'R', 'position' => 175, 'alt' => 'H']);
    expect(HgvsProtein::parse('NP_000537.3:p.(R175H)'))->toMatchArray(['ref' => 'R', 'position' => 175, 'alt' => 'H']);
});

it('returns null for non-missense or unparseable input', function () {
    expect(HgvsProtein::parse('c.524G>A'))->toBeNull();
    expect(HgvsProtein::parse('p.Arg175Ter'))->toBeNull();
    expect(HgvsProtein::parse(null))->toBeNull();
});
