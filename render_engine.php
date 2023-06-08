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

    include_once('core.php');
    
    $CLOUD_DATA = getConfig();

    if(!isset($_COOKIE['timezone'])){
        $ip = $_SERVER['REMOTE_ADDR'];
        $ipInfo = file_get_contents('http://ip-api.com/json/' . $ip);
        $ipInfo = json_decode($ipInfo);
        $timezone = $ipInfo->timezone;
        date_default_timezone_set($timezone);

        setcookie('timezone', $timezone, time() * 2, '/', $_SERVER['SERVER_NAME'], true, true);
    } else {
        date_default_timezone_set($_COOKIE['timezone']);
    }
    
    function _makeDirUI(){
        global $CLOUD_DATA;
        
        if(is_null($_GET['dest'])){
            $did = 1;
        } else {
            $did = $_GET['dest'];
        }

        $_alias = [
            '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
            '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
            '|#APPVER|' => APPVER,
            '|#APPVERDATE|' => APPVERREL,
            '|#DIRID|' => $did
        ];
        
        $_data = file_get_contents('patterns/_mkdir.html');

        foreach ($_alias as $sym => $val) {
            $_data = str_replace($sym, $val, $_data);
        }

        return $_data;
    }

    function _uploadUI(){
        global $CLOUD_DATA;

        $_alias = [
            '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
            '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
            '|#APPVER|' => APPVER,
            '|#APPVERDATE|' => APPVERREL,
        ];
        
        $_data = file_get_contents('patterns/_upload.html');

        foreach ($_alias as $sym => $val) {
            $_data = str_replace($sym, $val, $_data);
        }

        return $_data;
    }

    function _downloadUI(){
        global $CLOUD_DATA;
        
        $_alias = [
            '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
            '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
            '|#APPVER|' => APPVER,
            '|#APPVERDATE|' => APPVERREL,
        ];

        $_data = file_get_contents('patterns/_download.html');

        foreach ($_alias as $sym => $val) {
            $_data = str_replace($sym, $val, $_data);
        }

        return $_data;
    }

    function _textEditorUI($dest = 1){
        global $CLOUD_DATA;
        
        if(is_null($dest)){
            $dest = 1;
        }
        
        $_alias = [
            '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
            '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
            '|#APPVER|' => APPVER,
            '|#APPVERDATE|' => APPVERREL,
            '|#DIRID|' => $dest
        ];

        $_data = file_get_contents('patterns/_text-editor.html');

        foreach ($_alias as $sym => $val) {
            $_data = str_replace($sym, $val, $_data);
        }

        return $_data;
    }

    function _editUI($id){
        global $CLOUD_DATA;

        if(!is_null($id)){
            $data = checkURL($id);
            
            $dirID = $data['dirID'];
            
            if($dirID != 1){
                $db = openDB();
                $resDirs = $db->query("SELECT * FROM dirs WHERE id = {$dirID}");
                $res = $resDirs->fetchArray();
                
                $path = getcwd() . '/content/data/' . $res['name'] . '/';
                
                $db->close();
            } else {
                $path = getcwd() . '/content/data/';
            }
            
            $content = htmlentities(file_get_contents($path . $data['filename']));

            $name = str_replace('.fsst', '', $data['filename']);

        }

        $_alias = [
            '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
            '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
            '|#APPVER|' => APPVER,
            '|#APPVERDATE|' => APPVERREL,
            '|#NAME|' => $name,
            '|#DATAX|' => $content,
            '|#FID|' => $id
        ];

        $_data = file_get_contents('patterns/_edit.html');

        foreach ($_alias as $sym => $val) {
            $_data = str_replace($sym, $val, $_data);
        }

        return $_data;
    }

    function _codeUI(){
        global $CLOUD_DATA;
        
        $_alias = [
            '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
            '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
            '|#APPVER|' => APPVER,
            '|#APPVERDATE|' => APPVERREL,
        ];

        $_data = file_get_contents('patterns/_code.html');

        foreach ($_alias as $sym => $val) {
            $_data = str_replace($sym, $val, $_data);
        }

        return $_data;
    }
    
    /*function check(){
        $code = getConfig()['accessPolicy']['password'];

        if ((string)$_COOKIE['cdx'] == (string)$code) {
            return true;  
        } else {
            setcookie('cdx', '', -1, '/', $_SERVER['SERVER_NAME'], true, true);

            return false;
        }
    }*/
    
    function _dirAllUI($id){
        if(is_null($id)){
            header('Location: all');
            die;
        }
        
        global $CLOUD_DATA;
        
        $_dirPATH = 'Базовый раздел / ';
        
        $db = openDB();
        
        $resDirs = $db->query("SELECT * FROM dirs WHERE id = {$id}");
        
        $res = $resDirs->fetchArray();
        
        $_dname = $res['name'];
        $_did = $res['id'];
        $_protected = $res['isPassProtected'];
        $_pass = $res['pass'];
        
        $_dirPATH .= $_dname . ' / ';
        
        function renderAllFiles($info, $_dirPATH){
            $_cards = '';
            
            $ofID = $info['id'];
            
            $db = openDB();
            $res = $db->query("SELECT id, filename, filesize, urlCode, date_create, date_update, tpe FROM contents WHERE dirID = {$ofID} ORDER BY date_create DESC");
            
            $res_dirs = $db->query("SELECT * FROM dirs WHERE dirID = {$ofID} ORDER BY id DESC");
            
            while($dirRow = $res_dirs->fetchArray()){
                $dName = $dirRow['name'];
                $did = $dirRow['id'];
                $is = $dirRow['isPassProtected'];
                
                //$_dirPATH .= $dName . ' / ';
                
                if($is){
                    $isx = 'Каталог с паролем';
                } else {
                    $isx = 'Каталог без пароля';
                }
                
                $dirSize = bytesConverter(countBytes('content/data/' . $dName));
                $time = 'Создан в ' . date('d.m.Y H:i', $dirRow['date_create']);
                
                $_cards .= "
                <div class = 'card-dir' onclick = 'navTo(`cat?id={$did}`)'>
                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' class = 'catt_card'>
                <path class = 'catt_cx' fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'd='M22 19V9a2 2 0 0 0-2-2h-6.764a2 2 0 0 1-1.789-1.106l-.894-1.788A2 2 0 0 0 8.763 3H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2Z' /></svg>
                <div class = 'dir-data'>
                <h4 class='cfn'>{$dName}</h4>
                <h5 class='cfd'>Общий размер: {$dirSize}</h5>
                <h5 class='cfd'>{$time}</h5>
                <h5 class='cfd'>{$isx}</h5>
                </div>
                </div>
                ";
            }
            
            while ($row = $res->fetchArray()){
                if(strlen($row['filename']) <= 35){
                    $end = '';
                } else {
                    $end = '...';
                }
                
                $name = rtrim(mb_strimwidth($row['filename'], 0, 30)) . $end;
                $size = bytesConverter($row['filesize']);
                $id = $row['urlCode'];
                
                if(is_null($row['date_update'])){
                    $time_update = 'Последнее обновление в ' . date('d.m.Y H:i', $row['date_create']);
                } else {
                    $time_update = 'Последнее обновление в ' . date('d.m.Y H:i', $row['date_update']);
                }
    
                if($row['tpe'] == 0){
                    $svg = "
                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' class = 'dirb'>
                                <g class = 'dir' fill='none' stroke='currentColor' stroke-linejoin='round' stroke-width='2'>
                                    <path stroke-linecap='round'
                                        d='M4 4v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.342a2 2 0 0 0-.602-1.43l-4.44-4.342A2 2 0 0 0 13.56 2H6a2 2 0 0 0-2 2Zm5 9h6m-6 4h3' />
                                    <path d='M14 2v4a2 2 0 0 0 2 2h4' />
                                </g>
                            </svg>";
    
                    $event = "navTo(`{$id}`)";
                    $time = 'Загружен в ' . date('d.m.Y H:i', $row['date_create']);
                } elseif($row['tpe'] == 1){
                    $svg = "
                    <svg class = 'editb' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><g class = 'editbb' fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><path d='m16.474 5.408l2.118 2.117m-.756-3.982L12.109 9.27a2.118 2.118 0 0 0-.58 1.082L11 13l2.648-.53c.41-.082.786-.283 1.082-.579l5.727-5.727a1.853 1.853 0 1 0-2.621-2.621Z'/><path d='M19 15v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3'/></g></svg>
                    ";
    
                    $event = "navTo(`edit?id={$id}`)";
                    $time = 'Создана в ' . date('d.m.Y H:i', $row['date_create']);
                    $name = str_replace('.fsst', '', rtrim(mb_strimwidth($row['filename'], 0, 30)));
                }
                
                $_cards .= "
                    <div class='card-dir'>
                        {$svg}
                        <div class='dir-data' onclick = '{$event}'>
                            <h4 class='cfn'>{$name}</h4>
                            <h5 class='cfd'>{$size}</h5>
                            <h5 class='cfd'>{$time}</h5>
                            <h5 class='cfd'>{$time_update}</h5>
                        </div>
                    </div>
                ";
            }
    
            if($_cards == ''){
                $_cards = '<h3 class = "nod">У вас нет файлов</h3>';
            }
            
            $_dname = $info['name'];
            $_did = $info['id'];
            
            $_alias = [
                '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
                '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
                '|#APPVER|' => APPVER,
                '|#APPVERDATE|' => APPVERREL,
                '|#DIRNAME|' => $_dname,
                '|#PATH|' => $_dirPATH,
                '|#CARDS|' => $_cards,
                '|#DIRID|' => $_did,
            ];
    
            $_data = file_get_contents('patterns/_allDir.html');
    
            foreach ($_alias as $sym => $val) {
                $_data = str_replace($sym, $val, $_data);
            }
    
            echo $_data;
        }
        
        if(isset($_COOKIE['dir_' . $_did])){
            if($_COOKIE['dir_' . $_did] == $_pass){
                renderAllFiles($res, $_dirPATH);
                return;
            } 
        }
        
        if(!$res){
            header('Location: all');
            die;
        }
        
        if($_protected){
            $_alias = [
                '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
                '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
                '|#APPVER|' => APPVER,
                '|#APPVERDATE|' => APPVERREL,
                '|#DIRNAME|' => $_dname,
                '|#DIRID|' => $_did,
                '|#PATH|' => $_dirPATH,
            ];
    
            $_data = file_get_contents('patterns/_dirCode.html');
    
            foreach ($_alias as $sym => $val) {
                $_data = str_replace($sym, $val, $_data);
            }
    
            return $_data;
        } else {
            return renderAllFiles($res, $_dirPATH);
        }
        
        /*if(!isset($_COOKIE['cdx'])){
            header('Location: code-check');
            die;
        } else {
            
        }*/
    }
    
    # =========================================================
    
    function _allUI(){
        if(!isset($_COOKIE['cdx'])){
            header('Location: code-check');
            die;
        } else {
            $button = "<div class = 'red-bt' onclick = 'deauth()' id = 'getbt'><h3 class = 'red-bt-text' id = 'getbtt'>Выйти</h3></div>";
        }
        
        global $CLOUD_DATA;
        
        $cards = '';
        
        $db = openDB();
        $res = $db->query('SELECT id, filename, filesize, urlCode, date_create, date_update, tpe FROM contents WHERE dirID = 1 ORDER BY date_create DESC');
        
        $res_dirs = $db->query('SELECT * FROM dirs WHERE dirID = 1 AND name != "base" ORDER BY id DESC');
        
        while($dirRow = $res_dirs->fetchArray()){
            $dName = $dirRow['name'];
            $did = $dirRow['id'];
            $is = $dirRow['isPassProtected'];
            
            if($is){
                $isx = 'Каталог с паролем';
            } else {
                $isx = 'Каталог без пароля';
            }
            
            $dirSize = bytesConverter(countBytes('content/data/' . $dName));
            $time = 'Создан в ' . date('d.m.Y H:i', $dirRow['date_create']);
            
            $cards .= "
            <div class = 'card-dir'onclick='navTo(`cat?id={$did}`)'>
            <div class = 'preview'>
            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' class = 'catt_card'>
            <path class = 'catt_cx' fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'd='M22 19V9a2 2 0 0 0-2-2h-6.764a2 2 0 0 1-1.789-1.106l-.894-1.788A2 2 0 0 0 8.763 3H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2Z' /></svg>
            </div>
            <div class = 'dir-data'>
            <h4 class='cfn'>{$dName}</h4>
            <h5 class='cfd'>Общий размер: {$dirSize}</h5>
            <h5 class='cfd'>{$time}</h5>
            <h5 class='cfd'>{$isx}</h5>
            </div>
            </div>
            ";
        }
        
        $_FIDPAGE = 0;
        
        while ($row = $res->fetchArray()){
            if(strlen($row['filename']) <= 35){
                $end = '';
            } else {
                $end = '...';
            }
            
            $_origin = $row['filename'];
            $name = rtrim(mb_strimwidth($row['filename'], 0, 30)) . $end;
            $size = bytesConverter($row['filesize']);
            $id = $row['urlCode'];
            
            if(is_null($row['date_update'])){
                $time_update = 'Последнее обновление в ' . date('d.m.Y H:i', $row['date_create']);
            } else {
                $time_update = 'Последнее обновление в ' . date('d.m.Y H:i', $row['date_update']);
            }

            if($row['tpe'] == 0){
                $event = "navTo(`{$id}`)";
                
                if (str_contains($_origin, '.png') or str_contains($_origin, '.jpg')){
                    $URL = '/content/data/' . $_origin;
                    $_fullURL = $SERVER['SERVER_NAME'] . '/content/data/' . $_origin; 
                    $_getURL = 'https://' . $_SERVER['SERVER_NAME'] . '/' . $id;
                    
                    $svg = "<div class = 'preview exd' style = 'background-image:url({$_fullURL})!important;object-fit:contain;'>
                    <div class = 'img-tools'>
                    <svg class = 'copy-path-svg' onclick = 'openImgPreview(`{$URL}`)' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><g fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><path d='M21.257 10.962c.474.62.474 1.457 0 2.076C19.764 14.987 16.182 19 12 19c-4.182 0-7.764-4.013-9.257-5.962a1.692 1.692 0 0 1 0-2.076C4.236 9.013 7.818 5 12 5c4.182 0 7.764 4.013 9.257 5.962Z'/><circle cx='12' cy='12' r='3'/></g></svg>
                    <svg class = 'copy-path-svg' onclick = 'copyToBuff(`{$_getURL}`)' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><g fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><path d='M8 4v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.242a2 2 0 0 0-.602-1.43L16.083 2.57A2 2 0 0 0 14.685 2H10a2 2 0 0 0-2 2Z'/><path d='M16 18v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2'/></g></svg>
                    <svg class = 'copy-path-svg' onclick = '{$event}' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><path fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17'/></svg>
                    </div>
                    </div>";
                } else {
                    $_getURL = 'https://' . $_SERVER['SERVER_NAME'] . '/' . $id;
                    
                    $svg = "
                    <div class = 'preview' style = 'flex-direction:row;'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' class = 'dirb'>
                            <g class = 'dir' fill='none' stroke='currentColor' stroke-linejoin='round' stroke-width='2'>
                                <path stroke-linecap='round'
                                    d='M4 4v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.342a2 2 0 0 0-.602-1.43l-4.44-4.342A2 2 0 0 0 13.56 2H6a2 2 0 0 0-2 2Zm5 9h6m-6 4h3' />
                                <path d='M14 2v4a2 2 0 0 0 2 2h4' />
                            </g>
                    </svg>
                    <svg class = 'copy-path-svg' onclick = 'copyToBuff(`{$_getURL}`)' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><g fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><path d='M8 4v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.242a2 2 0 0 0-.602-1.43L16.083 2.57A2 2 0 0 0 14.685 2H10a2 2 0 0 0-2 2Z'/><path d='M16 18v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2'/></g></svg>
                    <svg class = 'copy-path-svg' onclick = '{$event}' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><path fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 0 0 4.561 21h14.878a2 2 0 0 0 1.94-1.515L22 17'/></svg>
                    </div>";
                }
                
                /*$svg = "
                <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' class = 'dirb'>
                            <g class = 'dir' fill='none' stroke='currentColor' stroke-linejoin='round' stroke-width='2'>
                                <path stroke-linecap='round'
                                    d='M4 4v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.342a2 2 0 0 0-.602-1.43l-4.44-4.342A2 2 0 0 0 13.56 2H6a2 2 0 0 0-2 2Zm5 9h6m-6 4h3' />
                                <path d='M14 2v4a2 2 0 0 0 2 2h4' />
                            </g>
                        </svg>";*/
                        
                        
                

                
                $time = 'Загружен в ' . date('d.m.Y H:i', $row['date_create']);
                
                
                
                //$copyPath = "<a href = '#' class = 'copy-path' onclick = 'copyToBuff(`{$_getURL}`)'>Скопировать ссылку</a>";
                $copyPath = '';
            } elseif($row['tpe'] == 1){
                $copyPath = "";
                
                $svg = "
                <div class = 'preview'>
                <svg class = 'editb' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'><g class = 'editbb' fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><path d='m16.474 5.408l2.118 2.117m-.756-3.982L12.109 9.27a2.118 2.118 0 0 0-.58 1.082L11 13l2.648-.53c.41-.082.786-.283 1.082-.579l5.727-5.727a1.853 1.853 0 1 0-2.621-2.621Z'/><path d='M19 15v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3'/></g></svg>
                </div>
                ";

                $event = "navTo(`edit?id={$id}`)";
                $time = 'Создана в ' . date('d.m.Y H:i', $row['date_create']);
                $name = str_replace('.fsst', '', rtrim(mb_strimwidth($row['filename'], 0, 30)));
            }
            
            $_fileRealID = $row['id'];
            
            $cards .= "
                <div class='card-dir'oncontextmenu='handleRightMouse(`fid-{$_FIDPAGE}`, `{$_fileRealID}`)'id='fid-{$_FIDPAGE}'>
                        {$svg}
                        <div class='dir-data'>
                            <h4 class='cfn'onclick = '{$event}'>{$name}</h4>
                            <h5 class='cfd'>{$size}</h5>
                            <h5 class='cfd'>{$time}</h5>
                            <h5 class='cfd'>{$time_update}</h5>
                            {$copyPath}
                        </div>
                    </div>
            ";
            
            $_FIDPAGE++;
        }

        if($cards == ''){
            $cards = '<h3 class = "nod">У вас нет файлов</h3>';
        }
        
        $_alias = [
            '|#FREE|' => (string)bytesConverter($CLOUD_DATA['storage']['max'] - $CLOUD_DATA['occuped']),
            '|#TOTAL|' => (string)bytesConverter($CLOUD_DATA['storage']['max']),
            '|#APPVER|' => APPVER,
            '|#APPVERDATE|' => APPVERREL,
            '|#CARDS|' => $cards,
            '|#DEAUTH|' => $button,
            '|#DIRID|' => 0,
        ];

        $_data = file_get_contents('patterns/_all.html');

        foreach ($_alias as $sym => $val) {
            $_data = str_replace($sym, $val, $_data);
        }

        return $_data;
    }
    
    function countBytes($path){
        $bytestotal = 0;
        $path = realpath($path);
        if($path!==false && $path!='' && file_exists($path)){
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal;
    }




?>