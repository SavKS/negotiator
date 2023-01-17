Пакет використовується як альтернатива JSON-ресурсів Laravel. Перевагою даного пакету є строга типізація мапингу та вбудований інструмент для генерації TypeScript-типів.

## Встановлення

```bash
composer require savks/negotiator
```

## Опис маперів

Для написання власного мапера потрібно створити клас який наслідує `\Savks\Negotiator\Support\Mapping\Mapper`. Приклад мапера:

```php
<?php

namespace App\Http\Mapping;

use App\Models\User;
use Savks\Negotiator\Support\Mapping\Mapper;

use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    ObjectValue
};

class UserMapper extends Mapper
{
    public function __construct(public readonly User $user)
    {
    }

    public function map(): ObjectValue
    {
        return new ObjectValue($this->user, fn (Factory $factory) => [
            'id' => $factory->string('id'),
            'firstName' => $factory->string('first_name'),
            'lastName' => $factory->string('first_name')->nullable(),
        ]);
    }
}
```

> Через те що мапинг строго типізований, потрібно звертати увагу на те, чи поле не може набувати значення `null`, і відповідно проставляти `->nullable()` для нього.

> Опис маперів не повинен містити імперативного коду, оскільки не можливо буде згенерувати типи. Це пов'язано з тим що при генерації відбувається імітація створення маперів для отримання інформації про типи, і якщо в описі буде присутній імперативний код, то це унеможливить роботу з ними.

## Вбудовані касти

### Прості типи

* `string`, `boolean`, `number` — примітиви.
* `constString`, `constBoolean`, `constNumber` — статичні типи. Відрізняються тим, що значення встановлюється явно. Також, можуть виступати як літерали (статичних значень).
* `anyObject` — дозволяє описати об'єкти опускаючи опис його полів (в TypeScript — це `Record<string, any>`).
* `any` — будь-яке значення (аналогічне такому в TypeScript).

### Комплексні типи

* `array` — звичайний масив типу — список. Працює на базі будь-якого ітеративного значення. Приклад:

```php
<?php

use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    ArrayValue\Item,
    ObjectValue
};

new ObjectValue($this->source, fn (Factory $factory) => [
    'items' => $factory->array(
        fn (Item $item) => $item->anyObject(),
        'items'
    ),
]);

```

* `object` — об'єкт зі статичними полями. Приклад:

```php
use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    ObjectValue
};

new ObjectValue($this->source, fn (Factory $factory) => [
    'field' => $factory->string('field'),
]);
```

* `keyedArray` — асоціативний масив/мапа, відрізняється від об'єкта тим, що базується на ітерованому значенні. Приклад:

```php
<?php

use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    ArrayValue\Item,
    ObjectValue
};

new ObjectValue($this->source, fn (Factory $factory) => [
    'items' => $factory->keyedArray(
        'id',
        fn (Item $item) => $item->anyObject(),
        'items'
    ),
]);

```

### Утилітарні типи

* `mapper` — дозволяє вказати як значенням інший мапер. Приклад:

```php
<?php

use App\Models\User;
use App\Mapping\UserMapper;

use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    ArrayValue\Item,
    ObjectValue
};

new ObjectValue($this->source, fn (Factory $factory) => [
    'user' => $factory->mapper(
        fn (User $item): UserMapper => new UserMapper($user),
        'user'
    ),
]);
```

> Для правильно генерації типів, для TypeScript, у функції-резолвер мапера важливо вказувати сам мапер як тип що повертається, в іншому випадку значення набуватиму значення `any`.

* `union` — дозволяє вказати декілька можливих типів. Вказується як набір варіантів з умовами (умови не впливають на генерацію типів). Приклад:

```php
<?php

use App\Models\User;

use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    ObjectValue
};

new ObjectValue($this->source, fn (Factory $factory) => [
    'field' => $factory
        ->union()
        ->variant(
            fn (User $user) => $user->role === 'admin',
            fn (Factory $factory) => $factory->object(fn (Factory $factory) => [
                'field' => $factory->string('field'),
            ])
        )
        ->variant(
            fn (User $user) => $user->role === 'guest',
            fn (Factory $factory) => $factory->object(fn (Factory $factory) => [
                'field' => $factory->string('field'),
            ])
        ),
]);
```

* `spread` — дозволяє розкласти один об'єкт в інший. Приклад:

```php
<?php

use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    ObjectValue
};

new ObjectValue($this->source, fn (Factory $factory) => [
    'field' => $factory->string('field'),
    
    $factory->spread(
        fn (Factory $factory) => [
            'otherField' => $factory->string('other_field')
        ],
        'accessor'
    ),
]);
```

* `intersection` — використовується для вказання перетнутих типів, зазвичай використовується якщо необхідно розширити інший мареп. Приклад:

```php
<?php

use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    ObjectValue
};

new ObjectValue($this->source, fn (Factory $factory) => [
    'field' => $factory->intersection(
        $factory->mapper(
            fn (User $user): UserMapper => new UserMapper($user),
            'user'
        ),
        $factory->object( fn (Factory $factory) => [
            'otherField' => $factory->string('other_field')
        ], 'user' ),
    ),
]);
```

```php
<?php

use Savks\Negotiator\Support\DTO\{
    Utils\Factory,
    Utils\Intersection,
    ObjectValue
};

new Intersection(
    new UserMapper($this->user),
    new ObjectValue( $this->user, fn (Factory $factory) => [
        'otherField' => $factory->string('other_field')
    ]),
);
```

## Генерація типів

Для генерації типів пакет містить клас генератора `Savks\Negotiator\Support\TypeGeneration\Generator`. Для роботи якого достатньо вказання для яких маперів і з якими просторами імен потрібно згенерувати код. Приклад використання:

```php
<?php

use App\Http\Mapping\UserMapper;

use Savks\Negotiator\Support\TypeGeneration\{
    Generator,
    Target
};

$generator = new Generator();

$generator->addTarget(
    new Target(UserMapper::class, '@dto')
);

$generator->saveTo('./path_to_file.ts');
```

> Бувають випадки коли генератор не зможе створити мапер для отримання типів, через те що мапер в конструкторі отримує специфічні вхідні дані. В такому випадку необхідно реалізувати інтерфейс `Savks\Negotiator\Support\Mapping\WithCustomMock` з методом створення маперу з довільними даними.

## Крайні випадки

1. **Неможливо декларативно описати дані для мапера.**

   Розв'язання цієї проблеми буде прокидка в каст кінцевих значень. Касти мають аксесор, це спосіб вказати звідки брати дані для роботи, він може бути анонімною функцією яка поверне кінцеве значення, в такому випадку в самому касті залишиться лише описати типи.
