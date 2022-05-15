<?php

namespace Plus\PaymentSystem\Processing\SepaViaGeDetails\Common;

abstract class AbstractParameters
{
    public const
        ERROR_TYPE = 'errorType',
        ERROR_NAME = 'name',
        ERROR_DESCRIPTION = 'description';

    /**
     * @var array
     */
    protected $params;

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @param string      $fieldName
     * @param string|null $group
     *
     * @return mixed
     */
    public function get(string $fieldName, ?string $group = null)
    {
        if (null !== $group) {
            return $this->params[$group][$fieldName] ?? null;
        }

        return $this->params[$fieldName] ?? null;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->params;
    }

    /**
     * @param string $fieldName
     *
     * @return bool
     */
    public function isset(string $fieldName): bool
    {
        return isset($this->params[$fieldName]);
    }

    /**
     * @param string $fieldName
     * @param        $value
     *
     * @return $this
     */
    public function set(string $fieldName, $value): self
    {
        $this->params[$fieldName] = $value;

        return $this;
    }
}
