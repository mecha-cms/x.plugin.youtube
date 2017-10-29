<?php

function fn_youtube($content, $lot = [], $that = null, $key = null) {
    $a = preg_split('#(<a(?:\s[^<>]+?)?>[\s\S]*?</a>|<[^<>]+?>)#', $content, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $s = "";
    $skip = 0;
    foreach ($a as $v) {
        if ($v && $v[0] === '<' && substr($v, -1) === '>') {
            if (substr($v, -4) === '</a>') {
                $a = HTML::apart($v);
                if (isset($a[2]['href']) && (
                    strpos($s, '://www.youtube.com/') !== false ||
                    strpos($s, '://www.youtu.be/') !== false ||
                    strpos($s, '://youtube.com/') !== false ||
                    strpos($s, '://youtu.be/') !== false
                )) {
                    $s .= fn_youtube_replace($a[2]['href']);
                } else {
                    $s .= $v;
                }
            } else {
                $s .= $v;
            }
            if (preg_match('#^<(?:code|kbd|pre|script|style|textarea)\b#', $v)) {
                $skip = 1;
            } else if ($v[1] === '/') {
                $skip = 0;
            }
        } else {
            $s .= $skip ? $v : fn_youtube_replace($v);
        }
    }
    return $s;
}

// TODO: Keep setting(s) from YouTube URL
function fn_youtube_replace($s) {
    if (
        strpos($s, '://www.youtube.com/') === false &&
        strpos($s, '://www.youtu.be/') === false &&
        strpos($s, '://youtube.com/') === false &&
        strpos($s, '://youtu.be/') === false
    ) {
        return $s;
    }
    return preg_replace('#\bhttps?://(?:www\.)?youtu(be\.com/watch\?v=|\.be/)(\w+)(\S*?)#i', '<p class="youtube" style="display:block;margin-right:0;margin-left:0;padding:25px 0 56.25%;position:relative;height:0;"><iframe style="display:block;margin:0;padding:0;border:0;position:absolute;top:0;left:0;width:100%;height:100%;" src="//www.youtube.com/embed/$2" allowfullscreen></iframe></p>', $s);
}

Hook::set([
    'comment.content',
    'page.content'
], 'fn_youtube', 2.1);