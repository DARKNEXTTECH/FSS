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

    define('APPVER', '1.0.0.2');
    define('APPVERREL', '25.05.2023');

    coreCheck();

    function openDB(){
        $db = new SQLite3("content/fss.db3");
        $db->exec('PRAGMA journal_mode=WAL;');
        $db->exec('PRAGMA threads = 8;');
        $db->busyTimeout(5000);

        return $db;
    }
    
    function randCodeInt(){
        $_nums = range(0, 9);
                    
        $_endCode = '';
                    
        for($i = 0; $i < 4; $i++){
            $_endCode .= $_nums[rand(0, 1)];
        }
        
        return $_endCode;
    }

    function checkURL($code){
       $db = openDB();
       
       $res = $db->querySingle("SELECT * FROM contents WHERE urlcode = '{$code}'", true);
       
       $db->close();
       return $res;

    }

    function coreCheck(){
        $_dirs = [
            'content',
            'content/_buffer',
            'content/data'
        ];
        
        foreach ($_dirs as $d) {
            if (!file_exists($d)) {
                mkdir($d);
            }
        }

        $_files = [
            'content/fss.db3' => 'db'
        ];

        foreach ($_files as $f => $t) {
            if(!file_exists($f)){
                switch ($t) {
                case 'db':
                    $db = new SQLite3($f);
                    $db->exec('PRAGMA journal_mode=WAL;');
                    $db->busyTimeout(5000);
                    
                    $db->query("CREATE TABLE IF NOT EXISTS dirs (
                        id             INTEGER     PRIMARY KEY
                                                UNIQUE
                                                NOT NULL,
                        name           TEXT (30)   NOT NULL
                                                UNIQUE,
                        isPassProtected INTEGER (1) DEFAULT (0),
                        pass           TEXT (64),
                        dirID INTEGER,
                        date_create INTEGER
                    );");
                    
                    $db->query("CREATE TABLE IF NOT EXISTS users (
                        id             INTEGER     PRIMARY KEY
                                                UNIQUE
                                                NOT NULL,
                        pass           TEXT (30)   NOT NULL,
                        dirID INTEGER REFERENCES dirs (id) DEFAULT(1)
                    );");
                    
                    $db->query("CREATE TABLE IF NOT EXISTS contents (
                        id       INTEGER PRIMARY KEY
                                        UNIQUE
                                        NOT NULL,
                        dirID    INTEGER REFERENCES dirs (id),
                        filename TEXT    NOT NULL,
                        filesize INTEGER NOT NULL,
                        urlCode  TEXT    NOT NULL
                                        UNIQUE,
                        date_create INTEGER,
                        date_update INTEGER,
                        tpe INTEGER
                    );");
                    
                    $db->exec("INSERT INTO dirs (id, name, isPassProtected, pass, dirID) VALUES (?, 'base', 0, '-', 1)");

                    $db->close();
                
                }
            }
        }
        
        if (!file_exists('.apx')) {
            $_basicCFG = [
                'storage' => [
                    'max' => 0,
                    'maxFileSize' => 0
                ],
        
                'colorSchema' => [
                    'accent' => '#2979ff',
                    'background' => '#111213',
                    'contrast' => '#1a1b1b'
                ],
        
                'accessPolicy' => [
                    'password' => hash('md5', randCodeInt()),
                    'digest' => false,
                ]
            ];
            
            $scan = scandir('content/data');
            
            unset($scan[0]);
            unset($scan[1]);
            
            if(count($scan) != 0){
                $_alias = [
                    '|#TYPE|' => 'Восстановление',
                    '|#DESC|' => 'Данная страница отображается в связи с утерей или повреждением конфигурации скрипта',
                ];
    
                $_data = file_get_contents('patterns/_install.html');
    
                foreach ($_alias as $sym => $val) {
                    $_data = str_replace($sym, $val, $_data);
                }
                
                echo $_data;
                die;
            } else {
                $_addr = $_SERVER['SERVER_NAME'];
                
                $_inCon = "
                    <div style = 'margin-top:20px;margin-bottom:20px;border:rgba(0, 0, 0, 0.05) solid 2px;padding:20px;'>
                        <h3>Проверьте данные:</h3>
                        <h4> > Адрес: <u>http(s)://{$_addr}</u><br>(Автоматическое управление SSL)</h4>
                        <div style = 'display:flex;align-items:center;'>
                            <h4> > Укажите кол-во свободного места в МБ. Оставьте значение «0», чтобы использовать всё доступное дисковое пространство (Только для выделенных серверов)</h4>
                            <input style = 'margin-left:20px;' type='number' id='freeMem' name='' min='128' step = '128' placeholder = 'Размер в мб'>
                            <h4 id = 'vidx' style = 'margin-left:20px;'></h4>
                        </div>
                        <div style = 'display:flex;align-items:center;'>
                            <h4> > Придумайте пароль для доступа ко всем файлам (Обычно используется 4-х значный код)</h4>
                            <input style = 'margin-left:20px;' type = 'password' placeholder = 'Пароль' id = 'code'>
                        </div>
                        <button onclick = 'startInstall()'>Начать установку</button>
                    </div>
                ";
                
                $_alias = [
                    '|#TYPE|' => 'Установка',
                    '|#DESC|' => 'Добро пожаловать в мастер установки скрипта FSS',
                    '|#CONTENT|' => $_inCon
                ];
    
                $_data = file_get_contents('patterns/_install.html');
    
                foreach ($_alias as $sym => $val) {
                    $_data = str_replace($sym, $val, $_data);
                }
                
                echo $_data;
                die;
            }
            
            #file_put_contents('.apx', bin2hex(serialize($_basicCFG)));
        } 
    }
    
    /*function getAppCFG(){
        $data = unserialize(hex2bin(file_get_contents('.apx')));
        
        return $data;
    }*/


    function getConfig(){
        if (file_exists('.apx')) {
            $data = unserialize(hex2bin(file_get_contents('.apx')));
        
            return $data;
        } else {
            echo 'SMTh WRONG';
        }
        
        /*$db = openDB();
        $res = $db->query('SELECT id, filename, filesize FROM contents');
        
        $total = 0;
        
        while ($row = $res->fetchArray()){
            if(!file_exists('content/data/' . $row['filename'])){
                $id = $row['id'];
                $db->exec("DELETE FROM contents WHERE id = {$id}");
            }
            
            $total += $row['filesize'];
        }
        
        return ['occuped' => $total, 'max' => 'FROM CFG'];*/
        
        #return ['occuped' => $total, 'max' => 'FROM CFG'];
        
    }

    # == FS ==

    function bytesConverter($bytes){
        $f = [
            40 => 'Тб',
            30 => 'Гб',
            20 => 'Мб',
            10 => 'Кб'
        ];

        # count via exp...

        foreach ($f as $exp => $postfix) {
            $pow = pow(2, $exp);
            if ($bytes >= $pow * 0.9) {
                return (round($bytes / $pow * 100) / 100) . ' ' . $postfix;
            }
        }

        return (round($bytes * 100) / 100) . ' Байт';
    }

    


?>