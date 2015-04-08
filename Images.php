<?php
namespace system\libs;

use \Imagine\Image\Box;
use \Imagine\Image\Point;
use \Imagine\Image\ImageInterface;
use \Imagine\Filter\Transformation;
use \Imagine\Imagick\Imagine;
use \Imagine\Image\Palette\RGB;

class Images{

    const STORAGE                        = '/storage/';

    const PLACEHOLD_FOLDER               = 'caps';

    const PLACEHOLD_SITE                 = 'http://placehold.it/';

    const PLACEHOLD_TEXT                 = null;

    const PLACEHOLD_EXPANSION            = 'gif';

    const ALLOWED_EXPANSIONS             = 'jpg|jpeg|png|gif';

    const MAIN_EXPANSIONS                = 'png';

    const CONFIG_FILE                    = 'images.php';

    const CONFIG_DEFAULT                 = 'default';

    /**
     * @var null
     * объект класса
     */
    private static $__instance           = null;

    /**
     * @var null
     * массив данных передаваемый разработчиком
     */
    private static $__data               = null;

    /**
     * @var null
     * путь к изображению, начиная от папки storage
     */
    private static $_src                 = null;

    /**
     * @var null
     * папка с изображением в директории storage
     */
    private static $__folder             = null;

    /**
     * @var null
     * ширина нового изображения
     */
    private static $_width               = null;

    /**
     * @var null
     * высота нового изображения
     */
    private static $_height              = null;

    /**
     * @var null
     * режим сжатия
     * 0 - пропорционально, так чтобы изображение было видно полностью
     * 1 - пропорционально по заданным размерам
     */
    private static $_crop                = null;

    /**
     * @var null
     * водяной знак
     */
    private static $_watermark           = null;

    /**
     * @var null
     * отступ водяного знака на изображении по оси x, если значение отрицательно - отступ отсчитывается от правого края
     */
    private static $_watermarkx          = null;

    /**
     * @var null
     * отступ водяного знака на изображении по оси y, если значение отрицательно - отступ отсчитывается от нижнего края
     */
    private static $_watermarky          = null;

    /**
     * @var null
     * поворот изображения в градусах
     */
    private static $_rotate              = null;

    /**
     * @var null
     * чёрно-белое изображиние
     */
    private static $_grayscale           = null;

    /**
     * @var null
     * эффект негатива
     */
    private static $_negative            = null;

    /**
     * @var null
     * эффект размытия
     */
    private static $_blur                = null;

    /**
     * @var null
     * эффект "гамма"
     */
    private static $_gamma               = null;

    /**
     * @var null
     * как будет вырезано изображение при $_crop = 1
     * null или center - по центру, left - начиная от левого края, right - начиная от правого края
     */
    private static $_alignx              = null;

    /**
     * @var null
     * как будет вырезано изображение при $_crop = 1
     * null или center - по центру, top - начиная от верхнего края, bottom - начиная от нижнего края
     */
    private static $_aligny              = null;

    /**
     * @var null
     * Новая ширина изображения
     */
    private static $__new_width          = null;

    /**
     * @var null
     * новая высота изображения
     */
    private static $__new_height         = null;

    /**
     * @var null
     * отступ по горизонтали при обрезе с параметром crop = 1
     */
    private static $__offset_x           = null;

    /**
     * @var null
     * отступ по вертикали при обрезе с параметром crop = 1
     */

    private static $__offset_y           = null;

    /**
     * @var null
     * полноый путь к файлу
     */
    private static $__full_src           = null;

    /**
     * @var null
     * полный путь к новому файлу
     */
    private static $__full_new_src       = null;

    /**
     * @var null
     * путь к изображению от директории storage
     */
    private static $__relative_src       = null;

    /**
     * @var null
     * название нового изображения
     */
    private static $__new_image_name     = null;

    /**
     * @var null
     * путь к сгенерированному изображению от директории storage
     */
    private static $__new_relative_src   = null;

    /**
     * @var null
     * по-умолчанию при $_crop = 0 если размеры итогового изображения будут несоответствовать заданным,
     * то создастся контейнер с заданными размерами, а итоговое изображение будет отцентрировано в нём
     * значение false отменяет такое поведение
     */
    private static $_fullsize            = null;

    /**
     * @var null
     * объект класса ReflectionClass
     */
    private static $__reflection         = null;

    /**
     * @var null
     * цвет фона у изображения
     */
    private static $_backroundcolor      = null;

    /**
     * @var null
     * свойство, содержащее конфигурационный массив для изображений
     */
    private static $__configs            = null;


    private function __construct(){
        self::$__reflection      = new \ReflectionClass($this);

        self::$__configs         = require_once(CONFIGS.'/'.self::CONFIG_FILE);

        return false;
    }

    /**
     * @param $data
     * @return null|Images
     * возвращает объект класса Images
     */
    public static function initialize($paths, $data){
        // запись пути к изображению
        self::installPaths($paths);

        // запись массива с данными в статическую переменную
        self::$__data = $data;

        if(self::$__instance === null)
            return $_instance = new self();

        return self::$__instance;
    }

    /**
     * @param $paths
     * @return bool
     * установка путей
     */
    private static function installPaths($paths){
        if(is_array($paths) && count($paths) > 1){
            list(self::$_src, self::$__folder) = $paths;
        }
        else{
            preg_match('/^([^\/]+)\/([^\/]+)\/?$/i', $paths, $matches);

            if($matches){
                list($full, self::$__folder, self::$_src) = $matches;
            }
        }

        self::$__relative_src = self::$__folder.'/'.self::$_src;

        return false;
    }

    /**
     * @return bool|string
     * ресайз изображений
     */
    public static function resize(){
        // установка значений переменным
        // проверка существования исходного изображения
        // проверка существования конечного изображения
        if(self::initialInspection() !== true)
            return self::initialInspection();

        // основной объект Imagine
        $imagine = new Imagine();

        // открытие исходного изображения
        $image = $imagine->open(self::$__full_src);

        // поворот изображения на заданное количество градусов
        if(self::$_rotate !== null)
            $image->rotate(self::$_rotate);

        // установка размеров исходного и нового изображений в зависимости от $_crop
        self::determineNewSize($image);

        // ресайз изображений по большей/меньшей стороне
        $image->resize(new Box(self::$__new_width, self::$__new_height));

        // если $_crop === 1 - вырезание изображения с точными размерами
        if(self::$_crop)
            $image->crop(new Point(self::$__offset_x, self::$__offset_y), new Box(self::$_width, self::$_height));

        // установка водяного знака
        if(self::$_watermark !== null)
            self::setWatermark($imagine, $image);

        // создание подложки для создания точного размера изображения (при $_crop === 0)
        if(self::$_fullsize === null || self::$_fullsize)
            $collage = self::setFullSizes($imagine, $image);

        // создание нового объекта с подложкой
        $image = isset($collage) && $collage !== false ? $collage : $image;

        // применение чёрно-белого фильтра
        if(self::$_grayscale !== null)
            $image->effects()->grayscale();

        // применение фильтра "негатив"
        if(self::$_negative !== null)
            $image->effects()->negative();

        // применение фильтра "размытия"
        if(self::$_blur !== null)
            $image->effects()->blur(self::$_blur);

        // применение фильтра "гамма"
        if(self::$_gamma !== null)
            $image->effects()->gamma(self::$_gamma);

        // сохранение изображения по генерируемому пути
        $image->save(self::generateNewFullSrc());

        // генерация результирующего URL
        return self::generateResultUrl();
    }

    /**
     * @param $imagine
     * @param $image
     * установка водяного знака на изображение
     */
    private static function setWatermark($imagine, $image){
        $watermark = $imagine->open(DOCUMENT_ROOT.self::STORAGE.self::PLACEHOLD_FOLDER.'/watermark.png');

        $w_sizes   = $watermark->getSize();

        $w_width   = $w_sizes->getWidth();
        $h_height  = $w_sizes->getHeight();

        if(self::$_fullsize === null || self::$_fullsize || self::$_crop){
            $i_width = self::$_width;
        }
        else{
            $i_width = self::$__new_width;
        }

        if(self::$_fullsize === null || self::$_fullsize || self::$_crop){
            $i_height = self::$_height;
        }
        else{
            $i_height = self::$__new_height;
        }

        $x = ((int)self::$_watermarkx >= 0) ? (int)self::$_watermarkx : ((int)self::$_watermarkx + $i_width - $w_width);
        $y = ((int)self::$_watermarky >= 0) ? (int)self::$_watermarky : ((int)self::$_watermarky + $i_height - $h_height);

        if($x < 0 || $x > ($i_width - $w_width)){
            $x = 0;
        }

        if($y < 0 || $y > ($i_height - $h_height)){
            $y = 0;
        }

        $image->paste($watermark, new Point($x, $y));

        return false;
    }

    /**
     * @return bool|string
     * начальные проверки
     */
    private static function initialInspection(){
        self::setValuesToVars();

        if(!self::checkImage())
            return self::getCap();

        //if(self::checkImage(self::generateNewFullSrc()))
            //return self::generateResultUrl();

        return true;
    }

    /**
     * @return bool
     * установка значений статичным переменным
     */
    public static function setValuesToVars(){
        $current_config = self::getConfig(self::getUserConfig());

        $result_config = array();

        foreach (self::$__data as $k => $v) {
            if(!is_int($k)) break;

            if($k != 0) next($current_config);

            $result_config[key($current_config)] = $v;

        }

        foreach (self::$__data as $k => $v) {
            if(is_int($k)) continue;

            if(array_key_exists($k, $current_config))
                $result_config[$k] = $v;

        }

        foreach($current_config as $k=>$v){
            $var_name = '_'.$k;
            self::$$var_name = isset($result_config[$k]) ? $result_config[$k] : $current_config[$k];
        }

        self::$__full_src = self::normalizeSrc();

        return false;
    }

    /**
     * @param bool $config
     * @return mixed
     * получение конфигурации, выбранной пользователм из configs/images.php, по-умолчанию default
     */
    private static function getConfig($config = false){
        return self::$__configs[(!$config ? self::CONFIG_DEFAULT : $config)];
    }

    /**
     * @return bool
     * алгорим получения требуемой конфигурации
     */
    public static function getUserConfig(){
        if(isset(self::$__data['set']) && self::$__data['set'] != '')
            return self::$__data['set'];

        if(count(self::$__data) == 1 && isset(self::$__configs[current(self::$__data)])) {
            $set = self::$__data[0];

            self::$__data[0] = null;

            return $set;
        }

        return false;
    }

    /**
     * @param $src
     * @return string
     * путь к файлу-заглушке
     */
    private static function normalizeSrc($src = false){
        return DOCUMENT_ROOT.self::STORAGE.($src === false ? self::$__relative_src : $src);
    }

    /**
     * @return string
     * генерация нового url-а
     */
    private static function generateResultUrl($src = false){
        return BASE_URL.self::STORAGE.($src === false ? self::$__new_relative_src : $src);
    }

    /**
     * @param $new_width
     * @param $new_height
     * @param $required_width
     * @param $required_height
     * @return bool
     * определение части изображения, которую нужно вырезать
     */
    private static function getOffsets($new_width, $new_height, $required_width, $required_height){
        switch(self::$_alignx){
            case null     :
            case 'center' :
                self::$__offset_x = round(($new_width - $required_width) / 2);
                break;
            case 'right'  :
                self::$__offset_x = round($new_width - $required_width);
                break;
            case 'left'   :
            default       :
                self::$__offset_x = 0;
        }

        switch(self::$_aligny){
            case null     :
            case 'center' :
                self::$__offset_y = round(($new_height - $required_height) / 2);
                break;
            case 'bottom'  :
                self::$__offset_y = round($new_height - $required_height);
                break;
            case 'top'   :
            default      :
                self::$__offset_y = 0;
        }

        return false;
    }

    /**
     * @param $image
     * @return bool
     * определение новых размеров изображения
     */
    private static function determineNewSize($image){
        $sizes              = $image->getSize();

        $real_width         = $sizes->getWidth();
        $real_height        = $sizes->getHeight();

        if(!self::$_crop && (self::$_width >= $real_width) && (self::$_height >= $real_height)){
            $correct_width = $real_width;
            $correct_height = $real_height;
        }

        $required_width = (isset($correct_width) ? $correct_width : self::$_width);
        $required_height = (isset($correct_height) ? $correct_height : self::$_height);

        $proportions_x      = $real_width  / $required_width;
        $proportions_y      = $real_height / $required_height;

        if(self::$_crop)
            $result_proportions = $proportions_x <= $proportions_y ? $proportions_x : $proportions_y;
        else{
            $result_proportions = $proportions_x <= $proportions_y ? $proportions_y : $proportions_x;
        }

        $new_width          = $real_width  / $result_proportions;
        $new_height         = $real_height / $result_proportions;

        self::getOffsets($new_width, $new_height, $required_width, $required_height);

        self::$__new_width  = round($new_width);
        self::$__new_height = round($new_height);

        return false;
    }

    /**
     * @return string
     * получение заглушки в случае, если исходное изображение отсутствует
     */
    private static function getCap(){
        $name_for_parse = self::$_width.'x'.self::$_height.(self::PLACEHOLD_TEXT !== null ? '&text='.urlencode(self::PLACEHOLD_TEXT) : '');

        $name_of_cap    =  $name_for_parse.'.'.self::PLACEHOLD_EXPANSION;

        $src_of_cap     = self::normalizeSrc(self::PLACEHOLD_FOLDER.'/'.$name_of_cap);

        if(self::checkImage($src_of_cap))
            return self::generateResultUrl(self::PLACEHOLD_FOLDER.'/'.$name_of_cap);

        copy(self::PLACEHOLD_SITE.$name_for_parse, $src_of_cap);

        return $src_of_cap;
    }

    /**
     * @return bool
     * проверка избражения на существование
     */
    private static function checkImage($src = false){
        return file_exists(($src === false ? self::$__full_src : $src));
    }

    /**
     * @return mixed|null
     * генерация абсолюьного пути к новому изображению
     */
    private static function generateNewFullSrc(){
        if(self::$__full_new_src !== null) return self::$__full_new_src;

        $properties = self::$__reflection->getStaticProperties();

        $new_name   = '';

        $key        = '';

        $i          = 1;

        foreach($properties as $k => $v){
            preg_match('/^_[a-z]+$/i', $k, $matches);

            if($k == '_src') {
                preg_match('/^[^\/]+\/([^\/]+)\.(' . self::ALLOWED_EXPANSIONS . ')$/i', self::$__relative_src, $fragments);

                if(count($fragments) == 3)
                    list($full, $v, $expansion) = $fragments;
            }

            if(count($matches) > 0 && $v !== null) {
                $new_name .= $v . '_';
                $key      .= $i;
            }

            $i++;
        }

        self::$__new_image_name = (rtrim($new_name, '_')).$key.'.'.self::MAIN_EXPANSIONS;

        self::$__new_relative_src = self::$__folder.'/'.self::$__new_image_name;

        self::$__full_new_src = preg_replace('/^(.+)\/[^\/]+$/i', '$1/'.self::$__new_image_name, self::$__full_src);

        return self::$__full_new_src;
    }

    /**
     * @param $imagine
     * @param $image
     * @return bool
     * установка контейнера для нового изображения для получения требуемых размеров
     */
    private static function setFullSizes($imagine, $image){
        if(self::$__new_width < self::$_width || self::$__new_height < self::$_height){
            $palette = new RGB();

            $size    = new Box(self::$_width, self::$_height);

            if(self::$_backroundcolor === null){

            }

            $color   = (self::$_backroundcolor === null) ? $palette->color('FFF', 0) : $palette->color(self::$_backroundcolor, 100);

            $collage = $imagine->create($size, $color);

            $x = round((self::$_width - self::$__new_width) / 2);
            $y = round((self::$_height - self::$__new_height) / 2);

            $collage->paste($image, new Point($x, $y));

            return $collage;
        }

        return false;
    }

    /**
     * @return null
     * получения папки для текущего изображения
     */
    public static function getFolder(){
        return self::$__folder;
    }

}
