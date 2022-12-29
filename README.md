# PresentationBundle

Allows to create online presentations using reveal-js

## Installation

Run `composer require neusta/converter-bundle:^1.0@dev` to activate the bundle

## Usage

After the bundle is activated you can directly use it by implementing a factory and a populator for your target and
source types.

Imagine your source type is `User`:

```php
class User
{
    private string $firstname;
    private string $lastname;
    private int $uuid;
    
    // with getters and fluent setters
}
```

and your target type is `Person`:

```php
class Person
{
    private string $fullName;

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }
}
```

and your task is to transform a given User instance into a Person instance.
Of course you can do it by instantiating a new Person and calling associated getters and setters in your code.
But - you shouldn't...why?

There are a lot of reasons but at least the most important is:

Separation of Concerns.

You should use the Converter-and-Populator-pattern. But how?!

Implement the following three artifacts:

### Factory

Implement a comfortable factory for your target type:

```php
use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Factory\TargetTypeFactory;

/**
 * @implements TargetTypeFactory<Person>
 */
class PersonFactory implements TargetTypeFactory
{
    public function create(?ConverterContext $ctx = null): Person
    {
        return new Person();
    }
}
```

Skip thinking about the converter context at the moment. It will help you...
may be not now but in a few weeks. You will see.

### Populators

Implement one or several populators:

```php
use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Populator\Populator;

/**
 * @implements Populator<User, Person>
 */
class PersonNamePopulator implements Populator
{
    public function populate(object $target, object $source, ?ConverterContext $ctx = null): void
    {
        $separationString = ' ';
        $target->setFullName($source->getFirstname() . $separationString . $source->getLastname());
    }
}
```
As you can see implementation here is quite simple - just concatenation of two attributes.
But however transformation will become more and more complexe it should be done in a testable, 
separated Populator or in several of them.

### Symfony configuration
To put things together declare the following converter in your Symfony config:
```yaml
person.converter:
    parent: 'default.converter'
    public: true
    arguments:
        $factory: '@YourNameSpace\PersonFactory'
        $populators:
            - '@YourNameSpace\PersonNamePopulator'
            # additional populators may follow 
```

### Conversion
And now if you want to convert Users into Persons just type in your code:
```php
/** @var Converter<User,Person> */
$converter = $this->getContainer()->get('person.converter');
...
$person = $this->converter->convert($user);
```
Conversion done.

## ConverterContext
Sometimes you will need parameterized conversion which is not depending on the objects themselves.
Think about environment parameters, localization or other specifications of your app.
This information can be put inside a simple `ConverterContext` object and called with your conversion:

```php
$ctx = new Neusta\ConverterBundle\Converter\DefaultConverterContext();
$ctx->setValue('locale', 'de');
...
$target = $this->converter->convert($source, $ctx);
```
The factory and the populators will be called with that context as well, so that they can read and 
use it:
```php
// inside the Populator implementation
if ($ctx && $ctx->hasKey('locale')) {
    $ocale = $ctx->getValue('locale');
}
```
Internally the DefaultConverterContext is only an associative array but the interface allows you to adapt your own
implementation of a domain-oriented context and use it in your populators as you like.

## Conversion with Caching
In some situations - especially if you are transforming objects with relation to objects - it may be helpful
to use caching to avoid conversion of same object instances again and again.

Therefore we offer a simple version of `DefaultCachedConverter`.

Before you can directly use it you have to implement a cache key strategy of your source objects;
i.e. you have to determine how one can differentiate the source objects.

This is the task of the `CacheKeyFactory`.

### CacheKeyFactory

May be in our User example there will be a unique user ID (uuid) then your `CacheKeyFactory`
should be the following:
```php
use Neusta\ConverterBundle\CacheManagement\CacheKeyFactory;

/**
 * @implements CacheKeyFactory<User>
 */
class UserKeyFactory implements CacheKeyFactory
{
    public function createCacheKey(object $source): string
    {
        return (string) $source->getUuid();
    }
}
```

### Symfony Configuration of cached conversion
To put things together declare the following cached converter in your Symfony config:
```yaml
person.converter:
    parent: 'default.converter.with.cache'
    public: true
    arguments:
        $factory: '@YourNameSpace\PersonFactory'
        $populators:
            - '@YourNameSpace\PersonNamePopulator'
        $cacheManagement: '@Neusta\ConverterBundle\CacheManagement\DefaultCacheManagement'

Neusta\ConverterBundle\CacheManagement\DefaultCacheManagement:
  arguments:
    $keyFactory: '@YourNameSpace\UserKeyFactory'
```

The `DefaultCacheManagement` is offering a simple array-based cache of converted objects which is using the $keyFactory 
to determine the cache key. This allows you to implement very domainspecific identifications of your object conversions.

The `DefaulCachedConverter` is using the `DefaultCacheManagement` component and always reads first from cache and if the
target object can not be found it will be written into the cache before returning.

## Why?!
May be you will ask yourself why not implement the Converter-and-Populator-pattern by yourself and use this extension 
instead. The answer is quite simple: 

It's a pattern and it should be done always in the same manner so that other developers will recognize the structure 
and be able to focus on the real important things:
the populations.

But if you find your "own" way you can not expect others to come into your mind asap.

Of course if you miss something here, just feel free to add it but be aware of compatibility of older
versions.