<?php

/**
 * Executa os testes JS e PHP do sistema
 */
class TestsController extends Eramo_Controller_Action {

    const CMD_PREFIX = "export PATH=/usr/local/bin:\$PATH ; ";
    
    protected $_singleTest = false;
    
    /**
     * Qualquer chamada à esse controller executa todos os testes do sistema
     * 
     * @param unknown $a
     * @param unknown $b
     */
    public function __call($a, $b){

        function _releaseProcessorNumber(){

            $ret = "2";

            if(stripos(shell_exec("uname"), 'darwin') !== false){
                $ret = trim(shell_exec("sysctl hw.ncpu | awk '{print $2}'"));
            }

            if(stripos(shell_exec("uname"), 'linux') !== false){
                $ret = trim(shell_exec("cat /proc/cpuinfo | grep processor | wc -l"));
            }

        // return $ret;
            return "4"; //padrão de nucleos usados
        }
        $grep = "";
        $jscores = _releaseProcessorNumber(); //2
        $phpcores = 4 * (int)$jscores; //16
        
        if(isset($_REQUEST['test'])){
            $grep = " | grep " . $_REQUEST['test'];
            $this->_singleTest = true;
        }

        if(isset($_REQUEST['cores'])){
            $jscores = $_REQUEST['cores'];
            $phpcores = $_REQUEST['cores'];
        }
        
        $timeStart = microtime(true);
        
        $dirnameTests = dirname($_SERVER['SCRIPT_FILENAME']) . "/app_tests/";
        
        chdir( $dirnameTests );
        
        $cmdPhpPgsql = "find -L . -type f | grep -v node_modules | grep php$ | grep -v ^./Php{$grep} | xargs -P {$phpcores} -I '{}' php -f PhpRun.php -- -d pgsql -f json '{}'";
        $cmdPhpSqlite = "find -L . -type f | grep -v node_modules | grep php$ | grep -v ^./Php{$grep} | xargs -P {$phpcores} -I '{}' php -f PhpRun.php -- -d sqlite -f json '{}'";
        
        // $cmdJs = "find -L . -type f | grep -v node_modules | grep .js$ | grep -v ^./Javascript{$grep} | sed 's/\.\///g'  | bash split_work.sh {$jscores} node JavascriptRun -";  // executa {$jscores} processos, cada um com somente um DOM e com 1/{$jscores} dos testes.
        //$cmdJs = find -L . -type f | grep -v node_modules | grep .js$ | grep -v ^./Javascript | echo "$(text=`cat`; count=`wc -l <<< "$text"` ; echo "$text" | xargs -L $(( $count / 2 + 1)) -P 2 node JavascriptRun )"; // codigo estranho
        $cmdJs = "find -L . -type f | grep -v node_modules | grep .js$ | grep -v ^./Javascript{$grep} | bash split_work.sh {$jscores} | xargs -L 1 -P {$jscores} node JavascriptRun"; // divide os testes entre {$jscores} processos e executa, criando {$jscores} DOM's
        
        print "<pre>";
        print "<strong>Executar:  <a href='./'>Todos</a>    |   <a href='./?php'>PHP</a>   |   <a href='./?js'>JS</a></strong>";
        print "\n".str_pad("", 100, "_")."\n\n";
        print "<strong>Caminho atual: ".getcwd()."</strong>\n\n";
        
        if(!isset($_REQUEST['js'])){
            print "<strong>Executando testes PHP (PGSQL):</strong>\n";
            print "\tComando: {$cmdPhpPgsql}\n\n";
            $this->_executeTestsPhp($cmdPhpPgsql);
            print "\n\n";
            
            print "<strong>Executando testes PHP (SQLITE):</strong>\n";
            print "\tComando: {$cmdPhpSqlite}\n\n";
            $this->_executeTestsPhp($cmdPhpSqlite);
            print "\n\n";
        }
        
        if(!isset($_REQUEST['php'])){
            print "<strong>Executando testes JS:</strong>\n";
            print "\tComando: {$cmdJs}\n\n";
            $this->_executeTestsJs($cmdJs);
            print "\n";
        }
        
        print "<strong>Testes executados em: " . round(microtime(true)-$timeStart, 3) . "s</strong>";
        print "</pre>";
        die();
    }
    
    /**
     * Executa os testes javascript baixando, caso necessário, os módulos do node.js necessários
     * 
     * @param String $cmd
     */
    protected function _executeTestsJs($cmd){
        $node_modules = getcwd()."/node_modules/";
        if(!is_dir($node_modules)){
            print "<strong>ERRO!</strong>\n";
            print "<strong>Módulos necessários não encontrados!</strong>\n";
            print "\tOs módulos necessários para a realização do teste com Node.js não foram encontrados.\n";
            print "\tPara corrigir, execute os seguinte comandos no terminal:\n\n";
            print "\t\t<code>cd ".getcwd()." && npm update && npm install iconv jasmine-node jsdom</code>\n\n";
            print "\tE rode os testes novamente.\n\n";
        }else{
            system(self::CMD_PREFIX.$cmd . " 2>&1 ");
        }
        
    }
    
    /**
     * Executa os testes PHP
     * 
     * @param String $cmd
     */
    protected function _executeTestsPhp($cmd){

        $startTime = microtime(true);
        
        $output = array();
        
        exec(self::CMD_PREFIX.$cmd, $output);
        
        if($this->_singleTest === true){
            print "<strong>Resultado da execução:</strong>\n\n";
//             echo htmlentities(implode("\n", $output)) . "\n\n";
        }
        
        $data = array();
        foreach($output as $line){
            $lineDecoded = json_decode($line, true);
            if($lineDecoded === null && strlen($line) > 0){
                $data['failedMessages']['__general'][] = $line;
            }
            foreach($lineDecoded as $key => $value){
                if(is_array($value)){
                    if(!$data[$key]){
                        $data[$key] = array();
                    }
                    $data[$key] = array_merge($data[$key], $value);
                }
                if(is_numeric($value)){
                    if(!isset($data[$key])){
                        $data[$key] = 0;
                    }
                    $data[$key] += $value;
                }
            }
        }
        
        print "Finished in ".(round(microtime(true)-$startTime, 3))." seconds\n";
        print $data['tests']. " tests, " . $data['asserts']. " assertions, " . $data['failed'] . " failures\n\n";
        
        if(count($data['failedMessages'])){
            print "Failures:";
            $i = 1;
            foreach($data['failedMessages'] as $testClass => $messages){
                foreach($messages as $msg){
                    print "\n\n" . $i++ . ") " . $testClass . ": ";
print $msg;
}
}
}
}
}


















