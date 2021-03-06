<?php

namespace M6\Pushbot;

use M6\Pushbot\CommandInterface;
use M6\Pushbot\Deployment;
use M6\Pushbot\Exception\ResponseException;

class Pushbot
{
    private $commands = [];

    private $pool;

    private $persister;

    public function __construct(Deployment\Pool $pool, Deployment\Pool\PersisterInterface $persister)
    {
        $this->pool = $pool;
        $this->persister = $persister;
    }

    public function registerCommand(string $className) : Pushbot
    {
        if (!is_subclass_of($className, CommandInterface::class)) {
            throw new \Exception("Class '$className' does not implements ".CommandInterface::class);
        }

        $classPath = explode('\\', $className);
        $this->commands[strtolower(end($classPath))] = $className;

        return $this;
    }

    public function execute(string $user = null, string $commandName = null, array $args = []) : Response
    {
        try {
            $this->persister->load($this->pool);
            $response = $this->instanciateCommand($commandName)
                ->execute($this->pool, $user, $args);
            $this->persister->save($this->pool);

            return $response;
        } catch (ResponseException $e) {
            return $e->getResponse();
        } catch (\Exception $e) {
            $response = $this->help($commandName == 'help' ? reset($args) : null);
            $response->status = Response::FAILURE;

            return $response;
        }
    }

    public function help(string $commandName = null) : Response
    {
        try {
            return $this->instanciateCommand($commandName)
                ->help();
        } catch (\Exception $e) {
            return new Response(
                Response::SUCCESS,
                'Available commands:'.PHP_EOL
                .implode(
                    PHP_EOL,
                    array_map(
                        function (string $commandName) {
                            return '  '.$this->instanciateCommand($commandName)->help()->body;
                        },
                        array_keys($this->commands)
                    )
                )
            );
        }
    }

    private function instanciateCommand(string $commandName = null) : CommandInterface
    {
        $commandName = $commandName ? strtolower($commandName) : $commandName;

        if (!isset($this->commands[$commandName])) {
            throw new \Exception('unknown command');
        }

        return new $this->commands[$commandName];
    }

}
