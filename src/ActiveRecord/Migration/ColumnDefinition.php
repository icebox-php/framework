<?php

namespace Icebox\ActiveRecord\Migration;

/**
 * ColumnDefinition - Handles individual column definitions with fluent interface
 */
class ColumnDefinition
{
    private $name;
    private $type;
    private $options;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $type
     * @param array $options
     */
    public function __construct(string $name, string $type, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * Set null constraint
     *
     * @param bool $nullable
     * @return self
     */
    public function null(bool $nullable = true): self
    {
        $this->options['null'] = $nullable;
        return $this;
    }

    /**
     * Set default value
     *
     * @param mixed $value
     * @return self
     */
    public function default($value): self
    {
        $this->options['default'] = $value;
        return $this;
    }

    /**
     * Set column comment
     *
     * @param string $comment
     * @return self
     */
    public function comment(string $comment): self
    {
        $this->options['comment'] = $comment;
        return $this;
    }

    /**
     * Set precision for decimal types
     *
     * @param int $precision
     * @return self
     */
    public function precision(int $precision): self
    {
        $this->options['precision'] = $precision;
        return $this;
    }

    /**
     * Set scale for decimal types
     *
     * @param int $scale
     * @return self
     */
    public function scale(int $scale): self
    {
        $this->options['scale'] = $scale;
        return $this;
    }

    /**
     * Set limit for string types
     *
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->options['limit'] = $limit;
        return $this;
    }

    /**
     * Set auto increment
     *
     * @param bool $autoIncrement
     * @return self
     */
    public function autoIncrement(bool $autoIncrement = true): self
    {
        $this->options['auto_increment'] = $autoIncrement;
        return $this;
    }

    /**
     * Convert to SQL column definition
     *
     * @return string
     */
    public function toSql(): string
    {
        $sqlType = SqlGenerator::mapColumnTypeToSql($this->type, $this->options);

        $parts = [$this->name, $sqlType];

        // Add NOT NULL if specified
        if (isset($this->options['null']) && !$this->options['null']) {
            $parts[] = 'NOT NULL';
        }

        // Add DEFAULT if specified
        if (isset($this->options['default'])) {
            $default = $this->options['default'];
            if (is_string($default) && !in_array(strtoupper($default), ['CURRENT_TIMESTAMP', 'NULL'])) {
                $default = "'{$default}'";
            } elseif (is_bool($default)) {
                $default = $default ? '1' : '0';
            }
            $parts[] = "DEFAULT {$default}";
        }

        // Add AUTO_INCREMENT if specified
        if (isset($this->options['auto_increment']) && $this->options['auto_increment']) {
            $parts[] = 'AUTO_INCREMENT';
        }

        // Add COMMENT if specified
        if (isset($this->options['comment'])) {
            $parts[] = "COMMENT '{$this->options['comment']}'";
        }

        return implode(' ', $parts);
    }
}
