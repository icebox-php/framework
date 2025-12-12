<?php

namespace Icebox\ActiveRecord;

/**
 * Rails-style Query Builder
 * 
 * Provides chainable query methods with Rails-like syntax
 */
class QueryBuilder
{
    /**
     * @var Model The model instance
     */
    private $model;

    /**
     * @var array WHERE conditions
     */
    private $conditions = [];

    /**
     * @var array ORDER BY clauses
     */
    private $order = [];

    /**
     * @var int|null LIMIT value
     */
    private $limit = null;

    /**
     * @var int|null OFFSET value
     */
    private $offset = null;

    /**
     * @var array Query parameters
     */
    private $params = [];

    /**
     * Constructor
     *
     * @param Model $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Add WHERE conditions
     *
     * Supports multiple syntax patterns:
     * 1. where('column', 'value')                    → equality
     * 2. where('column', 'operator', 'value')        → comparison
     * 3. where(['col1' => 'val1', 'col2' => 'val2']) → multiple AND
     * 4. where(['or' => ['col1' => 'val1', ...]])    → OR conditions
     * 5. where('raw SQL', [$params])                 → raw SQL
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function where($column, $operator = null, $value = null)
    {
        // Pattern 1: where('raw SQL', [$params])
        if (is_string($column) && is_array($operator) && $value === null) {
            $this->addRawCondition($column, $operator);
            return $this;
        }

        // Pattern 2: where(['col' => 'val', ...]) or where(['or' => [...]])
        if (is_array($column) && $operator === null && $value === null) {
            $this->addArrayConditions($column);
            return $this;
        }

        // Pattern 3: where('column', 'value') → equality (2 arguments)
        // Check if this is a 2-argument call (operator is actually the value)
        // We detect this by checking if operator is NOT a known SQL operator
        $knownOperators = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'IN', 'NOT IN', 'IS', 'IS NOT', 'BETWEEN'];
        if ($value === null && $operator !== null && !in_array(strtoupper($operator), $knownOperators)) {
            $value = $operator;
            $operator = '=';
        }

        // Pattern 4: where('column', 'operator', 'value')
        $this->addCondition($column, $operator, $value);
        return $this;
    }

    /**
     * Add OR WHERE conditions
     *
     * @param mixed $column
     * @param mixed $operator
     * @param mixed $value
     * @return self
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        // Handle same patterns as where() but wrap in OR
        if (is_array($column)) {
            $this->conditions[] = ['type' => 'or', 'conditions' => $column];
        } else {
            // Check if this is a 2-argument call (operator is actually the value)
            $knownOperators = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'IN', 'NOT IN', 'IS', 'IS NOT', 'BETWEEN'];
            if ($value === null && $operator !== null && !in_array(strtoupper($operator), $knownOperators)) {
                $value = $operator;
                $operator = '=';
            }
            $this->conditions[] = ['type' => 'or', 'column' => $column, 'operator' => $operator, 'value' => $value];
        }
        return $this;
    }

    /**
     * Add WHERE NULL condition
     *
     * @param string $column
     * @return self
     */
    public function whereNull($column)
    {
        return $this->where($column, 'IS', null);
    }

    /**
     * Add WHERE NOT NULL condition
     *
     * @param string $column
     * @return self
     */
    public function whereNotNull($column)
    {
        return $this->where($column, 'IS NOT', null);
    }

    /**
     * Add WHERE IN condition
     *
     * @param string $column
     * @param array $values
     * @return self
     */
    public function whereIn($column, array $values)
    {
        return $this->where($column, 'IN', $values);
    }

    /**
     * Add WHERE NOT IN condition
     *
     * @param string $column
     * @param array $values
     * @return self
     */
    public function whereNotIn($column, array $values)
    {
        return $this->where($column, 'NOT IN', $values);
    }

    /**
     * Add WHERE BETWEEN condition
     *
     * @param string $column
     * @param array $values [min, max]
     * @return self
     */
    public function whereBetween($column, array $values)
    {
        if (count($values) !== 2) {
            throw new \InvalidArgumentException('whereBetween requires exactly 2 values');
        }
        return $this->where($column, 'BETWEEN', $values);
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $column
     * @param string $direction ASC|DESC
     * @return self
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException('Order direction must be ASC or DESC');
        }
        $this->order[] = [$column, $direction];
        return $this;
    }

    /**
     * Add LIMIT clause
     *
     * @param int $limit
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Add OFFSET clause
     *
     * @param int $offset
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * Execute query and get results
     *
     * @return array
     */
    public function get()
    {
        $table = $this->model::getTable();
        $where = $this->buildWhereClause();
        
        $options = [];
        if (!empty($this->order)) {
            $orderParts = [];
            foreach ($this->order as $order) {
                $orderParts[] = "{$order[0]} {$order[1]}";
            }
            $options['order'] = implode(', ', $orderParts);
        }
        if ($this->limit !== null) {
            $options['limit'] = $this->limit;
        }
        if ($this->offset !== null) {
            $options['offset'] = $this->offset;
        }

        $results = Connection::select($table, $where, $options, $this->params);
        $models = [];

        foreach ($results as $result) {
            $models[] = new $this->model($result, true);
        }

        return $models;
    }

    /**
     * Get first result
     *
     * @return Model|null
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Get count of records
     *
     * @return int
     */
    public function count()
    {
        $table = $this->model::getTable();
        $where = $this->buildWhereClause();
        
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        $stmt = Connection::query($sql, $this->params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    /**
     * Check if any records exist
     *
     * @return bool
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Add raw SQL condition
     *
     * @param string $sql
     * @param array $params
     */
    private function addRawCondition($sql, $params)
    {
        $this->conditions[] = ['type' => 'raw', 'sql' => $sql];
        $this->params = array_merge($this->params, $params);
    }

    /**
     * Add conditions from array
     *
     * @param array $conditions
     */
    private function addArrayConditions(array $conditions)
    {
        if (isset($conditions['or'])) {
            $this->conditions[] = ['type' => 'or_group', 'conditions' => $conditions['or']];
        } else {
            foreach ($conditions as $column => $value) {
                $this->addCondition($column, '=', $value);
            }
        }
    }

    /**
     * Add single condition
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     */
    private function addCondition($column, $operator, $value)
    {
        $this->conditions[] = [
            'type' => 'and',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
    }

    /**
     * Build WHERE clause from conditions
     *
     * @return string
     */
    private function buildWhereClause()
    {
        if (empty($this->conditions)) {
            return '';
        }

        $whereParts = [];
        $this->params = []; // Reset params for building

        foreach ($this->conditions as $condition) {
            switch ($condition['type']) {
                case 'and':
                case 'or':
                    $sql = $this->buildCondition($condition);
                    $whereParts[] = $sql;
                    
                    // Add parameters based on condition type
                    $value = $condition['value'];
                    $operator = $condition['operator'];
                    
                    // For NULL values, no parameter needed
                    if ($value === null && ($operator === 'IS' || $operator === 'IS NOT' || $operator === '=' || $operator === '!=')) {
                        // No parameter to add
                    }
                    // For IN/NOT IN with array values
                    elseif (($operator === 'IN' || $operator === 'NOT IN') && is_array($value)) {
                        foreach ($value as $val) {
                            $this->params[] = $val;
                        }
                    }
                    // For BETWEEN with array values
                    elseif ($operator === 'BETWEEN' && is_array($value) && count($value) === 2) {
                        $this->params[] = $value[0];
                        $this->params[] = $value[1];
                    }
                    // For other conditions
                    else {
                        $this->params[] = $value;
                    }
                    break;
                    
                case 'or_group':
                    $orParts = [];
                    foreach ($condition['conditions'] as $col => $val) {
                        $orParts[] = $this->buildCondition(['column' => $col, 'operator' => '=', 'value' => $val]);
                        $this->params[] = $val;
                    }
                    $whereParts[] = '(' . implode(' OR ', $orParts) . ')';
                    break;
                    
                case 'raw':
                    $whereParts[] = $condition['sql'];
                    break;
            }
        }

        // Join all parts without additional AND since buildCondition already handles AND/OR
        // But we need to handle the case where first condition is OR (shouldn't happen)
        $where = '';
        foreach ($whereParts as $part) {
            if ($where === '') {
                $where = $part;
            } else {
                // If part starts with OR, just append it
                if (strtoupper(substr(trim($part), 0, 3)) === 'OR ') {
                    $where .= ' ' . $part;
                } else {
                    $where .= ' AND ' . $part;
                }
            }
        }
        
        return $where;
    }

    /**
     * Build single condition SQL
     *
     * @param array $condition
     * @return string
     */
    private function buildCondition(array $condition)
    {
        $column = $condition['column'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        $type = $condition['type'] ?? 'and';

        $prefix = $type === 'or' ? 'OR ' : '';

        // Handle NULL values
        if ($value === null) {
            if ($operator === '=' || $operator === 'IS') {
                return "{$prefix}{$column} IS NULL";
            } elseif ($operator === '!=' || $operator === 'IS NOT') {
                return "{$prefix}{$column} IS NOT NULL";
            }
        }

        // Handle IN/NOT IN
        if (($operator === 'IN' || $operator === 'NOT IN') && is_array($value)) {
            $placeholders = implode(', ', array_fill(0, count($value), '?'));
            return "{$prefix}{$column} {$operator} ({$placeholders})";
        }

        // Handle BETWEEN
        if ($operator === 'BETWEEN' && is_array($value) && count($value) === 2) {
            return "{$prefix}{$column} BETWEEN ? AND ?";
        }

        // Standard condition
        return "{$prefix}{$column} {$operator} ?";
    }
}
