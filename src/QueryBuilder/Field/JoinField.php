<?php

namespace Mash\MysqlJsonSerializer\QueryBuilder\Field;

class JoinField extends Field
{
    public const TYPE_MAX = 'max';

    public const TYPE_MIN = 'min';

    public const TYPE_FIRST = 'first';

    public const TYPE_COUNT = 'count';

    public const TYPE_COLLECTION = 'collection';

    /** @var string */
    private $orderBy;

    /** @var array */
    private $filter = [];

    /**
     * @var array
     */
    private $route;

    /**
     * @var string
     */
    private $property;

    /**
     * @var string
     */
    private $type;

    /**
     * @return array
     */
    public function getRoute(): array
    {
        return $this->route;
    }

    /**
     * @param array $route
     *
     * @return JoinField
     */
    public function setRoute(array $route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * @param null|string $column
     *
     * @return JoinField
     */
    public function setProperty(?string $column): self
    {
        $this->property = $column;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return JoinField
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * @param array $filter
     *
     * @return JoinField
     */
    public function setFilter(array $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    /**
     * @param string $orderBy
     *
     * @return JoinField
     */
    public function setOrderBy(string $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }
}
