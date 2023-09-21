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

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Mapper,
    Schema
};

use Savks\Negotiator\Support\Mapping\Mapper;

final class UserMapper extends Mapper
{
    public function __construct(public readonly User $user)
    {
    }

    public static function schema(): Cast
    {
        return Schema::object([
            'id' => Schema::string('id'),
            'firstName' => Schema::string('first_name'),
            'lastName' => Schema::string('last_name')->nullable(),
        ], 'user');
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
* `enum` — значення перерахування.
* `null` — визначає значення як NULL.
* `any` — будь-яке значення (аналогічне такому в TypeScript).

### Комплексні типи

* `array` — звичайний масив типу — список. Працює на базі будь-якого ітеративного значення. Приклад:

```php
<?php

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Schema
};

Schema::object([
    'items' => Schema::array(
        Schema::anyObject(),
        'items'
    ),
]);

```

* `object` — об'єкт зі статичними полями. Приклад:

```php
<?php

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Schema
};

Schema::object([
    'field' => Schema::string('field'),
]);
```

* `keyedArray` — асоціативний масив/мапа, відрізняється від об'єкта тим, що базується на ітерованому значенні. Приклад:

```php
<?php

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Schema
};

Schema::object([
    'items' => Schema::keyedArray(
        Schema::anyObject(),
        'items'
    ),
]);

```

### Утилітарні типи

* `mapper` — дозволяє вказати як значенням інший мапер. Приклад:

```php
<?php

use App\Models\User;

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Schema
};

Schema::object([
    'user' => Schema::mapper(
        fn (User $user): UserMapper => new UserMapper($user),
        'user'
    ),
]);

Schema::object([
    'user' => Schema::mapper( UserMapper::class, 'user'),
]);
```

> Для правильно генерації типів, для TypeScript, у функції-резолвер мапера важливо вказувати сам мапер як тип що повертається, в іншому випадку значення набуватиму значення `any`.

* `union` — дозволяє вказати декілька можливих типів. Вказується як набір варіантів з умовами (умови не впливають на генерацію типів). Приклад:

```php
<?php

use App\Models\User;

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Schema
};

Schema::object([
    'field' => Schema::union()
        ->variant(
            fn (User $user) => $user->role === 'admin',
            Schema::object([
                'field' => Schema::string('field'),
            ])
        )
        ->variant(
            fn (User $user) => $user->role === 'guest',
            Schema::object([
                'field' => Schema::string('field'),
            ])
        )
        ->default(
            Schema::object([
                'field' => Schema::string('field'),
            ])
        ),
]);
```

* `spread` — дозволяє розкласти один об'єкт в інший. Приклад:

```php
<?php

use Savks\Negotiator\Support\Mapping\Schema;

use Savks\Negotiator\Support\Mapping\Casts\{
    ObjectUtils\Spread,
    Cast
};

Schema::object([
    'field' => Schema::string('field'),
    
    new Spread([
        'otherField' => Schema::string('other_field')
    ], 'accessor'),
]);
```

* `typedField` — дозволяє вказувати поле з типізованим ключем. Приклад:

```php
<?php

use Savks\Negotiator\Support\Mapping\Schema;

use Savks\Negotiator\Support\Mapping\Casts\{
    ObjectUtils\TypedField,
    Cast
};

Schema::object([
    'field' => Schema::string('field'),
    
    new TypedField(SomeEnum::CASE, [
        'otherField' => Schema::string('other_field')
    ]),
]);
```

* `intersection` — використовується для вказання перетнутих типів, зазвичай використовується якщо необхідно розширити інший мареп. Приклад:

```php
<?php

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Schema
};

Schema::object([
    'field' => Schema::intersection(
        Schema::mapper(UserMapper::class, 'user'),

        Schema::object([
            'otherField' => Schema::string('other_field')
        ], 'user'),
    ),
]);
```

* `oneOfConst` — дозволяє вказати, що значення може набувати одного з типів-констант. Приклад:

```php
<?php

use Savks\Negotiator\Support\Mapping\{
    Casts\Cast,
    Schema
};

Schema::object([
    'field' => Schema::oneOfConst([
        Schema::constNumber(1),
        Schema::constNumber(2),
        Schema::constNumber(3),
    ]),
]);
```

## Генерація типів

Для генерації типів пакет містить клас генератора `Savks\Negotiator\Support\TypeGeneration\Generator`. Для роботи якого достатньо вказання для яких маперів і з якими просторами імен потрібно згенерувати код. Приклад використання:

```php
<?php

use App\Http\Mapping\UserMapper;
use Savks\Negotiator\Enums\RefTypes;
use Illuminate\Support\Str;

use Savks\Negotiator\Support\TypeGeneration\TypeScript\{
    Generator,
    Target
};

$generator = new Generator(
    /* Функція для визначення референсів.  */
    fn (RefTypes $type, string $target) => match ($type) {
        RefTypes::ENUM => sprintf(
            'import(\'@enums\').%s',
            class_basename($target::class)
        ),
        RefTypes::MAPPER => sprintf(
            'import(\'@dto\').%s',
            class_basename($target::class)
        ),
    }
);

$generator->addTarget(
    new Target(UserMapper::class, '@dto')
);

$generator->saveTo('./path_to_file.ts');
```

> Бувають випадки коли генератор не зможе створити мапер для отримання типів, через те що мапер в конструкторі отримує специфічні вхідні дані. В такому випадку необхідно реалізувати інтерфейс `Savks\Negotiator\Support\Mapping\WithCustomMock` з методом створення маперу з довільними даними.

## Крайні випадки

1. **Неможливо декларативно описати дані для мапера.**

   Розв'язання цієї проблеми буде прокидка в каст кінцевих значень. Касти мають аксесор, це спосіб вказати звідки брати дані для роботи, він може бути анонімною функцією яка поверне кінцеве значення, в такому випадку в самому касті залишиться лише описати типи.
