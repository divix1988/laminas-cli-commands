<?php

namespace %module_name%\Utils;

class Authentication extends \Laminas\Authentication\AuthenticationService {
    
    protected $adapter;
    protected $dbAdapter;
    protected $authAdapter;


    public function __construct($dbAdapter, $authAdapter) {
        $this->dbAdapter = $dbAdapter;
        $this->adapter = new Adapter(
            $this->dbAdapter,
            'users',
            'email',
            'password',
            'SHA2(CONCAT(password_salt, "'.Helper::SALT_KEY.'", ?), 512)'
        );
        $this->authAdapter = $authAdapter;
    }

    public function auth($email, $password) {
        if (empty($email) || empty($password)) {
            return false;
        }
        $this->adapter->setIdentity($email);
        $this->adapter->setCredential($password);
        $result = $this->adapter->authenticate();
        $this->authenticate($this->adapter);
        
        return $result;
    }

    public function getIdentity() {
        return $this->getAdapter()->getResultRowObject();
    }

    public function getIdentityArray()
    {
        return json_decode(json_encode($this->adapter->getResultRowObject()), true);
    }

    public function getAdapter() {
        return $this->adapter;
    }
}
