<?php

function fn_youtube($content, $lot = [], $that = null, $key = null) {
    $youtube_pattern = 'https?://(?:www\.)?(?:youtube\.com|youtu\.be)/[^\s<>]+';
    $ignore = [
        '<pre(?:\s[^<>]+?)?>[\s\S]*?</pre>',
        '<code(?:\s[^<>]+?)?>[\s\S]*?</code>',
        '<script(?:\s[^<>]+?)?>[\s\S]*?</script>',
        '<style(?:\s[^<>]+?)?>[\s\S]*?</style>',
        '<textarea(?:\s[^<>]+?)?>[\s\S]*?</textarea>'
    ];
    $take = [
        // An anchor in a paragraph tag, a YouTube URL in a paragraph tag
        '<p(?:\s[^<>]+?)?>\s*(?:<a(?:\s[^<>]+?)?>[\s\S]*?<\/a>|' . $youtube_pattern . ')\s*<\/p>',
        // An anchor in its own line, a YouTube URL in its own line
        '(?<=^|\n)(?:[ \t]*<a(?:\s[^<>]+?)?>[^\n]*?<\/a>[ \t]*|' . $youtube_pattern . ')(?=\n|$)'
    ];
    $part = preg_split('#(' . implode('|', $ignore) . '|' . implode('|', $take) . ')#', $content, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $s = "";
    foreach ($part as $v) {
        // `<p> ... </p>`
        if (substr($v, -4) === '</p>') {
            // `<p><a href="{$link}">text</a></p>`
            if (
                strpos($v, '</a>') !== false &&
                strpos($v, ' href="') !== false &&
                preg_match('#<a(?:\s[^<>]+?)?>[\s\S]*?<\/a>#', $v, $m)
            ) {
                $test = HTML::apart($m[0]);
                if ($test && isset($test[2]['href']) && preg_match('#^' . $youtube_pattern . '$#', $test[2]['href'])) {
                    $s .= fn_youtube_replace($test[2]['href'], 'p') ?: $v;
                } else {
                    $s .= $v;
                }
            // `<p>{$link}</p>`
            } else if (
                strpos($v, '://') !== false &&
                preg_match('#' . $youtube_pattern . '#', $v, $m)
            ) {
                $s .= fn_youtube_replace($m[0], 'p') ?: $v;
            } else {
                $s .= $v;
            }
        // `<a href="{$link}">text</a>`
        } else if (
            substr($v, -4) === '</a>' &&
            strpos($v, ' href="') !== false
        ) {
            $test = HTML::apart($v);
            if ($test && isset($test[2]['href']) && preg_match('#^' . $youtube_pattern . '$#', $test[2]['href'])) {
                $s .= fn_youtube_replace($test[2]['href'], 'p') ?: $v;
            } else {
                $s .= $v;
            }
        // `{$link}`
        } else if (
		    $v &&
			$v[0] !== '<' &&
			substr($v, -1) !== '>' &&
            strpos($v, '://') !== false &&
            strpos($v, "\n") === false &&
            preg_match('#' . $youtube_pattern . '#', $v, $m)
        ) {
            $s .= fn_youtube_replace($m[0]) ?: $v;
        } else {
            $s .= $v;
        }
    }
    return $s;
}

function fn_youtube_replace($href, $t = 'span') {
    $u = parse_url($href);
    parse_str(isset($u['query']) ? $u['query'] : "", $q);
    $q = array_replace_recursive(Plugin::state(__DIR__, 'q') ?: [], $q);
    $id = isset($q['v']) ? $q['v'] : null; // `https://www.youtube.com/watch?v={$id}`
    if (!empty($u['path'])) {
        if (strpos($href, '/v/') !== false) {
            $id = explode('/', trim($u['path'], '/'))[1]; // `https://www.youtube.com/v/{$id}`
        } else if (strpos($href, '/embed/') !== false) {
            $id = explode('/', trim($u['path'], '/'))[1]; // `https://www.youtube.com/embed/{$id}`
        } else if (strpos($href, '/user/') !== false) {
            // TODO
        } else if (strpos($href, '/channel/') !== false) {
            // TODO
        } else if (strpos($href, '.be/') !== false) {
            $id = explode('/', trim($u['path'], '/'))[0]; // `https://youtu.be/{$id}`
        } else {
            unset($q['v']);
        }
    } else {
        unset($q['v']);
    }
    if (isset($q['height'])) {
        if (is_numeric($q['height']) && strpos($q['height'], '%') === false) {
            $y = 'padding:0;height:' . $q['height'] . 'px';
        } else {
            $y = 'padding:25px 0 ' . $q['height'] . ';height:0';
        }
        if (isset($q['width'])) {
            $y .= ';width:' . (is_numeric($q['width']) ? $q['width'] . 'px' : $q['width']);
            unset($q['width']);
        }
        unset($q['height']);
    } else {
        $y = 'padding:25px 0 56.25%;height:0';
    }
    $q = http_build_query($q);
    $q = $q ? '?' . $q : "";
    return $id ? '<' . $t . ' class="youtube" style="display:block;margin-right:0;margin-left:0;' . $y . ';position:relative;"><iframe style="display:block;margin:0;padding:0;border:0;position:absolute;top:0;left:0;width:100%;height:100%;" src="//www.youtube.com/embed/' . $id . $q . '" allowfullscreen></iframe></' . $t . '>' : "";
}

Hook::set([
    'comment.content',
    'page.content'
], 'fn_youtube', 2.1);