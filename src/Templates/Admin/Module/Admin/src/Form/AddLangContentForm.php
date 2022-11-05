<?php
namespace Admin\Form;

use Laminas\Form\Element;

class AddLangContentForm extends \Laminas\Form\Form implements \Laminas\InputFilter\InputFilterProviderInterface
{
   
    protected $contentID;
    
    const ELEMENT_CONTENT = 'content';
    const ELEMENT_LANGUAGE = 'language';
   
    public function __construct($id, $langOptions) {
        $this->contentID = $id;
        parent::__construct('add_lang_content_form');

        $this->setAttribute('action', '../addlangcontent/'.$this->contentID);
        $this->setAttribute('class', 'styledForm');

        $this->add([
            'name' => self::ELEMENT_CONTENT,
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Contents'
            ],
            'attributes' => [
                'required' => true,
                'class' => 'ckeditor',
                'style' => 'width:100%'
            ],
        ]);

        foreach($langOptions as $lang) {
           $dropDownElements[$lang['id']] = $lang['name'];
        }

        $this->add([
            'name' => self::ELEMENT_LANGUAGE,
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Language',
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

        $this->completeMsg = 'form.addLangContent.success';
    }
   
    public function getInputFilterSpecification()
    {
        return [
            [
                'name' => self::ELEMENT_CONTENT,
                'filters' => [
                    ['name' => \Laminas\Filter\StringTrim::class]
                ]
            ],
            [
                'name' => self::ELEMENT_LANGUAGE,
                'filters' => [
                    ['name' => \DivixUtils\Laminas\Filter\FriendlyUrl::class],
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\NotEmpty::class,
                    ]
                ]
            ]
        ];
    }

}