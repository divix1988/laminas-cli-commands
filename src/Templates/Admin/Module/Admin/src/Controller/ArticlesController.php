<?php

namespace Admin\Controller;

use Admin\Form;

class ArticlesController extends AbstractController
{
    public function indexAction()
    {
        return [
            'pages' => $this->cmsObject->getPages()
        ];
    }

    public function previewAction()
    {
        $pageDetails = $this->cmsObject->getArtcileContentByUrl($this->params()->fromRoute('id'));

        if (!empty($pageDetails)) {
            $this->getEvent()->getTarget()->layout()->title = $pageDetails[0]['title'];
            $this->getEvent()->getTarget()->layout()->description = $pageDetails[0]['description'];
            $this->getEvent()->getTarget()->layout()->keywords = $pageDetails[0]['keywords'];
        }
        
        return [
            'page' => $pageDetails,
            'title' => $pageDetails[0]['title']
        ];
    }

    public function seecontentsAction()
    {
        $id = $this->params()->fromRoute('id');
        
        return [
            'title' => $this->cmsObject->getPage($id)['name'],
            'contents' => $this->cmsObject->getAllContentsByPageID($id),
            'pageID' => $id,
            'availableContents' => $this->cmsObject->getContents()
        ];
    }
    
    public function addpageAction()
    {
	$form = new Form\AddPageForm($this->cmsObject->getPages());
	$viewParams = ['addPageForm' => $form];
	
	if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());

            if ($form->isValid()) {
                $added = $this->cmsObject->addPage($form->get('name')->getValue(), $form->get('url')->getValue(), $form->get('parent_id')->getValue());
                
                if ($added) {
                    $this->flashMessenger()->addSuccessMessage('Page has been added.');
                } else {
                    $this->flashMessenger()->addWarningMessage('Page already exists.');
                }
            } else {
                $this->flashMessenger()->addErrorMessage('Incorrectly completed form.');
            }
	}
	return $viewParams;
    }

    public function editpageAction()
    {
	$pageId = $this->params()->fromRoute('id');
	$form = new Form\AddPageDetailsForm($pageId, $this->cmsObject->getLanguageList());
	$pageForm = new Form\AddPageForm($this->cmsObject->getPages(), $this->cmsObject->getPageByID($pageId));
	$viewParams = [
            'addPageDetails' => $form,
            'editPageForm' => $pageForm
	];

	if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('url')) {
                $pageForm->setData($this->getRequest()->getPost());

                if ($pageForm->isValid()) {
                    $this->cmsObject->updatePage(
                        $pageId, 
                        $pageForm->get('name')->getValue(), 
                        $pageForm->get('url')->getValue(), 
                        $pageForm->get('parent_id')->getValue()
                    );
                    $this->flashMessenger()->addSuccessMessage($pageForm->completeMessage);
                } else{
                    $this->flashMessenger()->addErrorMessage('Incorrectly completed form.');
                }
            } elseif ($this->params()->fromPost('title')) {
                $form->setData($this->getRequest()->getPost());

                if ($form->isValid()) {
                    $this->cmsObject->addMetadata(
                        $pageId,
                        $form->get('language')->getValue(),
                        $form->get('title')->getValue(),
                        $form->get('description')->getValue(),
                        $form->get('keywords')->getValue()
                    );
                    $form->completeMsg;
                } else{
                    'form.errors';
                }
            }
	}
	$viewParams['pageMetadata'] = $this->cmsObject->getAllPageDetails($pageId);
	$viewParams['pageId'] = $pageId;

	return $viewParams;
    }

    public function deleteAction()
    {
	$pageId = $this->params()->fromRoute('id');
	$this->cmsObject->deletePage($pageId);

        return $this->redirect()->toRoute('admin/articles');
    }
    
    /**
    * Shows all the page contents.
    *
    * @return void
    */
    public function showcontentsAction() {
        return ['contents' => $this->cmsObject->getContents()];
    }
   
   /**
    * Shows the add form for the page content.
    *
    * @return void
    */
    public function addcontentAction() {
        $form = new Form\AddContentForm();

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            
            if ($form->isValid()) {
                if ($this->cmsObject->addContent($form->get($form::ELEMENT_NAME)->getValue())) {
                    $this->flashMessenger()->addSuccessMessage($form->completeMessage);
                } else {
                    $this->flashMessenger()->addWarningMessage('Page content already exists.');
                }
            } else {
                $this->flashMessenger()->addErrorMessage('Incorrectly completed form.');
            } 
        }
        
        return ['addContentForm' => $form];
    }
   
   /**
    * Shows the edit form for the page content.
    *
    * @return void
    */
    public function editcontentAction() {
        $contentId = $this->params()->fromRoute('id');
        $form = new Form\AddLangContentForm($contentId, $this->cmsObject->getLanguageList());

        return [
            'addLangContentForm' => $form,
            'contentLanguages' => $this->cmsObject->getContentLanguages($contentId),
            'contentId' => $contentId
        ];
    }
   
   /**
    * Shows the add form for the language content.
    *
    * @return void
    */
    public function addlangcontentAction() {
        $contentID = $this->params()->fromRoute('id');
        $form = new Form\AddLangContentForm($contentID, $this->cmsObject->getLanguageList());

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            
            if ($form->isValid()) {
                $this->cmsObject->addLangContent(stripcslashes($form->get('language')->getValue()), $form->get('content')->getValue(), $contentID);
                $this->flashMessenger()->addSuccessMessage($form->completeMsg, true);

                //come back to edit page
                $this->redirect()->toRoute('admin/articles', ['action' => 'editcontent', 'id' => $contentID]);
            } else {
                $this->flashMessenger()->addErrorMessage('form.errors');
            } 
        }
        
        return ['addLangContentForm' => $form];
    }
    
    public function deletecontentAction() {
      $id = $this->params()->fromRoute('id');
         
      $this->cmsObject->deleteContent($id);  
      
      return $this->redirect()->toRoute('admin/articles', ['action' => 'showcontents']);
    }
   
   /**
    * Shows the edit form for the language content.
    *
    * @return void
    */
    public function updatelangcontentAction() {
        $contentID = $this->params()->fromRoute('id');
        $langContents = $this->cmsObject->getContentLanguages($contentID);
        $id = 0;

        foreach($langContents as $entry) {
           $id = $this->params()->fromPost('langID_'.$entry['lang']);

           $contents = $this->params()->fromPost('contents_'.$entry['lang']);
           $this->cmsObject->updateLangContent($id, $contents);
        }

        //come back to edit page
        $this->redirect()->toRoute('admin/articles', ['action' => 'editcontent', 'id' => $contentID]);
    }
   
   /**
    * Shows the view with all page contents assigned to the page.
    *
    * @return void
    */
   public function assigncontentAction() {
      $pageID = $this->params()->fromRoute('id');
      $contentID = $this->request->getPost('selectedContentID');
      
      $this->cmsObject->assignContentToPage($contentID, $pageID);
      //come back to see contents page
      $this->redirect()->toRoute('admin/articles', ['action' => 'seecontents', 'id' => $pageID]);
   }
   
   /**
    * Shows the edit form for the page metadata.
    *
    * @return void
    */
   public function editpagemetadataAction() {
      $pageID = $this->params()->fromRoute('id');
      $langContents = $this->cmsObject->getAllPageDetails($pageID);
      
      foreach($langContents as $entry) {
         $id = $this->params()->fromPost('metadataID_'.$entry['lang']);
         if (isset($id)) {
            $title = $this->params()->fromPost('title_'.$entry['lang']);
            $description = $this->params()->fromPost('description_'.$entry['lang']);
            $keywords = $this->params()->fromPost('keywords_'.$entry['lang']);
            $this->cmsObject->updateMetadata($id, $title, $description, $keywords);
         }
      }
      
      //come back to edit page
      $this->redirect()->toRoute('admin/articles', ['action' => 'editpage', 'id' => $pageID]);
   }
   
   /**
    * Unlink the association of page content from the page.
    *
    * @return void
    */
   public function unlinkcontentAction() {
      $pageID = $this->params()->fromRoute('id');
      $contentID = $this->params()->fromRoute('content_id');
         
      $this->cmsObject->unlinkContent($pageID, $contentID);  
      
      //come back to edit page
      $this->redirect()->toRoute('admin/articles', ['action' => 'seecontents', 'id' => $pageID]);
   }
   
    public function deleteallAction() {
        if (!$this->isGranted('deleteAll')) {
            //exit('unauthorized');
            throw new \LmcRbacMvc\Exception\UnauthorizedException('Unauthorized. An access for superadmins only.');
        }
        exit('congrats, you have access');
        //print_r($this->isGranted('deleteall'));
        //some our logic
        return true;
    }
}
