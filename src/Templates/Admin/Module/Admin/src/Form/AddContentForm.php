<?php

namespace Admin\Form;

use Laminas\Form\Element;

class AddContentForm extends \Laminas\Form\Form implements \Laminas\InputFilter\InputFilterProviderInterface
{    
    const ELEMENT_NAME = 'name';
   
    public function __construct() {
        parent::__construct('add_content');
        $this->setAttribute('class', 'styledForm');

        $this->add([
            'name' => self::ELEMENT_NAME,
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Name',
            ],
            'attributes' => [
                'required' => true
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Add',
                'class' => 'btn btn-primary'
            ]
        ]);

        $this->completeMessage = 'form.addContent.success';
    }
    
    public function getInputFilterSpecification()
    {
        return [
            [
                'name' => self::ELEMENT_NAME,
                'filters' => [
                    ['name' => \Laminas\Filter\StringTrim::class]
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 2,
                            'messages' => [
                                \Laminas\Validator\StringLength::TOO_SHORT => 'The minimum length is: %min%'
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

}