<?php
/**
 * @package     robo-tasks
 * @subpackage  
 *
 * @copyright   Copyright (C) 2005 - 2015 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace redcomponent\robo;

use Robo\Result;
use Robo\Task\BaseTask;
use Robo\Common\ExecOneCommand;
use Robo\Contract\CommandInterface;
use Robo\Contract\TaskInterface;
use Robo\Contract\PrintedInterface;
use Robo\Exception\TaskException;
use Robo\Common\Timer;
use GuzzleHttp;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class WaitForSeleniumStandaloneServerTask
 * @package redcomponent\robo
 */
class WaitForSeleniumStandaloneServer extends BaseTask implements TaskInterface
{

    use Timer;

    /**
     * @var the domain and port to selenium hub site
     */
    private $url;

    public function __construct($url = null)
    {
        if (is_null($url)) {
            $this->url = 'http://localhost:4444/wd/hub/static/resource/hub.html';
        }
    }

    /**
     * @return Result
     */
    public function run()
    {
        $this->startTimer();
        $this->printTaskInfo('Waiting for Selenium Standalone server to launch');

        $timeout = 0;
        while(!$this->isUrlAvailable($this->url))
        {
            $this->getOutput()->write('.');

            // If selenium has not started after 15 seconds then die
            if ($timeout > 15)
            {
                $error = new Result(
                    $this,
                    1,
                    'Selenium server was not launched',
                    []
                );

                $error::$stopOnFail = true;

                return $error;
            }

            sleep(1);
            $timeout++;
        }

        $this->stopTimer();

        return new Result(
            $this,
            0,
            'Selenium server is ready',
            ['time' => $this->getExecutionTime()]
        );
    }

    private function isUrlAvailable($url)
    {
        try {
            $client = new GuzzleHttp\Client();

            $client->getEventDispatcher()->addListener('request.error', function(Event $event) {
                if ($event['response']->getStatusCode() != 200) {
                    // Stop other events from firing when you get stytus-code != 200
                    $event->stopPropagation();
                }
            });

            $res = $client->get($this->url);
            if (200 == $res->getStatusCode())
            {
                return true;
            }
            $this->say('selenium not yet ready');
            return false;
        }
        catch (Exception $e)
        {
            $this->say('selenium not yet ready');

            return false;
        }
        return true;
    }
}