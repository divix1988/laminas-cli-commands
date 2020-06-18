<?php
namespace %module_name%\Form;

use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Element;

class UsernameFieldset extends Fieldset implements InputFilterProviderInterface
 {
    const ELEMENT_USERNAME = 'username';
    
    public function __construct()
    {
        parent::__construct('user_username');
         
        $this->add([
            'name' => self::ELEMENT_USERNAME,
            'type' => 'text',
            'options' => [
                'label' => 'Username'
            ],
            'attributes' => [
                'required' => true
            ]
        ]);
    }

    public function getInputFilterSpecification()
    {
        return array(
            [
                'name' => self::ELEMENT_USERNAME,
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
        );
    }
 }