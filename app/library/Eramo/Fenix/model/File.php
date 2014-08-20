<?php 

class Fenix_File extends Model {
    
    const INFO_NAME = 'name';
    const INFO_SIZE = 'size';
    const INFO_TYPE = 'type';
    const INFO_HASH = 'hash';
    
    /**
     * @return Fenix_File
     */
    public static function factory($name = "file", $section = null){
        return parent::factory($name, $section);
    }
    
    /**
     * (non-PHPdoc)
     * @see Model::insert()
     */
    public function insert($item){
        throw new Exception("Método não disponível, use os métodos File->download e File->upload para trabalhar com arquivos.");
    }
    
    /**
     * (non-PHPdoc)
     * @see Model::update()
     */
    public function update($item){
        throw new Exception("Método não disponível, use os métodos File->download e File->upload para trabalhar com arquivos.");
    }
    
    /**
     * (non-PHPdoc)
     * @see Model::delete()
     */
    public function delete($id){
        
        $this->_lobDelete($id);
        
        $status = parent::delete($id);
        
        return $status;
    }
    
    /**
     * Cleanup unused files
     */
    public function cleanup(){

        // Remove registros não referenciados por nenhum campo em nenhum modelo
        $model = Model::factory("model");
        $dados = $model->prepareSelect(array('schema', 'name'))->query()->fetchAll();
        
        $hashes = array();
        
        foreach($dados as $modelo){
            $modelo = Model::factory($modelo['schema'].".".$modelo['name']);
        
            $fieldsFiles = array();
        
            foreach($modelo->getSections() as $section){
                $fields = $modelo->getFields( $section );
                foreach($fields as $field){
                    if(ModelConfig::checkType($field, ModelConfig::FILE) || ModelConfig::checkType($field, ModelConfig::IMAGE)){
                        $fieldsFiles[ $section->getName() ][] = $field->getNameDatabase();
                    }
                }
            }
        
            foreach ($fieldsFiles as $section => $fields){
                $modeloSection = Model::factory($modelo->getName(), $section);
        
                $recordsFile = $modeloSection->prepareSelect($fields)->query()->fetchAll();
        
                foreach($recordsFile as $recordFile){
                    foreach($recordFile as $columnFile){
                        $file = Util::utf8_decode(json_decode(Util::utf8_encode($columnFile), true));
                        if( isset($file['hash']) && strlen($file['hash']) > 0){
                            $hashes[] = $file['hash'];
                        }
                    }
                }
            }
        }
        
        // Só mantém na tabela de arquivos os que estão na lista de hashes referenciados por algum registro do sistema
        if(count($hashes) == 0){
            $hashes = array('000');
        }
        
        $select = Model::factory($this->getName())->prepareSelect(array('id'))->where("hash NOT IN ('".implode("' , '", $hashes)."')");
        
        $dados = $select->query()->fetchAll();
        
        foreach($dados as $toDelete){
            $this->delete($toDelete['id']);
        }
        
        $database = Util::getConfig(Util::CONFIG_DATABASE);
        
        // Especificamente para o postgres faz uma limpeza excluindo também todos
        // os loid que não tem referência na tabela de arquivos 
        if($database->dbtype == 'PDO_PGSQL'){
            $selectOid = Model::factory($this->getName())->prepareSelect(array(Util::getConfig( Util::CONFIG_FILE )->column_oid => 'oid::int'))->__toString();
            Table::getDefaultAdapter()->query("SELECT LO_UNLINK(oid) FROM pg_largeobject_metadata WHERE oid NOT IN ({$selectOid})");
        }
        
        return true;
    }
    
    /**
     * Remove um large object a partir dos registros no model
     * 
     * @param int[] $id
     */
    protected function _lobDelete($id){

        if(!$id){
            return;
        }
        
        $config = Util::getConfig( Util::CONFIG_DATABASE );
        $columnOid = Util::getConfig( Util::CONFIG_FILE )->column_oid;
        
        // Remove o Large object no banco de dados caso seja necessário
        if($config->dbtype == 'PDO_PGSQL'){
            
            if(!is_array($id)){
                $id = array($id);
            }
            
            $cols = array();
            $cols["loid"] = new Zend_Db_Expr("CASE WHEN (SELECT COUNT(oid) FROM pg_largeobject_metadata WHERE oid = ".$this->getTableName().".oid::oid ) > 0 THEN LO_UNLINK(".$columnOid."::oid) ELSE 0 END");
            
            $this->prepareSelect($cols)->where('id IN ('.implode(",", $id).')')->query()->fetchAll();
        }
        
        if($config->dbtype == 'PDO_SQLITE'){
            $row = $this->getTable()->fetchRow("id = " . $id)->toArray();
            $databaseDir = dirname(Util::getConfig(Util::CONFIG_DATABASE)->dbname) . "/files/";
            $filename = $databaseDir . $row[$columnOid];
            
            // remove o arquivo caso ele exista
            if(is_file($filename)){
                unlink($filename);
            }
        }
        
    }
    
    /**
     * Creates a LOB in database
     * 
     * @param String $filename Filename
     * 
     * @return int oid
     */
    protected function _lobCreate($filename){
        
        $ret = null;
        
        if( Util::getConfig(Util::CONFIG_DATABASE)->dbtype == 'PDO_PGSQL' ){
            
            $conn = $this->getTable()->getAdapter()->getConnection();
            
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $oid = $conn->pgsqlLOBCreate();
            
            $stream = $conn->pgsqlLOBOpen($oid, 'w');
            
            $local = fopen($filename, 'rb');
            stream_copy_to_stream($local, $stream);
            
            fclose($local);
            
            $stream = null;
            
            $ret = $oid;
            
        } else if( Util::getConfig(Util::CONFIG_DATABASE)->dbtype == 'PDO_SQLITE' ){
            
            $configFile = Util::getConfig(Util::CONFIG_FILE);
            
            $fieldOid = $this->getField( $configFile->column_oid );
            
            // Valida que a coluna oid seja do tipo texto e caiba um hash sha1
            if(!ModelConfig::checkType($fieldOid, ModelConfig::TEXT)
            || $fieldOid->getSize() < 40){
                throw new Exception(get_class($this) . " :: A coluna 'oid' precisa ser do tipo text e ter suporte a pelo menos 40 caracteres para usar arquivos no SQLITE. ");
            }
            
            // Salvamento do arquivo com nome baseado no hash
            
            $databaseDir = dirname(Util::getConfig(Util::CONFIG_DATABASE)->dbname) . "/files/";
            
            if(!is_dir($databaseDir)){
                mkdir($databaseDir, 0770, true);
            }
            
            $md5 = "md5";
            
            // Ajuste no md5 para o caso de ser um Mac OS
            if(trim(exec("uname -a | grep Darwin | wc -l")) > 0){
                $md5 = "md5 -r";
            }
            
            $hashName = exec("{$md5} {$filename}  | awk '{print $1}'");
            
            if(!is_file($databaseDir.$hashName)){
                $cmd = "mv {$filename} {$databaseDir}{$hashName}";
                exec($cmd);
            }
            
            $ret = $hashName;
            
        }
        
        if($ret === null){
            throw new Exception( get_class($this) . " :: _lobCreate not implemented for this dbtype!");;
        }
        
        return $ret;
    }
    
    /**
     * Dumps a lob to a file
     *  
     * @param String $oid
     * @param String $filename
     */
    protected function _lobDump($oid, $filename){
        
        $done = null;
        
        if( Util::getConfig(Util::CONFIG_DATABASE)->dbtype == 'PDO_PGSQL' ){
            
            $started = false;
            
            if(Model::transactionStarted() == false){
                Model::beginTransaction();
                $started = true;
            }
            
            $conn = $this->getTable()->getAdapter()->getConnection();
            
            $stream = $conn->pgsqlLOBOpen($oid, 'r');
            
            $local = fopen($filename, 'wb+');
            stream_copy_to_stream($stream, $local);
            
            fclose($local);
            
            $stream = null;
            
            if($started == true){
                Model::rollbackTransaction();
            }
            
            $done = true;
            
        } else if( Util::getConfig(Util::CONFIG_DATABASE)->dbtype == 'PDO_SQLITE' ){
            
            $databaseDir = dirname(Util::getConfig(Util::CONFIG_DATABASE)->dbname) . "/files/";
            
            if(is_file($databaseDir.$oid)){
                
                copy($databaseDir.$oid, $filename);
                
                if(!is_file($filename)){
                    throw new Exception( get_class($this) . " :: Impossível copiar arquivo '{$databaseDir}{$oid}' para '{$filename}'.");;
                }
                
            } else {
                throw new Exception( get_class($this) . " :: Impossível localizar arquivo em '{$databaseDir}{$oid}'.");;
            }
            
            
            $done = true;
            
        }

        if($done === null){
            throw new Exception( get_class($this) . " :: _lobDump not implemented for this dbtype!");;
        }
        
    }
    
    /**
     * Compacta um arquivo utilizando o bzip2 quando esse está disponível no sistema
     * 
     * @param String $filename
     * 
     * @return String $filename
     */
    protected function _uploadBzip2($filename){
        
        exec("bzip2 " . $filename);
        
        if(is_file($filename.".bz2")){
            $filename = $filename.".bz2";
        }
        
        return $filename;
    }
    
    /**
     * Insere um novo arquivo no sistema
     * 
     * @param String $hash
     * @param String $name
     * @param String $tmp_name
     * @param String $type
     * @param String $size
     * 
     * @return String JSON de identificação do arquivo no sistema
     */
    public function upload($hash, $name, $tmp_name, $type, $size){
    
        $config = Util::getConfig( Util::CONFIG_FILE );
        
        $started = false;
        
        // Se não houver uma transação iniciada inicia
        if(Model::transactionStarted() == false){
            Model::beginTransaction();
            $started = true;
        }
    
        $bzip2 = 0;
        
        $tmp = explode(".", $name);
        $ext = strtolower(end($tmp));
        
        $noCompress = array('zip', 'bz2', 'gzip', 'docx', 'xlsx', 'png', 'jpeg', 'jpg', 'gif');
        
        if(!in_array($ext, $noCompress)){
            $tmp_name_bzip2 = $this->_uploadBzip2($tmp_name);
            if($tmp_name_bzip2 != $tmp_name){
                $tmp_name = $tmp_name_bzip2;
                $bzip2 = 1;
            }
        }
        
        $filesize = filesize($tmp_name);
        
        $oid = $this->_lobCreate($tmp_name);
    
        // Info
        $info = array();
        $info[ self::INFO_NAME ] = $name;
        $info[ self::INFO_TYPE ] = $type;
        $info[ self::INFO_SIZE ] = $size;
        $info[ self::INFO_HASH ] = $hash;
    
        // Salva a referência ao objeto feito upload no banco
        $item = array();
        $item[ $config->column_hash ] = $hash;
        $item[ $config->column_oid ] = $oid;
        $item[ $config->column_info ] = json_encode(Util::utf8_encode($info));
        $item[ $config->column_bzip2 ] = $bzip2;
        $item[ $config->column_size ] = $filesize;
    
        parent::insert( $item );
    
        // Só finaliza a transação caso ela tenha sido iniciada nesse método
        if($started == true){
            Model::commitTransaction();
        }
    
        return $info;
    }
    
    protected function _downloadBzip2($filename){
        
        exec("bzip2 -c -d {$filename} > {$filename}.original");
        
        if(!is_file($filename.".original")) {
            throw new Exception("Bzip2 não disponível em {$bzip2} mas o arquivo está compactado.");
        }
        
        exec("/bin/bash -c 'mv {$filename}.original {$filename}'");
        
    }
    
    /**
     * Obtém o nome do arquivo temporário a partir do hash
     *
     * @param String $hash
     *
     * @return string Caminho absoluto para o arquivo no sistema
     */
    public function _downloadTiffPage($filename, $page = null){
        
        if($page !== null){
            $page = str_pad(Util::num2alpha($page), 3, "a", STR_PAD_LEFT);
            
            $filename = $filename."-".$page.".tif";
            
            if(!is_file($filename)){
                return false;
            }
        }
        
        return $filename;
    }
    
    /**
     * Obtém o nome do arquivo temporário a partir do hash
     *
     * @param String $hash
     *
     * @return string Caminho absoluto para o arquivo no sistema
     */
    public function _downloadPdfPage($filename, $page = null){
        
        $ret = false;
        
        if($page !== null){
            
            if($page !== null){
                $ret = $filename."-".$page.".png";
            } else {
                $ret = $filename.".png";
            }
            
            if(!is_file($ret)){
                $ret = $filename.".png";
            }
            
            if(!is_file($ret)){
                $ret = false;
            }
        }
        
        return $ret;
    }
    
    /**
     * Obtém o nome do arquivo temporário a partir do hash ou do array arquivo
     * 
     * @param String $info Json de informações do arquivo u hash
     * 
     * @return string Caminho absoluto para o arquivo no sistema
     */
    public function getFilename($info){
        
        $ret = false;
        
        $hash = $info;
        
        if( $info = json_decode($info, true) ){
            $hash = $info['hash'];
        }
        
        $isHttpUrl = stripos($hash, "http") === 0;
        
        if( $isHttpUrl === true ){
            
            $ret = dirname($_SERVER['SCRIPT_FILENAME']) . "/tmp/files/" . sha1($hash);
            
            // Tenta fazer um cache local do arquivo disponível na url
            if(!is_file($ret)){
                file_put_contents($ret, file_get_contents($hash));
            }
            
        } else {
            
            $config = Util::getConfig( Util::CONFIG_FILE );
            
            $file = "tmp/files/" . $hash;
            $fileInfo = "tmp/files/" . $hash . ".info";
            
            // Se não existirem os arquivos de cache cria o cache do sistema
            if(!is_file($file) || !is_file($fileInfo)){
                
                $record = current($this->prepareSelect(array())->where($config->column_hash . ' = ?', $hash)->query()->fetchAll());
                
                if(!$record || !isset($record[ $config->column_oid ])){
                    throw new Exception("Arquivo {$hash} não encontrado 1.");
                }
                
                $this->_lobDump($record[ $config->column_oid ], $file);
                
                if($record[ $config->column_bzip2 ] == '1'){
                    $this->_downloadBzip2($file);
                }
                
                $fp = fopen($fileInfo, 'w+');
                fwrite($fp, $record[ $config->column_info ]);
                fclose($fp);
            }
            
            if(!is_file($fileInfo)){
                throw new Exception("Metadados do arquivo {$hash} não encontrado 2.");
            }
            
            $ret = dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $file;
        }
        
        if(!is_file($ret)){
            throw new Exception("Arquivo {$hash} não encontrado 2.");
        }
        
        return $ret;
    }
    
    /**
     * Obtém os metadados de um arquivo a partir de seu hash
     * 
     * @param String $hash
     * @return string
     */
    public function getInfo($hash){
        
        $file = $this->getFilename($hash);
        $fileInfo = $file . ".info";
        
        $info = Util::utf8_decode(json_decode(file_get_contents($fileInfo), true));
        
        return $info;
    }
    
    /**
     * Obtém o número de páginas de um documento 
     * 
     * @param String $hash
     * 
     * @return number
     */
    public function getNumberPages($info){
        
        $ret = -1;
        
        $filename = $this->getFilename($info[ self::INFO_HASH ]);
        
        // Quando é um arquivo TIFF
        if(stripos($info[self::INFO_TYPE], "tif") !== false){
            
            $output = array();
            exec("tiffinfo {$filename} | grep 'Resolution' | wc -l", $output);
            
            $ret = intval(trim(implode("", $output)));
        }
        
        // Quando é um arquivo PDF
        if(stripos($info[self::INFO_TYPE], "pdf") !== false){
            
            if(is_file($filename.".pages")){
                $ret = file_get_contents($filename.".pages");
            } else {
                
                $output = array();
                exec("identify {$filename} | wc -l", $output);
            
                $ret = intval(trim(implode("", $output)));
            
                file_put_contents($filename.".pages", $ret);
            }
            
        }
        
        // Se for um arquivo de imagem qualquer define como uma página
        $tipos = array('jpeg', 'jpg', 'png', 'gif', 'bmp');
        foreach($tipos as $tipo){
            if(stripos($info[self::INFO_TYPE], $tipo) !== false){
                $ret = 1;
            }
        }
        
        return $ret;
    }
    
    /**
     * Faz o procedimento de download específico para arquivos TIFF
     * 
     * @param String $filename
     * @param String[] $info
     * @param boolean $force
     * @param int $page
     * 
     * @throws Fenix_Exception
     */
    protected function _downloadTiff($filename, $info, $force, $page = 0){
        
        // Obtém a quantidade de páginas
        $pages = $this->getNumberPages($info);
        
        if($page == null){
            $page = 0;
        }
        
        // Valida a quantidade de páginas do arquivo
        if(is_numeric($pages) && $pages >= 1){
        
            // Se ainda não existe o arquivo da primeira página do documento
            // faz a separação dos arquivos e converte para PNG
            if($this->_downloadTiffPage($filename, 0) === false){
        
                // Faz a divisão do arquivo em imagens distintas com o tiffsplit
                exec("tiffsplit {$filename} {$filename}-");
        
                // Converte cada uma das imagens em png usando o imagemagick
                exec("mogrify -format png {$filename}-a*");
        
            }
        
            // Caso haja a definição na requisição para mostrar uma página
            // específica faz com que o arquivo jpeg dessa página seja retornado
            // ao invés da imagem completa
        
            // Valida a existência da página e do arquivo
            if($page < $pages){
        
                $filenamePage = $this->_downloadTiffPage($filename, $page);
                $filenamePagePng = str_replace("tif", "png", $filenamePage);
                
                if(!is_file($filenamePagePng)){
                    throw Exception("Não foi possível localizar o arquivo da página {$page}/{$pages} convertido.");
                }
                
                $filesize = filesize($filenamePagePng);
                
                $name = $info[ self::INFO_NAME ];
                $name = preg_replace("/\.tiff?/", "-{$page}.png", $name);
                $name = preg_replace("/\.TIFF?/", "-{$page}.png", $name);
                
                header("Content-Type: " . "image/png");
                header("Content-Lenght: " . $filesize);
                header('Content-Disposition: '.($force == true ? 'attachment' : 'inline').'; filename="'.$name.'"');
                
                readfile($filenamePagePng);
                die();
                
            } else {
                throw new Fenix_Exception("Não foi possível encontrar a página ".($page+1)." no documento. O documento possui {$pages} páginas.");
            }
        
        }
        
    }
    
    /**
     * Faz o procedimento de download específico para arquivos PDF
     * 
     * @param String $filename
     * @param String[] $info
     * @param boolean $force
     * @param int $page
     * 
     * @throws Fenix_Exception
     */
    protected function _downloadPdf($filename, $info, $force, $page = 0){
        
        // Obtém a quantidade de páginas do documento
        $pages = $this->getNumberPages($info);
        
        if($page == null){
            $page = 0;
        }
        
        $page = intval($page);
        
        // Valida a quantidade de páginas do arquivo
        if(is_numeric($pages) && $pages >= 1){
        
            // Se ainda não existe o arquivo da primeira página do documento
            // faz a separação dos arquivos e converte para PNG
            if($this->_downloadPdfPage($filename, 0) === false){
        
                // Faz a divisão do arquivo em imagens distintas com o imagemagick
                exec("convert {$filename} {$filename}.png");
        
            }
        
            // Caso haja a definição na requisição para mostrar uma página
            // específica faz com que o arquivo jpeg dessa página seja retornado
            // ao invés da imagem completa
        
            // Valida a existência da página e do arquivo
            if($page < $pages){
        
                $filenamePage = $this->_downloadPdfPage($filename, $page);
                
                if(!is_file($filenamePage)){
                    throw new Exception("Não foi possível localizar o arquivo da página ".($page+1)."/{$pages} convertido.");
                }
                
                $filesize = filesize($filenamePage);
                
                $name = $info[ self::INFO_NAME ];
                $name = preg_replace("/\.pdf/", "-{$page}.png", $name);
                $name = preg_replace("/\.PDF/", "-{$page}.png", $name);
                
                header("Content-Type: " . "image/png");
                header("Content-Lenght: " . $filesize);
                header('Content-Disposition: '.($force == true ? 'attachment' : 'inline').'; filename="'.$name.'"');
                
                readfile($filenamePage);
                die();
                
            } else {
                throw new Fenix_Exception("Não foi possível encrontrar a página {$page} no documento. O documento possui {$pages} páginas.");
            }
        
        }
        
    }
    
    /**
     * Faz o resize de um arquivo no sistema
     *
     * @param String $filename
     * @param String[] $info
     * @param boolean $force Define se será forçado o download do arquivo
     * @param int $w Largura máxima da imagem, quando é uma, opcionalmente
     * @param int $h Altura máxima da imagem, quando é uma, opcionalmente
     *
     * @return String $filename Caminho do arquivo no sistema
     */
    protected function _downloadResize($filename, $info, $force, $w, $h){
        
        // Faz a divisão do arquivo em imagens distintas com o imagemagick
        $geometry = "";
        
        if($h){
            $geometry = "x".$h;
        }
        
        if($w){
            $geometry = $w.$geometry;
        }
        
        if($geometry != null){
            $filenameResized = $filename."-".$geometry.".png";
            
            if(!is_file($filenameResized)){
                exec("convert -resize {$geometry} {$filename} {$filenameResized}");
            }
            
            if(!is_file($filenameResized)){
                throw new Exception("Impossível converter tamanho da imagem.");
            }
            
            $info[ self::INFO_TYPE ] = "image/png";
            $info[ self::INFO_SIZE ] = filesize($filenameResized);
            
            $this->_downloadSendFile($filenameResized, $info, $force);
        }
    }
    
    /**
     * Faz o download de um arquivo no sistema
     * 
     * @param String $hash
     * @param boolean $force Define se será forçado o download do arquivo
     * @param int $page Página opcional para alguns formatos
     * @param int $w Largura máxima da imagem, quando é uma, opcionalmente
     * @param int $h Altura máxima da imagem, quando é uma, opcionalmente
     * 
     * @return String $filename Caminho do arquivo no sistema
     */
    public function download($hash, $force, $page, $w = false, $h = false){
    
        // Controle de cache dos arquivos
        $expires = 8*60*60*60*24*12;
        
        $tag = $hash."-".($w ? $w : '').($h ? 'x'.$h : '');
        
        header("Pragma: public", true);
        header("Cache-Control: maxage=".$expires, true);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT', true);
        header('Etag: '.$tag);
        
        if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $tag){
            header('Not Modified', true, 304);
            die();
        }
        
        // Faz o processo normal de download do arquivo
        $filename = $this->getFilename($hash);
        $info = $this->getInfo($hash);
        
        // Se há uma definição de página faz a paginação do conteúdo
        if($page !== null){

            // Quando é um arquivo TIFF
            if(stripos($info[self::INFO_TYPE], "tif") !== false){
                $this->_downloadTiff($filename, $info, $force, $page);
            }
            
            // Quando é um arquivo PDF
            if(stripos($info[self::INFO_TYPE], "pdf") !== false){
                $this->_downloadPdf($filename, $info, $force, $page);
            }
            
        }
        
        // Se há uma definição do tamanho da imagem e é uma imagem faz a conversão de tamanho
        if($w || $h){
            $this->_downloadResize($filename, $info, $force, $w, $h);
        }
        
        $this->_downloadSendFile($filename, $info, $force);
    }
    
    /**
     * Faz o download do arquivo
     * 
     * @param String $filename
     * @param String[] $info
     * @param boolean $force
     */
    protected function _downloadSendFile($filename, $info, $force){
        
        // Outros tipos de arquivos sequem o fluxo normal de download do arquivo
        header("Content-Type: " . $info[ self::INFO_TYPE ]);
        header("Content-Lenght: " . $info[ self::INFO_SIZE ]);
        header('Content-Disposition: '.($force == true ? 'attachment' : 'inline').'; filename="'.$info[ self::INFO_NAME ].'"');
        
        readfile($filename);
        die();
    }
}









