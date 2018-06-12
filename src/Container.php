<?php
/**
 * This file is part of byrokrat/accounting.
 *
 * byrokrat/accounting is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * byrokrat/accounting is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with byrokrat/accounting. If not, see <http://www.gnu.org/licenses/>.
 *
 * Copyright 2016-18 Hannes Forsgård
 */

declare(strict_types = 1);

namespace byrokrat\accounting;

/**
 * A container is a queryable and attributable keeper of bookkeeping objects
 */
class Container implements AttributableInterface, QueryableInterface, \IteratorAggregate
{
    use Helper\AttributableTrait;

    /**
     * @var array Contained items
     */
    private $items;

    /**
     * Load items at construct
     */
    public function __construct(...$items)
    {
        $this->items = $items;
    }

    /**
     * Add additional item
     */
    public function addItem($item): self
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add multiple additional item
     */
    public function addItems(array $items): self
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * Get loaded items
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Implements the IteratorAggregate interface
     */
    public function getIterator(): iterable
    {
        foreach ($this->getItems() as $item) {
            yield $item;
        }
    }

    public function select(): Query
    {
        return new Query($this->getIterator());
    }
}
