<?php

namespace Savks\Negotiator\Jit;

use Closure;
use Savks\Negotiator\Support\Mapping\Mapper;

use Savks\Negotiator\Exceptions\{
    JitCompile,
    JitFail
};
use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    Value
};
use Savks\Negotiator\Support\Types\{
    Type,
    Types
};

class Jit extends Value
{
    public readonly string $uid;

    /**
     * @param Closure(Factory):Value $callback
     */
    public function __construct(
        protected mixed $source,
        protected Closure $callback,
        string $uid = null
    ) {
        if ($uid) {
            $this->uid = $uid;
        } else {
            [, $calledMapperItem] = debug_backtrace();

            $calledMapper = $calledMapperItem['object'] ?? null;

            if (! ($calledMapper instanceof Mapper)) {
                throw new JitFail('Can\'t resolve jit uid.');
            }

            $this->uid = $calledMapper::class;
        }
    }

    protected function types(): Type|Types
    {
        return ($this->callback)(new Factory(null))->types();
    }

    public function schema(): array
    {
        $callback = $this->callback;

        return app(JitCache::class)->resolve(
            $this,
            function () use ($callback) {
                $mappedValue = $callback(
                    new Factory(null)
                );

                if (! ($mappedValue instanceof Value)) {
                    throw new JitCompile(
                        sprintf(
                            'Jit callback must return: %s.',
                            implode(', ', [
                                Value::class,
                            ])
                        )
                    );
                }

                return $mappedValue->compileSchema();
            }
        );
    }

    public function finalize(): mixed
    {
        $schema = $this->compileSchema();

        /** @var class-string<Value> $type */
        $type = $schema['$$type'];

        return $type::compileUsingSchema($schema, $this->source);
    }
}
