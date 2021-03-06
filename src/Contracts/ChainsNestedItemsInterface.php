<?php
namespace Michaels\Manager\Contracts;

/**
 * Contract for Managers that use magic methods to burrow into the item store
 * See src/Traits/ChainsNestedItemsTrait.php for implementation example.
 */
interface ChainsNestedItemsInterface
{

    /**
     * Deletes item at the current level of nesting (and below)
     * @return mixed
     */
    public function drop();

    /**
     * Sets the current level of nesting.
     *
     * When burrowing into a manager using magic methods, each
     * level is a property `$manager->one->two->etc`. This method
     * must set the current level. It does NOT return a value, only
     * $this for method chaining.
     *
     * See src/Traits/ChainsNestedItemsTrait.php for implementation example.
     *
     * @param string $name Next level in dot notation to set
     * @return $this
     */
    public function __get($name);

    /**
     * Retrieves a value from the manager at the current nest level.
     *
     * Must return a value using $manager->get() using the called $name
     * as the alias and the current level set by __get() as the prefix.
     * Should throw ItemNotFoundException if item is not found.
     *
     * See src/Traits/ChainsNestedItemsTrait.php for implementation example.
     *
     * @param string $name The alias to be retrieved
     * @param array $arguments Not used at present
     * @throws \Michaels\Manager\Exceptions\ItemNotFoundException
     * @return mixed item value
     */
    public function __call($name, $arguments);

    /**
     * Sets an item at the current nest level.
     *
     * Similar to __call() above. Must navigate to desired level through methods
     * <$manager->one()->two()...> and then you can set a value as a property
     * <$manger->one()->two = "two">
     *
     * See src/Traits/ChainsNestedItemsTrait.php for implementation example.
     * @see Michaels\Manager\Contracts\ChainsNestedItemsInterface
     * @param string $key The alias to be retrieved
     * @param mixed $value Value to be set
     * @return $this
     */
    public function __set($key, $value);
}
