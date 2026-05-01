<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\MiddlewareInterface;

/**
 * Builds a middleware stack and executes it around the final handler.
 * Each middleware wraps the next via array_reduce on the reversed list.
 */
class Pipeline
{
    /** @param MiddlewareInterface[] $middlewares */
    public function __construct(
        private readonly array $middlewares,
        private readonly mixed $destination,
    ) {}

    public function handle(Request $request): Response
    {
        $stack = array_reduce(
            array_reverse($this->middlewares),
            static fn(callable $carry, MiddlewareInterface $mw) =>
                static fn(Request $req) => $mw->process($req, $carry),
            $this->destination,
        );

        return $stack($request);
    }
}
