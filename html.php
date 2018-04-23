<?php namespace tag; // vim: se fdm=marker:

final class html implements \ArrayAccess, \Countable{

  private const NORMAL    = 0;
  private const NOCLOSE   = 2**1;
  private const NOCHILD   = 2**2;
  private const SELFCLOSE = 2**3;
  private const STRIP     = 2**4;
  private const NOTEXT    = 2**5;
  private const NOSELF    = 2**6;
  private const ENTITY    = 2**7;
  private const RAW       = 2**8;

  private $open, $mask=self::NORMAL, $tags;
  private $prop = [];
  private $CHILD=[];


  function offsetExists($offset):bool{
    return isset($this->CHILD[$offset]);
  }


  function offsetGET($offset){
    return $this->CHILD[$offset];
  }


  function offsetSET($offset, $value):void{
    if(is_scalar($value)||$value instanceof self)
    $this->CHILD[$offset] = $value;
  }


  function offsetUnset($offset):void{
    unset($this->CHILD[$offset]);
  }


  function count():int{
    return count($this->CHILD);
  }


  static function __set_State():string{
    return "$this";//TODO
  }


  static private function create(
    ?string $tag,
    int $bit = self::NORMAL,
    string ...$tags
  ):self{
    return (function(?string $tag, int $bit, array $tags):self{
      if(
        is_null($tag) ||
        $tag && ctype_alpha($tag[0]) &&
        (
          ctype_alnum(str_replace('-',null,$tag,$count).$tag[-1]) && $count//CustomElement
          ||
          ctype_alnum(str_replace(':',null,$tag,$count).$tag[-1]) && $count<2//NS
        )
      )
        $this->open = $tag;
      else
        $this->open = 'div';

      [$this->mask, $this->tags] = [$bit, $tags];
      return $this;
    })->call(new self,$tag, $bit, $tags);
  }


  function __construct(string ...$html){
    $this->CHILD += $html;
  }


  function __invoke(...$children):self{

    if(~$this->mask&self::NOCHILD){

      array_map(function($child){

        if(is_scalar($child) && ~$this->mask&self::NOTEXT)
          $this->CHILD[] = $this->mask&self::RAW?"$child":htmlspecialchars($child,ENT_NOQUOTES);

        elseif($child instanceof self)
          if(!($this->mask&self::NOSELF && strcasecmp($child->name(),$this->open)==0))
            if(empty($this->tags) || in_array($child->name(),$this->tags))
              $this->CHILD[] = $this->mask&self::STRIP?
              strip_tags($child):
              $this->mask&self::ENTITY?htmlspecialchars($child,ENT_NOQUOTES):$child;

      },$children);

    }
    return $this;
  }


  function set(array ...$attributes):self{
    foreach($attributes as $item)
      foreach($item as $name=>$value)
        $this->$name = $value;
    return $this;
  }


  function __set(string $attr, $value):void{
    $this->open = $this->open??'div';
    if(ctype_alnum(str_replace(['-','_'],null,$attr))&&is_scalar($value))
      $this->prop{strtolower($attr)} = $value;
  }


  function __get(string $attr){
    return $this->prop{strtolower($attr)}??null;
  }


  function name():?string{
    return $this->open;
  }


  function __toString():string{
    if(is_null($this->open))
      return join($this->CHILD);
    elseif($this->CHILD)
      return sprintf("<%s%s>%s</%s>", $this->open, $this->attr(), join($this->CHILD), $this->open);
    elseif($this->mask&self::SELFCLOSE)
      return sprintf("<%s%s/>", $this->open, $this->attr());
    elseif($this->mask&self::NOCLOSE)
      return sprintf("<%s%s>", $this->open, $this->attr());
    else
      return sprintf("<%s%s></%s>", $this->open, $this->attr(), $this->open);
  }


  function __debugInfo():array{
    return $this->prop;
  }


  private function attr():string{
    $attr = '';
    foreach($this->prop as $k=>$v)
      switch(gettype($v)){
        case 'boolean':
          if($v)
            $attr .= " $k";
          break;
        case 'integer':
        case 'double':
          $attr .= " $k=$v";
          break;
        case 'string':
          if(ctype_alnum($v))
            $attr .= " $k=$v";
          else
            $attr .= ' '.$k.'="'.htmlentities($v).'"';
          break;
      }
    return $attr;
  }


  static function __callStatic(string $tag, array $args):self{
    return self::create($tag);
  }


  function wrap(string $tag='div', array $attributes=[]):self{
    $this->CHILD = [html::$tag()(...$this->CHILD)->set($attributes)];
    return $this;
  }


  function empty():self{
    $this->CHILD = [];
    return $this;
  }


  function sort():self{
    array_multisort(
      $this->CHILD,
      SORT_NATURAL|SORT_FLAG_CASE,
      array_map('strip_tags',$this->CHILD)
    );
    return $this;
  }


  function reverse():self{
    $this->CHILD = array_reverse($this->CHILD);
    return $this;
  }


  function shuffle():self{
    shuffle($this->CHILD);
    return $this;
  }


  function unique():self{
    foreach(array_unique($this->CHILD, SORT_STRING) as $idx=>$null)
      unset($this->CHILD[$idx]);
    return $this;
  }


  function filter(callable $cb):self{
    $this->CHILD = array_filter($this->CHILD, $cb, ARRAY_FILTER_USE_BOTH);
    return $this;
  }


  function pad(int $size, self $html):self{
    $this->CHILD = array_pad($this->CHILD, $size, $html);
    return $this;
  }


  /**
   * @param ...$dict ...file('/usr/share/dict/words',FILE_SKIP_EMPTY_LINES)
   */
  function lorem(int $min=3, int $max=8, string ...$dict):self{
    $dict = $dict?:['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'a', 'ac', 'accumsan', 'ad', 'aenean', 'aliquam', 'aliquet', 'ante', 'aptent', 'arcu', 'at', 'auctor', 'augue', 'bibendum', 'blandit', 'class', 'commodo', 'condimentum', 'congue', 'consequat', 'conubia', 'convallis', 'cras', 'cubilia', 'curabitur', 'curae', 'cursus', 'dapibus', 'diam', 'dictum', 'dictumst', 'dignissim', 'dis', 'donec', 'dui', 'duis', 'efficitur', 'egestas', 'eget', 'eleifend', 'elementum', 'enim', 'erat', 'eros', 'est', 'et', 'etiam', 'eu', 'euismod', 'ex', 'facilisi', 'facilisis', 'fames', 'faucibus', 'felis', 'fermentum', 'feugiat', 'finibus', 'fringilla', 'fusce', 'gravida', 'habitant', 'habitasse', 'hac', 'hendrerit', 'himenaeos', 'iaculis', 'id', 'imperdiet', 'in', 'inceptos', 'integer', 'interdum', 'justo', 'lacinia', 'lacus', 'laoreet', 'lectus', 'leo', 'libero', 'ligula', 'litora', 'lobortis', 'luctus', 'maecenas', 'magna', 'magnis', 'malesuada', 'massa', 'mattis', 'mauris', 'maximus', 'metus', 'mi', 'molestie', 'mollis', 'montes', 'morbi', 'mus', 'nam', 'nascetur', 'natoque', 'nec', 'neque', 'netus', 'nibh', 'nisi', 'nisl', 'non', 'nostra', 'nulla', 'nullam', 'nunc', 'odio', 'orci', 'ornare', 'parturient', 'pellentesque', 'penatibus', 'per', 'pharetra', 'phasellus', 'placerat', 'platea', 'porta', 'porttitor', 'posuere', 'potenti', 'praesent', 'pretium', 'primis', 'proin', 'pulvinar', 'purus', 'quam', 'quis', 'quisque', 'rhoncus', 'ridiculus', 'risus', 'rutrum', 'sagittis', 'sapien', 'scelerisque', 'sed', 'sem', 'semper', 'senectus', 'sociosqu', 'sodales', 'sollicitudin', 'suscipit', 'suspendisse', 'taciti', 'tellus', 'tempor', 'tempus', 'tincidunt', 'torquent', 'tortor', 'tristique', 'turpis', 'ullamcorper', 'ultrices', 'ultricies', 'urna', 'ut', 'varius', 'vehicula', 'vel', 'velit', 'venenatis', 'vestibulum', 'vitae', 'vivamus', 'viverra', 'volutpat', 'vulputate'];

    shuffle($dict);

    $this->CHILD[] = join(array_slice($dict, 0, mt_rand(min($min,$max),max($min,$max))), ' ');

    return $this;
  }


  static function html(string $lang=null):self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOCLOSE)->set(['lang'=>$lang]);
  }

  //{{{

  static function ol(array $list=[]):self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'li');
  }

  static function ul(array $list=[]):self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'li');
  }

  static function li(string $value=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['value'=>$value]);
  }

  static function dl():self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'dt','dd');
  }

  static function dt():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOSELF);
  }

  static function dd():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOSELF);
  }

  static function keygen():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOSELF);
  }

  static function table():self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'caption','thead','tbody','tfoot');
  }

  static function thead(string  ...$cell):self{
    $thead = self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'tr');
    if($cell){
      $tr = html::tr();
      foreach($cell as $th)
        $tr(html::th()($th));
      $thead($tr);
    }
    return $thead;
  }

  /**
   * @param array $data=[] 期望是一个二维数组
   */
  static function tbody(array $data=[]):self{
    $tbody = self::create(__FUNCTION__,self::NOTEXT|self::NOSELF,'tr');
    if($data){
      foreach($data as $tr){
        $tr = html::tr();
        foreach($tr as $td)
          $tr(html::td()($td));
      }
      $tbody($tr);
    }
    return $tbody;
  }

  static function tfoot():self{
    return self::create(__FUNCTION__,self::NOTEXT|self::NOSELF,'tr');
  }

  static function tr(string ...$cell):self{
    return self::create(__FUNCTION__,self::NOTEXT|self::NOSELF,'td','th');
  }

  static function th():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOSELF);
  }

  static function td():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOSELF);
  }

  static function input(string $type=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE)->set(['type'=>$type]);
  }

  static function label(string $for=null):self{
    return self::create(__FUNCTION__)->set(['for'=>$for]);
  }

  static function br():self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE);
  }

  static function hr():self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE);
  }

  static function wbr():self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE);
  }

  static function img(string $url=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE)->set(['src'=>$url]);
  }

  static function isindex(string $prompt=null, string $action=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE)->set(['prompt'=>$url,'action'=>$action]);
  }

  static function main():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function aside():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function marquee():self{
    return self::create(__FUNCTION__);
  }

  static function blink():self{
    return self::create(__FUNCTION__);
  }

  static function map(string $name=null):self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'area')->set(['name'=>$name]);
  }

  static function mark():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function menu(string $type=null):self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'menuitem','hr')->set(['type'=>$type]);
  }

  static function menuitem(string $type=null):self{
    return self::create(__FUNCTION__,self::STRIP)->set(['type'=>$type]);
  }

  static function meter(float $min=0, float $max=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['min'=>$min,'max'=>$max]);
  }

  static function nav():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function area(string $shape=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE)->set(['shape'=>$shape]);
  }

  static function article():self{
    return self::create(__FUNCTION__);
  }

  static function legend():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function p():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function canvas():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function title(string $str=null):self{
    return self::create(__FUNCTION__,self::STRIP|self::NOSELF)($str);
  }

  static function element():self{
    return self::create(__FUNCTION__);
  }

  static function head():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOSELF,'meta','link','title','style','script','base','basefont','bgsound','isindex');
  }

  static function base(string $href=null):self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOCHILD)->set(['href'=>$href]);
  }

  static function bgsound(string $src=null):self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOCHILD)->set(['src'=>$src]);
  }

  static function dir():self{
    return self::create(__FUNCTION__);
  }

  static function xml():self{
    return self::create(__FUNCTION__);
  }

  static function math():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function svg():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function body():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOSELF);
  }

  static function meta():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOCHILD);
  }

  static function link():self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOCHILD);
  }

  static function header():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function footer():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function a(string $url=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['href'=>$url]);
  }

  static function b():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function u():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function i():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function iframe():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function strong():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function em():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function ins():self{
    return self::create(__FUNCTION__);
  }

  static function del():self{
    return self::create(__FUNCTION__);
  }

  static function s():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function strike():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function shadow():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function address():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function var():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function abbr(string $title=null,string $innerText=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['title'=>$title])($innerText);
  }

  static function button(string $type=null):self{
    return self::create(__FUNCTION__,self::STRIP|self::NOSELF)->set(['type'=>$type]);
  }

  static function big():self{
    return self::create(__FUNCTION__);
  }

  static function bdi():self{
    return self::create(__FUNCTION__);
  }

  static function bdo(string $dir=null):self{
    return self::create(__FUNCTION__)->set(['dir'=>$dir]);
  }

  static function kbd(string ...$key):self{
    $kbd = self::create(__FUNCTION__,self::NORMAL,__FUNCTION__);
    foreach(array_filter(array_map('trim',$key)) as $k)
      $kbd(html::kbd()($k));
    return $kbd;
  }

  static function select(string $name=null, array ...$option):self{
    $select = self::create(__FUNCTION__,self::NOTEXT|self::NOSELF,'option')->set(['name'=>$name]);
    foreach($option as $opt)
      foreach($opt as $value=>$html)
        $select()(html::option($value,$html));
    return $select;
  }

  static function optgroup(string $label=null):self{
    return self::create(__FUNCTION__,self::NOCLOSE|self::NOSELF|self::NOTEXT,'option')->set(['label'=>$label]);
  }

  static function option(string $value=null, string $html=null, string $title=null):self{
    return self::create(__FUNCTION__,self::STRIP|self::NOCLOSE|self::NOSELF)->set(['value'=>$value,'title'=>$title])($html);
  }

  static function small():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function cite():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function colgroup():self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'col');
  }

  static function col(int $span=1):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['span'=>$span]);
  }

  static function data(string $value=null):self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function command():self{
    return self::create(__FUNCTION__,self::NOCHILD);
  }

  static function center():self{
    return self::create(__FUNCTION__);
  }

  static function content(string $select=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['select'=>$select]);
  }

  static function datalist(string $value=null):self{
    return self::create(__FUNCTION__,self::NOSELF|self::NOTEXT,'option');
  }

  static function details(bool $open=false):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['open'=>$open]);
  }

  static function dialog(bool $open=false):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['open'=>$open]);
  }

  static function dfn(string $title=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['title'=>$title]);
  }

  static function fieldset():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function figure():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function figcaption():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function form(string $action=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['action'=>$action]);
  }

  static function font(string $face=null):self{
    return self::create(__FUNCTION__)->set(['face'=>$face]);
  }

  static function basefont(string $face=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE)->set(['face'=>$face]);
  }

  static function audio():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function video():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function track():self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE);
  }

  static function output(string $name=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['name'=>$name]);
  }

  static function code(string $html=''):self{
    return self::create(__FUNCTION__,self::NOSELF|self::RAW)($html);//FIXME pre和code，谁包裹谁？？？
  }

  //FIXME pre强行转义之后，高亮库应该可以正确解析吧？？？
  static function pre(string $html=null):self{
    return self::create(__FUNCTION__,self::NOSELF|self::RAW)($html);//FIXME pre和code，谁包裹谁？？？
  }

  static function plaintext(string $html=null):self{
    return self::create(__FUNCTION__,self::NOSELF|self::RAW)($html);//FIXME pre和code，谁包裹谁？？？
  }

  static function xmp(string $html=null):self{
    return self::create(__FUNCTION__,self::NOSELF|self::RAW)($html);//FIXME pre和code，谁包裹谁？？？
  }

  static function picture():self{
    return self::create(__FUNCTION__,self::NOSELF,'source','img');
  }

  static function progress(int $max=null, int $value=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['max'=>$max,'value'=>$value]);
  }

  static function q(string $cite=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['cite'=>$cite]);
  }

  static function blockquote(string $cite=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['cite'=>$cite]);
  }

  static function ruby():self{
    return self::create(__FUNCTION__,self::NOSELF,'rp','rt','rb','rtc');
  }

  static function rb():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function rp():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function rt():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function rtc():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function section():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function samp():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function script(string $src=null):self{
    return self::create(__FUNCTION__,self::NOCHILD)->set(['src'=>$src]);
  }

  static function span():self{
    return self::create(__FUNCTION__);
  }

  static function sub():self{
    return self::create(__FUNCTION__);
  }

  static function sup():self{
    return self::create(__FUNCTION__);
  }

  static function slot(string $name=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['name'=>$name]);
  }

  static function summary():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function template():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function textarea():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function tt():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function time(\DateTime $datetime=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['datatime'=>$datetime]);
  }

  static function acronym(string $title=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['title'=>$title]);
  }

  static function noscript():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function nobr():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function noframes():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function frameset():self{
    return self::create(__FUNCTION__,self::NOTEXT,'frame');
  }

  static function frame(string $src=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE)->set(['src'=>$src]);
  }

  static function noembed():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function embed():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function applet():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function object():self{
    return self::create(__FUNCTION__,self::NOSELF);
  }

  static function param(string $name=null, string $value=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE)->set(['name'=>$name,'value'=>$value]);
  }

  static function nextid(int $n=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE,'param')->set(['n'=>$n]);
  }

  static function hgroup(string $id=null):self{
    return self::create(__FUNCTION__,self::NOSELF,'h1','h2','h3','h4','h5','h6')->set(['id'=>$id]);
  }

  static function h1(string $id=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['id'=>$id]);
  }

  static function h2(string $id=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['id'=>$id]);
  }

  static function h3(string $id=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['id'=>$id]);
  }

  static function h4(string $id=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['id'=>$id]);
  }

  static function h5(string $id=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['id'=>$id]);
  }

  static function h6(string $id=null):self{
    return self::create(__FUNCTION__,self::NOSELF)->set(['id'=>$id]);
  }

  static function source(string $type=null,string $src=null):self{
    return self::create(__FUNCTION__,self::NOCHILD|self::NOCLOSE)->set(['type'=>$type,'src'=>$src]);
  }

  //}}}

}
