<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator;

use Neusta\ConverterBundle\Converter\ConverterContext;
use Neusta\ConverterBundle\Populator\Populator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;

/**
 * @implements Populator<User, Person>
 */
class PersonNamePopulator implements Populator
{
    public function populate(object $target, object $source, ?ConverterContext $ctx = null): void
    {
        $separator = $ctx?->getValue('separator') ?? ' ';

        $target->setFullName(implode($separator, [$source->getFirstname(), $source->getLastname()]));
    }
}
