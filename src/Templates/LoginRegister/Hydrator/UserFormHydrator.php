<?php

namespace %module_name%\Hydrator;

use %module_name%\Form;

class UserFormHydrator implements \Laminas\Hydrator\Strategy\StrategyInterface
{
    protected $securityHelper;

    public function __construct($securityHelper)
    {
        $this->securityHelper = $securityHelper;
    }

    public function hydrate($form, $extraData = null)
    {
        if (!$form instanceof \Application\Form\UserRegisterForm) {
            throw new \Exception('invalid form object passed to the'.__CLASS__);
        }
        $data = $form->getData();
        $hashedPassword = $this->securityHelper->sha512($data[$form::FIELDSET_LOGIN][Form\UserLoginFieldset::ELEMENT_PASSWORD]);
        
        return [
            'username' => $data[$form::FIELDSET_USERNAME][Form\UsernameFieldset::ELEMENT_USERNAME],
            'email' => $data[$form::FIELDSET_LOGIN][Form\UserLoginFieldset::ELEMENT_EMAIL],
            'password' => $hashedPassword['hash'],
            'password_salt' => $hashedPassword['salt']
        ];
    }

    public function extract($array, $object = null)
    {
        return $array;
    }
}

