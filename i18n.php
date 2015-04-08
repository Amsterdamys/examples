<?php
class I18n{
    const APP_SRC = '/i18n';
    private $DB = null;
    private $_m = 'i18n';
    private $_languages = array();
    private $_images = array();
    private $tpl = null;
    private static $_instance = null;
    private static $_data = null;
    private static $_tbl_name = '';
    private static $_module_id = 0;
    private static $_id = 0;
    
    private function __construct(){
        global $DB;
        $this->DB         = $DB;
        $this->tpl        = new I18n_Smarty();
        $this->_languages = $this->getLanguages();
        $this->setCurrentLang();
        $this->setCurrentUserLang();
    }
    public static function getInstance($data = null, $tbl_name = '', $module_id = 0, $id = 0){
        self::$_data      = $data;
        self::$_tbl_name  = $tbl_name;
        self::$_module_id = $module_id;
        self::$_id        = $id;
        
        if(self::$_instance == null){
            return $_instance = new I18n();
        }
        return self::$_instance;
    }
    public function getLanguages(){
        return array('ru'=>array('title'=>'Rus', 'img'=>'ru.png'), 
                     'en'=>array('title'=>'Eng', 'img'=>'en.png'), 
                     'fr'=>array('title'=>'Fr' , 'img'=>'fr.png'), 
                     'ee'=>array('title'=>'Est', 'img'=>'ee.png'),
                    );
    }
    // Запись языка в сессию (админка)
    private function setCurrentLang(){
        if($this->checkVarLang() !== false && isset($this->_languages[$this->checkVarLang()])){
            $_SESSION['admin_lang'] = $this->checkVarLang();
            header("Location: ".updCURL(array('admin_lang'=>null)));
        }
        if(!isset($_SESSION['admin_lang'])){
            $_SESSION['admin_lang'] = 'ru';
        }
        return false;
    }
    // Запись языка в сессию (пользовательская часть)
    private function setCurrentUserLang(){
        if($this->checkVarUserLang() !== false && isset($this->_languages[$this->checkVarUserLang()])){
            $_SESSION['lang'] = $this->checkVarUserLang();
            header("Location: ".updCURL(array('lang'=>null)));
        }
        if(!isset($_SESSION['lang'])){
            $_SESSION['lang'] = 'ru';
        }
    }
    // Проверка существования GET-переменной с языком (админка)
    private function checkVarLang(){
        if(isset($_GET['admin_lang']) && $_GET['admin_lang'] != ''){
            return $_GET['admin_lang'];
        }
        return false;
    }
    // Проверка существования GET-переменной с языком (пользовательская часть)
    private function checkVarUserLang(){
        if(isset($_GET['lang']) && $_GET['lang'] != ''){
            return $_GET['lang'];
        }
        return false;
    }
    // Перевод одного элемента при редкатрировании
    public function translate($data = ''){
        if($this->getCurrentSession() == 'ru') return self::$_data;
        
        $fields = $this->getFields();
        $data = ($data == '')?(self::$_data):($data);

        foreach($fields as $v){
            $fields_value = $this->DB->execute('SELECT content FROM '.$this->_m.' WHERE id_element='.$data['id'].' AND id_field='.$v['id'].' AND language="'.$this->getCurrentSession().'"')->fetch_assoc();
            
            if(count($fields_value) == 0){
                $this->insertData($data['id'], $v['id']);
            }
            
            if(isset($data[$v['title_orig']])){
                $data[$v['title_orig']] = count($fields_value) == 0?'':$fields_value['content'];
            }
        }
        return $data;
    }
    // Перевод всех элементов
    public function translateAll(){
        if($this->getCurrentSession() == 'ru') return self::$_data;

        foreach(self::$_data as &$v){
            $v = $this->translate($v);
        }
        return self::$_data;
    }
    // Добавление/изменение элементов
    public function insert(){
        if($this->getCurrentSession() == 'ru') return self::$_data;
        
        $data = self::$_data;
        $tbl_name = self::$_tbl_name;
        
        $data['id'] = $this->getElementId();
        
        $fields = $this->getFields();
        foreach($fields as $v){
            $fields_value = $this->DB->execute('SELECT content FROM '.$this->_m.' WHERE id_element='.$data['id'].' AND id_field='.$v['id'].' AND language="'.$this->getCurrentSession().'"')->fetch_assoc();
            if(isset($data[$v['title_orig']])){
                if(!$fields_value){
                    $this->insertData($data['id'], $v['id'], $data[$v['title_orig']]);
                }
                else{
                    $this->updateData($data['id'], $v['id'], $tbl_name, $data[$v['title_orig']]);
                }
                $data[$v['title_orig']] = isset($_GET['id'])?$this->getBaseValueField($data['id'], $v['title_orig']):'';
            }
        }
        return $data;
    }
    // Удаление элемента
    public function delete(){
        $this->DB->execute('DELETE FROM '.$this->_m.' WHERE id_element='.$this->getCurrentIdElement().' AND id_module='.$this->getCurrentIdModule());
        return false; 
    }
    // Вставка строк
    private function insertData($id_element, $id_field, $content = ''){
        $stmt = $this->DB->prepare('INSERT INTO '.$this->_m.' (id_element, id_field, id_module, language, content) VALUES (:1, :2, :3, :4, :5)');
        $stmt->execute($id_element, $id_field, ((self::$_module_id == 0)?((int)$_GET['module']):(self::$_module_id)), $this->getCurrentSession(), $content);
        return false;
    }
    // Обновление контента в строках
    private function updateData($id_element, $id_field, $tbl_name, $content){
        $stmt = $this->DB->prepare('UPDATE '.$this->_m.' SET content = :1 WHERE id_field = :2 AND id_element = :3 AND language = :4');
        $stmt->execute($content, $id_field, $id_element, $this->getCurrentSession());   
        return false;
    }
    // Получение текущего id элемента
    private function getCurrentIdElement(){
        return (self::$_id == 0)?((int)$_GET['id']):(self::$_id);
    }
    // Получение текущего id модуля
    private function getCurrentIdModule(){
        return (self::$_module_id == 0)?((int)$_GET['module']):(self::$_module_id);
    }
    // Получение текущей сессии
    public function getCurrentSession(){
        if(array_shift(array_keys($_GET)) != 'admin'){
            return $_SESSION['lang'];
        }
        return $_SESSION['admin_lang'];
    }
    // Вывод html-разметки выбора языка
    public function getHtmlLang(){
        if(count($_GET) == 0 || array_shift(array_keys($_GET)) != 'admin' || !isset($_SESSION['UID']) || !$this->getUserAccess()) return false;
        
        $this->tpl->assign('curr_lang', $this->_languages[$this->getCurrentSession()]);
        $this->tpl->assign('url', URL('admin_lang'));
        $this->tpl->assign('langs', $this->_languages);
        return $this->tpl->fetch($this->_m.'.tpl');
    }
    // Проверка прав текущего пользователя
    private function getUserAccess(){
        $uid = $this->DB->execute('SELECT id FROM users WHERE login="'.$_SESSION['UID'].'"')->fetch_assoc();
        $access = $this->DB->execute('SELECT groupid FROM users_assign WHERE userid="'.$uid['id'].'"')->fetch_assoc();
        if($access['groupid'] != 2){
            return false;
        }
        return true;
    }
    // Получение мультиязычных полей текущего модуля
    public function getFields(){
        return $fields = $this->DB->execute("SELECT f.id, f.title_orig FROM tables_field f JOIN tables t ON t.id=f.id_table WHERE t.title_orig='".self::$_tbl_name."' AND f.translate='1' AND (f.show_edit='1' || f.show_view='1')")->fetchall_assoc();
    }
    // Выбор базового(русского) значения поля
    private function getBaseValueField($id_element, $field){
        $result = $this->DB->execute('SELECT '.$field.' FROM '.self::$_tbl_name.' WHERE id='.$id_element)->fetch_assoc();
        return $result[$field];
    }
    // Получение id элемента
    private function getElementId(){
        return isset($_GET['id'])?(int)$_GET['id']:getTableAutoIncrement(self::$_tbl_name);
    }
}
class I18n_Smarty extends Smarty { 
    function __construct() {
        parent::__construct();
        $this->template_dir = DOCUMENT_ROOT.'/i18n/templates/';
        $this->compile_dir	= TEMPLATES_C_DIR;
        $this->config_dir 	= CONFIGS_DIR;
        $this->cache_dir 	= CACHE_DIR;
    }
}
$mainTpl->assign("lang", I18n::getInstance()->getHtmlLang());
