#!/usr/bin/php
<?php

require_once 'app/library/Eramo/Util/Util.php';

class Install {
    
    const MANIFEST_FILE = 'fenix-manifest';
    const PATCH_SQL_FILE = 'fenix-sql-patch';
    
    protected function listDatabaseSql(){
        
        $configGeneral = Util::getConfig(Util::CONFIG_GENERAL, true);
        
        $sqlModel = "SELECT md5(xml) as md5, 'model-'||schema||'.'||name AS name FROM ".$configGeneral['modelTable']." ORDER BY 2,1";
        $sqlTable = "SELECT MD5(ARRAY_TO_STRING(ARRAY(SELECT C.column_name||'-'||C.data_type FROM information_schema.columns AS C WHERE T.table_name = C.table_name ORDER BY C.column_name, C.data_type), '-')) AS md5, 'table-'||T.table_schema||'.'||T.table_name AS name FROM information_schema.tables AS T WHERE T.table_schema NOT IN ('pg_catalog', 'information_schema') GROUP BY T.table_schema, T.table_name ORDER BY 2,1";
        
        $sql = "SELECT * FROM ((".$sqlModel . ") UNION (" . $sqlTable.")) AS T";
        
        return $sql;
    }
    
    protected function listDatabase($filenamePatchSql = null){
        
        $tmpSql = tempnam("/tmp/", self::PATCH_SQL_FILE);
        
        if($filenamePatchSql !== null && is_file($filenamePatchSql)){
            $content = file_get_contents($filenamePatchSql);
        
            $content = str_replace("COMMIT TRANSACTION", "", $content);
            $content = str_replace("COMMIT", "", $content);
            file_put_contents($tmpSql, $content);
        }
        
        file_put_contents($tmpSql, ";\n".$this->listDatabaseSql().";\n\n", FILE_APPEND);
        
        $ret = $this->execSql($tmpSql);
        
        unlink($tmpSql);
        
        return $ret;
    }
    
    public function execSql($filename){
        $output = array();
        
        $config = Util::getConfig(Util::CONFIG_DATABASE, true);
        
        $filenameError = $filename."_error";
        
        if($config && isset($config['host'])){
            
            exec("export PGPASSWORD=".$config['password']." ; psql -tAF ' ' -U ".$config['username']." -h ".$config['host']." -f {$filename} 2> {$filenameError} ", $output);
            
            $errorContents = file_get_contents($filenameError);
            
            if(stripos($errorContents, "ERROR") !== false){
                echo "Error executing SQL patch: \n\n";
                echo $errorContents."\n";
                echo "\nUnable to proceed, aborting... \n\n";
                exec("rm -Rf " . $filenameError);
                exec("rm -Rf " . $filename);
                die(-1);
            }
            
        }
        
        
        return $output;
    }
    
    protected function listFiles(){
        $md5 = $this->_isDarwin() ? "md5 -r"  : "md5sum";
        $cmd = "find . -type f | grep -v './tmp/' | grep -v './log' | grep -v './".self::MANIFEST_FILE."' | grep -v svn | grep -v 'config\.json' | xargs {$md5}";
        exec($cmd, $output);
        
        return $output;
    }
    
    protected function parseData($output){
        $ret = array();
        
        foreach($output as $line){
            if(strlen(trim($line)) > 0){
                $line = preg_split("@([\s]+)@", trim($line));
                if(count($line) >= 2){
                    $ret[trim($line[1])] = trim($line[0]);
                }
            }
        }
        
        return $ret;
    }
    
    protected function readFile($filename){
        return explode("\n", file_get_contents($filename));
    }
    
    public function createList($filename = null, $filenamePatchSql = null){
        
        if(!$filename){
            $filename = self::MANIFEST_FILE;
        }
        
        $listFiles = $this->listFiles();
        $listDatabase = $this->listDatabase($filenamePatchSql);
        
        $contents = implode("\n", $listFiles);
        $contents.= "\n".implode("\n", $listDatabase);
        
        file_put_contents($filename, $contents);
        
        return count( $listFiles ) + count( $listDatabase );
    }
    
    public function checkList($installTmpDir = null, $filenamePatchSql = null){
        
        echo "\nChecking object list based on manifest... \n\n";
        
        $filename = ($installTmpDir !== null ? $installTmpDir."/" : "") .self::MANIFEST_FILE;
        $filenameCheck = "/tmp/".self::MANIFEST_FILE."-check";
        
        if(!is_file($filename)){
            echo "\tFile '".$filename."' was not found. Unable to check.\n\n";
            return;
        }
        
        // Cria uma nova lista dos arquivos atuais
        $this->createList( $filenameCheck, $filenamePatchSql );
        
        // Prepara as duas listas para checagem
        $list = $this->parseData( $this->readFile($filename) );
        $listCheck = $this->parseData( $this->readFile( $filenameCheck ) );
        
        // Caso seja a instalação o $installTmpDir deve vir com o diretório onde o patch/release
        // foi extraído para validação. Faz assim a leitura desses arquivos e um merge com o 
        // conteúdo atual do diretório para checar se o conteúdo final está compatível com o manifesto
        if($installTmpDir !== null){
            $filenameCheckInstall = "/tmp/".self::MANIFEST_FILE."-check-install";
            
            $currentDir = getcwd();
            
            chdir($installTmpDir);
            $this->createList( $filenameCheckInstall, $filenamePatchSql );
            
            $listCheckInstall = $this->parseData( $this->readFile($filenameCheckInstall) );
            
            // Coloca os arquivos que serão instalados na lista de checagem final
            foreach($listCheckInstall as $object => $objectMd5){
                $listCheck[$object] = $objectMd5;
            }
            
            chdir($currentDir);
        }
        
        // Checa que todos os objetos da lista do manifesto de publicação
        // estão com mesmo md5 na versão sendo checada
        $errors = array();
        foreach($list as $object => $objectMd5){
            if(!isset($listCheck[$object]) || $listCheck[$object] !== $objectMd5){
                $errors[$object] = true; 
            }
        }
        
        // Se houve erros pára a execução e mostra a lista dos objetos errados
        if(count($errors) > 0){
            echo "Error - Following objects do not match: \n\n";
            foreach ($errors as $object => $v){
                echo "\t{$object}\n";
            }
            echo "\nUnable to proceed, aborting... \n\n";
            exec("rm -Rf " . $installTmpDir);
            die(-1);
        }
        
        echo count($list) . " objects validated successfully!\n\n";
        
    }
    
    public function finish(){
        
        echo "Setting file permissions...\n";
        
        exec("chmod -R 777 tmp");
        exec("chmod -R 777 log");
        exec("chmod -R 777 app_tests");
        
        echo "Removing temporary files...\n";
        
        exec("rm -Rf sql");
        
        exec("rm -Rf tmp/cache/*");
        
        exec("rm -Rf tmp/files/*js*");
        exec("rm -Rf tmp/files/*css*");
        
        exec("rm -Rf " . self::MANIFEST_FILE . "*");
        
        exec("rm -Rf /tmp/" . self::MANIFEST_FILE . "*");
        
        echo "Flushing memcache server...\n";
        exec("echo 'flush_all' | nc localhost 11211");
        
        echo "\nDone!\n\n";
        
    }
    
    protected function _isDarwin(){
        return stripos(exec("uname"), "darwin") !== false;
    }
}

$Install = new Install();

global $argv;

function checkArg($value = null){
    global $argv;
    foreach (array_slice($argv, 1) as $arg){
        if($arg === $value || $value === null){
            return $arg;
        }
    }
    return false;
}

function getArg($name){
    global $argv;
    $ret = null;
    $started = false;
    foreach (array_slice($argv, 1) as $arg){
        
        if($arg === $name || $name === null){
            $started = true;
            continue;
        }
        
        if($started == true && substr($arg, 0, 1) != "-"){
            $ret[] = $arg;
        }
        
        if(substr($arg, 0, 1) == "-"){
            $started = false;
        }
    }
    return $ret;
}

// Criação do arquivo de manifesto para checagem em instalação posterior
if( checkArg('-c') ){
    echo "\nCreating object check list... ";
    echo $Install->createList() . ' objects listed.';
    echo "\nDone!\n\n";
    die(0);
}

// Ativação de um módulo
if( checkArg('-m') || checkArg('--module') ){
    $modules = getArg('-m');
    echo "\nActivating module(s): '" . implode("', '", $modules). "'";
    foreach($modules as $module){
        
        $dirname = "app/modules/{$module}";
        if(is_dir($dirname)){
            exec("rm " . $dirname);
        }
        exec("cd app/modules/ ; ln -s ../../../{$module}/{$module}/ {$module}");
        
        $dirname = "app_tests/modules/{$module}";
        if(is_dir($dirname)){
            exec("rm " . $dirname);
        }
        exec("cd app_tests/modules/ ; ln -s ../../../{$module}/{$module}_tests/ {$module}; chmod -R 777 ../../../{$module}/{$module}_tests/");
        
    }
    echo "\nDone!\n\n";
}

// Instalação de patch ou versão completa
if( checkArg('-p') || (checkArg(null) && !checkArg('-m')) ){
    $filename = null;
    foreach($argv as $arg){
        if($arg != "install.php" && is_file($arg)){
            $filename = $arg;
        }
    }
    if($filename === null){
        echo "\nThe patch filename must be provided.\n\n";
        die(-1);
    }
    echo "\nTesting file ".$filename."...\n";
    if(stripos($filename, ".tar.bz2") === false || !is_file($filename)){
        echo "\nThe file {$filename} doesn`t appear to be a valid patch or full installation.\n\nAborting.\n\n";
        die(-1);
    }
    
    $name = explode("/", $filename);
    $name = end($name);
    $name = str_replace(".tar.bz2", "", $name);
    $installTmpDir = "/tmp/" . $name;
    @mkdir($installTmpDir);
    exec("tar jxpf " . $filename . " -C " . $installTmpDir);
    
    $filenamePatchSql = $installTmpDir."/sql/".$name.".sql";
    if(!is_file($filenamePatchSql)){
        $filenamePatchSql = null;
    }
    
    $Install->checkList( $installTmpDir, $filenamePatchSql );
    
    // Instalação definitiva
    echo "\nInstalling file ".$filename."...\n";
    exec("tar jxpf " . $filename); 
    
    // Execução do patch
    echo "Executing patch file ".$filenamePatchSql."...\n";
    $Install->execSql($filenamePatchSql);
    
    // Limpeza
    exec("rm -Rf " . $installTmpDir);
    $Install->finish();
    
    die(0);
}

if(  checkArg('-h') || checkArg('--help') ){
    echo "\nusage: php -f install.php -- [-c] [-p filename]";
    echo "\n\n\t -c\t\t Create manifest file.";
    echo "\n\t -m module\t Activate a module (must be checked out on the same root than current installation).\n\n";
    echo "\n\t -p filename\t Apply patch or full version.\n\n";
    
    echo "default behavior is perform a manifest file check and cleanup\n";
    echo "the installation (when the extraction is done manually)\n\n";
    die(0);
}

echo "\nValidating and cleaning up local installation...\n";

// default behavior
$Install->checkList();
$Install->finish();
die(0);






