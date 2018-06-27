<?php

// AbstractProcessPool.php

abstract class AbstractProcessesPool
{

    abstract protected function _createPool();

    abstract protected function _cleanPool();

    abstract protected function _destroyPool();

    abstract protected function _getPoolAge();

    abstract protected function _countPid();

    abstract protected function _addPid($pid);

    abstract protected function _removePid($pid);

    abstract protected function _getPidList();

    protected $_label;
    protected $_max;
    protected $_pid;

    public function __construct($label)
    {
        $this->_max = 0;
        $this->_label = $label;
        $this->_pid = getmypid();
    }

    public function getLabel()
    {
        return ($this->_label);
    }

    public function create($max = 20)
    {
        $this->_max = $max;
        $ret = $this->_createPool();
        usleep(500000);
        return $ret;
    }

    public function destroy()
    {
        $ret = $this->_destroyPool();
        return $ret;
    }

    public function waitForResource($timeout = 120, $interval = 500000, $callback = null)
    {
        // let enough time for children to take a resource
        usleep(200000);
        while (44000)
        {
            if (($callback != null) && (is_callable($callback)))
            {
                call_user_func($callback, $this);
            }
            $age = $this->_getPoolAge();
            if ($age == -1)
            {
                return false;
            }
            if ($age > $timeout)
            {
                return false;
            }
            $count = $this->_countPid();
            if ($count == -1)
            {
                return false;
            }
            if ($count < $this->_max)
            {
                break;
            }
            usleep($interval);
        }
        return true;
    }

    public function waitForTheEnd($timeout = 3600, $interval = 500000, $callback = null)
    {
        // let enough time to the last child to take a resource
        usleep(200000);
        while (44000)
        {
            if (($callback != null) && (is_callable($callback)))
            {
                call_user_func($callback, $this);
            }
            $age = $this->_getPoolAge();
            if ($age == -1)
            {
                return false;
            }
            if ($age > $timeout)
            {
                return false;
            }
            $count = $this->_countPid();
            if ($count == -1)
            {
                return false;
            }
            if ($count == 0)
            {
                break;
            }
            usleep($interval);
        }
        return true;
    }

    public function start()
    {
        $ret = $this->_addPid($this->_pid);
        return $ret;
    }

    public function finish()
    {
        $ret = $this->_removePid($this->_pid);
        return $ret;
    }

    public function killAllResources($code = 9)
    {
        $pids = $this->_getPidList();
        if ($pids == false)
        {
            $this->_cleanPool();
            return false;
        }
        foreach ($pids as $pid)
        {
            $pid = intval($pid);
            posix_kill($pid, $code);
            if ($this->_removePid($pid) == false)
            {
                return false;
            }
        }
        return true;
    }

}
