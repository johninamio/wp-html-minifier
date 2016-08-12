<?php

/*
 * Plugin Name: 	WP HTML-Minifier
 * Plugin URI: 		http://www.github.com/johninamio/wp-html-minifier/
 * Description: 	Simple WordPress HTML-Minifier.
 * Version: 		1.0
 * Author: 		johninamio
 * Author URI: 		http://www.github.com/johninamio
 */

// return if access directly
if(!defined('ABSPATH')){
    exit();
    die();
}

/**
 * HTML Minifier Finish
 * 
 * @type        function
 * 
 * @param       {string}    $html
 * @return      {object}    swp_Minifier
 * 
 * @since       1.0
 * @version     1.0
 */
function swp_html_minifier_finish($html){
    return new swp_html_Minifier($html);
}

/**
 * HTML Minifier Start
 * 
 * @type        function
 * 
 * @return      void
 * 
 * @since       1.0
 * @version     1.0
 */
function swp_html_minifier_start(){
    ob_start('swp_html_minifier_finish');
}
add_action('get_header', 'swp_html_minifier_start');

/**
 * HTML Minifier
 * 
 * @type        class
 * 
 * @since       1.0
 * @version     1.0
 */
final class swp_html_Minifier {
    
    protected   $compress_css       = true, 
                $compress_js        = true, 
                $remove_comments    = true, 
                $notice_comment     = false;

    protected   $html;
    
    /**
     * Construct
     * 
     * @type    function
     * @access      public
     * 
     * @param       {string}    $html           (required)
     * @uses        $this->parseHTML()          (required)
     * 
     * @since       1.0
     * @version     1.0
     */
    public function __construct($html){
        if(!empty($html)){
            $this->parse_HTML($html);
        }
    }
    
    /**
     * To String
     * 
     * @type    function
     * @access  public
     * 
     * @return  {string}    $html
     * 
     * @since       1.0
     * @version     1.0
     */
    public function __toString(){
        return $this->html;
    }
    
    /**
     * Add Notice
     * 
     * @type    function
     * @access  protected
     * 
     * @return  {string}    $html
     * 
     * @since       1.0
     * @version     1.0
     */
    protected function add_notice($raw, $compressed){
        $raw        = strlen($raw);
        $minified   = strlen($compressed);

        $savings    = ($raw-$minified) / $raw * 100;
        $savings    = round($savings, 2);
        
        if($this->notice_comment){
            $notice_comment =   sprintf(
                _x( "\r\n".'<!--WP HTML-Minifier compressed, size saved %1$s. From %2$s, now %3$s.-->'
                ,   'notice'
                ,   'swp'),
                "$savings%",
                "$raw bytes",
                "$minified bytes"
            );
            $this->html .= $notice_comment;
        }
    }
    
    /**
     * Minify HTML
     * 
     * @type        function
     * @access      protected
     * 
     * @param       {string}    $html           (required)
     * @return      {string}    $html
     * 
     * @since       1.0
     * @version     1.0
     */
    protected function minify_HTML($html){
        $pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        
        $overriding = false;
        $raw_tag = false;
        
        // Variable reused for output
        $html = '';
        
        foreach ($matches as $token){
            $tag = (isset($token['tag'])) ? strtolower($token['tag']) : null;
   		 
            $content = $token[0];

            if(is_null($tag)){
                if(!empty($token['script'])){
                    $strip = $this->compress_js;
                }else 
                if(!empty($token['style'])){
                    $strip = $this->compress_css;
                }else 
                if($content == '<!--wp-html-compression no compression-->'){
                    $overriding = !$overriding;

                    // Don't print the comment
                    continue;
                }else 
                if ($this->remove_comments){
                    if(!$overriding && $raw_tag != 'textarea'){
                        // Remove any HTML comments, except MSIE conditional comments
                        $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);
                    }
                }
            }
            else{
                if($tag == 'pre' || $tag == 'textarea'){
                    $raw_tag = $tag;
                } else 
                if($tag == '/pre' || $tag == '/textarea'){
                    $raw_tag = false;
                }
                else{
                    if($raw_tag || $overriding){
                         $strip = false;
                    }
                    else{
                        $strip = true;

                        // Remove any empty attributes, except: action, alt, content, src
                        $content = preg_replace('/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/', '$1', $content);

                        // Remove any space before the end of self-closing XHTML tags, JavaScript excluded
                        $content = str_replace(' />', '/>', $content);
                    }
                }
            }
   		 
            if ($strip){
                $content = $this->remove_white_space($content);
            }
   		 
            $html .= $content;
        }
   	 
        return $html;
    }
    
    /**
     * Parse HTML
     * 
     * @type        function
     * @access      public
     * 
     * @uses        $this->minify_HTML()            (required)
     * @uses        $this->minifyHTML()             (required)
     * 
     * @param       {string}    $html
     * 
     * @since       1.0
     * @version     1.0
     */
    public function parse_HTML($html){
        $this->html = $this->minify_HTML($html);
        
        if($this->notice_comment){
            $this->add_notice($html, $this->html);
        }
    }
    
    /**
     * Remove With Space
     * 
     * @type        function
     * @access      protected
     * 
     * @param       {string}    $str                (required)
     * @return      {string}    $str
     * 
     * @since       1.0
     * @version     1.0
     */
    protected function remove_white_space($str){
        $t = str_replace("\t", ' ', $str);
        
        $n = str_replace("\n",  '', $t);
        $r = str_replace("\r",  '', $n);
   	 
        while(stristr($r, '  ')){
            $r = str_replace('  ', ' ', $r);
        }
   	 
        return $r;
    }
    
}