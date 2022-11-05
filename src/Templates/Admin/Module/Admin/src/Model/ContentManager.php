<?php
namespace Admin\Model;

use Laminas\Db\Sql\Sql;

class ContentManager
{
    
    public $metadataTableName = 'page_metadata';
    public $pagesTableName = 'pages';
    public $contentTableName = 'page_contents';
    public $contentPageTableName = 'page_to_contents';
    public $langPageTableName = 'page_lang_contents';
    
    protected $db;
    protected $adapter;
    protected $lang;

    public function __construct($adapter) {
        $this->db = new Sql($adapter);
        $this->adapter = $adapter;
        $this->lang = 'en';
    }
    
    /** 
    * Returns a list of all pages.
    *
    * @return array
    */
    public function getPages(){
        $select = $this->db->select()
            ->from('pages');

        return $this->executeSql($select);
    }
    
    /** 
     * Returns a list of all standalone pages.
     *
     * @return array
     */
    public function getStandalonePages(){
        $select = $this->db->select()
            ->from(['p' => 'pages'])
            ->join(['m' => 'page_metadata'],
                'm.page_id = p.id');

        return $this->executeSql($select);
    }

    public function getPage($id){
        $select = $this->db->select()
            ->from('pages')
            ->where(['id' => $id]);

        return $this->executeSql($select)->current();
    }

    /** 
    * Adds new page.
    *
    * @param string $name page name
    * @param string $url page URL address
    * @param int $parentID an ID of parent page
    *
    * @return boolean
    */
    public function addPage($name, $url, $parentID){
        if (!$this->isPageExist($name)) {
            $data = array(
                'name' => $name,
                'url' => $url, //move filter into form
                'parent_id' => $parentID
            );

            $insert = $this->db->insert()
                ->into('pages')
                ->values($data);

            return $this->executeSql($insert);
        } else {
            return false;
        }
    }

    public function updatePage($pageId, $name, $url, $parentId){
        $data = array(
            'name' => $name,
            'url' => $url,
            'parent_id' => $parentId
        );
        $update = $this->db->update()
            ->table('pages')
            ->set($data)
            ->where(['id' => $pageId]);

        return $this->executeSql($update);
    }

    public function deletePage ($pageId)
    {
        $delete = $this->db->delete()
            ->from('pages')
            ->where(['id' => $pageId]);

        return $this->executeSql($delete);
    }

    /**
     * Adds new metadata for the given page ID.
     * 
     * @param int $pageID page ID
     * @param string $lang language code
     * @param string $title title
     * @param string $description description
     * @param string $keywords set of keywords separated by comas and spaces
     * 
     * @return boolean
    */
    public function addMetadata($pageID, $lang, $title, $description, $keywords){
        $data = array(
            'page_id' => $pageID,
            'lang' => $lang,
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords
        );
        
        $insert = $this->db->insert()
            ->into($this->metadataTableName)
            ->values($data);

        return $this->executeSql($insert);
    }
    
    /**
     * Updates metadata for the given metadata record ID.
     * 
     * @param int $metadataID metadata ID
     * @param string $title title
     * @param string $description description
     * @param string $keywords set of keywords separated by comas and spaces
     * 
     * @return boolean
    */
    public function updateMetadata($metadataID, $title, $description, $keywords){
        $data = array(
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords
        );
        
        $update = $this->db->update()
            ->table('page_metadata')
            ->set($data)
            ->where(['id' => $metadataID]);
        
        return $this->executeSql($update);
    }
    
    /** 
    * Adds new page content.
    *
    * @param string $name content name
    *
    * @return boolean
    */
    public function addContent($name){
        if (!$this->isContentExist($name)) {
            $data = array(
                'name' => $name
            );
            $insert = $this->db->insert()
                ->into($this->contentTableName)
                ->values($data);
            
            return $this->executeSql($insert);
        } else {
            return false;
        }
    }
    
    public function deleteContent ($contentId)
    {
        $delete = $this->db->delete()
            ->from($this->contentTableName)
            ->where(['id' => $contentId]);
        
        return $this->executeSql($delete);
    }
    
    /** 
    * Adds new language version of page content.
    *
    * @param string $lang language code
    * @param string $content content
    * @param int $pageContentID an ID of page content
    *
    * @return boolean
    */
    public function addLangContent($lang, $content, $pageContentID){
        if (!$this->isContentExist($lang)){
            $data = array(
                'lang' => $lang,
                'content' => $content,
                'page_content_id' => $pageContentID
            );
            
            $insert = $this->db->insert()
                ->into($this->langPageTableName)
                ->values($data);
            
            return $this->executeSql($insert);
        } else {
            return false;
        }
    }
    
    /** 
    * Updates language version of page content.
    *
    * @param int $langContentID language page content ID
    * @param string $content contents
    *
    * @return boolean
    */
    public function updateLangContent($langContentID, $content){
        $data = array(
            'content' => $content
        );

        $update = $this->db->update()
            ->table($this->langPageTableName)
            ->set($data)
            ->where(['id' => $langContentID]);
        
        return $this->executeSql($update);
    }
    
    
    /** 
    * Returns a collection of all page contents.
    *
    * @return array
    */
    public function getContents(){
        $select = $this->db->select()
            ->from('page_contents');

        return $this->executeSql($select);
    }
    
    /** 
    * Assigns page content to the particular page.
    *
    * @param int $contentID page content ID
    * @param int $pageID page ID
    *
    * @return boolean
    */
    public function assignContentToPage($contentID, $pageID){
        $data = array(
            'content_id' => $contentID,
            'page_id' => $pageID
        );
        $insert = $this->db->insert()
            ->into($this->contentPageTableName)
            ->values($data);

        return $this->executeSql($insert);
    }
    
    /**
     * Breaks the association between the given page and page content IDs.
     * 
     * @param int $pageID page ID
     * @param int $contentID page content ID
     * 
     * @return boolean
    */
    public function unlinkContent($pageID, $contentID){
        $delete = $this->db->delete()
            ->from('page_to_contents')
            ->where(['page_id' => $pageID, 'content_id' => $contentID]);
        
        return $this->executeSql($delete);	
    }
    
    /**
     * Checkes if page exists
     * 
     * @param string $permission permission to check
     * 
     * @return boolean
    */
    public function isPageExist($name){
        $sql = "SELECT COUNT(DISTINCT id) AS count FROM ".$this->pagesTableName." WHERE name=? LIMIT 1";
        $result = $this->adapter->query($sql, array($name));

        return $result->current()->count > 0;
    }
    
    /**
     * Checkes if page content exists.
     * 
     * @param string $name page content name
     * 
     * @return boolean
    */
    public function isContentExist($name){
        $sql = "SELECT COUNT(DISTINCT id) AS count FROM ".$this->contentTableName." WHERE name=? LIMIT 1";
        $result = $this->adapter->query($sql, array($name));
        
        return $result->current()->count > 0;
    }
    
    /**
     * Returns a collection of content language versions.
     * 
     * @param int $contentID content ID
     * 
     * @return array
    */
    public function getContentLanguages($contentID) {
        $select = $this->db->select()
            ->from($this->langPageTableName)
            ->where(['page_content_id' => $contentID]);

        $result = $this->executeSql($select);
        $newResult = array();
        $counter = 0;

        foreach ($result as $entry) {
            foreach ($entry as $key => $single)  {
                $newResult[$counter][$key] = stripslashes($entry[$key]);
            }
            $counter++;
        }

        return $newResult;
    }
    
    /**
     * Returns a list of page details.
     * 
     * @param int $pageID page ID
     * 
     * @return array
    */
    public function getAllPageDetails($pageID) {
        $select = $this->db->select()
            ->from($this->metadataTableName)
            ->where(['page_id' => $pageID]);
        
        return iterator_to_array($this->executeSql($select));
    }
    
    public function getPageByID($pageID) {
        $select = $this->db->select()
            ->from($this->pagesTableName)
            ->where(['id' => $pageID]);

        return $this->executeSql($select)->current();
    }
    
    public static function getLanguageList() {
        $list = array();
        $counter = 0;
        
        $list[$counter]['id'] = 'pl';
        $list[$counter]['name'] = 'Polish';
        $counter++;
        $list[$counter]['id'] = 'en';
        $list[$counter]['name'] = 'English';
        $counter++;
        
        return $list;
    }
    
    /**
     * Returns all contents for given page ID.
     * 
     * @param int $pageID page ID
     * 
     * @return boolean
    */
    public function getAllContentsByPageID($pageID){
        $condition = 'p2c.page_id';
        $sqlParameter = $pageID;

        return $this->retrieveAllPageContents($condition, $sqlParameter);
    }
    
    /**
     * Returns list of contents for the given page name.
     * 
     * @param string $pageName page name
     * 
     * @return array
    */
    public function getStaticContentByPageName($pageName) {
        $condition = 'p.name';
        $sqlParameter = $pageName;

        return $this->retrieveLangPageContents($condition, $sqlParameter);
    }
    
    /**
     * Returns list of contents for the given article name.
     * 
     * @param string $pageName article name
     * 
     * @return array
    */
    public function getArtcileContentByPageName($pageName) {
        $condition = 'p.name';
        $sqlParameter = $pageName;

        return $this->retrieveArticlePageContents($condition, $sqlParameter);
    }
    
    /**
     * Returns article matching given URL.
     * 
     * @param string $url URL address
     * 
     * @return array
    */
    public function getArticleContentByUrl($url) {
        $condition = 'p.url';
        $sqlParameter = $url;

        return $this->retrieveArticlePageContents($condition, $sqlParameter);
    }
    
    private function retrieveLangPageContents($condition, $parameter) {
        $select = $this->db->select()
            ->from(array('p2c' => 'page_to_contents'),
               array('content_id', 'page_id'))
            ->join(array('p' => 'pages'),
            'p.id = p2c.page_id')
            ->join(array('c' => 'page_contents'),
            'c.id = p2c.content_id')
            ->join(array('l' => $this->langPageTableName),
            'l.page_content_id = c.id')
            ->where([$condition => $parameter])
            ->where(['l.lang' => $this->lang]);

        $result = $this->executeSql($select);
        $resultNew = array();

        foreach ($result as $counter => $entry) {
            foreach ($entry as $key => $single) {
                $resultNew[$counter][$key] = stripslashes($single);
            }
        }

        return $resultNew;
    }
    
    private function retrieveArticlePageContents($condition, $parameter) {
        $select = $this->db->select()
            ->from(array('p2c' => 'page_to_contents'),
               array('content_id', 'page_id'))
            ->join(array('p' => 'pages'),
            'p.id = p2c.page_id')
            ->join(array('c' => 'page_contents'),
            'c.id = p2c.content_id')
            ->join(array('l' => $this->langPageTableName),
            'l.page_content_id = c.id')
            ->join(array('m' => 'page_metadata'),
            'm.page_id = p2c.page_id')
            ->where([$condition => $parameter])
            ->where(['l.lang' => $this->lang])
            ->where(['m.lang' => $this->lang]);
        
        $result = $this->executeSql($select);
        $resultNew = array();
        
        foreach ($result as $counter =>$entry) {
            foreach ($entry as $key => $single) {
                $resultNew[$counter][$key] = stripslashes($single);
            }
            $counter++;
        }

        return $resultNew;
	
    }
    
    private function retrieveAllPageContents($condition, $parameter) {
        $select = $this->db->select()
            ->from(array('p2c' => 'page_to_contents'))
            ->columns(array('content_id', 'page_id'))
            ->join(array('p' => 'pages'), 'p.id = p2c.page_id')
            ->join(array('c' => 'page_contents'), 'c.id = p2c.content_id')
            ->where([$condition => $parameter]);

        $result = iterator_to_array($this->executeSql($select));

        foreach ($result as $index => $row) {
            $result[$index]['langs'] = $this->getContentLanguages($result[$index]['content_id']);
        }
        
        return $result;
    }
    
    private function executeSql($sql)
    {
        $statement = $this->db->prepareStatementForSqlObject($sql);
        return $statement->execute();
    }
	
}
