<?php
namespace Michaels\Manager\Test\Stubs;

use Michaels\Manager\Traits\ManagesItemsTrait;

class ManagesItemsTraitStub
{
    use ManagesItemsTrait;

    /**
     * Build a new manager instance
     * @param array $items
     */
    public function __construct($items = [])
    {
        $this->initManager($items);
    }
}
