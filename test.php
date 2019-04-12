<?php

    include 'Tpl.php';

    $tpl = new Tpl();

    $title = '你好';
    $data = ['科比','韦德'];

    $abc = '模板引擎很难？';

    $tpl -> assign('title', $title);
    $tpl -> assign('data', $data);
    $tpl -> assign('abc', $abc);

    $tpl->display('test.html');