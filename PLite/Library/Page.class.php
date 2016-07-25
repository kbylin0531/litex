<?php
/**
 * Created by PhpStorm.
 * User: lnzhv
 * Date: 7/7/16
 * Time: 9:31 PM
 */

namespace PLite\Library;
use PLite\Core\URL;
use PLite\Lite;

/**
 * Class Page 分页类
 * <code>
 *      $page = new Page(1225,20);
 *      echo $page->show('',true);
 *      \PLite\dumpout(
 *          $page->getFirstRow(),
 *          $page->getPageSize(),
 *          $page->getNowPage()
 *      );
 * </code>
 * @package PLite\Library
 */
class Page extends Lite{

    const CONF_NAME = 'page';
    const CONF_CONVENTION = [
        'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
        'prev'   => '<',
        'next'   => '>',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',

        //分页参数名称
        'VAR_PAGE'  => 'page'
    ];
    /**
     * 起始行数
     * @var int
     */
    public $firstRow;
    /**
     * 列表每页显示行数
     * @var int
     */
    public $pageSise;
    /**
     * 总行数
     * @var int
     */
    public $totalRows;
    /**
     * 分页总页面数
     * @var int
     */
    public $totalPages;
    /**
     * 分页栏每页显示的页数
     * @var int
     */
    public $rollPage   = 11;
    /**
     * 当前的页数
     * @var int
     */
    private $nowPage = 1;

    /**
     * 分页跳转时要带的参数
     * @var array
     */
    public $parameter;
    /**
     * 最后一页是否显示总页数
     * @var bool
     */
    public $lastSuffix = false;
    /**
     * 分页参数名
     * @var string
     */
    private $p       = 'p';
    private $url     = ''; //当前链接URL

    /**
     * 分页显示定制
     * @var array
     */
    private $config  = [];

    /**
     * 架构函数
     * @param int $totalRows  总的记录数
     * @param int $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($totalRows, $listRows=20, $parameter = array()) {
        $this->config = self::getConfig();
        $this->p = $this->config['VAR_PAGE']; //设置分页参数名称
        /* 基础设置 */
        $this->totalRows  = $totalRows; //设置总记录数
        $this->pageSise   = $listRows;  //设置每页显示行数
        $this->parameter  = empty($parameter) ? $_GET : $parameter;
        $this->parameter or $this->parameter = [
            $this->p    => 1,//默认使用第一页
        ];

        $this->nowPage    = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->firstRow   = $this->pageSise * ($this->nowPage - 1);
    }

    /**
     * 获取当前分页号
     * @return int
     */
    public function getNowPage(){
        return $this->nowPage;
    }

    /**
     * 获取起始行的序号
     * @return int
     */
    public function getFirstRow(){
        return $this->firstRow;
    }

    /**
     * 获取每页的行数
     * @return int
     */
    public function getPageSize(){
        return $this->pageSise;
    }

    /**
     * 生成链接URL
     * @param  integer $page 页码
     * @return string
     */
    private function url($page){
        return str_replace('[PAGE]', $page, $this->url);
    }

    /**
     * 组装分页链接
     * @param string $class 类名称
     * @param bool $styled 是否带样式输出
     * @return string
     */
    public function show($class='',$styled=false) {
        if(0 == $this->totalRows) return '';

        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->pageSise); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->url = URL::url(REQUEST_ACTION, $this->parameter);
        /* 计算分页零时变量 */
        $now_cool_page      = $this->rollPage/2;
        $now_cool_page_ceil = ceil($now_cool_page);
        $this->lastSuffix && $this->config['last'] = $this->totalPages;

        //上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<a class="prev" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '';

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<a class="next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '';

        //第一页
        $the_first = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
            $the_first = '<a class="first" href="' . $this->url(1) . '">' . $this->config['first'] . '</a>';
        }

        //最后一页
        $the_end = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
            $the_end = '<a class="end" href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a>';
        }

        //数字连接
        $link_page = "";
        for($i = 1; $i <= $this->rollPage; $i++){
            if(($this->nowPage - $now_cool_page) <= 0 ){
                $page = $i;
            }elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
                $page = $this->totalPages - $this->rollPage + $i;
            }else{
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }
            if($page > 0 && $page != $this->nowPage){

                if($page <= $this->totalPages){
                    $link_page .= '<a class="num" href="' . $this->url($page) . '">' . $page . '</a>';
                }else{
                    break;
                }
            }else{
                if($page > 0 && $this->totalPages != 1){
                    $link_page .= '<span class="current">' . $page . '</span>';
                }
            }
        }

        //替换分页内容
        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'),
            array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages),
            $this->config['theme']);
        $str = '';
        if($styled) $str = '<style>._sp_{margin:10px}._sp_:after,._sp_:before{display:table;content:""}._sp_:after{clear:both}._sp_ a,._sp_ span{text-decoration:none;float:left;padding:0 10px;height:28px;line-height:28px;color:#333;margin-right:10px;background-color:#ddd;border-radius:3px}._sp_ a:hover{text-decoration:none;background-color:#339c38;color:#fff}._sp_ .current{background-color:#44b549;color:#fff}._sp_ .next,._sp_ .prev{font-weight:700}</style>';
        return $str."<div class='_sp_ {$class}'>{$page_str}</div>";
    }
}