<?php
namespace Michaels\Manager\Test\Stubs;

use Michaels\Manager\Traits\ChainsNestedItemsTrait;
use Michaels\Manager\Traits\ManagesItemsTrait;

class ChainsNestedItemsTraitStub
{
    use ManagesItemsTrait, ChainsNestedItemsTrait;

    /**
     * Build a new manager instance
     * @param array $items
     */
    public function __construct($items = [])
    {
        $this->initManager($items);
    }
}
