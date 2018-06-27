<?php

// ProcessPoolMysql.php

class ProcessesPoolMySQL extends AbstractProcessesPool
{

    protected $_sql;

    public function __construct($label, PDO $sql)
    {
        parent::__construct($label);
        $this->_sql = $sql;
        $this->_label = sha1($label);
    }

    protected function _createPool()
    {
        $request = "
            INSERT IGNORE INTO processes_pool
            VALUES ( ?, ?, NULL, CURRENT_TIMESTAMP )
        ";
        $this->_query($request, $this->_label, 0);
        return $this->_cleanPool();
    }

    protected function _cleanPool()
    {
        $request = "
            UPDATE processes_pool
            SET
                nb_launched = ?,
                pid_list = NULL,
                updated = CURRENT_TIMESTAMP
            WHERE label = ?
        ";
        $this->_query($request, 0, $this->_label);
        return true;
    }

    protected function _destroyPool()
    {
        $request = "
            DELETE FROM processes_pool
            WHERE label = ?
        ";
        $this->_query($request, $this->_label);
        return true;
    }

    protected function _getPoolAge()
    {
        $request = "
            SELECT (CURRENT_TIMESTAMP - updated) AS age
            FROM processes_pool
            WHERE label = ?
         ";
        $ret = $this->_query($request, $this->_label);
        if ($ret === null)
        {
            return -1;
        }
        return $ret['age'];
    }

    protected function _countPid()
    {
        $req = "
            SELECT nb_launched AS nb
            FROM processes_pool
            WHERE label = ?
        ";
        $ret = $this->_query($req, $this->_label);
        if ($ret === null)
        {
            return -1;
        }
        return $ret['nb'];
    }

    protected function _addPid($pid)
    {
        $request = "
            UPDATE processes_pool
            SET
                nb_launched = (nb_launched + 1),
                pid_list = CONCAT_WS(',', (SELECT IF(LENGTH(pid_list) = 0, NULL, pid_list )), ?),
                updated = CURRENT_TIMESTAMP
            WHERE label = ?
        ";
        $this->_query($request, $pid, $this->_label);
        return true;
    }

    protected function _removePid($pid)
    {
        $req = "
            UPDATE processes_pool
            SET
                nb_launched = (nb_launched - 1),
                pid_list =
                    CONCAT_WS(',', (SELECT IF (LENGTH(
                        SUBSTRING_INDEX(pid_list, ',', (FIND_IN_SET(?, pid_list) - 1))) = 0, null,
                            SUBSTRING_INDEX(pid_list, ',', (FIND_IN_SET(?, pid_list) - 1)))), (SELECT IF (LENGTH(
                                SUBSTRING_INDEX(pid_list, ',', (-1 * ((LENGTH(pid_list) - LENGTH(REPLACE(pid_list, ',', ''))) + 1 - FIND_IN_SET(?, pid_list))))) = 0, null,
                                    SUBSTRING_INDEX(pid_list, ',', (-1 * ((LENGTH(pid_list) - LENGTH(REPLACE(pid_list, ',', ''))) + 1 - FIND_IN_SET(?, pid_list))
                                )
                            )
                        )
                    )
                 ),
                updated = CURRENT_TIMESTAMP
            WHERE label = ?";
        $this->_query($req, $pid, $pid, $pid, $pid, $this->_label);
        return true;
    }

    protected function _getPidList()
    {
        $req = "
            SELECT pid_list
            FROM processes_pool
            WHERE label = ?
        ";
        $ret = $this->_query($req, $this->_label);
        if ($ret === null)
        {
            return false;
        }
        if ($ret['pid_list'] == null)
        {
            return array();
        }
        $pid_list = explode(',', $ret['pid_list']);
        return $pid_list;
    }

    protected function _query($request)
    {
        $return = null;

        $stmt = $this->_sql->prepare($request);
        if ($stmt === false)
        {
            return $return;
        }

        $params = func_get_args();
        array_shift($params);

//        echo "Request = {$request}\n";
//        echo "Params = " . var_export($params, true) . "\n";

        if ($stmt->execute($params) === false)
        {
            return $return;
        }

        if (strncasecmp(trim($request), 'SELECT', 6) === 0)
        {
            $return = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $return;
    }

}
