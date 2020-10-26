<?php

namespace %module_name%\Form;

use Laminas\Form\Element;

class UserRegisterForm extends \Laminas\Form\Form implements \Laminas\InputFilter\InputFilterProviderInterface
{
    const TIMEOUT = 300;
    const ELEMENT_PASSWORD_CONFIRM = 'confirm_password';
    const ELEMENT_CAPTCHA = 'captcha';
    const FIELDSET_USERNAME = 'user_username';
    const FIELDSET_LOGIN = 'user_login';
    
%constants%

    public function __construct($name = 'register_user', $params)
    {
        parent::__construct($name, $params);
        $this->setAttribute('class', 'styledForm');
        $this->add([
            'type' => UsernameFieldset::class,
            'name' => self::FIELDSET_USERNAME
        ]);

        $this->add([
            'type' => UserLoginFieldset::class,
            'name' => self::FIELDSET_LOGIN,
            'options' => $params
        ]);

        $this->add([
            'name' => self::ELEMENT_PASSWORD_CONFIRM,
            'type' => Element\Password::class,
            'options' => [
                'label' => 'Repeat password',
            ],
            'attributes' => [
                'required' => true
            ],
        ]);
        
%properties%

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
                            'token' => ['user_login' => 'password'],
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

