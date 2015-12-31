<?php
namespace Jack\System;

abstract class JackORM{

    //-----------------------------------------------------------------------
    // Class specific properties, modify only if you know what you are doing.
    //-----------------------------------------------------------------------
    private $dbh;                   // Database handle.
    private $error;                 // Exception errors.
    private $sth;                   // Statement handle.
    private $query = "";            // The query string that we are building
    private $customCrafted = false; // Whether or not user is crafting queries by himself using query() method
    private $select = false;        // The SELECT part of the query
    private $where = [];      gi      // WHERE clause.
    private $like = [];             // LIKE clause
    private $groupBy = false;       // GROUP BY clause.
    private $having = [];           // HAVING clause.
    private $limit = null;          // LIMIT clause.
    private $count = false;         // Whether or not we are using count() method.
    private static $lastQuery;      // The last executed SQL query See showQuery().
    /**
     * Initializes the PDO database connection
     */
    function __construct()
    {
        // tries to connect
        try {
            $this->dbh = new PDO("mysql:host=" . DB_HOST . ";
                                  dbname="     . DATABASE . ";
                                  charset="    . DB_CHARSET . "",
                DB_USERNAME,
                DB_PASSWORD,
                // Return the number of found (matched) rows, not the number of changed rows.
                array(PDO::MYSQL_ATTR_FOUND_ROWS => true));
            // PDO can use exceptions to handle errors.
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Use MySQL prepared statements not PDO emulated.
            $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            // Persistent database connections can increase performance.
            $this->dbh->setAttribute(PDO::ATTR_PERSISTENT, true);
        } catch (PDOException $e) { // Catch any errors
            $this->$error = $e->getMessage();
        }
    }
//-----------------------------------------------------------------------
// Read methods
//-----------------------------------------------------------------------
    /**
     * Select method.
     *
     * @param  string $columns You can specify which columns to select as a comma separated string values. Default is *.
     * @return object
     */
    public function select($columns = '*')
    {
        $this->query .= "SELECT $columns FROM fRoMtAbLe ";
        $this->select = true;
        return $this;
    }
    /**
     * Method used for counting.
     *
     * @param  string $columns Which columns you want to count.
     * @return object
     */
    public function count($columns = "*")
    {
        $this->query = "SELECT COUNT($columns) as cnt FROM fRoMtAbLe ";
        $this->count = true;
        return $this;
    }
    /**
     * Specifies the from portion of the query.
     *
     * @param  string $table The user submitted table name.
     * @return object
     */
    public function from($table)
    {
        $this->query = str_replace("fRoMtAbLe", $table, $this->query);
        static::$table = $table;
        return $this;
    }
    /**
     * where method. Here you can specify your WHERE condition. If you have multiple conditions use andWhere() and
     * orWhere() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions WHERE condition. Example: ['id' => 5] will produce WHERE id = 5.
     *                            To specify operator other than '=', pass the operator value as the
     *                            second array element Eg: ['id' => 5, '>='].
     * @return object
     */
    public function where(array $condition)
    {
        if (count($condition) > 2) {
            throw new Exception("where method can not accept more than one condition, please use
                                 andWhere() or orWhere() to add more conditions to your query", 1);
        }
        $column = array_keys($condition)[0];
        if (isset(array_values($condition)[1])) {
            $operator = array_values($condition)[1];
        } else {
            $operator = " = ";
        }

        $this->where = array_slice($condition, 0, 1);
        $this->query .= "WHERE $column $operator ? ";
        return $this;
    }
    /**
     * andWhere method. Here you can specify your AND (WHERE) condition. If you have multiple conditions use more
     * andWhere() and orWhere() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions AND (WHERE) condition. Example: ['id' => 5] will produce WHERE id = 5.
     *                            To specify operator other than '=', pass the operator value as the
     *                            second array element Eg: ['id' => 5, '>='].
     * @return object
     */
    public function andWhere(array $condition)
    {
        if (count($condition) > 2) {
            throw new Exception("andWhere method can not accept more than one condition, please use more
                                 andWhere() or orWhere() methods to add more conditions to your query", 1);
        }
        if (empty($this->where)) {
            throw new Exception("You need to use where() method first.", 1);
        }
        $column = array_keys($condition)[0];
        if (isset(array_values($condition)[1])) {
            $operator = array_values($condition)[1];
        } else {
            $operator = " = ";
        }

        $this->where = array_merge_recursive($this->where, array_slice($condition, 0, 1));
        $this->query .= "AND $column $operator ? ";
        return $this;
    }
    /**
     * orWhere method. Here you can specify your OR (WHERE) condition. If you have multiple conditions use more
     * andWhere() and orWhere() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions OR (WHERE) condition. Example: ['id' => 5] will produce WHERE id = 5.
     *                            To specify operator other than '=', pass the operator value as the
     *                            second array element Eg: ['id' => 5, '>='].
     * @return object
     */
    public function orWhere(array $condition)
    {
        if (count($condition) > 2) {
            throw new Exception("orWhere method can not accept more than one condition, please use more
                                 orWhere() or andWhere() methods to add more conditions to your query", 1);
        }
        if (empty($this->where)) {
            throw new Exception("You need to use where() method first.", 1);
        }
        $column = array_keys($condition)[0];
        if (isset(array_values($condition)[1])) {
            $operator = array_values($condition)[1];
        } else {
            $operator = " = ";
        }

        $this->where = array_merge_recursive($this->where, array_slice($condition, 0, 1));
        $this->query .= "OR $column $operator ? ";
        return $this;
    }
    /**
     * like method. Here you can specify your LIKE condition. If you have multiple conditions use andLike() and
     * orLike() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions LIKE condition. Example: ['id' => %5] will produce WHERE id LIKE %5.
     * @return object
     */
    public function like(array $condition)
    {
        if (count($this->where) > 0) {
            throw new Exception("you can not use both where() and like() methods together, please use
                                 andLike() or orLike() if you need to add LIKE to existing WHERE", 1);
        }
        if (count($condition) > 2) {
            throw new Exception("like method can not accept more than one conditin, please use
                                 andLike() or orLike() to add more conditions to your query", 1);
        }
        $column = array_keys($condition)[0];
        $this->like = array_slice($condition, 0, 1);
        $this->query .= "WHERE $column LIKE ? ";
        return $this;
    }
    /**
     * andLike method. Here you can specify your AND (LIKE) condition. If you have multiple conditions use more
     * andLike() and orLike() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions AND (LIKE) condition. Example: ['id' => '%5'] will produce AND id LIKE %5.
     * @return object
     */
    public function andLike(array $condition)
    {
        if (count($condition) > 2) {
            throw new Exception("andLike method can not accept more than one condition, please use more
                                 andLike() or orLike() methods to add more conditions to your query", 1);
        }
        if (empty($this->where) && empty($this->like)) {
            throw new Exception("You need to use where() OR like() method first.", 1);
        }
        $column = array_keys($condition)[0];
        $this->like = array_merge_recursive($this->like, array_slice($condition, 0, 1));
        $this->query .= "AND $column LIKE ? ";
        return $this;
    }
    /**
     * orLike method. Here you can specify your OR (LIKE) condition. If you have multiple conditions use more
     * andLike() and orLike() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions OR (LIKE) condition. Example: ['id' => '%5'] will produce OR id LIKE %5.
     * @return object
     */
    public function orLike(array $condition)
    {
        if (count($condition) > 2) {
            throw new Exception("orLike method can not accept more than one condition, please use more
                                 orLike() or andLike() methods to add more conditions to your query", 1);
        }
        if (empty($this->where) && empty($this->like)) {
            throw new Exception("You need to use where() OR like() method first.", 1);
        }
        $column = array_keys($condition)[0];
        $this->like = array_merge_recursive($this->like, array_slice($condition, 0, 1));
        $this->query .= "OR $column LIKE ? ";
        return $this;
    }
    /**
     * having method. Here you can specify your HAVING condition. If you have multiple conditions use andHaving() and
     * orHaving() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions HAVING condition. Example: ['id' => 5] will produce HAVING id = 5.
     *                            To specify operator other than '=', pass the operator value as the second
     *                            array element. Eg: ['id' => 5, '>='].
     * @return object
     */
    public function having(array $condition)
    {
        if (count($condition) > 2) {
            throw new Exception("having method can not accept more than one condition, please use
                                 andHaving() or orHaving() to add more conditions to your query", 1);
        }
        if ($this->groupBy == false) {
            throw new Exception("You need to use groupBy() method in order to be able to use having()", 1);
        }
        $column = array_keys($condition)[0];
        $value = array_values($condition)[0];
        if (isset(array_values($condition)[1])) {
            $operator = array_values($condition)[1];
        } else {
            $operator = " = ";
        }

        $this->having = array_slice($condition, 0, 1);
        $this->query .= "HAVING $column $operator ? ";
        return $this;
    }
    /**
     * andHaving method. Here you can specify your AND (HAVING) condition. If you have multiple conditions use more
     * andHaving() and orHaving() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions OR (HAVING) condition. Example: ['id' => 5] will produce HAVING id = 5.
     *                            To specify operator other than '=', pass the operator value as the second
     *                            array element. Eg: ['id' => 5, '>='].
     * @return object
     */
    public function andHaving(array $condition)
    {
        if (count($condition) > 2) {
            throw new Exception("andHaving method can not accept more than one condition, please use more
                                 andHaving() or orWhere() methods to add more conditions to your query", 1);
        }
        if ($this->groupBy == false || empty($this->having)) {
            throw new Exception("You need to use groupBy() and having() methods first.", 1);
        }
        $column = array_keys($condition)[0];
        $value = array_values($condition)[0];
        if (isset(array_values($condition)[1])) {
            $operator = array_values($condition)[1];
        } else {
            $operator = " = ";
        }
        $this->having = array_merge_recursive($this->having, array_slice($condition, 0, 1));
        $this->query .= "AND $column $operator ? ";
        return $this;
    }
    /**
     * orHaving method. Here you can specify your OR (HAVING) condition. If you have multiple conditions use more
     * andHaving() and orHaving() methods. Each method including this one can state only one condition.
     *
     * @param  array  $conditions OR (HAVING) condition. Example: ['id' => 5] will produce HAVING id = 5.
     *                            To specify operator other than '=', pass the operator value as the second
     *                            array element. Eg: ['id' => 5, '>='].
     * @return object
     */
    public function orHaving(array $condition)
    {
        if (count($condition) > 2) {
            throw new Exception("orWhere method can not accept more than one condition, please use more
                                 orWhere() or andWhere() methods to add more conditions to your query", 1);
        }
        if ($this->groupBy == false || empty($this->having)) {
            throw new Exception("You need to use groupBy() and having() methods first.", 1);
        }
        $column = array_keys($condition)[0];
        $value = array_values($condition)[0];
        if (isset(array_values($condition)[1])) {
            $operator = array_values($condition)[1];
        } else {
            $operator = " = ";
        }
        $this->having = array_merge_recursive($this->having, array_slice($condition, 0, 1));
        $this->query .= "OR $column $operator ? ";
        return $this;
    }
    /**
     * Specifies the LEFT JOIN part of the query.
     *
     * @param  string $tableToJoinWith The right table name.
     * @param  string $on              The ON condition
     * @return object
     */
    public function leftJoin($tableToJoinWith, $on)
    {
        $this->query .= "LEFT JOIN $tableToJoinWith ON $on ";
        return $this;
    }
    /**
     * Specifies the RIGHT JOIN part of the query.
     *
     * @param  string $tableToJoinWith The right table name.
     * @param  string $on              The ON condition
     * @return object
     */
    public function rightJoin($tableToJoinWith, $on)
    {
        $this->query .= "RIGHT JOIN $tableToJoinWith ON $on ";
        return $this;
    }
    /**
     * Specifies the INNER JOIN part of the query.
     *
     * @param  string $tableToJoinWith The right table name.
     * @param  string $on              The ON condition
     * @return object
     */
    public function innerJoin($tableToJoinWith, $on)
    {
        $this->query .= "INNER JOIN $tableToJoinWith ON $on ";
        return $this;
    }
    /**
     * Since default JOIN type in MYSQL is INNER, you can use this shortcut method that will just call innerJoin().
     *
     * @param  string $tableToJoinWith The right table name.
     * @param  string $on              The ON condition
     * @return object                  Calls the innerJoin() method
     */
    public function join($tableToJoinWith, $on)
    {
        return $this->innerJoin($tableToJoinWith, $on);
    }
    /**
     * groupBy method. Specifies the GROUP BY part of the query.
     *
     * @param  string
     * @return object
     */
    public function groupBy($groupBy)
    {
        $this->query .= "GROUP BY $groupBy ";
        $this->groupBy = true;
        return $this;
    }
    /**
     * orderBy method. Specifies the ORDER BY part of the query.
     *
     * @param  string $order The ORDER BY
     * @return object
     */
    public function orderBy($order) {
        $this->query .= "ORDER BY $order ";
        return $this;
    }
    /**
     * limit method. Specifies the LIMIT part of the query.
     *
     * @param  integer The LIMIT
     * @return object
     */
    public function limit($limit)
    {
        if (!is_int($limit)) {
            throw new Exception("The value you have specified for limit is not integer!", 1);
        }
        $this->limit = $limit;
        $this->query .= "LIMIT $limit ";
        return $this;
    }
    /**
     * offset method. Specifies the OFFSET part of the query.
     *
     * @param  integer The OFFSET
     * @return object
     */
    public function offset($offset)
    {
        if (!is_int($offset)) {
            throw new Exception("The value you have specified for offset is not integer!", 1);
        }
        if ($this->limit == null) {
            throw new Exception("You can not specify offset without limit!", 1);
        }
        $this->query .= "OFFSET $offset";
        return $this;
    }
//-----------------------------------------------------------------------
// Methods for custom select query crafting
//-----------------------------------------------------------------------
    /**
     * Method that is accepting custom crafted sql query.
     *
     * @param  mixed $sql Your SQL query. Please use bind() method to bind values!
     * @return object
     */
    protected function query($sql)
    {
        $this->query .= $sql;
        $this->customCrafted = true;
        $this->sth = $this->dbh->prepare($this->query);
        $this->sth->setFetchMode(PDO::FETCH_CLASS, ucfirst(get_called_class()));
        return $this;
    }
    /**
     * Protected method that will invoke the private one called bindValues().
     * We do not want you to invoke bindValues directly since it is used internally in this class,
     * and overriding it ( if it would be protected or public ) can cause DB class malfunction.
     * If you need to change the way you are doing binding, you can override this method with no worries about
     * internal class calls.
     *
     *
     * @param  array        $data                         The array of placeholder => values
     * @param  integer|null $numberOfPreviousPlaceholders If we are using this method more than once
     *                                                    ( inside insert and update methods we will ), we need to know
     *                                                    how many previous binds we did.
     * @return object
     */
    protected function bind(array $data, $numberOfPreviousPlaceholders = null)
    {
        return $this->bindValues($data, $numberOfPreviousPlaceholders);
    }
//-----------------------------------------------------------------------
// Save methods
//-----------------------------------------------------------------------
    /**
     * Method that is inserting data into database.
     *
     * @param  string|null  $intoTable You can specify the table you want to insert into, or let the model use
     *                                 static::$table property.
     * @param  array  $data            The array of columns and their values.
     * @param string  $extra           The extra condition.
     * @return integer                 The row count.
     */
    public function insert($intoTable = null, array $data = [], $extra = '')
    {
        /**
         * $intoTable param is optional, that is why in case you specify only one param method we consider it to be
         * array of columns and their values you want to insert.
         */
        if (is_array($intoTable)) {
            $data = $intoTable;
            $extra = $data;
            $intoTable = static::$table;
        }
        // sets the column names
        $columnNames = join(", ", array_keys($data));
        $values = "";
        // sets the placeholders
        foreach ($data as $column => $value) {
            $values .= "?, ";
        }
        // clean the extra ", " from the end
        $values = substr($values, 0, -2);
        // inserts data into database
        $this->sth = $this->dbh->prepare("INSERT INTO " . $intoTable . " ( " . $columnNames . " )
                                          VALUES ( " . $values . " ) " . $extra . "");
        // bind parameters to placeholder values
        $this->bindValues($data);
        $this->sth->execute();
        self::$lastQuery = $this->sth;
        // returns the last inserted id.
        return $this->dbh->lastInsertId();
    }
    /**
     * Method that is inserting data into database.
     *
     * @param  string|null $intoTable You can specify the table you want to insert into, or let the model use
     *                                static::$table property.
     * @param  array  $data           The array of columns and their values.
     * @return integer                The row count.
     */
    public function update($intoTable = null, array $data = [], array $condition = [])
    {
        /**
         * $intoTable param is optional, so in case you do not specify if we will
         * "move" params one spot to the left.
         */
        if (is_array($intoTable)) {
            $condition = $data;
            $data = $intoTable;
            $intoTable = static::$table;
        }
        $values = "";
        $i = 1;
        // sets the placeholders
        foreach ($data as $column => $value) {
            $values .= "$column = ?, ";
            $i++;
        }
        // clean the extra ", " from the end
        $columnNames = substr($values, 0, -2);
        if (count($condition) > 2) {
            throw new Exception("It is not possible to specify more than 1 condition!", 1);
        }
        $column = array_keys($condition)[0];
        if (isset(array_values($condition)[1])) {
            $operator = array_values($condition)[1];
        } else {
            $operator = " = ";
        }
        // inserts data into database
        $this->sth = $this->dbh->prepare("UPDATE " . $intoTable . "
                                          SET " . $columnNames . "
                                          WHERE $column $operator ? ");
        // bind parameters to placeholder values
        $this->bindValues($data);
        $this->bindValues(array_slice($condition, 0, 1), $i);
        $this->sth->execute();
        self::$lastQuery = $this->sth;
        // returns the number of affected rows.
        return $this->sth->rowCount();
    }
//-----------------------------------------------------------------------
// Delete methods
//-----------------------------------------------------------------------
    /**
     * Delete method that can delete by id or custom condition.
     *
     * @param  string        $fromTable The table you want to delete from. Defaults to static::$table of your model.
     * @param  array|integer $condition The id of row to be deleted or custom where condition supplied as an array.
     * @return integer                  The number of affected rows.
     */
    public function delete($fromTable = null, $condition = null)
    {
        if (!is_string($fromTable)) {
            $condition = $fromTable;
            $fromTable = static::$table;
        }
        // we are deleting by id
        if (is_integer($condition)) {
            $this->sth = $this->dbh->prepare("DELETE FROM " . $fromTable . " WHERE  id = :id ");
            $this->sth->bindParam(':id', $condition, PDO::PARAM_INT);
            $this->sth->execute();
            self::$lastQuery = $this->sth;
            return $this->sth->rowCount();
        } else { // we are deleting using custom WHERE
            $column = array_keys($condition)[0];
            if (isset(array_values($condition)[1])) {
                $operator = array_values($condition)[1];
            } else {
                $operator = " = ";
            }
            $this->sth = $this->dbh->prepare("DELETE FROM " . $fromTable . " WHERE $column $operator ? ");

            $this->bindValues(array_slice($condition, 0, 1));
            $this->sth->execute();
            self::$lastQuery = $this->sth;
            return $this->sth->rowCount();
        }
    }
    /**
     * Deletes all records from the specified table.
     *
     * @param  string  $fromTable You can specify the table you want to purge. Defaults to static::$table of your model.
     * @return integer            The number of affected rows.
     */
    public function deleteAll($fromTable = null)
    {
        if (is_null($fromTable)) {
            $fromTable = static::$table;
        }
        $this->sth = $this->dbh->prepare("DELETE FROM " . $fromTable . " ");
        $this->sth->execute();
        self::$lastQuery = $this->sth;
        return $this->sth->rowCount();
    }
//-----------------------------------------------------------------------
// Helper methods
//-----------------------------------------------------------------------
    /**
     * Method returning number of elements that are not arrays, recursively.
     *
     * @param  array   $array  Array to be checked
     * @param  integer &$count Counted so far
     * @return integer         The number of elements
     */
    private function count_elt($array, &$count = 0)
    {
        foreach($array as $v) if(is_array($v)) $this->count_elt($v, $count); else ++$count;
        return $count;
    }
    /**
     * Method that is binding values.
     *
     * @param  array        $data                         The array of column => values
     * @param  integer|null $numberOfPreviousPlaceholders If we are using this method more than once, we need to know
     *                                                    how many previous bind we did.
     * @return object
     */
    private function bindValues(array $data, $numberOfPreviousPlaceholders = null)
    {
        if (is_null($numberOfPreviousPlaceholders)) {
            $i = 1;
        } else {
            $i = $numberOfPreviousPlaceholders;
        }
        // get all values
        foreach ($data as $key => &$value) {
            // we have the nested array like 'rating' => [ 0 => 2, 1 => 4]
            if (is_array($value)) {
                foreach ($value as $innerKey => &$innerValue) {
                    if (is_int($innerValue)) {
                        $this->sth->bindParam($i, $innerValue, PDO::PARAM_INT);
                    } elseif (is_bool($innerValue)) {
                        $this->sth->bindParam($i, $innerValue, PDO::PARAM_BOOL);
                    } elseif (is_null($innerValue)) {
                        $this->sth->bindParam($i, $innerValue, PDO::PARAM_NULL);
                    } else {
                        $this->sth->bindParam($i, $innerValue, PDO::PARAM_STR);
                    }
                    $i++;
                }
            } else {
                if (is_int($value)) {
                    $this->sth->bindParam($i, $value, PDO::PARAM_INT);
                } elseif (is_bool($value)) {
                    $this->sth->bindParam($i, $value, PDO::PARAM_BOOL);
                } elseif (is_null($value)) {
                    $this->sth->bindParam($i, $value, PDO::PARAM_NULL);
                } else {
                    $this->sth->bindParam($i, $value, PDO::PARAM_STR);
                }
                $i++;
            } // if/else
        } // main foreach
        return $this;
    }
    /**
     * Dynamically binds the parameter values for WHERE/LIKE/HAVING conditions
     */
    private function bindValuesForConditions()
    {
        $where  = $this->where;
        $like   = $this->like;
        $having = $this->having;
        $whereCount  = $this->count_elt($where);
        $likeCount = $this->count_elt($like);
        $havingCount = $this->count_elt($having);
        if ($whereCount > 0) {
            $this->bindValues($where);
        }
        if ($likeCount > 0) {
            $j = $whereCount + 1;
            $this->bindValues($like, $j);
        }
        if ($havingCount > 0) {
            $k = (isset($j)) ? $j + 1 : $whereCount +1 ;
            $this->bindValues($having, $k);
        }
    }
    /**
     * Executes the select type queries. You need to call this method at the end of queries that are doing DB read.
     *
     * @return object
     */
    public function get()
    {
        if ($this->select == false && $this->count == false && $this->customCrafted == false) {
            throw new Exception("Did you used select() or count() method ?.
                                 If you are not trying to craft your own query with the query() method,
                                 you will have to use either select() or count() first.", 1);
        }
        // if query is custom crafted we only have to execute it here, other stuff is done in query() and bind() methods
        if ($this->customCrafted == true) {
            $this->sth->execute();
        } else {
            $query = str_replace("fRoMtAbLe", static::$table, $this->query);
            $this->sth = $this->dbh->prepare($query);
            $this->sth->setFetchMode(PDO::FETCH_CLASS, ucfirst(get_called_class()));
            $this->bindValuesForConditions();
            $this->sth->execute();
            self::$lastQuery = $query;
        }
        if ($this->count == true) {
            foreach ($this->sth as $key => $value) {
                return $value->cnt;
            }
        }
        return $this->sth;
    }
    /**
     * Method that is displaying last executed query.
     * Please use only for debug/learn purpose.
     * You should never display this information to end users.
     *
     * @provide string      Query string.
     */
    public static function showQuery()
    {
        echo "<hr>";
        echo "<pre>";
        var_dump(self::$lastQuery);
        echo "</pre>";
        echo "<hr>";
    }
}