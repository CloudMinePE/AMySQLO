<?php

declare(strict_types=1);

namespace xisrapilx\amysqlo;

use pocketmine\snooze\SleeperHandler;
use pocketmine\snooze\SleeperNotifier;
use Thread;
use xisrapilx\amysqlo\statement\CallableNamedPreparedStatement;
use xisrapilx\amysqlo\statement\CallableStatement;
use xisrapilx\amysqlo\thread\MySQLOThread;
use xisrapilx\amysqlo\thread\NewThread;
use xisrapilx\amysqlo\thread\queue\QueryReceiveQueue;
use xisrapilx\amysqlo\thread\queue\QuerySendQueue;
use xisrapilx\mysqlo\credentials\Credentials;
use xisrapilx\mysqlo\exception\MySQLOException;

class AsyncMySQL{

    /** @var SleeperHandler */
    private $sleeperHandler;

    /** @var Credentials */
    private $credentials;

    /** @var Thread[] */
    private $threads = [];

    /** @var QuerySendQueue */
    private $sendQueue;

    /** @var QueryReceiveQueue */
    private $recvQueue;

    /** @var SleeperNotifier */
    private $queryNotifier;

    /** @var int */
    private $queryCounter = -1;

    /** @var CallableStatement[] */
    private $statements = [];

    /**
     * AsyncMySQLO constructor.
     *
     * @param SleeperHandler $sleeperHandler
     * @param Credentials $credentials
     */
    public function __construct(SleeperHandler $sleeperHandler, Credentials $credentials){
        $this->credentials = $credentials;
        $this->sleeperHandler = $sleeperHandler;

        $this->sendQueue = new QuerySendQueue();
        $this->recvQueue = new QueryReceiveQueue();
        $this->queryNotifier = new SleeperNotifier();
        $sleeperHandler->addNotifier($this->queryNotifier, function() : void{
            $queryId = 0;
            $result = null;

            while($this->recvQueue->fetchResult($queryId, $result)){
                $query = $this->statements[$queryId];
                if($result instanceof MySQLOException && $query->getOnError() !== null){
                    $query->getOnError()($result);
                }elseif($query->getOnSuccess() !== null){
                    switch($query->getMode()){
                        case CallableStatement::MODE_SELECT_AND_MAP_SINGLE:
                            if(is_array($result))
                                $query->getOnSuccess()(...$result);
                            else
                                $query->getOnSuccess()($result);
                            break;

                        default:
                            $query->getOnSuccess()($result);
                    }
                }
            }
        });
    }

    /**
     * @param int $threadCount
     * @param string $threadClass
     * @param callable $onSuccessConnection <code>function(int $threadId) : void{}</code>
     * @param callable $onConnectionError <code>function(int $threadId, ConnectionException $exception) : void{}</code>
     * @param array $additionalParams Additional params to thread class constructor
     */
    public function start(int $threadCount = 2, string $threadClass = NewThread::class, callable $onSuccessConnection = null, callable $onConnectionError = null, ...$additionalParams) : void{
        for($i = 0; $i < $threadCount; ++$i){
            $connectionNotifier = new SleeperNotifier();
            $thread = new $threadClass($this->sendQueue, $this->recvQueue, $this->queryNotifier, $connectionNotifier, $this->credentials, ...$additionalParams);
            if($thread instanceof Thread && $thread instanceof MySQLOThread){
                $sleeperHandler = $this->sleeperHandler;
                $this->sleeperHandler->addNotifier($connectionNotifier, function() use($onConnectionError, $onSuccessConnection, $sleeperHandler, $connectionNotifier, $thread) : void{
                    $sleeperHandler->removeNotifier($connectionNotifier);

                    $exception = $thread->getConnectionException();
                    if($exception !== null){
                        if($onConnectionError !== null)
                            $onConnectionError($thread->getThreadId(), $exception);
                    }else{
                        if($onSuccessConnection !== null)
                            $onSuccessConnection($thread->getThreadId());
                    }
                });
                $thread->start();

                $this->threads[$thread->getThreadId()] = $thread;
            }
        }
    }

    public function stopAll(){
        $this->sendQueue->invalidate();
        foreach($this->threads as $thread){
            $thread->join();
        }

        $this->threads = [];
    }

    /**
     * @param string $query
     *
     * @return CallableStatement
     */
    public function query(string $query) : CallableStatement{
        ++$this->queryCounter;
        $query = new CallableStatement($this->queryCounter, $query, $this->sendQueue);

        $this->statements[$this->queryCounter] = $query;

        return $query;
    }

    /**
     * @param string $query
     *
     * @return CallableNamedPreparedStatement
     */
    public function prepare(string $query) : CallableNamedPreparedStatement{
        ++$this->queryCounter;
        $query = new CallableNamedPreparedStatement($this->queryCounter, $query, $this->sendQueue);

        $this->statements[$this->queryCounter] = $query;

        return $query;
    }
}