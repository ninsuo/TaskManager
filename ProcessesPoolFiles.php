<?php

// ProcessPoolFiles.php

class ProcessesPoolFiles extends AbstractProcessesPool
{

    protected $_dir;

    public function __construct($label, $dir)
    {
        parent::__construct($label);
        if ((!is_dir($dir)) || (!is_writable($dir)))
        {
            throw new Exception("Directory '{$dir}' does not exist or is not writable.");
        }
        $sha1 = sha1($label);
        $this->_dir = "{$dir}/pool_{$sha1}";
    }

    protected function _createPool()
    {
        if ((!is_dir($this->_dir)) && (!mkdir($this->_dir, 0777)))
        {
            throw new Exception("Could not create '{$this->_dir}'");
        }
        usleep(100000);
        if ($this->_cleanPool() == false)
        {
            return false;
        }
        return true;
    }

    protected function _cleanPool()
    {
        $dh = opendir($this->_dir);
        if ($dh == false)
        {
            return false;
        }
        while (($file = readdir($dh)) !== false)
        {
            if (($file != '.') && ($file != '..'))
            {
                if (unlink($this->_dir . '/' . $file) == false)
                {
                    return false;
                }
            }
        }
        closedir($dh);
        return true;
    }

    protected function _destroyPool()
    {
        if ($this->_cleanPool() == false)
        {
            return false;
        }
        if (!rmdir($this->_dir))
        {
            return false;
        }
        return true;
    }

    protected function _getPoolAge()
    {
        $age = -1;
        $count = 0;
        $dh = opendir($this->_dir);
        if ($dh == false)
        {
            return false;
        }
        while (($file = readdir($dh)) !== false)
        {
            if (($file != '.') && ($file != '..'))
            {
                $stat = @stat($this->_dir . '/' . $file);
                if ($stat['mtime'] > $age)
                {
                    $age = $stat['mtime'];
                }
                $count++;
            }
        }
        closedir($dh);
        clearstatcache();
        return (($count > 0) ? (@time() - $age) : (0));
    }

    protected function _countPid()
    {
        $count = 0;
        $dh = opendir($this->_dir);
        if ($dh == false)
        {
            return -1;
        }
        while (($file = readdir($dh)) !== false)
        {
            if (($file != '.') && ($file != '..'))
            {
                $count++;
            }
        }
        closedir($dh);
        return $count;
    }

    protected function _addPid($pid)
    {
        $file = $this->_dir . "/" . $pid;
        if (is_file($file))
        {
            return true;
        }
        echo "{$file}\n";
        $file = fopen($file, 'w');
        if ($file == false)
        {
            return false;
        }
        fclose($file);
        return true;
    }

    protected function _removePid($pid)
    {
        $file = $this->_dir . "/" . $pid;
        if (!is_file($file))
        {
            return true;
        }
        if (unlink($file) == false)
        {
            return false;
        }
        return true;
    }

    protected function _getPidList()
    {
        $array = array();
        $dh = opendir($this->_dir);
        if ($dh == false)
        {
            return false;
        }
        while (($file = readdir($dh)) !== false)
        {
            if (($file != '.') && ($file != '..'))
            {
                $array[] = $file;
            }
        }
        closedir($dh);
        return $array;
    }

}
