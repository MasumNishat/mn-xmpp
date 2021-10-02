<?php
/*
 *
 *  // +-------------------------------------------------------------------------+
 *  // | Copyright (c) 2021 Al Masum Nishat                                      |
 *  // | All rights reserved.                                                    |
 *  // |                                                                         |
 *  // | Redistribution and use in source and binary forms, with or without      |
 *  // | modification, are permitted provided that the following conditions      |
 *  // | are met:                                                                |
 *  // |                                                                         |
 *  // | o Redistributions of source code must retain the above copyright        |
 *  // |   notice, this list of conditions and the following disclaimer.         |
 *  // | o Redistributions in binary form must reproduce the above copyright     |
 *  // |   notice, this list of conditions and the following disclaimer in the   |
 *  // |   documentation and/or other materials provided with the distribution.  |
 *  // | o The names of the authors may not be used to endorse or promote        |
 *  // |   products derived from this software without specific prior written    |
 *  // |   permission.                                                           |
 *  // |                                                                         |
 *  // | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS     |
 *  // | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT       |
 *  // | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR   |
 *  // | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT    |
 *  // | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,   |
 *  // | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT        |
 *  // | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,   |
 *  // | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY   |
 *  // | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT     |
 *  // | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE   |
 *  // | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.    |
 *  // |                                                                         |
 *  // +-------------------------------------------------------------------------+
 *  // | Author: Al Masum Nishat <masum.nishat21@gmail.com>                      |
 *  // +-------------------------------------------------------------------------+
 *
 */

namespace PhpPush\XMPP\Laravel\Commands;

use Cache;
use Illuminate\Console\Command;
use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;
use PhpPush\XMPP\Laravel\DataManager;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class phpPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phpPush:connect
    {--R|register : Register a new user in configured host}
    {--E|execute : Execute command on connected server}
    {options?* : Any extension has UI namespace execution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connect admin user with XMPP server and listen incoming';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws ReflectionException
     */
    function get_func_argNames($funcName) {
        if (is_array($funcName)) {
            $f = new ReflectionMethod($funcName[0], $funcName[1]);
        } else {
            $f = new ReflectionFunction($funcName);
        }
        $result = array();
        foreach ($f->getParameters() as $param) {
            $result[$param->name][0] = $param->getType()->getName();
            try {
                $result[$param->name][1] = $param->getDefaultValue();
            } catch (ReflectionException $e) {
                $result[$param->name][1] = '';
            }

        }
        return $result;
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws ReflectionException
     */
    public function handle()
    {
        if ($this->option('register')) {
            $conf = config('php-push-xmpp');
            $conf['regReq'] = true;
            $connection = LaravelXMPPConnectionManager::getInstance($conf);
            $connection->register($this->getData(DataManager::getInstance()->getData(DataManager::REG_FIELDS)));
        } elseif ($this->option('execute')){
            $options = $this->arguments()['options'];
            $extension = "PhpPush\\XMPP\\UI\\".$options[0];
            if (class_exists($extension)) {
                $app = $extension::getInstance();
                if (isset($options[1]) && method_exists($app, $options[1])) {
                    $params = $this->get_func_argNames([$extension, $options[1]]);
                    print_r($params);
                    foreach ($params as $param=>$type){
                        switch ($type[0]) {
                            case 'string':
                                $data[] = $this->ask("$param?")?: $type[1];
                                break;
                            case 'bool':
                                $data[] = $this->confirm("$param?") || ((is_bool($type[1]) && $type[1]));
                        }
                    }
                    print_r(call_user_func_array([$app, $options[1]], $data));
                } else {
                    $this->newLine();
                    $this->error("Method ($options[1]) not found in class (". "PhpPush\\XMPP\\UI\\".$extension. ") not found.");
                    $this->newLine();
                }
            } else {
                $this->newLine();
                $this->error("Class (". "PhpPush\\XMPP\\UI\\".$extension. ") not found.");
                $this->newLine();
            }
        } else {
            $connection = LaravelXMPPConnectionManager::getInstance(config('php-push-xmpp'));
            $connection->listen();
        }

        return 0;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function getData(array $fields): array
    {
        $ret = [];
        if ($this->showRegIns($fields)) {
            foreach ($fields as $key=>$field) {
                if (trim($field) == '') {
                    if (strtolower(trim($field)) == 'password') {
                        $ret[$key] = $this->secret("$key?");
                    } else {
                        $ret[$key] = $this->ask("$key?");
                    }

                }
            }
        }
        return $ret;
    }

    /**
     * @param $fields
     * @return bool
     */
    private function showRegIns($fields): bool
    {
        if (isset($fields['instructions']) && trim($fields['instructions']) != '') {
            return $this->confirm("Instruction: $fields[instructions]\n Proceed?");
        }
        return true;
    }
}
