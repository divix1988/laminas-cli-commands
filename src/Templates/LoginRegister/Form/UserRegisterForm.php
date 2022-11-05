<?php

namespace %module_name%\Form;

use Laminas\Form\Element;

class UserRegisterForm extends \Laminas\Form\Form implements \Laminas\InputFilter\InputFilterProviderInterface
{
    const TIMEOUT = 300;
    const ELEMENT_PASSWORD_CONFIRM = 'confirm_password';
    const ELEMENT_CSRF = 'users_csrf';
    const ELEMENT_CAPTCHA = 'captcha';
    
%constants%

    public function __construct($name = 'register_user', $params)
    {
        parent::__construct($name, $params);
        $this->setAttribute('class', 'styledForm');
        
%properties%

        $this->add([
            'name' => self::ELEMENT_CSRF,
            'type' => Element\Csrf::class,
            'options' => [
                'salt' => 'unique',
                'timeout' => self::TIMEOUT
            ],
            'attributes' => [
                'id' => self::ELEMENT_CSRF
            ]
        ]);

        /*$this->add([
            'name' => self::ELEMENT_CAPTCHA,
            'type' => Element\Captcha::class,
            'options' => [
                'label' => 'Rewrite Captcha text:',
                'captcha' => new \Laminas\Captcha\Image([
                    'name' => 'myCaptcha',
                    'messages' => array(
                        'badCaptcha' => 'incorrectly rewritten image text'
                    ),
                    'wordLen' => 5,
                    'timeout' => self::TIMEOUT,
                    'font' => APPLICATION_PATH.'/public/fonts/arbli.ttf',
                    'imgDir' => APPLICATION_PATH.'/public/img/captcha/',
                    'imgUrl' => $this->getOption('baseUrl').'/public/img/captcha/',
                    'lineNoiseLevel' => 4,
                    'width' => 200,
                    'height' => 70
                ]),
            ]
        ]);*/

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Register',
                'class' => 'btn btn-primary'
            ]
        ]);

        $this->setAttribute('method', 'POST');
    }

    public function getInputFilterSpecification()
    {
        return [
            [
                'name' => self::ELEMENT_PASSWORD_CONFIRM,
                'filters' => [
                    ['name' => \Laminas\Filter\StringTrim::class]
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\Identical::class,
                        'options' => [
                            'token' => ['password'],
                            'messages' => [
                                \Laminas\Validator\Identical::NOT_SAME => 'Passwords are not the same'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}

