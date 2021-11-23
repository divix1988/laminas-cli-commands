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
        $hashedPassword = $this->securityHelper->sha512($data[Form\UserRegisterForm::ELEMENT_PASSWORD]);
        
        return [
            'email' => $data[Form\UserRegisterForm::ELEMENT_EMAIL],
            'password' => $hashedPassword['hash'],
            'password_salt' => $hashedPassword['salt'],
%hydrate_fileds%
        ];
    }

    public function extract($array, $object = null)
    {
        return $array;
    }
}

