<?php

/**
 * Classe básica para representar de forma genérica uma página HTML
 * 
 * @copyright Eramo Software
 * @since 02/2013
 */
abstract class Page {
    
    /**
     * Lista de Atributos da view para ser repassada ao template
     * @var unknown_type
     */
    protected $_attributes = array();
    
    /**
     * Lista dos arquivos JS padrão no carregamento das páginas
     * @var String
     */
    protected $_jsDefault = array('fenix/jquery.js',
                                  'fenix/bootstrap.js',
                                  'fenix/bootstrap-modal.js',
                                  'fenix/bootstrap-modalmanager.js',
                                  'fenix/Fenix.js',
                                  'fenix/Fenix_Model.js',
                                  'fenix/date.format.js',
                                  'fenix/json2.js',
                                  'fenix/select2.js',
                                  'fenix/select2.defaults.js',
                                  'fenix/typeahead.js'
    );
    
    /**
     * Lista dos arquivos CSS padrão no carregamento das páginas
     * @var String
     */
    protected $_cssDefault = array('fenix/bootstrap.css',
                                   'fenix/bootstrap-modal.css',
                                   'fenix/fenix.css',
                                   'fenix/datepicker.css',
                                   'fenix/select2.css',
                                   'fenix/select2.defaults.css',
                                   'fenix/typeahead.css'
    );
    
    /**
     * Lista de Arquivos JS dessa página
     * @var unknown_type
     */
    protected $_js = array();
    
    /**
     * Lista de arquivos CSS dessa página
     * @var String[]
     */
    protected $_css = array();
    
    /**
     * Lista de comandos JS a serem realizados ao carregar a página
     * @var unknown_type
     */
    protected $_onload = array();
    
    /**
     * Título padrão de uma Página
     * @var String
     */
    protected $_title = "Default Title";
    
    /**
     * Método abstrato que precisa ser implementado
     * na classe concreta para definição do template
     */
    protected function _template(){
        return dirname(__FILE__).'/html/page.html.php';
    }
    
    /**
     * Constutor de uma página
     */
    public function __construct(){
        
        // Adiciona os arquivos CSS padrão das páginas
        foreach($this->_cssDefault as $css){
            $this->addCss($css);
        }

        // Adiciona os arquivos Javascript padrão das páginas
        foreach($this->_jsDefault as $js){
            $this->addJs($js);
        }
    }
    
    /**
     * Magic method to set a attribute's value
     * @param String $key
     * @param mixed $value
     */
    public function __set($key, $value){
        $this->_attributes[$key] = $value;
        return $this;
    }

    /**
     * Magic method to get a attribute's value
     * @param String $key
     * @param mixed $value
     */
    public function __get($key){
        return isset($this->_attributes[$key]) ? $this->_attributes[$key] : null;
    }
    
    /**
     * Adiciona um novo arquivo JS à lista dessa página
     * 
     * @param String $filename Following the format: <module>/<file>
     */
    public function addJs($filename){
        if(is_array($filename)){
            foreach($filename as $f){
                $this->addJs($f);
            }
        } else if(!is_array($filename)) {
            $this->_js[md5($filename)] = $filename;
        }
        return $this;
    }
    
    /**
     * Retorna a lista de arquivos JS incluídos nessa página
     * @return String[]
     */
    public function getJs(){
        return $this->_js;
    }
    
    /**
     * Retorna a lista de arquivos CSS incluídos nessa página
     * @return String[]
     */
    public function getCss(){
        return $this->_css;
    }
    
    /**
     * Percorre o array de linhas de javascript e adiciona ao escopo JS quando o início da linha
     * (geralmente uma chamada de function dentro do JS) bate com o nome de um arquivo JS no padrão
     * definido Module_Model.js dentro de app/modules/Module/view/js/
     * @param unknown $lines
     */
    protected function _parseJsLines($lines){
        foreach($lines as $line){
            
            $line = trim($line);
            
            $regExp = "/^([A-Z\_a-z0-9]+)_([A-Z\_a-z0-9]+)\./";
        
            if($line != null && preg_match($regExp, $line)){
        
                $matches = array();
                preg_match_all($regExp, $line, $matches);
        
                $filename = $matches[0][0]."js";
        
                $module = strtolower($matches[1][0]);
        
                if(is_file("app/modules/{$module}/view/js/{$filename}")){
                    $this->addJs($module."/".$filename);
                }
        
            }
        }
    }
    
    /**
     * Adiciona um novo comando JS a ser executado no carregamento da página
     * 
     * @param String $inlineJs
     * @param int $order
     * @return Page
     */
    public function addOnload($inlineJs, $order = false){
        if($order === false){
            $this->_onload[] = $inlineJs;
        } else {
            $this->_onload = array_merge(array_slice($this->_onload, 0, $order), array($inlineJs), array_slice($this->_onload, $order));
        }
        return $this;
    }
    
    /**
     * Adiciona um novo arquivo CSS à lista dessa página
     * 
     * @param String $filename Seguindo o formato: <modulo>/<arquivo>
     * @return Page
     */
    public function addCss($filename){
        if(is_array($filename)){
            foreach($filename as $f){
                $this->addCss($f);
            }
        } else if(!is_array($filename)) {
            $this->_css[md5($filename)] = $filename;
        }
        return $this;
    }
    
    /**
     * Prepara uma lista de comandos JS para serem executados
     * no momento do carregamento da página 
     * 
     * @param $documentReady boolean Define se o onload será executado depois de carregado o documento ou diretamente no JS
     * 
     * @throws Exception
     */
    protected function _prepareJsOnload($documentReady = true){
        
        $ret = null;
        
        $onload = $this->_prepareJsOnloadString($documentReady);
        
        if($onload !== null){
            
            $filename = 'tmp/files/'.md5($onload).'.js';
            
            if(!file_exists($filename)){
                $fp = fopen($filename, 'w+');
                fwrite($fp, $onload);
                fclose($fp);
                if(!file_exists($filename)){
                    throw new Exception(__CLASS__ . ": Unable to write onload file at " . $filename);
                }
            }
            
            $this->addJs($filename);
            
            $filename = explode("/", $filename);
            
            $ret = end($filename);
        }
        
        return $ret;
    }
    
    /**
     * Prepara a string de carregamento da tela em JS
     * @param String $documentReady
     * @return String
     */
    protected function _prepareJsOnloadString($documentReady = true){
        
        $ret = null;
        
        if( count($this->_onload) > 0 ){
            
            $ret = implode(";", $this->_onload).";";
            
            if($documentReady === true){
                $ret = "$(document).ready(function(){ ".$ret." });";
            }
            
        }
        
        return $ret;
    }
    
    /**
     * Define o nome real de um arquivo CSS ou JS no sistema de arquivos
     * 
     * @param String $file
     */
    protected function _prepareFilesJoinRealFilename($file){
        
        $modules = Eramo_Controller_Front::getInstance()->getModules();
        
        $tmp = explode(".", $file);
        $ext = end($tmp);
        
        // Se o arquivo não é acessível diretamente verifica se ele é de algum módulo
        if(!is_file($file)){
            $module = null;
            if(count(explode("/", $file)) > 1){
                list($module) = explode("/", $file);
            }
            foreach(array_reverse($modules) as $m){
                if($m == $module || $module === null){
                    $moduleDir = 'app/' . ($m != 'fenix' ? 'modules/' : '') . $m . '/view/' . $ext . '/';
                    // Quando não há módulo definido, usará o primeiro arquivo encontrado em um dos módulos
                    if($module === null){
                        $fileTmp = $moduleDir."/".$file; 
                        if(is_file($fileTmp)){
                            $file = $fileTmp;
                            break;
                        }
                    }
                    // QUando há módulo definido (padrão MODULO/ARQUIVO)
                    else {
                        $file = str_replace($m."/", $moduleDir, $file);
                    }
                }
            }
        }
        
        if(!is_file($file)){
            Util::error_handler(null, "Arquivo {$file} não existe.", '', '', '');
            $file = null;
        }
        
        return $file;
    }
    
    /**
     * Junta vários arquivos CSS ou JS e os prepara para serem
     * enviados de forma otimizada ao browser do usuário.
     * 
     * @param String[] $files
     * @param $onlyTest boolean Define se é somente um teste
     * 
     * @return String[]
     */
    protected function _prepareFilesJoin($files, $onlyTest = false){
        
        $dirname = "tmp/files/";
        
        $tmp = explode(".", current($files));
        $ext = end($tmp);
        
        $filename = "";
        
        // Última modificação dos arquivos
        $time = 0;
        
        foreach($files as $k => $file){
            $files[$k] = $this->_prepareFilesJoinRealFilename($file);
            if($files[$k] == null){
                unset($files[$k]);
                continue;
            }
            $time = max(filemtime($files[$k]), $time);
        }
        
        if($onlyTest === false && is_writable($dirname)){
            
            $filename = $dirname . md5( implode(",", array_keys($files)) ) . "." . $ext;
            
            // Se o arquivo de cache não existir ou o timestamp dele for inferior
            // ao maior timestamp dos arquivos sendo considerados cria/sobresceve
            // o arquivo de cache
            if( !is_file($filename) || $time > filemtime($filename) ){
            
                $fp = fopen($filename, "w+");
                
                foreach($files as $file){
                    fwrite($fp, file_get_contents($file));
                    
                    // Por segurança coloca um terminador na junção dos arquivos JS
                    if($ext == "js"){
                        fwrite($fp, ';');
                    }
                }
                
                fclose($fp);
                
                // Cria a versão compactada do arquivo de cache
                file_put_contents($filename.".gz", gzencode(file_get_contents($filename)));
            }
            
            $files = array( substr($filename, strrpos($filename, "/")+1) );
            
        }
        
        $v = null;

        // Ambiente DEV impede que os arquivos CSS e JS sejam usados em cache
        if( Util::isDev() ){
            
            $v = rand(0, 999999);
            
        }
        // Caso não seja DEV tenta localizar a informação de versão do sistema para
        // permitir que o cache seja feito com base na versão em produção
        else {
            
            $filenameVersion = dirname($_SERVER['SCRIPT_FILENAME']).'/version.txt';
            
            if(is_file($filenameVersion)){
                
                $version = file_get_contents( $filenameVersion );
                
                if($version !== false){
                    $version = explode("\n", $version);
                    $version = explode(":", $version[0]);
                    $v = trim($version[1]);
                }
            }
            
        }
        
        // Insere o sufixo nas url dos arquivos
        if($v !== null){
            foreach ($files as $key => $file){
                $files[$key] = $file."?v=".$v;
            }
        }
        
        
        return $files;
    }
    
    /**
     * Define a URL absoluta de todos os arquivos
     *  
     * @param String[] $files
     * 
     * @return String[]
     */
    protected function _prepareFilesUrl($files){
        
        $baseUrl = Util::getBaseUrl();
        
        foreach($files as $k => $file){
            $files[$k] = $baseUrl . $file;
        }
        
        return $files;
    }
    
    /**
     * Cria novos arquivos/diretórios na estrutura de testes do sistema
     * para corresponder aos arquivos JS sendo utilizados no sistema
     * 
     * Todos os diretórios arquivos são criados em /app_tests na raiz do sistema
     * fazendo-se um espelhamento do conteúdo do projeto em /app
     * 
     */
    protected function _prepareJsTests(){
        
        // Salva a lista de arquivos JS padrão do sistema (estão em toda as páginas)
        // para que o testador saiba quais arquivos adicionar diretamente ao escopo
        if(!is_file("app_tests/JavascriptRunRequired.js")){
            file_put_contents("app_tests/JavascriptRunRequired.js", "");
            foreach ($this->_jsDefault as $file){
                $filename = $this->_prepareFilesJoinRealFilename($file);
                file_put_contents("app_tests/JavascriptRunRequired.js", "require('../{$filename}');\n", FILE_APPEND);
            }
        }
        
        // Lista de arquivos requeridos (por padrão os arquivos que foram incluídos
        // no escopo antes do arquivo atual), caso sejam necessários outros o desenvolvedor
        // deve mudá-los manualmente no arquivo gerado
        $requires = array();
        
        // Percorre a lista de arquivos do sistema localizando todos os arquivos
        // e verificando se o correspondente de testes já foi criado
        foreach ($this->_js as $file){
            
            $filename = $this->_prepareFilesJoinRealFilename($file);
            
            // Caso já não esteja na lista de inclusão padrão inclui na lista de required
            if(!in_array($file, $this->_jsDefault)){
                $requires[] = $filename;
            }
            
            // Faz a criação/atualização do arquivo de testes, caso necessário
            if( is_file($filename) ){
                
                $filenameTest = preg_replace("|^app/|", "app_tests/", $filename);
                
                // Só gera o stub caso sejam arquivos dos módulos ou que /^Fenix(.*)\.js/
                $isModuleOrFenix = stripos($filenameTest, "/modules/") !== false || preg_match("/\/Fenix(.*)\.js$/", $filenameTest);
                
                // Só gera caso seja um objeto válido (no formato da expressão regular
                $objectName = explode("/", str_replace(".js", "", $filename));
                $objectName = end($objectName);
                $isValidObject = preg_match("/^([a-z\_]+)$/", $objectName) === 1;
                
                // Só gera caso não seja arquivo do jquery ou bootstrap
                $isNotJqueryOrBootstrap = stripos($objectName, "bootstrap") !== 0 && stripos($objectName, "jquery") !== 0; 
                
                // Só gera caso o nome do arquivo de teste seja válido
                $filenameTestFile = explode("/", str_replace(".js", "", $filenameTest));
                $filenameTestFile = end($filenameTestFile);
                
                $isValidFilenameTest = preg_match("/^([a-z\_]+)$/", $filenameTestFile) === 1; 
                
                if($isModuleOrFenix && $isValidObject && $isNotJqueryOrBootstrap){
                    
                    // Escreve o conteúdo no arquivo, caso necessário
                    $this->_prepareJsTestsGenerateStub($filenameTest, $requires);
    
                    // Gera os stubs para cada uma das funções no JS que esteja no padrão
                    $this->_prepareJsTestsGenerateStubModuleFunctions($filename, $requires);
                    
                }
                    
            }
        }
        
    }
    
    /**
     * Gera o conteúdo stub do arquivo de teste
     * 
     * @return String
     */
    protected function _prepareJsTestsGenerateStub($filenameTest, $requires){
                    
        $data = file_get_contents("app_tests/JavascriptStubModule.js");
        
        $data = $this->_prepareJsTestsGenerateStubReplace($data, $filenameTest, $requires);
        
        $this->_prepareJsTestsWriteFile($filenameTest, $data);
    }
    
    /**
     * Escreve o conteúdo num arquivo criando a hierarquia de diretórios ou arquivos, caso necessário
     * 
     * @param String $filename
     * @param String $data
     */
    protected function _prepareJsTestsWriteFile($filename, $data){
        
        $dirname = dirname($filename);
        
        // Cria o diretório se necessário
        if(!is_dir($dirname)){
            mkdir($dirname, 0777, true);
        }
        
        // Valida a criação do diretório ou existência do mesmo
        if(!is_dir($dirname)){
            Util::error_handler(null, "Não foi possível criar o diretório {$dirname}.", '', '', '');
        }
        
        // Só cria o arquivo caso já não exista
        if(!is_file($filename)){
            file_put_contents($filename, $data);
            chmod($filename, 0777);
        }
        
        // Valida a criação do arquivo ou existência do mesmo
        if(!is_file($filename)){
            Util::error_handler(null, "Não foi possível criar o arquivo {$filename}.", '', '', '');
        }
        
    }
    

    /**
     * Gera o conteúdo stub do arquivo de teste
     *
     * @return String
     */
    protected function _prepareJsTestsGenerateStubModuleFunctions($filename, $requires){
        
        $object = explode("/", str_replace(".js", "", $filename));
        $object = end($object);
        
        // Lista das funções localizadas
        $functionList = array();
        
        // Localiza as funções no padrão do sistema no arquivo
        $content = file($filename);
        $regExp = "/{$object}.([A-Za-z0-9\s\.]+)\=[\s]*function/";
        foreach ($content as $line){
            if(preg_match($regExp, $line)){
                $functionList[] = trim(trim($line), "{");
            }
        }
        
        // Determina o diretório de localização dos testes
        $dirname = preg_replace("|^app/|", "app_tests/", dirname($filename)) . "/" . $object . "/";
        
        $this->_prepareJsTestsGenerateStubFunctionCall($dirname, $functionList, $requires);
        
    }
    
    /**
     * Gera o conteúdo stub de uma chamada de função
     *
     * @return String
     */
    protected function _prepareJsTestsGenerateStubFunctionCall($dirname, $functionList, $requires, $variableValues = array()){
        
        // Gera o conteúdo dos stubs das funções
        $dataStub = file_get_contents("app_tests/JavascriptStubFunctionCall.js");
        
        foreach($functionList as $function){
        
            $name = explode("=", $function);
            $name = trim(current($name));
        
            $variables = array();
            $variablesNames = array();
        
            preg_match_all("/function\(([^\(]+)\)/", $function, $matches);
            if(count($matches[1]) > 0){
                foreach(explode(",", $matches[1][0]) as $variable){
        
                    $variable = trim($variable);
        
                    $variablesNames[] = $variable;
                    
                    $variableValue = isset($variableValues[ $variable ]) ? $variableValues[ $variable ] : "\"@TODO\"";
        
                    $variables[] = str_pad("", 8, " ") . "var {$variable} = {$variableValue};";
                }
                $variables = implode("\n", $variables);
            } else {
                $variables = str_pad("", 8, " ") . "// not defined";
            }
        
            $replaceExtra = array("__VARIABLES__" => $variables,
                                  "__EXECUTION__" => $name."(".implode(", ", $variablesNames).")"
            );
        
            // Cria o arquivo num subdiretório para cada objeto
            $filenameTest = $dirname . $name . ".js";
            
            // Só gera caso o nome do
            $filenameTestFile = explode("/", str_replace(".js", "", $filenameTest));
            $filenameTestFile = end($filenameTestFile);
            
            $isValidFilenameTest = preg_match("/^([a-z\_]+)$/", $filenameTestFile) === 1;
            
            if($isValidFilenameTest){
                $data = $this->_prepareJsTestsGenerateStubReplace($dataStub, $filenameTest, $requires, $replaceExtra);
            
                $this->_prepareJsTestsWriteFile($filenameTest, $data);
            }
        
        }
    }
    
    /**
     * Substitui o conteúdo do arquivo nas variáveis padrão dos stubs 
     * 
     * @param String $data
     * @param String $filenameTest
     * @param String[] $requires
     * @param String[] $replaceExtra
     * 
     * @return String
     */
    protected function _prepareJsTestsGenerateStubReplace($data, $filenameTest, $requires, $replaceExtra = array()){
        
        $file = explode("/", $filenameTest);
        $file = end($file);
        
        $requirePrefix = "require(app_root+'/";
        $requireSuffix = "');\n";
        
        $replace = array("__CLASSNAME__" => str_replace(".js", "", $file),
                         "__REQUIRES__" => $requirePrefix.implode($requireSuffix.$requirePrefix, $requires).$requireSuffix,
                         "__FILENAME__" => $file);
        
        if(count($requires) == 0){
            $replace["__REQUIRES__"] = "// no file is required"; 
        }
        
        $replace = array_merge($replace, $replaceExtra);
        
        foreach($replace as $search => $replace){
            $data = str_replace($search, $replace, $data);
        }
        
        return $data;
    }
    
    /**
     * Prepara a lista de arquivos JS compactando-os em um único arquivo 
     * 
     * @param $documentReady boolean Define se o onload será executado depois de carregado o documento ou diretamente no JS
     * @param $separateOnload boolean Define se o onload será criado num arquivo JS separado (útil na alteração de um registro no Form, por exemplo, onde o contéudo do arquivo JS completo seria toda vez diferente).
     * 
     * @return String[]
     */
    public function prepareJs($documentReady = true){
        
        // Adiciona os arquivos JS referenciados ao escopo quando encontrados
        $this->_parseJsLines($this->_onload);
        
        // Condições específicas para modo DEV
        if( Util::isDev() == true ){
            
            // Cria novos arquivos/diretórios na estrutura de testes do sistema
            // para corresponder aos arquivos JS sendo utilizados no sistema
            $this->_prepareJsTests();
        
            // Executa primeiro um teste para ser possível capturar erros
            $this->_prepareFilesJoin($this->_js, true);
            
            $this->_prepareJsErrors();
            $this->_prepareJsLogs();
        }

        // Faz a execução final
        $js = $this->_prepareFilesJoin($this->_js);
        
        // Define a URL direta do arquivo JS
        $js = $this->_prepareFilesUrl($js);
        
        return $js;
    }
    
    /**
     * Prepara a lista de arquivos CSS compactando-os em um único arquivo
     *
     * @return String[]
     */
    public function prepareCss(){
        
        $split = false;
        
        // Cria um regra de divisão do CSS para evitar problemas no IE 9
        // T2414 - Layout - Geral - Ace - Problema no IE9
        // https://sgp.eramo.com.br/sgp/tarefa/#T2414
        // http://blogs.msdn.com/b/ieinternals/archive/2011/05/14/internet-explorer-stylesheet-rule-selector-import-sheet-limit-maximum.aspx
        $matches = array();
        if( preg_match_all("/MSIE\s([0-9\.]+)/", $_SERVER['HTTP_USER_AGENT'], $matches) > 0){
            if(floatval($matches[1][0]) <= 9){
                $split = 2;
            }
        }
        
        if($split === false){
            $css = $this->_prepareFilesJoin($this->_css);
        } else {
            $css = array();
            for($i = 0; $i < count($this->_css); $i+=$split){
                $css = array_merge( $css, $this->_prepareFilesJoin( array_slice($this->_css, $i, $split) ) );
            }
        }
        
        $css = $this->_prepareFilesUrl($css);
        
        return $css;
    }
    
    /**
     * Verifica os cabeçalhos da requisição e adiciona um onload chamando
     * o procedimento padrão do framework para mostrar os erros ao usuário 
     */
    protected function _prepareJsErrors(){

        $errors = array();

        // Localiza os erros salvos nos headers
        foreach(headers_list() as $header){
            if(stripos($header, Util::HEADER_FENIX_ERROR) === 0){
                $errors[] = trim(str_replace(Util::HEADER_FENIX_ERROR.": ", "", $header));
            }
        }
        
        // Adiciona evento de mostrar erros ao usuário
        if(count($errors) > 0){
            $this->addOnload("Fenix._internals.serverCheckErrorShow(".json_encode(Util::utf8_encode($errors)).");");
        }
        
    }
    
    /**
     * Verifica os cabeçalhos da requisição e adiciona um onload chamando
     * o procedimento padrão do framework para mostrar os erros ao usuário 
     */
    protected function _prepareJsLogs(){

        $logs = array();

        // Localiza os erros salvos nos headers
        foreach(headers_list() as $header){
            if(stripos($header, Util::HEADER_FENIX_LOG) === 0){
                $logs[] = trim(str_replace(Util::HEADER_FENIX_LOG.": ", "", $header));
            }
        }
        
        // Adiciona evento de mostrar erros ao usuário
        if(count($logs) > 0){
            $this->addOnload("Fenix._internals.serverCheckLogShow(".json_encode($logs).");");
        }
        
    }
    
    /**
     * Preparação do procedimento de renderização da tela
     * 
     * @return stdClass Um objeto com as informação a serem utilizadas na renderização da tela
     */
    public function prepareRender($separateOnload = false){
        
        // Cria uma classe view para repasse das configurações ao template 
        $view = new stdClass();
        
        // Repassa todos os atributos da classe à view
        foreach($this->_attributes as $key => $val){
            $view->$key = $val;
        }
        
        $view->_baseUrl = Util::getBaseUrl();
        
        $this->addOnload('if(typeof Fenix != "undefined") Fenix.getBaseUrl = function(){ return "' . Util::getBaseUrl() . '"; };');
        
        $view->_css = $this->prepareCss();
        
        $view->_js = $this->prepareJs(true, $separateOnload);
        
        $view->_jsOnload = $this->_prepareJsOnloadString();
        
        $view->_title = $this->_title;
        
        return $view;
    }
    
    /**
     * Envia para o browser a renderização do template definido por _template()
     * 
     * @param boolean $separateOnload Determina se o onload será criado num arquovo JS separado (Útil na abertura de Form de alteração de registro)
     */
    public function render($separateOnload = false){
        
        $view = $this->prepareRender($separateOnload);
        
        require_once $this->_template();
    }

}

class PageField extends ModelField {

    /**
     * Nome do campo (igual ao campo na tabela)
     *
     * @var String
     */
    const NAME = 'name';
    /**
     * Título do campo (exibição na interface)
     * @var string
     */
    const TITLE = 'title';
    /**
     * Tipo do campo
     * @var string
     */
    const TYPE = 'type';
    /**
     * FK - Tabela ao qual esse campo se refere
     * @var string
     */
    const TABLE = 'table';
    /**
     * FK - Chave a qual esse campo se refere
     * @var string
     */
    const KEY = 'key';
    /**
     * FK - Campo na tabela destino usado como exibição da FK (combo)
     * @var string
     */
    const FIELD = 'field';
    /**
     * FK - Campo no modelo do qual esse campo depende
     * @var string
     */
    const DEPENDS = 'depends';
    /**
     * FK - Chave de dependência no modelo do campo atual (filtrado pelo valor do campo DEPENDS)
     * @var string
     */
    const DEPENDS_KEY = 'dependsKey';
    /**
     * Dados do campo (geralmente para uso no formatter)
     * @var string
     */
    const DATA = 'data';
    /**
     * Valor padrão do campo (utilizado pelo formatter para a definição necessária)
     * @var mixed
     */
    const DEFAULT_VALUE = 'default';
    /**
     * Tamanho do campo em campos de texto
     * @var int
     */
    const SIZE = 'size';
    /**
     * Descrição do campo, dependendo do formatter é exibida na interface do sistema
     * @var string
     */
    const DESCRIPTION = 'description';
    /**
     * Determina se o campo será ou não requerido para enviar o formulário
     * @var boolean
     */
    const REQUIRED = 'required';
    /**
     * Define se o campo será ou não pesquisável na grid
     * @var boolean
     */
    const SEARCHABLE = 'searchable';
    /**
     * Define se o campo será ou não obtido remotamente
     * @var boolean
     */
    const REMOTE = 'remote';
    /**
     * FK - Define se o registro na tabela à qual essa FK se refere pode ser inserida de forma simples (somente com o nome digitado diretamente no combo)
     * @var boolean
     */
    const INSERT = 'insert';
    /**
     * Define a largura do campo no form/grid
     * @var unknown
     */
    const WIDTH = 'width';
    /**
     * Define a linha na qual o campo será exibido no formulário
     * @var int
     */
    const ROW = 'row';
    /**
     * Define se o campo será editável, visível ou não no Form
     * @var char[e|v|n]
     */
    const FORM = 'form';
    /**
     * Define se o campo será editável, visível ou não no Grid
     * @var char[e|v|n]
     */
    const GRID = 'grid';
    /**
     * Define um formatter (função javascript) que será executada para definição do html no Form/Grid
     * @var unknown
     */
    const FORMATTER = 'formatter';

    /**
     * Nome do campo (igual ao campo na tabela)
     * @param String
     */
    public function setName($v){ return $this->__set(self::NAME, $v); }
    /**
     * Título do campo (exibição na interface)
     * @param string
     */
    public function setTitle($v){ return $this->__set(self::TITLE, $v); }
    /**
     * Tipo do campo
     * @param string
     */
    public function setType($v){ return $this->__set(self::TYPE, $v); }
    /**
     * FK - Tabela ao qual esse campo se refere
     * @param string
     */
    public function setTable($v){ return $this->__set(self::TABLE, $v); }
    /**
     * FK - Chave a qual esse campo se refere
     * @param string
     */
    public function setKey($v){ return $this->__set(self::KEY, $v); }
    /**
     * FK - Campo na tabela destino usado como exibição da FK (combo)
     * @param string
     */
    public function setField($v){ return $this->__set(self::FIELD, $v); }
    /**
     * FK - Campo no modelo do qual esse campo depende
     * @param string
     */
    public function setDepends($v){ return $this->__set(self::DEPENDS, $v); }
    /**
     * FK - Chave de dependência no modelo do campo atual (filtrado pelo valor do campo DEPENDS)
     * @param string
     */
    public function setDependsKey($v){ return $this->__set(self::DEPENDS_KEY, $v); }
    /**
     * Dados do campo (geralmente para uso no formatter)
     * @param string
     */
    public function setData($v){ return $this->__set(self::DATA, $v); }
    /**
     * Valor padrão do campo (utilizado pelo formatter para a definição necessária)
     * @param mixed
     */
    public function setDefault($v){ return $this->__set(self::DEFAULT_VALUE, $v); }
    /**
     * Tamanho do campo em campos de texto
     * @param int
     */
    public function setSize($v){ return $this->__set(self::SIZE, $v); }
    /**
     * Descrição do campo, dependendo do formatter é exibida na interface do sistema
     * @param string
     */
    public function setDescription($v){ return $this->__set(self::DESCRIPTION, $v); }
    /**
     * Determina se o campo será ou não requerido para enviar o formulário
     * @param boolean
     */
    public function setRequired($v){ return $this->__set(self::REQUIRED, $v); }
    /**
     * Define se o campo será ou não pesquisável na grid
     * @param boolean
     */
    public function setSearchable($v){ return $this->__set(self::SEARCHABLE, $v); }
    /**
     * FK - Define se o registro na tabela à qual essa FK se refere pode ser inserida de forma simples (somente com o nome digitado diretamente no combo)
     * @param boolean
     */
    public function setInsert($v){ return $this->__set(self::INSERT, $v); }
    /**
     * Define a largura do campo no form/grid
     * @param string
     */
    public function setWidth($v){ return $this->__set(self::WIDTH, $v); }
    /**
     * Define a linha na qual o campo será exibido no formulário
     * @param int
     */
    public function setRow($v){ return $this->__set(self::ROW, $v); }
    /**
     * Define se o campo será editável, visível ou não no Form
     * @param char[e|v|n]
     */
    public function setForm($v){ return $this->__set(self::FORM, $v); }
    /**
     * Define se o campo será editável, visível ou não no Grid
     * @param char[e|v|n]
     */
    public function setGrid($v){ return $this->__set(self::GRID, $v); }
    /**
     * Define um formatter (função javascript) que será executada para definição do html no Form/Grid
     * @param string
     */
    public function setFormatter($v){ return $this->__set(self::FORMATTER, $v); }

    /**
     * Nome do campo (igual ao campo na tabela)
     * @return String
     */
    public function getName(){ return $this->__get( self::NAME ); }
    /**
     * Título do campo (exibição na interface)
     * @return string
     */
    public function getTitle(){ return $this->__get( self::TITLE ); }
    /**
     * Tipo do campo
     * @return string
     */
    public function getType(){ return $this->__get( self::TYPE ); }
    /**
     * FK - Tabela ao qual esse campo se refere
     * @return string
     */
    public function getTable(){ return $this->__get( self::TABLE ); }
    /**
     * FK - Chave a qual esse campo se refere
     * @return string
     */
    public function getKey(){ return $this->__get( self::KEY ); }
    /**
     * FK - Campo na tabela destino usado como exibição da FK (combo)
     * @return string
     */
    public function getField($tableAliasReplace = true){ return parent::getField($tableAliasReplace); }
    /**
     * FK - Campo no modelo do qual esse campo depende
     * @return string
     */
    public function getDepends(){ return $this->__get( self::DEPENDS ); }
    /**
     * FK - Chave de dependência no modelo do campo atual (filtrado pelo valor do campo DEPENDS)
     * @return string
     */
    public function getDependsKey(){ return $this->__get( self::DEPENDS_KEY ); }
    /**
     * Dados do campo (geralmente para uso no formatter)
     * @return string
     */
    public function getData(){ return $this->__get( self::DATA ); }
    /**
     * Valor padrão do campo (utilizado pelo formatter para a definição necessária)
     * @return mixed
     */
    public function getDefault(){ return $this->__get( self::DEFAULT_VALUE ); }
    /**
     * Tamanho do campo em campos de texto
     * @return int
     */
    public function getSize(){ return $this->__get( self::SIZE ); }
    /**
     * Descrição do campo, dependendo do formatter é exibida na interface do sistema
     * @return string
     */
    public function getDescription(){ return $this->__get( self::DESCRIPTION ); }
    /**
     * Determina se o campo será ou não requerido para enviar o formulário
     * @return boolean
     */
    public function getRequired(){ return $this->__get( self::REQUIRED ); }
    /**
     * Define se o campo será ou não pesquisável na grid
     * @return boolean
     */
    public function getSearchable(){ return $this->__get( self::SEARCHABLE ); }
    /**
     * FK - Define se o registro na tabela à qual essa FK se refere pode ser inserida de forma simples (somente com o nome digitado diretamente no combo)
     * @return boolean
     */
    public function getInsert(){ return $this->__get( self::INSERT ); }
    /**
     * Define a largura do campo no form/grid
     * @return string
     */
    public function getWidth(){ return $this->__get( self::WIDTH ); }
    /**
     * Define a linha na qual o campo será exibido no formulário
     * @return int
     */
    public function getRow(){ return $this->__get( self::ROW ); }
    /**
     * Define se o campo será editável, visível ou não no Form
     * @return char[e|v|n]
     */
    public function getForm(){ return $this->__get( self::FORM ); }
    /**
     * Define se o campo será editável, visível ou não no Grid
     * @return char[e|v|n]
     */
    public function getGrid(){ return $this->__get( self::GRID ); }
    /**
     * Define um formatter (função javascript) que será executada para definição do html no Form/Grid
     * @return string
     */
    public function getFormatter(){ return $this->__get( self::FORMATTER ); }
    
}

/**
 * Uma classe simples e genérica para exibição de um template qualquer no sistema
 *
 * @copyright Eramo Software
 * @since 02/2013
 */
class View extends Page {
    
    /**
     * Título padrão da Página
     * @var String
     */
    protected $_title = "";
    
    /**
     * Template PHP para exibição
     * 
     * @var String
     */
    protected $_template = null; 
    
    /**
     * Marcado para determinar se já há uma página interna incluída
     * @var boolean
     */
    protected $_hasPage = false;
    
    /**
     * (non-PHPdoc)
     * @see Page::_template()
     */
    protected function _template(){
        return $this->_template;
    }
    
    /**
     * Cria um objeto view 
     * @param String $template Template PHP a ser criado
     * @throws Exception
     * @return View
     */
    public static function factory($template){
        return new self($template);
    }
    
    /**
     * 
     * @param String $template
     * @throws Exception
     */
    public function __construct($template, $cssDefault = true, $jsDefault = true){
        
        // Redefine o css padrão do sistema
        if($cssDefault === false){
            $this->_cssDefault = array();
        }
        
        // Redefine o js padrão do sistema
        if($jsDefault === false){
            $this->_jsDefault = array();
        }
        
        parent::__construct();
        
        // Se não conseguir abrir da view arquivo diretamente faz uma busca em locais
        // prováveis para tentar localizar o arquivo e redefinir o caminho antes de mostrar
        // um erro 
        if(!is_file($template)){
            
            $dispatcher = Eramo_Controller_Front::getInstance()->getDispatcher();
            
            $prefix = null;
            
            if($dispatcher['module'] == "default"){
                $prefix = "app/fenix/view/html/";
            } else {
                $prefix = "app/modules/".$dispatcher['module']."/view/html/";
            }
            
            if(is_file($prefix . $template)){
                $template = $prefix . $template;
            }
            
        }
        
        // Se ainda assim não for localizado o template lança a execção
        if(!is_file($template)){
            throw new Exception("Template '{$template}' not found");
        }
        
        // Define o template do objeto atual
        $this->_template = $template;
        
        $this->_urlPrefix = Util::getBaseUrl();
    }
    
    /**
     * Adiciona uma nova página interna (Grid ou Form) ao escopo de execução
     *
     * @param String $name Nome da página para referência dentro do JS
     * @param PageInternal $instancia de um objeto PageInternal
     * @param boolean $ignoreCss Ignora a inclusão dos arquivos CSS
     * @param boolean $ignoreJs Ignora a inclusão dos arquivos JS
     */
    public function addPage($name, PageInternal $page, $ignoreCss = false, $ignoreJs = false){
    
        $options = $page->prepareRenderInline();
        
        if($ignoreCss === false){
            $this->addCss($options['css']);
        }
        
        if($ignoreJs === false){
            $this->addJs($options['js']);
        }
        
        if($this->_hasPage == false){
            $this->addOnload(" View = {}; ");
            $this->addOnload(" ViewOnload = {}; ");
            $this->_hasPage = true;
        }
        
        $this->addOnload(" View['{$name}'] = {$options['options']}; ");
        $this->addOnload(" ViewOnload['{$name}'] = function(){ ".implode("; ", $options['onload'])." }; ");
        
    }
    
    
    public static function formatDate($v){
        return implode("/", array_reverse(explode("-", substr($v, 0, 10))));
    }
    
    public static function formatCurrency($v){
        return number_format($v, 2, ",", ".");
    }
}

















