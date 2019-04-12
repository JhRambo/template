<?php
    class Tpl{
        //模板文件的路径
        protected $viewDir = './view/';
        //生成缓存文件的路径
        protected $cacheDir = './cache/';
        //过期时间
        protected $lifeTime = 3600;
        //用来存放显示变量的数组
        protected $vars = [];

        //构造函数对成员变量初始化
        function __construct($viewDir=null, $cacheDir=null, $lifeTime=null)
        {
            if(!empty($viewDir)){
                if($this->checkDir($viewDir)){
                    $this->viewDir = $viewDir;
                }
            }
            if(!empty($cacheDir)){
                if($this->checkDir($cacheDir)){
                    $this->cacheDir = $cacheDir;
                }
            }
            if(!empty($lifeTime)){
                $this->lifeTime = $lifeTime;
            }
        }

        /**
         * 检查路径是否正确
         */
        protected function checkDir($dirPath)
        {
            if(!file_exists($dirPath) || !is_dir($dirPath)){
                return mkdir($dirPath,0755,true);
            }
            if(!is_writable($dirPath) || !is_readable($dirPath)){
                return chmod($dirPath,0755);
            }
        }
        //需要对外公开的方法
        //分配变量的方法
        function assign($name, $value)
        {
            $this->vars[$name] = $value;
        }

        /**
         *  展示缓存文件的方法
         *  $viewName 模板文件名
         * $isInclude 模板文件是仅仅需要编译，还是需要先编译再包含进来
         * $uri index.php?page=1，为了使缓存文件的文件名不重复，将文件名和uri拼接起来，再md5，生成缓存的文件名
         */
        function display($viewName, $isInclude = true, $uri = null)
        {
            //拼接模板文件的全路径
            $viewPath = rtrim($this->viewDir,'/').'/'.$viewName;
            //如果模板文件的全路径不存在，则终止
            if(!file_exists($viewPath)){
                die(); 
            }
            //拼接缓存文件的全路径
            $cacheName = md5($viewName.$uri).'php';
            $cachePath = rtrim($this->cacheDir,'/').'/'.$cacheName;

            //如果缓存文件不存在，则编译模板文件，生成缓存文件
            if(!file_exists($cachePath)){
                //编译模板文件
                $php = $this->complite($viewPath);
                //写入文件，生成缓存文件
                file_put_contents($cachePath,$php);
            }else{
                //1.如果缓存文件存在，先判断缓存文件是否过期
                $isTimeout = (filectime($cachePath) + $this->lifeTime) > time() ? false : true;
                //2.再判断缓存文件是否被修改过，如果被修改，需要重新生成
                $isChange = filemtime($viewPath) > filemtime($cachePath) ? true : false; 
                if($isTimeout || $isChange){
                    $php = $this->complite($viewPath);
                    file_put_contents($cachePath, $php);
                }
            }
            
            //判断缓存文件是否需要包含进来
            if($isInclude){
                //将变量解析出来
                extract($this->vars);
                //展示缓存文件
                include $cachePath;
            }
        }


        //编译html文件
        protected function complite($filePath)
        {
            //读取文件内容
            $html = file_get_contents($filePath);
            //正则替换
            $array = [
                '{$%%}' => '<?=$\1; ?>',
                '{foreach %%}' => '<?php foreach(\1): ?>',
                '{/foreach}' => '<?php endforeach ?>',
                '{include %%}' => '',
                '{if %%}' => '<?php if(\1): ?>'
            ];

            //遍历数组，将%%全部修改为 .+ ,然后执行正则替换
            foreach($array as $key => $value){
                //生成正则表达式
                $pattern = '#'.str_replace('%%', '(.+?)', preg_quote($key, '#')).'#';
                //实现正则替换
                if(strstr($pattern, 'include')){
                    $html = preg_replace_callback($pattern, [$this, 'parseInclude'], $html);
                }else{
                    $html = preg_replace($pattern, $value, $html);
                }
            }
            return $html;
        }

        //处理include正则表达式
        protected function parseInclude($data)
        {
            //将文件名两边的引号去掉
            $fileName = trim($data[1], '\'"');
            //然后不包含文件生成缓存
            $this->display($fileName, false);
            //拼接缓存文件全路径
            $cacheName = md5($fileName).'php';
            $cachePath = rtrim($this->cacheDir, '/').'/'.$cacheName;
            return '<?php include "'.$cachePath.'"?>';
        }
    }