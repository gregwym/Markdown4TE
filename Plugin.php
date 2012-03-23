<?php
/**
 * Markdown编辑器 (修改自<a href="http://forum.typecho.org/topic.php?id=1218" target="_blank">ichuan</a>的0.1版本)
 * 
 * @package Markdown
 * @author Greg Wang
 * @version 0.2.1
 * @dependence 9.9.2-*
 * @link http://dolast.com
 */

class Markdown_Plugin implements Typecho_Plugin_Interface
{
    protected static $markdownify = null;

    public static function activate()
    {
        // replace richEditor
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('Markdown_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('Markdown_Plugin', 'render');
        // hook for markdown filter
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('Markdown_Plugin', 'saveHook');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('Markdown_Plugin', 'saveHook');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('Markdown_Plugin', 'loadHook');
    }

    public static function deactivate(){}

    public static function saveHook($contents, $obj)
    {
        require_once dirname(__FILE__) . '/markdown.php';
        $contents['text'] = Markdown($contents['text']);
        return $contents;
    }

    public static function strEndWith($str, $end)
    {
        return (strpos($str, $end) + strlen($end) == strlen($str));
    }

    public static function loadHook($contents, $obj)
    {
        $uri = @parse_url($_SERVER['REQUEST_URI']);
        if (is_array($uri) && (self::strEndWith($uri['path'], '/admin/write-post.php')) ||
                               self::strEndWith($uri['path'], '/admin/write-page.php')){
            require_once dirname(__FILE__) . '/markdownify/markdownify.php';
            if (is_null(self::$markdownify))
                self::$markdownify = new Markdownify;
            $contents['text'] = self::$markdownify->parseString($contents['text']);
        }
        return $contents;
    }

    public static function render()
    {
        $options = Helper::options();
        $js = Typecho_Common::url('Markdown/pagedown/wmd.js', $options->pluginUrl);
        $converter_js = Typecho_Common::url('Markdown/pagedown/Markdown.Converter.js', $options->pluginUrl);
        $senitizer_js = Typecho_Common::url('Markdown/pagedown/Markdown.Sanitizer.js', $options->pluginUrl);
        $editor_js = Typecho_Common::url('Markdown/pagedown/Markdown.Editor.js', $options->pluginUrl);
        $css = Typecho_Common::url('Markdown/pagedown/pagedown.css', $options->pluginUrl);
        $preview = _t('预览');
        echo <<<EOF
<link rel="stylesheet" type="text/css" href="${css}" />
<script type="text/javascript" src="${converter_js}"></script>
<script type="text/javascript" src="${senitizer_js}"></script>
<script type="text/javascript" src="${editor_js}"></script>
<script type="text/javascript">

    var 
        p_bar = new Element('p'), 
        wmd_button_bar = new Element('div', {id: 'wmd-button-bar'}), 
        wmd_preview = new Element('div', {id: 'wmd-preview', class: 'wmd-preview'}),
        p_preview = new Element('p'),
        label = new Element('label', {class: 'typecho-label', for: 'wmd-preview', text: '${preview}'}),
        textarea = $('text').setProperty('id', 'wmd-input')
                            .setProperty('class', 'wmd-input')
                            .setProperty('style', '');
        p_text = textarea.getParent();
    
    wmd_button_bar.inject(p_bar);
    p_bar.inject(p_text, 'before');
    wmd_preview.inject(p_preview);
    p_preview.inject(p_text, 'after');
    label.inject(p_text, 'after');
    
    var converter1 = Markdown.getSanitizingConverter();
    var editor1 = new Markdown.Editor(converter1);
    editor1.run();

    var textEditor = new Typecho.textarea('#wmd-input', {
        autoSaveTime: 30,
        resizeAble: true,
        autoSave: false,
        autoSaveMessageElement: 'auto-save-message',
        autoSaveLeaveMessage: '您的内容尚未保存, 是否离开此页面?',
        resizeUrl: 'http://blog.gregwym.info/action/ajax'
    });

    /** 这两个函数在插件中必须实现 */
    var insertImageToEditor = function (title, url, link, cid) {
        textEditor.setContent('<a href="' + link + '" title="' + title + '"><img src="' + url + '" alt="' + title + '" /></a>', '');
        new Fx.Scroll(window).toElement($(document).getElement('textarea#wmd-input'));
    };
    
    var insertLinkToEditor = function (title, url, link, cid) {
        textEditor.setContent('<a href="' + url + '" title="' + title + '">' + title + '</a>', '');
        new Fx.Scroll(window).toElement($(document).getElement('textarea#wmd-input'));
    };
</script>
EOF;
    }

    public static function config(Typecho_Widget_Helper_Form $form){}

    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}
