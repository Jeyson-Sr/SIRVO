<?php

test('the document defaults to dark appearance', function () {
    $html = $this->get(route('login'))->assertOk()->getContent();

    expect($html)->toContain('class="dark"');
});

test('the document uses dark appearance when the appearance cookie is dark', function () {
    $html = $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('login'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('class="dark"');
});

test('the document stays light when the appearance cookie is light', function () {
    $html = $this->withUnencryptedCookie('appearance', 'light')
        ->get(route('login'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('<html')
        ->not->toContain('class="dark"');
});
