<?php 

    /*
        > DNT SOFTWARE 2023
        > Fast Save Storage
        
        Product license:
            Commercial use: not allowed
            Tech. Support: not applicable
            Warranty: not applicable
        Product info:
            Version: 1.0.0.1
            Channel: alpha
            Last update: 20.04.23
        Developer info:
            Name: DNT SOFTWARE
            Web: www.darknext.net
            VK: vk.com/tsmc0
            TG: tsmc0.t.me
            
        All right belong to DNTS
    */

    $method_list = [
        'upload',
        'download',
        'all',
        'code-check',
        'text-editor',
        'edit',
        'make-dir',
        'cat'
    ];

    $method = $_GET['route'];
    
    if(!isset($method)){
        header('Location: upload');
        die;
    }
    
    include_once('core.php'); 

    if(!in_array($method, $method_list)){
        $data = checkURL($method);

        if (count($data) != 0){
            $F = __DIR__ . '/content/data/' . $data['filename'];
            
            if(!file_exists($F)){
                echo "<h1>Файл не найден</h1><h3>Вы будете перенаправлены на главную странцу через 5 сек.</h3><script>setTimeout(() => {window.location.href = 'upload'}, 5000)</script>";
            }
            
            if(ob_get_level()){
                ob_end_clean();
            }
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($F));
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($F));
            
            readfile($F);
            
            exit;
        } else {
            header('Location: upload');
            die;
        }
    }

    include_once('render_engine.php');
    
    switch ($method) {
    case 'cat':
        echo _dirAllUI($_GET['id']);
    }

    switch ($method) {
    case 'make-dir':
        echo _makeDirUI();
    }

    switch ($method) {
    case 'upload':
        echo _uploadUI();
    }

    switch ($method) {
    case 'edit':
        if(!isset($_GET['id'])){
            header('Location: text-editor');
            die;
        }

        if(check()){
            echo _editUI($_GET['id']);
        } else {
            header('Location: upload');
            die;
        }  
    }

    function check(){
        include_once('core.php');
        
        $code = getConfig()['accessPolicy']['password'];

        if ((string)$_COOKIE['cdx'] == (string)$code) {
            return true;  
        } else {
            setcookie('cdx', '', -1, '/', $_SERVER['SERVER_NAME'], true, true);

            return false;
        }
    }

    switch ($method) {
    case 'text-editor':
        echo _textEditorUI($_GET['dest']);
    }

    switch ($method) {
    case 'download':
        echo _downloadUI();
    }

    switch ($method) {
    case 'code-check':
        echo _codeUI();
    }

    switch ($method) {
    case 'all':
        echo _allUI();
    }
?>