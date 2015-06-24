<?php
namespace Michaels\Manager\Test\Stubs;

use Michaels\Manager\Traits\ManagesItemsTrait;

/**
 * Class ManagesItemsTraitStub
 * @package Stubs
 */
class ManagesItemsTraitStub
{
    use ManagesItemsTrait;

    /**
     * The items stored in the manager
     * @var array $items Items governed by manager
     */
    protected $items;

    /**
     * Build a new manager instance
     * @param array $items
     */
    public function __construct($items = [])
    {
        $this->initManager($items);
    }
}
