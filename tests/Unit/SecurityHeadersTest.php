<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class);

test('cross origin headers include the configured embedder policy', function (bool $enforce, string $policy) {
    config(['security.enforce_cross_origin_embedder_policy' => $enforce]);

    $response = (new SecurityHeaders)->handle(
        Request::create('https://example.test'),
        fn () => new Response,
    );

    expect($response->headers->get('Cross-Origin-Resource-Policy'))->toBe('same-origin')
        ->and($response->headers->get('Cross-Origin-Opener-Policy'))->toBe('same-origin')
        ->and($response->headers->get('Cross-Origin-Embedder-Policy'))->toBe($policy);
})->with([
    'compatible mode' => [false, 'unsafe-none'],
    'enforced mode' => [true, 'require-corp'],
]);
