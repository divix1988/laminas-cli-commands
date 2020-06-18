<?php
namespace %module_name%\Form;

use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Element;

class UserLoginFieldset extends Fieldset implements InputFilterProviderInterface
 {
    const TIMEOUT = 300;
    const ELEMENT_EMAIL = 'email';
    const ELEMENT_PASSWORD = 'password';
    const ELEMENT_CSRF = 'users_csrf';
    
    public function __construct()
    {
        parent::__construct('user_login');
         
        $this->add([
            'type' => Element\Email::class,
            'name' => self::ELEMENT_EMAIL,
            'attributes' => [
                'required' => true,
            ],
            'options' => [
                'label' => 'Email'
            ]
        ]);
        
        $this->add([
            'name' => self::ELEMENT_PASSWORD,
            'type' => Element\Password::class,
            'options' => [
                'label' => 'Password',
            ],
            'attributes' => [
                'required' => true
            ],
        ]);
        
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
    }

    public function getInputFilterSpecification()
    {      
        $validators = [
            [
                'name' => self::ELEMENT_EMAIL,
                'filters' => [
                    ['name' => \Laminas\Filter\StringTrim::class]
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 5, 
                            'messages' => [
                                \Laminas\Validator\StringLength::TOO_SHORT => 'The minimum length is: %min%'
                            ]
                        ]
                    ],
                    [
                        'name' => 'EmailAddress',
                        'options' => array( 
                            'messages' => array(
                                \Laminas\Validator\EmailAddress::INVALID_FORMAT => 'validator.email.format',
                                \Laminas\Validator\EmailAddress::INVALID => 'validator.email.general',
                                \Laminas\Validator\EmailAddress::INVALID_HOSTNAME => 'validator.email.hostname',
                                \Laminas\Validator\EmailAddress::INVALID_LOCAL_PART => 'validator.email.local',
                                \Laminas\Validator\Hostname::UNKNOWN_TLD => 'validator.email.unknown_domain',
                                \Laminas\Validator\Hostname::LOCAL_NAME_NOT_ALLOWED => 'validator.email.name_not_allowed'
                            )
                        )
                    ]
                ]
            ],
            [
                'name' => self::ELEMENT_PASSWORD,
                'required' => true,
                'filters' => [
                    ['name' => \Laminas\Filter\StringTrim::class]
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 5,
                            'messages' => [
                                \Laminas\Validator\StringLength::TOO_SHORT => 'The minimum length is: %min%'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        //let's add extra DB validator to the register form, bypassing login form
        if (!empty($this->getOption('dbAdapter'))) {
            $validators[0]['validators'][] = [
                'name' => \Laminas\Validator\Db\NoRecordExists::class,
                'options' => array(
                    'adapter' => $this->getOption('dbAdapter'),
                    'table' => 'users',
                    'field' => 'email',
                    'messages' => array(
                        \Laminas\Validator\Db\NoRecordExists::ERROR_RECORD_FOUND => 'Provided email address already exists in database'
                    )
                )
            ];
        }
        
        return $validators;
    }
 }