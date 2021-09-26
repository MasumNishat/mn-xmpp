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

use Illuminate\Console\Command;
use PhpPush\XMPP\Core\LaravelXMPPConnectionManager;

class phpPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phpPush:connect';

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        LaravelXMPPConnectionManager::getInstance(config('php-push-xmpp'))->listen();
        return 0;
    }
}
