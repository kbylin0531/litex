<?php
/**
 * Created by phpStorm.
 * User: linzh_000
 * Date: 2016/2/16
 * Time: 10:16
 *
 *
 * From CodeIgniter
 *
 * An open source application development framework for php
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2015, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	http://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
namespace PLite\Util\Helper;

/**
 * CodeIgniter XML Helpers
 *
 * The XMLHelper file contains functions that assist in working with XML data.
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/xml_helper.html
 */
class XMLHelper {
    /**
     * Convert Reserved XML characters to Entities
     * 将XML标签转换成实体以避免被浏览器解析成标签而实际输出原始XML字符串
     *
     * Takes a string as input and converts the following reserved XML characters to entities
     *  ①Ampersands: &
     *  ②Less than and greater than characters: < >
     *  ③Single and double quotes: ‘ “
     *  ④Dashes: -
     *
     * This function ignores ampersands if they are part of existing numbered character entities, e.g. &#123;.
     *
     * <code>
     *  echo '<p>Here is a paragraph & an entity ------ (&#123;).</p><br />';
     *  echo XMLHelper::convert('<p>Here is a paragraph & an entity ------ (&#123;).</p><br />');
     *
     *  // &lt;p&gt;Here is a paragraph &amp; an entity &#45;&#45;&#45;&#45;&#45;&#45; (&#123;).&lt;/p&gt;&lt;br /&gt;
     *  //于是浏览器上能显示为：<p>Here is a paragraph & an entity ------ ({).</p><br />
     *  //如果未转换则直接显示为：Here is a paragraph & an entity ------ ({).
     * </code>
     *
     * @param string $str  the text string to convert
     * @param bool|FALSE $protect_all  Whether to protect all content that looks like a potential entity instead of just numbered entities, e.g. &foo;
     * @return string XML-converted string
     */
    public static function convert($str, $protect_all = false)    {
        $temp = '__TEMP_AMPERSANDS__';

        // Replace entities to temporary markers so that
        // ampersands won't get messed up
        $str = preg_replace('/&#(\d+);/', $temp.'\\1;', $str);

        if ($protect_all === TRUE)
        {
            $str = preg_replace('/&(\w+);/', $temp.'\\1;', $str);
        }

        $str = str_replace(
            array('&', '<', '>', '"', "'", '-'),
            array('&amp;', '&lt;', '&gt;', '&quot;', '&apos;', '&#45;'),
            $str
        );

        // Decode the temp markers back to entities
        $str = preg_replace('/'.$temp.'(\d+);/', '&#\\1;', $str);

        if ($protect_all === TRUE)
        {
            return preg_replace('/'.$temp.'(\w+);/', '&\\1;', $str);
        }

        return $str;
    }

    /**
     * TP3.2.3
     *
     * 数据XML编码
     * @param mixed  $data 数据
     * @param string $item 数字索引时的节点名称
     * @param string $id   数字索引key转换为的属性名
     * @return string
     */
    public static function data2Xml($data, $item='item', $id='id') {
        $xml = $attr = '';
        foreach ($data as $key => $val) {
            if(is_numeric($key)){
                $id && $attr = " {$id}=\"{$key}\"";
                $key  = $item;
            }
            $content = (is_array($val) || is_object($val)) ? self::data2Xml($val, $item, $id) : $val;
            $xml    .=  "<{$key}{$attr}>{$content}</{$key}>";
        }
        return $xml;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string|array $attrs 根节点属性
     * @param string $id   数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    public static function encode($data, $root='root', $item='item', $attrs='', $id='id', $encoding='utf-8') {
        if(!$attrs){
            if(is_array($attrs)){
                $_attr = array();
                foreach ($attrs as $key => $value) {
                    $_attr[] = "{$key}=\"{$value}\"";
                }
                $attrs = implode(' ', $_attr);
            }
        }else{
            $attrs = '';
        }

        $inner = self::data2Xml($data, $item, $id);
        return "<?xml version=\"1.0\" encoding=\"{$encoding}\"?><{$root} {$attrs}>{$inner}</{$root}>";
    }


    /**
     * 数组转XML
     * @param array $arr
     * @return string
     */
    public static function arrayToXml(array $arr){
        $xml = '<xml>';
        foreach ($arr as $key=>$val){
            if (is_numeric($val)){
                $xml.="<{$key}>{$val}</{$key}>";
            }else{
                $xml.="<{$key}><![CDATA[{$val}]]></{$key}>";
            }
        }
        return $xml .'</xml>';
    }

    /**
     * 将XML转为array
     * @param string $xml
     * @return array
     */
    public static function xmlToArray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

}
