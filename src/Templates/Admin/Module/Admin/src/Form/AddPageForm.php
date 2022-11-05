<?php

namespace Admin\Form;

use Laminas\Form\Element;

class AddPageForm extends \Laminas\Form\Form implements \Laminas\InputFilter\InputFilterProviderInterface
{
    const ELEMENT_NAME = 'name';
    const ELEMENT_URL = 'url';
    const ELEMENT_PARENT_ID = 'parent_id';
    const ELEMENT_SUBMIT = 'submit';

    public function __construct($pages = array(), $pageDetails = array())
    {
        parent::__construct('add_page_form');
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
            'name' => self::ELEMENT_URL,
            'type' => Element\Text::class,
            'options' => [
                'label' => 'URL Address',
            ],
            'attributes' => [
                'required' => true
            ],
        ]);

        $dropDownElements = ['Root'];

        foreach($pages as $page) {
            if (!empty($pageDetails) && $page['name'] == $pageDetails['name']) {
                continue;
            }
            $dropDownElements[$page['id']] = $page['name'];
        }

        $this->add([
            'name' => self::ELEMENT_PARENT_ID,
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Site Parent',
                'value_options' => $dropDownElements
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

        if (!empty($pageDetails)) {
            $this->setAttribute('id', 'editPage');
            //fill fileds with the passed in data
            $this->get(self::ELEMENT_NAME)->setValue($pageDetails['name']);
            $this->get(self::ELEMENT_URL)->setValue($pageDetails['url']);
            $this->get(self::ELEMENT_PARENT_ID)->setValue($pageDetails['parent_id']);
            $this->get(self::ELEMENT_SUBMIT)->setValue('Edytuj');
        }
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
                            'min' => 5,
                            'messages' => [
                                \Laminas\Validator\StringLength::TOO_SHORT => 'The minimum length is: %min%'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => self::ELEMENT_URL,
                'filters' => [
                    ['name' => \Utils\Laminas\Filter\FriendlyUrl::class],
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 3,
                            'messages' => [
                                \Laminas\Validator\StringLength::TOO_SHORT => 'The minimum length is: %min%'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
