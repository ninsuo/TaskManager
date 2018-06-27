<?php

// En utilisant la classe Sync, créer Thread et Mutex des classes bcp plus simples pour gérer le multitache

/**
 * A cross-over between mutex and semaphore (in fact, a mutex is a semaphore of size 1).
 *
 * @uses Sync
 * @see http://www.phpclasses.org/package/8071-PHP-Share-variables-across-multiple-PHP-apps.html
 * @license http://opensource.org/licenses/bsd-license.html
 * @author Alain Tiemblo <alain@fuz.org>
 */
class Mutex
{

    private $_size;
    private $_shared;
    private $_key;

    public function __construct(Sync $shared, $size, $key = 'sem')
    {
        $this->_shared = $shared;
        $this->resize($size);
        $this->changeKey($key);
    }

    public function take($interval = 0, $timeout = 0)
    {
        if ((!is_numeric($interval)) || (!is_numeric($timeout)))
        {
            throw new \Exception(sprintf("Invalid parameter given to %s.", __METHOD__));
        }
        list($interval, $timeout) = array(abs(round($interval)), abs(round($timeout)));

        $elapsed = 0;
        $unavailable = true;
        do
        {
            if (($timeout > 0) && ($elapsed > $timeout))
            {
                throw new \Exception(sprintf("Unable to take a lock after %d seconds", $timeout));
            }


            // Attention, ici accès concurrentiel: deux sémaphores peuvent avoir le même identifiant!
            $sem = $this->_shared->{$this->_key};


            if (is_null($sem))
            {
                $sem = 0;
            }

            if ($sem < $this->_size)
            {
                $sem++;
                $this->_shared->{$this->_key} = $sem;
                $unavailable = false;
            }
            else
            {
                usleep($interval);
            }
        }
        while ($unavailable);
        return $sem;
    }

    public function release()
    {
        $sem = $this->_shared->{$this->_key};
    }

    public function resize($size)
    {
        if ((!is_numeric($size)) || ($size < 0))
        {
            throw new \Exception("Inavalid parameter given: mutex ")
        }
        $this->_size = $size;
        return $this;
    }

    public function changeKey($key)
    {
        if (!is_string($key))
        {
            throw new \Exception("Invalid parameter given: key must be a string.");
        }
        if (!preg_match("/[a-zA-Z_][a-zA-Z0-9_]*/", $key))
        {
            throw new \Exception("Invalid key '%s' given: key should only contain alphanumeric and underscore characters.");
        }
        $this->_key = $key;
        return $this;
    }

    public function destroy()
    {

    }

}