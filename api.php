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

    /*
        [FATAL]: 
            > 105 - Method not found in method list (user-error)

        [INFO]:
            > 200 - OK
        [WARN]: 
    
    */ 

    $method_list = [
        'get',
        'upload',
        'check-code',
        'deauth',
        'save',
        'install',
        'mkdir',
        'check-code-dir',
        'remove-file'

    ];

    $method = $_POST['method'];

    if (!in_array($method, $method_list)) {
        respond(['code' => 105, 'data' => 'Неизвестный метод']);
    }
    
    switch($method){
    case 'install':
        $freeSpace = $_POST['freeMEM'];
        $userPass = $_POST['code'];
        
        if(empty(ltrim($userPass))){
            respond(['code' => 105, 'data' => 'Сбой сохранения конфигурации']);
        }
        
        $pass = hash('md5', $userPass);
        
        $_basicCFG = [
            'storage' => [
                'max' => ($freeSpace * 1024) * 1024,
                'maxFileSize' => 0
            ],
        
            'colorSchema' => [
                'accent' => '#2979ff',
                'background' => '#111213',
                'contrast' => '#1a1b1b'
            ],
        
            'accessPolicy' => [
                'password' => hash('md5', $userPass),
                'digest' => false,
            ]
        ];
        
        if(file_put_contents('.apx', bin2hex(serialize($_basicCFG)))){
            respond(['code' => 200, 'data' => 'OK']);
        } else {
            respond(['code' => 105, 'data' => 'Сбой сохранения конфигурации']);
        }
    }
    
    include_once('core.php');
    
    switch($method){
    case 'remove-file':
        $id = (int)$_POST['id'];
        
        if(empty(ltrim($id))){
            respond(['code' => 105, 'data' => 'Нет идентификатора']);
        }
        
        $db = openDB();
        $resFile = $db->query("SELECT * FROM contents WHERE id = {$id}");
        $res = $resFile->fetchArray(SQLITE3_ASSOC);
        
        if($res){
            $path = getcwd() . '/content/data/' . $res['filename'];
            
            if(file_exists($path)){
                if(unlink($path)){
                   $db->query("DELETE FROM contents WHERE id = {$id}");
                   
                   respond(['code' => 200, 'data' => 'Файл удалён успешно']); 
                } else {
                   respond(['code' => 105, 'data' => 'Ошибка очистки данных']);
                }
            } else {
                $db->query("DELETE FROM contents WHERE id = {$id}");
            }
            
            $db->close();
        } else {
            respond(['code' => 105, 'data' => 'Ошибка получения данных']);
        }
    }
    
    switch($method){
    case 'check-code-dir':
        $dirID = $_POST['dirID'];
        $code = $_POST['code'];
        
        if(empty(ltrim($dirID))){
            respond(['code' => 105, 'data' => 'Не найден идентификатор каталога']);
        }
        
        if(empty(ltrim($code))){
            respond(['code' => 105, 'data' => 'Не указан пароль от каталога']);
        }
        
        $db = openDB();
        $resDirs = $db->query("SELECT pass FROM dirs WHERE id = {$dirID}");
        $res = $resDirs->fetchArray();
        
        if(!$res){
           respond(['code' => 105, 'data' => 'Каталог не существует']); 
        } else {
           if(hash('md5', $code) == $res['pass']){
              setcookie('dir_' . $dirID, $res['pass'], 0, '/', $_SERVER['SERVER_NAME'], true, true);
              
              respond(['code' => 200, 'data' => 'Доступ разрешён']);  
           } else {
              respond(['code' => 105, 'data' => 'Неправильный пароль']);
           }
        }
    }
    
    switch($method){
    case 'mkdir':
        $dirname = $_POST['name'];
        $pass = $_POST['pass'];
        $dirID = $_POST['dest'];
        
        $_namesBlackList = [
            'base'    
        ];
        
        if(in_array($dirname, $_namesBlackList)){
            respond(['code' => 105, 'data' => 'Недопустимое имя каталога']);
        }
        
        if($dirID != 1){
            $db = openDB();
            $resDirs = $db->query("SELECT * FROM dirs WHERE id = {$dirID}");
            $res = $resDirs->fetchArray();
            
            $path = getcwd() . '/content/data/' . $res['name'] . '/';
            
            $db->close();
        } else {
            $path = getcwd() . '/content/data/';
        }
        
        if(empty(ltrim($dirname))){
            respond(['code' => 105, 'data' => 'Укажите имя каталога']);
        }
        
        if(!file_exists($path . $dirname)){
            if(mkdir($path . $dirname)){
                $db = openDB();
                
                $isPass = empty(trim($pass));
                
                if(!$isPass){
                    $pass = "'" . hash('md5', $pass) . "'";
                    $isPass = 1;
                } else {
                    $pass = "''";
                    $isPass = 0;
                }
                
                $dc = (int)time();
                
                $r = $db->exec("INSERT INTO dirs (id, name, isPassProtected, pass, dirID, date_create) VALUES (?, '{$dirname}', {$isPass}, {$pass}, {$dirID}, {$dc})");
                
                if($r){
                    respond(['code' => 200, 'data' => 'Каталог успешно создан']);
                } else {
                    if(file_exists($path . $dirname)){rmdir($path . $dirname);}
                    
                    respond(['code' => 205, 'data' => 'Каталог не создан']);
                }
                
                $db->close();
                
                respond(['code' => 200, 'data' => 'Каталог успешно создан']);
            } else {
                respond(['code' => 205, 'data' => 'Не удалось создать каталог']);
            }
        } else {
            respond(['code' => 205, 'data' => 'Каталог с таким именем уже существует']);
        }
        
    }
    
    switch ($method) {
    case 'save':
        $name = $_POST['name'];
        $content = $_POST['content'];
        $id = $_POST['id'];
        $dirID = $_POST['dest'];

        if(empty(ltrim($name))){
            respond(['code' => 205, 'data' => 'Укажите имя заметки']);
        }
        
        if($dirID != 1){
            $db = openDB();
            $resDirs = $db->query("SELECT * FROM dirs WHERE id = {$dirID}");
            $res = $resDirs->fetchArray();
            
            $path = getcwd() . '/content/data/' . $res['name'] . '/';
            
            $db->close();
        } else {
            $path = getcwd() . '/content/data/';
        }
        
        if(file_exists($path . $name . '.fsst')){
            if (file_put_contents($path . $name . '.fsst', $content)) {
                $db = openDB();

                $t = time();
                
                $db->exec("UPDATE contents SET date_update = {$t} WHERE urlCode = '{$id}'");

                $db->close();
                
                respond(['code' => 200, 'data' => 'Данные обновлены']);
            } else {
                respond(['code' => 205, 'data' => 'Не удалось сохранить']);
            }
        } else {
            if(file_put_contents($path . $name . '.fsst', $content)){
                $db = openDB();

                $size = filesize($path . $name . '.fsst');
                $_syms = range('A', 'Z');
                $_nums = range(0, 9);

                $_endCode = '';

                for ($i = 0; $i < rand(4, 6); $i++) {
                    $_types = [
                        0 => $_syms,
                        1 => $_nums
                    ];

                    $_endCode .= $_types[rand(0, 1)][rand(0, count($_types[rand(0, 1)]) - 1)];
                }

                $t = time();

                $name = $name . '.fsst';

                $r = $db->exec("INSERT INTO contents (id, dirID, filename, filesize, urlCode, date_create, tpe) VALUES (?, {$dirID}, '{$name}', {$size}, '{$_endCode}', {$t}, 1)");

                $db->close();
                respond(['code' => 200, 'data' => $r]);
            } else {
                respond(['code' => 205, 'data' => 'Не удалось сохранить']);
            }
        }
    }

    switch ($method) {
    case 'get':
        $search = $_POST['data'];
        
        if(ltrim(empty($search))){
            respond(['code' => 205, 'data' => 'Укажите ID файла']);
        }
        
        if(str_starts_with($search, 'https://') or str_starts_with($search, 'http://')){
            $search = str_replace('https://' . $_SERVER['SERVER_NAME'] . '/', '', $search);
        }
        
        $db = openDB();
        
        $res = $db->prepare("SELECT * FROM `contents` WHERE `urlCode` = :se;");
        $res->bindValue(':se', $search);
        $r = $res->execute();
        $x = $r->fetchArray(SQLITE3_ASSOC);
        
        if(is_array($x)){
            $url = 'https://' . $_SERVER['SERVER_NAME'] . '/' . $x['urlCode'];
            
            respond(['code' => 200, 'data' => $x + ['fullPath' => 'https://' . $_SERVER['SERVER_NAME'] . '/content/data/' . $x['filename'], 'qr' => "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={$url}"]]);
        } else {
            respond(['code' => 205, 'data' => 'Файл не найден']);
        }
    }
    
    switch ($method) {
    case 'deauth':
        setcookie('cdx', '', -1, '/', $_SERVER['SERVER_NAME'], true, true);
        
        respond(['code' => 200, 'data' => 'OK']);
    }
    
    switch ($method) {
    case 'check-code':
        include_once('core.php');
        
        $code = getConfig()['accessPolicy']['password'];
        
        $cd = hash('md5', $_POST['code']);
        
        if($code == $cd){
            setcookie('cdx', $cd, 0, '/', $_SERVER['SERVER_NAME'], true, true);
            
            respond(['code' => 200, 'data' => 'OK']);
        } else {
            respond(['code' => 105, 'data' => 'NOT OK']);
        }
        
        
    }
    
    function checkCode(){
        $code = getConfig()['accessPolicy']['password'];
        
        if($_COOKIE['cdx'] != $code){
            setcookie('cdx', '', -1, '/', $_SERVER['SERVER_NAME'], true, true);
            
            return false;
        } else {
            return true;
        }
    }

    switch ($method) {
    case 'upload':
        $file = $_FILES['file'];
        $_files = [];

        foreach ($file as $k => $l) {
            foreach ($l as $i => $v) {
                $_files[$i][$k] = $v;
            }
        }	

        foreach($_files as $f){
            if($f['error'] == 0){
                if(move_uploaded_file($f['tmp_name'], getcwd() . '/content/data/' . $f['name'])){
                    $db = openDB();
                    
                    $name = $f['name'];
                    $size = $f['size'];
                    
                    $_syms = range('A', 'Z');
                    $_nums = range(0, 9);
                    
                    $_endCode = '';
                    
                    for($i = 0; $i < rand(4, 6); $i++){
                        $_types = [
                            0 => $_syms,
                            1 => $_nums
                        ];
                        
                        $_endCode .= $_types[rand(0, 1)][rand(0, count($_types[rand(0, 1)])-1)];
                    }
                    
                    $t = time();
                    
                    $db->exec("DELETE FROM contents WHERE filename = '{$name}'");

                    $db->exec("INSERT INTO contents (id, dirID, filename, filesize, urlCode, date_create, tpe) VALUES (?, 1, '{$name}', {$size}, '{$_endCode}', {$t}, 0)");
                    
                    $url = "https://" . $_SERVER['SERVER_NAME'] . '/' . $_endCode;

                    $db->close();
                    respond(['code' => 200, 'data' => ['code' => $_endCode, 'qr' => "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={$url}", 'url' => 'https://' . $_SERVER['SERVER_NAME'] . '/' . $_endCode]]);
                }
            }
        }
    }

    # == METHODS == 

    function respond($data){
        echo json_encode($data);
        die;
    }


?>