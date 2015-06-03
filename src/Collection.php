<?php
namespace Michaels\Manager;

use ArrayIterator;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Interop\Container\ContainerInterface;
use Michaels\Manager\Contracts\ChainsNestedItemsInterface;
use Michaels\Manager\Contracts\ManagesItemsInterface;
use Michaels\Manager\Traits\ArrayableTrait;
use Michaels\Manager\Traits\ChainsNestedItemsTrait;
use Michaels\Manager\Traits\CollectionTrait;
use Michaels\Manager\Traits\ManagesItemsTrait;

/**
 * Manages deeply nested, complex data with extra collection methods.
 *
 * This concrete class implements ManagesItems and Collection as well as
 * Container interoperability and various array functionality.
 *
 * @package Michaels\Manager
 */
class Collection extends \Illuminate\Support\Collection
//    implements
//    ManagesItemsInterface,
//    ContainerInterface,
//    ArrayAccess,
//    Countable,
//    IteratorAggregate,
//    JsonSerializable
{
    use ManagesItemsTrait;
//        , ArrayableTrait;
}
