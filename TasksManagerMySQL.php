<?php

class TasksManagerMySQL implements TasksManagerInterface
{

    protected $_cluster_label;
    protected $_calcul_label;
    protected $_sql;

    const WAITING = "waiting";
    const RUNNING = "running";
    const SUCCESS = "success";
    const FAILED = "failed";

    public function __construct($label, PDO $sql)
    {
        $this->_sql = $sql;
        $this->_cluster_label = substr($label, 0, 40);
    }

    public function getClusterLabel()
    {
        return $this->_cluster_label;
    }

    public function getCalculLabel()
    {
        return $this->_calcul_label;
    }

    public function destroy()
    {
        $request = "
            DELETE FROM tasks_manager
            WHERE cluster_label = ?
        ";
        $this->_query($request, $this->_cluster_label);
        return $this;
    }

    public function start($calcul_label)
    {
        $this->_calcul_label = $calcul_label;
        $this->add($calcul_label, self::RUNNING);
        return $this;
    }

    public function finish($status = self::SUCCESS)
    {
        if (!$this->_isStatus($status))
        {
            throw new Exception("{$status} is not a valid status.");
        }
        if (is_null($this->_cluster_label))
        {
            throw new Exception("finish() called, but task never started.");
        }
        $request = "
            UPDATE tasks_manager
            SET status = ?
            WHERE cluster_label = ?
            AND calcul_label = ?
         ";
        $this->_query($request, $status, $this->_cluster_label, substr($this->_calcul_label, 0, 40));
        return $this;
    }

    public function add($calcul_label, $status = self::WAITING)
    {
        if (!$this->_isStatus($status))
        {
            throw new Exception("{$status} is not a valid status.");
        }
        $request = "
            INSERT INTO tasks_manager (
                cluster_label, calcul_label, status
            ) VALUES (
                ?, ?, ?
            )
            ON DUPLICATE KEY UPDATE
                status = ?
        ";
        $calcul_label = substr($calcul_label, 0, 40);
        $this->_query($request, $this->_cluster_label, $calcul_label, $status, $status);
        return $this;
    }

    public function delete($calcul_label)
    {
        $request = "
            DELETE FROM tasks_manager
            WHERE cluster_label = ?
            AND calcul_label = ?
        ";
        $this->_query($request, $this->_cluster_label, substr($calcul_label, 0, 40));
        return $this;
    }

    public function countStatus($status = self::SUCCESS)
    {
        if (!$this->_isStatus($status))
        {
            throw new Exception("{$status} is not a valid status.");
        }
        $request = "
            SELECT COUNT(*) AS cnt
            FROM tasks_manager
            WHERE cluster_label = ?
            AND status = ?
        ";
        $ret = $this->_query($request, $this->_cluster_label, $status);
        return $ret[0]['cnt'];
    }

    public function count()
    {
        $request = "
            SELECT COUNT(id) AS cnt
            FROM tasks_manager
            WHERE cluster_label = ?
        ";
        $ret = $this->_query($request, $this->_cluster_label);
        return $ret[0]['cnt'];
    }

    public function getCalculsByStatus($status = self::SUCCESS)
    {
        if (!$this->_isStatus($status))
        {
            throw new Exception("{$status} is not a valid status.");
        }
        $request = "
            SELECT calcul_label
            FROM tasks_manager
            WHERE cluster_label = ?
            AND status = ?
        ";
        $ret = $this->_query($request, $this->_cluster_label, $status);
        $array = array();
        if (!is_null($ret))
        {
            $array = array_map(function($row) {
                return $row['calcul_label'];
            }, $ret);
        }
        return $array;
    }

    public function switchStatus($statusA = self::RUNNING, $statusB = null)
    {
        if (!$this->_isStatus($statusA))
        {
            throw new Exception("{$statusA} is not a valid status.");
        }
        if ((!is_null($statusB)) && (!$this->_isStatus($statusB)))
        {
            throw new Exception("{$statusB} is not a valid status.");
        }
        if ($statusB != null)
        {
            $request = "
                UPDATE tasks_manager
                SET status = ?
                WHERE cluster_label = ?
                AND status = ?
            ";
            $this->_query($request, $statusB, $this->_cluster_label, $statusA);
        }
        else
        {
            $request = "
                UPDATE tasks_manager
                SET status = ?
                WHERE cluster_label = ?
            ";
            $this->_query($request, $statusA, $this->_cluster_label);
        }
        return $this;
    }

    private function _isStatus($status)
    {
        if (!is_string($status))
        {
            return false;
        }
        return in_array($status, array(
                self::FAILED,
                self::RUNNING,
                self::SUCCESS,
                self::WAITING,
        ));
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

        if ($stmt->execute($params) === false)
        {
            return $return;
        }

        if (strncasecmp(trim($request), 'SELECT', 6) === 0)
        {
            $return = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $return;
    }

}
